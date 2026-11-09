<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\Attribute\AsCommand;

/**
 * Command fixture for console integration tests.
 */
#[AsCommand('abstract')]
abstract class AbstractCommand extends Command
{
}
