<?php

namespace App\Tests\Functional\Admin;

use App\Entity\User;
use App\Tests\Functional\AppWebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * CRUD admin des comptes utilisateurs : contrôle d'accès, liste, création,
 * affichage, édition (dont les rôles) et suppression.
 */
final class UserControllerTest extends AppWebTestCase
{
    private const BASE_PATH = '/admin/user';

    /**
     * Coche une case à cocher rendue par le ChoiceType expanded/multiple `roles`, sans dépendre
     * de son index dans le tableau `user[roles][]` (DomCrawler ne sait pas cibler un groupe de
     * cases à cocher par valeur).
     *
     * @param Crawler $crawler
     * @param string $role
     * @return void
     */
    private function checkRole(Crawler $crawler, string $role): void
    {
        $node = $crawler->filter(sprintf('input[value="%s"]', $role))->getNode(0);
        if (!$node instanceof \DOMElement) {
            throw new \LogicException(sprintf('Aucune case à cocher pour le rôle "%s".', $role));
        }

        $node->setAttribute('checked', 'checked');
    }

    public function testIndexRequiresAuthentication(): void
    {
        $this->client->request('GET', self::BASE_PATH);

        self::assertResponseStatusCodeSame(302);
    }

    public function testIndexForbiddenForRegularUser(): void
    {
        $this->login($this->createUser());
        $this->client->request('GET', self::BASE_PATH);

        self::assertResponseStatusCodeSame(403);
    }

    public function testIndexListsUsers(): void
    {
        $user = $this->createUser();
        $this->login($this->createAdmin());

        $this->client->request('GET', self::BASE_PATH);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', $user->getEmail());
    }

    public function testCreatePersistsUser(): void
    {
        $this->login($this->createAdmin());
        $email = uniqid('nouveau.', true) . '@test.local';
        $username = uniqid('nouv.', true);

        $crawler = $this->client->request('GET', self::BASE_PATH . '/new');
        self::assertResponseIsSuccessful();

        $this->checkRole($crawler, 'ROLE_USER');
        $form = $crawler->filter('input[name="user[email]"]')->closest('form')->form([
            'user[email]' => $email,
            'user[username]' => $username,
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects(self::BASE_PATH);

        $saved = $this->em()->getRepository(User::class)->findOneBy(['email' => $email]);
        self::assertNotNull($saved);
        self::assertSame($username, $saved->getUsername());
        self::assertContains('ROLE_USER', $saved->getRoles());
    }

    public function testShowDisplaysUser(): void
    {
        $user = $this->createUser([], height: 172.5);
        $this->login($this->createAdmin());

        $this->client->request('GET', self::BASE_PATH . '/' . $user->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', $user->getEmail());
        self::assertSelectorTextContains('body', '172.5 cm');
    }

    public function testEditUpdatesUsernameAndHeight(): void
    {
        $user = $this->createUser();
        $this->login($this->createAdmin());
        $newUsername = uniqid('modif.', true);

        $crawler = $this->client->request('GET', self::BASE_PATH . '/' . $user->getId() . '/edit');
        self::assertResponseIsSuccessful();

        $this->checkRole($crawler, 'ROLE_USER');
        $form = $crawler->filter('input[name="user[username]"]')->closest('form')->form([
            'user[username]' => $newUsername,
            'user[height]' => '180',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects(self::BASE_PATH);
        $this->em()->clear();
        $updated = $this->em()->find(User::class, $user->getId());
        self::assertSame($newUsername, $updated->getUsername());
        self::assertSame(180.0, $updated->getHeight());
    }

    public function testEditCanPromoteUserToAdmin(): void
    {
        $user = $this->createUser();
        $this->login($this->createAdmin());

        $crawler = $this->client->request('GET', self::BASE_PATH . '/' . $user->getId() . '/edit');
        self::assertResponseIsSuccessful();

        $this->checkRole($crawler, 'ROLE_USER');
        $this->checkRole($crawler, 'ROLE_ADMIN');
        $form = $crawler->filter('input[name="user[username]"]')->closest('form')->form();
        $this->client->submit($form);

        self::assertResponseRedirects(self::BASE_PATH);
        $this->em()->clear();
        $updated = $this->em()->find(User::class, $user->getId());
        self::assertContains('ROLE_ADMIN', $updated->getRoles());
    }

    public function testDeleteRemovesUser(): void
    {
        $user = $this->createUser();
        $userId = $user->getId();
        $this->login($this->createAdmin());

        $crawler = $this->client->request('GET', self::BASE_PATH . '/' . $userId);
        $form = $crawler->filter(sprintf('form[action="%s/%d"]', self::BASE_PATH, $userId))->form();
        $this->client->submit($form);

        self::assertResponseRedirects(self::BASE_PATH);
        $this->em()->clear();
        self::assertNull($this->em()->find(User::class, $userId));
    }

    public function testDeleteWithInvalidCsrfTokenKeepsUser(): void
    {
        $user = $this->createUser();
        $userId = $user->getId();
        $this->login($this->createAdmin());

        $this->client->request('POST', self::BASE_PATH . '/' . $userId, ['_token' => 'invalide']);

        self::assertResponseRedirects(self::BASE_PATH);
        $this->em()->clear();
        self::assertNotNull($this->em()->find(User::class, $userId));
    }
}
