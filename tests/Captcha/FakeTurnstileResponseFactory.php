<?php

namespace App\Tests\Captcha;

use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Simule l'API siteverify de Cloudflare Turnstile dans l'environnement de test :
 * seul le jeton {@see self::VALID_TOKEN} est accepté. Branché sur le client HTTP
 * de TurnstileVerifier par `config/services_test.yaml`.
 */
final class FakeTurnstileResponseFactory
{
    public const VALID_TOKEN = 'test-turnstile-token';

    /**
     * @param string $method
     * @param string $url
     * @param array<string, mixed> $options
     * @return MockResponse
     */
    public function __invoke(string $method, string $url, array $options): MockResponse
    {
        parse_str((string) ($options['body'] ?? ''), $body);
        $success = self::VALID_TOKEN === ($body['response'] ?? null);

        return new MockResponse((string) json_encode([
            'success' => $success,
            'error-codes' => $success ? [] : ['invalid-input-response'],
        ]), ['response_headers' => ['content-type' => 'application/json']]);
    }
}
