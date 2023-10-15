<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;

class AutoLoadModelCommand extends Command
{
    protected $modelClass = 'Posts';

    public $Posts = null;
}
