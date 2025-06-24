<?php

namespace App\Controller;

use App\Form\ContactForm;
use App\Form\NoticesForm;
use App\Repository\CategoryRepository;
use App\Repository\NoticesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class HomeController extends AbstractController
{

    #[Route("/", name: "home")]
    public function index (CategoryRepository $categoryRepository): Response
    {
        if (!($this->getUser())) {
            return $this->redirectToRoute('app_register');
        }

        if ($this->getUser()->isAdmin()) {
            return $this->redirectToRoute('admin_index');
        }

        $categories = $categoryRepository->findAll();

        return $this->render('client/index.html.twig', [
            'categories' => $categories,
        ]);
    }


    #[Route("/about", name: "about")]
    public function about (Request $request, NoticesRepository $noticesRepository, EntityManagerInterface $entityManager): Response
    {
        $notices = $noticesRepository->findOneBy([
            'id' => 1
        ]);

        $form = $this->createForm(NoticesForm::class, $notices);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Les mentions ont bien été mises à jour');
            return $this->redirectToRoute('home');
        }

        return $this->render('client/about.html.twig', [
            'notices' => $notices,
            'noticesForm' => $form
        ]);
    }


    #[Route("/contact", name: "contact")]
    public function contact(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ContactForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $user = $this->getUser();
                $email = (new Email())
                    ->from('contact@lacvi.fr')
                    ->to('contact@lacvi.fr')
                    ->subject($form->get('subject')->getData())
                    ->text('Message envoyé de '. $user->getName() . ' : '. $form->get('message')->getData());

                $mailer->send($email);
                $this->addFlash('success', 'Mail envoyé');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'envoi : ' . $e->getMessage());
            }

            return $this->redirectToRoute('home');
        }

        return $this->render('client/contact.html.twig', [
            'contactForm' => $form,
        ]);
    }

}

?>
