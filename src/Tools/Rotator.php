<?php

declare(strict_types=1);

namespace Ezyt\MonologBD\Tools;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Exception;
use Symfony\Component\Console\Style\OutputStyle;
use Throwable;

use function count;

final class Rotator implements RotatorInterface
{
    protected const DATE_FORMAT = 'YmdHis';

    private $connection;
    private $initializer;
    private $tables;

    public function __construct(Connection $connection, InitializerInterface $initializer, array $tables)
    {
        $this->connection = $connection;
        $this->initializer = $initializer;
        $this->tables = $tables;
    }

    /**
     * @param OutputStyle $io
     * @param int $historySize
     * @throws ConnectionException
     * @throws Exception
     * @throws Throwable
     */
    public function rotate(OutputStyle $io, int $historySize): void
    {
        $this->connection->beginTransaction();

        try {
            $this->execute($io, $historySize);

            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    /**
     * @param OutputStyle $io
     * @param int $historySize
     * @throws Exception
     */
    private function execute(OutputStyle $io, int $historySize): void
    {
        foreach ($this->tables as $table) {
            $this->renameTable($this->connection, $table, $io);
        }

        $this->initializer->init($io);

        foreach ($this->tables as $table) {
            $this->deleteOldVersions($this->connection, $table, $historySize, $io);
        }
    }

    /**
     * @param Connection $connection
     * @param string $tableName
     * @param OutputStyle|null $io
     * @throws Exception
     */
    private function renameTable(Connection $connection, string $tableName, ?OutputStyle $io): void
    {
        $newTableName = $tableName . '_' . date(self::DATE_FORMAT);
        $connection->executeQuery(
            sprintf(
                'ALTER TABLE %s RENAME TO %s;',
                $tableName,
                $newTableName
            )
        );
        $connection->executeQuery(
            sprintf(
                'ALTER INDEX idx_%s_level RENAME TO idx_%s_level;',
                $tableName,
                $newTableName
            )
        );
        $connection->executeQuery(
            sprintf(
                'ALTER INDEX idx_%s_category RENAME TO idx_%s_category;',
                $tableName,
                $newTableName
            )
        );
        $connection->executeQuery(
            sprintf(
                'ALTER INDEX idx_%s_created_at RENAME TO idx_%s_created_at;',
                $tableName,
                $newTableName
            )
        );
        if ($io !== null) {
            $io->success(sprintf('Table `%s` moved to `%s`', $tableName, $newTableName));
        }
    }

    /**
     * @param Connection $connection
     * @param string $tableName
     * @param int $historySize
     * @param OutputStyle|null $io
     * @throws Exception
     */
    protected function deleteOldVersions(
        Connection $connection,
        string $tableName,
        int $historySize,
        ?OutputStyle $io
    ): void {
        $schemaManager = $connection->getSchemaManager();
        if ($schemaManager === null) {
            throw new Exception('SchemaManager is null');
        }
        $tables = array_filter(
            $schemaManager->listTableNames(),
            static function ($value) use ($tableName) {
                return str_starts_with($value, $tableName . '_');
            },
            ARRAY_FILTER_USE_BOTH
        );
        rsort($tables);

        for ($i = $historySize, $tablesCount = count($tables); $i < $tablesCount; $i++) {
            $connection->executeQuery(
                sprintf(
                    'DROP TABLE IF EXISTS %s;',
                    $tables[$i]
                )
            );
            if ($io !== null) {
                $io->success(sprintf('Dropping table `%s`', $tables[$i]));
            }
        }
    }
}
