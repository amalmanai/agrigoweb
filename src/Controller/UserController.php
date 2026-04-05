<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function profile(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function editProfile(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès.');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('user/edit_profile.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/users', name: 'app_admin_users')]
    #[IsGranted('ROLE_ADMIN')]
    public function listUsers(Request $request, UserRepository $userRepository): Response
    {
        $query = $request->query->get('q', '');
        $role = $request->query->get('role', '');
        $status = $request->query->get('status', '');
        $sortBy = $request->query->get('sort', 'idUser');
        $sortOrder = $request->query->get('order', 'DESC');

        $users = $userRepository->findByAdvancedFilters($query, $role, $status, $sortBy, $sortOrder);

        $totalUsers = $userRepository->count([]);
        $activeUsers = $userRepository->count(['isActive' => true]);
        $inactiveUsers = $totalUsers - $activeUsers;

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'query' => $query,
            'role' => $role,
            'status' => $status,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'inactiveUsers' => $inactiveUsers,
        ]);
    }

    #[Route('/admin/users/export/pdf', name: 'app_admin_users_export_pdf')]
    #[IsGranted('ROLE_ADMIN')]
    public function exportUsersPdf(Request $request, UserRepository $userRepository): Response
    {
        $query = $request->query->get('q', '');
        $role = $request->query->get('role', '');
        $status = $request->query->get('status', '');
        $sortBy = $request->query->get('sort', 'idUser');
        $sortOrder = $request->query->get('order', 'DESC');

        $users = $userRepository->findByAdvancedFilters($query, $role, $status, $sortBy, $sortOrder);

        $html = $this->renderView('admin/users_pdf.html.twig', [
            'users' => $users,
            'date' => new \DateTime(),
        ]);

        $dompdf = new \Dompdf\Dompdf();
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf->setOptions($options);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="utilisateurs_' . date('Y-m-d_H-i') . '.pdf"',
        ]);
    }

    #[Route('/admin/user/{id}', name: 'app_admin_user_detail')]
    #[IsGranted('ROLE_ADMIN')]
    public function userDetail(User $user): Response
    {
        return $this->render('admin/user_detail.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/admin/user/{id}/edit', name: 'app_admin_user_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function editUser(User $user, Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $entityManager->flush();

                $this->addFlash('success', 'Utilisateur modifié avec succès.');
                return $this->redirectToRoute('app_admin_users');
            } else {
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('danger', $error->getMessage());
                }
            }
        }

        return $this->render('admin/edit_user.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/admin/user/{id}/toggle', name: 'app_admin_user_toggle')]
    #[IsGranted('ROLE_ADMIN')]
    public function toggleUser(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setIsActive(!$user->isActive());
        $entityManager->flush();

        $this->addFlash('success', 'Statut de l\'utilisateur mis à jour.');
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/admin/user/{id}/delete', name: 'app_admin_user_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteUser(User $user, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur supprimé.');
        return $this->redirectToRoute('app_admin_users');
    }
}