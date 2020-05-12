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
namespace Cake\Test\TestCase\View\Widget;

use Cake\TestSuite\TestCase;
use Cake\View\StringTemplate;
use Cake\View\View;
use Cake\View\Widget\WidgetLocator;
use TestApp\View\Widget\TestUsingViewWidget;

/**
 * WidgetLocator test case
 */
class WidgetLocatorTest extends TestCase
{
    /**
     * @var \Cake\View\StringTemplate
     */
    protected $templates;

    /**
     * @var \Cake\View\View
     */
    protected $view;

    /**
     * setup method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->templates = new StringTemplate();
        $this->view = new View();
    }

    /**
     * Test adding new widgets.
     *
     * @return void
     */
    public function testAddInConstructor()
    {
        $widgets = [
            'text' => ['Cake\View\Widget\BasicWidget'],
            'label' => ['Label'],
        ];
        $inputs = new WidgetLocator($this->templates, $this->view, $widgets);
        $result = $inputs->get('text');
        $this->assertInstanceOf('Cake\View\Widget\BasicWidget', $result);

        $result = $inputs->get('label');
        $this->assertInstanceOf('Cake\View\Widget\LabelWidget', $result);
    }

    /**
     * Test that view instance is properly passed to widget constructor.
     *
     * @return void
     */
    public function testGeneratingWidgetUsingViewInstance()
    {
        $inputs = new WidgetLocator(
            $this->templates,
            $this->view,
            ['test' => [TestUsingViewWidget::class, '_view']]
        );

        /** @var \TestApp\View\Widget\TestUsingViewWidget $widget */
        $widget = $inputs->get('test');
        $this->assertInstanceOf(View::class, $widget->getView());
    }

    /**
     * Test loading widgets files in the app.
     *
     * @return void
     */
    public function testAddWidgetsFromConfigInConstructor()
    {
        $widgets = [
            'text' => ['Cake\View\Widget\BasicWidget'],
            'test_widgets',
        ];
        $inputs = new WidgetLocator($this->templates, $this->view, $widgets);
        $this->assertInstanceOf('Cake\View\Widget\LabelWidget', $inputs->get('text'));
    }

    /**
     * Test loading templates files from a plugin
     *
     * @return void
     */
    public function testAddPluginWidgetsFromConfigInConstructor()
    {
        $this->loadPlugins(['TestPlugin']);
        $widgets = [
            'text' => ['Cake\View\Widget\BasicWidget'],
            'TestPlugin.test_widgets',
        ];
        $inputs = new WidgetLocator($this->templates, $this->view, $widgets);
        $this->assertInstanceOf('Cake\View\Widget\LabelWidget', $inputs->get('text'));
        $this->clearPlugins();
    }

    /**
     * Test adding new widgets.
     *
     * @return void
     */
    public function testAdd()
    {
        $inputs = new WidgetLocator($this->templates, $this->view);
        $inputs->add([
            'text' => ['Cake\View\Widget\BasicWidget'],
        ]);
        $result = $inputs->get('text');
        $this->assertInstanceOf('Cake\View\Widget\WidgetInterface', $result);

        $inputs = new WidgetLocator($this->templates, $this->view);
        $inputs->add([
            'hidden' => 'Cake\View\Widget\BasicWidget',
        ]);
        $result = $inputs->get('hidden');
        $this->assertInstanceOf('Cake\View\Widget\WidgetInterface', $result);
    }

    /**
     * Test adding an instance of an invalid type.
     *
     * @return void
     */
    public function testAddInvalidType()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Widget objects must implement `Cake\View\Widget\WidgetInterface`. Got `stdClass` instance instead.'
        );
        $inputs = new WidgetLocator($this->templates, $this->view);
        $inputs->add([
            'text' => new \stdClass(),
        ]);
    }

    /**
     * Test getting registered widgets.
     *
     * @return void
     */
    public function testGet()
    {
        $inputs = new WidgetLocator($this->templates, $this->view);
        $inputs->add([
            'text' => ['Cake\View\Widget\BasicWidget'],
        ]);
        $result = $inputs->get('text');
        $this->assertInstanceOf('Cake\View\Widget\BasicWidget', $result);
        $this->assertSame($result, $inputs->get('text'));
    }

    /**
     * Test getting fallback widgets.
     *
     * @return void
     */
    public function testGetFallback()
    {
        $inputs = new WidgetLocator($this->templates, $this->view);
        $inputs->add([
            '_default' => ['Cake\View\Widget\BasicWidget'],
        ]);
        $result = $inputs->get('text');
        $this->assertInstanceOf('Cake\View\Widget\BasicWidget', $result);

        $result2 = $inputs->get('hidden');
        $this->assertSame($result, $result2);
    }

    /**
     * Test getting errors
     *
     * @return void
     */
    public function testGetNoFallbackError()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown widget `foo`');
        $inputs = new WidgetLocator($this->templates, $this->view);
        $inputs->clear();
        $inputs->get('foo');
    }

    /**
     * Test getting resolve dependency
     *
     * @return void
     */
    public function testGetResolveDependency()
    {
        $inputs = new WidgetLocator($this->templates, $this->view);
        $inputs->clear();
        $inputs->add([
            'label' => ['Cake\View\Widget\LabelWidget'],
            'multicheckbox' => ['Cake\View\Widget\MultiCheckboxWidget', 'label'],
        ]);
        $result = $inputs->get('multicheckbox');
        $this->assertInstanceOf('Cake\View\Widget\MultiCheckboxWidget', $result);
    }

    /**
     * Test getting resolve dependency missing class
     *
     * @return void
     */
    public function testGetResolveDependencyMissingClass()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to locate widget class "TestApp\View\DerpWidget"');
        $inputs = new WidgetLocator($this->templates, $this->view);
        $inputs->add(['test' => ['TestApp\View\DerpWidget']]);
        $inputs->get('test');
    }

    /**
     * Test getting resolve dependency missing dependency
     *
     * @return void
     */
    public function testGetResolveDependencyMissingDependency()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown widget `label`');
        $inputs = new WidgetLocator($this->templates, $this->view);
        $inputs->clear();
        $inputs->add(['multicheckbox' => ['Cake\View\Widget\MultiCheckboxWidget', 'label']]);
        $inputs->get('multicheckbox');
    }
}
