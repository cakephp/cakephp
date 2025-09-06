<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Utility;

use BadMethodCallException;
use Cake\Utility\MacroableStaticTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class MacroableStaticTraitTest
 *
 * This test suite verifies the behavior of the MacroableStaticTrait.
 * It ensures static-level macros can be registered, invoked statically,
 * work with closures or invokable classes, and can access private static
 * methods through binding.
 *
 * It also ensures exceptions are thrown when attempting to call undefined
 * static macros.
 */
class MacroableStaticTraitTest extends TestCase
{
    /**
     * @var object An anonymous class instance using MacroableStaticTrait
     */
    private object $macroableTraitClass;

    /**
     * Set up the test environment by creating a new class
     * that uses MacroableStaticTrait and contains private static
     * methods to test binding.
     */
    protected function setUp(): void
    {
        $this->macroableTraitClass = new class ()
        {
            use MacroableStaticTrait;

            private static function getPrivateStatic(): string
            {
                return 'privateStaticValue';
            }
        };
    }

    /**
     * Test that a new static macro can be registered and called.
     */
    public function testCanRegisterAndCallMacroStatically(): void
    {
        $this->macroableTraitClass::macro('newMethod', function () {
            return 'newValue';
        });

        $this->assertSame('newValue', $this->macroableTraitClass::newMethod());
    }

    /**
     * Test that an invokable class can be registered as a static macro
     * and called statically.
     */
    public function testCanRegisterInvokableClassAsMacro(): void
    {
        $this->macroableTraitClass::macro('newMethod', new class ()
        {
            public function __invoke(): string
            {
                return 'newValue';
            }
        });

        $this->assertSame('newValue', $this->macroableTraitClass::newMethod());
    }

    /**
     * Test that static macros can call private static methods
     * when bound to the class context.
     */
    public function testCanWorkOnStaticMethods(): void
    {
        $this->macroableTraitClass::macro('testStatic', function () {
            /** @var class-string $class */
            $class = static::class;

            return $class::getPrivateStatic();
        });

        $this->assertSame('privateStaticValue', $this->macroableTraitClass::testStatic());
    }

    /**
     * Test that calling an undefined static macro
     * throws a BadMethodCallException.
     */
    public function testThrowsExceptionIfStaticMethodDoesNotExist(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->macroableTraitClass::nonExistingMethod();
    }
}
