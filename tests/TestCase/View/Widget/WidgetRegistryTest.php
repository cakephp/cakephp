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
namespace Cake\Test\TestCase\View\Widget;

use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use Cake\View\StringTemplate;
use Cake\View\View;
use Cake\View\Widget\WidgetRegistry;

/**
 * WidgetRegistry test case
 */
class WidgetRegistryTestCase extends TestCase
{

    /**
     * setup method
     *
     * @return void
     */
    public function setUp()
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
        $inputs = new WidgetRegistry($this->templates, $this->view, $widgets);
        $result = $inputs->get('text');
        $this->assertInstanceOf('Cake\View\Widget\BasicWidget', $result);

        $result = $inputs->get('label');
        $this->assertInstanceOf('Cake\View\Widget\LabelWidget', $result);
    }

    /**
     * Test getting view instance from registry.
     *
     * @return void
     */
    public function testGetViewInstance()
    {
        $inputs = new WidgetRegistry($this->templates, $this->view, []);

        $result = $inputs->get('_view');
        $this->assertInstanceOf('Cake\View\View', $result);
    }

    /**
     * Test loading widgets files in the app.
     *
     * @return void
     */
    public function testAddWidgetsFromConfigInConstuctor()
    {
        $widgets = [
            'text' => ['Cake\View\Widget\BasicWidget'],
            'test_widgets',
        ];
        $inputs = new WidgetRegistry($this->templates, $this->view, $widgets);
        $this->assertInstanceOf('Cake\View\Widget\LabelWidget', $inputs->get('text'));
    }

    /**
     * Test loading templates files from a plugin
     *
     * @return void
     */
    public function testAddPluginWidgetsFromConfigInConstuctor()
    {
        Plugin::load('TestPlugin');
        $widgets = [
            'text' => ['Cake\View\Widget\BasicWidget'],
            'TestPlugin.test_widgets',
        ];
        $inputs = new WidgetRegistry($this->templates, $this->view, $widgets);
        $this->assertInstanceOf('Cake\View\Widget\LabelWidget', $inputs->get('text'));
    }

    /**
     * Test adding new widgets.
     *
     * @return void
     */
    public function testAdd()
    {
        $inputs = new WidgetRegistry($this->templates, $this->view);
        $result = $inputs->add([
            'text' => ['Cake\View\Widget\BasicWidget'],
        ]);
        $this->assertNull($result);
        $result = $inputs->get('text');
        $this->assertInstanceOf('Cake\View\Widget\WidgetInterface', $result);

        $inputs = new WidgetRegistry($this->templates, $this->view);
        $result = $inputs->add([
            'hidden' => 'Cake\View\Widget\BasicWidget',
        ]);
        $this->assertNull($result);
        $result = $inputs->get('hidden');
        $this->assertInstanceOf('Cake\View\Widget\WidgetInterface', $result);
    }

    /**
     * Test adding an instance of an invalid type.
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Widget objects must implement Cake\View\Widget\WidgetInterface
     * @return void
     */
    public function testAddInvalidType()
    {
        $inputs = new WidgetRegistry($this->templates, $this->view);
        $inputs->add([
            'text' => new \StdClass()
        ]);
    }

    /**
     * Test getting registered widgets.
     *
     * @return void
     */
    public function testGet()
    {
        $inputs = new WidgetRegistry($this->templates, $this->view);
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
        $inputs = new WidgetRegistry($this->templates, $this->view);
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
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unknown widget "foo"
     * @return void
     */
    public function testGetNoFallbackError()
    {
        $inputs = new WidgetRegistry($this->templates, $this->view);
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
        $inputs = new WidgetRegistry($this->templates, $this->view);
        $inputs->clear();
        $inputs->add([
            'label' => ['Cake\View\Widget\LabelWidget'],
            'multicheckbox' => ['Cake\View\Widget\MultiCheckboxWidget', 'label']
        ]);
        $result = $inputs->get('multicheckbox');
        $this->assertInstanceOf('Cake\View\Widget\MultiCheckboxWidget', $result);
    }

    /**
     * Test getting resolve dependency missing class
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to locate widget class "TestApp\View\DerpWidget"
     * @return void
     */
    public function testGetResolveDependencyMissingClass()
    {
        $inputs = new WidgetRegistry($this->templates, $this->view);
        $inputs->add(['test' => ['TestApp\View\DerpWidget']]);
        $inputs->get('test');
    }

    /**
     * Test getting resolve dependency missing dependency
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unknown widget "label"
     * @return void
     */
    public function testGetResolveDependencyMissingDependency()
    {
        $inputs = new WidgetRegistry($this->templates, $this->view);
        $inputs->clear();
        $inputs->add(['multicheckbox' => ['Cake\View\Widget\MultiCheckboxWidget', 'label']]);
        $inputs->get('multicheckbox');
    }
}
