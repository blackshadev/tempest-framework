<?php

namespace Tempest\Console\Exceptions;

use RuntimeException;
use Tempest\Console\ConsoleOutput;

abstract class ConsoleException extends RuntimeException
{
    abstract public function render(ConsoleOutput $output): void;
}