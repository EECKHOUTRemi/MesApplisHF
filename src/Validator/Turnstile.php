<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/** Le champ doit contenir un jeton Cloudflare Turnstile valide. */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
class Turnstile extends Constraint
{
    public string $message = 'La vérification anti-robot a échoué. Veuillez réessayer.';

    /**
     * @param string|null $message
     * @param string[]|null $groups
     * @param mixed $payload
     */
    public function __construct(?string $message = null, ?array $groups = null, mixed $payload = null)
    {
        parent::__construct(null, $groups, $payload);

        $this->message = $message ?? $this->message;
    }
}
