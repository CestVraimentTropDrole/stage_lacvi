<?php

namespace App\Controller;

use App\Enum\OrderState;
use App\Entity\Order;
use App\Entity\Article;
use App\Entity\OrderItem;
use App\Form\ArticleForm;
use App\Repository\ArticleRepository;
use App\Repository\OrderItemRepository;
use App\Repository\OrderRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use App\Service\FileUploadService;

class ArticleController extends AbstractController
{

    #[Route("/article/{id}", name: "article_show", requirements: ['id' => '[0-9-]+'])]
    public function article (Request $request, ArticleRepository $articleRepository, OrderRepository $orderRepository): Response
    {
        // Récupère l'id de l'article dans l'url pour afficher ses informations
        $id = $request->attributes->get('id');
        $article = $articleRepository->findOneBy([
            'id' => $id,
        ]);
        $category = $article->getCategory();

        // Vérifie que l'utilisateur a un panier
        $user = $this->getUser();
        $hasBasket = false;
        if ($user) {
            $basket = $orderRepository->findOneBy([
                'user' => $user,
                'state' => OrderState::BASKET,
            ]);
            $hasBasket = $basket !== null;
        }

        return $this->render('client/article.html.twig', [
            'article' => $article,
            'category' => $category,
            'basket' => $hasBasket,
        ]);
    }

    #[Route("/article/{id}/add", name: "article_add", methods: ['POST'])]
    public function addToBasket (Request $request, OrderRepository $orderRepository, ArticleRepository $articleRepository, EntityManagerInterface $entityManager, OrderItemRepository $orderItemRepository): Response
    {
        // Vérifie que l'utilisateur a un panier
        $user = $this->getUser();
        $basket = $orderRepository->findOneBy([
            'user' => $user,
            'state' => OrderState::BASKET,
        ]);

        if (!$basket) { // Sinon il créé un panier
            $date = $request->request->get('date');
            $basket = $this->createBasket($entityManager, $date); // Et il récupère l'id du panier qui vient d'être créé
        }

        try {   // Essaye de récupérer la quantité entrée pendant la commande
            $quantity = (int) $request->request->get('quantity', 1);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'ajout au panier : ' . $e->getMessage());
        } 

        $id = $request->attributes->get('id'); // Récupère l'id de l'article ajouté
        $article = $articleRepository->findOneBy([
            'id' => $id,
        ]);

        $isArticle = $orderItemRepository->findOneBy([
            'orderid' => $basket,
            'articleid' => $article
        ]);

        if ($isArticle) {
            $setQuantity = $isArticle->getQuantity() + $quantity;
            $isArticle->setQuantity($setQuantity);
        } else {
            $this->addArticleToBasket($entityManager, $basket, $article, $quantity);
        }

        return $this->redirectToRoute('search', [
            'id' => $article->getCategory()->getId()
        ]);
    }


    #[Route("/admin/article/add", name: "admin_article_add")]
    public function addArticle (Request $request, EntityManagerInterface $entityManager, FileUploadService $fileUploadService): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleForm::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $article->setStock(1);

            $file = $form->get('image')->getData();

            if ($file) {
                try {
                    $fileName = $fileUploadService->upload($file);
                    $article->setImage($fileName);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image');

                    return $this->render('admin/addarticle.article.html.twig', [
                        'articleForm' => $form,
                    ]);
                }
            }

            $entityManager->persist($article);
            $entityManager->flush();
            $this->addFlash('success', 'L\'article a été ajouté avec succès');

            return $this->redirectToRoute('admin_index');
        }

        return $this->render('admin/addarticle.html.twig', [
            'articleForm' => $form,
        ]);
    }

    #[Route("/admin/article/{id}", name: "admin_article", requirements: ['id' => '[0-9-]+'])]
    public function adminShowArticle(Request $request, ArticleRepository $articleRepository, EntityManagerInterface $entityManager, FileUploadService $fileUploadService): Response
    {
        $id = $request->attributes->get('id');
        $article = $articleRepository->findOneBy([
            'id' => $id,
        ]);
        $category = $article->getCategory();

        $form = $this->createForm(ArticleForm::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $article->setStock(1);

            $file = $form->get('image')->getData();

            if ($file) {
                try {
                    $fileName = $fileUploadService->upload($file);
                    $article->setImage($fileName);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image');

                    return $this->render('admin/addarticle.article.html.twig', [
                        'articleForm' => $form,
                    ]);
                }
            }

            $entityManager->persist($article);
            $entityManager->flush();
            $this->addFlash('success', 'L\'article a été ajouté avec succès');

            return $this->redirectToRoute('admin_article', ['id' => $id]);
        }

        return $this->render('admin/article.html.twig', [
            'article' => $article,
            'category' => $category,
            'articleForm' => $form,
        ]);
    }

    #[Route("/admin/article/drop/{id}", name: "admin_article_drop", requirements: ['id' => '[0-9-]+'])]
    public function adminDropArticle (Request $request, ArticleRepository $articleRepository, OrderItemRepository $orderItemRepository, EntityManagerInterface $entityManager): Response
    {
        $id = $request->attributes->get('id');
        $article = $articleRepository->findOneBy([
            'id' => $id,
        ]);
        $category = $article->getCategory()->getId();

        $orderitems = $orderItemRepository->findBy([
            'articleid' => $article,
        ]);

        foreach ($orderitems as $orderitem) {
            $entityManager->remove($orderitem);
            $entityManager->flush();
        }

        $entityManager->remove($article);
        $entityManager->flush();
        $this->addFlash('success', 'L\'article a été supprimé avec succès');

        return $this->redirectToRoute('search', ['id' => $category]);
    }


    public function createBasket (EntityManagerInterface $entityManager, $date): Order
    {
        $user = $this->getUser();

        $basket = new Order();
        $basket->setUser($user);
        $basket->setDate(new \DateTime($date));
        $basket->setCreated(new \DateTime());
        $basket->setState(OrderState::BASKET);

        $entityManager->persist($basket);
        $entityManager->flush();

        return $basket;
    }

    public function addArticleToBasket (EntityManagerInterface $entityManager, Order $order, Article $article, int $quantity)
    {
        $orderitem = new OrderItem();
        $orderitem->setOrderid($order);
        $orderitem->setArticleid($article);
        $orderitem->setQuantity($quantity);

        $entityManager->persist($orderitem);
        $entityManager->flush();

        return;
    }

}

?>
