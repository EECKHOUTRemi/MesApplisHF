<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Mercure\ChatTopics;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Mercure\Exception\ExceptionInterface;

/**
 * Pose le cookie d'autorisation Mercure sur chaque page HTML d'un utilisateur
 * connecté.
 *
 * La barre de navigation écoute le flux temps réel sur tout le site : le cookie
 * ne peut donc pas être posé par le seul contrôleur du chat. Il n'autorise qu'un
 * sujet, celui de l'utilisateur courant.
 */
final class MercureCookieSubscriber implements EventSubscriberInterface
{
    /**
     * @param Authorization $authorization
     * @param Security $security
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Authorization $authorization,
        private readonly Security $security,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onKernelResponse'];
    }

    /**
     * @param ResponseEvent $event
     * @return void
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $user = $this->security->getUser();

        if (!$event->isMainRequest() || $request->getRequestFormat() !== 'html' || !$user instanceof User) {
            return;
        }

        try {
            $cookie = $this->authorization->createCookie($request, [ChatTopics::forUser($user)]);
        } catch (ExceptionInterface $exception) {
            // Hub mal configuré (typiquement MERCURE_PUBLIC_URL sur un autre
            // domaine que le site). Le temps réel ne marchera pas, mais toutes
            // les pages du site n'ont pas à tomber avec lui.
            $this->logger->error('Cookie d\'autorisation Mercure impossible à créer.', [
                'exception' => $exception,
            ]);

            return;
        }

        $event->getResponse()->headers->setCookie($cookie);
    }
}
