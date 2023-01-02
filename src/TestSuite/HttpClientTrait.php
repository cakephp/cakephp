<?php
declare(strict_types=1);

deprecationWarning(
    'Since 4.3.0: Cake\TestSuite\HttpClientTrait is deprecated. ' .
    'Use Cake\Http\TestSuite\HttpClientTrait instead.'
);
class_exists('Cake\Http\TestSuite\HttpClientTrait');
