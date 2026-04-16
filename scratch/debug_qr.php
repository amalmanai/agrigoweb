<?php
require 'vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

try {
    $reflection = new ReflectionClass(Builder::class);
    echo "Class: " . Builder::class . "\n";
    echo "Parent: " . ($reflection->getParentClass() ? $reflection->getParentClass()->getName() : "None") . "\n";
    echo "Methods:\n";
    foreach ($reflection->getMethods() as $method) {
        echo "- " . $method->getName() . "\n";
    }
    if ($reflection->hasMethod('__call')) {
        echo "Has __call\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
