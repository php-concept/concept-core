<?php declare(strict_types=1);

namespace Tests\Core\Console\Commands;

use Concept\Core\Console\Commands\RouteListCommand;
use League\Route\Router;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class RouteListCommandTest extends TestCase
{
    public function testShowsWarningWhenNoRoutesFound(): void
    {
        $tester = new CommandTester(new RouteListCommand(new Router()));

        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Application Routes', $tester->getDisplay());
        self::assertStringContainsString('No routes found.', $tester->getDisplay());
    }

    public function testDisplaysRoutesWithShortMiddlewareNamesByDefault(): void
    {
        $router = new Router();
        $router->lazyMiddleware(SampleRouteListMiddleware::class);
        $router->get('/users', 'UserController::index')->setName('users.index');
        $router->group('/api', function ($router): void {
            $router->get('/health', 'HealthController::show')->setName('api.health');
        });

        $tester = new CommandTester(new RouteListCommand($router));
        $exitCode = $tester->execute([]);
        $display = $tester->getDisplay();

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('UserController::index', $display);
        self::assertStringContainsString('/api/health', $display);
        self::assertStringContainsString('SampleRouteListMiddleware', $display);
        self::assertStringNotContainsString(__NAMESPACE__ . '\\SampleRouteListMiddleware', $display);
        self::assertStringContainsString('Total: 2 route(s).', $display);
    }

    public function testDisplaysFullMiddlewareClassNamesWithOption(): void
    {
        $router = new Router();
        $router->lazyMiddleware(SampleRouteListMiddleware::class);
        $router->get('/users', 'UserController::index');

        $tester = new CommandTester(new RouteListCommand($router));
        $exitCode = $tester->execute(['--full-middleware' => true]);
        $display = $tester->getDisplay();

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString(SampleRouteListMiddleware::class, $display);
    }
}

final class SampleRouteListMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request);
    }
}
