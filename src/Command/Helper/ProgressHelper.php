<?php
declare(strict_types=1);

use Cake\Console\Helper\ProgressHelper;
use function Cake\Core\deprecationWarning;

deprecationWarning('5.4.0', 'Cake\Command\Helper\ProgressHelper is deprecated. Use Cake\Console\Helper\ProgressHelper instead.');

class_exists(ProgressHelper::class);
