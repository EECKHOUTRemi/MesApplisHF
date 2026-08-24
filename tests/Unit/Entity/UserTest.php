<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Friends\Conversation;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/** Teste les invariants de l'entité User : rôles, identifiant et état de vérification. */
class UserTest extends TestCase
{
    public function testGetRolesAlwaysContainsRoleUser(): void
    {
        $user = new User();

        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testGetRolesDeduplicates(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);

        $roles = $user->getRoles();

        $this->assertSame($roles, array_values(array_unique($roles)));
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles);
    }

    public function testUserIdentifierIsEmail(): void
    {
        $user = new User();
        $user->setEmail('jean@exemple.fr');

        $this->assertSame('jean@exemple.fr', $user->getUserIdentifier());
    }

    public function testNewUserIsNotVerified(): void
    {
        $this->assertFalse(new User()->isVerified());
    }

    public function testNewUserHasNoImage(): void
    {
        $this->assertNull(new User()->getImage());
    }

    public function testSetImageStoresFilenameAndCanBeReset(): void
    {
        $user = new User();

        $this->assertSame($user, $user->setImage('avatar.png'));
        $this->assertSame('avatar.png', $user->getImage());

        $user->setImage(null);
        $this->assertNull($user->getImage());
    }

    public function testNewUserHasNoIdentityYet(): void
    {
        $user = new User();

        $this->assertNull($user->getId());
        $this->assertNull($user->getEmail());
        $this->assertNull($user->getUsername());
        $this->assertNull($user->getPassword());
        // Fixé à la construction (cf. constructeur de User), contrairement au
        // reste de l'identité qui n'existe qu'après persistance.
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
        $this->assertNull($user->getHeight());
    }

    public function testSettersAreFluentAndStoreTheirValue(): void
    {
        $user      = new User();
        $createdAt = new \DateTimeImmutable('2026-01-15 09:30:00');

        $this->assertSame($user, $user->setEmail('claire@exemple.fr'));
        $this->assertSame($user, $user->setUsername('claire'));
        $this->assertSame($user, $user->setPassword('hash'));
        $this->assertSame($user, $user->setCreatedAt($createdAt));
        $this->assertSame($user, $user->setIsVerified(true));
        $this->assertSame($user, $user->setHeight(172.5));

        $this->assertSame('claire@exemple.fr', $user->getEmail());
        $this->assertSame('claire', $user->getUsername());
        $this->assertSame('hash', $user->getPassword());
        $this->assertSame($createdAt, $user->getCreatedAt());
        $this->assertTrue($user->isVerified());
        $this->assertSame(172.5, $user->getHeight());
    }

    /** La taille est facultative : MonPoids ne calcule alors pas d'IMC. */
    public function testHeightCanBeCleared(): void
    {
        $user = new User();
        $user->setHeight(180.0);

        $user->setHeight(null);

        $this->assertNull($user->getHeight());
    }

    /**
     * Le hachage du mot de passe ne doit jamais atterrir tel quel en session.
     * C'est `__serialize()` qui s'en charge depuis Symfony 7.3, `eraseCredentials()`
     * étant dépréciée : rien ne sert donc de tester cette dernière.
     */
    public function testSerializeReplacesThePasswordHash(): void
    {
        $user = new User();
        $user->setPassword('hash-tres-secret');

        $data = $user->__serialize();
        $key  = "\0" . User::class . "\0password";

        $this->assertArrayHasKey($key, $data);
        $this->assertNotSame('hash-tres-secret', $data[$key]);
        $this->assertSame(hash('crc32c', 'hash-tres-secret'), $data[$key]);
    }

    public function testNewUserHasNoConversation(): void
    {
        $this->assertCount(0, new User()->getConversations());
    }

    public function testAddConversationRegistersBothSidesOnlyOnce(): void
    {
        $user         = new User();
        $conversation = new Conversation();

        $this->assertSame($user, $user->addConversation($conversation));
        $user->addConversation($conversation);

        $this->assertCount(1, $user->getConversations());
        $this->assertTrue($user->getConversations()->contains($conversation));
        // L'association est portée par Conversation : les deux côtés doivent suivre.
        $this->assertTrue($conversation->getUsers()->contains($user));
    }

    public function testRemoveConversationDetachesBothSides(): void
    {
        $user         = new User();
        $conversation = new Conversation();
        $user->addConversation($conversation);

        $this->assertSame($user, $user->removeConversation($conversation));

        $this->assertCount(0, $user->getConversations());
        $this->assertFalse($conversation->getUsers()->contains($user));
    }

    public function testRemoveAnUnknownConversationChangesNothing(): void
    {
        $user  = new User();
        $known = new Conversation();
        $user->addConversation($known);

        $user->removeConversation(new Conversation());

        $this->assertCount(1, $user->getConversations());
    }
}
