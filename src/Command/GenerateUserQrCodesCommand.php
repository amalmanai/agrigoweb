<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\BuilderInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:generate-qrcodes',
    description: 'Generates login QR codes for all users',
)]
class GenerateUserQrCodesCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private BuilderInterface $qrCodeBuilder,
        #[Autowire('%user_qr_codes_directory%')]
        private readonly string $userQrCodesDirectory,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $users = $this->userRepository->findAll();
        $filesystem = new Filesystem();
        $qrCodeDir = rtrim($this->userQrCodesDirectory, '/\\').'/';
        if (!$filesystem->exists($qrCodeDir)) {
            $filesystem->mkdir($qrCodeDir);
        }

        $count = 0;
        foreach ($users as $user) {
            $id = $user->getIdUser();
            $email = $user->getEmailUser();
            $fileName = sprintf('user_%d_%s.svg', $id, $email);
            $filePath = $qrCodeDir . $fileName;

            // Skip if QR code already exists
            if ($filesystem->exists($filePath)) {
                continue;
            }

            // Ensure user has a login token for fallback
            if (!$user->getLoginToken()) {
                $user->setLoginToken(bin2hex(random_bytes(32)));
                $this->entityManager->persist($user);
            }

            // Generate QR code with the premium format: AGRIGO-USER:{id}:{email}
            $qrData = sprintf('AGRIGO-USER:%d:%s', $id, $email);
            $result = $this->qrCodeBuilder->build(
                data: $qrData
            );

            // Save the QR code image
            $result->saveToFile($filePath);

            $count++;
        }

        $this->entityManager->flush();

        $io->success(sprintf('Successfully generated %d NEW QR codes in %s', $count, $qrCodeDir));

        return Command::SUCCESS;
    }
}
