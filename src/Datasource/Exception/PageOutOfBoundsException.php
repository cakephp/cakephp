<?php
declare(strict_types=1);

use function Cake\Core\deprecationWarning;

deprecationWarning(
    'Since 4.2.0: Cake\Datasource\Exception\PageOutOfBoundsException is deprecated. ' .
    'Use Cake\Datasource\Paging\Exception\PageOutOfBoundsException instead.'
);
class_exists('Cake\Datasource\Paging\Exception\PageOutOfBoundsException');
