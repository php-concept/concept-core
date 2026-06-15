<?php declare(strict_types=1);

namespace Concept\Core\Services\Session\Contracts;

use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface as SymfonyFlashBagInterface;

interface FlashBagInterface extends SymfonyFlashBagInterface
{
    public function addError(string $message): void;

    public function addInfo(string $message): void;

    public function addSuccess(string $message): void;

    public function addWarning(string $message): void;
}
