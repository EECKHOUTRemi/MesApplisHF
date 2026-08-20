<?php

namespace App\Tests\Functional\Friends;

use App\Entity\Friends\Conversation;
use App\Entity\Friends\Message;
use App\Entity\Friends\Relationship;
use App\Entity\MaCuisine\Recipe;
use App\Entity\User;
use App\Mercure\ChatTopics;
use App\Tests\Functional\AppWebTestCase;
use App\Tests\Mercure\CollectingPublisher;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chat entre amis : accès, envoi de message et publication Mercure.
 *
 * Le hub est remplacé par un mock (voir `config/services_test.yaml`), donc
 * aucun hub n'a besoin de tourner : les mises à jour publiées sont relues
 * dans le conteneur.
 */
final class ChatControllerTest extends AppWebTestCase
{
    private const BASE = '/friends/chat';

    private const CONTENT = 'Bonjour à toi';

    public function testIndexRequiresLogin(): void
    {
        $this->client->request('GET', self::BASE);

        self::assertResponseRedirects('/login');
    }

    public function testIndexListsConversations(): void
    {
        [$me, $friend]  = $this->createFriends();
        $conversation   = $this->createConversation($me, $friend);
        $this->createMessage($conversation, $friend, 'Salut !');

        $this->login($me);
        $crawler = $this->client->request('GET', self::BASE);

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('li[data-conversation-id="' . $conversation->getId() . '"]'));
        self::assertStringContainsString('Salut !', $crawler->filter('[data-chat-preview]')->text());
    }

    public function testShowMarksReceivedMessagesAsRead(): void
    {
        [$me, $friend] = $this->createFriends();
        $conversation  = $this->createConversation($me, $friend);
        $message       = $this->createMessage($conversation, $friend, 'Tu es là ?');

        $this->login($me);
        $this->openConversation($conversation);

        self::assertNotNull($this->reload($message)->getReadAt());
    }

    public function testShowSetsTheMercureAuthorizationCookie(): void
    {
        [$me, $friend] = $this->createFriends();
        $conversation  = $this->createConversation($me, $friend);

        $this->login($me);
        $this->openConversation($conversation);

        $cookies = $this->client->getResponse()->headers->getCookies();
        $names   = array_map(static fn (Cookie $cookie): string => $cookie->getName(), $cookies);

        self::assertContains(
            'mercureAuthorization',
            $names,
            'Le cookie signé par le hub est nécessaire pour s\'abonner au flux.'
        );
    }

    public function testShowIsForbiddenToNonParticipants(): void
    {
        [$me, $friend] = $this->createFriends();
        $conversation  = $this->createConversation($me, $friend);

        $this->login($this->createUser());
        $this->client->request('GET', self::BASE . '/' . $conversation->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testSendMessagePublishesOneUpdatePerParticipant(): void
    {
        [$me, $friend] = $this->createFriends();
        $conversation  = $this->createConversation($me, $friend);

        $this->login($me);
        $tokens = $this->openConversation($conversation);

        $this->client->request('POST', $this->messageUrl($conversation), [
            'message' => self::CONTENT,
            '_token'  => $tokens['message'],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $publisher = $this->publisher();

        self::assertCount(2, $publisher->updates(), 'Un message par participant, rendu pour chaque lecteur.');

        // La bulle poussée à l'auteur est « sortante », celle poussée à l'ami « entrante ».
        $mine   = $this->payloadOf($publisher, ChatTopics::forUser($me));
        $theirs = $this->payloadOf($publisher, ChatTopics::forUser($friend));

        self::assertSame($conversation->getId(), $mine['conversationId']);
        self::assertTrue($mine['fromMe']);
        self::assertFalse($theirs['fromMe']);
        self::assertSame(self::CONTENT, $theirs['preview']);
        self::assertStringContainsString('chat-bubble-out', $mine['html']);
        self::assertStringContainsString('chat-bubble-in', $theirs['html']);
    }

    public function testSendMessageRejectsAnEmptyBody(): void
    {
        [$me, $friend] = $this->createFriends();
        $conversation  = $this->createConversation($me, $friend);

        $this->login($me);
        $tokens = $this->openConversation($conversation);

        $this->client->request('POST', $this->messageUrl($conversation), [
            'message' => '   ',
            '_token'  => $tokens['message'],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertSame([], $this->publisher()->updates());
    }

    public function testSendMessageRejectsAnInvalidCsrfToken(): void
    {
        [$me, $friend] = $this->createFriends();
        $conversation  = $this->createConversation($me, $friend);

        $this->login($me);
        $this->client->request('POST', $this->messageUrl($conversation), [
            'message' => self::CONTENT,
            '_token'  => 'invalide',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testSendMessageIsForbiddenOnceTheFriendshipIsGone(): void
    {
        [$me, $friend, $relationship] = $this->createFriends();
        $conversation = $this->createConversation($me, $friend);

        // Le jeton est récupéré tant que le formulaire est encore rendu.
        $this->login($me);
        $tokens = $this->openConversation($conversation);

        $this->em()->remove($this->reload($relationship));
        $this->em()->flush();

        $this->client->request('POST', $this->messageUrl($conversation), [
            'message' => self::CONTENT,
            '_token'  => $tokens['message'],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        self::assertSame([], $this->publisher()->updates());
    }

    public function testSendMessageSucceedsWhenTheRecipeFieldIsAnEmptyString(): void
    {
        // Le champ caché "recipe" du formulaire vaut toujours "" quand aucune recette
        // n'est jointe (voir show.html.twig#recipeInput), jamais absent de la requête.
        [$me, $friend] = $this->createFriends();
        $conversation  = $this->createConversation($me, $friend);

        $this->login($me);
        $tokens = $this->openConversation($conversation);

        $this->client->request('POST', $this->messageUrl($conversation), [
            'message' => self::CONTENT,
            'recipe'  => '',
            '_token'  => $tokens['message'],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $sent = $this->em()->getRepository(Message::class)->findOneBy(['conversation' => $conversation]);

        self::assertInstanceOf(Message::class, $sent);
        self::assertSame(self::CONTENT, $sent->getContent());
        self::assertNull($sent->getRecipeAttached());
    }

    public function testSendMessageAttachesARecipeWithoutAnyText(): void
    {
        [$me, $friend] = $this->createFriends();
        $conversation  = $this->createConversation($me, $friend);
        $recipe        = $this->createRecipe($me, 'Tarte aux pommes');

        $this->login($me);
        $tokens = $this->openConversation($conversation);

        $this->client->request('POST', $this->messageUrl($conversation), [
            'message' => '',
            'recipe'  => (string) $recipe->getId(),
            '_token'  => $tokens['message'],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $sent = $this->em()->getRepository(Message::class)->findOneBy(['conversation' => $conversation]);

        self::assertInstanceOf(Message::class, $sent);
        self::assertNull($sent->getContent());
        self::assertSame($recipe->getId(), $sent->getRecipeAttached()?->getId());

        // Sans texte, l'aperçu se rabat sur le nom de la recette.
        $theirs = $this->payloadOf($this->publisher(), ChatTopics::forUser($friend));

        self::assertSame('Recette : Tarte aux pommes', $theirs['preview']);
        self::assertStringContainsString('Tarte aux pommes', $theirs['html']);
    }

    public function testSendMessageRejectsAnUnknownRecipe(): void
    {
        [$me, $friend] = $this->createFriends();
        $conversation  = $this->createConversation($me, $friend);

        $this->login($me);
        $tokens = $this->openConversation($conversation);

        // Recette créée puis supprimée : l'identifiant est certain de ne plus exister.
        $recipe = $this->createRecipe($me, 'Recette éphémère');
        $gone   = (string) $recipe->getId();

        $this->em()->remove($this->reload($recipe));
        $this->em()->flush();

        $this->client->request('POST', $this->messageUrl($conversation), [
            'message' => self::CONTENT,
            'recipe'  => $gone,
            '_token'  => $tokens['message'],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertSame([], $this->publisher()->updates());
    }

    public function testRecipePickerListsTheRecipesMatchingTheSearch(): void
    {
        // La base n'est pas vidée entre les exécutions : le nom doit être unique.
        $name = 'Gratin-' . bin2hex(random_bytes(4));
        $me   = $this->createUser();
        $this->createRecipe($me, $name);

        $this->login($me);
        $crawler = $this->client->request('GET', self::BASE . '/recipes', ['q' => mb_strtolower($name)]);

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('[data-recipe-name="' . $name . '"]'));

        $crawler = $this->client->request('GET', self::BASE . '/recipes', ['q' => 'introuvable-' . uniqid()]);

        self::assertCount(0, $crawler->filter('[data-recipe-id]'));
    }

    public function testMarkAsReadClearsTheUnreadMessages(): void
    {
        [$me, $friend] = $this->createFriends();
        $conversation  = $this->createConversation($me, $friend);

        $this->login($me);
        $tokens = $this->openConversation($conversation);

        // Message reçu après l'ouverture de la page, comme via le flux Mercure.
        $message = $this->createMessage($conversation, $friend, 'Coucou');

        $this->client->request('POST', self::BASE . '/' . $conversation->getId() . '/read', [
            '_token' => $tokens['read'],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        self::assertNotNull($this->reload($message)->getReadAt());
    }

    /**
     * Deux utilisateurs amis, plus la relation qui les lie.
     *
     * @return array{User, User, Relationship}
     */
    private function createFriends(): array
    {
        $me     = $this->createUser();
        $friend = $this->createUser();

        $relationship = (new Relationship())
            ->setuser1($me)
            ->setuser2($friend)
            ->setStatus(Relationship::STATUS_ACCEPTED)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->em()->persist($relationship);
        $this->em()->flush();

        return [$me, $friend, $relationship];
    }

    /**
     * @param User $me
     * @param User $friend
     * @return Conversation
     */
    private function createConversation(User $me, User $friend): Conversation
    {
        $conversation = (new Conversation())
            ->addUser($me)
            ->addUser($friend);

        $this->em()->persist($conversation);
        $this->em()->flush();

        return $conversation;
    }

    /**
     * @param Conversation $conversation
     * @param User $author
     * @param string $content
     * @return Message
     */
    private function createMessage(Conversation $conversation, User $author, string $content): Message
    {
        $conversation = $this->reload($conversation);

        $message = (new Message())
            ->setConversation($conversation)
            ->setAuthor($this->reload($author))
            ->setContent($content);

        $conversation->setLastMessageAt($message->getSentAt());

        $this->em()->persist($message);
        $this->em()->flush();

        return $message;
    }

    /**
     * @param User $author
     * @param string $name
     * @return Recipe
     */
    private function createRecipe(User $author, string $name): Recipe
    {
        $recipe = (new Recipe())
            ->setAuthor($this->reload($author))
            ->setName($name)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->em()->persist($recipe);
        $this->em()->flush();

        return $recipe;
    }

    /**
     * Ouvre la conversation et retourne les jetons CSRF de la page. Ils ne
     * peuvent pas être fabriqués hors requête : ils sont liés à la session.
     *
     * @param Conversation $conversation
     * @return array{message: string, read: string}
     */
    private function openConversation(Conversation $conversation): array
    {
        $crawler = $this->client->request('GET', self::BASE . '/' . $conversation->getId());

        self::assertResponseIsSuccessful();

        $messageToken = $crawler->filter('#messageForm input[name="_token"]');

        return [
            // Absent d'une conversation en lecture seule : le formulaire n'est pas rendu.
            'message' => $messageToken->count() > 0 ? (string) $messageToken->attr('value') : '',
            'read'    => (string) $crawler->filter('#chatThread')->attr('data-read-token'),
        ];
    }

    /**
     * @param Conversation $conversation
     * @return string
     */
    private function messageUrl(Conversation $conversation): string
    {
        return self::BASE . '/' . $conversation->getId() . '/message';
    }

    /**
     * Relit une entité depuis la base. Le rafraîchissement est indispensable
     * après une requête HTTP : `markAsRead()` est un UPDATE en masse, invisible
     * pour les objets déjà chargés en mémoire.
     *
     * @template T of object
     * @param T $entity
     * @return T
     */
    private function reload(object $entity): object
    {
        $em      = $this->em();
        $id      = $em->getClassMetadata($entity::class)->getIdentifierValues($entity);
        $managed = $em->find($entity::class, $id);

        self::assertInstanceOf($entity::class, $managed);
        $em->refresh($managed);

        return $managed;
    }

    /** @return CollectingPublisher */
    private function publisher(): CollectingPublisher
    {
        $publisher = static::getContainer()->get(CollectingPublisher::class);

        self::assertInstanceOf(CollectingPublisher::class, $publisher);

        return $publisher;
    }

    /**
     * Décode la charge utile publiée sur un sujet.
     *
     * @param CollectingPublisher $publisher
     * @param string $topic
     * @return array<string, mixed>
     */
    private function payloadOf(CollectingPublisher $publisher, string $topic): array
    {
        $updates = $publisher->updatesFor($topic);

        self::assertCount(1, $updates, sprintf('Une seule mise à jour attendue sur "%s".', $topic));

        $payload = json_decode($updates[0]->getData(), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($payload);

        return $payload;
    }
}
