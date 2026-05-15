<?php declare(strict_types=1);

namespace Concept\Core\Components\Component\Contracts;

use Concept\Core\Components\Database\Contracts\SeederInterface;
use Illuminate\Database\Migrations\Migration;
use League\Container\ServiceProvider\ServiceProviderInterface;
use Symfony\Component\Console\Command\Command;
use Twig\Extension\ExtensionInterface;

interface ComponentInterface
{
    public function name(): string;

    public function version(): string;

    public function description(): string;

    /**
     * Absolute path to the component routes file, or null when the component has no HTTP routes.
     */
    public function routes(): ?string;

    /**
     * Extra service providers owned by this component.
     *
     * @return class-string<ServiceProviderInterface>[]
     */
    public function providers(): array;

    /**
     * @return class-string<ExtensionInterface>[]
     */
    public function twigExtensions(): array;

    /**
     * Twig namespace => views path relative to the application root.
     *
     * @return array<string, string>
     */
    public function twigNamespaces(): array;

    /**
     * URI path prefix => Twig namespace.
     *
     * @return array<string, string>
     */
    public function twigRouteNamespaces(): array;

    /**
     * @return class-string<Command>[]
     */
    public function commands(): array;

    /**
     * @return class-string<SeederInterface>[]
     */
    public function seeders(): array;

    /**
     * @return class-string<Migration>[]
     */
    public function migrations(): array;
}
