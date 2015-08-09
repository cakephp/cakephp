<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View;

use Cake\TestSuite\TestCase;
use Cake\View\ViewBuilder;

/**
 * View builder test case.
 */
class ViewBuilderTest extends TestCase
{
    /**
     * data provider for string properties.
     *
     * @return array
     */
    public function stringPropertyProvider()
    {
        return [
            ['layoutPath', 'Admin/'],
            ['viewPath', 'Admin/'],
            ['plugin', 'TestPlugin'],
            ['layout', 'admin'],
            ['theme', 'TestPlugin'],
            ['template', 'edit'],
            ['name', 'Articles'],
            ['autoLayout', true],
            ['className', 'Cake\View\JsonView'],
        ];
    }

    /**
     * data provider for array properties.
     *
     * @return array
     */
    public function arrayPropertyProvider()
    {
        return [
            ['helpers', ['Html', 'Form']],
            ['options', ['key' => 'value']],
        ];
    }

    /**
     * Test string property accessor/mutator methods.
     *
     * @dataProvider stringPropertyProvider
     * @return void
     */
    public function testStringProperties($property, $value)
    {
        $builder = new ViewBuilder();
        $this->assertNull($builder->{$property}(), 'Default value should be null');
        $this->assertSame($builder, $builder->{$property}($value), 'Setter returns this');
        $this->assertSame($value, $builder->{$property}(), 'Getter gets value.');
    }

    /**
     * Test array property accessor/mutator methods.
     *
     * @dataProvider arrayPropertyProvider
     * @return void
     */
    public function testArrayProperties($property, $value)
    {
        $builder = new ViewBuilder();
        $this->assertSame([], $builder->{$property}(), 'Default value should be empty list');
        $this->assertSame($builder, $builder->{$property}($value), 'Setter returns this');
        $this->assertSame($value, $builder->{$property}(), 'Getter gets value.');
    }

    /**
     * Test array property accessor/mutator methods.
     *
     * @dataProvider arrayPropertyProvider
     * @return void
     */
    public function testArrayPropertyMerge($property, $value)
    {
        $builder = new ViewBuilder();
        $builder->{$property}($value);

        $builder->{$property}(['Merged'], true);
        $this->assertSame(array_merge($value, ['Merged']), $builder->{$property}(), 'Should merge');

        $builder->{$property}($value, false);
        $this->assertSame($value, $builder->{$property}(), 'Should replace');
    }

    /**
     * test building with all the options.
     *
     * @return void
     */
    public function testBuildComplete()
    {
        $this->markTestIncomplete('not done');
    }

    /**
     * test missing view class
     *
     * @expectedException \Cake\View\Exception\MissingViewException
     * @expectedExceptionMessage View class "Foo" is missing.
     * @return void
     */
    public function testBuildMissingViewClass()
    {
        $this->markTestIncomplete('not done');
    }
}
