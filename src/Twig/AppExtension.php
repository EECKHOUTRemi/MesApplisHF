<?php

namespace App\Twig;

use App\Repository\Friends\RelationshipRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigTest;

class AppExtension extends AbstractExtension
{
    /**
     * @param string $projectDir Racine du projet, pour résoudre les chemins relatifs au docroot
     * @param RelationshipRepository $relationshipRepository
     * @param Security $security
     */
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')] private readonly string $projectDir,
        private readonly RelationshipRepository $relationshipRepository,
        private readonly Security $security,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('pending_friend_requests_count', $this->pendingFriendRequestsCount(...)),
        ];
    }

    /**
     * Retourne le nombre de demandes d'amitié en attente pour l'utilisateur connecté.
     *
     * @return int
     */
    public function pendingFriendRequestsCount(): int
    {
        $user = $this->security->getUser();

        if ($user === null) {
            return 0;
        }

        return $this->relationshipRepository->countPendingFor($user);
    }

    /**
     * @return TwigTest[]
     */
    public function getTests(): array
    {
        return [
            new TwigTest(
                'ondisk',
                fn (string $filename): bool =>
                    file_exists($this->projectDir . '/public/' . ltrim($filename, '/'))
            ),
        ];
    }
}
