<?php
declare(strict_types=1);

use Cake\Console\Helper\TableHelper;
use function Cake\Core\deprecationWarning;

deprecationWarning('5.4.0', 'Cake\Command\Helper\TableHelper is deprecated. Use Cake\Console\Helper\TableHelper instead.');

class_exists(TableHelper::class);
