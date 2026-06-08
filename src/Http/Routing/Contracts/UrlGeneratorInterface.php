<?php declare(strict_types=1);

namespace Concept\Core\Http\Routing\Contracts;

interface UrlGeneratorInterface
{
    public function base(): string;

    /**
     * @param string $name
     * @param array<string, mixed> $parameters
     * @return string
     */
    public function uri(string $name, array $parameters = []): string;

    /**
     * @param string $name
     * @param array<string, mixed> $parameters
     * @return string
     */
    public function url(string $name, array $parameters = []): string;
}
