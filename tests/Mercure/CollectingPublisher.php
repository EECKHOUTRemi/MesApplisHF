<?php

namespace App\Tests\Mercure;

use Symfony\Component\Mercure\Update;

/**
 * Remplace l'appel HTTP au hub Mercure dans l'environnement de test : les mises
 * à jour sont mémorisées, ce qui permet de les assertionner sans hub à faire
 * tourner. Branché sur le hub par `config/services_test.yaml`.
 */
final class CollectingPublisher
{
    /** @var list<Update> */
    private array $updates = [];

    /**
     * Signature attendue par `MockHub` : retourne l'identifiant de la mise à jour.
     *
     * @param Update $update
     * @return string
     */
    public function __invoke(Update $update): string
    {
        $this->updates[] = $update;

        return (string) count($this->updates);
    }

    /**
     * @return list<Update>
     */
    public function updates(): array
    {
        return $this->updates;
    }

    /**
     * Retourne les mises à jour publiées sur un sujet donné.
     *
     * @param string $topic
     * @return list<Update>
     */
    public function updatesFor(string $topic): array
    {
        return array_values(array_filter(
            $this->updates,
            static fn (Update $update): bool => in_array($topic, $update->getTopics(), true)
        ));
    }
}
