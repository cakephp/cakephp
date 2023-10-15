<?php
declare(strict_types=1);

use function Cake\Core\deprecationWarning;

deprecationWarning(
    'Since 4.3.0: Cake\TestSuite\LegacyCommandRunner is deprecated. ' .
    'Use Cake\Console\TestSuite\LegacyCommandRunner instead.'
);
class_exists('Cake\Console\TestSuite\LegacyCommandRunner');
