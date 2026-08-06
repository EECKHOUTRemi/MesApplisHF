<?php

namespace App\Tests\Unit\Entity;

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
}
