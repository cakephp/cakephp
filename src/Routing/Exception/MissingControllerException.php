<?php
declare(strict_types=1);

deprecationWarning(
    'Since 4.2.0:  Cake\Routing\Exception\MissingControllerException is deprecated.' .
    'Use Cake\Http\Exception\MissingControllerException instead.'
);
class_exists('Cake\Http\Exception\MissingControllerException');
