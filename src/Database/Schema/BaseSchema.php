<?php
declare(strict_types=1);

deprecationWarning(
    'Since 4.1.0: Cake\Database\Schema\BaseSchema is deprecated. ' .
    'Use Cake\Database\Schema\SchemaDialect instead.'
);
class_exists('Cake\Database\Schema\SchemaDialect');
