<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
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
     */
    public function testAddingMultipleFields(): void
    {
        $schema = new Schema();
        $schema->addFields([
            'email' => 'string',
            'body' => ['type' => 'string', 'length' => 1000],
        ]);
        $this->assertSame(['email', 'body'], $schema->fields());
        $this->assertSame('string', $schema->field('email')['type']);
        $this->assertSame('string', $schema->field('body')['type']);
    }

    /**
     * test adding fields.
     */
    public function testAddingFields(): void
    {
        $schema = new Schema();

        $res = $schema->addField('name', ['type' => 'string']);
        $this->assertSame($schema, $res, 'Should be chainable');

        $this->assertSame(['name'], $schema->fields());
        $res = $schema->field('name');
        $expected = ['type' => 'string', 'length' => null, 'precision' => null, 'default' => null];
        $this->assertEquals($expected, $res);

        $res = $schema->addField('email', 'string');
        $this->assertSame($schema, $res, 'Should be chainable');

        $this->assertSame(['name', 'email'], $schema->fields());
        $res = $schema->field('email');
        $expected = ['type' => 'string', 'length' => null, 'precision' => null, 'default' => null];
        $this->assertEquals($expected, $res);
    }

    /**
     * test adding field whitelist attrs
     */
    public function testAddingFieldsWhitelist(): void
    {
        $schema = new Schema();

        $schema->addField('name', ['derp' => 'derp', 'type' => 'string']);

        $expected = ['type' => 'string', 'length' => null, 'precision' => null, 'default' => null];
        $this->assertEquals($expected, $schema->field('name'));
    }

    /**
     * Test removing fields.
     */
    public function testRemovingFields(): void
    {
        $schema = new Schema();

        $schema->addField('name', ['type' => 'string']);
        $this->assertSame(['name'], $schema->fields());

        $res = $schema->removeField('name');
        $this->assertSame($schema, $res, 'Should be chainable');
        $this->assertSame([], $schema->fields());
        $this->assertNull($schema->field('name'));
    }

    /**
     * test fieldType
     */
    public function testFieldType(): void
    {
        $schema = new Schema();

        $schema->addField('name', 'string')
            ->addField('numbery', [
                'type' => 'decimal',
                'required' => true,
            ]);
        $this->assertSame('string', $schema->fieldType('name'));
        $this->assertSame('decimal', $schema->fieldType('numbery'));
        $this->assertNull($schema->fieldType('nope'));
    }

    /**
     * test __debugInfo
     */
    public function testDebugInfo(): void
    {
        $schema = new Schema();

        $schema->addField('name', 'string')
            ->addField('numbery', [
                'type' => 'decimal',
                'required' => true,
            ]);
        $result = $schema->__debugInfo();
        $expected = [
            '_fields' => [
                'name' => ['type' => 'string', 'length' => null, 'precision' => null, 'default' => null],
                'numbery' => ['type' => 'decimal', 'length' => null, 'precision' => null, 'default' => null],
            ],
        ];
        $this->assertEquals($expected, $result);
    }
}
