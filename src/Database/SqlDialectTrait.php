<?php
declare(strict_types=1);

deprecationWarning(
    'Since 4.1.0: Cake\Database\SqlDialectTrait is deprecated. ' .
    'Use Cake\Database\Driver\SqlDialectTrait instead.'
);
class_exists('Cake\Database\Driver\SqlDialectTrait');
