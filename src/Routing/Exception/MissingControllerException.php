<?php
declare(strict_types=1);

class_alias(
    'Cake\Http\Exception\MissingControllerException',
    'Cake\Routing\Exception\MissingControllerException'
);
deprecationWarning(
    'Use Cake\Http\Exception\MissingControllerException instead of Cake\Routing\Exception\MissingControllerException.',
    0
);
