<?php

namespace App\Tests\Unit\Security;

use App\Security\TurnstileVerifier;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/** Teste la vérification serveur des jetons Cloudflare Turnstile. */
final class TurnstileVerifierTest extends TestCase
{
    /**
     * @param MockResponse|\Closure $response
     * @return TurnstileVerifier
     */
    private function createVerifier(MockResponse|\Closure $response): TurnstileVerifier
    {
        return new TurnstileVerifier(new MockHttpClient($response), new NullLogger(), 'secret-key');
    }

    public function testAcceptsTokenConfirmedByCloudflare(): void
    {
        $sentBody = null;
        $verifier = $this->createVerifier(function (string $method, string $url, array $options) use (&$sentBody) {
            $this->assertSame('POST', $method);
            $this->assertSame(TurnstileVerifier::VERIFY_URL, $url);
            parse_str($options['body'], $sentBody);

            return new MockResponse('{"success":true}');
        });

        $this->assertTrue($verifier->verify('token', '203.0.113.7'));
        $this->assertSame(['secret' => 'secret-key', 'response' => 'token', 'remoteip' => '203.0.113.7'], $sentBody);
    }

    public function testRejectsTokenRefusedByCloudflare(): void
    {
        $verifier = $this->createVerifier(
            new MockResponse('{"success":false,"error-codes":["invalid-input-response"]}')
        );

        $this->assertFalse($verifier->verify('token'));
    }

    public function testRejectsEmptyTokenWithoutCallingCloudflare(): void
    {
        $verifier = $this->createVerifier(function (): never {
            $this->fail('Cloudflare ne doit pas être appelé sans jeton.');
        });

        $this->assertFalse($verifier->verify(null));
        $this->assertFalse($verifier->verify(''));
    }

    public function testFailsClosedOnNetworkError(): void
    {
        $verifier = $this->createVerifier(new MockResponse('', ['error' => 'Connexion impossible']));

        $this->assertFalse($verifier->verify('token'));
    }

    public function testFailsClosedOnServerError(): void
    {
        $verifier = $this->createVerifier(new MockResponse('oops', ['http_code' => 500]));

        $this->assertFalse($verifier->verify('token'));
    }
}
