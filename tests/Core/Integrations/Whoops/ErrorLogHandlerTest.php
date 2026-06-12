<?php declare(strict_types=1);

namespace Tests\Core\Integrations\Whoops;

use Concept\Core\Services\Logger\Contracts\LoggerInterface;
use Concept\Core\Integrations\Whoops\ErrorLogHandler;
use League\Container\Container;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Tests\Fixtures\Core\ArrayContainer;
use Whoops\Handler\Handler;

final class ErrorLogHandlerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_URI']);
        parent::tearDown();
    }

    public function testHandleReturnsDoneWhenLoggerMissing(): void
    {
        $handler = new ErrorLogHandler(new ArrayContainer([]));
        $handler->setException(new \RuntimeException('boom'));

        self::assertSame(Handler::DONE, $handler->handle());
    }

    public function testHandleLogsExceptionUsingRequestPathFromPsrRequest(): void
    {
        $_SERVER['REQUEST_URI'] = '/server-fallback';

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('exception')
            ->with(
                self::isInstanceOf(\RuntimeException::class),
                '/from-request'
            );

        $request = (new ServerRequest())->withUri(new Uri('https://app.test/from-request'));

        $container = new ArrayContainer([
            LoggerInterface::class => $logger,
            ServerRequestInterface::class => $request,
        ]);

        $handler = new ErrorLogHandler($container);
        $handler->setException(new \RuntimeException('boom'));

        self::assertSame(Handler::DONE, $handler->handle());
    }

    public function testHandleFallsBackToServerRequestUriWhenNoPsrRequest(): void
    {
        $_SERVER['REQUEST_URI'] = '/from-server';

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('exception')
            ->with(self::isInstanceOf(\RuntimeException::class), '/from-server');

        $container = new ArrayContainer([
            LoggerInterface::class => $logger,
        ]);

        $handler = new ErrorLogHandler($container);
        $handler->setException(new \RuntimeException('boom'));

        self::assertSame(Handler::DONE, $handler->handle());
    }

    public function testHandleReturnsDoneWhenLoggerResolutionFails(): void
    {
        $container = new Container();
        $container->addServiceProvider(new class extends AbstractServiceProvider {
            public function provides(string $id): bool
            {
                return $id === LoggerInterface::class;
            }

            public function register(): void
            {
                $this->getContainer()->add(LoggerInterface::class, function (): never {
                    throw new \RuntimeException('missing dependency');
                });
            }
        });

        $handler = new ErrorLogHandler($container);
        $handler->setException(new \RuntimeException('boom'));

        self::assertSame(Handler::DONE, $handler->handle());
    }

    public function testHandleUsesUnknownWhenServerRequestUriIsNotString(): void
    {
        $_SERVER['REQUEST_URI'] = ['not-string'];

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('exception')
            ->with(self::isInstanceOf(\RuntimeException::class), 'unknown');

        $container = new ArrayContainer([
            LoggerInterface::class => $logger,
        ]);

        $handler = new ErrorLogHandler($container);
        $handler->setException(new \RuntimeException('boom'));

        self::assertSame(Handler::DONE, $handler->handle());
    }
}
