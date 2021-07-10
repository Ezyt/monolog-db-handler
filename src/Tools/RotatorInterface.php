<?php

declare(strict_types=1);

namespace Ezyt\MonologBD\Tools;

use Doctrine\DBAL\ConnectionException;
use Symfony\Component\Console\Style\OutputStyle;

interface RotatorInterface
{
    /**
     * @param OutputStyle $io
     * @param int $historySize
     * @throws ConnectionException
     */
    public function rotate(OutputStyle $io, int $historySize): void;
}
