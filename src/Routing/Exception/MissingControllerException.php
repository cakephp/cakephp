<?php
declare(strict_types=1);

class_exists('Cake\Http\Exception\MissingControllerException');
deprecationWarning(
    'Use Cake\Http\Exception\MissingControllerException instead of Cake\Routing\Exception\MissingControllerException.',
    0
);
