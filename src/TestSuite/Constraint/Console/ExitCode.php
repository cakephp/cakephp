<?php
declare(strict_types=1);

deprecationWarning(
    'Since 4.3.0: Cake\TestSuite\Constraint\Console\ExitCode is deprecated. ' .
    'Use Cake\Console\TestSuite\Constraint\ExitCode instead.'
);
class_exists('Cake\Console\TestSuite\Constraint\ExitCode');
