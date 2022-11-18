<?php
declare(strict_types=1);

class_exists('Cake\Error\ConsoleErrorHandler');
deprecationWarning(
    'Use Cake\Error\ConsoleErrorHandler instead of Cake\Console\ConsoleErrorHandler.'
);
