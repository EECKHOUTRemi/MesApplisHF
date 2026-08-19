<?php

namespace App\Mercure;

use App\Entity\User;

/**
 * Nommage des sujets Mercure du chat.
 *
 * Un sujet par utilisateur, et non un par conversation : le rendu d'un message
 * dépend de son lecteur (bulle « envoyée » ou « reçue », compteur de non-lus),
 * un utilisateur ne peut structurellement pas s'abonner au flux d'un autre, et
 * le navigateur n'ouvre qu'une seule connexion SSE, valable sur tout le site.
 */
final class ChatTopics
{
    private const USER_PATTERN = '/friends/chat/user/%d';

    /**
     * Sujet privé sur lequel un utilisateur reçoit ses propres notifications.
     *
     * @param User $user
     * @return string
     */
    public static function forUser(User $user): string
    {
        return sprintf(self::USER_PATTERN, (int) $user->getId());
    }
}
