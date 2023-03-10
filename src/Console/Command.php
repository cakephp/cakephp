<?php
declare(strict_types=1);

use function Cake\Core\deprecationWarning;

deprecationWarning(
    'Since 4.0.0: Cake\Console\Command is deprecated use Cake\Command\Command instead'
);
class_exists('Cake\Command\Command');
