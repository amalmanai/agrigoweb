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
<<<<<<< HEAD
        $user->setPassword('password123'); // 11 caractères
        
=======
        $user->setPassword('pass1234'); // 8 caractères

>>>>>>> c2d7907 (update projet)
        $manager = new UserManager();
        $this->assertTrue($manager->validate($user));
    }

    public function testUserWithInvalidEmail()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email invalide');
<<<<<<< HEAD
        
        $user = new User();
        $user->setEmailUser('email_invalide');
        $user->setPassword('password123');
        
=======

        $user = new User();
        $user->setEmailUser('email_invalide');
        $user->setPassword('pass1234');

>>>>>>> c2d7907 (update projet)
        $manager = new UserManager();
        $manager->validate($user);
    }

    public function testUserWithShortPassword()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le mot de passe doit contenir au moins 8 caractères');
<<<<<<< HEAD
        
        $user = new User();
        $user->setEmailUser('test@gmail.com');
        $user->setPassword('short'); // 5 caractères
        
=======

        $user = new User();
        $user->setEmailUser('test@gmail.com');
        $user->setPassword('short'); // 5 caractères

>>>>>>> c2d7907 (update projet)
        $manager = new UserManager();
        $manager->validate($user);
    }
}
