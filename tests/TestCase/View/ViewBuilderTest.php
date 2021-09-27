<?php
declare(strict_types=1);

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

use Cake\Event\EventManager;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Cake\View\Exception\MissingViewException;
use Cake\View\ViewBuilder;

/**
 * View builder test case.
 */
class ViewBuilderTest extends TestCase
{
    public function testSetVar(): void
    {
        $builder = new ViewBuilder();

        $builder->setVar('testing', 'value');
        $this->assertSame('value', $builder->getVar('testing'));
    }

    public function testSetVars(): void
    {
        $builder = new ViewBuilder();

        $data = ['test' => 'val', 'foo' => 'bar'];
        $builder->setVars($data);
        $this->assertEquals($data, $builder->getVars());

        $update = ['test' => 'updated'];
        $builder->setVars($update);
        $this->assertEquals(
            ['foo' => 'bar', 'test' => 'updated'],
            $builder->getVars()
        );

        $update = ['overwrite' => 'yes'];
        $builder->setVars($update, false);
        $this->assertEquals(
            ['overwrite' => 'yes'],
            $builder->getVars()
        );
    }

    public function testHasVar(): void
    {
        $builder = new ViewBuilder();

        $this->assertFalse($builder->hasVar('foo'));

        $builder->setVar('foo', 'value');
        $this->assertTrue($builder->hasVar('foo'));

        $builder->setVar('bar', null);
        $this->assertTrue($builder->hasVar('bar'));
    }

    /**
     * data provider for string properties.
     *
     * @return array
     */
    public function stringPropertyProvider(): array
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
     * data provider for boolean properties.
     * Format: [key, expectedDefault, newValue]
     *
     * @return array
     */
    public function boolPropertyProvider(): array
    {
        return [
            ['autoLayout', true, false],
        ];
    }

    /**
     * data provider for array properties.
     *
     * @return array
     */
    public function arrayPropertyProvider(): array
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
     */
    public function testStringProperties(string $property, string $value): void
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
     * @dataProvider boolPropertyProvider
     */
    public function testBoolProperties(string $property, bool $default, bool $value): void
    {
        $set = 'enable' . ucfirst($property);
        $get = 'is' . ucfirst($property) . 'Enabled';

        $builder = new ViewBuilder();
        $this->assertSame($default, $builder->{$get}(), 'Default value not as expected');
        $this->assertSame($builder, $builder->{$set}($value), 'Setter returns this');
        $this->assertSame($value, $builder->{$get}(), 'Getter gets value.');
    }

    /**
     * Test array property accessor/mutator methods.
     *
     * @dataProvider arrayPropertyProvider
     */
    public function testArrayProperties(string $property, array $value): void
    {
        $get = 'get' . ucfirst($property);
        $set = 'set' . ucfirst($property);

        $builder = new ViewBuilder();
        $this->assertSame([], $builder->{$get}(), 'Default value should be empty list');
        $this->deprecated(function () use ($set, $value, $builder) {
            // only deprecated wrapper should remove in cake 5, assert should kept
            if ($set !== 'setHelpers') {
                trigger_error('Only setHelpers has deprecation warning in cake +4.x', E_USER_DEPRECATED);
            }
            $this->assertSame($builder, $builder->{$set}($value), 'Setter returns this');
        });
        $this->assertSame($value, $builder->{$get}(), 'Getter gets value.');
    }

    /**
     * Test array property accessor/mutator methods.
     *
     * @dataProvider arrayPropertyProvider
     */
    public function testArrayPropertyMerge(string $property, array $value): void
    {
        $get = 'get' . ucfirst($property);
        $set = 'set' . ucfirst($property);

        $this->deprecated(function () use ($get, $set, $value) {
            // only deprecated wrapper and setHelpers testing should remove in cake 5, other asserts should kept
            if ($set !== 'setHelpers') {
                trigger_error('Only setHelpers has deprecation warning in cake 4.x', E_USER_DEPRECATED);
            }
            $builder = new ViewBuilder();
            $builder->{$set}($value);

            $builder->{$set}(['Merged'], true);
            $this->assertSame(array_merge($value, ['Merged']), $builder->{$get}(), 'Should merge');

            $builder->{$set}($value, false);
            $this->assertSame($value, $builder->{$get}(), 'Should replace');
        });
    }

