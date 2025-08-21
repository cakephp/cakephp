<?php
declare(strict_types=1);

use Cake\Utility\Macroable;
use PHPUnit\Framework\TestCase;

/**
 * Class MacroableTest
 *
 * This test suite verifies the behavior of the CakePHP Macroable trait.
 * It ensures macros can be registered, invoked dynamically and statically,
 * can work with closures, invokable classes, parameters, and can access
 * private properties/methods through binding.
 *
 * It also ensures exceptions are thrown when attempting to call
 * undefined macros.
 */
class MacroableTest extends TestCase
{
    /**
     * @var object an anonymous class instance using the Macroable trait
     */
    private $macroableClass;

    /**
     * Set up the test environment by creating a new class
     * that uses the Macroable trait and contains private
     * properties and methods to test binding.
     */
    protected function setUp(): void
    {
        $this->macroableClass = new class ()
        {
            private $privateVariable = 'privateValue';

            use Macroable;

            private static function getPrivateStatic()
            {
                return 'privateStaticValue';
            }
        };
    }

    /**
     * Test that a new macro can be registered and called
     * on an instance of the class.
     */
    public function testCanRegisterAndCallMacro()
    {
        $this->macroableClass::macro('newMethod', function () {
            return 'newValue';
        });

        $this->assertSame('newValue', $this->macroableClass->newMethod());
    }

    /**
     * Test that a new macro can be registered and called
     * statically on the class.
     */
    public function testCanRegisterAndCallMacroStatically()
    {
        $this->macroableClass::macro('newMethod', function () {
            return 'newValue';
        });

        $this->assertSame('newValue', $this->macroableClass::newMethod());
    }

    /**
     * Test that an invokable class can be registered as a macro
     * and called dynamically and statically.
     */
    public function testCanRegisterInvokableClassAsMacro()
    {
        $this->macroableClass::macro('newMethod', new class ()
        {
            public function __invoke()
            {
                return 'newValue';
            }
        });

        $this->assertSame('newValue', $this->macroableClass->newMethod());
        $this->assertSame('newValue', $this->macroableClass::newMethod());
    }

    /**
     * Test that parameters are passed correctly into macros.
     */
    public function testParametersArePassedCorrectly()
    {
        $this->macroableClass::macro('concatenate', function (...$strings) {
            return implode('-', $strings);
        });

        $this->assertSame('one-two-three', $this->macroableClass->concatenate('one', 'two', 'three'));
    }

    /**
     * Test that registered macros are bound to the class context
     * and can access private properties.
     */
    public function testRegisteredMethodsAreBoundToTheClass()
    {
        $this->macroableClass::macro('newMethod', function () {
            /** @var object{privateVariable:string} $this */
            return $this->privateVariable;
        });

        $this->assertSame('privateValue', $this->macroableClass->newMethod());
    }

    /**
     * Test that macros can call private static methods
     * when defined inside the class.
     */
    public function testCanWorkOnStaticMethods()
    {
        $this->macroableClass::macro('testStatic', function () {
            /** @var class-string $this */
            return $this::getPrivateStatic();
        });

        $this->assertSame('privateStaticValue', $this->macroableClass->testStatic());
    }

    /**
     * Test that calling an undefined macro on an instance
     * throws a BadMethodCallException.
     */
    public function testThrowsExceptionIfMethodDoesNotExist()
    {
        $this->expectException(BadMethodCallException::class);

        $this->macroableClass->nonExistingMethod();
    }

    /**
     * Test that calling an undefined static macro
     * throws a BadMethodCallException.
     */
    public function testThrowsExceptionIfStaticMethodDoesNotExist()
    {
        $this->expectException(BadMethodCallException::class);

        $this->macroableClass::nonExistingMethod();
    }
}
