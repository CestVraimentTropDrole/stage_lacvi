<?php

namespace App\Controller;

use App\Form\EditUserForm;
use App\Repository\OrderItemRepository;
use App\Repository\OrderRepository;
use App\Service\OrderInfosService;
use App\Service\PdfGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class ProfileController extends AbstractController
{

    #[Route("/profile", name: "profile")]
    public function profile (OrderRepository $orderRepository, OrderInfosService $orderInfosService): Response
    {
        $user = $this->getUser();

        $orders = $orderRepository->findBy([
            'user' => $user,
        ]);

        foreach ($orders as $order) {
            $order->total = $orderInfosService->getTotalPrice($order->getId()); // Calcule le total de la commande
            $orderInfosService->testState($order);  // Vérifie si l'état de la commande correspond toujours à la date
            $order->isModifiable = $orderInfosService->isModifiable($order);    // Vérifie si la commande est toujours modifiable
        }

        return $this->render('client/profile.html.twig', [
            'user' => $user,
            'orders' => $orders,
        ]);
    }

    #[Route("/profile/edit", name: "profile.edit")]
    public function edit (Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(EditUserForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('profile');
        }

        return $this->render('client/edit.html.twig', [
            'user' => $user,
            'editForm' => $form,
        ]);
    }


    #[Route("/stream_pdf/{id}", name: "pdf_stream", requirements: ['id' => '[0-9-]+'])]
    public function streamPdf(Request $request, PdfGeneratorService $pdfGeneratorService, OrderRepository $orderRepository, OrderItemRepository $orderItemRepository): Response
    {
        $id = $request->attributes->get('id');
        $order = $orderRepository->findOneBy([
            'id' => $id
        ]);

        $articles = $orderItemRepository->findBy([
            'orderid' => $order
        ]);

        //$html = $this->renderView('pdf.html.twig', [
            //'order' => $order,
            //'user' => $this->getUser(),
            //'articles' => $articles
        //]);
        $name = "lacvi_cde_" . $order->getId();

        return $this->render('pdf.html.twig', [
            'order' => $order,
            'user' => $this->getUser(),
            'articles' => $articles
        ]);

        //return $pdfGeneratorService->getStreamResponse($html, $name . '.pdf');
    }

}

?>
