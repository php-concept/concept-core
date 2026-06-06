<?php declare(strict_types=1);

namespace Concept\Core\Components\View\Contracts;

interface ViewInterface
{
    /**
     * @param string $viewName
     * @param array<mixed> $data
     * @return string
     */
    public function render(string $viewName, array $data = []): string;

    /**
     * @param array<mixed> $data
     * @return void
     */
    public function share(array $data): void;
}