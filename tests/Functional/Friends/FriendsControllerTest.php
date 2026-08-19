<?php

namespace App\Tests\Functional\Friends;

use App\Entity\Friends\Relationship;
use App\Entity\User;
use App\Tests\Functional\AppWebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use ValueError;

/**
 * Espace « Amis » : liste des demandes et des amis, envoi et réponse à une
 * demande, suppression d'une relation et recherche d'utilisateurs en AJAX.
 *
 * Les données créées portent des identifiants uniques : la base n'est pas
 * vidée entre les exécutions.
 */
final class FriendsControllerTest extends AppWebTestCase
{
    private const BASE = '/friends';

    private const BASE_PROFILE_PATH = '/profil/';

    private const BASE_RM_RELATIONSHIP_PATH = '/removeRelationship/';

    private const AJAX = self::BASE . '/users-ajax';

    private Filesystem $filesystem;

    /** @var list<string> Chemins absolus des fichiers créés, nettoyés en fin de test. */
    private array $createdFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem = new Filesystem();
    }

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $path) {
            $this->filesystem->remove($path);
        }
        $this->createdFiles = [];

        parent::tearDown();
    }

    public function testIndexRequiresLogin(): void
    {
        $this->client->request('GET', self::BASE);

        self::assertResponseRedirects('/login');
    }

    public function testIndexListsPendingRequestsAndFriends(): void
    {
        $me        = $this->createNamedUser('Moi-');
        $applicant = $this->createNamedUser('Demandeur-');
        $friend    = $this->createNamedUser('Ami-');

        // Demande reçue : c'est `user2` qui la voit dans ses demandes en attente.
        $this->createRelationship($applicant, $me, Relationship::STATUS_PENDING);
        $this->createRelationship($me, $friend, Relationship::STATUS_ACCEPTED);

        $this->login($me);
        $crawler = $this->client->request('GET', self::BASE);

        self::assertResponseIsSuccessful();

        $body = $crawler->filter('body')->text();

        self::assertStringContainsString((string) $applicant->getUsername(), $body);
        self::assertStringContainsString((string) $friend->getUsername(), $body);
    }

    public function testSendRequestCreatesAPendingRelationship(): void
    {
        $me    = $this->createNamedUser('Envoi-');
        $other = $this->createNamedUser('Cible-');

        $this->login($me);
        $this->client->request('POST', self::BASE . '/sendRequest/' . $other->getId());

        self::assertResponseRedirects(self::BASE_PROFILE_PATH . $other->getId());

        $relationship = $this->relationshipBetween($me, $other);

        self::assertInstanceOf(Relationship::class, $relationship);
        self::assertSame(Relationship::STATUS_PENDING, $relationship->getStatus());
        self::assertSame($me->getId(), $relationship->getuser1()?->getId());
        self::assertNotNull($relationship->getCreatedAt());
    }

    public function testSendRequestToAnUnknownUserIsNotFound(): void
    {
        $this->login($this->createNamedUser('Perdu-'));
        $this->client->request('POST', self::BASE . '/sendRequest/' . $this->missingUserId());

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testAnswerRequestAcceptsAndStampsTheUpdate(): void
    {
        $applicant = $this->createNamedUser('Postulant-');
        $me        = $this->createNamedUser('Recepteur-');
        $pending   = $this->createRelationship($applicant, $me, Relationship::STATUS_PENDING);

        $this->login($me);
        $this->client->request('POST', self::BASE . '/answerRequest/' . $pending->getId(), [
            'answer' => Relationship::STATUS_ACCEPTED,
        ]);

        self::assertResponseRedirects(self::BASE);

        $this->em()->clear();
        $updated = $this->em()->find(Relationship::class, $pending->getId());

        self::assertInstanceOf(Relationship::class, $updated);
        self::assertSame(Relationship::STATUS_ACCEPTED, $updated->getStatus());
        self::assertNotNull($updated->getUpdatedAt());
    }

    public function testAnswerRequestRejectsAnUnknownStatus(): void
    {
        $applicant = $this->createNamedUser('Statut-');
        $me        = $this->createNamedUser('Invalide-');
        $pending   = $this->createRelationship($applicant, $me, Relationship::STATUS_PENDING);

        $this->login($me);
        $this->client->catchExceptions(false);

        $this->expectException(ValueError::class);

        $this->client->request('POST', self::BASE . '/answerRequest/' . $pending->getId(), [
            'answer' => 'blocked',
        ]);
    }

    public function testRemoveRelationshipRedirectsToTheProfileByDefault(): void
    {
        $me     = $this->createNamedUser('Retrait-');
        $friend = $this->createNamedUser('Ancien-');
        $this->createRelationship($me, $friend, Relationship::STATUS_ACCEPTED);

        $this->login($me);
        $this->client->request('POST', self::BASE . self::BASE_RM_RELATIONSHIP_PATH . $friend->getId());

        self::assertResponseRedirects(self::BASE_PROFILE_PATH . $friend->getId());
        self::assertNull($this->relationshipBetween($me, $friend));
    }

    /**
     * La relation est cherchée dans les deux sens : ici c'est l'ami qui l'avait
     * créée, et la page « Amis » demande explicitement son propre retour.
     */
    public function testRemoveRelationshipWorksInTheOtherDirectionAndHonoursTheRedirect(): void
    {
        $me     = $this->createNamedUser('Cible2-');
        $friend = $this->createNamedUser('Auteur2-');
        $this->createRelationship($friend, $me, Relationship::STATUS_ACCEPTED);

        $this->login($me);
        $this->client->request('POST', self::BASE . self::BASE_RM_RELATIONSHIP_PATH . $friend->getId(), [
            'redirect' => 'friends',
        ]);

        self::assertResponseRedirects(self::BASE);
        self::assertNull($this->relationshipBetween($me, $friend));
    }

    public function testRemoveRelationshipIsNotFoundWithoutAnyRelation(): void
    {
        $me      = $this->createNamedUser('Sans-');
        $unknown = $this->createNamedUser('Relation-');

        $this->login($me);
        $this->client->request('POST', self::BASE . self::BASE_RM_RELATIONSHIP_PATH . $unknown->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUsersAjaxIgnoresTooShortQueries(): void
    {
        $this->login($this->createNamedUser('Court-'));
        $this->client->request('GET', self::AJAX, ['q' => 'a']);

        self::assertResponseIsSuccessful();
        self::assertSame([], $this->jsonResponse());
    }

    public function testUsersAjaxReturnsMatchesWithoutTheCurrentUser(): void
    {
        $token = bin2hex(random_bytes(4));
        $me    = $this->createNamedUser('Chercheur' . $token . '-');
        $found = $this->createNamedUser('Trouve' . $token . '-');

        $this->login($me);
        // Le jeton est commun aux deux pseudos : seul l'autre compte doit ressortir.
        $this->client->request('GET', self::AJAX, ['q' => $token]);

        self::assertResponseIsSuccessful();

        $payload = $this->jsonResponse();

        self::assertCount(1, $payload);
        self::assertSame($found->getId(), $payload[0]['id']);
        self::assertSame($found->getUsername(), $payload[0]['username']);
        self::assertSame(self::BASE_PROFILE_PATH . $found->getId(), $payload[0]['url']);
        // Sans photo de profil, aucune source d'image n'est calculée.
        self::assertNull($payload[0]['image']);
        self::assertSame([], $payload[0]['sources']);
    }

    public function testUsersAjaxExposesTheProfilePictureVariants(): void
    {
        $token = bin2hex(random_bytes(4));
        $me    = $this->createNamedUser('Photo' . $token . '-');
        $found = $this->createNamedUser('Avatar' . $token . '-');

        $found->setImage($this->storeProfilePicture());
        $this->em()->flush();

        $this->login($me);
        $this->client->request('GET', self::AJAX, ['q' => $token]);

        self::assertResponseIsSuccessful();

        $payload = $this->jsonResponse();

        self::assertCount(1, $payload);
        self::assertNotNull($payload[0]['image']);
        self::assertSame(['avif', 'webp', 'png', 'jpeg'], array_keys($payload[0]['sources']));
    }

    /**
     * Dépose un PNG 1x1 dans le dossier des photos de profil et retourne son nom.
     *
     * @return string
     */
    private function storeProfilePicture(): string
    {
        $directory = (string) static::getContainer()->getParameter('profile_images_directory');
        $this->filesystem->mkdir($directory);

        $name = 'ajax_' . bin2hex(random_bytes(6)) . '.png';
        $path = $directory . '/' . $name;

        file_put_contents($path, (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M8AAAMBAQDJ/pLvAAAAAElFTkSuQmCC'
        ));
        $this->createdFiles[] = $path;

        return $name;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function jsonResponse(): array
    {
        $payload = json_decode(
            (string) $this->client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertIsArray($payload);

        return $payload;
    }

    /**
     * Pseudo unique : la recherche AJAX porte dessus et la base est partagée
     * entre les exécutions.
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
     * @param User $user1
     * @param User $user2
     * @param string $status
     * @return Relationship
     */
    private function createRelationship(User $user1, User $user2, string $status): Relationship
    {
        $relationship = (new Relationship())
            ->setuser1($user1)
            ->setuser2($user2)
            ->setStatus($status)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->em()->persist($relationship);
        $this->em()->flush();

        return $relationship;
    }

    /**
     * @param User $userA
     * @param User $userB
     * @return Relationship|null
     */
    private function relationshipBetween(User $userA, User $userB): ?Relationship
    {
        $repository = $this->em()->getRepository(Relationship::class);

        return $repository->findOneBy(['user1' => $userA, 'user2' => $userB])
            ?? $repository->findOneBy(['user1' => $userB, 'user2' => $userA]);
    }

    /** @return int */
    private function missingUserId(): int
    {
        $ghost = $this->createUser();
        $id    = (int) $ghost->getId();

        $this->em()->remove($ghost);
        $this->em()->flush();

        return $id;
    }
}
