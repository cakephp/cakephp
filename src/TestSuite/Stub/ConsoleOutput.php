<?php
declare(strict_types=1);

deprecationWarning(
    'Since 4.3.0: Cake\TestSuite\Stub\ConsoleOutput is deprecated. ' .
    'Use Cake\Console\TestSuite\StubConsoleOutput instead.'
);
class_exists('Cake\Console\TestSuite\StubConsoleOutput');
