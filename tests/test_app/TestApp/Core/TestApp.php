<?php
declare(strict_types=1);

namespace TestApp\Core;

use Cake\Core\App;

class TestApp extends App
{
    public static $existsInBaseCallback;

    protected static function _classExistsInBase(string $name, string $namespace): bool
    {
        $callback = static::$existsInBaseCallback;

        return $callback($name, $namespace);
    }
}
