<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Console\Command;

class AutoLoadModelCommand extends Command
{
    public $modelClass = 'Posts';

    public static function defaultName(): string
    {
        return 'auto_load_model';
    }
}
