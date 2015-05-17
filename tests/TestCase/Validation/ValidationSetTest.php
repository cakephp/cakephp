<?php
/**
 * ValidationSetTest file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license infValidationation, please see the LICENSE.txt
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
use Cake\Validation\ValidationSet;

/**
 * ValidationSetTest
 *
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
        $field = new ValidationSet;
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
        $field = new ValidationSet;
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
        $set = (new ValidationSet)
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
        $set = (new ValidationSet)
            ->add('notBlank', ['rule' => 'notBlank'])
            ->add('numeric', ['rule' => 'numeric'])
            ->add('other', ['rule' => 'email']);

        $this->assertTrue(isset($set['notBlank']));
        $this->assertTrue(isset($set['numeric']));
        $this->assertTrue(isset($set['other']));
        $this->assertFalse(isset($set['fail']));
    }

    /**
     * Tests setting a rule in the set using array access
     *
     * @return void
     */
    public function testArrayAccessSet()
    {
        $set = (new ValidationSet)
            ->add('notBlank', ['rule' => 'notBlank']);

        $this->assertFalse(isset($set['other']));
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
        $set = (new ValidationSet)
            ->add('notBlank', ['rule' => 'notBlank'])
            ->add('numeric', ['rule' => 'numeric'])
            ->add('other', ['rule' => 'email']);

        unset($set['notBlank']);
        $this->assertFalse(isset($set['notBlank']));

        unset($set['numeric']);
        $this->assertFalse(isset($set['numeric']));

        unset($set['other']);
        $this->assertFalse(isset($set['other']));
    }

    /**
     * Tests it is possible to iterate a validation set object
     *
     * @return void
     */
    public function testIterator()
    {
        $set = (new ValidationSet)
            ->add('notBlank', ['rule' => 'notBlank'])
            ->add('numeric', ['rule' => 'numeric'])
            ->add('other', ['rule' => 'email']);

        $i = 0;
        foreach ($set as $name => $rule) {
            if ($i === 0) {
                $this->assertEquals('notBlank', $name);
            }
            if ($i === 1) {
                $this->assertEquals('numeric', $name);
            }
            if ($i === 2) {
                $this->assertEquals('other', $name);
            }
            $this->assertInstanceOf('Cake\Validation\ValidationRule', $rule);
            $i++;
        }
        $this->assertEquals(3, $i);
    }

    /**
     * Tests countable interface
     *
     * @return void
     */
    public function testCount()
    {
        $set = (new ValidationSet)
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
        $set = new ValidationSet('title', [
            '_validatePresent' => true,
            'notBlank' => ['rule' => 'notBlank'],
            'numeric' => ['rule' => 'numeric'],
            'other' => ['rule' => ['other', 1]],
        ]);

        $set->remove('notBlank');
        $this->assertFalse(isset($set['notBlank']));

        $set->remove('numeric');
        $this->assertFalse(isset($set['numeric']));

        $set->remove('other');
        $this->assertFalse(isset($set['other']));
    }
}
