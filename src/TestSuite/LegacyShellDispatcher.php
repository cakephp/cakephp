<?php
declare(strict_types=1);

deprecationWarning(
    'Since 4.3.0: Cake\TestSuite\LegacyShellDispatcher is deprecated. ' .
    'Use Cake\Console\TestSuite\LegacyShellDispatcher.'
);
class_exists('Cake\Console\TestSuite\LegacyShellDispatcher');
