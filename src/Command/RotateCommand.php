<?php

declare(strict_types=1);

namespace App\Command\Log;

use Ezyt\MonologBD\Tools\RotatorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class RotateCommand extends Command
{
    public const COMMAND_NAME = 'app:log:rotate';

    protected static $defaultName = self::COMMAND_NAME;

    protected const HISTORY_SIZE_KEY     = 'history_size';
    protected const DEFAULT_HISTORY_SIZE = '2';

    protected $rotator;

    public function __construct(
        RotatorInterface $rotator,
        string $name = null
    ) {
        parent::__construct($name);
        $this->rotator = $rotator;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function configure(): void
    {
        $this->addArgument(
            static::HISTORY_SIZE_KEY,
            InputArgument::OPTIONAL,
            'Quantity of archive tables',
            static::DEFAULT_HISTORY_SIZE
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $historySize = $input->getArgument(static::HISTORY_SIZE_KEY);
        try {
            if (!is_numeric($historySize)) {
                throw new InvalidArgumentException('HistorySize argument should be integer.');
            }
            $this->rotator->rotate($io, (int)$historySize);
            $io->success('Log rotation successfully completed.');
        } catch (Throwable $exception) {
            $io->error('Failed to rotate logs: ' . $exception->getMessage());
        }

        return self::SUCCESS;
    }
}
