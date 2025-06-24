<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchpageController extends AbstractController
{

    #[Route("/search/{id}", name: "search", requirements: ['id' => '[0-9-]+'])]
    public function index (Request $request, CategoryRepository $categoryRepository, ArticleRepository $articleRepository): Response
    {
        $id = $request->attributes->get('id');

        $category = $categoryRepository->findOneBy([
            'id' => $id,
        ]);

        $articles = $articleRepository->findBy([
            'category' => $category,
        ]);

        return $this->render('client/search.html.twig', [
            'category' => $category,
            'articles' => $articles,
        ]);
    }

}

?>
