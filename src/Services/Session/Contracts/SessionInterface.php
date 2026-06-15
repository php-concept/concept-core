<?php declare(strict_types=1);

namespace Concept\Core\Services\Session\Contracts;

use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

interface SessionInterface extends FlashBagAwareSessionInterface
{
    public function getFlashBag(): FlashBagInterface;
}
