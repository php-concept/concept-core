<?php declare(strict_types=1);

namespace Tests\Core\Components\Logger;

use Concept\Core\Components\Logger\Logger;
use Monolog\Logger as Monolog;
use PHPUnit\Framework\TestCase;

final class LoggerTest extends TestCase
{
    public function testLogDelegatesToMonolog(): void
    {
        $monolog = $this->createMock(Monolog::class);
        $monolog->expects(self::once())
            ->method('log')
            ->with('info', 'message', ['k' => 'v']);

        $logger = new Logger($monolog);
        $logger->log('info', 'message', ['k' => 'v']);
    }

    public function testExceptionLogsErrorWithContextAndUri(): void
    {
        $monolog = $this->createMock(Monolog::class);
        $monolog->expects(self::once())
            ->method('log')
            ->with(
                'error',
                'Boom',
                self::callback(static function (array $context): bool {
                    return ($context['code'] ?? null) === 422
                        && isset($context['file'], $context['line'], $context['trace'])
                        && ($context['uri'] ?? null) === '/users';
                })
            );

        $logger = new Logger($monolog);

        $exception = new class ('Boom') extends \RuntimeException {
            public function getStatusCode(): int
            {
                return 422;
            }
        };

        $logger->exception($exception, '/users');
    }

    public function testExceptionOmitsUriWhenEmpty(): void
    {
        $monolog = $this->createMock(Monolog::class);
        $monolog->expects(self::once())
            ->method('log')
            ->with(
                'error',
                'Failed',
                self::callback(static function (array $context): bool {
                    return ($context['code'] ?? null) === 500 && !array_key_exists('uri', $context);
                })
            );

        $logger = new Logger($monolog);
        $logger->exception(new \RuntimeException('Failed', 500));
    }
}
