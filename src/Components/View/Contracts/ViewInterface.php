<?php declare(strict_types=1);

namespace Concept\Core\Components\View\Contracts;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

interface ViewInterface
{
    public const string DEFAULT_EXTENSION = '.twig';

    /**
     * @param string $viewName
     * @param array<mixed> $data
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(string $viewName, array $data = []): string;
}