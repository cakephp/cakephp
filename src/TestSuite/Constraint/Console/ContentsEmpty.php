<?php
declare(strict_types=1);

deprecationWarning(
    'Since 4.3.0: Cake\TestSuite\Constraint\Console\ContentsEmpty is deprecated. ' .
    'Use Cake\Console\TestSuite\Constraint\ContentsEmpty instead.'
);
class_exists('Cake\Console\TestSuite\Constraint\ContentsEmpty');
