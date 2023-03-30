<?php
declare(strict_types=1);

use function Cake\Core\deprecationWarning;

deprecationWarning(
    'Since 4.2.0: Cake\Database\Exception is deprecated. ' .
    'Use Cake\Database\Exception\DatabaseException instead.'
);
class_exists('Cake\Database\Exception\DatabaseException');
