<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Builder\BuilderInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, BuilderInterface $qrCodeBuilder): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $photoFile */
            $photoFile = $form->get('photoPath')->getData();

            if ($photoFile) {
                $newFilename = uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('profile_photos_directory'),
                        $newFilename
                    );
                    $user->setPhotoPath($newFilename);
                } catch (FileException $e) {
                    // silent fail or log
                }
            }

            // Store password in plain text
            $user->setPassword($form->get('password')->getData());

            // Save Face ID descriptor if captured
            $user->setFaceDescriptor($form->get('faceDescriptor')->getData());

            $user->setRoleUser('ROLE_USER'); // Default role for registration

            $entityManager->persist($user);
            $entityManager->flush();

            // Automatic QR Code Generation
            $qrCodePath = 'C:\Users\Amal\AgriGo\user-qrs\user_'.$user->getIdUser().'_'.$user->getEmailUser().'.png';
            if (!file_exists('C:\Users\Amal\AgriGo\user-qrs')) {
                mkdir('C:\Users\Amal\AgriGo\user-qrs', 0777, true);
            }

            $result = $qrCodeBuilder->build(
                data: 'AGRIGO-USER:'.$user->getIdUser().':'.$user->getEmailUser(),
                size: 300,
                margin: 10,
                roundBlockSizeMode: RoundBlockSizeMode::Margin
            );

            $result->saveToFile($qrCodePath);

            // Store user info in session for success page
            $request->getSession()->set('registration_success_user', [
                'id' => $user->getIdUser(),
                'name' => $user->getPrenomUser(),
                'qr_path' => 'user_'.$user->getIdUser().'_'.$user->getEmailUser().'.png'
            ]);

            return $this->redirectToRoute('app_registration_success');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/register/success', name: 'app_registration_success')]
    public function success(Request $request): Response
    {
        $userData = $request->getSession()->get('registration_success_user');

        if (!$userData) {
            return $this->redirectToRoute('app_register');
        }

        // We'll serve the image from the custom directory or symlink
        // For now, let's assume we want to show it. 
        // A better way is to stream it since it's outside public.
        
        return $this->render('registration/success.html.twig', [
            'user' => $userData
        ]);
    }

    #[Route('/qr-code/download/{filename}', name: 'app_qr_download')]
    public function downloadQr(string $filename): Response
    {
        $path = 'C:\Users\Amal\AgriGo\user-qrs\\' . $filename;
        if (!file_exists($path)) {
            throw $this->createNotFoundException('QR Code non trouvé.');
        }

        return $this->file($path);
    }

    #[Route('/qr-code/view/{filename}', name: 'app_qr_view')]
    public function viewQr(string $filename): Response
    {
        $path = 'C:\Users\Amal\AgriGo\user-qrs\\' . $filename;
        if (!file_exists($path)) {
            throw $this->createNotFoundException('QR Code non trouvé.');
        }

        return $this->file($path, $filename, \Symfony\Component\HttpFoundation\ResponseHeaderBag::DISPOSITION_INLINE);
    }
}
