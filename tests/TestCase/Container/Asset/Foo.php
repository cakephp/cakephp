<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Asset;

class Foo
{
    public $bar;

    public $myString;

    public static $staticBar;

    public static $staticHello;

    public function __construct(?Bar $bar = null, ?string $myString = null)
    {
        $this->bar = $bar;
        $this->myString = $myString;
    }

    public function setBar(Bar $bar): void
    {
        $this->bar = $bar;
    }

    public static function staticSetBar(Bar $bar, $hello = 'hello world'): void
    {
        self::$staticHello = $hello;
        self::$staticBar = $bar;
    }
}
