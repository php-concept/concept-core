<?php declare(strict_types=1);

namespace Concept\Core\Services\Component\Contracts;

use Concept\Core\Services\Database\Contracts\SeederInterface;
use League\Container\ServiceProvider\ServiceProviderInterface;
use Symfony\Component\Console\Command\Command;

interface ComponentInterface
{
    /**
     * Unique human-readable identifier for the component.
     */
    public function name(): string;

    /**
     * Semantic version string of the component.
     */
    public function version(): string;

    /**
     * Short human-readable summary of the component's purpose.
     */
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
     * View engine extension classes registered with the template engine.
     *
     * @return class-string[]
     */
    public function viewExtensions(): array;

    /**
     * View namespace => views path relative to the application root.
     *
     * @return array<string, string>
     */
    public function viewPaths(): array;

    /**
     * URI path prefix => view namespace used to resolve templates for matching requests.
     *
     * @return array<string, string>
     */
    public function viewContexts(): array;

    /**
     * Console commands owned by this component.
     *
     * @return class-string<Command>[]
     */
    public function commands(): array;

    /**
     * Database seeder classes owned by this component.
     *
     * @return class-string<SeederInterface>[]
     */
    public function seeders(): array;

    /**
     * Migration directory paths relative to the application root.
     *
     * @return list<string>
     */
    public function migrationPaths(): array;

    /**
     * Source => target map for publishable assets.
     * Both paths must be relative to the application root.
     *
     * @return array<string, string>
     */
    public function assets(): array;
}
