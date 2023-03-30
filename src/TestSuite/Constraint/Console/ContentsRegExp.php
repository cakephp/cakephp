<?php
declare(strict_types=1);

use function Cake\Core\deprecationWarning;

deprecationWarning(
    'Since 4.3.0: Cake\TestSuite\Constraint\Console\ContentsRegExp is deprecated. ' .
    'Use Cake\Console\TestSuite\Constraint\ContentsRegExp instead.'
);
class_exists('Cake\Console\TestSuite\Constraint\ContentsRegExp');
