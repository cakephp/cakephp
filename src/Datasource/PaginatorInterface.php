<?php
declare(strict_types=1);

deprecationWarning(
    'Since 4.2.0:  Cake\Datasource\PaginatorInterface is deprecated. ' .
    'Use Cake\Datasource\Paging\PaginatorInterface instead.'
);
class_exists('Cake\Datasource\Paging\PaginatorInterface');
