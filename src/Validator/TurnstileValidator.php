<?php

namespace App\Validator;

use App\Security\TurnstileVerifier;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/** Valide la contrainte {@see Turnstile} via {@see TurnstileVerifier}. */
class TurnstileValidator extends ConstraintValidator
{
    /**
     * @param TurnstileVerifier $verifier
     * @param RequestStack $requestStack
     */
    public function __construct(private TurnstileVerifier $verifier, private RequestStack $requestStack)
    {
    }

    /**
     * @param mixed $value
     * @param Constraint $constraint
     * @return void
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Turnstile) {
            throw new UnexpectedTypeException($constraint, Turnstile::class);
        }

        $token = \is_string($value) ? $value : null;
        $remoteIp = $this->requestStack->getCurrentRequest()?->getClientIp();

        if (!$this->verifier->verify($token, $remoteIp)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
