<?php
declare(strict_types=1);

/**
 * CakePHP(tm) Tests <https://book.cakephp.org/view/1196/Testing>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @since         2.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Validation;

use Cake\TestSuite\TestCase;
use Cake\Validation\ValidationRule;
use InvalidArgumentException;

/**
 * ValidationRuleTest
 */
class ValidationRuleTest extends TestCase
{
    /**
     * Auxiliary method to test custom validators
     */
    public function willFail(): bool
    {
        return false;
    }

    /**
     * Auxiliary method to test custom validators
     */
    public function willPass(): bool
    {
        return true;
    }

    /**
     * Auxiliary method to test custom validators
     */
    public function willFail3(): string
    {
        return 'string';
    }

    /**
     * tests that passing custom validation methods work
     */
    public function testCustomMethods(): void
    {
        $data = 'some data';
        $providers = ['default' => $this];

        $context = ['newRecord' => true];
        $Rule = new ValidationRule(['rule' => 'willFail']);
        $this->assertFalse($Rule->process($data, $providers, $context));

        $Rule = new ValidationRule(['rule' => 'willPass', 'pass' => ['key' => 'value']]);
        $this->assertTrue($Rule->process($data, $providers, $context));

        $Rule = new ValidationRule(['rule' => 'willFail3']);
        $this->assertSame('string', $Rule->process($data, $providers, $context));

        $Rule = new ValidationRule(['rule' => 'willFail', 'message' => 'foo']);
        $this->assertSame('foo', $Rule->process($data, $providers, $context));
    }

    /**
     * Test using a custom validation method with no provider declared.
     */
    public function testCustomMethodNoProvider(): void
    {
        $data = 'some data';
        $context = ['field' => 'custom', 'newRecord' => true];
        $providers = ['default' => ''];

        $rule = new ValidationRule([
            'rule' => [$this, 'willFail'],
        ]);
        $this->assertFalse($rule->process($data, $providers, $context));
    }

    /**
     * Make sure errors are triggered when validation is missing.
     */
    public function testCustomMethodMissingError(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to call method "totallyMissing" in "default" provider for field "test"');
        $def = ['rule' => ['totallyMissing']];
        $data = 'some data';
        $providers = ['default' => $this];

        $Rule = new ValidationRule($def);
        $Rule->process($data, $providers, ['newRecord' => true, 'field' => 'test']);
    }

    /**
     * Tests that a rule can be skipped
     */
    public function testSkip(): void
    {
        $data = 'some data';
        $providers = ['default' => $this];

        $Rule = new ValidationRule([
            'rule' => 'willFail',
            'on' => 'create',
        ]);
        $this->assertFalse($Rule->process($data, $providers, ['newRecord' => true]));

        $Rule = new ValidationRule([
            'rule' => 'willFail',
            'on' => 'update',
        ]);
        $this->assertTrue($Rule->process($data, $providers, ['newRecord' => true]));

        $Rule = new ValidationRule([
            'rule' => 'willFail',
            'on' => 'update',
        ]);
        $this->assertFalse($Rule->process($data, $providers, ['newRecord' => false]));
    }

    /**
     * Tests that the 'on' key can be a callable function
     */
    public function testCallableOn(): void
    {
        $data = 'some data';
        $providers = ['default' => $this];

        $Rule = new ValidationRule([
            'rule' => 'willFail',
            'on' => function ($context) use ($providers) {
                $expected = compact('providers') + ['newRecord' => true, 'data' => []];
                $this->assertEquals($expected, $context);

                return true;
            },
        ]);
        $this->assertFalse($Rule->process($data, $providers, ['newRecord' => true]));

        $Rule = new ValidationRule([
            'rule' => 'willFail',
            'on' => function ($context) use ($providers) {
                $expected = compact('providers') + ['newRecord' => true, 'data' => []];
                $this->assertEquals($expected, $context);

                return false;
            },
        ]);
        $this->assertTrue($Rule->process($data, $providers, ['newRecord' => true]));
    }

    /**
     * testGet
     */
    public function testGet(): void
    {
        $Rule = new ValidationRule(['rule' => 'willFail', 'message' => 'foo']);

        $this->assertSame('willFail', $Rule->get('rule'));
        $this->assertSame('foo', $Rule->get('message'));
        $this->assertSame('default', $Rule->get('provider'));
        $this->assertEquals([], $Rule->get('pass'));
        $this->assertNull($Rule->get('nonexistent'));

        $Rule = new ValidationRule(['rule' => ['willPass', 'param'], 'message' => 'bar']);

        $this->assertSame('willPass', $Rule->get('rule'));
        $this->assertSame('bar', $Rule->get('message'));
        $this->assertEquals(['param'], $Rule->get('pass'));
    }
}
