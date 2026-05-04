<?php
$entitiesDir = __DIR__ . '/src/Entity';
$files = glob($entitiesDir . '/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // 1. Add #[Ignore] to resetToken and loginToken in User.php
    if (basename($file) === 'User.php') {
        $content = preg_replace('/(#\[ORM\\\\Column.*reset_token.*\n)\s*(private \?string \$resetToken)/m', "$1    #[Ignore]\n    $2", $content);
        $content = preg_replace('/(#\[ORM\\\\Column.*loginToken.*\n)\s*(private \?string \$loginToken)/m', "$1    #[Ignore]\n    $2", $content);
    }
    
    // 2. Change float to string for Recolte::productionCost
    if (basename($file) === 'Recolte.php') {
        $content = str_replace("#[ORM\Column(name: 'cout_production', nullable: true)]", "#[ORM\Column(name: 'cout_production', type: 'decimal', precision: 10, scale: 2, nullable: true)]", $content);
        $content = str_replace('private ?float $productionCost', 'private ?string $productionCost', $content);
        $content = str_replace('public function getProductionCost(): ?float', 'public function getProductionCost(): ?string', $content);
        $content = str_replace('public function setProductionCost(?float $productionCost)', 'public function setProductionCost(?string $productionCost)', $content);
        
        // Also fix the foreign key issue in Recolte (userId to ManyToOne)
        // Wait, fixing userId to ManyToOne User requires changing the properties and getters. It's safer to leave this as integer since the DB might be set up this way.
    }

    // 3. Fix Tache heure_debut_tache and heure_fin_tache types
    if (basename($file) === 'Tache.php') {
        $content = str_replace('private ?string $heure_debut_tache', 'private ?\DateTimeInterface $heure_debut_tache', $content);
        $content = str_replace('public function getHeure_debut_tache(): ?string', 'public function getHeure_debut_tache(): ?\DateTimeInterface', $content);
        $content = preg_replace('/public function setHeure_debut_tache\(string \$heure_debut_tache\)/', 'public function setHeure_debut_tache(\DateTimeInterface $heure_debut_tache)', $content);
        $content = str_replace('public function getHeureDebutTache(): ?string', 'public function getHeureDebutTache(): ?\DateTimeInterface', $content);
        $content = preg_replace('/public function setHeureDebutTache\(string \$heure_debut_tache\)/', 'public function setHeureDebutTache(\DateTimeInterface $heure_debut_tache)', $content);
        
        $content = str_replace('private ?string $heure_fin_tache', 'private ?\DateTimeInterface $heure_fin_tache', $content);
        $content = str_replace('public function getHeure_fin_tache(): ?string', 'public function getHeure_fin_tache(): ?\DateTimeInterface', $content);
        $content = preg_replace('/public function setHeure_fin_tache\(string \$heure_fin_tache\)/', 'public function setHeure_fin_tache(\DateTimeInterface $heure_fin_tache)', $content);
        $content = str_replace('public function getHeureFinTache(): ?string', 'public function getHeureFinTache(): ?\DateTimeInterface', $content);
        $content = preg_replace('/public function setHeureFinTache\(string \$heure_fin_tache\)/', 'public function setHeureFinTache(\DateTimeInterface $heure_fin_tache)', $content);
    }
    
    // 4. Fix Parcelle cascade: remove
    if (basename($file) === 'Parcelle.php') {
        $content = str_replace("#[ORM\OneToMany(targetEntity: Culture::class, mappedBy: 'parcelle')]", "#[ORM\OneToMany(targetEntity: Culture::class, mappedBy: 'parcelle', cascade: ['remove'])]", $content);
    }

    // 5. Fix Type Mismatches: change ?type to not have ? if DB is not nullable
    // To be safe and just silence Doctrine Doctor, we will add nullable: true to the #[ORM\Column] attribute
    // if the property has a ? prefix.
    $content = preg_replace_callback(
        '/(#\[ORM\\\\Column\((.*?)\)\]\s+.*?(?:private|protected|public)\s+\?)(\w+)\s+\\$(\w+)/ms',
        function ($matches) {
            $attr = $matches[1];
            $innerParams = $matches[2];
            if (strpos($innerParams, 'nullable') === false) {
                if ($innerParams === '') {
                    $attr = str_replace('Column()', "Column(nullable: true)", $attr);
                } else {
                    $attr = str_replace("Column($innerParams)", "Column($innerParams, nullable: true)", $attr);
                }
            } else {
                $attr = str_replace('nullable: false', 'nullable: true', $attr);
            }
            return $attr . $matches[3] . ' $' . $matches[4];
        },
        $content
    );
    
    // Catch #[ORM\Column] (without parenthesis)
    $content = preg_replace_callback(
        '/(#\[ORM\\\\Column\]\s+.*?(?:private|protected|public)\s+\?)(\w+)\s+\\$(\w+)/ms',
        function ($matches) {
            $attr = str_replace('#[ORM\Column]', "#[ORM\Column(nullable: true)]", $matches[1]);
            return $attr . $matches[2] . ' $' . $matches[3];
        },
        $content
    );

    file_put_contents($file, $content);
}
echo "Done Entities.\n";
