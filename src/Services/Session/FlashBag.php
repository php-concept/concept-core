<?php declare(strict_types=1);

namespace Concept\Core\Services\Session;

use Concept\Core\Services\Session\Contracts\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag as SymfonyFlashBag;

final class FlashBag extends SymfonyFlashBag implements FlashBagInterface
{
    public function addError(string $message): void
    {
        $this->add(FlashType::ERROR, $message);
    }

    public function addInfo(string $message): void
    {
        $this->add(FlashType::INFO, $message);
    }

    public function addSuccess(string $message): void
    {
        $this->add(FlashType::SUCCESS, $message);
    }

    public function addWarning(string $message): void
    {
        $this->add(FlashType::WARNING, $message);
    }
}
