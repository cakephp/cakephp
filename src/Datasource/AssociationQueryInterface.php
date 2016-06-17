<?php

namespace Cake\Datasource;

interface AssociationQueryInterface extends QueryInterface
{
    public function contain($associations = null, $override = false);
}
