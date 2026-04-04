<?php
declare(strict_types=1);

use Cake\Console\Helper\TreeHelper;
use function Cake\Core\deprecationWarning;

deprecationWarning('5.4.0', 'Cake\Command\Helper\TreeHelper is deprecated. Use Cake\Console\Helper\TreeHelper instead.');

class_exists(TreeHelper::class);
