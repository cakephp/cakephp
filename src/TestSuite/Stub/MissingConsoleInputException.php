<?php
declare(strict_types=1);

deprecationWarning(
    'Since 4.2.0: Cake\TestSuite\Stub\MissingConsoleInputException is deprecated. ' .
    'Use Cake\Console\TestSuite\MissingConsoleInputException instead.'
);
class_exists('Cake\Console\TestSuite\MissingConsoleInputException');
