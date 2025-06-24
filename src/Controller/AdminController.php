<?php

namespace App\Controller;

use App\Enum\UserRoles;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class AdminController extends AbstractController
{

    #[Route("/admin", name: "admin_index")]
    public function index (CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();

        return $this->render('admin/index.html.twig', [
            'categories' => $categories,
        ]);
    }


    #[Route("/admin/accounts", name: "admin_accounts")]
    public function adminAccounts (UserRepository $userRepository): Response
    {
        $users = $userRepository->findBy([
            'role' => UserRoles::ROLE_SIGNEDIN,
        ]);

        return $this->render('admin/accounts.html.twig', [
            'users' => $users,
            'number' => count($users),
        ]);
    }

    #[Route("/admin/accounts/validate/{id}", name: "admin_accounts_validate", requirements: ["id" => "[0-9-]+"])]
    public function adminAccountsValidate (Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $id = $request->attributes->get('id');
        $user = $userRepository->findOneBy([
            'id' => $id,
        ]);
        if (!$user) {
            throw $this->createNotFoundException('L\'utilisateur n\'existe pas');
        }

        $user->validateUser();
        $entityManager->flush();

        try {
            $email = (new Email())
                ->from('contact@lacvi.fr')
                ->to($user->getEmail())
                ->subject('Compte validé')
                ->text('Votre compte sur le site Internet de LACVI a été validé.');
            $mailer->send($email);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'envoi : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_accounts');
    }

    #[Route("/admin/accounts/drop/{id}", name: "admin_accounts_drop", requirements: ["id" => "[0-9-]+"])]
    public function adminAccountsDrop (Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $id = $request->attributes->get('id');
        $user = $userRepository->findOneBy([
            'id' => $id,
        ]);
        if (!$user) {
            throw $this->createNotFoundException('L\'utilisateur n\'existe pas');
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return $this->redirectToRoute('admin_accounts');
    }

}

?>
