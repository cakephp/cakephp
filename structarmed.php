<?php
declare(strict_types=1);

use Boundwize\StructArmed\Architecture;

return Architecture::define()
    ->layerPattern('Cache', '/^Cake\\\\Cache\\\\.*$/')
    ->layerPattern('Collection', '/^Cake\\\\Collection\\\\.*$/')
    ->layerPattern('Command', '/^Cake\\\\Command\\\\.*$/')
    ->layerPattern('Console', '/^Cake\\\\Console\\\\.*$/')
    ->layerPattern('Container', '/^Cake\\\\Container\\\\.*$/')
    ->layerPattern('Controller', '/^Cake\\\\Controller\\\\.*$/')
    ->layerPattern('Core', '/^Cake\\\\Core\\\\.*$/')
    ->layerPattern('Database', '/^Cake\\\\Database\\\\.*$/')
    ->layerPattern('Datasource', '/^Cake\\\\Datasource\\\\.*$/')
    ->layerPattern('Error', '/^Cake\\\\Error\\\\.*$/')
    ->layerPattern('Event', '/^Cake\\\\Event\\\\.*$/')
    ->layerPattern('Form', '/^Cake\\\\Form\\\\.*$/')
    ->layerPattern('Http', '/^Cake\\\\Http\\\\.*$/')
    ->layerPattern('I18n', '/^Cake\\\\I18n\\\\.*$/')
    ->layerPattern('Lock', '/^Cake\\\\Lock\\\\.*$/')
    ->layerPattern('Log', '/^Cake\\\\Log\\\\.*$/')
    ->layerPattern('Mailer', '/^Cake\\\\Mailer\\\\.*$/')
    ->layerPattern('Network', '/^Cake\\\\Network\\\\.*$/')
    ->layerPattern('ORM', '/^Cake\\\\ORM\\\\.*$/')
    ->layerPattern('Routing', '/^Cake\\\\Routing\\\\.*$/')
    ->layerPattern('TestSuite', '/^Cake\\\\TestSuite\\\\.*$/')
    ->layerPattern('Utility', '/^Cake\\\\Utility\\\\.*$/')
    ->layerPattern('Validation', '/^Cake\\\\Validation\\\\.*$/')
    ->layerPattern('View', '/^Cake\\\\View\\\\.*$/')
    ->ruleset([
        'Cache' => ['+Event', 'Log', 'Utility'],
        'Collection' => [],
        'Command' => ['Console', '+Database', 'ORM', '+Routing'],
        'Console' => ['Command', 'Database', 'Error', '+Event', 'Log', 'Routing', 'Utility'],
        'Container' => [],
        'Controller' => ['Datasource', '+Event', 'Form', 'Http', 'Log', 'ORM', 'Routing', 'Utility', 'View'],
        'Core' => ['Cache', 'Console', 'Container', 'Event', 'Http', 'Routing', 'Utility'],
        'Database' => ['+Cache', 'Datasource', 'I18n'],
        'Datasource' => ['Cache', 'Collection', 'Database', '+Event', 'Utility'],
        'Error' => ['Controller', 'Database', '+Log', '+Routing', 'View'],
        'Event' => ['Core'],
        'Form' => ['+Event', 'Utility', 'Validation'],
        'Http' => ['+Cache', 'Console', 'Controller', 'Datasource', 'Error', 'I18n', 'ORM', 'Routing'],
        'I18n' => ['Cache', 'Core', 'Utility'],
        'Lock' => ['+Event', 'Log', 'Utility'],
        'Log' => ['Console', '+Event', 'Utility'],
        'Mailer' => ['Datasource', '+Event', 'Http', 'Log', 'Network', 'ORM', 'Utility', 'View'],
        'Network' => ['Core', 'Utility', 'Validation'],
        'ORM' => ['Collection', 'Database', 'Datasource', 'Event', '+Utility', 'Validation'],
        'Routing' => ['Http', '+Utility'],
        'TestSuite' => ['Collection', 'Controller', 'Database', 'Datasource', 'Error', '+Event', 'Form', 'Http', 'Log', 'Mailer', 'ORM', 'Routing', 'Utility'],
        'Utility' => ['Core', 'I18n'],
        'Validation' => ['Event', 'ORM', '+Utility'],
        'View' => ['+Cache', 'Form', '+ORM', '+Routing'],
    ]);
