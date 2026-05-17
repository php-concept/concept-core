<?php declare(strict_types=1);

namespace Concept\Core\Providers\Concerns;

use Concept\Core\Events\EventDispatcherResolver;
use League\Event\EventDispatcher;

trait PeeksEventDispatcher
{
    protected function peekEventDispatcher(): ?EventDispatcher
    {
        return EventDispatcherResolver::resolve($this->getContainer());
    }
}
