<?php
declare(strict_types=1);

class_alias(
    'Cake\Controller\ControllerFactory',
    'Cake\Http\ControllerFactory'
);
deprecationWarning(
    'Use Cake\Controller\ControllerFactory instead of Cake\Http\ControllerFactory.'
);
