<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
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
            ['templatePath', 'Admin/'],
            ['plugin', 'TestPlugin'],
            ['layout', 'admin'],
            ['theme', 'TestPlugin'],
            ['template', 'edit'],
            ['name', 'Articles'],
            ['className', 'Cake\View\JsonView'],
        ];
    }

    /**
     * data provider for string properties.
     *
     * @return array
     */
    public function boolPropertyProvider()
    {
        return [
            ['autoLayout', true],
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
        $get = 'get' . ucfirst($property);
        $set = 'set' . ucfirst($property);

        $builder = new ViewBuilder();
        $this->assertNull($builder->{$get}(), 'Default value should be null');
        $this->assertSame($builder, $builder->{$set}($value), 'Setter returns this');
        $this->assertSame($value, $builder->{$get}(), 'Getter gets value.');
    }

    /**
     * Test string property accessor/mutator methods.
     *
     * @deprecated
     * @dataProvider boolPropertyProvider
     * @return void
     */
    public function testBoolProperties($property, $value)
    {
        $set = 'enable' . ucfirst($property);
        $get = 'is' . ucfirst($property) . 'Enabled';

        $builder = new ViewBuilder();
        $this->assertNull($builder->{$get}(), 'Default value should be null');
        $this->assertSame($builder, $builder->{$set}($value), 'Setter returns this');
        $this->assertSame($value, $builder->{$get}(), 'Getter gets value.');
    }

    /**
     * Test array property accessor/mutator methods.
     *
     * @dataProvider arrayPropertyProvider
     * @return void
     */
    public function testArrayProperties($property, $value)
    {
        $get = 'get' . ucfirst($property);
        $set = 'set' . ucfirst($property);

        $builder = new ViewBuilder();
        $this->assertSame([], $builder->{$get}(), 'Default value should be empty list');
        $this->assertSame($builder, $builder->{$set}($value), 'Setter returns this');
        $this->assertSame($value, $builder->{$get}(), 'Getter gets value.');
    }

    /**
     * Test array property accessor/mutator methods.
     *
     * @dataProvider arrayPropertyProvider
     * @return void
     */
    public function testArrayPropertyMerge($property, $value)
    {
        $get = 'get' . ucfirst($property);
        $set = 'set' . ucfirst($property);

        $builder = new ViewBuilder();
        $builder->{$set}($value);

        $builder->{$set}(['Merged'], true);
        $this->assertSame(array_merge($value, ['Merged']), $builder->{$get}(), 'Should merge');

        $builder->{$set}($value, false);
        $this->assertSame($value, $builder->{$get}(), 'Should replace');
    }

    /**
     * Test string property accessor/mutator methods.
     *
     * @deprecated
     * @dataProvider stringPropertyProvider
     * @return void
     */
    public function testStringPropertiesDeprecated($property, $value)
    {
        $this->deprecated(function () use ($value, $property) {
            $builder = new ViewBuilder();
            $this->assertNull($builder->{$property}(), 'Default value should be null');
            $this->assertSame($builder, $builder->{$property}($value), 'Setter returns this');
            $this->assertSame($value, $builder->{$property}(), 'Getter gets value.');
        });
    }

    /**
     * Test string property accessor/mutator methods.
     *
     * @deprecated
     * @dataProvider boolPropertyProvider
     * @return void
     */
    public function testBoolPropertiesDeprecated($property, $value)
    {
        $this->deprecated(function () use ($value, $property) {
            $builder = new ViewBuilder();
            $this->assertNull($builder->{$property}(), 'Default value should be null');
            $this->assertSame($builder, $builder->{$property}($value), 'Setter returns this');
            $this->assertSame($value, $builder->{$property}(), 'Getter gets value.');
        });
    }

    /**
     * Test array property accessor/mutator methods.
     *
     * @deprecated
     * @dataProvider arrayPropertyProvider
     * @return void
     */
    public function testArrayPropertiesDeprecated($property, $value)
    {
        $this->deprecated(function () use ($value, $property) {
            $builder = new ViewBuilder();
            $this->assertSame([], $builder->{$property}(), 'Default value should be empty list');
            $this->assertSame($builder, $builder->{$property}($value), 'Setter returns this');
            $this->assertSame($value, $builder->{$property}(), 'Getter gets value.');
        });
    }

    /**
     * Test array property accessor/mutator methods.
     *
     * @deprecated
     * @dataProvider arrayPropertyProvider
     * @return void
     */
    public function testArrayPropertyMergeDeprecated($property, $value)
    {
        $this->deprecated(function () use ($value, $property) {
            $builder = new ViewBuilder();
            $builder->{$property}($value);

            $builder->{$property}(['Merged'], true);
            $this->assertSame(array_merge($value, ['Merged']), $builder->{$property}(), 'Should merge');

            $builder->{$property}($value, false);
            $this->assertSame($value, $builder->{$property}(), 'Should replace');
        });
    }

    /**
     * test building with all the options.
     *
     * @return void
     */
    public function testBuildComplete()
    {
        $request = new ServerRequest();
        $response = new Response();
        $events = $this->getMockBuilder('Cake\Event\EventManager')->getMock();

        $builder = new ViewBuilder();
        $builder->setName('Articles')
            ->setClassName('Ajax')
            ->setTemplate('edit')
            ->setLayout('default')
            ->setTemplatePath('Articles/')
            ->setHelpers(['Form', 'Html'])
            ->setLayoutPath('Admin/')
            ->setTheme('TestTheme')
            ->setPlugin('TestPlugin');
        $view = $builder->build(
            ['one' => 'value'],
            $request,
            $response,
            $events
        );
        $this->assertInstanceOf('Cake\View\AjaxView', $view);
        $this->assertEquals('edit', $view->getTemplate());
        $this->assertEquals('default', $view->getLayout());
        $this->assertEquals('Articles/', $view->getTemplatePath());
        $this->assertEquals('Admin/', $view->getLayoutPath());
        $this->assertEquals('TestPlugin', $view->plugin);
        $this->assertEquals('TestTheme', $view->theme);
        $this->assertSame($request, $view->request);
        $this->assertInstanceOf(Response::class, $view->response);
        $this->assertSame($events, $view->getEventManager());
        $this->assertSame(['one' => 'value'], $view->viewVars);
        $this->assertInstanceOf('Cake\View\Helper\HtmlHelper', $view->Html);
        $this->assertInstanceOf('Cake\View\Helper\FormHelper', $view->Form);
    }

    /**
     * Test that the default is AppView.
     *
     * @return void
     */
    public function testBuildAppViewMissing()
    {
        static::setAppNamespace('Nope');
        $builder = new ViewBuilder();
        $view = $builder->build();
        $this->assertInstanceOf('Cake\View\View', $view);
    }

    /**
     * Test that the default is AppView.
     *
     * @return void
     */
    public function testBuildAppViewPresent()
    {
        static::setAppNamespace();
        $builder = new ViewBuilder();
        $view = $builder->build();
        $this->assertInstanceOf('TestApp\View\AppView', $view);
    }

    /**
     * test missing view class
     *
     * @return void
     */
    public function testBuildMissingViewClass()
    {
        $this->expectException(\Cake\View\Exception\MissingViewException::class);
        $this->expectExceptionMessage('View class "Foo" is missing.');
        $builder = new ViewBuilder();
        $builder->setClassName('Foo');
        $builder->build();
    }

    /**
     * testJsonSerialize()
     *
     * @return void
     */
    public function testJsonSerialize()
    {
        $builder = new ViewBuilder();

        $builder
            ->setTemplate('default')
            ->setLayout('test')
            ->setHelpers(['Html'])
            ->setClassName('JsonView');

        $result = json_decode(json_encode($builder), true);

        $expected = [
            '_template' => 'default',
            '_layout' => 'test',
            '_helpers' => ['Html'],
            '_className' => 'JsonView',
        ];
        $this->assertEquals($expected, $result);

        $result = json_decode(json_encode(unserialize(serialize($builder))), true);
        $this->assertEquals($expected, $result);
    }

    /**
     * testCreateFromArray()
     *
     * @return void
     */
    public function testCreateFromArray()
    {
        $builder = new ViewBuilder();

        $builder
            ->setTemplate('default')
            ->setLayout('test')
            ->setHelpers(['Html'])
            ->setClassName('JsonView');

        $result = json_encode($builder);

        $builder = new ViewBuilder();
        $builder->createFromArray(json_decode($result, true));

        $this->assertEquals('default', $builder->getTemplate());
        $this->assertEquals('test', $builder->getLayout());
        $this->assertEquals(['Html'], $builder->getHelpers());
        $this->assertEquals('JsonView', $builder->getClassName());
    }
}
