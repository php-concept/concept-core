<?php declare(strict_types=1);

namespace Concept\Core\Providers\Concerns;

use Concept\Core\Events\EventDispatcherResolver;
use Psr\EventDispatcher\EventDispatcherInterface;

trait PeeksEventDispatcher
{
    protected function peekEventDispatcher(): ?EventDispatcherInterface
    {
        return EventDispatcherResolver::resolve($this->getContainer());
    }
}
