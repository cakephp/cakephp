<?php
declare(strict_types=1);

/**
 * ValidationSetTest file
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/view/1196/Testing>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license infValidationation, please see the LICENSE.txt
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
use Cake\Validation\ValidationSet;

/**
 * ValidationSetTest
 */
class ValidationSetTest extends TestCase
{
    /**
     * testGetRule method
     *
     * @return void
     */
    public function testGetRule()
    {
        $field = new ValidationSet();
        $field->add('notBlank', ['rule' => 'notBlank', 'message' => 'Can not be empty']);
        $result = $field->rule('notBlank');
        $this->assertInstanceOf('Cake\Validation\ValidationRule', $result);
        $expected = new ValidationRule(['rule' => 'notBlank', 'message' => 'Can not be empty']);
        $this->assertEquals($expected, $result);
    }

    /**
     * testGetRules method
     *
     * @return void
     */
    public function testGetRules()
    {
        $field = new ValidationSet();
        $field->add('notBlank', ['rule' => 'notBlank', 'message' => 'Can not be empty']);

        $result = $field->rules();
        $this->assertEquals(['notBlank'], array_keys($result));
        $this->assertInstanceOf('Cake\Validation\ValidationRule', $result['notBlank']);
    }

    /**
     * Tests getting a rule from the set using array access
     *
     * @return void
     */
    public function testArrayAccessGet()
    {
        $set = (new ValidationSet())
            ->add('notBlank', ['rule' => 'notBlank'])
            ->add('numeric', ['rule' => 'numeric'])
            ->add('other', ['rule' => 'email']);

        $rule = $set['notBlank'];
        $this->assertInstanceOf('Cake\Validation\ValidationRule', $rule);
        $this->assertEquals(new ValidationRule(['rule' => 'notBlank']), $rule);

        $rule = $set['numeric'];
        $this->assertInstanceOf('Cake\Validation\ValidationRule', $rule);
        $this->assertEquals(new ValidationRule(['rule' => 'numeric']), $rule);

        $rule = $set['other'];
        $this->assertInstanceOf('Cake\Validation\ValidationRule', $rule);
        $this->assertEquals(new ValidationRule(['rule' => 'email']), $rule);
    }

    /**
     * Tests checking a rule from the set using array access
     *
     * @return void
     */
    public function testArrayAccessExists()
    {
        $set = (new ValidationSet())
            ->add('notBlank', ['rule' => 'notBlank'])
            ->add('numeric', ['rule' => 'numeric'])
            ->add('other', ['rule' => 'email']);

        $this->assertArrayHasKey('notBlank', $set);
        $this->assertArrayHasKey('numeric', $set);
        $this->assertArrayHasKey('other', $set);
        $this->assertArrayNotHasKey('fail', $set);
    }

    /**
     * Tests setting a rule in the set using array access
     *
     * @return void
     */
    public function testArrayAccessSet()
    {
        $set = (new ValidationSet())
            ->add('notBlank', ['rule' => 'notBlank']);

        $this->assertArrayNotHasKey('other', $set);
        $set['other'] = ['rule' => 'email'];
        $rule = $set['other'];
        $this->assertInstanceOf('Cake\Validation\ValidationRule', $rule);
        $this->assertEquals(new ValidationRule(['rule' => 'email']), $rule);
    }

    /**
     * Tests unseting a rule from the set using array access
     *
     * @return void
     */
    public function testArrayAccessUnset()
    {
        $set = (new ValidationSet())
            ->add('notBlank', ['rule' => 'notBlank'])
            ->add('numeric', ['rule' => 'numeric'])
            ->add('other', ['rule' => 'email']);

        unset($set['notBlank']);
        $this->assertArrayNotHasKey('notBlank', $set);

        unset($set['numeric']);
        $this->assertArrayNotHasKey('numeric', $set);

        unset($set['other']);
        $this->assertArrayNotHasKey('other', $set);
    }

    /**
     * Tests it is possible to iterate a validation set object
     *
     * @return void
     */
    public function testIterator()
    {
        $set = (new ValidationSet())
            ->add('notBlank', ['rule' => 'notBlank'])
            ->add('numeric', ['rule' => 'numeric'])
            ->add('other', ['rule' => 'email']);

        $i = 0;
        foreach ($set as $name => $rule) {
            if ($i === 0) {
                $this->assertSame('notBlank', $name);
            }
            if ($i === 1) {
                $this->assertSame('numeric', $name);
            }
            if ($i === 2) {
                $this->assertSame('other', $name);
            }
            $this->assertInstanceOf('Cake\Validation\ValidationRule', $rule);
            $i++;
        }
        $this->assertSame(3, $i);
    }

    /**
     * Tests countable interface
     *
     * @return void
     */
    public function testCount()
    {
        $set = (new ValidationSet())
            ->add('notBlank', ['rule' => 'notBlank'])
            ->add('numeric', ['rule' => 'numeric'])
            ->add('other', ['rule' => 'email']);
        $this->assertCount(3, $set);

        unset($set['other']);
        $this->assertCount(2, $set);
    }

    /**
     * Test removeRule method
     *
     * @return void
     */
    public function testRemoveRule()
    {
        $set = (new ValidationSet())
            ->add('notBlank', ['rule' => 'notBlank'])
            ->add('numeric', ['rule' => 'numeric'])
            ->add('other', ['rule' => 'email']);

        $this->assertArrayHasKey('notBlank', $set);
        $set->remove('notBlank');
        $this->assertArrayNotHasKey('notBlank', $set);

        $this->assertArrayHasKey('numeric', $set);
        $set->remove('numeric');
        $this->assertArrayNotHasKey('numeric', $set);

        $this->assertArrayHasKey('other', $set);
        $set->remove('other');
        $this->assertArrayNotHasKey('other', $set);
    }

    /**
     * Test requirePresence and isPresenceRequired methods
     *
     * @return void
     */
    public function testRequirePresence()
    {
        $set = new ValidationSet();

        $this->assertFalse($set->isPresenceRequired());

        $set->requirePresence(true);
        $this->assertTrue($set->isPresenceRequired());

        $set->requirePresence(false);
        $this->assertFalse($set->isPresenceRequired());
    }

    /**
     * Test allowEmpty and isEmptyAllowed methods
     *
     * @return void
     */
    public function testAllowEmpty()
    {
        $set = new ValidationSet();

        $this->assertFalse($set->isEmptyAllowed());

        $set->allowEmpty(true);
        $this->assertTrue($set->isEmptyAllowed());

        $set->allowEmpty(false);
        $this->assertFalse($set->isEmptyAllowed());
    }
}
