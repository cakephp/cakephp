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
        'Cache' => ['Core', 'Event', 'Log', 'Utility'],
        'Collection' => [],
        'Command' => ['Cache', 'Console', 'Core', 'Database', 'Datasource', 'Event', 'Http', 'I18n', 'Log', 'ORM', 'Routing', 'Utility'],
        'Console' => ['Command', 'Core', 'Database', 'Error', 'Event', 'Log', 'Routing', 'Utility'],
        'Container' => [],
        'Controller' => ['Core', 'Datasource', 'Event', 'Form', 'Http', 'Log', 'ORM', 'Routing', 'Utility', 'View'],
        'Core' => ['Cache', 'Console', 'Container', 'Event', 'Http', 'Routing', 'Utility'],
        'Database' => ['Cache', 'Core', 'Datasource', 'Event', 'I18n', 'Log', 'Utility'],
        'Datasource' => ['Cache', 'Collection', 'Core', 'Database', 'Event', 'Utility'],
        'Error' => ['Console', 'Controller', 'Core', 'Database', 'Event', 'Http', 'I18n', 'Log', 'Routing', 'Utility', 'View'],
        'Event' => ['Core'],
        'Form' => ['Core', 'Event', 'Utility', 'Validation'],
        'Http' => ['Cache', 'Console', 'Controller', 'Core', 'Datasource', 'Error', 'Event', 'I18n', 'Log', 'ORM', 'Routing', 'Utility'],
        'I18n' => ['Cache', 'Core', 'Utility'],
        'Lock' => ['Core', 'Event', 'Log', 'Utility'],
        'Log' => ['Console', 'Core', 'Event', 'Utility'],
        'Mailer' => ['Core', 'Datasource', 'Event', 'Http', 'Log', 'Network', 'ORM', 'Utility', 'View'],
        'Network' => ['Core', 'Utility', 'Validation'],
        'ORM' => ['Collection', 'Core', 'Database', 'Datasource', 'Event', 'I18n', 'Utility', 'Validation'],
        'Routing' => ['Core', 'Http', 'I18n', 'Utility'],
        'TestSuite' => ['Collection', 'Controller', 'Core', 'Database', 'Datasource', 'Error', 'Event', 'Form', 'Http', 'Log', 'Mailer', 'ORM', 'Routing', 'Utility'],
        'Utility' => ['Core', 'I18n'],
        'Validation' => ['Core', 'Event', 'I18n', 'ORM', 'Utility'],
        'View' => ['Cache', 'Collection', 'Core', 'Database', 'Datasource', 'Event', 'Form', 'Http', 'I18n', 'Log', 'ORM', 'Routing', 'Utility', 'Validation'],
    ]);