    /**
     * Tests that adding non-assoc and assoc merge properly.
     *
     * @return void
     */
    public function testAddHelpers(): void
    {
        $builder = new ViewBuilder();
        $builder->addHelper('Form');
        $builder->addHelpers(['Form' => ['config' => 'value']]);

        $helpers = $builder->getHelpers();
        $expected = [
            'Form',
            'Form' => [
                'config' => 'value',
            ],
        ];
        $this->assertSame($expected, $helpers);
    }

    /**
     * test building with all the options.
     */
    public function testBuildComplete(): void
    {
        $this->deprecated(function () {
            $request = new ServerRequest();
            $response = new Response();
            $events = new EventManager();

            $builder = new ViewBuilder();
            $builder->setName('Articles')
                ->setClassName('Ajax')
                ->setTemplate('edit')
                ->setLayout('default')
                ->setTemplatePath('Articles/')
                ->setHelpers(['Form', 'Html'], false)
                ->setLayoutPath('Admin/')
                ->setTheme('TestTheme')
                ->setPlugin('TestPlugin')
                ->setVars(['foo' => 'bar', 'x' => 'old']);
            $view = $builder->build(
                ['one' => 'value', 'x' => 'new'],
                $request,
                $response,
                $events
            );
            $this->assertInstanceOf('Cake\View\AjaxView', $view);
            $this->assertSame('edit', $view->getTemplate());
            $this->assertSame('default', $view->getLayout());
            $this->assertSame('Articles/', $view->getTemplatePath());
            $this->assertSame('Admin/', $view->getLayoutPath());
            $this->assertSame('TestPlugin', $view->getPlugin());
            $this->assertSame('TestTheme', $view->getTheme());
            $this->assertSame($request, $view->getRequest());
            $this->assertInstanceOf(Response::class, $view->getResponse());
            $this->assertSame($events, $view->getEventManager());
            $this->assertSame(['one', 'x', 'foo'], $view->getVars());
            $this->assertSame('value', $view->get('one'));
            $this->assertSame('bar', $view->get('foo'));
            $this->assertInstanceOf('Cake\View\Helper\HtmlHelper', $view->Html);
            $this->assertInstanceOf('Cake\View\Helper\FormHelper', $view->Form);
        });
    }

    /**
     * Test that the default is AppView.
     */
    public function testBuildAppViewMissing(): void
    {
        static::setAppNamespace('Nope');
        $builder = new ViewBuilder();
        $view = $builder->build();
        $this->assertInstanceOf('Cake\View\View', $view);
    }

    /**
     * Test that the default is AppView.
     */
    public function testBuildAppViewPresent(): void
    {
        static::setAppNamespace();
        $builder = new ViewBuilder();
        $view = $builder->build();
        $this->assertInstanceOf('TestApp\View\AppView', $view);
    }

    /**
     * test missing view class
     */
    public function testBuildMissingViewClass(): void
    {
        $this->expectException(MissingViewException::class);
        $this->expectExceptionMessage('View class "Foo" is missing.');
        $builder = new ViewBuilder();
        $builder->setClassName('Foo');
        $builder->build();
    }

    /**
     * testJsonSerialize()
     */
    public function testJsonSerialize(): void
    {
        $builder = new ViewBuilder();

        $builder
            ->setTemplate('default')
            ->setLayout('test')
            ->setHelpers(['Html'], false)
            ->setClassName('JsonView');

        $result = json_decode(json_encode($builder), true);

        $expected = [
            '_template' => 'default',
            '_layout' => 'test',
            '_helpers' => ['Html'],
            '_className' => 'JsonView',
            '_autoLayout' => true,
        ];
        $this->assertEquals($expected, $result);

        $result = json_decode(json_encode(unserialize(serialize($builder))), true);
        $this->assertEquals($expected, $result);
    }

