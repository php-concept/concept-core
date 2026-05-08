<?php declare(strict_types=1);

namespace Concept\Core\Components\View;

use Concept\Core\Components\View\Contracts\ViewInterface;
use Twig\Environment as Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class View implements ViewInterface
{
    public function __construct(
        public readonly Twig $twig
    ) {}

    /**
     * @param string $viewName
     * @param array<mixed> $data
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(string $viewName, array $data = []): string
    {
        if (!str_ends_with($viewName, self::DEFAULT_EXTENSION)) {
            $viewName .= self::DEFAULT_EXTENSION;
        }

        return $this->twig->render($viewName, $data);
    }
}