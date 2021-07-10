<?php

declare(strict_types=1);

namespace Ezyt\MonologBD\Command;

use Ezyt\MonologBD\Tools\InitializerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class InitCommand extends Command
{
    public const COMMAND_NAME = 'app:init:log';

    protected static $defaultName = self::COMMAND_NAME;

    protected $initializer;

    public function __construct(
        InitializerInterface $initializer,
        string $name = null
    ) {
        parent::__construct($name);
        $this->initializer = $initializer;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $this->initializer->init($io);
            $io->success('Log initialization successfully completed.');
        } catch (Throwable $exception) {
            $io->error('Failed to initialize logs: ' . $exception->getMessage());
        }

        return self::SUCCESS;
    }
}
