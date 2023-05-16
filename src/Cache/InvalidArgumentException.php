<?php
declare(strict_types=1);

use function Cake\Core\deprecationWarning;

/**
 * @deprecated 4.4.12 Use {@link \Cake\Cache\Exception\InvalidArgumentException} instead.
 */
deprecationWarning(
    'Since 4.4.12: Cake\Cache\InvalidArgumentException is deprecated ' .
    'use Cake\Cache\Exception\InvalidArgumentException instead'
);
class_exists('Cake\Cache\Exception\InvalidArgumentException');
