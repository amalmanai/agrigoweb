<?php

require_once 'vendor/autoload.php';

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

// Créer le kernel Symfony
use App\Kernel;

$kernel = new Kernel('dev', false);
$kernel->boot();

$container = $kernel->getContainer();

// Récupérer les services nécessaires
$entityManager = $container->get('doctrine.orm.entity_manager');
$passwordHasher = $container->get('security.password_hasher');

// Récupérer tous les utilisateurs
$users = $entityManager->getRepository(User::class)->findAll();

foreach ($users as $user) {
    // Hasher le mot de passe actuel s'il n'est pas déjà hashé
    $password = $user->getPassword();
    
    // Vérifier si le mot de passe est déjà hashé (commence par $2$ pour bcrypt ou $argon)
    if (!str_starts_with($password, '$2') && !str_starts_with($password, '$argon')) {
        // Hasher le mot de passe
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        
        echo "Mot de passe hashé pour l'utilisateur: " . $user->getEmailUser() . "\n";
    } else {
        echo "Le mot de passe est déjà hashé pour: " . $user->getEmailUser() . "\n";
    }
}

// Sauvegarder les changements
$entityManager->flush();

echo "Mise à jour des mots de passe terminée!\n";

// Afficher les identifiants pour connexion
echo "\nIdentifiants de connexion disponibles:\n";
echo "----------------------------------------\n";
foreach ($users as $user) {
    if ($user->isActive()) {
        $role = in_array('ROLE_ADMIN', $user->getRoles()) ? 'ADMIN' : 'USER';
        echo "Email: " . $user->getEmailUser() . " | Rôle: " . $role . " | Actif: Oui\n";
    }
}

