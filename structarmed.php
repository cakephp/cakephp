<?php
declare(strict_types=1);

use Boundwize\StructArmed\Architecture;
use Boundwize\StructArmed\Rule\Rules\Layer\MayNotDependOnRule;

return Architecture::define()
    ->layerPattern('Collection', '/^Cake\\\\Collection\\\\.*$/')
    ->layerPattern('Database', '/^Cake\\\\Database\\\\.*$/')
    ->layerPattern('Datasource', '/^Cake\\\\Datasource\\\\.*$/')
    ->rule(
        'collection.must_not_depend_on_database',
        new MayNotDependOnRule(from: 'Collection', to: 'Database', toPath: 'Cake/Database')
    )
    ->rule(
        'collection.must_not_depend_on_orm',
        new MayNotDependOnRule(from: 'Collection', to: 'ORM', toPath: 'Cake/ORM')
    )
    ->rule(
        'database.must_not_depend_on_orm',
        new MayNotDependOnRule(from: 'Database', to: 'ORM', toPath: 'Cake/ORM')
    )
    ->rule(
        'datasource.must_not_depend_on_orm',
        new MayNotDependOnRule(from: 'Datasource', to: 'ORM', toPath: 'Cake/ORM')
    );
