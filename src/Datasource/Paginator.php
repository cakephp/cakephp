<?php
declare(strict_types=1);

class_exists('Cake\Paging\NumericPaginator');
deprecationWarning(
    'Use Cake\Datasource\Paging\NumericPaginator instead of Cake\Datasource\Paginator.'
);
