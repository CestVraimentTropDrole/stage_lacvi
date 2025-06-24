<?php

namespace App\Controller;

use App\Enum\OrderState;
use App\Form\OrderDateForm;
use App\Repository\OrderRepository;
use App\Repository\OrderItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\DeleteOrdersService;
use App\Service\OrderInfosService;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class OrderController extends AbstractController
{
    #[Route("/basket", name: "basket")]
    public function index(Request $request, OrderRepository $orderRepository, OrderItemRepository $orderItemRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $order = $orderRepository->findOneBy([
            'user' => $user,
            'state' => OrderState::BASKET,
        ]);
        $articles = $orderItemRepository->findBy([
            'orderid' => $order,
        ]);

        $form = $this->createForm(OrderDateForm::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
        }

        return $this->render('client/basket.html.twig', [
            'order' => $order,
            'articles' => $articles,
            'form' => $form,
        ]);
    }


    #[Route("basket/modify/{id}", name: "order_modify")]
    public function modifyOrder(Request $request, OrderRepository $orderRepository, DeleteOrdersService $deleteOrdersService, EntityManagerInterface $entityManager): Response
    {
        // Retrouve le panier actuel de l'utilisateur
        $user = $this->getUser();
        $order = $orderRepository->findOneBy([
            'user' => $user,
            'state' => OrderState::BASKET,
        ]);

        // S'il y a un panier, il est remplacé par la commande actuelle
        if ($order) {
            $deleteOrdersService->dropOrder($order);
        }

        $id = $request->attributes->get('id'); // Récupère l'id de la commande dans l'url
        $order = $orderRepository->findOneBy([
            'id' => $id,
        ]);

        if (!$order) {
            throw $this->createNotFoundException('La réservation n\'existe pas');
        }

        $order->setBasket();
        $entityManager->flush();

        return $this->redirectToRoute('basket');
    }


    #[Route("/basket/{id}/remove", name: "article_remove")]
    public function removeArticle (Request $request, OrderItemRepository $orderItemRepository, EntityManagerInterface $entityManager): Response
    {
        $id = $request->attributes->get('id'); // Récupère l'id de l'orderitem à retirer

        $orderitem = $orderItemRepository->findOneBy([
            'id' => $id,
        ]);

        $entityManager->remove($orderitem);
        $entityManager->flush();

        return $this->redirectToRoute('basket');
    }


    #[Route("/basket/{id}/drop", name: "basket_drop")]
    public function dropBasket (Request $request, OrderRepository $orderRepository, OrderItemRepository $orderItemRepository, EntityManagerInterface $entityManager): Response
    {
        $id = $request->attributes->get('id'); // Récupère l'id de la commande à retirer
        $user = $this->getUser();

        $order = $orderRepository->findOneBy([
            'id' => $id,
        ]);

        if(!$order) {
            throw $this->createNotFoundException('La réservation n\'existe pas');
        }

        if ($order->isBasket() && $order->getUser() == $user) {
            $orderitems = $orderItemRepository->findBy([
                'orderid' => $order,
            ]);

            foreach ($orderitems as $orderitem) {
                $entityManager->remove($orderitem);
                $entityManager->flush();
            }

            $entityManager->remove($order);
            $entityManager->flush();
        } else {
            throw $this->createNotFoundException('La réservation est déjà validée');
        }

        return $this->redirectToRoute('home');
    }


    #[Route("/basket/{id}/validate", name: "basket_validate")]
    public function createOrder (Request $request, OrderRepository $orderRepository, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $id = $request->attributes->get('id');  // Récupère l'id de la commande dans l'url
        $user = $this->getUser();

        $order = $orderRepository->findOneBy([  // Retrouve la réservation à partir de l'id
            'id' => $id,
        ]);

        if (!$order) {  // Erreur si la réservation n'existe pas
            throw $this->createNotFoundException('La réservation n\'existe pas');
        }

        if ($order->getState() == OrderState::BASKET) { // Vérifie que la réservation est bien un panier
            $order->moveToNextState();  // Passe la réservation en Créée
            $entityManager->flush();
        } else {    // Si la réservation n'est pas un panier
            throw $this->createNotFoundException('Votre réservation est déjà validée');
        }

        try {
            $email = (new Email())
                ->from('contact@lacvi.fr')
                ->to('contact@lacvi.fr')
                ->subject('Nouvelle réservation')
                ->text($user->getName(). ' vient de terminer une nouvelle commande.');
            $mailer->send($email);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'envoi : ' . $e->getMessage());
        }

        return $this->redirectToRoute('profile');
    }


    #[Route("/admin/orders", name: "admin_orders")]
    public function adminOrder (OrderRepository $orderRepository, OrderInfosService $orderInfosService): Response
    {
        $orders = $orderRepository->findByStateOrderDate(OrderState::CREATED);

        foreach ($orders as $order) {
            $order->total = $orderInfosService->getTotalPrice($order->getId());
        }

        return $this->render('admin/orders.html.twig', [
            'orders' => $orders,
            'number' => count($orders),
        ]);
    }

    #[Route("/admin/orders/{id}", name: "admin_orders_show", requirements: ['id' => '[0-9-]+'])]
    public function adminOrderShow (Request $request, OrderRepository $orderRepository, OrderItemRepository $orderItemRepository): Response
    {
        $id = $request->attributes->get('id');
        $order = $orderRepository->findOneBy([
            'id' => $id,
        ]);
        $articles = $orderItemRepository->findBy([
            'orderid' => $order,
        ]);
        if (!$order) {
            throw $this->createNotFoundException('La réservation recherchée n\'existe pas');
        }

        return $this->render('admin/showorder.html.twig', [
            'order' => $order,
            'articles' => $articles,
        ]);
    }

    #[Route("admin/orders/drop/{id}", name: "admin_orders_drop", requirements: ['id' => '[0-9-]+'])]
    public function adminOrderDrop (Request $request, OrderRepository $orderRepository, DeleteOrdersService $deleteOrdersService): Response
    {
        $id = $request->attributes->get('id');
        $order = $orderRepository->findOneBy([
            'id' => $id,
        ]);
        if(!$order) {
            throw $this->createNotFoundException('La réservation n\'existe pas');
        }

        if ($order->getState() == OrderState::CREATED) {
            $deleteOrdersService->dropOrder($order);
        }

        return $this->redirectToRoute('admin_orders');
    }

    #[Route("admin/orders/validate/{id}", name: "admin_orders_validate", requirements: ['id' => '[0-9-]+'])]
    public function adminOrderValidate (Request $request, OrderRepository $orderRepository, EntityManagerInterface $entityManager): Response
    {
        $id = $request->attributes->get('id');
        $order = $orderRepository->findOneBy([
            'id' => $id,
        ]);
        if (!$order) {
            throw $this->createNotFoundException('La réservation demandée n\'existe pas');
        }

        if ($order->getState() == OrderState::CREATED) {
            $order->moveToNextState();
            $entityManager->flush();
        }
        else {
            throw $this->createNotFoundException('La réservation n\'est pas à valider');
        }

        return $this->redirectToRoute('admin_orders');
    }

}
