<?php declare(strict_types=1);

namespace Concept\Core\Services\Component\Contracts;

use Concept\Core\Services\Database\Contracts\SeederInterface;
use Illuminate\Database\Migrations\Migration;
use League\Container\ServiceProvider\ServiceProviderInterface;
use Symfony\Component\Console\Command\Command;

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
     * @return class-string[]
     */
    public function viewExtensions(): array;

    /**
     * Namespace => views path relative to the application root.
     *
     * @return array<string, string>
     */
    public function viewPaths(): array;

    /**
     * URI path prefix => Namespace.
     *
     * @return array<string, string>
     */
    public function viewContexts(): array;

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

    /**
     * @return array<string, string>
     */
    public function assets(): array;
}
