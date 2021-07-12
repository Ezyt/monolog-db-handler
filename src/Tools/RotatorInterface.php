<?php

declare(strict_types=1);

namespace Ezyt\MonologBD\Tools;

use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Exception;
use Symfony\Component\Console\Style\OutputStyle;

interface RotatorInterface
{
    /**
     * @param OutputStyle $io
     * @param int $historySize
     * @throws ConnectionException
     * @throws Exception
     */
    public function rotate(OutputStyle $io, int $historySize): void;
}
