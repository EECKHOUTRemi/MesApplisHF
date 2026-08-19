<?php

namespace App\Security\Voter;

use App\Entity\Friends\Conversation;
use App\Entity\Friends\Relationship;
use App\Entity\User;
use App\Repository\Friends\RelationshipRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Autorise l'accès à une conversation : lecture pour les participants,
 * envoi de messages tant que l'amitié est acceptée.
 *
 * @extends Voter<string, Conversation>
 */
final class ConversationVoter extends Voter
{
    public const VIEW = 'CONVERSATION_VIEW';
    public const POST = 'CONVERSATION_POST';

    /**
     * @param RelationshipRepository $relationships
     */
    public function __construct(private readonly RelationshipRepository $relationships)
    {
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::POST], true)
            && $subject instanceof Conversation;
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     * @param Vote|null $vote
     * @return bool
     */
    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        $user = $token->getUser();

        if (!$user instanceof User || !$subject->getUsers()->contains($user)) {
            $vote?->addReason('Vous n\'avez pas le droit d\'accéder à cette conversation.');

            return false;
        }

        if ($attribute === self::VIEW) {
            return true;
        }

        return $this->isStillFriend($user, $subject, $vote);
    }

    /**
     * @param User $user
     * @param Conversation $conversation
     * @param Vote|null $vote
     * @return bool
     */
    private function isStillFriend(User $user, Conversation $conversation, ?Vote $vote): bool
    {
        $other = $conversation->getUsers()
            ->filter(static fn (User $u) => $u !== $user)
            ->first();

        if (!$other instanceof User) {
            $vote?->addReason("Cette conversation n'a plus de destinataire.");

            return false;
        }

        $relationship = $this->relationships->findRelationShipByUsers($user, $other);

        if ($relationship?->getStatus() !== Relationship::STATUS_ACCEPTED) {
            $vote?->addReason("Vous n'êtes plus amis : cette conversation est en lecture seule.");

            return false;
        }

        return true;
    }
}
