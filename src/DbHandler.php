<?php

declare(strict_types=1);

namespace Ezyt\MonologBD;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Statement;
use JsonException;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Psr\Log\InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Throwable;

use function get_class;
use function is_array;

class DbHandler extends AbstractProcessingHandler
{
    public const DEFAULT_CATEGORY = 'default';

    private $id;
    private $extendContext;
    private $categoryRequire;

    /** @var Statement */
    private $query;

    public function __construct(
        Connection $connection,
        string $tableName,
        bool $extendContext = true,
        bool $categoryRequire = true
    ) {
        parent::__construct(Logger::INFO);
        $this->query = $connection->prepare(
            'INSERT INTO ' . $tableName
            . ' (level, category, message, context) VALUES (:level, :category, :message, :context)'
        );
        $this->id = Uuid::uuid4();
        $this->extendContext = $extendContext;
        $this->categoryRequire = $categoryRequire;
    }

    /**
     * @param array $record
     * @throws InvalidArgumentException
     * @throws JsonException
     */
    protected function write(array $record): void
    {
        $level = $this->getRecordLevel($record);

        $category = $this->getRecordCategory($record);
        $message = $this->getRecordMessage($record);

        $this->query->execute(
            [
                'level'    => Logger::getLevelName($level),
                'category' => $category,
                'message'  => $message,
                'context'  => $this->getRecordContext($record),
            ]
        );
    }

    private function getRecordLevel(array $record): int
    {
        return (int)($record['level'] ?? Logger::ERROR);
    }

    private function getRecordCategory(array $record): string
    {
        return $record['context']['category'] ?? static::DEFAULT_CATEGORY;
    }

    private function getRecordMessage(array $record): ?string
    {
        return $record['message'] ?? null;
    }

    /**
     * @param array $record
     * @return string
     * @throws JsonException
     */
    private function getRecordContext(array $record): string
    {
        $context = $record['context'] ?? [];
        if (!is_array($context)) {
            $context = [];
        }

        if (isset($context['category'])) {
            unset($context['category']);
        }

        if (isset($context['exception']) && $context['exception'] instanceof Throwable) {
            $context['origin']['message'] = $context['exception']->getMessage();
            $context['origin']['class'] = get_class($context['exception']);
            $context['origin']['trace'] = $context['exception']->getTraceAsString();
            unset($context['exception']);
        }

        if ($this->extendContext) {
            $context['id'] = $this->id;
            $context['pid'] = getmypid();
            $context['memory_usage'] = memory_get_usage();
            $context['peak_memory_usage'] = memory_get_peak_usage();
        }

        return json_encode($context, JSON_THROW_ON_ERROR);
    }

    public function isHandling(array $record): bool
    {
        return parent::isHandling($record)
            && (!$this->categoryRequire || $record['context']['category']);
    }
}
