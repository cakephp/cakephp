<?php
declare(strict_types=1);

use function Cake\Core\deprecationWarning;

deprecationWarning(
    'Since 4.3.0: Cake\Console\ConsoleErrorHandler is deprecated. ' .
    'Use Cake\Error\ConsoleErrorHandler instead.'
);
class_exists('Cake\Error\ConsoleErrorHandler');
