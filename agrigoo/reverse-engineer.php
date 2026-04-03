<?php
// reverse-engineer.php
require_once 'vendor/autoload.php';

// Database configuration - agri_go_db
$dbHost = '127.0.0.1';
$dbName = 'agri_go_db';
$dbUser = 'root';
$dbPass = '';
$dbPort = 3306;

// Entity namespace and output directory
$namespace = 'App\\Entity';
$outputDir = __DIR__ . '/src/Entity';

// Repository namespace and output directory
$repositoryNamespace = 'App\\Repository';
$repositoryOutputDir = __DIR__ . '/src/Repository';

// Create output directories if they don't exist
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}
if (!is_dir($repositoryOutputDir)) {
    mkdir($repositoryOutputDir, 0777, true);
}

// Connect to the database
try {
    $pdo = new PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to database '$dbName' successfully!\n";
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// Get all tables
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Store table information for relationship processing
$tableInfo = [];
$foreignKeys = [];
$manyToManyTables = [];
$uniqueConstraints = [];

// First pass: collect table information, foreign keys, and unique constraints
foreach ($tables as $table) {
    // Skip migration tables
    if (strpos($table, 'migration') !== false || strpos($table, 'doctrine') !== false) {
        continue;
    }

    echo "Analyzing table structure: $table\n";

    // Convert table name to class name (keep plural as-is, just PascalCase)
    $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $table)));

    // Get table columns
    $stmt = $pdo->query("DESCRIBE `$table`");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get primary key
    $primaryKey = null;
    foreach ($columns as $column) {
        if ($column['Key'] === 'PRI') {
            $primaryKey = $column['Field'];
            break;
        }
    }

    $tableInfo[$table] = [
        'className' => $className,
        'columns'   => $columns,
        'primaryKey'=> $primaryKey
    ];

    // Get foreign keys
    try {
        $stmt = $pdo->query("
            SELECT
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE
                TABLE_SCHEMA = '$dbName' AND
                TABLE_NAME = '$table' AND
                REFERENCED_TABLE_NAME IS NOT NULL
        ");

        $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($fks as $fk) {
            $foreignKeys[$table][] = [
                'column'    => $fk['COLUMN_NAME'],
                'refTable'  => $fk['REFERENCED_TABLE_NAME'],
                'refColumn' => $fk['REFERENCED_COLUMN_NAME']
            ];
        }
    } catch (PDOException $e) {
        echo "Warning: Could not retrieve foreign keys for table $table: " . $e->getMessage() . "\n";
    }

    // Get unique constraints (for OneToOne relationships)
    try {
        $stmt = $pdo->query("
            SELECT
                COLUMN_NAME,
                INDEX_NAME
            FROM
                INFORMATION_SCHEMA.STATISTICS
            WHERE
                TABLE_SCHEMA = '$dbName' AND
                TABLE_NAME = '$table' AND
                NON_UNIQUE = 0 AND
                INDEX_NAME != 'PRIMARY'
        ");

        $uniques = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($uniques as $unique) {
            $uniqueConstraints[$table][] = [
                'column'    => $unique['COLUMN_NAME'],
                'indexName' => $unique['INDEX_NAME']
            ];
        }
    } catch (PDOException $e) {
        echo "Warning: Could not retrieve unique constraints for table $table: " . $e->getMessage() . "\n";
    }
}

// Identify potential ManyToMany join tables
foreach ($tables as $table) {
    if (strpos($table, 'migration') !== false || strpos($table, 'doctrine') !== false) {
        continue;
    }

    if (isset($foreignKeys[$table]) && count($foreignKeys[$table]) >= 2) {
        $columns    = $tableInfo[$table]['columns'];
        $nonFkColumns = count($columns);
        $fkColumns  = count($foreignKeys[$table]);

        if ($fkColumns >= 2 && $fkColumns >= ($nonFkColumns - 2)) {
            echo "Detected potential ManyToMany join table: $table\n";
            $manyToManyTables[$table] = [
                'foreignKeys' => $foreignKeys[$table]
            ];
        }
    }
}

// Second pass: generate entity classes with relationships
foreach ($tables as $table) {
    if (strpos($table, 'migration') !== false || strpos($table, 'doctrine') !== false || isset($manyToManyTables[$table])) {
        continue;
    }

    echo "Generating entity for table: $table\n";

    $className  = $tableInfo[$table]['className'];
    $columns    = $tableInfo[$table]['columns'];
    $primaryKey = $tableInfo[$table]['primaryKey'];

    // Build entity code
    $entityCode  = "<?php\n\n";
    $entityCode .= "namespace $namespace;\n\n";
    $entityCode .= "use Doctrine\\ORM\\Mapping as ORM;\n";
    $entityCode .= "use Doctrine\\Common\\Collections\\ArrayCollection;\n";
    $entityCode .= "use Doctrine\\Common\\Collections\\Collection;\n";
    $entityCode .= "use $repositoryNamespace\\{$className}Repository;\n\n";
    $entityCode .= "#[ORM\\Entity(repositoryClass: {$className}Repository::class)]\n";
    $entityCode .= "#[ORM\\Table(name: '$table')]\n";
    $entityCode .= "class $className\n{\n";

    // Add properties
    foreach ($columns as $column) {
        $fieldName   = $column['Field'];
        $fieldType   = mapMySQLTypeToPhpType($column['Type']);
        $doctrineType = mapMySQLTypeToDoctrineType($column['Type']);

        $isForeignKey     = false;
        $relationshipCode = "";

        if (isset($foreignKeys[$table])) {
            foreach ($foreignKeys[$table] as $fk) {
                if ($fk['column'] === $fieldName) {
                    $isForeignKey = true;
                    $refTableClassName = $tableInfo[$fk['refTable']]['className'];

                    // Check OneToOne (unique constraint)
                    $isOneToOne = false;
                    if (isset($uniqueConstraints[$table])) {
                        foreach ($uniqueConstraints[$table] as $unique) {
                            if ($unique['column'] === $fieldName) {
                                $isOneToOne = true;
                                break;
                            }
                        }
                    }

                    $propName = lcfirst($refTableClassName);

                    if ($isOneToOne) {
                        $relationshipCode .= "    #[ORM\\OneToOne(targetEntity: {$refTableClassName}::class, inversedBy: '" . lcfirst($className) . "')]\n";
                        $relationshipCode .= "    #[ORM\\JoinColumn(name: '$fieldName', referencedColumnName: '{$fk['refColumn']}', unique: true)]\n";
                        $relationshipCode .= "    private ?{$refTableClassName} \${$propName} = null;\n\n";

                        $relationshipCode .= "    public function get{$refTableClassName}(): ?{$refTableClassName}\n    {\n";
                        $relationshipCode .= "        return \$this->{$propName};\n    }\n\n";
                        $relationshipCode .= "    public function set{$refTableClassName}(?{$refTableClassName} \${$propName}): static\n    {\n";
                        $relationshipCode .= "        \$this->{$propName} = \${$propName};\n        return \$this;\n    }\n\n";
                    } else {
                        $relationshipCode .= "    #[ORM\\ManyToOne(targetEntity: {$refTableClassName}::class, inversedBy: '" . lcfirst($className) . "s')]\n";
                        $relationshipCode .= "    #[ORM\\JoinColumn(name: '$fieldName', referencedColumnName: '{$fk['refColumn']}')]\n";
                        $relationshipCode .= "    private ?{$refTableClassName} \${$propName} = null;\n\n";

                        $relationshipCode .= "    public function get{$refTableClassName}(): ?{$refTableClassName}\n    {\n";
                        $relationshipCode .= "        return \$this->{$propName};\n    }\n\n";
                        $relationshipCode .= "    public function set{$refTableClassName}(?{$refTableClassName} \${$propName}): static\n    {\n";
                        $relationshipCode .= "        \$this->{$propName} = \${$propName};\n        return \$this;\n    }\n\n";
                    }

                    break;
                }
            }
        }

        if (!$isForeignKey) {
            $nullable = $column['Null'] === 'YES' ? 'true' : 'false';

            if ($fieldName === $primaryKey) {
                $entityCode .= "    #[ORM\\Id]\n";
                $entityCode .= "    #[ORM\\GeneratedValue]\n";
                $entityCode .= "    #[ORM\\Column(type: '$doctrineType')]\n";
                $entityCode .= "    private ?{$fieldType} \${$fieldName} = null;\n\n";
            } else {
                $entityCode .= "    #[ORM\\Column(type: '$doctrineType', nullable: $nullable)]\n";
                $entityCode .= "    private ?{$fieldType} \${$fieldName} = null;\n\n";
            }

            // Getter
            $getterPrefix = ($fieldType === 'bool') ? 'is' : 'get';
            $entityCode .= "    public function {$getterPrefix}" . ucfirst($fieldName) . "(): ?{$fieldType}\n    {\n";
            $entityCode .= "        return \$this->{$fieldName};\n    }\n\n";

            // Setter
            $nullHint = $column['Null'] === 'YES' ? '?' : '';
            $entityCode .= "    public function set" . ucfirst($fieldName) . "({$nullHint}{$fieldType} \${$fieldName}): static\n    {\n";
            $entityCode .= "        \$this->{$fieldName} = \${$fieldName};\n        return \$this;\n    }\n\n";
        } else {
            $entityCode .= $relationshipCode;
        }
    }

    // Add OneToMany inverse relationships
    foreach ($tables as $otherTable) {
        if (isset($foreignKeys[$otherTable]) && !isset($manyToManyTables[$otherTable])) {
            foreach ($foreignKeys[$otherTable] as $fk) {
                if ($fk['refTable'] === $table) {
                    $otherClassName = $tableInfo[$otherTable]['className'];
                    $isOneToOne = false;

                    if (isset($uniqueConstraints[$otherTable])) {
                        foreach ($uniqueConstraints[$otherTable] as $unique) {
                            if ($unique['column'] === $fk['column']) {
                                $isOneToOne = true;
                                break;
                            }
                        }
                    }

                    if ($isOneToOne) {
                        $propName = lcfirst($otherClassName);
                        $entityCode .= "    #[ORM\\OneToOne(targetEntity: {$otherClassName}::class, mappedBy: '" . lcfirst($className) . "')]\n";
                        $entityCode .= "    private ?{$otherClassName} \${$propName} = null;\n\n";

                        $entityCode .= "    public function get{$otherClassName}(): ?{$otherClassName}\n    {\n";
                        $entityCode .= "        return \$this->{$propName};\n    }\n\n";
                        $entityCode .= "    public function set{$otherClassName}(?{$otherClassName} \${$propName}): static\n    {\n";
                        $entityCode .= "        \$this->{$propName} = \${$propName};\n        return \$this;\n    }\n\n";
                    } else {
                        $collectionVar = lcfirst($otherClassName) . 's';
                        $singularVar   = lcfirst($otherClassName);

                        $entityCode .= "    #[ORM\\OneToMany(targetEntity: {$otherClassName}::class, mappedBy: '" . lcfirst($className) . "')]\n";
                        $entityCode .= "    private Collection \${$collectionVar};\n\n";

                        $entityCode .= "    /**\n     * @return Collection<int, {$otherClassName}>\n     */\n";
                        $entityCode .= "    public function get" . ucfirst($collectionVar) . "(): Collection\n    {\n";
                        $entityCode .= "        if (!\$this->{$collectionVar} instanceof Collection) {\n";
                        $entityCode .= "            \$this->{$collectionVar} = new ArrayCollection();\n        }\n";
                        $entityCode .= "        return \$this->{$collectionVar};\n    }\n\n";

                        $entityCode .= "    public function add" . ucfirst($singularVar) . "({$otherClassName} \${$singularVar}): static\n    {\n";
                        $entityCode .= "        if (!\$this->get" . ucfirst($collectionVar) . "()->contains(\${$singularVar})) {\n";
                        $entityCode .= "            \$this->get" . ucfirst($collectionVar) . "()->add(\${$singularVar});\n        }\n";
                        $entityCode .= "        return \$this;\n    }\n\n";

                        $entityCode .= "    public function remove" . ucfirst($singularVar) . "({$otherClassName} \${$singularVar}): static\n    {\n";
                        $entityCode .= "        \$this->get" . ucfirst($collectionVar) . "()->removeElement(\${$singularVar});\n";
                        $entityCode .= "        return \$this;\n    }\n\n";
                    }
                }
            }
        }
    }

    // Add ManyToMany relationships
    foreach ($manyToManyTables as $joinTable => $joinInfo) {
        $fks = $joinInfo['foreignKeys'];
        $isPartOfRelationship = false;
        $otherTableName = null;
        $thisTableFk    = null;
        $otherTableFk   = null;

        foreach ($fks as $fk) {
            if ($fk['refTable'] === $table) {
                $isPartOfRelationship = true;
                $thisTableFk = $fk;
                foreach ($fks as $otherFk) {
                    if ($otherFk['refTable'] !== $table) {
                        $otherTableName = $otherFk['refTable'];
                        $otherTableFk   = $otherFk;
                        break;
                    }
                }
                break;
            }
        }

        if ($isPartOfRelationship && $otherTableName && $thisTableFk && $otherTableFk) {
            $otherTableClassName = $tableInfo[$otherTableName]['className'];
            $collectionVar = lcfirst($otherTableClassName) . 's';
            $singularVar   = lcfirst($otherTableClassName);

            $entityCode .= "    #[ORM\\ManyToMany(targetEntity: {$otherTableClassName}::class, inversedBy: '" . lcfirst($className) . "s')]\n";
            $entityCode .= "    #[ORM\\JoinTable(\n";
            $entityCode .= "        name: '$joinTable',\n";
            $entityCode .= "        joinColumns: [new ORM\\JoinColumn(name: '{$thisTableFk['column']}', referencedColumnName: '{$thisTableFk['refColumn']}')],\n";
            $entityCode .= "        inverseJoinColumns: [new ORM\\JoinColumn(name: '{$otherTableFk['column']}', referencedColumnName: '{$otherTableFk['refColumn']}')]\n";
            $entityCode .= "    )]\n";
            $entityCode .= "    private Collection \${$collectionVar};\n\n";

            $entityCode .= "    /**\n     * @return Collection<int, {$otherTableClassName}>\n     */\n";
            $entityCode .= "    public function get" . ucfirst($collectionVar) . "(): Collection\n    {\n";
            $entityCode .= "        if (!\$this->{$collectionVar} instanceof Collection) {\n";
            $entityCode .= "            \$this->{$collectionVar} = new ArrayCollection();\n        }\n";
            $entityCode .= "        return \$this->{$collectionVar};\n    }\n\n";

            $entityCode .= "    public function add" . ucfirst($singularVar) . "({$otherTableClassName} \${$singularVar}): static\n    {\n";
            $entityCode .= "        if (!\$this->get" . ucfirst($collectionVar) . "()->contains(\${$singularVar})) {\n";
            $entityCode .= "            \$this->get" . ucfirst($collectionVar) . "()->add(\${$singularVar});\n        }\n";
            $entityCode .= "        return \$this;\n    }\n\n";

            $entityCode .= "    public function remove" . ucfirst($singularVar) . "({$otherTableClassName} \${$singularVar}): static\n    {\n";
            $entityCode .= "        \$this->get" . ucfirst($collectionVar) . "()->removeElement(\${$singularVar});\n";
            $entityCode .= "        return \$this;\n    }\n\n";
        }
    }

    $entityCode .= "}\n";

    // Write entity file
    $filePath = "$outputDir/$className.php";
    file_put_contents($filePath, $entityCode);
    echo "  -> Generated entity: $filePath\n";

    // Generate corresponding Repository
    $repoCode  = "<?php\n\n";
    $repoCode .= "namespace $repositoryNamespace;\n\n";
    $repoCode .= "use $namespace\\{$className};\n";
    $repoCode .= "use Doctrine\\Bundle\\DoctrineBundle\\Repository\\ServiceEntityRepository;\n";
    $repoCode .= "use Doctrine\\Persistence\\ManagerRegistry;\n\n";
    $repoCode .= "/**\n * @extends ServiceEntityRepository<{$className}>\n */\n";
    $repoCode .= "class {$className}Repository extends ServiceEntityRepository\n{\n";
    $repoCode .= "    public function __construct(ManagerRegistry \$registry)\n    {\n";
    $repoCode .= "        parent::__construct(\$registry, {$className}::class);\n    }\n}\n";

    $repoFilePath = "$repositoryOutputDir/{$className}Repository.php";
    file_put_contents($repoFilePath, $repoCode);
    echo "  -> Generated repository: $repoFilePath\n";
}

echo "\nDone! Entity and Repository generation complete.\n";
echo "Now run: php bin/console make:entity --regenerate\n";

// Helper functions
function mapMySQLTypeToPhpType($mysqlType)
{
    if (strpos($mysqlType, 'tinyint(1)') !== false) return 'bool';
    if (strpos($mysqlType, 'int') !== false)         return 'int';
    if (strpos($mysqlType, 'float') !== false || strpos($mysqlType, 'double') !== false || strpos($mysqlType, 'decimal') !== false) return 'float';
    if (strpos($mysqlType, 'datetime') !== false || strpos($mysqlType, 'timestamp') !== false) return '\DateTimeInterface';
    if (strpos($mysqlType, 'date') !== false)        return '\DateTimeInterface';
    return 'string';
}

function mapMySQLTypeToDoctrineType($mysqlType)
{
    if (strpos($mysqlType, 'tinyint(1)') !== false) return 'boolean';
    if (strpos($mysqlType, 'int') !== false)         return 'integer';
    if (strpos($mysqlType, 'float') !== false)       return 'float';
    if (strpos($mysqlType, 'double') !== false || strpos($mysqlType, 'decimal') !== false) return 'decimal';
    if (strpos($mysqlType, 'datetime') !== false || strpos($mysqlType, 'timestamp') !== false) return 'datetime';
    if (strpos($mysqlType, 'date') !== false)        return 'date';
    if (strpos($mysqlType, 'time') !== false)        return 'time';
    if (strpos($mysqlType, 'text') !== false)        return 'text';
    if (strpos($mysqlType, 'blob') !== false || strpos($mysqlType, 'binary') !== false) return 'blob';
    return 'string';
}
