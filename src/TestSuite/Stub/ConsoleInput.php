<?php
declare(strict_types=1);

deprecationWarning(
    'Since 4.3.0: Cake\TestSuite\Stub\ConsoleInput is deprecated. ' .
    'Use Cake\Console\TestSuite\StubConsoleInput.'
);
class_exists('Cake\Console\TestSuite\StubConsoleInput');
