<?php
declare(strict_types=1);

deprecationWarning(
    'Since 4.1.0: Cake\Database\Schema\PostgresSchema is deprecated. ' .
    'Use Cake\Database\Schema\PostgresSchemaDialect instead.'
);
class_exists('Cake\Database\Schema\PostgresSchemaDialect');
