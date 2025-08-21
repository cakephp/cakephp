<?php
declare(strict_types=1);

/**
 * Class MacroableTraitTest
 *
 * This test suite verifies the behavior of the CakePHP MacroableTrait trait.
 * It ensures macros can be registered, invoked dynamically and statically,
 * can work with closures, invokable classes, parameters, and can access
 * private properties/methods through binding.
 *
 * It also ensures exceptions are thrown when attempting to call
 * undefined macros.
 */
namespace Cake\Test\TestCase\Utility;

use BadMethodCallException;
use Cake\Utility\MacroableTrait;
use PHPUnit\Framework\TestCase;

class MacroableTraitTest extends TestCase
{
    /**
     * @var object an anonymous class instance using the MacroableTrait trait
     */
    private object $MacroableTraitClass;

    /**
     * Set up the test environment by creating a new class
     * that uses the MacroableTrait trait and contains private
     * properties and methods to test binding.
     */
    protected function setUp(): void
    {
        $this->MacroableTraitClass = new class ()
        {
            use MacroableTrait;

            private $privateVariable = 'privateValue';

            private static function getPrivateStatic(): string
            {
                return 'privateStaticValue';
            }
        };
    }

    /**
     * Test that a new macro can be registered and called
     * on an instance of the class.
     */
    public function testCanRegisterAndCallMacro(): void
    {
        $this->MacroableTraitClass::macro('newMethod', function () {
            return 'newValue';
        });

        $this->assertSame('newValue', $this->MacroableTraitClass->newMethod());
    }

    /**
     * Test that a new macro can be registered and called
     * statically on the class.
     */
    public function testCanRegisterAndCallMacroStatically(): void
    {
        $this->MacroableTraitClass::macro('newMethod', function () {
            return 'newValue';
        });

        $this->assertSame('newValue', $this->MacroableTraitClass::newMethod());
    }

    /**
     * Test that an invokable class can be registered as a macro
     * and called dynamically and statically.
     */
    public function testCanRegisterInvokableClassAsMacro(): void
    {
        $this->MacroableTraitClass::macro('newMethod', new class ()
        {
            public function __invoke(): string
            {
                return 'newValue';
            }
        });

        $this->assertSame('newValue', $this->MacroableTraitClass->newMethod());
        $this->assertSame('newValue', $this->MacroableTraitClass::newMethod());
    }

    /**
     * Test that parameters are passed correctly into macros.
     */
    public function testParametersArePassedCorrectly(): void
    {
        $this->MacroableTraitClass::macro('concatenate', function (...$strings) {
            return implode('-', $strings);
        });

        $this->assertSame('one-two-three', $this->MacroableTraitClass->concatenate('one', 'two', 'three'));
    }

    /**
     * Test that registered macros are bound to the class context
     * and can access private properties.
     */
    public function testRegisteredMethodsAreBoundToTheClass(): void
    {
        $this->MacroableTraitClass::macro('newMethod', function () {
            /** @var object{privateVariable:string} $this */
            return $this->privateVariable;
        });

        $this->assertSame('privateValue', $this->MacroableTraitClass->newMethod());
    }

    /**
     * Test that macros can call private static methods
     * when defined inside the class.
     */
    public function testCanWorkOnStaticMethods(): void
    {
        $this->MacroableTraitClass::macro('testStatic', function () {
            /** @var class-string $this */
            return $this::getPrivateStatic();
        });

        $this->assertSame('privateStaticValue', $this->MacroableTraitClass->testStatic());
    }

    /**
     * Test that calling an undefined macro on an instance
     * throws a BadMethodCallException.
     */
    public function testThrowsExceptionIfMethodDoesNotExist(): void
    {
        $this->expectException(BadMethodCallException::class);

        $this->MacroableTraitClass->nonExistingMethod();
    }

    /**
     * Test that calling an undefined static macro
     * throws a BadMethodCallException.
     */
    public function testThrowsExceptionIfStaticMethodDoesNotExist(): void
    {
        $this->expectException(BadMethodCallException::class);

        $this->MacroableTraitClass::nonExistingMethod();
    }
}
