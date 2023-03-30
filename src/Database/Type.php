<?php
declare(strict_types=1);

use function Cake\Core\deprecationWarning;

deprecationWarning(
    'Since 4.0.0: Cake\Database\Type is deprecated. Use Cake\Database\TypeFactory instead.'
);
class_exists('Cake\Database\TypeFactory');
