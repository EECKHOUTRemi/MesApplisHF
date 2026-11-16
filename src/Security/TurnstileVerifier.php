<?php

namespace App\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Vérifie côté serveur un jeton Cloudflare Turnstile auprès de l'API siteverify.
 *
 * @see https://developers.cloudflare.com/turnstile/get-started/server-side-validation/
 */
class TurnstileVerifier
{
    public const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * @param HttpClientInterface $httpClient
     * @param LoggerInterface $logger
     * @param string $secretKey
     */
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        #[Autowire('%env(TURNSTILE_SECRET_KEY)%')]
        private string $secretKey,
    ) {
    }

    /**
     * Retourne true uniquement si Cloudflare confirme le jeton. Toute erreur réseau
     * ou réponse inattendue est traitée comme un échec (fail closed).
     *
     * @param string|null $token
     * @param string|null $remoteIp
     * @return bool
     */
    public function verify(?string $token, ?string $remoteIp = null): bool
    {
        // Les jetons Turnstile font au plus 2048 caractères.
        if (null === $token || '' === $token || \strlen($token) > 2048) {
            return false;
        }

        $body = ['secret' => $this->secretKey, 'response' => $token];
        if (null !== $remoteIp) {
            $body['remoteip'] = $remoteIp;
        }

        try {
            $data = $this->httpClient->request('POST', self::VERIFY_URL, [
                'body' => $body,
                'timeout' => 5,
            ])->toArray();
        } catch (ExceptionInterface $e) {
            $this->logger->error('Turnstile verification request failed: {message}', ['message' => $e->getMessage()]);

            return false;
        }

        if (true !== ($data['success'] ?? null)) {
            $this->logger->info('Turnstile token rejected', ['error-codes' => $data['error-codes'] ?? []]);

            return false;
        }

        return true;
    }
}
