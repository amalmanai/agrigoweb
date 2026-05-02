<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserManager;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    public function testValidUser()
    {
        $user = new User();
        $user->setEmailUser('test@gmail.com');
        $user->setPassword('password123'); // 11 caractères
        
        $manager = new UserManager();
        $this->assertTrue($manager->validate($user));
    }

    public function testUserWithInvalidEmail()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email invalide');
        
        $user = new User();
        $user->setEmailUser('email_invalide');
        $user->setPassword('password123');
        
        $manager = new UserManager();
        $manager->validate($user);
    }

    public function testUserWithShortPassword()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le mot de passe doit contenir au moins 8 caractères');
        
        $user = new User();
        $user->setEmailUser('test@gmail.com');
        $user->setPassword('short'); // 5 caractères
        
        $manager = new UserManager();
        $manager->validate($user);
    }
}
