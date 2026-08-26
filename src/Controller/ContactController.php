<?php

namespace App\Controller;

use App\Form\ContactFormType;
use App\Notifier\EmailNotifier;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/contact', name: 'app_contact_')]
final class ContactController extends AbstractController
{
    #[Route(name: 'index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $form = $this->createForm(ContactFormType::class);

        $userInterface = $this->getUser();
        if ($userInterface) {
            $form->setData(['email' => $userInterface->getUserIdentifier()]);
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/send', name: 'send', methods: ['POST'])]
    public function sendMail(Request $request, EmailNotifier $emailNotifier): Response
    {
        $form = $this->createForm(ContactFormType::class);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('danger', 'Votre message n\'a pas pu être envoyé, merci de vérifier les champs.');

            return $this->redirectToRoute('app_contact_index');
        }

        $data = $form->getData();
        $emailNotifier->sendContactEmail($data['email'], $data['subject'], $data['content']);

        $this->addFlash('success', 'Votre message a bien été envoyé, nous vous répondrons rapidement.');

        return $this->redirectToRoute('app_contact_index');
    }
}
