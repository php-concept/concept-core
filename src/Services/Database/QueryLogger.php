<?php declare(strict_types=1);

namespace Concept\Core\Services\Database;

use Concept\Core\Services\DataMasker\Contracts\DataMaskerInterface;
use Illuminate\Database\Events\QueryExecuted;
use Monolog\Logger as Monolog;
use Monolog\LogRecord;

final class QueryLogger
{
    public function __construct(
        private readonly Monolog $monolog,
        ?DataMaskerInterface $masker
    ) {
        if (!$masker) {
            return;
        }

        $this->monolog->pushProcessor(function (LogRecord $record) use ($masker): LogRecord {
            return $record->with(
                context: $masker->mask($record->context)
            );
        });
    }

    public function log(QueryExecuted $query): void
    {
        $context = [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time,
            'connection' => $query->connectionName,
        ];

        $this->monolog->debug('SQL: ' . $query->toRawSql(), $context);
    }
}
