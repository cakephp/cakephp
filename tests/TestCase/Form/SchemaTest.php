<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Form;

use Cake\Form\Schema;
use Cake\TestSuite\TestCase;

/**
 * Form schema test case.
 */
class SchemaTest extends TestCase
{

    /**
     * Test adding multiple fields.
     *
     * @return void
     */
    public function testAddingMultipleFields()
    {
        $schema = new Schema();
        $schema->addFields([
            'email' => 'string',
            'body' => ['type' => 'string', 'length' => 1000]
        ]);
        $this->assertEquals(['email', 'body'], $schema->fields());
        $this->assertEquals('string', $schema->field('email')['type']);
        $this->assertEquals('string', $schema->field('body')['type']);
    }

    /**
     * test adding fields.
     *
     * @return void
     */
    public function testAddingFields()
    {
        $schema = new Schema();

        $res = $schema->addField('name', ['type' => 'string']);
        $this->assertSame($schema, $res, 'Should be chainable');

        $this->assertEquals(['name'], $schema->fields());
        $res = $schema->field('name');
        $expected = ['type' => 'string', 'length' => null, 'precision' => null];
        $this->assertEquals($expected, $res);

        $res = $schema->addField('email', 'string');
        $this->assertSame($schema, $res, 'Should be chainable');

        $this->assertEquals(['name', 'email'], $schema->fields());
        $res = $schema->field('email');
        $expected = ['type' => 'string', 'length' => null, 'precision' => null];
        $this->assertEquals($expected, $res);
    }

    /**
     * test adding field whitelist attrs
     *
     * @return void
     */
    public function testAddingFieldsWhitelist()
    {
        $schema = new Schema();

        $schema->addField('name', ['derp' => 'derp', 'type' => 'string']);
        $expected = ['type' => 'string', 'length' => null, 'precision' => null];
        $this->assertEquals($expected, $schema->field('name'));
    }

    /**
     * Test removing fields.
     *
     * @return void
     */
    public function testRemovingFields()
    {
        $schema = new Schema();

        $schema->addField('name', ['type' => 'string']);
        $this->assertEquals(['name'], $schema->fields());

        $res = $schema->removeField('name');
        $this->assertSame($schema, $res, 'Should be chainable');
        $this->assertEquals([], $schema->fields());
        $this->assertNull($schema->field('name'));
    }

    /**
     * test fieldType
     *
     * @return void
     */
    public function testFieldType()
    {
        $schema = new Schema();

        $schema->addField('name', 'string')
            ->addField('numbery', [
                'type' => 'decimal',
                'required' => true
            ]);
        $this->assertEquals('string', $schema->fieldType('name'));
        $this->assertEquals('decimal', $schema->fieldType('numbery'));
        $this->assertNull($schema->fieldType('nope'));
    }

    /**
     * test __debugInfo
     *
     * @return void
     */
    public function testDebugInfo()
    {
        $schema = new Schema();

        $schema->addField('name', 'string')
            ->addField('numbery', [
                'type' => 'decimal',
                'required' => true
            ]);
        $result = $schema->__debugInfo();
        $expected = [
            '_fields' => [
                'name' => ['type' => 'string', 'length' => null, 'precision' => null],
                'numbery' => ['type' => 'decimal', 'length' => null, 'precision' => null],
            ],
        ];
        $this->assertEquals($expected, $result);
    }
}
