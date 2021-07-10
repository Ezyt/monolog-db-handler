<?php

declare(strict_types=1);

namespace Ezyt\MonologBD\Tools;

use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\Console\Style\OutputStyle;

final class Initializer implements InitializerInterface
{
    private $connection;
    private $tables;
    private $transactional;

    public function __construct(Connection $connection, array $tables, bool $transactional = true)
    {
        $this->connection = $connection;
        $this->tables = $tables;
        $this->transactional = $transactional;
    }

    public function init(OutputStyle $io): void
    {
        if ($this->transactional) {
            $this->connection->beginTransaction();
        }
        foreach ($this->tables as $table) {
            $this->createTable($this->connection, $table);
            if ($io !== null) {
                $io->success(sprintf('Table `%s` created.', $table));
            }
        }

        if ($this->transactional) {
            $this->connection->commit();
        }
    }

    protected function createTable(Connection $connection, string $tableName): void
    {
        $connection->exec(
            sprintf(
                'CREATE TABLE IF NOT EXISTS %s (id SERIAL NOT NULL, level VARCHAR(255) DEFAULT NULL,' .
                ' category VARCHAR(255) DEFAULT NULL, message TEXT DEFAULT NULL, context JSONB DEFAULT NULL,' .
                ' created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(id))',
                $tableName
            )
        );
        $connection->exec(
            sprintf(
                'CREATE INDEX IF NOT EXISTS idx_%s_level ON %s (level)',
                $tableName,
                $tableName
            )
        );
        $connection->exec(
            sprintf(
                'CREATE INDEX IF NOT EXISTS idx_%s_category ON %s (category)',
                $tableName,
                $tableName
            )
        );
        $connection->exec(
            sprintf(
                'CREATE INDEX IF NOT EXISTS idx_%s_created_at ON %s (created_at)',
                $tableName,
                $tableName
            )
        );
    }
}
