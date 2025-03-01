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
use Cake\View\Widget\BasicWidget;
use Cake\View\Widget\LabelWidget;
use Cake\View\Widget\MultiCheckboxWidget;
use Cake\View\Widget\WidgetInterface;
use Cake\View\Widget\WidgetLocator;
use InvalidArgumentException;
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
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->templates = new StringTemplate();
        $this->view = new View();
    }

    /**
     * Test adding new widgets.
     */
    public function testAddInConstructor(): void
    {
        $widgets = [
            'text' => [BasicWidget::class],
            'label' => ['Label'],
        ];
        $inputs = new WidgetLocator($this->templates, $this->view, $widgets);
        $result = $inputs->get('text');
        $this->assertInstanceOf(BasicWidget::class, $result);

        $result = $inputs->get('label');
        $this->assertInstanceOf(LabelWidget::class, $result);
    }

    /**
     * Test that view instance is properly passed to widget constructor.
     */
    public function testGeneratingWidgetUsingViewInstance(): void
    {
        $inputs = new WidgetLocator(
            $this->templates,
            $this->view,
            ['test' => [TestUsingViewWidget::class, '_view']],
        );

        /** @var \TestApp\View\Widget\TestUsingViewWidget $widget */
        $widget = $inputs->get('test');
        $this->assertInstanceOf(View::class, $widget->getView());
    }

    /**
     * Test loading widgets files in the app.
     */
    public function testAddWidgetsFromConfigInConstructor(): void
    {
        $widgets = [
            'text' => [BasicWidget::class],
            'test_widgets',
        ];
        $inputs = new WidgetLocator($this->templates, $this->view, $widgets);
        $this->assertInstanceOf(LabelWidget::class, $inputs->get('text'));
    }

    /**
     * Test loading templates files from a plugin
     */
    public function testAddPluginWidgetsFromConfigInConstructor(): void
    {
        $this->loadPlugins(['TestPlugin']);
        $widgets = [
            'text' => [BasicWidget::class],
            'TestPlugin.test_widgets',
        ];
        $inputs = new WidgetLocator($this->templates, $this->view, $widgets);
        $this->assertInstanceOf(LabelWidget::class, $inputs->get('text'));
        $this->clearPlugins();
    }

    /**
     * Test adding new widgets.
     */
    public function testAdd(): void
    {
        $inputs = new WidgetLocator($this->templates, $this->view);
        $inputs->add([
            'text' => [BasicWidget::class],
        ]);
        $result = $inputs->get('text');
        $this->assertInstanceOf(WidgetInterface::class, $result);

        $inputs = new WidgetLocator($this->templates, $this->view);
        $inputs->add([
            'hidden' => BasicWidget::class,
        ]);
        $result = $inputs->get('hidden');
        $this->assertInstanceOf(WidgetInterface::class, $result);
    }

    /**
     * Test getting registered widgets.
     */
    public function testGet(): void
    {
        $inputs = new WidgetLocator($this->templates, $this->view);
        $inputs->add([
            'text' => [BasicWidget::class],
        ]);
        $result = $inputs->get('text');
        $this->assertInstanceOf(BasicWidget::class, $result);
        $this->assertSame($result, $inputs->get('text'));
    }

    /**
     * Test getting fallback widgets.
     */
    public function testGetFallback(): void
    {
        $inputs = new WidgetLocator($this->templates, $this->view);
        $inputs->add([
            '_default' => [BasicWidget::class],
        ]);
        $result = $inputs->get('text');
        $this->assertInstanceOf(BasicWidget::class, $result);

        $result2 = $inputs->get('hidden');
        $this->assertSame($result, $result2);
    }

    /**
     * Test getting errors
     */
    public function testGetNoFallbackError(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown widget `foo`');
        $inputs = new WidgetLocator($this->templates, $this->view);
        $inputs->clear();
        $inputs->get('foo');
    }

    /**
     * Test getting resolve dependency
     */
    public function testGetResolveDependency(): void
    {
        $inputs = new WidgetLocator($this->templates, $this->view);
        $inputs->clear();
        $inputs->add([
            'label' => [LabelWidget::class],
            'multicheckbox' => [MultiCheckboxWidget::class, 'label'],
        ]);
        $result = $inputs->get('multicheckbox');
        $this->assertInstanceOf(MultiCheckboxWidget::class, $result);
    }

    /**
     * Test getting resolve dependency missing class
     */
    public function testGetResolveDependencyMissingClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to locate widget class `TestApp\View\DerpWidget`.');
        $inputs = new WidgetLocator($this->templates, $this->view);
        $inputs->add(['test' => ['TestApp\View\DerpWidget']]);
        $inputs->get('test');
    }

    /**
     * Test getting resolve dependency missing dependency
     */
    public function testGetResolveDependencyMissingDependency(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown widget `label`');
        $inputs = new WidgetLocator($this->templates, $this->view);
        $inputs->clear();
        $inputs->add(['multicheckbox' => [MultiCheckboxWidget::class, 'label']]);
        $inputs->get('multicheckbox');
    }
}
