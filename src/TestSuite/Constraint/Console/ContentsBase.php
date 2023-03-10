<?php
declare(strict_types=1);

use function Cake\Core\deprecationWarning;

deprecationWarning(
    'Since 4.3.0: Cake\TestSuite\Constraint\Console\ContentsBase is deprecated. ' .
    'Use Cake\Console\TestSuite\Constraint\ContentsBase instead.'
);
class_exists('Cake\Console\TestSuite\Constraint\ContentsBase');
