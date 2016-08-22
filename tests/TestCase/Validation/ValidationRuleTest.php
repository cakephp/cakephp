<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @since         2.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Validation;

use Cake\TestSuite\TestCase;
use Cake\Validation\ValidationRule;

/**
 * ValidationRuleTest
 */
class ValidationRuleTest extends TestCase
{

    /**
     * Auxiliary method to test custom validators
     *
     * @return bool
     */
    public function willFail()
    {
        return false;
    }

    /**
     * Auxiliary method to test custom validators
     *
     * @return bool
     */
    public function willPass()
    {
        return true;
    }

    /**
     * Auxiliary method to test custom validators
     *
     * @return string
     */
    public function willFail3()
    {
        return 'string';
    }

    /**
     * tests that passing custom validation methods work
     *
     * @return void
     */
    public function testCustomMethods()
    {
        $data = 'some data';
        $providers = ['default' => $this];

        $context = ['newRecord' => true];
        $Rule = new ValidationRule(['rule' => 'willFail']);
        $this->assertFalse($Rule->process($data, $providers, $context));

        $Rule = new ValidationRule(['rule' => 'willPass']);
        $this->assertTrue($Rule->process($data, $providers, $context));

        $Rule = new ValidationRule(['rule' => 'willFail3']);
        $this->assertEquals('string', $Rule->process($data, $providers, $context));

        $Rule = new ValidationRule(['rule' => 'willFail', 'message' => 'foo']);
        $this->assertEquals('foo', $Rule->process($data, $providers, $context));
    }

    /**
     * Test using a custom validation method with no provider declared.
     *
     * @return void
     */
    public function testCustomMethodNoProvider()
    {
        $data = 'some data';
        $context = ['field' => 'custom', 'newRecord' => true];
        $providers = ['default' => ''];

        $rule = new ValidationRule([
            'rule' => [$this, 'willFail']
        ]);
        $this->assertFalse($rule->process($data, $providers, $context));
    }

    /**
     * Make sure errors are triggered when validation is missing.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unable to call method "totallyMissing" in "default" provider for field "test"
     * @return void
     */
    public function testCustomMethodMissingError()
    {
        $def = ['rule' => ['totallyMissing']];
        $data = 'some data';
        $providers = ['default' => $this];

        $Rule = new ValidationRule($def);
        $Rule->process($data, $providers, ['newRecord' => true, 'field' => 'test']);
    }

    /**
     * Tests that a rule can be skipped
     *
     * @return void
     */
    public function testSkip()
    {
        $data = 'some data';
        $providers = ['default' => $this];

        $Rule = new ValidationRule([
            'rule' => 'willFail',
            'on' => 'create'
        ]);
        $this->assertFalse($Rule->process($data, $providers, ['newRecord' => true]));

        $Rule = new ValidationRule([
            'rule' => 'willFail',
            'on' => 'update'
        ]);
        $this->assertTrue($Rule->process($data, $providers, ['newRecord' => true]));

        $Rule = new ValidationRule([
            'rule' => 'willFail',
            'on' => 'update'
        ]);
        $this->assertFalse($Rule->process($data, $providers, ['newRecord' => false]));
    }

    /**
     * Tests that the 'on' key can be a callable function
     *
     * @return void
     */
    public function testCallableOn()
    {
        $data = 'some data';
        $providers = ['default' => $this];

        $Rule = new ValidationRule([
            'rule' => 'willFail',
            'on' => function ($context) use ($providers) {
                $expected = compact('providers') + ['newRecord' => true, 'data' => []];
                $this->assertEquals($expected, $context);

                return true;
            }
        ]);
        $this->assertFalse($Rule->process($data, $providers, ['newRecord' => true]));

        $Rule = new ValidationRule([
            'rule' => 'willFail',
            'on' => function ($context) use ($providers) {
                $expected = compact('providers') + ['newRecord' => true, 'data' => []];
                $this->assertEquals($expected, $context);

                return false;
            }
        ]);
        $this->assertTrue($Rule->process($data, $providers, ['newRecord' => true]));
    }

    /**
     * testGet
     *
     * @return void
     */
    public function testGet()
    {
        $Rule = new ValidationRule(['rule' => 'willFail', 'message' => 'foo']);

        $this->assertEquals('willFail', $Rule->get('rule'));
        $this->assertEquals('foo', $Rule->get('message'));
        $this->assertEquals('default', $Rule->get('provider'));
        $this->assertEquals([], $Rule->get('pass'));
        $this->assertNull($Rule->get('non-existent'));

        $Rule = new ValidationRule(['rule' => ['willPass', 'param'], 'message' => 'bar']);

        $this->assertEquals('willPass', $Rule->get('rule'));
        $this->assertEquals('bar', $Rule->get('message'));
        $this->assertEquals(['param'], $Rule->get('pass'));
    }
}
