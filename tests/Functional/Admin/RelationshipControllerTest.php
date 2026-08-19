<?php

namespace App\Tests\Functional\Admin;

use App\Entity\Friends\Relationship;
use App\Entity\User;
use App\Form\RelationshipType;
use App\Tests\Functional\AppWebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * CRUD admin des relations : accès réservé aux administrateurs, création via
 * le formulaire RelationshipType, modification, détail et suppression.
 */
final class RelationshipControllerTest extends AppWebTestCase
{
    private const BASE = '/admin/relationship';

    public function testIndexIsForbiddenToRegularUsers(): void
    {
        $this->login($this->createUser());
        $this->client->request('GET', self::BASE);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testIndexListsRelationshipsWithBothParticipants(): void
    {
        $relationship = $this->createRelationship(Relationship::STATUS_ACCEPTED);

        $this->login($this->createAdmin());
        $crawler = $this->client->request('GET', self::BASE);

        $this->assertResponseIsSuccessful();

        $body = $crawler->filter('body')->text();

        $this->assertStringContainsString((string) $relationship->getuser1()?->getUsername(), $body);
        $this->assertStringContainsString((string) $relationship->getuser2()?->getUsername(), $body);
    }

    public function testNewPersistsTheRelationshipAndStampsCreatedAt(): void
    {
        $user1 = $this->createNamedUser('Demandeur-');
        $user2 = $this->createNamedUser('Destinataire-');

        $this->login($this->createAdmin());
        $crawler = $this->client->request('GET', self::BASE . '/new');

        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form([
            'relationship[user1]'  => (string) $user1->getId(),
            'relationship[user2]'  => (string) $user2->getId(),
            'relationship[status]' => Relationship::STATUS_PENDING,
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects(self::BASE, Response::HTTP_SEE_OTHER);

        $created = $this->em()->getRepository(Relationship::class)->findOneBy([
            'user1' => $user1,
            'user2' => $user2,
        ]);

        $this->assertInstanceOf(Relationship::class, $created);
        $this->assertSame(Relationship::STATUS_PENDING, $created->getStatus());
        // Horodatage posé par le contrôleur : le formulaire ne l'expose pas.
        $this->assertNotNull($created->getCreatedAt());
        $this->assertNull($created->getUpdatedAt());
    }

    /** Le libellé des comptes lève l'ambiguïté entre deux pseudos identiques. */
    public function testNewFormLabelsUsersWithTheirEmail(): void
    {
        $user = $this->createNamedUser('Etiquette-');

        $this->login($this->createAdmin());
        $crawler = $this->client->request('GET', self::BASE . '/new');

        $this->assertResponseIsSuccessful();

        $option = $crawler->filter(sprintf('#relationship_user1 option[value="%d"]', $user->getId()));

        $this->assertCount(1, $option);
        $this->assertSame(RelationshipType::userLabel($user), trim($option->text()));
    }

    public function testShowDisplaysTheRelationship(): void
    {
        $relationship = $this->createRelationship(Relationship::STATUS_PENDING);

        $this->login($this->createAdmin());
        $crawler = $this->client->request('GET', self::BASE . '/' . $relationship->getId());

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString(
            (string) $relationship->getuser1()?->getUsername(),
            $crawler->filter('body')->text()
        );
    }

    public function testEditUpdatesTheStatusAndStampsUpdatedAt(): void
    {
        $relationship = $this->createRelationship(Relationship::STATUS_PENDING);

        $this->login($this->createAdmin());
        $crawler = $this->client->request('GET', self::BASE . '/' . $relationship->getId() . '/edit');

        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form([
            'relationship[status]' => Relationship::STATUS_ACCEPTED,
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects(self::BASE, Response::HTTP_SEE_OTHER);

        $this->em()->clear();
        $updated = $this->em()->find(Relationship::class, $relationship->getId());

        $this->assertInstanceOf(Relationship::class, $updated);
        $this->assertSame(Relationship::STATUS_ACCEPTED, $updated->getStatus());
        $this->assertNotNull($updated->getUpdatedAt());
    }

    public function testDeleteRemovesTheRelationship(): void
    {
        $relationship = $this->createRelationship(Relationship::STATUS_ACCEPTED);
        $id           = $relationship->getId();

        $this->login($this->createAdmin());

        // Le formulaire de suppression (jeton CSRF inclus) est sur la page de détail.
        $crawler = $this->client->request('GET', self::BASE . '/' . $id);
        $this->client->submit($crawler->filter(sprintf('form[action="%s/%d"]', self::BASE, $id))->form());

        $this->assertResponseRedirects(self::BASE, Response::HTTP_SEE_OTHER);

        $this->em()->clear();
        $this->assertNull($this->em()->find(Relationship::class, $id));
    }

    public function testDeleteKeepsTheRelationshipWithAnInvalidCsrfToken(): void
    {
        $relationship = $this->createRelationship(Relationship::STATUS_ACCEPTED);
        $id           = $relationship->getId();

        $this->login($this->createAdmin());
        $this->client->request('POST', self::BASE . '/' . $id, ['_token' => 'invalide']);

        $this->assertResponseRedirects(self::BASE, Response::HTTP_SEE_OTHER);

        $this->em()->clear();
        $this->assertNotNull($this->em()->find(Relationship::class, $id));
    }

    /**
     * Pseudo unique : les listes déroulantes du formulaire contiennent tous les
     * comptes de la base, partagée entre les exécutions.
     *
     * @param string $prefix
     * @return User
     */
    private function createNamedUser(string $prefix): User
    {
        $user = $this->createUser();
        // La colonne est limitée à 30 caractères.
        $user->setUsername(substr($prefix . bin2hex(random_bytes(6)), 0, 30));

        $this->em()->flush();

        return $user;
    }

    /**
     * @param string $status
     * @return Relationship
     */
    private function createRelationship(string $status): Relationship
    {
        $relationship = (new Relationship())
            ->setuser1($this->createNamedUser('Admin1-'))
            ->setuser2($this->createNamedUser('Admin2-'))
            ->setStatus($status)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->em()->persist($relationship);
        $this->em()->flush();

        return $relationship;
    }
}
