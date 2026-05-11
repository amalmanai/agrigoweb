<?php

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$em = $kernel->getContainer()->get('doctrine')->getManager();
$meta = $em->getMetadataFactory()->getAllMetadata();

foreach ($meta as $m) {
    try {
        $em->getRepository($m->getName())->findOneBy([]);
    } catch (\Exception $e) {
        echo "Error loading " . $m->getName() . ": " . $e->getMessage() . "\n";
    }
}
echo "Done checking all entities.\n";
