<?php
declare(strict_types=1);

deprecationWarning(
    'Since 4.1.0: Cake\Database\Schema\SqliteSchema is deprecated. ' .
    'Use Cake\Database\Schema\SqliteSchemaDialect instead.'
);
class_exists('Cake\Database\Schema\SqliteSchemaDialect');
