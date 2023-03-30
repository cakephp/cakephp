<?php
declare(strict_types=1);

use function Cake\Core\deprecationWarning;

deprecationWarning(
    'Since 4.3.0: Cake\TestSuite\ContainerStubTrait is deprecated. ' .
    'Use Cake\Core\TestSuite\ContainerStubTrait instead.'
);
class_exists('Cake\Core\TestSuite\ContainerStubTrait');
