<?php

declare(strict_types=1);

namespace Ezyt\MonologBD\Tools;

use Symfony\Component\Console\Style\OutputStyle;

interface InitializerInterface
{
    public function init(OutputStyle $io): void;
}
