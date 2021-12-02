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
 * @since         3.3.6
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View\Helper;

use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\Helper\BreadcrumbsHelper;
use Cake\View\View;
use LogicException;

class BreadcrumbsHelperTest extends TestCase
{
    /**
     * Instance of the BreadcrumbsHelper
     *
     * @var BreadcrumbsHelper
     */
    protected $breadcrumbs;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();
        $view = new View();
        $this->breadcrumbs = new BreadcrumbsHelper($view);

        Router::reload();
        Router::createRouteBuilder('/')->fallbacks();
    }

    /**
     * Test adding crumbs to the trail using add()
     */
    public function testAdd(): void
    {
        $this->breadcrumbs
            ->add('Home', '/', ['class' => 'first'])
            ->add('Some text', ['controller' => 'Some', 'action' => 'text']);

        $result = $this->breadcrumbs->getCrumbs();
        $expected = [
            [
                'title' => 'Home',
                'url' => '/',
                'options' => [
                    'class' => 'first',
                ],
            ],
            [
                'title' => 'Some text',
                'url' => [
                    'controller' => 'Some',
                    'action' => 'text',
                ],
                'options' => [],
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test adding multiple crumbs at once to the trail using add()
     */
    public function testAddMultiple(): void
    {
        $this->breadcrumbs
            ->add([
                [
                    'title' => 'Home',
                    'url' => '/',
                    'options' => ['class' => 'first'],
                ],
                [
                    'title' => 'Some text',
                    'url' => ['controller' => 'Some', 'action' => 'text'],
                ],
                [
                    'title' => 'Final',
                ],
            ]);

        $result = $this->breadcrumbs->getCrumbs();
        $expected = [
            [
                'title' => 'Home',
                'url' => '/',
                'options' => [
                    'class' => 'first',
                ],
            ],
            [
                'title' => 'Some text',
                'url' => [
                    'controller' => 'Some',
                    'action' => 'text',
                ],
                'options' => [],
            ],
            [
                'title' => 'Final',
                'url' => null,
                'options' => [],
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test adding crumbs to the trail using prepend()
     */
    public function testPrepend(): void
    {
        $this->breadcrumbs
            ->add('Home', '/', ['class' => 'first'])
            ->prepend('Some text', ['controller' => 'Some', 'action' => 'text'])
            ->prepend('The root', '/root', ['data-name' => 'some-name']);

        $result = $this->breadcrumbs->getCrumbs();
        $expected = [
            [
                'title' => 'The root',
                'url' => '/root',
                'options' => ['data-name' => 'some-name'],
            ],
            [
                'title' => 'Some text',
                'url' => [
                    'controller' => 'Some',
                    'action' => 'text',
                ],
                'options' => [],
            ],
            [
                'title' => 'Home',
                'url' => '/',
                'options' => [
                    'class' => 'first',
                ],
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test adding crumbs to the trail using prepend()
     */
    public function testPrependMultiple(): void
    {
        $this->breadcrumbs
            ->add('Home', '/', ['class' => 'first'])
            ->prepend([
                ['title' => 'Some text', 'url' => ['controller' => 'Some', 'action' => 'text']],
                ['title' => 'The root', 'url' => '/root', 'options' => ['data-name' => 'some-name']],
            ]);

        $result = $this->breadcrumbs->getCrumbs();
        $expected = [
            [
                'title' => 'Some text',
                'url' => [
                    'controller' => 'Some',
                    'action' => 'text',
                ],
                'options' => [],
            ],
            [
                'title' => 'The root',
                'url' => '/root',
                'options' => ['data-name' => 'some-name'],
            ],
            [
                'title' => 'Home',
                'url' => '/',
                'options' => [
                    'class' => 'first',
                ],
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test ability to empty crumbs list.
     */
    public function testReset(): void
    {
        $this->breadcrumbs->add('Home', '/');
        $this->breadcrumbs->add('Products', '/products');

        $crumbs = $this->breadcrumbs->getCrumbs();
        $this->assertSame(count($crumbs), 2);

        $this->breadcrumbs->reset();
        $actual = $this->breadcrumbs->getCrumbs();
        $this->assertEquals($actual, []);
    }

    /**
     * Test adding crumbs to a specific index
     */
    public function testInsertAt(): void
    {
        $this->breadcrumbs
            ->add('Home', '/', ['class' => 'first'])
            ->prepend('Some text', ['controller' => 'Some', 'action' => 'text'])
            ->insertAt(1, 'Insert At', ['controller' => 'Insert', 'action' => 'at'])
            ->insertAt(1, 'Insert At Again', ['controller' => 'Insert', 'action' => 'at_again']);

        $result = $this->breadcrumbs->getCrumbs();
        $expected = [
            [
                'title' => 'Some text',
                'url' => [
                    'controller' => 'Some',
                    'action' => 'text',
                ],
                'options' => [],
            ],
            [
                'title' => 'Insert At Again',
                'url' => [
                    'controller' => 'Insert',
                    'action' => 'at_again',
                ],
                'options' => [],
            ],
            [
                'title' => 'Insert At',
                'url' => [
                    'controller' => 'Insert',
                    'action' => 'at',
                ],
                'options' => [],
            ],
            [
                'title' => 'Home',
                'url' => '/',
                'options' => [
                    'class' => 'first',
                ],
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test adding crumbs to a specific index
     */
    public function testInsertAtIndexOutOfBounds(): void
    {
        $this->expectException(LogicException::class);
        $this->breadcrumbs
            ->add('Home', '/', ['class' => 'first'])
            ->insertAt(2, 'Insert At Again', ['controller' => 'Insert', 'action' => 'at_again']);
    }

    /**
     * Test adding crumbs before a specific one
     */
    public function testInsertBefore(): void
    {
        $this->breadcrumbs
            ->add('Home', '/', ['class' => 'first'])
            ->prepend('Some text', ['controller' => 'Some', 'action' => 'text'])
            ->prepend('The root', '/root', ['data-name' => 'some-name'])
            ->insertBefore('The root', 'The super root');

        $result = $this->breadcrumbs->getCrumbs();
        $expected = [
            [
                'title' => 'The super root',
                'url' => null,
                'options' => [],
            ],
            [
                'title' => 'The root',
                'url' => '/root',
                'options' => ['data-name' => 'some-name'],
            ],
            [
                'title' => 'Some text',
                'url' => [
                    'controller' => 'Some',
                    'action' => 'text',
                ],
                'options' => [],
            ],
            [
                'title' => 'Home',
                'url' => '/',
                'options' => [
                    'class' => 'first',
                ],
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test adding crumbs after a specific one
     */
    public function testInsertAfter(): void
    {
        $this->breadcrumbs
            ->add('Home', '/', ['class' => 'first'])
            ->prepend('Some text', ['controller' => 'Some', 'action' => 'text'])
            ->prepend('The root', '/root', ['data-name' => 'some-name'])
            ->insertAfter('The root', 'The less super root');

        $result = $this->breadcrumbs->getCrumbs();
        $expected = [
            [
                'title' => 'The root',
                'url' => '/root',
                'options' => ['data-name' => 'some-name'],
            ],
            [
                'title' => 'The less super root',
                'url' => null,
                'options' => [],
            ],
            [
                'title' => 'Some text',
                'url' => [
                    'controller' => 'Some',
                    'action' => 'text',
                ],
                'options' => [],
            ],
            [
                'title' => 'Home',
                'url' => '/',
                'options' => [
                    'class' => 'first',
                ],
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test adding crumbs after a specific one
     */
    public function testInsertAfterLastItem(): void
    {
        $this->breadcrumbs
            ->add('Home', '/')
            ->insertAfter('Home', 'Below Home', '/below', ['class' => 'second']);

        $result = $this->breadcrumbs->getCrumbs();
        $expected = [
            [
                'title' => 'Home',
                'url' => '/',
                'options' => [],
            ],
            [
                'title' => 'Below Home',
                'url' => '/below',
                'options' => [
                    'class' => 'second',
                ],
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests the render method
     */
    public function testRender(): void
    {
        $this->assertSame('', $this->breadcrumbs->render());

        $this->breadcrumbs
            ->add('Home', '/', ['class' => 'first', 'innerAttrs' => ['data-foo' => 'bar']])
            ->add('Some text', ['controller' => 'TestsApps', 'action' => 'someMethod'])
            ->add('Final crumb', null, ['class' => 'final', 'innerAttrs' => ['class' => 'final-link']]);

        $result = $this->breadcrumbs->render(
            ['data-stuff' => 'foo and bar'],
            ['separator' => '<i class="fa fa-angle-right"></i>', 'class' => 'separator']
        );
        $expected = [
            ['ul' => ['data-stuff' => 'foo and bar']],
            ['li' => ['class' => 'first']],
            ['a' => ['href' => '/', 'data-foo' => 'bar']],
            'Home',
            '/a',
            '/li',
            ['li' => ['class' => 'separator']],
            ['span' => []],
            ['i' => ['class' => 'fa fa-angle-right']],
            '/i',
            '/span',
            '/li',
            ['li' => []],
            ['a' => ['href' => '/TestsApps/someMethod']],
            'Some text',
            '/a',
            '/li',
            ['li' => ['class' => 'separator']],
            ['span' => []],
            ['i' => ['class' => 'fa fa-angle-right']],
            '/i',
            '/span',
            '/li',
            ['li' => ['class' => 'final']],
            ['span' => ['class' => 'final-link']],
            'Final crumb',
            '/span',
            '/li',
            '/ul',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Tests the render method with custom templates
     */
    public function testRenderCustomTemplate(): void
    {
        $this->breadcrumbs = new BreadcrumbsHelper(new View(), [
            'templates' => [
                'wrapper' => '<ol itemtype="http://schema.org/BreadcrumbList"{{attrs}}>{{content}}</ol>',
                'item' => '<li itemprop="itemListElement" itemtype="http://schema.org/ListItem"{{attrs}}><a itemtype="http://schema.org/Thing" itemprop="item" href="{{url}}"{{innerAttrs}}><span itemprop="name">{{title}}</span></a></li>',
                'itemWithoutLink' => '<li itemprop="itemListElement" itemtype="http://schema.org/ListItem"{{attrs}}><span itemprop="name"{{innerAttrs}}>{{title}}</span></li>',
            ],
        ]);
        $this->breadcrumbs
            ->add('Home', '/', ['class' => 'first', 'innerAttrs' => ['data-foo' => 'bar']])
            ->add('Final crumb', null, ['class' => 'final', 'innerAttrs' => ['class' => 'final-link']]);

        $result = $this->breadcrumbs->render(
            ['data-stuff' => 'foo and bar'],
            ['separator' => ' > ', 'class' => 'separator']
        );
        $expected = [
            ['ol' => ['itemtype' => 'http://schema.org/BreadcrumbList', 'data-stuff' => 'foo and bar']],
            ['li' => ['itemprop' => 'itemListElement', 'itemtype' => 'http://schema.org/ListItem', 'class' => 'first']],
            ['a' => ['itemtype' => 'http://schema.org/Thing', 'itemprop' => 'item', 'href' => '/', 'data-foo' => 'bar']],
            ['span' => ['itemprop' => 'name']],
            'Home',
            '/span',
            '/a',
            '/li',
            ['li' => ['itemprop' => 'itemListElement', 'itemtype' => 'http://schema.org/ListItem', 'class' => 'final']],
            ['span' => ['itemprop' => 'name', 'class' => 'final-link']],
            'Final crumb',
            '/span',
            '/li',
            '/ol',
        ];
        $this->assertHtml($expected, $result, true);
    }

    /**
     * Tests the render method with template vars
     */
    public function testRenderCustomTemplateTemplateVars(): void
    {
        $this->breadcrumbs = new BreadcrumbsHelper(new View(), [
            'templates' => [
                'wrapper' => '{{thing}}<ol itemtype="http://schema.org/BreadcrumbList"{{attrs}}>{{content}}</ol>',
                'item' => '<li itemprop="itemListElement" itemtype="http://schema.org/ListItem"{{attrs}}><a itemtype="http://schema.org/Thing" itemprop="item" href="{{url}}"{{innerAttrs}}><span itemprop="name">{{title}}</span></a>{{foo}}</li>',
                'itemWithoutLink' => '<li itemprop="itemListElement" itemtype="http://schema.org/ListItem"{{attrs}}><span itemprop="name"{{innerAttrs}}>{{title}}</span>{{barbaz}}</li>',
            ],
        ]);
        $this->breadcrumbs
            ->add('Home', '/', ['class' => 'first', 'innerAttrs' => ['data-foo' => 'bar'], 'templateVars' => ['foo' => 'barbaz']])
            ->add('Final crumb', null, ['class' => 'final', 'innerAttrs' => ['class' => 'final-link'], 'templateVars' => ['barbaz' => 'foo']]);

        $result = $this->breadcrumbs->render(
            ['data-stuff' => 'foo and bar', 'templateVars' => ['thing' => 'somestuff']],
            ['separator' => ' > ', 'class' => 'separator']
        );
        $expected = [
            'somestuff',
            ['ol' => ['itemtype' => 'http://schema.org/BreadcrumbList', 'data-stuff' => 'foo and bar']],
            ['li' => ['itemprop' => 'itemListElement', 'itemtype' => 'http://schema.org/ListItem', 'class' => 'first']],
            ['a' => ['itemtype' => 'http://schema.org/Thing', 'itemprop' => 'item', 'href' => '/', 'data-foo' => 'bar']],
            ['span' => ['itemprop' => 'name']],
            'Home',
            '/span',
            '/a',
            'barbaz',
            '/li',
            ['li' => ['itemprop' => 'itemListElement', 'itemtype' => 'http://schema.org/ListItem', 'class' => 'final']],
            ['span' => ['itemprop' => 'name', 'class' => 'final-link']],
            'Final crumb',
            '/span',
            'foo',
            '/li',
            '/ol',
        ];
        $this->assertHtml($expected, $result, true);
    }
}
