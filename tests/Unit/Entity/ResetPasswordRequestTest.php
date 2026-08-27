<?php

namespace App\Tests\Unit\Entity;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/** Teste l'entité ResetPasswordRequest : rattachement à l'utilisateur et expiration. */
class ResetPasswordRequestTest extends TestCase
{
    public function testConstructorStoresUserAndTokenData(): void
    {
        $user = new User();
        $expiresAt = new \DateTimeImmutable('+1 hour');

        $request = new ResetPasswordRequest($user, $expiresAt, 'selecteur', 'token-hache');

        $this->assertNull($request->getId(), "L'id n'est attribué qu'à la persistance");
        $this->assertSame($user, $request->getUser());
        $this->assertSame($expiresAt, $request->getExpiresAt());
        $this->assertSame('token-hache', $request->getHashedToken());
    }

    public function testIsNotExpiredWhenExpirationIsInTheFuture(): void
    {
        $request = new ResetPasswordRequest(
            new User(),
            new \DateTimeImmutable('+1 hour'),
            'selecteur',
            'token-hache'
        );

        $this->assertFalse($request->isExpired());
    }

    public function testIsExpiredWhenExpirationIsInThePast(): void
    {
        $request = new ResetPasswordRequest(
            new User(),
            new \DateTimeImmutable('-1 hour'),
            'selecteur',
            'token-hache'
        );

        $this->assertTrue($request->isExpired());
    }
}
