<?php declare(strict_types=1);

namespace Concept\Core\Components\Csrf;

use Concept\Core\Http\SessionKey;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CsrfTokenManager
{
    private const int CSRF_TOKEN_LENGTH = 32;

    public function __construct(
        private readonly SessionInterface $session
    ) {}

    public function getToken(): string
    {
        if (!$this->session->has(SessionKey::CSRF_TOKEN)) {
            $token = bin2hex(random_bytes(self::CSRF_TOKEN_LENGTH));
            $this->session->set(SessionKey::CSRF_TOKEN, $token);
        }

        $token = $this->session->get(SessionKey::CSRF_TOKEN);
        if (!is_string($token)) {
            return '';
        }

        return $token;
    }

    public function validate(?string $token): bool
    {
        if (!$token || !$this->session->has(SessionKey::CSRF_TOKEN)) {
            return false;
        }

        $sessionToken = $this->session->get(SessionKey::CSRF_TOKEN);
        if (!is_string($sessionToken)) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }
}
