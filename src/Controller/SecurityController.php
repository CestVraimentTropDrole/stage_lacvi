<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\DeleteOrdersService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }


    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }


    #[Route('/profile/delete', name: 'profile_delete', methods: ['POST'])]
    public function deleteProfile(Request $request, EntityManagerInterface $entityManager, DeleteOrdersService $deleteOrdersService, OrderRepository $orderRepository): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            throw $this->createAccessDeniedException();
        }
        
        // Vérification du token CSRF
        if ($this->isCsrfTokenValid('delete_profile', $request->request->get('_token'))) {
            // Déconnexion avant suppression
            $this->container->get('security.token_storage')->setToken(null);

            $orders = $orderRepository->findBy([
                'user' => $user,
            ]);

            foreach ($orders as $order) {
                $deleteOrdersService->dropOrder($order);
            }

            $entityManager->remove($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_register');
        }
        
        $this->addFlash('error', 'Token CSRF invalide');
        return $this->redirectToRoute('home');
    }
}