    /**
     * testCreateFromArray()
     */
    public function testCreateFromArray(): void
    {
        $builder = new ViewBuilder();

        $builder
            ->setTemplate('default')
            ->setLayout('test')
            ->setHelpers(['Html'], false)
            ->setClassName('JsonView');

        $result = json_encode($builder);

        $builder = new ViewBuilder();
        $builder->createFromArray(json_decode($result, true));

        $this->assertSame('default', $builder->getTemplate());
        $this->assertSame('test', $builder->getLayout());
        $this->assertEquals(['Html'], $builder->getHelpers());
        $this->assertSame('JsonView', $builder->getClassName());
    }

    /**
     * test setOptions() with 1 string param, merge true
     */
    public function testSetOptionsOne(): void
    {
        $builder = new ViewBuilder();
        $this->assertSame($builder, $builder->setOptions(['newOption']));
        $this->assertContains('newOption', $builder->getOptions());
    }

    /**
     * test setOptions() with 2 strings in array, merge true.
     */
    public function testSetOptionsMultiple(): void
    {
        $builder = new ViewBuilder();
        $builder->setOptions(['oldOption'], false);

        $option = ['newOption', 'anotherOption'];
        $builder->setOptions($option);
        $expects = ['oldOption', 'newOption', 'anotherOption'];

        $result = $builder->getOptions();
        $this->assertContainsOnly('string', $result);
        $this->assertEquals($expects, $result);
    }

    /**
     * test empty params reads _viewOptions.
     */
    public function testReadingViewOptions(): void
    {
        $builder = new ViewBuilder();
        $builder->setOptions(['one', 'two', 'three'], false);

        $this->assertEquals(['one', 'two', 'three'], $builder->getOptions());
    }

    /**
     * test setting $merge `false` overrides correct options.
     */
    public function testMergeFalseViewOptions(): void
    {
        $builder = new ViewBuilder();
        $builder->setOptions(['one', 'two', 'three'], false);

        $expected = ['four', 'five', 'six'];
        $builder->setOptions($expected, false);
        $this->assertEquals($expected, $builder->getOptions());
    }

    /**
     * test _viewOptions is undefined and $opts is null, an empty array is returned.
     */
    public function testUndefinedValidViewOptions(): void
    {
        $builder = new ViewBuilder();
        $builder->setOptions([], false);
        $result = $builder->getOptions();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testOptionSetGet(): void
    {
        $builder = new ViewBuilder();
        $result = $builder->setOption('foo', 'bar');
        $this->assertSame($builder, $result);
        $this->assertSame('bar', $builder->getOption('foo'));

        $builder->setOption('foo', 'overwrite');
        $this->assertSame('overwrite', $builder->getOption('foo'));

        $this->assertNull($builder->getOption('nonexistent'));
    }

    public function testDisableAutoLayout(): void
    {
        $builder = new ViewBuilder();
        $this->assertTrue($builder->isAutoLayoutEnabled());

        $builder->disableAutoLayout();
        $this->assertFalse($builder->isAutoLayoutEnabled());
    }

    public function testAddHelperChained(): void
    {
        $builder = new ViewBuilder();
        $builder->addHelper('Form')
            ->addHelper('Time')
            ->addHelper('Text');

        $helpers = $builder->getHelpers();
        $expected = [
            'Form',
            'Time',
            'Text',
        ];
        $this->assertSame($expected, $helpers);
    }

    public function testAddHelperOptions(): void
    {
        $builder = new ViewBuilder();
        $builder->addHelper('Form')
            ->addHelper('Text', ['foo' => 'bar']);

        $helpers = $builder->getHelpers();
        $this->assertSame(['foo' => 'bar'], $helpers['Text']);
    }
}
