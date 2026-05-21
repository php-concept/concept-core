<?php declare(strict_types=1);

namespace Concept\Core\Components\Routing\Contracts;

interface UrlGeneratorInterface
{
    /**
     * @param string $name
     * @param array<string, mixed> $parameters
     * @return string
     */
    public function route(string $name, array $parameters = []): string;
}