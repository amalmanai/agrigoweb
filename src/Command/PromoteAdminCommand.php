<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:promote-admin',
    description: 'Promote a user to admin role',
)]
class PromoteAdminCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        // Get the second user (ghassenbencheikh@gmail.com)
        $user = $this->em->getRepository(User::class)->findOneBy(['emailUser' => 'ghassenbencheikh@gmail.com']);
        
        if (!$user) {
            $io->error('User not found');
            return Command::FAILURE;
        }
        
        $user->setRoleUser('ROLE_ADMIN');
        $this->em->persist($user);
        $this->em->flush();
        
        $io->success("User {$user->getEmailUser()} promoted to ROLE_ADMIN");
        
        return Command::SUCCESS;
    }
}
