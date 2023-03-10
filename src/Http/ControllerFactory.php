<?php
declare(strict_types=1);

use function Cake\Core\deprecationWarning;

deprecationWarning(
    'Since 4.2.0: Cake\Http\ControllerFactory is deprecated. ' .
    'Use Cake\Controller\ControllerFactory instead.'
);
class_exists('Cake\Controller\ControllerFactory');
