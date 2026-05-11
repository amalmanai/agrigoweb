<?php
require 'vendor/autoload.php';
$kernel = new App\Kernel('dev', true);
$kernel->boot();
$em = $kernel->getContainer()->get('doctrine')->getManager();
$user = new App\Entity\User();
$user->setEmailUser('test_google_' . rand() . '@gmail.com');
$user->setGoogleId('test1234');
$user->setNomUser('Utilisateur');
$user->setPrenomUser('Google');
$user->setNumUser(0);
$user->setAdresseUser('Compte Google - A completer');
$user->setPassword(bin2hex(random_bytes(10)));
$user->setRoleUser('ROLE_USER');
$user->setIsActive(true);
$em->persist($user);
try {
    $em->flush();
    echo 'Success: ID ' . $user->getIdUser();
} catch (\Exception $e) {
    echo 'Error: '. $e->getMessage();
}
