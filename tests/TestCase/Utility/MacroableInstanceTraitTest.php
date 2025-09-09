<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Utility;

use BadMethodCallException;
use Cake\Utility\MacroableInstanceTrait;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Class MacroableInstanceTraitTest
 *
 * This test suite verifies the behavior of the MacroableInstanceTrait.
 * It ensures instance-level macros can be registered, invoked on objects,
 * work with closures or invokable classes, and can access private
 * instance methods through binding.
 *
 * It also ensures exceptions are thrown when attempting to call
 * undefined or non-callable macros.
 */
class MacroableInstanceTraitTest extends TestCase
{
    /**
     * @var object An anonymous class instance using MacroableInstanceTrait
     */
    private object $macroableTraitClass;

    /**
     * Set up the test environment by creating a new class
     * that uses MacroableInstanceTrait and contains private
     * methods to test closure binding.
     */
    protected function setUp(): void
    {
        $this->macroableTraitClass = new class ()
        {
            use MacroableInstanceTrait;

            private function getPrivateValue(): string
            {
                return 'privateInstanceValue';
            }
        };
    }

    protected function tearDown(): void
    {
        // Ensure macros are cleared between tests
        $this->macroableTraitClass::clearMacros();
    }

    /**
     * Test that a new instance macro can be registered and called.
     */
    public function testCanRegisterAndCallMacro(): void
    {
        $this->macroableTraitClass::macro('sayHello', function (string $name): string {
            return "Hello, {$name}";
        });

        $this->assertSame('Hello, World', $this->macroableTraitClass->sayHello('World'));
    }

    /**
     * Test that an invokable class can be registered as an instance macro
     * and called on an object.
     */
    public function testCanRegisterInvokableClassAsMacro(): void
    {
        $this->macroableTraitClass::macro('double', new class () {
            public function __invoke(int $value): int
            {
                return $value * 2;
            }
        });

        $this->assertSame(10, $this->macroableTraitClass->double(5));
    }

    /**
     * Test that macros can call private instance methods
     * when bound to the object context.
     */
    public function testCanWorkOnPrivateMethods(): void
    {
        $this->macroableTraitClass::macro('getSecret', function (): string {
            /** @var object{getPrivateValue: callable():string} $this */
            return $this->getPrivateValue();
        });

        $this->assertSame('privateInstanceValue', $this->macroableTraitClass->getSecret());
    }

    /**
     * Test that calling an undefined macro
     * throws a BadMethodCallException.
     */
    public function testThrowsExceptionIfMethodDoesNotExist(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->macroableTraitClass->nonExistingMethod();
    }

    /**
     * Test that registering a non-callable macro
     * throws a BadMethodCallException when invoked.
     */
    public function testThrowsExceptionIfMacroIsNotCallable(): void
    {
        $this->macroableTraitClass::macro('notCallable', new stdClass());

        $this->expectException(BadMethodCallException::class);
        $this->macroableTraitClass->notCallable();
    }

    /**
     * Test that all macros can be cleared.
     */
    public function testClearMacros(): void
    {
        $this->macroableTraitClass::macro('foo', fn() => 'bar');
        $this->assertTrue($this->macroableTraitClass::hasMacro('foo'));

        $this->macroableTraitClass::clearMacros();
        $this->assertFalse($this->macroableTraitClass::hasMacro('foo'));
    }
}
