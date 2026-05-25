<?php
declare(strict_types=1);

use Boundwize\StructArmed\Architecture;
use Boundwize\StructArmed\Rule\Rules\Layer\MayNotDependOnRule;

return Architecture::define()
    ->layerPattern('Datasource', '/^Cake\\\\Datasource\\\\.*$/')
    ->rule(
        'datasource.must_not_depend_on_orm',
        new MayNotDependOnRule(from: 'Datasource', to: 'ORM', toPath: 'Cake/ORM')
    );
