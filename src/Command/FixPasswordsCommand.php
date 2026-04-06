<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:fix-passwords',
    description: 'Hash plaintext passwords in the database',
)]
class FixPasswordsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $users = $this->em->getRepository(User::class)->findAll();
        $hashedCount = 0;
        
        foreach ($users as $user) {
            $plainPassword = $user->getPassword();
            
            // Skip if already hashed
            if (strpos($plainPassword, '$2y$') === 0 || strpos($plainPassword, '$2a$') === 0 || strpos($plainPassword, '$2b$') === 0) {
                $io->writeln("✓ User {$user->getEmailUser()} already hashed");
                continue;
            }
            
            try {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
                $this->em->persist($user);
                $hashedCount++;
                $io->writeln("✓ Hashed password for: {$user->getEmailUser()}");
            } catch (\Exception $e) {
                $io->error("Failed to hash password for {$user->getEmailUser()}: {$e->getMessage()}");
                return Command::FAILURE;
            }
        }
        
        $this->em->flush();
        $io->success("Password hashing complete! {$hashedCount} password(s) hashed.");
        
        return Command::SUCCESS;
    }
}
