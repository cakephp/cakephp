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
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View\Helper;

use ArrayObject;
use Cake\Collection\Collection;
use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\Form\Form;
use Cake\Http\ServerRequest;
use Cake\I18n\FrozenDate;
use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;
use Cake\Validation\Validator;
use Cake\View\Form\EntityContext;
use Cake\View\Helper\FormHelper;
use Cake\View\View;
use Cake\View\Widget\WidgetLocator;
use InvalidArgumentException;
use ReflectionProperty;
use RuntimeException;
use TestApp\Model\Entity\Article;
use TestApp\Model\Table\ContactsTable;
use TestApp\Model\Table\ValidateUsersTable;

/**
 * FormHelperTest class
 *
 * @property \Cake\View\Helper\FormHelper $Form
 * @property \Cake\View\View $View
 */
class FormHelperTest extends TestCase
{
    /**
     * Fixtures to be used
     *
     * @var array<string>
     */
    protected $fixtures = ['core.Articles', 'core.Comments'];

    /**
     * @var array
     */
    protected $article = [];

    /**
     * @var string
     */
    protected $url;

    /**
     * @var \Cake\View\Helper\FormHelper
     */
    protected $Form;

    /**
     * @var \Cake\View\View
     */
    protected $View;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();

        Configure::write('Config.language', 'eng');
        Configure::write('App.base', '');
        static::setAppNamespace('Cake\Test\TestCase\View\Helper');

        $request = new ServerRequest([
            'webroot' => '',
            'base' => '',
            'url' => '/articles/add',
            'params' => [
                'controller' => 'Articles',
                'action' => 'add',
                'plugin' => null,
            ],
        ]);
        $this->View = new View($request);
        Router::reload();
        Router::setRequest($request);

        $this->url = '/articles/add';
        $this->Form = new FormHelper($this->View);

        $this->article = [
            'schema' => [
                'id' => ['type' => 'integer'],
                'author_id' => ['type' => 'integer', 'null' => true],
                'title' => ['type' => 'string', 'null' => true],
                'body' => 'text',
                'published' => ['type' => 'string', 'length' => 1, 'default' => 'N'],
                '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
            ],
            'required' => [
                'author_id' => true,
                'title' => true,
            ],
        ];

        Security::setSalt('foo!');
        $builder = Router::createRouteBuilder('/');
        $builder->connect('/{controller}', ['action' => 'index']);
        $builder->connect('/{controller}/{action}/*');
    }

    /**
     * tearDown method
     */
    public function tearDown(): void
    {
        parent::tearDown();
        unset($this->Form, $this->Controller, $this->View);
    }

    /**
     * Test construct() with the templates option.
     */
    public function testConstructTemplatesFile(): void
    {
        $helper = new FormHelper($this->View, [
            'templates' => 'htmlhelper_tags',
        ]);
        $result = $helper->control('name');
        $this->assertStringContainsString('<input', $result);
    }

    /**
     * Test that when specifying custom widgets the config array for that widget
     * is overwritten instead of merged.
     */
    public function testConstructWithWidgets(): void
    {
        $config = [
            'widgets' => [
                'datetime' => ['Cake\View\Widget\LabelWidget', 'select'],
            ],
        ];
        $helper = new FormHelper($this->View, $config);
        $locator = $helper->getWidgetLocator();
        $this->assertInstanceOf('Cake\View\Widget\LabelWidget', $locator->get('datetime'));
    }

    /**
     * Test that when specifying custom widgets config file and it should be
     * added to widgets array. WidgetLocator will load widgets in constructor.
     */
    public function testConstructWithWidgetsConfig(): void
    {
        $helper = new FormHelper($this->View, ['widgets' => ['test_widgets']]);
        $locator = $helper->getWidgetLocator();
        $this->assertInstanceOf('Cake\View\Widget\LabelWidget', $locator->get('text'));
    }

    /**
     * Test setting the widget locator
     */
    public function testSetAndGetWidgetLocator(): void
    {
        $helper = new FormHelper($this->View);
        $locator = new WidgetLocator($helper->templater(), $this->View);
        $helper->setWidgetLocator($locator);

        $this->assertSame($locator, $helper->getWidgetLocator());
    }

    /**
     * Test overridding grouped input types which controls generation of "for"
     * attribute of labels.
     */
    public function testConstructWithGroupedInputTypes(): void
    {
        $helper = new FormHelper($this->View, [
            'groupedInputTypes' => ['radio'],
        ]);

        $result = $helper->control('when', ['type' => 'datetime-local']);
        $this->assertStringContainsString('<label for="when">When</label>', $result);
    }

    /**
     * Test registering a new widget class and rendering it.
     */
    public function testAddWidgetAndRenderWidget(): void
    {
        $data = [
            'val' => 1,
        ];
        $mock = $this->getMockBuilder('Cake\View\Widget\WidgetInterface')->getMock();
        $this->Form->addWidget('test', $mock);
        $mock->expects($this->once())
            ->method('render')
            ->with($data)
            ->will($this->returnValue('HTML'));
        $result = $this->Form->widget('test', $data);
        $this->assertSame('HTML', $result);
    }

    /**
     * Test that secureFields() of widget is called after calling render(),
     * not before.
     */
    public function testOrderForRenderingWidgetAndFetchingSecureFields(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', [
            'unlockedFields' => [],
        ]));

        $data = [
            'val' => 1,
            'name' => 'test',
        ];
        $mock = $this->getMockBuilder('Cake\View\Widget\WidgetInterface')->getMock();
        $this->Form->addWidget('test', $mock);

        $mock->expects($this->once())
            ->method('render')
            ->with($data)
            ->will($this->returnValue('HTML'));

        $mock->expects($this->once())
            ->method('secureFields')
            ->with($data)
            ->will($this->returnValue(['test']));

        $this->Form->create();
        $result = $this->Form->widget('test', $data + ['secure' => true]);
        $this->assertSame('HTML', $result);
    }

    /**
     * Test that empty string is not added to secure fields list when
     * rendering input widget without name.
     */
    public function testRenderingWidgetWithEmptyName(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));
        $this->Form->create();

        $result = $this->Form->widget('select', ['secure' => true, 'name' => '']);
        $this->assertSame('<select name=""></select>', $result);
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEquals([], $result);

        $result = $this->Form->widget('select', ['secure' => true, 'name' => '0']);
        $this->assertSame('<select name="0"></select>', $result);
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEquals(['0'], $result);
    }

    /**
     * Test registering an invalid widget class.
     */
    public function testAddWidgetInvalid(): void
    {
        $this->expectException(\RuntimeException::class);
        $mock = new \stdClass();
        $this->Form->addWidget('test', $mock);
        $this->Form->widget('test');
    }

    /**
     * Test adding a new context class.
     */
    public function testAddContextProvider(): void
    {
        $context = 'My data';
        $stub = $this->getMockBuilder('Cake\View\Form\ContextInterface')->getMock();
        $this->Form->addContextProvider('test', function ($request, $data) use ($context, $stub) {
            $this->assertInstanceOf('Cake\Http\ServerRequest', $request);
            $this->assertSame($context, $data['entity']);

            return $stub;
        });
        $this->Form->create($context);
        $result = $this->Form->context();
        $this->assertSame($stub, $result);
    }

    /**
     * Test replacing a context class.
     */
    public function testAddContextProviderReplace(): void
    {
        $entity = new Article();
        $stub = $this->getMockBuilder('Cake\View\Form\ContextInterface')->getMock();
        $this->Form->addContextProvider('orm', function ($request, $data) use ($stub) {
            return $stub;
        });
        $this->Form->create($entity);
        $result = $this->Form->context();
        $this->assertSame($stub, $result);
    }

    /**
     * Test overriding a context class.
     */
    public function testAddContextProviderAdd(): void
    {
        $entity = new Article();
        $stub = $this->getMockBuilder('Cake\View\Form\ContextInterface')->getMock();
        $this->Form->addContextProvider('newshiny', function ($request, $data) use ($stub) {
            if ($data['entity'] instanceof Entity) {
                return $stub;
            }
        });
        $this->Form->create($entity);
        $result = $this->Form->context();
        $this->assertSame($stub, $result);
    }

    /**
     * Provides context options for create().
     *
     * @return array
     */
    public function contextSelectionProvider(): array
    {
        $entity = new Article();
        $collection = new Collection([$entity]);
        $emptyCollection = new Collection([]);
        $arrayObject = new ArrayObject([]);
        $data = [
            'schema' => [
                'title' => ['type' => 'string'],
            ],
        ];
        $form = new Form();
        $custom = $this->getMockBuilder('Cake\View\Form\ContextInterface')->getMock();

        return [
            'entity' => [$entity, 'Cake\View\Form\EntityContext'],
            'collection' => [$collection, 'Cake\View\Form\EntityContext'],
            'empty_collection' => [$emptyCollection, 'Cake\View\Form\NullContext'],
            'array' => [$data, 'Cake\View\Form\ArrayContext'],
            'form' => [$form, 'Cake\View\Form\FormContext'],
            'none' => [null, 'Cake\View\Form\NullContext'],
            'custom' => [$custom, get_class($custom)],
        ];
    }

    /**
     * Test default context selection in create()
     *
     * @dataProvider contextSelectionProvider
     * @param mixed $data
     */
    public function testCreateContextSelectionBuiltIn($data, string $class): void
    {
        $this->Form->create($data);
        $this->assertInstanceOf($class, $this->Form->context());
    }

    /**
     * Data provider for type option.
     *
     * @return array
     */
    public static function requestTypeProvider(): array
    {
        return [
            // type, method, override
            ['post', 'post', 'POST'],
            ['put', 'post', 'PUT'],
            ['patch', 'post', 'PATCH'],
            ['delete', 'post', 'DELETE'],
        ];
    }

    /**
     * Test creating file forms.
     */
    public function testCreateFile(): void
    {
        $encoding = strtolower(Configure::read('App.encoding'));
        $result = $this->Form->create(null, ['type' => 'file']);
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/articles/add',
                'accept-charset' => $encoding, 'enctype' => 'multipart/form-data',
            ],
            'div' => ['style' => 'display:none;'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test creating GET forms.
     */
    public function testCreateGet(): void
    {
        $encoding = strtolower(Configure::read('App.encoding'));
        $result = $this->Form->create(null, ['type' => 'get']);
        $expected = ['form' => [
            'method' => 'get', 'action' => '/articles/add',
            'accept-charset' => $encoding,
        ]];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test explicit method/enctype options.
     *
     * Explicit method overwrites inferred method from 'type'
     */
    public function testCreateExplicitMethodEnctype(): void
    {
        $encoding = strtolower(Configure::read('App.encoding'));
        $result = $this->Form->create(null, [
            'type' => 'get',
            'method' => 'put',
            'enctype' => 'multipart/form-data',
        ]);
        $expected = ['form' => [
            'method' => 'put',
            'action' => '/articles/add',
            'enctype' => 'multipart/form-data',
            'accept-charset' => $encoding,
        ]];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test create() with the templates option.
     */
    public function testCreateTemplatesArray(): void
    {
        $result = $this->Form->create($this->article, [
            'templates' => [
                'formStart' => '<form class="form-horizontal"{{attrs}}>',
            ],
        ]);
        $expected = [
            'form' => [
                'class' => 'form-horizontal',
                'method' => 'post',
                'action' => '/articles/add',
                'accept-charset' => 'utf-8',
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test create() with the templates option.
     */
    public function testCreateTemplatesFile(): void
    {
        $result = $this->Form->create($this->article, [
            'templates' => 'htmlhelper_tags',
        ]);
        $expected = [
            'start form',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test that create() and end() restore templates.
     */
    public function testCreateEndRestoreTemplates(): void
    {
        $this->Form->create($this->article, [
            'templates' => ['input' => 'custom input element'],
        ]);
        $this->Form->end();
        $this->assertNotEquals('custom input element', $this->Form->templater()->get('input'));
    }

    /**
     * Test using template vars in various templates used by control() method.
     */
    public function testControlTemplateVars(): void
    {
        $result = $this->Form->control('text', [
            'templates' => [
                'input' => '<input custom="{{forinput}}" type="{{type}}" name="{{name}}"{{attrs}}/>',
                'label' => '<label{{attrs}}>{{text}} {{forlabel}}</label>',
                'formGroup' => '{{label}}{{forgroup}}{{input}}',
                'inputContainer' => '<div class="input {{type}}{{required}}">{{content}}{{forcontainer}}</div>',
            ],
            'templateVars' => [
                'forinput' => 'in-input',
                'forlabel' => 'in-label',
                'forgroup' => 'in-group',
                'forcontainer' => 'in-container',
            ],
        ]);
        $expected = [
            'div' => ['class'],
            'label' => ['for'],
            'Text in-label',
            '/label',
            'in-group',
            'input' => ['name', 'type' => 'text', 'id', 'custom' => 'in-input'],
            'in-container',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test ensuring template variables work in template files loaded
     * during control().
     */
    public function testControlTemplatesFromFile(): void
    {
        $result = $this->Form->control('title', [
            'templates' => 'test_templates',
            'templateVars' => [
                'forcontainer' => 'container-data',
            ],
        ]);
        $expected = [
            'div' => ['class'],
            'label' => ['for'],
            'Title',
            '/label',
            'input' => ['name', 'type' => 'text', 'id'],
            'container-data',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test using template vars in inputSubmit and submitContainer template.
     */
    public function testSubmitTemplateVars(): void
    {
        $this->Form->setTemplates([
            'inputSubmit' => '<input custom="{{forinput}}" type="{{type}}"{{attrs}}/>',
            'submitContainer' => '<div class="submit">{{content}}{{forcontainer}}</div>',
        ]);
        $result = $this->Form->submit('Submit', [
            'templateVars' => [
                'forinput' => 'in-input',
                'forcontainer' => 'in-container',
            ],
        ]);
        $expected = [
            'div' => ['class'],
            'input' => ['custom' => 'in-input', 'type' => 'submit', 'value' => 'Submit'],
            'in-container',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test the create() method
     *
     * @dataProvider requestTypeProvider
     */
    public function testCreateTypeOptions(string $type, string $method, string $override): void
    {
        $encoding = strtolower(Configure::read('App.encoding'));
        $result = $this->Form->create(null, ['type' => $type]);
        $expected = [
            'form' => [
                'method' => $method, 'action' => '/articles/add',
                'accept-charset' => $encoding,
            ],
        ];

        $extra = [
            'div' => ['style' => 'display:none;'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => $override],
            '/div',
        ];

        if ($type !== 'post') {
            $expected = array_merge($expected, $extra);
        }

        $this->assertHtml($expected, $result);
    }

    /**
     * Test using template vars in Create (formStart template)
     */
    public function testCreateTemplateVars(): void
    {
        $result = $this->Form->create($this->article, [
            'templates' => [
                'formStart' => '<h4 class="mb">{{header}}</h4><form{{attrs}}>',
            ],
            'templateVars' => ['header' => 'headertext'],
        ]);
        $expected = [
            'h4' => ['class'],
            'headertext',
            '/h4',
            'form' => [
                'method' => 'post',
                'action' => '/articles/add',
                'accept-charset' => 'utf-8',
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test opening a form for an update operation.
     */
    public function testCreateUpdateForm(): void
    {
        $encoding = strtolower(Configure::read('App.encoding'));

        $this->View->setRequest($this->View->getRequest()
            ->withRequestTarget('/articles/edit/1')
            ->withParam('action', 'edit'));

        $this->article['defaults']['id'] = 1;

        $result = $this->Form->create($this->article);
        $expected = [
            'form' => [
                'method' => 'post',
                'action' => '/articles/edit/1',
                'accept-charset' => $encoding,
            ],
            'div' => ['style' => 'display:none;'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'PUT'],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test create() with automatic url generation
     */
    public function testCreateAutoUrl(): void
    {
        $encoding = strtolower(Configure::read('App.encoding'));

        $this->View->setRequest($this->View->getRequest()
            ->withRequestTarget('/articles/delete/10')
            ->withParam('action', 'delete'));
        $result = $this->Form->create($this->article);
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/articles/delete/10',
                'accept-charset' => $encoding,
            ],
        ];
        $this->assertHtml($expected, $result);

        $this->article['defaults'] = ['id' => 1];
        $this->View->setRequest($this->View->getRequest()
            ->withRequestTarget('/Articles/edit/1')
            ->withParam('action', 'delete'));
        $result = $this->Form->create($this->article, ['url' => ['action' => 'edit', 1]]);
        $expected = [
            'form' => [
                'method' => 'post',
                'action' => '/Articles/edit/1',
                'accept-charset' => $encoding,
            ],
            'div' => ['style' => 'display:none;'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'PUT'],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $this->View->setRequest($this->View->getRequest()
            ->withParam('action', 'add'));
        $result = $this->Form->create($this->article, ['url' => ['action' => 'publish', 1]]);
        $expected = [
            'form' => [
                'method' => 'post',
                'action' => '/Articles/publish/1',
                'accept-charset' => $encoding,
            ],
            'div' => ['style' => 'display:none;'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'PUT'],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->create($this->article, ['url' => '/Articles/publish']);
        $expected = [
            'form' => ['method' => 'post', 'action' => '/Articles/publish', 'accept-charset' => $encoding],
            'div' => ['style' => 'display:none;'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'PUT'],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $this->View->setRequest($this->View->getRequest()
            ->withParam('controller', 'Pages'));
        $result = $this->Form->create($this->article, ['url' => ['action' => 'signup', 1]]);
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/Pages/signup/1',
                'accept-charset' => $encoding,
            ],
            'div' => ['style' => 'display:none;'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'PUT'],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test create() with no URL (no "action" attribute for <form> tag)
     */
    public function testCreateNoUrl(): void
    {
        $result = $this->Form->create(null, ['url' => false]);
        $expected = [
            'form' => [
                'method' => 'post',
                'accept-charset' => strtolower(Configure::read('App.encoding')),
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test create() with a custom route
     */
    public function testCreateCustomRoute(): void
    {
        $builder = Router::createRouteBuilder('/');
        $builder->connect('/login', ['controller' => 'Users', 'action' => 'login']);
        $encoding = strtolower(Configure::read('App.encoding'));

        $this->View->setRequest($this->View->getRequest()
            ->withParam('controller', 'Users'));

        $result = $this->Form->create(null, ['url' => ['action' => 'login']]);
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/login',
                'accept-charset' => $encoding,
            ],
        ];
        $this->assertHtml($expected, $result);

        $builder->connect(
            '/new-article',
            ['controller' => 'Articles', 'action' => 'myAction'],
            ['_name' => 'my-route']
        );
        $result = $this->Form->create(null, ['url' => ['_name' => 'my-route']]);
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/new-article',
                'accept-charset' => $encoding,
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test automatic accept-charset overriding
     */
    public function testCreateWithAcceptCharset(): void
    {
        $result = $this->Form->create(
            $this->article,
            [
                'type' => 'post', 'url' => ['action' => 'index'], 'encoding' => 'iso-8859-1',
            ]
        );
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/Articles',
                'accept-charset' => 'iso-8859-1',
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test base form URL when 'url' param is passed with multiple parameters (&)
     */
    public function testCreateQueryStringRequest(): void
    {
        $encoding = strtolower(Configure::read('App.encoding'));
        $result = $this->Form->create($this->article, [
            'type' => 'post',
            'escape' => false,
            'url' => [
                'controller' => 'Controller',
                'action' => 'action',
                '?' => ['param1' => 'value1', 'param2' => 'value2'],
            ],
        ]);
        $expected = [
            'form' => [
                'method' => 'post',
                'action' => '/Controller/action?param1=value1&amp;param2=value2',
                'accept-charset' => $encoding,
            ],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->create($this->article, [
            'type' => 'post',
            'url' => [
                'controller' => 'Controller',
                'action' => 'action',
                '?' => ['param1' => 'value1', 'param2' => 'value2'],
            ],
        ]);
        $this->assertHtml($expected, $result);
    }

    /**
     * test that create() doesn't cause errors by multiple id's being in the primary key
     * as could happen with multiple select or checkboxes.
     */
    public function testCreateWithMultipleIdInData(): void
    {
        $encoding = strtolower(Configure::read('App.encoding'));

        $this->View->setRequest($this->View->getRequest()->withData('Article.id', [1, 2]));
        $result = $this->Form->create($this->article);
        $expected = [
            'form' => [
                'method' => 'post',
                'action' => '/articles/add',
                'accept-charset' => $encoding,
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test that create() doesn't add in extra passed params.
     */
    public function testCreatePassedArgs(): void
    {
        $encoding = strtolower(Configure::read('App.encoding'));
        $this->View->setRequest($this->View->getRequest()->withData('Article.id', 1));
        $result = $this->Form->create($this->article, [
            'type' => 'post',
            'escape' => false,
            'url' => [
                'action' => 'edit',
                'myparam',
            ],
        ]);
        $expected = [
            'form' => [
                'method' => 'post',
                'action' => '/Articles/edit/myparam',
                'accept-charset' => $encoding,
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test creating a get form, and get form inputs.
     */
    public function testGetFormCreate(): void
    {
        $encoding = strtolower(Configure::read('App.encoding'));
        $result = $this->Form->create($this->article, ['type' => 'get']);
        $expected = ['form' => [
            'method' => 'get', 'action' => '/articles/add',
            'accept-charset' => $encoding,
        ]];
        $this->assertHtml($expected, $result);

        $result = $this->Form->text('title');
        $expected = ['input' => [
            'name' => 'title', 'type' => 'text', 'required' => 'required',
        ]];
        $this->assertHtml($expected, $result);

        $result = $this->Form->password('password');
        $expected = ['input' => [
            'name' => 'password', 'type' => 'password',
        ]];
        $this->assertHtml($expected, $result);
        $this->assertDoesNotMatchRegularExpression('/<input[^<>]+[^id|name|type|value]=[^<>]*\/>$/', $result);

        $result = $this->Form->text('user_form');
        $expected = ['input' => [
            'name' => 'user_form', 'type' => 'text',
        ]];
        $this->assertHtml($expected, $result);
    }

    /**
     * test get form, and inputs when the model param is false
     */
    public function testGetFormWithFalseModel(): void
    {
        $encoding = strtolower(Configure::read('App.encoding'));
        $this->View->setRequest($this->View->getRequest()->withParam('controller', 'ContactTest'));
        $result = $this->Form->create(null, [
            'type' => 'get', 'url' => ['controller' => 'ContactTest'],
        ]);

        $expected = ['form' => [
            'method' => 'get', 'action' => '/ContactTest/add',
            'accept-charset' => $encoding,
        ]];
        $this->assertHtml($expected, $result);

        $result = $this->Form->text('reason');
        $expected = [
            'input' => ['type' => 'text', 'name' => 'reason'],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormCreateWithSecurity method
     *
     * Test form->create() with security key.
     */
    public function testCreateWithSecurity(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('csrfToken', 'testKey'));
        $encoding = strtolower(Configure::read('App.encoding'));
        $result = $this->Form->create($this->article, [
            'url' => '/articles/publish',
        ]);
        $expected = [
            'form' => ['method' => 'post', 'action' => '/articles/publish', 'accept-charset' => $encoding],
            'div' => ['style' => 'display:none;'],
            ['input' => [
                'type' => 'hidden',
                'name' => '_csrfToken',
                'value' => 'testKey',
                'autocomplete' => 'off',
            ]],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->create($this->article, ['url' => '/articles/publish', 'id' => 'MyForm']);
        $expected['form']['id'] = 'MyForm';
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormCreateGetNoSecurity method
     *
     * Test form->create() with no security key as its a get form
     */
    public function testCreateEndGetNoSecurity(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('csrfToken', 'testKey'));
        $article = new Article();
        $result = $this->Form->create($article, [
            'type' => 'get',
            'url' => '/contacts/add',
        ]);
        $this->assertStringNotContainsString('testKey', $result);

        $result = $this->Form->end();
        $this->assertStringNotContainsString('testKey', $result);
    }

    /**
     * Tests form hash generation with model-less data
     */
    public function testValidateHashNoModel(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));

        $fields = ['anything'];
        $this->Form->create();
        $result = $this->Form->secure($fields);

        $hash = hash_hmac('sha1', $this->url . serialize($fields) . session_id(), Security::getSalt());
        $this->assertStringContainsString($hash, $result);
    }

    /**
     * Tests that hidden fields generated for checkboxes don't get locked
     */
    public function testNoCheckboxLocking(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));
        $this->Form->create();

        $this->assertSame([], $this->Form->getFormProtector()->__debugInfo()['fields']);

        $this->Form->checkbox('check', ['value' => '1']);
        $this->assertSame(['check'], $this->Form->getFormProtector()->__debugInfo()['fields']);
    }

    /**
     * testFormSecurityFields method
     *
     * Test generation of secure form hash generation.
     */
    public function testFormSecurityFields(): void
    {
        $fields = ['Model.password', 'Model.username', 'Model.valid' => '0'];

        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));
        $this->Form->create();
        $result = $this->Form->secure($fields);

        $hash = hash_hmac('sha1', $this->url . serialize($fields) . session_id(), Security::getSalt());
        $hash .= ':' . 'Model.valid';
        $hash = urlencode($hash);
        $tokenDebug = urlencode(json_encode([
            $this->url,
            $fields,
            [],
        ]));
        $expected = [
            'div' => ['style' => 'display:none;'],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[fields]',
                'value' => $hash,
                'autocomplete' => 'off',
            ]],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[unlocked]',
                'value' => '',
                'autocomplete' => 'off',
            ]],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[debug]',
                'value' => $tokenDebug,
                'autocomplete' => 'off',
            ]],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormSecurityFields method
     *
     * Test debug token is not generated if debug is false
     */
    public function testFormSecurityFieldsNoDebugMode(): void
    {
        Configure::write('debug', false);
        $fields = ['Model.password', 'Model.username', 'Model.valid' => '0'];

        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));
        $this->Form->create();
        $result = $this->Form->secure($fields);

        $hash = hash_hmac('sha1', $this->url . serialize($fields) . session_id(), Security::getSalt());
        $hash .= ':' . 'Model.valid';
        $hash = urlencode($hash);
        $expected = [
            'div' => ['style' => 'display:none;'],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[fields]',
                'autocomplete' => 'off',
                'value' => $hash,
            ]],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[unlocked]',
                'autocomplete' => 'off',
                'value' => '',
            ]],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Tests correct generation of number fields for smallint
     */
    public function testTextFieldGenerationForSmallint(): void
    {
        $this->article['schema'] = [
            'foo' => [
                'type' => 'smallinteger',
                'null' => false,
                'default' => null,
                'length' => 10,
            ],
        ];

        $this->Form->create($this->article);
        $result = $this->Form->control('foo');
        $this->assertStringContainsString('class="input number"', $result);
        $this->assertStringContainsString('type="number"', $result);
    }

    /**
     * Tests correct generation of number fields for tinyint
     */
    public function testTextFieldGenerationForTinyint(): void
    {
        $this->article['schema'] = [
            'foo' => [
                'type' => 'tinyinteger',
                'null' => false,
                'default' => null,
                'length' => 10,
            ],
        ];

        $this->Form->create($this->article);
        $result = $this->Form->control('foo');
        $this->assertStringContainsString('class="input number"', $result);
        $this->assertStringContainsString('type="number"', $result);
    }

    /**
     * Tests correct generation of number fields for double and float fields
     */
    public function testTextFieldGenerationForFloats(): void
    {
        $this->article['schema'] = [
            'foo' => [
                'type' => 'float',
                'null' => false,
                'default' => null,
                'length' => 10,
            ],
        ];

        $this->Form->create($this->article);
        $result = $this->Form->control('foo');
        $expected = [
            'div' => ['class' => 'input number'],
            'label' => ['for' => 'foo'],
            'Foo',
            '/label',
            ['input' => [
                'type' => 'number',
                'name' => 'foo',
                'id' => 'foo',
                'step' => 'any',
            ]],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('foo', ['step' => 0.5]);
        $expected = [
            'div' => ['class' => 'input number'],
            'label' => ['for' => 'foo'],
            'Foo',
            '/label',
            ['input' => [
                'type' => 'number',
                'name' => 'foo',
                'id' => 'foo',
                'step' => '0.5',
            ]],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Tests correct generation of number fields for integer fields
     */
    public function testTextFieldTypeNumberGenerationForIntegers(): void
    {
        $this->getTableLocator()->get('Contacts', [
            'className' => ContactsTable::class,
        ]);
        $this->Form->create([], ['context' => ['table' => 'Contacts']]);
        $result = $this->Form->control('age');
        $expected = [
            'div' => ['class' => 'input number'],
            'label' => ['for' => 'age'],
            'Age',
            '/label',
            ['input' => [
                'type' => 'number', 'name' => 'age',
                'id' => 'age',
            ]],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Tests correct generation of file upload fields for binary fields
     */
    public function testFileUploadFieldTypeGenerationForBinaries(): void
    {
        $table = $this->getTableLocator()->get('Contacts', [
            'className' => ContactsTable::class,
        ]);
        $table->setSchema(['foo' => [
            'type' => 'binary',
            'null' => false,
            'default' => null,
            'length' => 1024,
        ]]);
        $this->Form->create([], ['context' => ['table' => 'Contacts']]);

        $result = $this->Form->control('foo');
        $expected = [
            'div' => ['class' => 'input file'],
            'label' => ['for' => 'foo'],
            'Foo',
            '/label',
            ['input' => [
                'type' => 'file', 'name' => 'foo',
                'id' => 'foo',
            ]],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormSecurityMultipleFields method
     *
     * Test secure() with multiple row form. Ensure hash is correct.
     */
    public function testFormSecurityMultipleFields(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));
        $this->Form->create();

        $fields = [
            'Model.0.password', 'Model.0.username', 'Model.0.hidden' => 'value',
            'Model.0.valid' => '0', 'Model.1.password', 'Model.1.username',
            'Model.1.hidden' => 'value', 'Model.1.valid' => '0',
        ];
        $result = $this->Form->secure($fields);

        $sortedFields = [
                'Model.0.password',
                'Model.0.username',
                'Model.1.password',
                'Model.1.username',
                'Model.0.hidden' => 'value',
                'Model.0.valid' => '0',
                'Model.1.hidden' => 'value',
                'Model.1.valid' => '0',
        ];
        $hash = hash_hmac('sha1', $this->url . serialize($sortedFields) . session_id(), Security::getSalt());
        $hash .= ':Model.0.hidden|Model.0.valid|Model.1.hidden|Model.1.valid';
        $hash = urlencode($hash);

        $tokenDebug = urlencode(json_encode([
            $this->url,
            $fields,
            [],
        ]));

        $expected = [
            'div' => ['style' => 'display:none;'],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[fields]',
                'value' => $hash,
                'autocomplete' => 'off',
            ]],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[unlocked]',
                'autocomplete' => 'off',
                'value' => '',
            ]],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[debug]',
                'value' => $tokenDebug,
                'autocomplete' => 'off',
            ]],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormSecurityMultipleSubmitButtons
     *
     * test form submit generation and ensure that _Token is only created on end()
     */
    public function testFormSecurityMultipleSubmitButtons(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));

        $this->Form->create($this->article);
        $this->Form->text('Address.title');
        $this->Form->text('Address.first_name');

        $result = $this->Form->submit('Save', ['name' => 'save']);
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'submit', 'name' => 'save', 'value' => 'Save'],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->submit('Cancel', ['name' => 'cancel']);
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'submit', 'name' => 'cancel', 'value' => 'Cancel'],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->end();
        $tokenDebug = urlencode(json_encode([
            '/articles/add',
            [
                'Address.title',
                'Address.first_name',
            ],
            ['save', 'cancel'],
        ]));

        $expected = [
            'div' => ['style' => 'display:none;'],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[fields]',
                'autocomplete',
                'value',
            ]],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[unlocked]',
                'value' => 'cancel%7Csave',
                'autocomplete' => 'off',
            ]],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[debug]',
                'value' => $tokenDebug,
                'autocomplete' => 'off',
            ]],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test that buttons created with foo[bar] name attributes are unlocked correctly.
     */
    public function testSecurityButtonNestedNamed(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));

        $this->Form->create();
        $this->Form->button('Test', ['type' => 'submit', 'name' => 'Address[button]']);
        $result = $this->Form->getFormProtector()->__debugInfo()['unlockedFields'];
        $this->assertEquals(['Address.button'], $result);
    }

    /**
     * Test that submit inputs created with foo[bar] name attributes are unlocked correctly.
     */
    public function testSecuritySubmitNestedNamed(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));

        $this->Form->create($this->article);
        $this->Form->submit('Test', ['type' => 'submit', 'name' => 'Address[button]']);
        $result = $this->Form->getFormProtector()->__debugInfo()['unlockedFields'];
        $this->assertEquals(['Address.button'], $result);
    }

    /**
     * Test that the correct fields are unlocked for image submits with no names.
     */
    public function testSecuritySubmitImageNoName(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));

        $this->Form->create();
        $result = $this->Form->submit('save.png');
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'image', 'src' => 'img/save.png'],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->getFormProtector()->__debugInfo()['unlockedFields'];
        $this->assertEquals(['x', 'y'], $result);
    }

    /**
     * Test that the correct fields are unlocked for image submits with names.
     */
    public function testSecuritySubmitImageName(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));

        $this->Form->create();
        $result = $this->Form->submit('save.png', ['name' => 'test']);
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'image', 'name' => 'test', 'src' => 'img/save.png'],
            '/div',
        ];
        $this->assertHtml($expected, $result);
        $result = $this->Form->getFormProtector()->__debugInfo()['unlockedFields'];
        $this->assertEquals(['test', 'test_x', 'test_y'], $result);
    }

    /**
     * testFormSecurityMultipleControlFields method
     *
     * Test secure form creation with multiple row creation. Checks hidden, text, checkbox field types
     */
    public function testFormSecurityMultipleControlFields(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));
        $this->Form->create();

        $this->Form->hidden('Addresses.0.id', ['value' => '123456']);
        $this->Form->control('Addresses.0.title');
        $this->Form->control('Addresses.0.first_name');
        $this->Form->control('Addresses.0.last_name');
        $this->Form->control('Addresses.0.address');
        $this->Form->control('Addresses.0.city');
        $this->Form->control('Addresses.0.phone');
        $this->Form->control('Addresses.0.primary', ['type' => 'checkbox']);

        $this->Form->hidden('Addresses.1.id', ['value' => '654321']);
        $this->Form->control('Addresses.1.title');
        $this->Form->control('Addresses.1.first_name');
        $this->Form->control('Addresses.1.last_name');
        $this->Form->control('Addresses.1.address');
        $this->Form->control('Addresses.1.city');
        $this->Form->control('Addresses.1.phone');
        $this->Form->control('Addresses.1.primary', ['type' => 'checkbox']);

        $result = $this->Form->secure();
        $hash = 'a4fe49bde94894a01375e7aa2873ea8114a96471%3AAddresses.0.id%7CAddresses.1.id';
        $tokenDebug = urlencode(json_encode([
            '/articles/add',
            [
                'Addresses.0.id' => '123456',
                'Addresses.0.title',
                'Addresses.0.first_name',
                'Addresses.0.last_name',
                'Addresses.0.address',
                'Addresses.0.city',
                'Addresses.0.phone',
                'Addresses.0.primary',
                'Addresses.1.id' => '654321',
                'Addresses.1.title',
                'Addresses.1.first_name',
                'Addresses.1.last_name',
                'Addresses.1.address',
                'Addresses.1.city',
                'Addresses.1.phone',
                'Addresses.1.primary',
            ],
            [],
        ]));
        $expected = [
            'div' => ['style' => 'display:none;'],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[fields]',
                'value' => $hash,
                'autocomplete' => 'off',
            ]],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[unlocked]',
                'autocomplete' => 'off',
                'value' => '',
            ]],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[debug]',
                'value' => $tokenDebug,
                'autocomplete' => 'off',
            ]],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormSecurityArrayFields method
     *
     * Test form security with Model.field.0 style inputs.
     */
    public function testFormSecurityArrayFields(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));

        $this->Form->create();
        $this->Form->text('Address.primary.1');
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertSame('Address.primary', $result[0]);

        $this->Form->text('Address.secondary.1.0');
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertSame('Address.secondary', $result[1]);
    }

    /**
     * testFormSecurityMultipleControlDisabledFields method
     *
     * Test secure form generation with multiple records and disabled fields.
     */
    public function testFormSecurityMultipleControlDisabledFields(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', [
            'unlockedFields' => ['first_name', 'address'],
        ]));
        $this->Form->create();

        $this->Form->hidden('Addresses.0.id', ['value' => '123456']);
        $this->Form->text('Addresses.0.title');
        $this->Form->text('Addresses.0.first_name');
        $this->Form->text('Addresses.0.last_name');
        $this->Form->text('Addresses.0.address');
        $this->Form->text('Addresses.0.city');
        $this->Form->text('Addresses.0.phone');
        $this->Form->hidden('Addresses.1.id', ['value' => '654321']);
        $this->Form->text('Addresses.1.title');
        $this->Form->text('Addresses.1.first_name');
        $this->Form->text('Addresses.1.last_name');
        $this->Form->text('Addresses.1.address');
        $this->Form->text('Addresses.1.city');
        $this->Form->text('Addresses.1.phone');

        $result = $this->Form->secure();
        $hash = '43c4db25e4162c5e4edd9dea51f5f9d9d92215ec%3AAddresses.0.id%7CAddresses.1.id';
        $tokenDebug = urlencode(json_encode([
                '/articles/add',
                [
                    'Addresses.0.id' => '123456',
                    'Addresses.0.title',
                    'Addresses.0.last_name',
                    'Addresses.0.city',
                    'Addresses.0.phone',
                    'Addresses.1.id' => '654321',
                    'Addresses.1.title',
                    'Addresses.1.last_name',
                    'Addresses.1.city',
                    'Addresses.1.phone',
                ],
                [
                    'first_name',
                    'address',
                ],
            ]));

        $expected = [
            'div' => ['style' => 'display:none;'],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[fields]',
                'autocomplete' => 'off',
                'value' => $hash,
            ]],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[unlocked]',
                'autocomplete' => 'off',
                'value' => 'address%7Cfirst_name',
            ]],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[debug]',
                'autocomplete' => 'off',
                'value' => $tokenDebug,
            ]],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormSecurityControlDisabledFields method
     *
     * Test single record form with disabled fields.
     */
    public function testFormSecurityControlUnlockedFields(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', [
            'unlockedFields' => ['first_name', 'address'],
        ]));
        $this->Form->create();
        $result = $this->Form->getFormProtector()->__debugInfo()['unlockedFields'];
        $this->assertEquals(
            $this->View->getRequest()->getAttribute('formTokenData'),
            ['unlockedFields' => $result]
        );

        $this->Form->hidden('Addresses.id', ['value' => '123456']);
        $this->Form->text('Addresses.title');
        $this->Form->text('Addresses.first_name');
        $this->Form->text('Addresses.last_name');
        $this->Form->text('Addresses.address');
        $this->Form->text('Addresses.city');
        $this->Form->text('Addresses.phone');

        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $expected = [
            'Addresses.id' => '123456', 'Addresses.title', 'Addresses.last_name',
            'Addresses.city', 'Addresses.phone',
        ];
        $this->assertEquals($expected, $result);

        $result = $this->Form->secure($expected, ['data-foo' => 'bar']);

        $hash = 'f98315a7d5515e5ae32e35f7d680207c085fae69%3AAddresses.id';
        $tokenDebug = urlencode(json_encode([
                '/articles/add',
                [
                    'Addresses.id' => '123456',
                    'Addresses.title',
                    'Addresses.last_name',
                    'Addresses.city',
                    'Addresses.phone',
                ],
                [
                    'first_name',
                    'address',
                ],
            ]));

        $expected = [
            'div' => ['style' => 'display:none;'],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[fields]',
                'value' => $hash,
                'autocomplete' => 'off',
                'data-foo' => 'bar',
            ]],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[unlocked]',
                'value' => 'address%7Cfirst_name',
                'autocomplete' => 'off',
                'data-foo' => 'bar',
            ]],
            ['input' => [
                'type' => 'hidden', 'name' => '_Token[debug]',
                'value' => $tokenDebug,
                'autocomplete' => 'off',
                'data-foo' => 'bar',
            ]],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormSecurityControlUnlockedFieldsDebugSecurityTrue method
     *
     * Test single record form with debugSecurity param.
     */
    public function testFormSecurityControlUnlockedFieldsDebugSecurityTrue(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', [
            'unlockedFields' => ['first_name', 'address'],
        ]));
        $this->Form->create();
        $result = $this->Form->getFormProtector()->__debugInfo()['unlockedFields'];
        $this->assertEquals(
            $this->View->getRequest()->getAttribute('formTokenData'),
            ['unlockedFields' => $result]
        );

        $this->Form->hidden('Addresses.id', ['value' => '123456']);
        $this->Form->text('Addresses.title');
        $this->Form->text('Addresses.first_name');
        $this->Form->text('Addresses.last_name');
        $this->Form->text('Addresses.address');
        $this->Form->text('Addresses.city');
        $this->Form->text('Addresses.phone');

        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $expected = [
            'Addresses.id' => '123456', 'Addresses.title', 'Addresses.last_name',
            'Addresses.city', 'Addresses.phone',
        ];
        $this->assertEquals($expected, $result);
        $result = $this->Form->secure($expected, ['data-foo' => 'bar', 'debugSecurity' => true]);

        $hash = 'f98315a7d5515e5ae32e35f7d680207c085fae69%3AAddresses.id';
        $tokenDebug = urlencode(json_encode([
            '/articles/add',
            [
                'Addresses.id' => '123456',
                'Addresses.title',
                'Addresses.last_name',
                'Addresses.city',
                'Addresses.phone',
            ],
            [
                'first_name',
                'address',
            ],
        ]));

        $expected = [
            'div' => ['style' => 'display:none;'],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[fields]',
                'value' => $hash,
                'autocomplete' => 'off',
                'data-foo' => 'bar',
            ]],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[unlocked]',
                'value' => 'address%7Cfirst_name',
                'autocomplete' => 'off',
                'data-foo' => 'bar',
            ]],
            ['input' => [
                'type' => 'hidden', 'name' => '_Token[debug]',
                'value' => $tokenDebug,
                'autocomplete' => 'off',
                'data-foo' => 'bar',
            ]],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormSecurityControlUnlockedFieldsDebugSecurityFalse method
     *
     * Debug is false, debugSecurity is true -> no debug
     */
    public function testFormSecurityControlUnlockedFieldsDebugSecurityDebugFalse(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', [
            'unlockedFields' => ['first_name', 'address'],
        ]));
        $this->Form->create();
        $result = $this->Form->getFormProtector()->__debugInfo()['unlockedFields'];
        $this->assertEquals(
            $this->View->getRequest()->getAttribute('formTokenData'),
            ['unlockedFields' => $result]
        );

        $this->Form->hidden('Addresses.id', ['value' => '123456']);
        $this->Form->text('Addresses.title');
        $this->Form->text('Addresses.first_name');
        $this->Form->text('Addresses.last_name');
        $this->Form->text('Addresses.address');
        $this->Form->text('Addresses.city');
        $this->Form->text('Addresses.phone');

        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $expected = [
            'Addresses.id' => '123456', 'Addresses.title', 'Addresses.last_name',
            'Addresses.city', 'Addresses.phone',
        ];
        $this->assertEquals($expected, $result);
        Configure::write('debug', false);
        $result = $this->Form->secure($expected, ['data-foo' => 'bar', 'debugSecurity' => true]);

        $hash = 'f98315a7d5515e5ae32e35f7d680207c085fae69%3AAddresses.id';
        $expected = [
            'div' => ['style' => 'display:none;'],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[fields]',
                'value' => $hash,
                'autocomplete' => 'off',
                'data-foo' => 'bar',
            ]],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[unlocked]',
                'value' => 'address%7Cfirst_name',
                'autocomplete' => 'off',
                'data-foo' => 'bar',
            ]],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormSecurityControlUnlockedFieldsDebugSecurityFalse method
     *
     * Test single record form with debugSecurity param.
     */
    public function testFormSecurityControlUnlockedFieldsDebugSecurityFalse(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', [
            'unlockedFields' => ['first_name', 'address'],
        ]));
        $this->Form->create();
        $result = $this->Form->getFormProtector()->__debugInfo()['unlockedFields'];
        $this->assertEquals(
            $this->View->getRequest()->getAttribute('formTokenData'),
            ['unlockedFields' => $result]
        );

        $this->Form->hidden('Addresses.id', ['value' => '123456']);
        $this->Form->text('Addresses.title');
        $this->Form->text('Addresses.first_name');
        $this->Form->text('Addresses.last_name');
        $this->Form->text('Addresses.address');
        $this->Form->text('Addresses.city');
        $this->Form->text('Addresses.phone');

        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $expected = [
            'Addresses.id' => '123456', 'Addresses.title', 'Addresses.last_name',
            'Addresses.city', 'Addresses.phone',
        ];
        $this->assertEquals($expected, $result);

        $result = $this->Form->secure($expected, ['data-foo' => 'bar', 'debugSecurity' => false]);
        $hash = 'f98315a7d5515e5ae32e35f7d680207c085fae69%3AAddresses.id';

        $expected = [
            'div' => ['style' => 'display:none;'],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[fields]',
                'value' => $hash,
                'autocomplete' => 'off',
                'data-foo' => 'bar',
            ]],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[unlocked]',
                'value' => 'address%7Cfirst_name',
                'autocomplete' => 'off',
                'data-foo' => 'bar',
            ]],
            '/div',
        ];

        $this->assertHtml($expected, $result);
    }

    /**
     * testFormSecureWithCustomNameAttribute method
     *
     * Test securing inputs with custom name attributes.
     */
    public function testFormSecureWithCustomNameAttribute(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));
        $this->Form->create();

        $this->Form->text('UserForm.published', ['name' => 'User[custom]']);
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertSame('User.custom', $result[0]);

        $this->Form->text('UserForm.published', ['name' => 'User[custom][another][value]']);
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertSame('User.custom.another.value', $result[1]);
    }

    /**
     * testFormSecuredControl method
     *
     * Test generation of entire secure form, assertions made on control() output.
     */
    public function testFormSecuredControl(): void
    {
        $this->View->setRequest($this->View->getRequest()
            ->withAttribute('formTokenData', [])
            ->withAttribute('csrfToken', 'testKey'));
        $this->article['schema'] = [
            'ratio' => ['type' => 'decimal', 'length' => 5, 'precision' => 6],
            'population' => ['type' => 'decimal', 'length' => 15, 'precision' => 0],
        ];

        $result = $this->Form->create($this->article, ['url' => '/articles/add']);
        $encoding = strtolower(Configure::read('App.encoding'));
        $expected = [
            'form' => ['method' => 'post', 'action' => '/articles/add', 'accept-charset' => $encoding],
            'div' => ['style' => 'display:none;'],
            ['input' => [
                'type' => 'hidden',
                'name' => '_csrfToken',
                'value' => 'testKey',
                'autocomplete' => 'off',
            ]],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('ratio');
        $expected = [
            'div' => ['class'],
            'label' => ['for'],
            'Ratio',
            '/label',
            'input' => ['name', 'type' => 'number', 'step' => '0.000001', 'id'],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('population');
        $expected = [
            'div' => ['class'],
            'label' => ['for'],
            'Population',
            '/label',
            'input' => ['name', 'type' => 'number', 'step' => '1', 'id'],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('published', ['type' => 'text']);
        $expected = [
            'div' => ['class' => 'input text'],
            'label' => ['for' => 'published'],
            'Published',
            '/label',
            ['input' => [
                'type' => 'text',
                'name' => 'published',
                'id' => 'published',
            ]],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('other', ['type' => 'text']);
        $expected = [
            'div' => ['class' => 'input text'],
            'label' => ['for' => 'other'],
            'Other',
            '/label',
            ['input' => [
                'type' => 'text',
                'name' => 'other',
                'id',
            ]],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->hidden('stuff');
        $expected = [
            'input' => [
                'type' => 'hidden',
                'name' => 'stuff',
            ],
        ];

        $this->assertHtml($expected, $result);

        $result = $this->Form->hidden('hidden', ['value' => false]);
        $expected = ['input' => [
            'type' => 'hidden',
            'name' => 'hidden',
            'value' => '0',
        ]];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('something', ['type' => 'checkbox']);
        $expected = [
            'div' => ['class' => 'input checkbox'],
            ['input' => [
                'type' => 'hidden',
                'name' => 'something',
                'value' => '0',
            ]],
            'label' => ['for' => 'something'],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'something',
                'value' => '1',
                'id' => 'something',
            ]],
            'Something',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $expectedFields = [
            'ratio',
            'population',
            'published',
            'other',
            'stuff' => '',
            'hidden' => '0',
            'something',
        ];
        $this->assertEquals($expectedFields, $result);

        $result = $this->Form->secure();
        $tokenDebug = urlencode(json_encode([
            '/articles/add',
            $expectedFields,
            [],
        ]));

        $expected = [
            'div' => ['style' => 'display:none;'],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[fields]',
                'value',
                'autocomplete',
            ]],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[unlocked]',
                'value' => '',
                'autocomplete' => 'off',
            ]],
            ['input' => [
                'type' => 'hidden', 'name' => '_Token[debug]',
                'value' => $tokenDebug,
                'autocomplete' => 'off',
            ]],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $data = [
            'ratio' => '',
            'population' => '',
            'published' => '',
            'other' => '',
            'stuff' => '',
            'hidden' => '0',
            'something' => '',
            '_Token' => $this->Form->getFormProtector()->buildTokenData(),
        ];

        $this->assertTrue($this->Form->getFormProtector()->validate($data, '', ''));
    }

    /**
     * testSecuredControlCustomName method
     *
     * Test secured inputs with custom names.
     */
    public function testSecuredControlCustomName(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));
        $this->Form->create();

        $this->Form->text('text_input', [
            'name' => 'Option[General.default_role]',
        ]);
        $expected = ['Option.General.default_role'];
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEquals($expected, $result);

        $this->Form->select('select_box', [1, 2], [
            'name' => 'Option[General.select_role]',
        ]);
        $expected[] = 'Option.General.select_role';
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEquals($expected, $result);

        $this->Form->text('other.things[]');
        $expected[] = 'other.things';
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEquals($expected, $result);
    }

    /**
     * testSecuredControlDuplicate method
     *
     * Test that a hidden field followed by a visible field
     * undoes the hidden field locking.
     */
    public function testSecuredControlDuplicate(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));
        $this->Form->create();

        $this->Form->control('text_val', [
                'type' => 'hidden',
                'value' => 'some text',
        ]);
        $expected = ['text_val' => 'some text'];
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEquals($expected, $result);

        $this->Form->control('text_val', [
                'type' => 'text',
        ]);
        $expected = ['text_val'];
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEquals($expected, $result);
    }

    /**
     * testFormSecuredFileControl method
     *
     * Tests that the correct keys are added to the field hash index.
     */
    public function testFormSecuredFileControl(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));
        $this->Form->create();

        $this->Form->file('Attachment.file');
        $expected = ['Attachment.file'];
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEquals($expected, $result);
    }

    /**
     * testFormSecuredMultipleSelect method
     *
     * Test that multiple selects keys are added to field hash.
     */
    public function testFormSecuredMultipleSelect(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));
        $this->Form->create();

        $options = ['1' => 'one', '2' => 'two'];
        $this->Form->select('Model.select', $options);
        $expected = ['Model.select'];
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEquals($expected, $result);

        $this->Form->fields = [];
        $this->Form->select('Model.select', $options, ['multiple' => true]);
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEquals($expected, $result);
    }

    /**
     * testFormSecuredRadio method
     */
    public function testFormSecuredRadio(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));
        $this->Form->create();

        $options = ['1' => 'option1', '2' => 'option2'];

        $this->Form->radio('Test.test', $options);
        $expected = ['Test.test'];
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEquals($expected, $result);

        $this->Form->radio('Test.all', $options, [
            'disabled' => ['option1', 'option2'],
        ]);
        $expected = ['Test.test', 'Test.all' => ''];
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEquals($expected, $result);

        $this->Form->radio('Test.some', $options, [
            'disabled' => ['option1'],
        ]);
        $expected = ['Test.test', 'Test.all' => '', 'Test.some'];
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEquals($expected, $result);
    }

    /**
     * testFormSecuredAndDisabledNotAssoc method
     *
     * Test that when disabled is in a list based attribute array it works.
     */
    public function testFormSecuredAndDisabledNotAssoc(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));
        $this->Form->create();

        $this->deprecated(function () {
            $this->Form->select('Model.select', [1, 2], ['disabled']);
            $this->Form->checkbox('Model.checkbox', ['disabled']);
            $this->Form->text('Model.text', ['disabled']);
            $this->Form->textarea('Model.textarea', ['disabled']);
            $this->Form->password('Model.password', ['disabled']);
            $this->Form->radio('Model.radio', [1, 2], ['disabled']);
        });

        $expected = [
            'Model.radio' => '',
        ];
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEquals($expected, $result);
    }

    /**
     * testFormSecuredAndDisabled method
     *
     * Test that forms with disabled inputs + secured forms leave off the inputs from the form
     * hashing.
     */
    public function testFormSecuredAndDisabled(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));
        $this->Form->create();

        $this->Form->checkbox('Model.checkbox', ['disabled' => true]);
        $this->Form->text('Model.text', ['disabled' => true]);
        $this->Form->password('Model.text', ['disabled' => true]);
        $this->Form->textarea('Model.textarea', ['disabled' => true]);
        $this->Form->select('Model.select', [1, 2], ['disabled' => true]);
        $this->Form->radio('Model.radio', [1, 2], ['disabled' => [1, 2]]);
        $this->Form->year('Model.year', ['disabled' => true]);
        $this->Form->month('Model.month', ['disabled' => true]);
        $this->Form->day('Model.day', ['disabled' => true]);
        $this->Form->hour('Model.hour', ['disabled' => true]);
        $this->Form->minute('Model.minute', ['disabled' => true]);
        $this->Form->meridian('Model.meridian', ['disabled' => true]);

        $expected = [
            'Model.radio' => '',
        ];
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEquals($expected, $result);
    }

    /**
     * testUnlockFieldAddsToList method
     *
     * Test disableField.
     */
    public function testUnlockFieldAddsToList(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', [
            'unlockedFields' => [],
        ]));
        $this->Form->create();

        $this->Form->unlockField('Contact.name');
        $this->Form->text('Contact.name');

        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEquals([], $result);

        $result = $this->Form->getFormProtector()->__debugInfo()['unlockedFields'];
        $this->assertEquals(['Contact.name'], $result);
    }

    /**
     * testUnlockFieldRemovingFromFields method
     *
     * Test unlockField removing from fields array.
     */
    public function testUnlockFieldRemovingFromFields(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', [
            'unlockedFields' => [],
        ]));
        $this->Form->create($this->article);
        $this->Form->hidden('Article.id', ['value' => 1]);
        $this->Form->text('Article.title');

        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertSame('1', $result['Article.id'], 'Hidden input should be secured.');
        $this->assertContains('Article.title', $result, 'Field should be secured.');

        $this->Form->unlockField('Article.title');
        $this->Form->unlockField('Article.id');
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEquals([], $result);
    }

    /**
     * testResetUnlockFields method
     *
     * Test reset unlockFields, when create new form.
     */
    public function testResetUnlockFields(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', [
            'key' => 'testKey',
            'unlockedFields' => [],
        ]));

        $this->Form->create();
        $this->Form->unlockField('Contact.id');
        $this->Form->hidden('Contact.id', ['value' => 1]);
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEmpty($result, 'Field should be unlocked');
        $this->Form->end();

        $this->Form->create();
        $this->Form->hidden('Contact.id', ['value' => 1]);
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertSame('1', $result['Contact.id'], 'Hidden input should be secured.');
    }

    /**
     * testSecuredFormUrlIgnoresHost method
     *
     * Test that only the path + query elements of a form's URL show up in their hash.
     */
    public function testSecuredFormUrlIgnoresHost(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', ['key' => 'testKey']));

        $expected = '2548654895b160d724042ed269a2a863fd9d66ee%3A';
        $this->Form->create($this->article, [
            'url' => ['controller' => 'articles', 'action' => 'view', 1, '?' => ['page' => 1]],
        ]);
        $result = $this->Form->secure();
        $this->assertStringContainsString($expected, $result);

        $this->Form->create($this->article, ['url' => 'http://localhost/articles/view/1?page=1']);
        $result = $this->Form->secure();
        $this->assertStringContainsString($expected, $result, 'Full URL should only use path and query.');

        $this->Form->create($this->article, ['url' => '/articles/view/1?page=1']);
        $result = $this->Form->secure();
        $this->assertStringContainsString($expected, $result, 'URL path + query should work.');

        $this->Form->create($this->article, ['url' => '/articles/view/1']);
        $result = $this->Form->secure();
        $this->assertStringNotContainsString($expected, $result, 'URL is different');
    }

    /**
     * testSecuredFormUrlHasHtmlAndIdentifier method
     *
     * Test that URL, HTML and identifier show up in their hashes.
     */
    public function testSecuredFormUrlHasHtmlAndIdentifier(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));

        $expected = '0a913f45b887b4d9cc2650ef1edc50183896959c%3A';
        $this->Form->create($this->article, [
            'url' => [
                'controller' => 'articles',
                'action' => 'view',
                '?' => [
                    'page' => 1,
                    'limit' => 10,
                    'html' => '<>"',
                ],
                '#' => 'result',
            ],
        ]);
        $result = $this->Form->secure();
        $this->assertStringContainsString($expected, $result);

        $this->Form->create($this->article, [
            'url' => 'http://localhost/articles/view?page=1&limit=10&html=%3C%3E%22#result',
        ]);
        $result = $this->Form->secure();
        $this->assertStringContainsString($expected, $result, 'Full URL should only use path and query.');

        $this->Form->create($this->article, [
            'url' => '/articles/view?page=1&limit=10&html=%3C%3E%22#result',
        ]);
        $result = $this->Form->secure();
        $this->assertStringContainsString($expected, $result, 'URL path + query should work.');
    }

    /**
     * testErrorMessageDisplay method
     *
     * Test error message display.
     */
    public function testErrorMessageDisplay(): void
    {
        $this->article['errors'] = [
            'Article' => [
                'title' => 'error message',
                'content' => 'some <strong>test</strong> data with <a href="#">HTML</a> chars',
            ],
        ];
        $this->Form->create($this->article);

        $result = $this->Form->control('Article.title');
        $expected = [
            'div' => ['class' => 'input text error'],
            'label' => ['for' => 'article-title'],
            'Title',
            '/label',
            'input' => [
                'type' => 'text',
                'name' => 'Article[title]',
                'id' => 'article-title',
                'class' => 'form-error',
                'aria-invalid' => 'true',
                'aria-describedby' => 'article-title-error',
            ],
            ['div' => ['class' => 'error-message', 'id' => 'article-title-error']],
            'error message',
            '/div',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('Article.title', [
            'templates' => [
                'inputContainerError' => '<div class="input {{type}}{{required}} error">{{content}}</div>',
            ],
        ]);

        $expected = [
            'div' => ['class' => 'input text error'],
            'label' => ['for' => 'article-title'],
            'Title',
            '/label',
            'input' => [
                'type' => 'text',
                'name' => 'Article[title]',
                'id' => 'article-title',
                'class' => 'form-error',
                // No aria-describedby because error template is custom
                'aria-invalid' => 'true',
            ],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('Article.content');
        $expected = [
            'div' => ['class' => 'input text error'],
            'label' => ['for' => 'article-content'],
            'Content',
            '/label',
            'input' => [
                'type' => 'text',
                'name' => 'Article[content]',
                'id' => 'article-content',
                'class' => 'form-error',
                'aria-invalid' => 'true',
                'aria-describedby' => 'article-content-error',
            ],
            ['div' => ['class' => 'error-message', 'id' => 'article-content-error']],
            'some &lt;strong&gt;test&lt;/strong&gt; data with &lt;a href=&quot;#&quot;&gt;HTML&lt;/a&gt; chars',
            '/div',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('Article.content', ['error' => ['escape' => true]]);
        $expected = [
            'div' => ['class' => 'input text error'],
            'label' => ['for' => 'article-content'],
            'Content',
            '/label',
            'input' => [
                'type' => 'text',
                'name' => 'Article[content]',
                'id' => 'article-content',
                'class' => 'form-error',
                'aria-invalid' => 'true',
                'aria-describedby' => 'article-content-error',
            ],
            ['div' => ['class' => 'error-message', 'id' => 'article-content-error']],
            'some &lt;strong&gt;test&lt;/strong&gt; data with &lt;a href=&quot;#&quot;&gt;HTML&lt;/a&gt; chars',
            '/div',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('Article.content', ['error' => ['escape' => false]]);
        $expected = [
            'div' => ['class' => 'input text error'],
            'label' => ['for' => 'article-content'],
            'Content',
            '/label',
            'input' => [
                'type' => 'text',
                'name' => 'Article[content]',
                'id' => 'article-content',
                'class' => 'form-error',
                'aria-invalid' => 'true',
                'aria-describedby' => 'article-content-error',
            ],
            ['div' => ['class' => 'error-message', 'id' => 'article-content-error']],
            'some <strong>test</strong> data with <a href="#">HTML</a> chars',
            '/div',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testEmptyErrorValidation method
     *
     * Test validation errors, when validation message is an empty string.
     */
    public function testEmptyErrorValidation(): void
    {
        $this->article['errors'] = [
            'Article' => ['title' => ''],
        ];
        $this->Form->create($this->article);

        $result = $this->Form->control('Article.title');
        $expected = [
            'div' => ['class' => 'input text error'],
            'label' => ['for' => 'article-title'],
            'Title',
            '/label',
            'input' => [
                'type' => 'text',
                'name' => 'Article[title]',
                'id' => 'article-title',
                'class' => 'form-error',
                'aria-invalid' => 'true',
                'aria-describedby' => 'article-title-error',
            ],
            ['div' => ['class' => 'error-message', 'id' => 'article-title-error']],
            [],
            '/div',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testEmptyControlErrorValidation method
     *
     * Test validation errors, when calling control() overriding validation message by an empty string.
     */
    public function testEmptyControlErrorValidation(): void
    {
        $this->article['errors'] = [
            'Article' => ['title' => 'error message'],
        ];
        $this->Form->create($this->article);

        $result = $this->Form->control('Article.title', ['error' => '']);
        $expected = [
            'div' => ['class' => 'input text error'],
            'label' => ['for' => 'article-title'],
            'Title',
            '/label',
            'input' => [
                'aria-invalid' => 'true',
                'aria-describedby' => 'article-title-error',
                'type' => 'text',
                'name' => 'Article[title]',
                'id' => 'article-title',
                'class' => 'form-error',
            ],
            ['div' => ['class' => 'error-message', 'id' => 'article-title-error']],
            [],
            '/div',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testControlErrorMessage method
     *
     * Test validation errors, when calling control() overriding validation messages.
     */
    public function testControlErrorMessage(): void
    {
        $this->article['errors'] = [
            'title' => ['error message'],
        ];
        $this->Form->create($this->article);

        $result = $this->Form->control('title', [
            'error' => 'Custom error!',
        ]);
        $expected = [
            'div' => ['class' => 'input text required error'],
            'label' => ['for' => 'title'],
            'Title',
            '/label',
            'input' => [
                'type' => 'text',
                'name' => 'title',
                'id' => 'title',
                'class' => 'form-error',
                'required' => 'required',
                'data-validity-message' => 'This field cannot be left empty',
                'oninvalid' => 'this.setCustomValidity(&#039;&#039;); if (!this.value) this.setCustomValidity(this.dataset.validityMessage)',
                'oninput' => 'this.setCustomValidity(&#039;&#039;)',
                'aria-required' => 'true',
                'aria-invalid' => 'true',
                'aria-describedby' => 'title-error',
            ],
            ['div' => ['class' => 'error-message', 'id' => 'title-error']],
            'Custom error!',
            '/div',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('title', [
            'error' => ['error message' => 'Custom error!'],
        ]);
        $expected = [
            'div' => ['class' => 'input text required error'],
            'label' => ['for' => 'title'],
            'Title',
            '/label',
            'input' => [
                'type' => 'text',
                'name' => 'title',
                'id' => 'title',
                'aria-required' => 'true',
                'aria-invalid' => 'true',
                'aria-describedby' => 'title-error',
                'class' => 'form-error',
                'required' => 'required',
                'data-validity-message' => 'This field cannot be left empty',
                'oninvalid' => 'this.setCustomValidity(&#039;&#039;); if (!this.value) this.setCustomValidity(this.dataset.validityMessage)',
                'oninput' => 'this.setCustomValidity(&#039;&#039;)',
            ],
            ['div' => ['class' => 'error-message', 'id' => 'title-error']],
            'Custom error!',
            '/div',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormValidationAssociated method
     *
     * Tests displaying errors for nested entities.
     */
    public function testFormValidationAssociated(): void
    {
        $nested = new Entity(['foo' => 'bar']);
        $nested->setError('foo', ['not a valid bar']);
        $entity = new Entity(['nested' => $nested]);
        $this->Form->create($entity, ['context' => ['table' => 'Articles']]);

        $result = $this->Form->error('nested.foo');
        $this->assertSame('<div class="error-message" id="nested-foo-error">not a valid bar</div>', $result);
    }

    /**
     * testFormValidationAssociatedSecondLevel method
     *
     * Test form error display with associated model.
     */
    public function testFormValidationAssociatedSecondLevel(): void
    {
        $inner = new Entity(['bar' => 'baz']);
        $nested = new Entity(['foo' => $inner]);
        $entity = new Entity(['nested' => $nested]);
        $inner->setError('bar', ['not a valid one']);
        $this->Form->create($entity, ['context' => ['table' => 'Articles']]);
        $result = $this->Form->error('nested.foo.bar');
        $this->assertSame('<div class="error-message" id="nested-foo-bar-error">not a valid one</div>', $result);
    }

    /**
     * testFormValidationMultiRecord method
     *
     * Test form error display with multiple records.
     */
    public function testFormValidationMultiRecord(): void
    {
        $one = new Entity();
        $two = new Entity();
        $this->getTableLocator()->get('Contacts', [
            'className' => ContactsTable::class,
        ]);
        $one->set('email', '');
        $one->setError('email', ['invalid email']);

        $two->set('name', '');
        $two->setError('name', ['This is wrong']);
        $this->Form->create([$one, $two], ['context' => ['table' => 'Contacts']]);

        $result = $this->Form->control('0.email');
        $expected = [
            'div' => ['class' => 'input email error'],
            'label' => ['for' => '0-email'],
            'Email',
            '/label',
            'input' => [
                'type' => 'email',
                'name' => '0[email]',
                'id' => '0-email',
                'class' => 'form-error',
                'maxlength' => 255,
                'value' => '',
                'aria-invalid' => 'true',
                'aria-describedby' => '0-email-error',
            ],
            ['div' => ['class' => 'error-message', 'id' => '0-email-error']],
            'invalid email',
            '/div',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('1.name');
        $expected = [
            'div' => ['class' => 'input text error'],
            'label' => ['for' => '1-name'],
            'Name',
            '/label',
            'input' => [
                'type' => 'text',
                'name' => '1[name]',
                'id' => '1-name',
                'class' => 'form-error',
                'maxlength' => 255,
                'value' => '',
                'aria-invalid' => 'true',
                'aria-describedby' => '1-name-error',
            ],
            ['div' => ['class' => 'error-message', 'id' => '1-name-error']],
            'This is wrong',
            '/div',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testControl method
     *
     * Test various incarnations of control().
     */
    public function testControl(): void
    {
        $this->getTableLocator()->get('ValidateUsers', [
            'className' => ValidateUsersTable::class,
        ]);
        $this->Form->create([], ['context' => ['table' => 'ValidateUsers']]);
        $result = $this->Form->control('ValidateUsers.balance');
        $expected = [
            'div' => ['class'],
            'label' => ['for'],
            'Balance',
            '/label',
            'input' => ['name', 'type' => 'number', 'id', 'step'],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('ValidateUser.cost_decimal');
        $expected = [
            'div' => ['class'],
            'label' => ['for'],
            'Cost Decimal',
            '/label',
            'input' => ['name', 'type' => 'number', 'step' => '0.001', 'id'],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('ValidateUser.null_decimal');
        $expected = [
            'div' => ['class'],
            'label' => ['for'],
            'Null Decimal',
            '/label',
            'input' => ['name', 'type' => 'number', 'id'],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testControlCustomization method
     *
     * Tests the input method and passing custom options.
     */
    public function testControlCustomization(): void
    {
        $this->getTableLocator()->get('Contacts', [
            'className' => ContactsTable::class,
        ]);
        $this->Form->create([], ['context' => ['table' => 'Contacts']]);
        $result = $this->Form->control('Contact.email', ['id' => 'custom']);
        $expected = [
            'div' => ['class' => 'input email'],
            'label' => ['for' => 'custom'],
            'Email',
            '/label',
            ['input' => [
                'type' => 'email', 'name' => 'Contact[email]',
                'id' => 'custom', 'maxlength' => 255,
            ]],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('Contact.email', [
            'templates' => ['inputContainer' => '<div>{{content}}</div>'],
        ]);
        $expected = [
            '<div',
            'label' => ['for' => 'contact-email'],
            'Email',
            '/label',
            ['input' => [
                'type' => 'email', 'name' => 'Contact[email]',
                'id' => 'contact-email', 'maxlength' => 255,
            ]],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('Contact.email', ['type' => 'text']);
        $expected = [
            'div' => ['class' => 'input text'],
            'label' => ['for' => 'contact-email'],
            'Email',
            '/label',
            ['input' => [
                'type' => 'text', 'name' => 'Contact[email]',
                'id' => 'contact-email', 'maxlength' => '255',
            ]],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('Contact.5.email', ['type' => 'text']);
        $expected = [
            'div' => ['class' => 'input text'],
            'label' => ['for' => 'contact-5-email'],
            'Email',
            '/label',
            ['input' => [
                'type' => 'text', 'name' => 'Contact[5][email]',
                'id' => 'contact-5-email', 'maxlength' => '255',
            ]],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('Contact.password');
        $expected = [
            'div' => ['class' => 'input password'],
            'label' => ['for' => 'contact-password'],
            'Password',
            '/label',
            ['input' => [
                'type' => 'password', 'name' => 'Contact[password]',
                'id' => 'contact-password',
            ]],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('Contact.email', [
            'type' => 'file', 'class' => 'textbox',
        ]);
        $expected = [
            'div' => ['class' => 'input file'],
            'label' => ['for' => 'contact-email'],
            'Email',
            '/label',
            ['input' => [
                'type' => 'file', 'name' => 'Contact[email]', 'class' => 'textbox',
                'id' => 'contact-email',
            ]],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $entity = new Entity(['phone' => 'Hello & World > weird chars']);
        $this->Form->create($entity, ['context' => ['table' => 'Contacts']]);
        $result = $this->Form->control('phone');
        $expected = [
            'div' => ['class' => 'input tel'],
            'label' => ['for' => 'phone'],
            'Phone',
            '/label',
            ['input' => [
                'type' => 'tel', 'name' => 'phone',
                'value' => 'Hello &amp; World &gt; weird chars',
                'id' => 'phone', 'maxlength' => 255,
            ]],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $this->View->setRequest(
            $this->View->getRequest()->withData('Model.0.OtherModel.field', 'My value')
        );
        $this->Form->create();
        $result = $this->Form->control('Model.0.OtherModel.field', ['id' => 'myId']);
        $expected = [
            'div' => ['class' => 'input text'],
            'label' => ['for' => 'myId'],
            'Field',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'Model[0][OtherModel][field]',
                'value' => 'My value', 'id' => 'myId',
            ],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $this->View->setRequest($this->View->getRequest()->withParsedBody([]));
        $this->Form->create();

        $entity->setError('field', 'Badness!');
        $this->Form->create($entity, ['context' => ['table' => 'Contacts']]);
        $result = $this->Form->control('field');
        $expected = [
            'div' => ['class' => 'input text error'],
            'label' => ['for' => 'field'],
            'Field',
            '/label',
            'input' => [
                'type' => 'text',
                'name' => 'field',
                'id' => 'field',
                'class' => 'form-error',
                'aria-invalid' => 'true',
                'aria-describedby' => 'field-error',
            ],
            ['div' => ['class' => 'error-message', 'id' => 'field-error']],
            'Badness!',
            '/div',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('field', [
            'templates' => [
                'inputContainerError' => '{{content}}{{error}}',
                'error' => '<span class="error-message">{{content}}</span>',
            ],
        ]);
        $expected = [
            'label' => ['for' => 'field'],
            'Field',
            '/label',
            'input' => [
                'type' => 'text',
                'name' => 'field',
                'id' => 'field',
                'class' => 'form-error',
                // No aria-describedby because error template is custom
                'aria-invalid' => 'true',
            ],
            ['span' => ['class' => 'error-message']],
            'Badness!',
            '/span',
        ];
        $this->assertHtml($expected, $result);

        $entity->setError('field', ['minLength'], true);
        $result = $this->Form->control('field', [
            'error' => [
                'minLength' => 'Le login doit contenir au moins 2 caractères',
                'maxLength' => 'login too large',
            ],
        ]);
        $expected = [
            'div' => ['class' => 'input text error'],
            'label' => ['for' => 'field'],
            'Field',
            '/label',
            'input' => [
                'type' => 'text',
                'name' => 'field',
                'id' => 'field',
                'class' => 'form-error',
                'aria-invalid' => 'true',
                'aria-describedby' => 'field-error',
            ],
            ['div' => ['class' => 'error-message', 'id' => 'field-error']],
            'Le login doit contenir au moins 2 caractères',
            '/div',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $entity->setError('field', ['maxLength'], true);
        $result = $this->Form->control('field', [
            'error' => [
                'minLength' => 'Le login doit contenir au moins 2 caractères',
                'maxLength' => 'login too large',
            ],
        ]);
        $expected = [
            'div' => ['class' => 'input text error'],
            'label' => ['for' => 'field'],
            'Field',
            '/label',
            'input' => [
                'type' => 'text',
                'name' => 'field',
                'id' => 'field',
                'class' => 'form-error',
                'aria-invalid' => 'true',
                'aria-describedby' => 'field-error',
            ],
            ['div' => ['class' => 'error-message', 'id' => 'field-error']],
            'login too large',
            '/div',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testControlWithTemplateFile method
     *
     * Test that control() accepts a template file.
     */
    public function testControlWithTemplateFile(): void
    {
        $result = $this->Form->control('field', [
            'templates' => 'htmlhelper_tags',
        ]);
        $expected = [
            'label' => ['for' => 'field'],
            'Field',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'field',
                'id' => 'field',
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testNestedControlsEndWithBrackets method
     *
     * Test that nested inputs end with brackets.
     */
    public function testNestedControlsEndWithBrackets(): void
    {
        $result = $this->Form->text('nested.text[]');
        $expected = [
            'input' => [
                'type' => 'text', 'name' => 'nested[text][]',
            ],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->file('nested.file[]');
        $expected = [
            'input' => [
                'type' => 'file', 'name' => 'nested[file][]',
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testCreateIdPrefix method
     *
     * Test id prefix.
     */
    public function testCreateIdPrefix(): void
    {
        $this->Form->create(null, ['idPrefix' => 'prefix']);

        $result = $this->Form->control('field');
        $expected = [
            'div' => ['class' => 'input text'],
            'label' => ['for' => 'prefix-field'],
            'Field',
            '/label',
            'input' => ['type' => 'text', 'name' => 'field', 'id' => 'prefix-field'],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('field', ['id' => 'custom-id']);
        $expected = [
            'div' => ['class' => 'input text'],
            'label' => ['for' => 'custom-id'],
            'Field',
            '/label',
            'input' => ['type' => 'text', 'name' => 'field', 'id' => 'custom-id'],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->radio('Model.field', ['option A']);
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'Model[field]', 'value' => '', 'id' => 'prefix-model-field'],
            'label' => ['for' => 'prefix-model-field-0'],
            ['input' => [
                'type' => 'radio',
                'name' => 'Model[field]',
                'value' => '0',
                'id' => 'prefix-model-field-0',
            ]],
            'option A',
            '/label',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->radio('Model.field', ['option A', 'option']);
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'Model[field]', 'value' => '', 'id' => 'prefix-model-field'],
            'label' => ['for' => 'prefix-model-field-0'],
            ['input' => [
                'type' => 'radio',
                'name' => 'Model[field]',
                'value' => '0',
                'id' => 'prefix-model-field-0',
            ]],
            'option A',
            '/label',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->select(
            'Model.multi_field',
            ['first'],
            ['multiple' => 'checkbox']
        );
        $expected = [
            'input' => [
                'type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => '', 'id' => 'prefix-model-multi-field',
            ],
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'prefix-model-multi-field-0']],
            ['input' => [
                'type' => 'checkbox', 'name' => 'Model[multi_field][]',
                'value' => '0', 'id' => 'prefix-model-multi-field-0',
            ]],
            'first',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $this->Form->end();
        $result = $this->Form->control('field');
        $expected = [
            'div' => ['class' => 'input text'],
            'label' => ['for' => 'field'],
            'Field',
            '/label',
            'input' => ['type' => 'text', 'name' => 'field', 'id' => 'field'],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testControlZero method
     *
     * Test that inputs with 0 can be created.
     */
    public function testControlZero(): void
    {
        $this->getTableLocator()->get('Contacts', [
            'className' => ContactsTable::class,
        ]);
        $this->Form->create([], ['context' => ['table' => 'Contacts']]);
        $result = $this->Form->control('0');
        $expected = [
            'div' => ['class' => 'input text'],
            'label' => ['for' => '0'], '/label',
            'input' => ['type' => 'text', 'name' => '0', 'id' => '0'],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testControlCheckbox method
     *
     * Test control() with checkbox creation.
     */
    public function testControlCheckbox(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->getSchema()->addColumn('active', ['type' => 'boolean', 'default' => null]);
        $article = $articles->newEmptyEntity();

        $this->Form->create($article);

        $result = $this->Form->control('Articles.active');
        $expected = [
            'div' => ['class' => 'input checkbox'],
            'input' => ['type' => 'hidden', 'name' => 'Articles[active]', 'value' => '0'],
            'label' => ['for' => 'articles-active'],
            ['input' => ['type' => 'checkbox', 'name' => 'Articles[active]', 'value' => '1', 'id' => 'articles-active']],
            'Active',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('Articles.active', ['label' => false, 'checked' => true]);
        $expected = [
            'div' => ['class' => 'input checkbox'],
            'input' => ['type' => 'hidden', 'name' => 'Articles[active]', 'value' => '0'],
            ['input' => ['type' => 'checkbox', 'name' => 'Articles[active]', 'value' => '1', 'id' => 'articles-active', 'checked' => 'checked']],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('Articles.active', ['label' => false, 'checked' => 1]);
        $expected = [
            'div' => ['class' => 'input checkbox'],
            'input' => ['type' => 'hidden', 'name' => 'Articles[active]', 'value' => '0'],
            ['input' => ['type' => 'checkbox', 'name' => 'Articles[active]', 'value' => '1', 'id' => 'articles-active', 'checked' => 'checked']],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('Articles.active', ['label' => false, 'checked' => '1']);
        $expected = [
            'div' => ['class' => 'input checkbox'],
            'input' => ['type' => 'hidden', 'name' => 'Articles[active]', 'value' => '0'],
            ['input' => ['type' => 'checkbox', 'name' => 'Articles[active]', 'value' => '1', 'id' => 'articles-active', 'checked' => 'checked']],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('Articles.disabled', [
            'label' => 'Disabled',
            'type' => 'checkbox',
            'data-foo' => 'disabled',
        ]);
        $expected = [
            'div' => ['class' => 'input checkbox'],
            'input' => ['type' => 'hidden', 'name' => 'Articles[disabled]', 'value' => '0'],
            'label' => ['for' => 'articles-disabled'],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'Articles[disabled]',
                'value' => '1',
                'id' => 'articles-disabled',
                'data-foo' => 'disabled',
            ]],
            'Disabled',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('Articles.confirm', [
            'label' => 'Confirm <b>me</b>!',
            'type' => 'checkbox',
            'escape' => false,
        ]);
        $expected = [
            'div' => ['class' => 'input checkbox'],
            'input' => ['type' => 'hidden', 'name' => 'Articles[confirm]', 'value' => '0'],
            'label' => ['for' => 'articles-confirm'],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'Articles[confirm]',
                'value' => '1',
                'id' => 'articles-confirm',
            ]],
            'Confirm <b>me</b>!',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testControlHidden method
     *
     * Test that control() does not create wrapping div and label tag for hidden fields.
     */
    public function testControlHidden(): void
    {
        $this->getTableLocator()->get('ValidateUsers', [
            'className' => ValidateUsersTable::class,
        ]);
        $this->Form->create([], ['context' => ['table' => 'ValidateUsers']]);

        $result = $this->Form->control('ValidateUser.id');
        $expected = [
            'input' => ['name', 'type' => 'hidden', 'id'],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('ValidateUser.custom', ['type' => 'hidden']);
        $expected = [
            'input' => ['name', 'type' => 'hidden', 'id'],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testControlDatetime method
     *
     * Test form->control() with datetime.
     */
    public function testControlDatetime(): void
    {
        $result = $this->Form->control('prueba', [
            'type' => 'datetime',
            'value' => new FrozenTime('2019-09-27 02:52:43'),
        ]);
        $expected = [
            'div' => ['class' => 'input datetime'],
            'label' => ['for' => 'prueba'],
            'Prueba',
            '/label',
            'input' => [
                'name' => 'prueba',
                'id' => 'prueba',
                'type' => 'datetime-local',
                'value' => '2019-09-27T02:52:43',
                'step' => '1',
            ],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testControlDatetimeIdPrefix method
     *
     * Test form->control() with datetime with id prefix.
     */
    public function testControlDatetimeIdPrefix(): void
    {
        $this->Form->create(null, ['idPrefix' => 'prefix']);

        $result = $this->Form->control('prueba', [
            'type' => 'datetime',
        ]);
        $expected = [
            'div' => ['class' => 'input datetime'],
            'label' => ['for' => 'prefix-prueba'],
            'Prueba',
            '/label',
            'input' => [
                'name' => 'prueba',
                'id' => 'prefix-prueba',
                'type' => 'datetime-local',
                'value' => '',
                'step' => '1',
            ],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testControlDatetimeStep method
     *
     * Test form->control() with datetime with custom step size.
     */
    public function testControlDatetimeStep(): void
    {
        $result = $this->Form->control('prueba', [
            'type' => 'datetime',
            'value' => new FrozenTime('2019-09-27 02:52:43'),
            'step' => '0.5',
        ]);
        $expected = [
            'div' => ['class' => 'input datetime'],
            'label' => ['for' => 'prueba'],
            'Prueba',
            '/label',
            'input' => [
                'name' => 'prueba',
                'id' => 'prueba',
                'type' => 'datetime-local',
                'value' => '2019-09-27T02:52:43.000',
                'step' => '0.5',
            ],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testControlCheckboxWithDisabledElements method
     *
     * Test generating checkboxes with disabled elements.
     */
    public function testControlCheckboxWithDisabledElements(): void
    {
        $options = [1 => 'One', 2 => 'Two', '3' => 'Three'];
        $result = $this->Form->control('Contact.multiple', [
            'multiple' => 'checkbox',
            'disabled' => 'disabled',
            'options' => $options,
        ]);

        $expected = [
            ['div' => ['class' => 'input select']],
            ['label' => ['for' => 'contact-multiple']],
            'Multiple',
            '/label',
            ['input' => ['type' => 'hidden', 'name' => 'Contact[multiple]', 'disabled' => 'disabled', 'value' => '', 'id' => 'contact-multiple']],
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'contact-multiple-1']],
            ['input' => ['type' => 'checkbox', 'name' => 'Contact[multiple][]', 'value' => 1, 'disabled' => 'disabled', 'id' => 'contact-multiple-1']],
            'One',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'contact-multiple-2']],
            ['input' => ['type' => 'checkbox', 'name' => 'Contact[multiple][]', 'value' => 2, 'disabled' => 'disabled', 'id' => 'contact-multiple-2']],
            'Two',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'contact-multiple-3']],
            ['input' => ['type' => 'checkbox', 'name' => 'Contact[multiple][]', 'value' => 3, 'disabled' => 'disabled', 'id' => 'contact-multiple-3']],
            'Three',
            '/label',
            '/div',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        // make sure 50 does only disable 50, and not 50f5c0cf
        $options = ['50' => 'Fifty', '50f5c0cf' => 'Stringy'];
        $disabled = [50];

        $expected = [
            ['div' => ['class' => 'input select']],
            ['label' => ['for' => 'contact-multiple']],
            'Multiple',
            '/label',
            ['input' => ['type' => 'hidden', 'name' => 'Contact[multiple]', 'value' => '', 'id' => 'contact-multiple']],
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'contact-multiple-50']],
            ['input' => ['type' => 'checkbox', 'name' => 'Contact[multiple][]', 'value' => 50, 'disabled' => 'disabled', 'id' => 'contact-multiple-50']],
            'Fifty',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'contact-multiple-50f5c0cf']],
            ['input' => ['type' => 'checkbox', 'name' => 'Contact[multiple][]', 'value' => '50f5c0cf', 'id' => 'contact-multiple-50f5c0cf']],
            'Stringy',
            '/label',
            '/div',
            '/div',
        ];
        $result = $this->Form->control('Contact.multiple', ['multiple' => 'checkbox', 'disabled' => $disabled, 'options' => $options]);
        $this->assertHtml($expected, $result);
    }

    /**
     * testControlWithLeadingInteger method
     *
     * Test input name with leading integer, ensure attributes are generated correctly.
     */
    public function testControlWithLeadingInteger(): void
    {
        $result = $this->Form->text('0.Node.title');
        $expected = [
            'input' => ['name' => '0[Node][title]', 'type' => 'text'],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testControlSelectType method
     *
     * Test form->control() with select type inputs.
     */
    public function testControlSelectType(): void
    {
        $result = $this->Form->control(
            'email',
            [
            'options' => ['è' => 'Firést', 'é' => 'Secoènd'], 'empty' => true]
        );
        $expected = [
            'div' => ['class' => 'input select'],
            'label' => ['for' => 'email'],
            'Email',
            '/label',
            ['select' => ['name' => 'email', 'id' => 'email']],
            ['option' => ['value' => '']],
            '/option',
            ['option' => ['value' => 'è']],
            'Firést',
            '/option',
            ['option' => ['value' => 'é']],
            'Secoènd',
            '/option',
            '/select',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control(
            'email',
            [
            'options' => ['First', 'Second'], 'empty' => true]
        );
        $expected = [
            'div' => ['class' => 'input select'],
            'label' => ['for' => 'email'],
            'Email',
            '/label',
            ['select' => ['name' => 'email', 'id' => 'email']],
            ['option' => ['value' => '']],
            '/option',
            ['option' => ['value' => '0']],
            'First',
            '/option',
            ['option' => ['value' => '1']],
            'Second',
            '/option',
            '/select',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('email', [
            'type' => 'select',
            'options' => new ArrayObject(['First', 'Second']),
            'empty' => true,
        ]);
        $this->assertHtml($expected, $result);

        $this->View->set('users', ['value' => 'good', 'other' => 'bad']);
        $this->View->setRequest(
            $this->View->getRequest()->withData('Model', ['user_id' => 'value'])
        );
        $this->Form->create();
        $result = $this->Form->control('Model.user_id', ['empty' => true]);
        $expected = [
            'div' => ['class' => 'input select'],
            'label' => ['for' => 'model-user-id'],
            'User',
            '/label',
            'select' => ['name' => 'Model[user_id]', 'id' => 'model-user-id'],
            ['option' => ['value' => '']],
            '/option',
            ['option' => ['value' => 'value', 'selected' => 'selected']],
            'good',
            '/option',
            ['option' => ['value' => 'other']],
            'bad',
            '/option',
            '/select',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $this->View->set('users', ['value' => 'good', 'other' => 'bad']);
        $this->View->setRequest(
            $this->View->getRequest()->withData('Thing', ['user_id' => null])
        );
        $result = $this->Form->control('Thing.user_id', ['empty' => 'Some Empty']);
        $expected = [
            'div' => ['class' => 'input select'],
            'label' => ['for' => 'thing-user-id'],
            'User',
            '/label',
            'select' => ['name' => 'Thing[user_id]', 'id' => 'thing-user-id'],
            ['option' => ['value' => '']],
            'Some Empty',
            '/option',
            ['option' => ['value' => 'value']],
            'good',
            '/option',
            ['option' => ['value' => 'other']],
            'bad',
            '/option',
            '/select',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $this->View->set('users', ['value' => 'good', 'other' => 'bad']);
        $this->View->setRequest(
            $this->View->getRequest()->withData('Thing', ['user_id' => 'value'])
        );
        $this->Form->create();
        $result = $this->Form->control('Thing.user_id', ['empty' => 'Some Empty']);
        $expected = [
            'div' => ['class' => 'input select'],
            'label' => ['for' => 'thing-user-id'],
            'User',
            '/label',
            'select' => ['name' => 'Thing[user_id]', 'id' => 'thing-user-id'],
            ['option' => ['value' => '']],
            'Some Empty',
            '/option',
            ['option' => ['value' => 'value', 'selected' => 'selected']],
            'good',
            '/option',
            ['option' => ['value' => 'other']],
            'bad',
            '/option',
            '/select',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('Publisher.id', [
            'label' => 'Publisher',
            'type' => 'select',
            'multiple' => 'checkbox',
            'options' => ['Value 1' => 'Label 1', 'Value 2' => 'Label 2'],
        ]);
        $expected = [
            ['div' => ['class' => 'input select']],
                ['label' => ['for' => 'publisher-id']],
                'Publisher',
                '/label',
                'input' => ['type' => 'hidden', 'name' => 'Publisher[id]', 'value' => '', 'id' => 'publisher-id'],
                ['div' => ['class' => 'checkbox']],
                ['label' => ['for' => 'publisher-id-value-1']],
                ['input' => ['type' => 'checkbox', 'name' => 'Publisher[id][]', 'value' => 'Value 1', 'id' => 'publisher-id-value-1']],
                'Label 1',
                '/label',
                '/div',
                ['div' => ['class' => 'checkbox']],
                ['label' => ['for' => 'publisher-id-value-2']],
                ['input' => ['type' => 'checkbox', 'name' => 'Publisher[id][]', 'value' => 'Value 2', 'id' => 'publisher-id-value-2']],
                'Label 2',
                '/label',
                '/div',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testControlWithNonStandardPrimaryKeyMakesHidden method
     *
     * Test that control() and a non standard primary key makes a hidden input by default.
     */
    public function testControlWithNonStandardPrimaryKeyMakesHidden(): void
    {
        $this->article['schema']['_constraints']['primary']['columns'] = ['title'];
        $this->Form->create($this->article);
        $result = $this->Form->control('title');
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'title', 'id' => 'title'],
        ];
        $this->assertHtml($expected, $result);

        $this->article['schema']['_constraints']['primary']['columns'] = ['title', 'body'];
        $this->Form->create($this->article);
        $result = $this->Form->control('title');
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'title', 'id' => 'title'],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('body');
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'body', 'id' => 'body'],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testControlOverridingMagicSelectType method
     *
     * Test that overriding the magic select type widget is possible.
     */
    public function testControlOverridingMagicSelectType(): void
    {
        $this->View->set('users', ['value' => 'good', 'other' => 'bad']);
        $result = $this->Form->control('Model.user_id', ['type' => 'text']);
        $expected = [
            'div' => ['class' => 'input text'],
            'label' => ['for' => 'model-user-id'], 'User', '/label',
            'input' => ['name' => 'Model[user_id]', 'type' => 'text', 'id' => 'model-user-id'],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        //Check that magic types still work for plural/singular vars
        $this->View->set('types', ['value' => 'good', 'other' => 'bad']);
        $result = $this->Form->control('Model.type');
        $expected = [
            'div' => ['class' => 'input select'],
            'label' => ['for' => 'model-type'], 'Type', '/label',
            'select' => ['name' => 'Model[type]', 'id' => 'model-type'],
            ['option' => ['value' => 'value']], 'good', '/option',
            ['option' => ['value' => 'other']], 'bad', '/option',
            '/select',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testControlMagicTypeDoesNotOverride method
     *
     * Test that inferred types do not override developer input.
     */
    public function testControlMagicTypeDoesNotOverride(): void
    {
        $this->View->set('users', ['value' => 'good', 'other' => 'bad']);
        $result = $this->Form->control('Model.user', ['type' => 'checkbox']);
        $expected = [
            'div' => ['class' => 'input checkbox'],
            ['input' => [
                'type' => 'hidden',
                'name' => 'Model[user]',
                'value' => 0,
            ]],
            'label' => ['for' => 'model-user'],
            ['input' => [
                'name' => 'Model[user]',
                'type' => 'checkbox',
                'id' => 'model-user',
                'value' => 1,
            ]],
            'User',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        // make sure that for HABTM the multiple option is not being overwritten in case it's truly
        $options = [
            1 => 'blue',
            2 => 'red',
        ];
        $result = $this->Form->control('tags._ids', ['options' => $options, 'multiple' => 'checkbox']);
        $expected = [
            'div' => ['class' => 'input select'],
            'label' => ['for' => 'tags-ids'],
            'Tags',
            '/label',
            'input' => ['type' => 'hidden', 'name' => 'tags[_ids]', 'value' => '', 'id' => 'tags-ids'],

            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'tags-ids-1']],
            ['input' => [
                'id' => 'tags-ids-1', 'type' => 'checkbox',
                'value' => '1', 'name' => 'tags[_ids][]',
            ]],
            'blue',
            '/label',
            '/div',

            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'tags-ids-2']],
            ['input' => [
                'id' => 'tags-ids-2', 'type' => 'checkbox',
                'value' => '2', 'name' => 'tags[_ids][]',
            ]],
            'red',
            '/label',
            '/div',

            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testControlMagicSelectForTypeNumber method
     *
     * Test that magic control() selects are created for type=number.
     */
    public function testControlMagicSelectForTypeNumber(): void
    {
        $this->getTableLocator()->get('ValidateUsers', [
            'className' => ValidateUsersTable::class,
        ]);
        $entity = new Entity(['balance' => 1]);
        $this->Form->create($entity, ['context' => ['table' => 'ValidateUsers']]);
        $this->View->set('balances', [0 => 'nothing', 1 => 'some', 100 => 'a lot']);
        $result = $this->Form->control('balance');
        $expected = [
            'div' => ['class' => 'input select'],
            'label' => ['for' => 'balance'],
            'Balance',
            '/label',
            'select' => ['name' => 'balance', 'id' => 'balance'],
            ['option' => ['value' => '0']],
            'nothing',
            '/option',
            ['option' => ['value' => '1', 'selected' => 'selected']],
            'some',
            '/option',
            ['option' => ['value' => '100']],
            'a lot',
            '/option',
            '/select',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testInvalidControlTypeOption method
     *
     * Test invalid 'input' type option to control() function.
     */
    public function testInvalidControlTypeOption(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid type \'input\' used for field \'text\'');
        $this->Form->control('text', ['type' => 'input']);
    }

    /**
     * testControlMagicSelectChangeToRadio method
     *
     * Test that magic control() selects can easily be converted into radio types without error.
     */
    public function testControlMagicSelectChangeToRadio(): void
    {
        $this->View->set('users', ['value' => 'good', 'other' => 'bad']);
        $result = $this->Form->control('Model.user_id', ['type' => 'radio']);
        $this->assertStringContainsString('input type="radio"', $result);
    }

    /**
     * testFormControlSubmit method
     *
     * Test correct results for form::control() and type submit.
     */
    public function testFormControlSubmit(): void
    {
        $result = $this->Form->control('Test Submit', ['type' => 'submit', 'class' => 'foobar']);
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'submit', 'class' => 'foobar', 'id' => 'test-submit', 'value' => 'Test Submit'],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormControls method
     *
     * Test correct results from Form::controls().
     */
    public function testFormControlsLegendFieldset(): void
    {
        $this->Form->create($this->article);
        $result = $this->Form->allControls([], ['legend' => 'The Legend']);
        $expected = [
            '<fieldset',
            '<legend',
            'The Legend',
            '/legend',
            '*/fieldset',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->allControls([], ['fieldset' => true, 'legend' => 'Field of Dreams']);
        $this->assertStringContainsString('<legend>Field of Dreams</legend>', $result);
        $this->assertStringContainsString('<fieldset>', $result);

        $result = $this->Form->allControls([], ['fieldset' => false, 'legend' => false]);
        $this->assertStringNotContainsString('<legend>', $result);
        $this->assertStringNotContainsString('<fieldset>', $result);

        $result = $this->Form->allControls([], ['fieldset' => false, 'legend' => 'Hello']);
        $this->assertStringNotContainsString('<legend>', $result);
        $this->assertStringNotContainsString('<fieldset>', $result);

        $this->Form->create($this->article);
        $this->View->setRequest($this->View->getRequest()
            ->withParam('prefix', 'admin')
            ->withParam('action', 'admin_edit')
            ->withParam('controller', 'articles'));
        $result = $this->Form->allControls();
        $expected = [
            '<fieldset',
            '<legend',
            'New Article',
            '/legend',
            '*/fieldset',
        ];
        $this->assertHtml($expected, $result);

        $this->Form->create($this->article);
        $result = $this->Form->allControls([], ['fieldset' => [], 'legend' => 'The Legend']);
        $expected = [
            '<fieldset',
            '<legend',
            'The Legend',
            '/legend',
            '*/fieldset',
        ];
        $this->assertHtml($expected, $result);

        $this->Form->create($this->article);
        $result = $this->Form->allControls([], [
            'fieldset' => [
                'class' => 'some-class some-other-class',
                'disabled' => true,
                'data-param' => 'a-param',
            ],
            'legend' => 'The Legend',
        ]);
        $expected = [
            '<fieldset class="some-class some-other-class" disabled="disabled" data-param="a-param"',
            '<legend',
            'The Legend',
            '/legend',
            '*/fieldset',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormControls method
     *
     * Test the controls() method.
     */
    public function testFormControls(): void
    {
        $this->Form->create($this->article);
        $result = $this->Form->allControls();
        $expected = [
            '<fieldset',
            '<legend', 'New Article', '/legend',
            'input' => ['type' => 'hidden', 'name' => 'id', 'id' => 'id'],
            ['div' => ['class' => 'input select required']],
            '*/div',
            ['div' => ['class' => 'input text required']],
            '*/div',
            ['div' => ['class' => 'input text']],
            '*/div',
            ['div' => ['class' => 'input text']],
            '*/div',
            '/fieldset',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->allControls([
            'published' => ['type' => 'boolean'],
        ]);
        $expected = [
            '<fieldset',
            '<legend', 'New Article', '/legend',
            'input' => ['type' => 'hidden', 'name' => 'id', 'id' => 'id'],
            ['div' => ['class' => 'input select required']],
            '*/div',
            ['div' => ['class' => 'input text required']],
            '*/div',
            ['div' => ['class' => 'input text']],
            '*/div',
            ['div' => ['class' => 'input boolean']],
            '*/div',
            '/fieldset',
        ];
        $this->assertHtml($expected, $result);

        $this->Form->create($this->article);
        $result = $this->Form->allControls([], ['legend' => 'Hello']);
        $expected = [
            'fieldset' => [],
            'legend' => [],
            'Hello',
            '/legend',
            'input' => ['type' => 'hidden', 'name' => 'id', 'id' => 'id'],
            ['div' => ['class' => 'input select required']],
            '*/div',
            ['div' => ['class' => 'input text required']],
            '*/div',
            ['div' => ['class' => 'input text']],
            '*/div',
            ['div' => ['class' => 'input text']],
            '*/div',
            '/fieldset',
        ];
        $this->assertHtml($expected, $result);

        $this->Form->create();
        $expected = [
            'fieldset' => [],
            ['div' => ['class' => 'input text']],
            'label' => ['for' => 'foo'],
            'Foo',
            '/label',
            'input' => ['type' => 'text', 'name' => 'foo', 'id' => 'foo'],
            '*/div',
            '/fieldset',
        ];
        $result = $this->Form->allControls(
            ['foo' => ['type' => 'text']],
            ['legend' => false]
        );
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormControlsBlacklist method
     */
    public function testFormControlsBlacklist(): void
    {
        $this->Form->create($this->article);
        $result = $this->Form->allControls([
            'id' => false,
        ]);
        $expected = [
            '<fieldset',
            '<legend', 'New Article', '/legend',
            ['div' => ['class' => 'input select required']],
            '*/div',
            ['div' => ['class' => 'input text required']],
            '*/div',
            ['div' => ['class' => 'input text']],
            '*/div',
            ['div' => ['class' => 'input text']],
            '*/div',
            '/fieldset',
        ];
        $this->assertHtml($expected, $result);

        $this->Form->create($this->article);
        $result = $this->Form->allControls([
            'id' => [],
        ]);
        $expected = [
            '<fieldset',
            '<legend', 'New Article', '/legend',
            'input' => ['type' => 'hidden', 'name' => 'id', 'id' => 'id'],
            ['div' => ['class' => 'input select required']],
            '*/div',
            ['div' => ['class' => 'input text required']],
            '*/div',
            ['div' => ['class' => 'input text']],
            '*/div',
            ['div' => ['class' => 'input text']],
            '*/div',
            '/fieldset',
        ];
        $this->assertHtml($expected, $result, true);
    }

    /**
     * testSelectAsCheckbox method
     *
     * Test multi-select widget with checkbox formatting.
     */
    public function testSelectAsCheckbox(): void
    {
        $result = $this->Form->select(
            'Model.multi_field',
            ['first', 'second', 'third'],
            ['multiple' => 'checkbox', 'value' => [0, 1]]
        );
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => '', 'id' => 'model-multi-field'],
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-0', 'class' => 'selected']],
            ['input' => ['type' => 'checkbox', 'name' => 'Model[multi_field][]', 'checked' => 'checked', 'value' => '0', 'id' => 'model-multi-field-0']],
            'first',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-1', 'class' => 'selected']],
            ['input' => ['type' => 'checkbox', 'name' => 'Model[multi_field][]', 'checked' => 'checked', 'value' => '1', 'id' => 'model-multi-field-1']],
            'second',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-2']],
            ['input' => ['type' => 'checkbox', 'name' => 'Model[multi_field][]', 'value' => '2', 'id' => 'model-multi-field-2']],
            'third',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->select(
            'Model.multi_field',
            ['1/2' => 'half'],
            ['multiple' => 'checkbox']
        );
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => '', 'id' => 'model-multi-field'],
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-1-2']],
            ['input' => ['type' => 'checkbox', 'name' => 'Model[multi_field][]', 'value' => '1/2', 'id' => 'model-multi-field-1-2']],
            'half',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testLabel method
     *
     * Test label generation.
     */
    public function testLabel(): void
    {
        $result = $this->Form->label('Person.name');
        $expected = ['label' => ['for' => 'person-name'], 'Name', '/label'];
        $this->assertHtml($expected, $result);

        $result = $this->Form->label('Person.name');
        $expected = ['label' => ['for' => 'person-name'], 'Name', '/label'];
        $this->assertHtml($expected, $result);

        $result = $this->Form->label('Person.first_name');
        $expected = ['label' => ['for' => 'person-first-name'], 'First Name', '/label'];
        $this->assertHtml($expected, $result);

        $result = $this->Form->label('Person.first_name', 'Your first name');
        $expected = ['label' => ['for' => 'person-first-name'], 'Your first name', '/label'];
        $this->assertHtml($expected, $result);

        $result = $this->Form->label('Person.first_name', 'Your first name', ['class' => 'my-class']);
        $expected = ['label' => ['for' => 'person-first-name', 'class' => 'my-class'], 'Your first name', '/label'];
        $this->assertHtml($expected, $result);

        $result = $this->Form->label('Person.first_name', 'Your first name', ['class' => 'my-class', 'id' => 'LabelID']);
        $expected = ['label' => ['for' => 'person-first-name', 'class' => 'my-class', 'id' => 'LabelID'], 'Your first name', '/label'];
        $this->assertHtml($expected, $result);

        $result = $this->Form->label('Person.first_name', '');
        $expected = ['label' => ['for' => 'person-first-name'], '/label'];
        $this->assertHtml($expected, $result);

        $result = $this->Form->label('Person.2.name', '');
        $expected = ['label' => ['for' => 'person-2-name'], '/label'];
        $this->assertHtml($expected, $result);
    }

    /**
     * testLabelContainControl method
     *
     * Test that label() can accept an input with the correct template vars.
     */
    public function testLabelContainControl(): void
    {
        $this->Form->setTemplates([
            'label' => '<label{{attrs}}>{{input}}{{text}}</label>',
        ]);
        $result = $this->Form->label('Person.accept_terms', 'Accept', [
            'input' => '<input type="checkbox" name="accept_tos"/>',
        ]);
        $expected = [
            'label' => ['for' => 'person-accept-terms'],
            'input' => ['type' => 'checkbox', 'name' => 'accept_tos'],
            'Accept',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testTextbox method
     *
     * Test textbox element generation.
     */
    public function testTextbox(): void
    {
        $result = $this->Form->text('Model.field');
        $expected = ['input' => ['type' => 'text', 'name' => 'Model[field]']];
        $this->assertHtml($expected, $result);

        $result = $this->Form->text('Model.field', ['type' => 'password']);
        $expected = ['input' => ['type' => 'password', 'name' => 'Model[field]']];
        $this->assertHtml($expected, $result);

        $result = $this->Form->text('Model.field', ['id' => 'theID']);
        $expected = ['input' => ['type' => 'text', 'name' => 'Model[field]', 'id' => 'theID']];
        $this->assertHtml($expected, $result);
    }

    /**
     * testTextBoxDataAndError method
     *
     * Test that text() hooks up with request data and error fields.
     */
    public function testTextBoxDataAndError(): void
    {
        $this->article['errors'] = [
            'Contact' => ['text' => 'wrong'],
        ];
        $this->View->setRequest($this->View->getRequest()
            ->withData('Model.text', 'test <strong>HTML</strong> values')
            ->withData('Contact.text', 'test'));
        $this->Form->create($this->article);

        $result = $this->Form->text('Model.text');
        $expected = [
            'input' => [
                'type' => 'text',
                'name' => 'Model[text]',
                'value' => 'test &lt;strong&gt;HTML&lt;/strong&gt; values',
            ],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->text('Contact.text', ['id' => 'theID']);
        $expected = [
            'input' => [
                'type' => 'text',
                'name' => 'Contact[text]',
                'value' => 'test',
                'id' => 'theID',
                'class' => 'form-error',
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testDefaultValue method
     *
     * Test default value setting.
     */
    public function testTextDefaultValue(): void
    {
        $this->View->setRequest($this->View->getRequest()->withData('Model.field', 'test'));
        $result = $this->Form->text('Model.field', ['default' => 'default value']);
        $expected = ['input' => ['type' => 'text', 'name' => 'Model[field]', 'value' => 'test']];
        $this->assertHtml($expected, $result);

        $this->View->setRequest($this->View->getRequest()->withParsedBody([]));
        $this->Form->create();
        $result = $this->Form->text('Model.field', ['default' => 'default value']);
        $expected = ['input' => ['type' => 'text', 'name' => 'Model[field]', 'value' => 'default value']];
        $this->assertHtml($expected, $result);

        $Articles = $this->getTableLocator()->get('Articles');
        $title = $Articles->getSchema()->getColumn('title');
        $Articles->getSchema()->addColumn(
            'title',
            ['default' => 'default title', 'length' => 255] + $title
        );

        $entity = $Articles->newEmptyEntity();
        $this->Form->create($entity);

        // Get default value from schema
        $result = $this->Form->text('title');
        $expected = ['input' => ['type' => 'text', 'name' => 'title', 'value' => 'default title', 'maxlength' => '255']];
        $this->assertHtml($expected, $result);

        // Don't get value from schema
        $result = $this->Form->text('title', ['schemaDefault' => false]);
        $expected = ['input' => ['type' => 'text', 'name' => 'title', 'maxlength' => '255']];
        $this->assertHtml($expected, $result);

        // Custom default value overrides default value from schema
        $result = $this->Form->text('title', ['default' => 'override default']);
        $expected = ['input' => ['type' => 'text', 'name' => 'title', 'value' => 'override default', 'maxlength' => '255']];
        $this->assertHtml($expected, $result);

        // Default value from schema is used only for new entities.
        $entity->setNew(false);
        $result = $this->Form->text('title');
        $expected = ['input' => ['type' => 'text', 'name' => 'title', 'maxlength' => '255']];
        $this->assertHtml($expected, $result);
    }

    /**
     * testError method
     *
     * Test field error generation.
     */
    public function testError(): void
    {
        $this->article['errors'] = [
            'Article' => ['field' => 'email'],
        ];
        $this->Form->create($this->article);

        $result = $this->Form->error('Article.field');
        $expected = [
            ['div' => ['class' => 'error-message', 'id' => 'article-field-error']],
            'email',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->error('Article.field', '<strong>Badness!</strong>');
        $expected = [
            ['div' => ['class' => 'error-message', 'id' => 'article-field-error']],
            '&lt;strong&gt;Badness!&lt;/strong&gt;',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->error('Article.field', '<strong>Badness!</strong>', ['escape' => false]);
        $expected = [
            ['div' => ['class' => 'error-message', 'id' => 'article-field-error']],
            '<strong', 'Badness!', '/strong',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testErrorRuleName method
     *
     * Test error translation can use rule names for translating.
     */
    public function testErrorRuleName(): void
    {
        $this->article['errors'] = [
            'Article' => [
                'field' => ['email' => 'Your email was not good'],
            ],
        ];
        $this->Form->create($this->article);

        $result = $this->Form->error('Article.field');
        $expected = [
            ['div' => ['class' => 'error-message', 'id' => 'article-field-error']],
            'Your email was not good',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->error('Article.field', ['email' => 'Email in use']);
        $expected = [
            ['div' => ['class' => 'error-message', 'id' => 'article-field-error']],
            'Email in use',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->error('Article.field', ['Your email was not good' => 'Email in use']);
        $expected = [
            ['div' => ['class' => 'error-message', 'id' => 'article-field-error']],
            'Email in use',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->error('Article.field', [
            'email' => 'Key is preferred',
            'Your email was not good' => 'Email in use',
        ]);
        $expected = [
            ['div' => ['class' => 'error-message', 'id' => 'article-field-error']],
            'Key is preferred',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testErrorMessages method
     *
     * Test error with nested lists.
     */
    public function testErrorMessages(): void
    {
        $this->article['errors'] = [
            'Article' => ['field' => 'email'],
        ];
        $this->Form->create($this->article);

        $result = $this->Form->error('Article.field', [
            'email' => 'No good!',
        ]);
        $expected = [
            'div' => ['class' => 'error-message', 'id' => 'article-field-error'],
            'No good!',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testErrorMultipleMessages method
     *
     * Test error() with multiple messages.
     */
    public function testErrorMultipleMessages(): void
    {
        $this->article['errors'] = [
            'field' => ['notBlank', 'email', 'Something else'],
        ];
        $this->Form->create($this->article);

        $result = $this->Form->error('field', [
            'notBlank' => 'Cannot be empty',
            'email' => 'No good!',
        ]);
        $expected = [
            'div' => ['class' => 'error-message', 'id' => 'field-error'],
            'ul' => [],
            '<li', 'Cannot be empty', '/li',
            '<li', 'No good!', '/li',
            '<li', 'Something else', '/li',
            '/ul',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testPassword method
     *
     * Test password element generation.
     */
    public function testPassword(): void
    {
        $this->article['errors'] = [
            'Contact' => [
                'passwd' => 1,
            ],
        ];
        $this->Form->create($this->article);

        $result = $this->Form->password('Contact.field');
        $expected = ['input' => ['type' => 'password', 'name' => 'Contact[field]']];
        $this->assertHtml($expected, $result);

        $this->View->setRequest($this->View->getRequest()->withData('Contact.passwd', 'test'));
        $this->Form->create($this->article);
        $result = $this->Form->password('Contact.passwd', ['id' => 'theID']);
        $expected = ['input' => ['type' => 'password', 'name' => 'Contact[passwd]', 'value' => 'test', 'id' => 'theID', 'class' => 'form-error']];
        $this->assertHtml($expected, $result);
    }

    /**
     * testRadio method
     *
     * Test radio element set generation.
     */
    public function testRadio(): void
    {
        $result = $this->Form->radio('Model.field', ['option A']);
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'Model[field]', 'value' => '', 'id' => 'model-field'],
            'label' => ['for' => 'model-field-0'],
            ['input' => ['type' => 'radio', 'name' => 'Model[field]', 'value' => '0', 'id' => 'model-field-0']],
            'option A',
            '/label',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->radio('Model.field', new Collection(['option A']));
        $this->assertHtml($expected, $result);

        $result = $this->Form->radio('Model.field', ['option A', 'option B']);
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'Model[field]', 'value' => '', 'id' => 'model-field'],
            ['label' => ['for' => 'model-field-0']],
            ['input' => ['type' => 'radio', 'name' => 'Model[field]', 'value' => '0', 'id' => 'model-field-0']],
            'option A',
            '/label',
            ['label' => ['for' => 'model-field-1']],
            ['input' => ['type' => 'radio', 'name' => 'Model[field]', 'value' => '1', 'id' => 'model-field-1']],
            'option B',
            '/label',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->radio(
            'Employee.gender',
            ['male' => 'Male', 'female' => 'Female'],
            ['form' => 'my-form']
        );
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'Employee[gender]', 'value' => '', 'form' => 'my-form', 'id' => 'employee-gender'],
            ['label' => ['for' => 'employee-gender-male']],
            ['input' => ['type' => 'radio', 'name' => 'Employee[gender]', 'value' => 'male', 'id' => 'employee-gender-male', 'form' => 'my-form']],
            'Male',
            '/label',
            ['label' => ['for' => 'employee-gender-female']],
            ['input' => ['type' => 'radio', 'name' => 'Employee[gender]', 'value' => 'female', 'id' => 'employee-gender-female', 'form' => 'my-form']],
            'Female',
            '/label',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->radio('Model.field', ['option A', 'option B'], ['name' => 'Model[custom]']);
        $expected = [
            ['input' => ['type' => 'hidden', 'name' => 'Model[custom]', 'value' => '', 'id' => 'model-field']],
            ['label' => ['for' => 'model-custom-0']],
            ['input' => ['type' => 'radio', 'name' => 'Model[custom]', 'value' => '0', 'id' => 'model-custom-0']],
            'option A',
            '/label',
            ['label' => ['for' => 'model-custom-1']],
            ['input' => ['type' => 'radio', 'name' => 'Model[custom]', 'value' => '1', 'id' => 'model-custom-1']],
            'option B',
            '/label',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->radio(
            'Employee.gender',
            [
                ['value' => 'male', 'text' => 'Male', 'style' => 'width:20px'],
                ['value' => 'female', 'text' => 'Female', 'style' => 'width:20px'],
            ]
        );
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'Employee[gender]', 'value' => '', 'id' => 'employee-gender'],
            ['label' => ['for' => 'employee-gender-male']],
            ['input' => ['type' => 'radio', 'name' => 'Employee[gender]', 'value' => 'male',
                'id' => 'employee-gender-male', 'style' => 'width:20px']],
            'Male',
            '/label',
            ['label' => ['for' => 'employee-gender-female']],
            ['input' => ['type' => 'radio', 'name' => 'Employee[gender]', 'value' => 'female',
                'id' => 'employee-gender-female', 'style' => 'width:20px']],
            'Female',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test radio with complex options and empty disabled data.
     */
    public function testRadioComplexDisabled(): void
    {
        $options = [
            ['value' => 'r', 'text' => 'red'],
            ['value' => 'b', 'text' => 'blue'],
        ];
        $attrs = ['disabled' => []];
        $result = $this->Form->radio('Model.field', $options, $attrs);
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'Model[field]', 'value' => '', 'id' => 'model-field'],
            ['label' => ['for' => 'model-field-r']],
            ['input' => ['type' => 'radio', 'name' => 'Model[field]', 'value' => 'r', 'id' => 'model-field-r']],
            'red',
            '/label',
            ['label' => ['for' => 'model-field-b']],
            ['input' => ['type' => 'radio', 'name' => 'Model[field]', 'value' => 'b', 'id' => 'model-field-b']],
            'blue',
            '/label',
        ];
        $this->assertHtml($expected, $result);

        $attrs = ['disabled' => ['r']];
        $result = $this->Form->radio('Model.field', $options, $attrs);
        $this->assertStringContainsString('disabled="disabled"', $result);
    }

    /**
     * testRadioDefaultValue method
     *
     * Test default value setting on radio() method.
     */
    public function testRadioDefaultValue(): void
    {
        $Articles = $this->getTableLocator()->get('Articles');
        $title = $Articles->getSchema()->getColumn('title');
        $Articles->getSchema()->addColumn(
            'title',
            ['default' => '1'] + $title
        );

        $this->Form->create($Articles->newEmptyEntity());

        $result = $this->Form->radio('title', ['option A', 'option B']);
        $expected = [
            ['input' => ['type' => 'hidden', 'name' => 'title', 'value' => '', 'id' => 'title']],
            ['label' => ['for' => 'title-0']],
            ['input' => ['type' => 'radio', 'name' => 'title', 'value' => '0', 'id' => 'title-0']],
            'option A',
            '/label',
            ['label' => ['for' => 'title-1']],
            ['input' => ['type' => 'radio', 'name' => 'title', 'value' => '1', 'id' => 'title-1', 'checked' => 'checked']],
            'option B',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test setting a hiddenField value on radio buttons.
     */
    public function testRadioHiddenFieldValue(): void
    {
        $result = $this->Form->radio('title', ['option A'], ['hiddenField' => 'N']);
        $expected = [
            ['input' => ['type' => 'hidden', 'name' => 'title', 'value' => 'N', 'id' => 'title']],
            'label' => ['for' => 'title-0'],
            ['input' => ['type' => 'radio', 'name' => 'title', 'value' => '0', 'id' => 'title-0']],
            'option A',
            '/label',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->radio('title', ['option A'], ['hiddenField' => '']);
        $expected = [
            ['input' => ['type' => 'hidden', 'name' => 'title', 'value' => '', 'id' => 'title']],
            'label' => ['for' => 'title-0'],
            ['input' => ['type' => 'radio', 'name' => 'title', 'value' => '0', 'id' => 'title-0']],
            'option A',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testControlRadio method
     *
     * Test that input works with radio types.
     */
    public function testControlRadio(): void
    {
        $result = $this->Form->control('test', [
            'type' => 'radio',
            'options' => ['A', 'B'],
        ]);
        $expected = [
            ['div' => ['class' => 'input radio']],
                '<label',
                'Test',
                '/label',
                ['input' => ['type' => 'hidden', 'name' => 'test', 'value' => '', 'id' => 'test']],
                ['label' => ['for' => 'test-0']],
                    ['input' => ['type' => 'radio', 'name' => 'test', 'value' => '0', 'id' => 'test-0']],
                    'A',
                '/label',
                ['label' => ['for' => 'test-1']],
                    ['input' => ['type' => 'radio', 'name' => 'test', 'value' => '1', 'id' => 'test-1']],
                    'B',
                '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('test', [
            'type' => 'radio',
            'options' => ['A', 'B'],
            'value' => '0',
        ]);
        $expected = [
            ['div' => ['class' => 'input radio']],
                '<label',
                'Test',
                '/label',
                ['input' => ['type' => 'hidden', 'name' => 'test', 'value' => '', 'id' => 'test']],
                ['label' => ['for' => 'test-0']],
                    ['input' => ['type' => 'radio', 'checked' => 'checked', 'name' => 'test', 'value' => '0', 'id' => 'test-0']],
                    'A',
                '/label',
                ['label' => ['for' => 'test-1']],
                    ['input' => ['type' => 'radio', 'name' => 'test', 'value' => '1', 'id' => 'test-1']],
                    'B',
                '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('test', [
            'type' => 'radio',
            'options' => ['A', 'B'],
            'label' => false,
        ]);
        $expected = [
            ['div' => ['class' => 'input radio']],
                ['input' => ['type' => 'hidden', 'name' => 'test', 'value' => '', 'id' => 'test']],
                ['label' => ['for' => 'test-0']],
                    ['input' => ['type' => 'radio', 'name' => 'test', 'value' => '0', 'id' => 'test-0']],
                    'A',
                '/label',
                ['label' => ['for' => 'test-1']],
                    ['input' => ['type' => 'radio', 'name' => 'test', 'value' => '1', 'id' => 'test-1']],
                    'B',
                '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('accept', [
            'type' => 'radio',
            'options' => [
                1 => 'positive',
                -1 => 'negative',
            ],
            'label' => false,
        ]);
        $expected = [
            ['div' => ['class' => 'input radio']],
                ['input' => ['type' => 'hidden', 'name' => 'accept', 'value' => '', 'id' => 'accept']],
                ['label' => ['for' => 'accept-1']],
                ['input' => [
                    'type' => 'radio',
                    'name' => 'accept',
                    'value' => '1',
                    'id' => 'accept-1',
                ]],
                'positive',
                '/label',
                ['label' => ['for' => 'accept--1']],
                ['input' => [
                    'type' => 'radio',
                    'name' => 'accept',
                    'value' => '-1',
                    'id' => 'accept--1',
                ]],
                'negative',
                '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testRadioNoLabel method
     *
     * Test that radio() works with label = false.
     */
    public function testRadioNoLabel(): void
    {
        $result = $this->Form->radio('Model.field', ['A', 'B'], ['label' => false]);
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'Model[field]', 'value' => '', 'id' => 'model-field'],
            ['input' => ['type' => 'radio', 'name' => 'Model[field]', 'value' => '0', 'id' => 'model-field-0']],
            ['input' => ['type' => 'radio', 'name' => 'Model[field]', 'value' => '1', 'id' => 'model-field-1']],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testRadioControlInsideLabel method
     *
     * Test generating radio input inside label ala twitter bootstrap.
     */
    public function testRadioControlInsideLabel(): void
    {
        $this->Form->setTemplates([
            'label' => '<label{{attrs}}>{{input}}{{text}}</label>',
            'radioWrapper' => '{{label}}',
        ]);

        $result = $this->Form->radio('Model.field', ['option A', 'option B']);
        // phpcs:disable
        $expected = [
            ['input' => [
                'type' => 'hidden',
                'name' => 'Model[field]',
                'value' => '',
                'id' => 'model-field'
            ]],
            ['label' => ['for' => 'model-field-0']],
                ['input' => [
                    'type' => 'radio',
                    'name' => 'Model[field]',
                    'value' => '0',
                    'id' => 'model-field-0'
                ]],
                'option A',
            '/label',
            ['label' => ['for' => 'model-field-1']],
                ['input' => [
                    'type' => 'radio',
                    'name' => 'Model[field]',
                    'value' => '1',
                    'id' => 'model-field-1'
                ]],
                'option B',
            '/label',
        ];
        // phpcs:enable
        $this->assertHtml($expected, $result);
    }

    /**
     * testRadioHiddenControlDisabling method
     *
     * Test disabling the hidden input for radio buttons.
     */
    public function testRadioHiddenControlDisabling(): void
    {
        $result = $this->Form->radio('Model.1.field', ['option A'], ['hiddenField' => false]);
        $expected = [
            'label' => ['for' => 'model-1-field-0'],
            'input' => ['type' => 'radio', 'name' => 'Model[1][field]', 'value' => '0', 'id' => 'model-1-field-0'],
            'option A',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testRadioOutOfRange method
     *
     * Test radio element set generation.
     */
    public function testRadioOutOfRange(): void
    {
        $result = $this->Form->radio('Model.field', ['v' => 'value'], ['value' => 'nope']);
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'Model[field]', 'value' => '', 'id' => 'model-field'],
            'label' => ['for' => 'model-field-v'],
            ['input' => ['type' => 'radio', 'name' => 'Model[field]', 'value' => 'v', 'id' => 'model-field-v']],
            'value',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testSelect method
     *
     * Test select element generation.
     */
    public function testSelect(): void
    {
        $result = $this->Form->select('Model.field', []);
        $expected = [
            'select' => ['name' => 'Model[field]'],
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $this->View->setRequest($this->View->getRequest()->withData('Model', ['field' => 'value']));
        $this->Form->create();
        $result = $this->Form->select('Model.field', ['value' => 'good', 'other' => 'bad']);
        $expected = [
            'select' => ['name' => 'Model[field]'],
            ['option' => ['value' => 'value', 'selected' => 'selected']],
            'good',
            '/option',
            ['option' => ['value' => 'other']],
            'bad',
            '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->select('Model.field', new Collection(['value' => 'good', 'other' => 'bad']));
        $this->assertHtml($expected, $result);

        $this->View->setRequest($this->View->getRequest()->withParsedBody([]));
        $this->Form->create();
        $result = $this->Form->select('Model.field', ['value' => 'good', 'other' => 'bad']);
        $expected = [
            'select' => ['name' => 'Model[field]'],
            ['option' => ['value' => 'value']],
            'good',
            '/option',
            ['option' => ['value' => 'other']],
            'bad',
            '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $options = [
            ['value' => 'first', 'text' => 'First'],
            ['value' => 'first', 'text' => 'Another First'],
        ];
        $result = $this->Form->select(
            'Model.field',
            $options,
            ['escape' => false, 'empty' => false]
        );
        $expected = [
            'select' => ['name' => 'Model[field]'],
            ['option' => ['value' => 'first']],
            'First',
            '/option',
            ['option' => ['value' => 'first']],
            'Another First',
            '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $this->View->setRequest($this->View->getRequest()->withParsedBody(['Model' => ['contact_id' => 228]]));
        $this->Form->create();
        $result = $this->Form->select(
            'Model.contact_id',
            ['228' => '228 value', '228-1' => '228-1 value', '228-2' => '228-2 value'],
            ['escape' => false, 'empty' => 'pick something']
        );

        $expected = [
            'select' => ['name' => 'Model[contact_id]'],
            ['option' => ['value' => '']], 'pick something', '/option',
            ['option' => ['value' => '228', 'selected' => 'selected']], '228 value', '/option',
            ['option' => ['value' => '228-1']], '228-1 value', '/option',
            ['option' => ['value' => '228-2']], '228-2 value', '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $this->View->setRequest($this->View->getRequest()->withData('Model.field', 0));
        $this->Form->create();
        $result = $this->Form->select('Model.field', ['0' => 'No', '1' => 'Yes']);
        $expected = [
            'select' => ['name' => 'Model[field]'],
            ['option' => ['value' => '0', 'selected' => 'selected']], 'No', '/option',
            ['option' => ['value' => '1']], 'Yes', '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testSelectEscapeHtml method
     *
     * Test that select() escapes HTML.
     */
    public function testSelectEscapeHtml(): void
    {
        $result = $this->Form->select(
            'Model.field',
            ['first' => 'first "html" <chars>', 'second' => 'value'],
            ['empty' => false]
        );
        $expected = [
            'select' => ['name' => 'Model[field]'],
            ['option' => ['value' => 'first']],
            'first &quot;html&quot; &lt;chars&gt;',
            '/option',
            ['option' => ['value' => 'second']],
            'value',
            '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->select(
            'Model.field',
            ['first' => 'first "html" <chars>', 'second' => 'value'],
            ['escape' => false, 'empty' => false]
        );
        $expected = [
            'select' => ['name' => 'Model[field]'],
            ['option' => ['value' => 'first']],
            'first "html" <chars>',
            '/option',
            ['option' => ['value' => 'second']],
            'value',
            '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testSelectRequired method
     *
     * Test select() with required and disabled attributes.
     */
    public function testSelectRequired(): void
    {
        $this->article['required'] = [
            'user_id' => true,
        ];
        $this->Form->create($this->article);
        $result = $this->Form->select('user_id', ['option A']);
        $expected = [
            'select' => [
                'name' => 'user_id',
                'required' => 'required',
            ],
            ['option' => ['value' => '0']], 'option A', '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->select('user_id', ['option A'], ['disabled' => true]);
        $expected = [
            'select' => [
                'name' => 'user_id',
                'disabled' => 'disabled',
            ],
            ['option' => ['value' => '0']], 'option A', '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    public function testSelectEmptyWithRequiredFalse(): void
    {
        $Articles = $this->getTableLocator()->get('Articles');
        $validator = $Articles->getValidator('default');
        $validator->allowEmptyString('user_id');
        $Articles->setValidator('default', $validator);

        $entity = $Articles->newEmptyEntity();
        $this->Form->create($entity);
        $result = $this->Form->select('user_id', ['option A']);
        $expected = [
            'select' => [
                'name' => 'user_id',
            ],
            ['option' => ['value' => '']], '/option',
            ['option' => ['value' => '0']], 'option A', '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testNestedSelect method
     *
     * Test select element generation with optgroups.
     */
    public function testNestedSelect(): void
    {
        $result = $this->Form->select(
            'Model.field',
            [1 => 'One', 2 => 'Two', 'Three' => [
                3 => 'Three', 4 => 'Four', 5 => 'Five',
            ]],
            ['empty' => false]
        );
        $expected = [
            'select' => ['name' => 'Model[field]'],
            ['option' => ['value' => 1]],
            'One',
            '/option',
            ['option' => ['value' => 2]],
            'Two',
            '/option',
            ['optgroup' => ['label' => 'Three']],
                ['option' => ['value' => 3]],
                'Three',
                '/option',
                ['option' => ['value' => 4]],
                'Four',
                '/option',
                ['option' => ['value' => 5]],
                'Five',
                '/option',
            '/optgroup',
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testSelectMultiple method
     *
     * Test generation of multiple select elements.
     */
    public function testSelectMultiple(): void
    {
        $options = ['first', 'second', 'third'];
        $result = $this->Form->select(
            'Model.multi_field',
            $options,
            ['form' => 'my-form', 'multiple' => true]
        );
        $expected = [
            'input' => [
                'type' => 'hidden',
                'name' => 'Model[multi_field]',
                'value' => '',
                'form' => 'my-form',
            ],
            'select' => [
                'name' => 'Model[multi_field][]',
                'multiple' => 'multiple',
                'form' => 'my-form',
            ],
            ['option' => ['value' => '0']],
            'first',
            '/option',
            ['option' => ['value' => '1']],
            'second',
            '/option',
            ['option' => ['value' => '2']],
            'third',
            '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->select(
            'Model.multi_field',
            $options,
            ['multiple' => 'multiple', 'form' => 'my-form']
        );
        $this->assertHtml($expected, $result);

        $result = $this->Form->select(
            'Model.multi_field',
            $options,
            ['form' => 'my-form', 'multiple' => false]
        );
        $this->assertStringNotContainsString('multiple', $result);
    }

    /**
     * testCheckboxZeroValue method
     *
     * Test that a checkbox can have 0 for the value and 1 for the hidden input.
     */
    public function testCheckboxZeroValue(): void
    {
        $result = $this->Form->control('User.get_spam', [
            'type' => 'checkbox',
            'value' => '0',
            'hiddenField' => '1',
        ]);
        $expected = [
            'div' => ['class' => 'input checkbox'],
            'label' => ['for' => 'user-get-spam'],
            ['input' => [
                'type' => 'hidden', 'name' => 'User[get_spam]',
                'value' => '1',
            ]],
            ['input' => [
                'type' => 'checkbox', 'name' => 'User[get_spam]',
                'value' => '0', 'id' => 'user-get-spam',
            ]],
            'Get Spam',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('User.get_spam', [
            'type' => 'checkbox',
            'value' => '0',
            'hiddenField' => '',
        ]);
        $expected = [
            'div' => ['class' => 'input checkbox'],
            'label' => ['for' => 'user-get-spam'],
            ['input' => [
                'type' => 'hidden', 'name' => 'User[get_spam]',
                'value' => '',
            ]],
            ['input' => [
                'type' => 'checkbox', 'name' => 'User[get_spam]',
                'value' => '0', 'id' => 'user-get-spam',
            ]],
            'Get Spam',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testHabtmSelectBox method
     *
     * Test generation of habtm select boxes.
     */
    public function testHabtmSelectBox(): void
    {
        $options = [
            1 => 'blue',
            2 => 'red',
            3 => 'green',
        ];
        $tags = [
            new Entity(['id' => 1, 'name' => 'blue']),
            new Entity(['id' => 3, 'name' => 'green']),
        ];
        $article = new Article(['tags' => $tags]);
        $this->Form->create($article);
        $result = $this->Form->control('tags._ids', ['options' => $options]);
        $expected = [
            'div' => ['class' => 'input select'],
            'label' => ['for' => 'tags-ids'],
            'Tags',
            '/label',
            'input' => ['type' => 'hidden', 'name' => 'tags[_ids]', 'value' => ''],
            'select' => [
                'name' => 'tags[_ids][]', 'id' => 'tags-ids',
                'multiple' => 'multiple',
            ],
            ['option' => ['value' => '1', 'selected' => 'selected']],
            'blue',
            '/option',
            ['option' => ['value' => '2']],
            'red',
            '/option',
            ['option' => ['value' => '3', 'selected' => 'selected']],
            'green',
            '/option',
            '/select',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        // make sure only 50 is selected, and not 50f5c0cf
        $options = [
            '1' => 'blue',
            '50f5c0cf' => 'red',
            '50' => 'green',
        ];
        $tags = [
            new Entity(['id' => 1, 'name' => 'blue']),
            new Entity(['id' => 50, 'name' => 'green']),
        ];
        $article = new Article(['tags' => $tags]);
        $this->Form->create($article);
        $result = $this->Form->control('tags._ids', ['options' => $options]);
        $expected = [
            'div' => ['class' => 'input select'],
            'label' => ['for' => 'tags-ids'],
            'Tags',
            '/label',
            'input' => ['type' => 'hidden', 'name' => 'tags[_ids]', 'value' => ''],
            'select' => [
                'name' => 'tags[_ids][]', 'id' => 'tags-ids',
                'multiple' => 'multiple',
            ],
            ['option' => ['value' => '1', 'selected' => 'selected']],
            'blue',
            '/option',
            ['option' => ['value' => '50f5c0cf']],
            'red',
            '/option',
            ['option' => ['value' => '50', 'selected' => 'selected']],
            'green',
            '/option',
            '/select',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $spacecraft = [
            1 => 'Orion',
            2 => 'Helios',
        ];
        $this->View->set('spacecraft', $spacecraft);
        $this->Form->create();
        $result = $this->Form->control('spacecraft._ids');
        $expected = [
            'div' => ['class' => 'input select'],
            'label' => ['for' => 'spacecraft-ids'],
            'Spacecraft',
            '/label',
            'input' => ['type' => 'hidden', 'name' => 'spacecraft[_ids]', 'value' => ''],
            'select' => [
                'name' => 'spacecraft[_ids][]', 'id' => 'spacecraft-ids',
                'multiple' => 'multiple',
            ],
            ['option' => ['value' => '1']],
            'Orion',
            '/option',
            ['option' => ['value' => '2']],
            'Helios',
            '/option',
            '/select',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testErrorsForBelongsToManySelect method
     *
     * Tests that errors for belongsToMany select fields are being
     * picked up properly.
     */
    public function testErrorsForBelongsToManySelect(): void
    {
        $spacecraft = [
            1 => 'Orion',
            2 => 'Helios',
        ];
        $this->View->set('spacecraft', $spacecraft);

        $article = new Article();
        $article->setError('spacecraft', ['Invalid']);

        $this->Form->create($article);
        $result = $this->Form->control('spacecraft._ids');

        $expected = [
            ['div' => ['class' => 'input select error']],
            'label' => ['for' => 'spacecraft-ids'],
            'Spacecraft',
            '/label',
            'input' => ['type' => 'hidden', 'name' => 'spacecraft[_ids]', 'value' => ''],
            'select' => [
                'name' => 'spacecraft[_ids][]',
                'id' => 'spacecraft-ids',
                'multiple' => 'multiple',
            ],
            ['option' => ['value' => '1']],
            'Orion',
            '/option',
            ['option' => ['value' => '2']],
            'Helios',
            '/option',
            '/select',
            ['div' => ['class' => 'error-message', 'id' => 'spacecraft-error']],
            'Invalid',
            '/div',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testSelectMultipleCheckboxes method
     *
     * Test generation of multi select elements in checkbox format.
     */
    public function testSelectMultipleCheckboxes(): void
    {
        $result = $this->Form->select(
            'Model.multi_field',
            ['first', 'second', 'third'],
            ['multiple' => 'checkbox']
        );

        $expected = [
            'input' => [
                'type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => '', 'id' => 'model-multi-field',
            ],
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-0']],
            ['input' => [
                'type' => 'checkbox', 'name' => 'Model[multi_field][]',
                'value' => '0', 'id' => 'model-multi-field-0',
            ]],
            'first',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-1']],
            ['input' => [
                'type' => 'checkbox', 'name' => 'Model[multi_field][]',
                'value' => '1', 'id' => 'model-multi-field-1',
            ]],
            'second',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-2']],
            ['input' => [
                'type' => 'checkbox', 'name' => 'Model[multi_field][]',
                'value' => '2', 'id' => 'model-multi-field-2',
            ]],
            'third',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->select(
            'Model.multi_field',
            ['a+' => 'first', 'a++' => 'second', 'a+++' => 'third'],
            ['multiple' => 'checkbox']
        );
        $expected = [
            'input' => [
                'type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => '', 'id' => 'model-multi-field',
            ],
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-a+']],
            ['input' => [
                'type' => 'checkbox', 'name' => 'Model[multi_field][]',
                'value' => 'a+', 'id' => 'model-multi-field-a+',
            ]],
            'first',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-a++']],
            ['input' => [
                'type' => 'checkbox', 'name' => 'Model[multi_field][]',
                'value' => 'a++', 'id' => 'model-multi-field-a++',
            ]],
            'second',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-a+++']],
            ['input' => [
                'type' => 'checkbox', 'name' => 'Model[multi_field][]',
                'value' => 'a+++', 'id' => 'model-multi-field-a+++',
            ]],
            'third',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->select(
            'Model.multi_field',
            ['a>b' => 'first', 'a<b' => 'second', 'a"b' => 'third'],
            ['multiple' => 'checkbox']
        );
        $expected = [
            'input' => [
                'type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => '', 'id' => 'model-multi-field',
            ],
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-a-b']],
            ['input' => [
                'type' => 'checkbox', 'name' => 'Model[multi_field][]',
                'value' => 'a&gt;b', 'id' => 'model-multi-field-a-b',
            ]],
            'first',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-a-b1']],
            ['input' => [
                'type' => 'checkbox', 'name' => 'Model[multi_field][]',
                'value' => 'a&lt;b', 'id' => 'model-multi-field-a-b1',
            ]],
            'second',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-a-b2']],
            ['input' => [
                'type' => 'checkbox', 'name' => 'Model[multi_field][]',
                'value' => 'a&quot;b', 'id' => 'model-multi-field-a-b2',
            ]],
            'third',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testSelectMultipleCheckboxRequestData method
     *
     * Ensure that multiCheckbox reads from the request data.
     */
    public function testSelectMultipleCheckboxRequestData(): void
    {
        $this->View->setRequest($this->View->getRequest()->withData('Model', ['tags' => [1]]));
        $result = $this->Form->select(
            'Model.tags',
            ['1' => 'first', 'Array' => 'Array'],
            ['multiple' => 'checkbox']
        );
        $expected = [
            'input' => [
                'type' => 'hidden', 'name' => 'Model[tags]', 'value' => '', 'id' => 'model-tags',
            ],
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-tags-1', 'class' => 'selected']],
            ['input' => [
                'type' => 'checkbox', 'name' => 'Model[tags][]',
                'value' => '1', 'id' => 'model-tags-1', 'checked' => 'checked',
            ]],
            'first',
            '/label',
            '/div',

            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-tags-array']],
            ['input' => [
                'type' => 'checkbox', 'name' => 'Model[tags][]',
                'value' => 'Array', 'id' => 'model-tags-array',
            ]],
            'Array',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testSelectMultipleCheckboxSecurity method
     *
     * Checks the security hash array generated for multiple-input checkbox elements.
     */
    public function testSelectMultipleCheckboxSecurity(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));
        $this->Form->create();

        $this->Form->select(
            'Model.multi_field',
            ['1' => 'first', '2' => 'second', '3' => 'third'],
            ['multiple' => 'checkbox']
        );
        $fields = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEquals(['Model.multi_field'], $fields);

        $result = $this->Form->secure();
        $hash = hash_hmac('sha1', $this->url . serialize($fields) . session_id(), Security::getSalt());
        $hash = urlencode($hash . ':');
        $this->assertStringContainsString('"' . $hash . '"', $result);
    }

    /**
     * testSelectMultipleSecureWithNoOptions method
     *
     * Multiple select elements should always be secured as they always participate
     * in the POST data.
     */
    public function testSelectMultipleSecureWithNoOptions(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));
        $this->Form->create();

        $this->Form->select(
            'Model.select',
            [],
            ['multiple' => true]
        );
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEquals(['Model.select'], $result);
    }

    /**
     * testSelectNoSecureWithNoOptions method
     *
     * When a select box has no options it should not be added to the fields list
     * as it always fail post validation.
     */
    public function testSelectNoSecureWithNoOptions(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));
        $this->Form->create();

        $this->Form->select(
            'Model.select',
            []
        );
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEquals([], $result);

        $this->Form->select(
            'Model.user_id',
            [],
            ['empty' => true]
        );
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEquals(['Model.user_id'], $result);
    }

    /**
     * testControlMultipleCheckboxes method
     *
     * Test control() resulting in multi select elements being generated.
     */
    public function testControlMultipleCheckboxes(): void
    {
        $result = $this->Form->control('Model.multi_field', [
            'options' => ['first', 'second', 'third'],
            'multiple' => 'checkbox',
        ]);
        $expected = [
            ['div' => ['class' => 'input select']],
            ['label' => ['for' => 'model-multi-field']],
            'Multi Field',
            '/label',
            'input' => ['type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => '', 'id' => 'model-multi-field'],
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-0']],
            ['input' => ['type' => 'checkbox', 'name' => 'Model[multi_field][]', 'value' => '0', 'id' => 'model-multi-field-0']],
            'first',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-1']],
            ['input' => ['type' => 'checkbox', 'name' => 'Model[multi_field][]', 'value' => '1', 'id' => 'model-multi-field-1']],
            'second',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-2']],
            ['input' => ['type' => 'checkbox', 'name' => 'Model[multi_field][]', 'value' => '2', 'id' => 'model-multi-field-2']],
            'third',
            '/label',
            '/div',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('Model.multi_field', [
            'options' => ['a' => 'first', 'b' => 'second', 'c' => 'third'],
            'multiple' => 'checkbox',
        ]);
        $expected = [
            ['div' => ['class' => 'input select']],
            ['label' => ['for' => 'model-multi-field']],
            'Multi Field',
            '/label',
            'input' => ['type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => '', 'id' => 'model-multi-field'],
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-a']],
            ['input' => ['type' => 'checkbox', 'name' => 'Model[multi_field][]', 'value' => 'a', 'id' => 'model-multi-field-a']],
            'first',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-b']],
            ['input' => ['type' => 'checkbox', 'name' => 'Model[multi_field][]', 'value' => 'b', 'id' => 'model-multi-field-b']],
            'second',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-c']],
            ['input' => ['type' => 'checkbox', 'name' => 'Model[multi_field][]', 'value' => 'c', 'id' => 'model-multi-field-c']],
            'third',
            '/label',
            '/div',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testSelectHiddenFieldOmission method
     *
     * Test that select() with 'hiddenField' => false omits the hidden field.
     */
    public function testSelectHiddenFieldOmission(): void
    {
        $result = $this->Form->select(
            'Model.multi_field',
            ['first', 'second'],
            ['multiple' => 'checkbox', 'hiddenField' => false, 'value' => null]
        );
        $this->assertStringNotContainsString('type="hidden"', $result);
    }

    /**
     * testSelectCheckboxMultipleOverrideName method
     *
     * Test that select() with multiple = checkbox works with overriding name attribute.
     */
    public function testSelectCheckboxMultipleOverrideName(): void
    {
        $result = $this->Form->select('category', ['1', '2'], [
            'multiple' => 'checkbox',
            'name' => 'fish',
        ]);
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'fish', 'value' => '', 'id' => 'category'],
            ['div' => ['class' => 'checkbox']],
                ['label' => ['for' => 'fish-0']],
                    ['input' => ['type' => 'checkbox', 'name' => 'fish[]', 'value' => '0', 'id' => 'fish-0']],
                    '1',
                '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
                ['label' => ['for' => 'fish-1']],
                    ['input' => ['type' => 'checkbox', 'name' => 'fish[]', 'value' => '1', 'id' => 'fish-1']],
                    '2',
                '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->multiCheckbox(
            'category',
            new Collection(['1', '2']),
            ['name' => 'fish']
        );
        $this->assertHtml($expected, $result);

        $result = $this->Form->multiCheckbox('category', ['1', '2'], [
            'name' => 'fish',
        ]);
        $this->assertHtml($expected, $result);
    }

    /**
     * testControlMultiCheckbox method
     *
     * Test that control() works with multicheckbox.
     */
    public function testControlMultiCheckbox(): void
    {
        $result = $this->Form->control('category', [
            'type' => 'multicheckbox',
            'options' => ['1', '2'],
        ]);
        $expected = [
            ['div' => ['class' => 'input multicheckbox']],
            '<label',
            'Category',
            '/label',
            'input' => ['type' => 'hidden', 'name' => 'category', 'value' => '', 'id' => 'category'],
            ['div' => ['class' => 'checkbox']],
                ['label' => ['for' => 'category-0']],
                    ['input' => ['type' => 'checkbox', 'name' => 'category[]', 'value' => '0', 'id' => 'category-0']],
                    '1',
                '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
                ['label' => ['for' => 'category-1']],
                    ['input' => ['type' => 'checkbox', 'name' => 'category[]', 'value' => '1', 'id' => 'category-1']],
                    '2',
                '/label',
            '/div',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testCheckbox method
     *
     * Test generation of checkboxes.
     */
    public function testCheckbox(): void
    {
        $result = $this->Form->checkbox('Model.field');
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'Model[field]', 'value' => '0'],
            ['input' => ['type' => 'checkbox', 'name' => 'Model[field]', 'value' => '1']],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->checkbox('Model.field', [
            'id' => 'theID',
            'value' => 'myvalue',
            'form' => 'my-form',
        ]);
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'Model[field]', 'value' => '0', 'form' => 'my-form'],
            ['input' => [
                'type' => 'checkbox', 'name' => 'Model[field]',
                'value' => 'myvalue', 'id' => 'theID',
                'form' => 'my-form',
            ]],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testCheckboxDefaultValue method
     *
     * Test default value setting on checkbox() method.
     */
    public function testCheckboxDefaultValue(): void
    {
        $this->View->setRequest($this->View->getRequest()->withData('Model.field', false));
        $result = $this->Form->checkbox('Model.field', ['default' => true, 'hiddenField' => false]);
        $expected = ['input' => ['type' => 'checkbox', 'name' => 'Model[field]', 'value' => '1']];
        $this->assertHtml($expected, $result);

        $this->View->setRequest($this->View->getRequest()->withData('Model.field', null));
        $this->Form->create();
        $result = $this->Form->checkbox('Model.field', ['default' => true, 'hiddenField' => false]);
        $expected = ['input' => ['type' => 'checkbox', 'name' => 'Model[field]', 'value' => '1', 'checked' => 'checked']];
        $this->assertHtml($expected, $result);

        $this->View->setRequest($this->View->getRequest()->withData('Model.field', true));
        $this->Form->create();
        $result = $this->Form->checkbox('Model.field', ['default' => false, 'hiddenField' => false]);
        $expected = ['input' => ['type' => 'checkbox', 'name' => 'Model[field]', 'value' => '1', 'checked' => 'checked']];
        $this->assertHtml($expected, $result);

        $this->View->setRequest($this->View->getRequest()->withData('Model.field', null));
        $this->Form->create();
        $result = $this->Form->checkbox('Model.field', ['default' => false, 'hiddenField' => false]);
        $expected = ['input' => ['type' => 'checkbox', 'name' => 'Model[field]', 'value' => '1']];
        $this->assertHtml($expected, $result);

        $Articles = $this->getTableLocator()->get('Articles');
        $Articles->getSchema()->addColumn(
            'published',
            ['type' => 'boolean', 'null' => false, 'default' => true]
        );

        $this->Form->create($Articles->newEmptyEntity());
        $result = $this->Form->checkbox('published', ['hiddenField' => false]);
        $expected = ['input' => ['type' => 'checkbox', 'name' => 'published', 'value' => '1', 'checked' => 'checked']];
        $this->assertHtml($expected, $result);
    }

    /**
     * testCheckboxCheckedAndError method
     *
     * Test checkbox being checked or having errors.
     */
    public function testCheckboxCheckedAndError(): void
    {
        $this->article['errors'] = [
            'published' => true,
        ];
        $this->View->setRequest($this->View->getRequest()->withData('published', 'myvalue'));
        $this->Form->create($this->article);

        $result = $this->Form->checkbox('published', ['id' => 'theID', 'value' => 'myvalue']);
        $expected = [
            'input' => ['type' => 'hidden', 'class' => 'form-error', 'name' => 'published', 'value' => '0'],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'published',
                'value' => 'myvalue',
                'id' => 'theID',
                'checked' => 'checked',
                'class' => 'form-error',
            ]],
        ];
        $this->assertHtml($expected, $result);

        $this->View->setRequest($this->View->getRequest()->withData('published', ''));
        $this->Form->create($this->article);
        $result = $this->Form->checkbox('published');
        $expected = [
            'input' => ['type' => 'hidden', 'class' => 'form-error', 'name' => 'published', 'value' => '0'],
            ['input' => ['type' => 'checkbox', 'name' => 'published', 'value' => '1', 'class' => 'form-error']],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testCheckboxCustomNameAttribute method
     *
     * Test checkbox() with a custom name attribute.
     */
    public function testCheckboxCustomNameAttribute(): void
    {
        $result = $this->Form->checkbox('Test.test', ['name' => 'myField']);
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'myField', 'value' => '0'],
            ['input' => ['type' => 'checkbox', 'name' => 'myField', 'value' => '1']],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testCheckboxHiddenField method
     *
     * Test that the hidden input for checkboxes can be omitted or set to a
     * specific value.
     */
    public function testCheckboxHiddenField(): void
    {
        $result = $this->Form->checkbox('UserForm.something', [
            'hiddenField' => false,
        ]);
        $expected = [
            'input' => [
                'type' => 'checkbox',
                'name' => 'UserForm[something]',
                'value' => '1',
            ],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->checkbox('UserForm.something', [
            'value' => 'Y',
            'hiddenField' => '',
        ]);
        $expected = [
            ['input' => [
                'type' => 'hidden', 'name' => 'UserForm[something]',
                'value' => '',
            ]],
            ['input' => [
                'type' => 'checkbox', 'name' => 'UserForm[something]',
                'value' => 'Y',
            ]],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->checkbox('UserForm.something', [
            'value' => 'Y',
            'hiddenField' => 'N',
        ]);
        $expected = [
            ['input' => [
                'type' => 'hidden', 'name' => 'UserForm[something]',
                'value' => 'N',
            ]],
            ['input' => [
                'type' => 'checkbox', 'name' => 'UserForm[something]',
                'value' => 'Y',
            ]],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testTime method
     *
     * Test the time type.
     */
    public function testTime(): void
    {
        $result = $this->Form->time('start_time', [
            'value' => '2014-03-08 16:30:00',
        ]);

        $expected = [
            'input' => [
                'type' => 'time',
                'name' => 'start_time',
                'value' => '16:30:00',
                'step' => '1',
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testDate method
     *
     * Test the date type.
     */
    public function testDate(): void
    {
        $result = $this->Form->date('start_day', [
            'value' => '2014-03-08',
        ]);

        $expected = [
            'input' => [
                'type' => 'date',
                'name' => 'start_day',
                'value' => '2014-03-08',
            ],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->date('start_day', [
            'value' => new FrozenDate('2014-03-08'),
        ]);
        $this->assertHtml($expected, $result);
    }

    /**
     * testDateTime method
     */
    public function testDateTime(): void
    {
        $result = $this->Form->dateTime('date', ['default' => true]);
        $expected = [
            'input' => [
                'type' => 'datetime-local',
                'name' => 'date',
                'value' => 'preg:/' . date('Y-m-d') . 'T\d{2}:\d{2}:\d{2}/',
                'step' => '1',
            ],
        ];

        $this->assertHtml($expected, $result);
    }

    /**
     * testDateTimeSecured method
     *
     * Test that datetime fields are added to protected fields list.
     */
    public function testDateTimeSecured(): void
    {
        $this->View->setRequest(
            $this->View->getRequest()->withAttribute('formTokenData', ['unlockedFields' => []])
        );
        $this->Form->create();

        $this->Form->dateTime('date');
        $expected = ['date'];
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEquals($expected, $result);

        $this->Form->fields = [];
        $this->Form->date('published');
        $expected = ['date', 'published'];
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEquals($expected, $result);
    }

    /**
     * testDateTimeSecuredDisabled method
     *
     * Test that datetime fields are added to protected fields list.
     */
    public function testDateTimeSecuredDisabled(): void
    {
        $this->View->setRequest(
            $this->View->getRequest()->withAttribute('formTokenData', ['unlockedFields' => []])
        );
        $this->Form->create();

        $this->Form->dateTime('date', ['secure' => false]);
        $expected = [];
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEquals($expected, $result);

        $this->Form->fields = [];
        $this->Form->date('published', ['secure' => false]);
        $expected = [];
        $result = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEquals($expected, $result);
    }

    /**
     * testDatetimeWithDefault method
     *
     * Test that datetime() and default values work.
     */
    public function testDatetimeWithDefault(): void
    {
        $result = $this->Form->dateTime('updated', ['value' => '2009-06-01 11:15:30']);
        $expected = [
            'input' => [
                'type' => 'datetime-local',
                'name' => 'updated',
                'value' => '2009-06-01T11:15:30',
                'step' => '1',
            ],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->dateTime('updated', [
            'default' => '2009-06-01 11:15:30',
        ]);
        $expected = [
            'input' => [
                'type' => 'datetime-local',
                'name' => 'updated',
                'value' => '2009-06-01T11:15:30',
                'step' => '1',
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testMonth method
     *
     * Test generation of a month input.
     */
    public function testMonth(): void
    {
        $result = $this->Form->month('field', ['value' => '']);
        $expected = [
            'input' => [
                'type' => 'month',
                'name' => 'field',
                'value' => '',
            ],
        ];
        $this->assertHtml($expected, $result);

        $this->View->setRequest(
            $this->View->getRequest()->withData('release', '2050-02-10')
        );
        $this->Form->create();
        $result = $this->Form->month('release');

        $expected = [
            'input' => [
                'type' => 'month',
                'name' => 'release',
                'value' => '2050-02',
            ],
        ];
        $this->assertHtml($expected, $result);

        $this->View->setRequest(
            $this->View->getRequest()->withData('release', '2050-03')
        );
        $this->Form->create();
        $result = $this->Form->month('release');
        $expected = [
            'input' => [
                'type' => 'month',
                'name' => 'release',
                'value' => '2050-03',
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testYear method
     *
     * Test generation of a year input.
     */
    public function testYear(): void
    {
        $this->View->setRequest(
            $this->View->getRequest()->withData('published', '2006')
        );

        $result = $this->Form->year('field', ['value' => '', 'min' => 2006, 'max' => 2007]);
        $expected = [
            ['select' => ['name' => 'field']],
            ['option' => ['selected' => 'selected', 'value' => '']],
            '/option',
            ['option' => ['value' => '2007']],
            '2007',
            '/option',
            ['option' => ['value' => '2006']],
            '2006',
            '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->year('field', [
            'value' => '',
            'min' => 2006,
            'max' => 2007,
            'order' => 'asc',
        ]);
        $expected = [
            ['select' => ['name' => 'field']],
            ['option' => ['selected' => 'selected', 'value' => '']],
            '/option',
            ['option' => ['value' => '2006']],
            '2006',
            '/option',
            ['option' => ['value' => '2007']],
            '2007',
            '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->year('published', [
            'empty' => false,
            'min' => 2006,
            'max' => 2007,
        ]);
        $expected = [
            ['select' => ['name' => 'published']],
            ['option' => ['value' => '2007']],
            '2007',
            '/option',
            ['option' => ['value' => '2006', 'selected' => 'selected']],
            '2006',
            '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->year('published', [
            'empty' => false,
            'value' => new FrozenDate('2008-01-12'),
            'min' => 2007,
            'max' => 2009,
        ]);
        $expected = [
            ['select' => ['name' => 'published']],
            ['option' => ['value' => '2009']],
            '2009',
            '/option',
            ['option' => ['value' => '2008', 'selected' => 'selected']],
            '2008',
            '/option',
            ['option' => ['value' => '2007']],
            '2007',
            '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->year('published', [
            'empty' => 'Published on',
        ]);
        $this->assertStringContainsString('Published on', $result);
    }

    /**
     * testControlYearPreEpoch method
     *
     * Test minYear being prior to the unix epoch.
     */
    public function testControlYearPreEpoch(): void
    {
        $start = date('Y') - 80;
        $end = date('Y') - 18;
        $result = $this->Form->control('birth_year', [
            'type' => 'year',
            'label' => 'Birth Year',
            'min' => $start,
            'max' => $end,
        ]);
        $this->assertStringContainsString('value="' . $start . '">' . $start, $result);
        $this->assertStringContainsString('value="' . $end . '">' . $end, $result);
        $this->assertStringNotContainsString('value="00">00', $result);
    }

    /**
     * test control() datetime & required attributes
     */
    public function testControlDatetimeRequired(): void
    {
        $result = $this->Form->control('birthday', [
            'type' => 'date',
            'required' => true,
        ]);
        $this->assertStringContainsString(
            '<input type="date" name="birthday" required="required"',
            $result
        );
    }

    /**
     * testYearAutoExpandRange method
     */
    public function testYearAutoExpandRange(): void
    {
        $this->View->setRequest($this->View->getRequest()->withData('birthday', '1930'));
        $result = $this->Form->year('birthday');
        preg_match_all('/<option value="([\d]+)"/', $result, $matches);

        $result = $matches[1];
        $expected = range(date('Y') + 5, 1930);
        $this->assertEquals($expected, $result);

        $this->View->setRequest($this->View->getRequest()->withData('release', '2050'));
        $this->Form->create();
        $result = $this->Form->year('release');
        preg_match_all('/<option value="([\d]+)"/', $result, $matches);

        $result = $matches[1];
        $expected = range(2050, date('Y') - 5);
        $this->assertEquals($expected, $result);

        $this->View->setRequest($this->View->getRequest()->withData('release', '1881'));
        $this->Form->create();
        $result = $this->Form->year('release', [
            'min' => 1890,
            'max' => 1900,
        ]);
        preg_match_all('/<option value="([\d]+)"/', $result, $matches);

        $result = $matches[1];
        $expected = range(1900, 1881);
        $this->assertEquals($expected, $result);
    }

    /**
     * test control placeholder + label
     */
    public function testControlLabelAndPlaceholder(): void
    {
        $this->Form->create($this->article);
        $result = $this->Form->control('title', ['label' => 'Title', 'placeholder' => 'Add title']);
        $expected = [
            'div' => ['class' => 'input text required'],
            'label' => ['for' => 'title'],
            'Title',
            '/label',
            'input' => [
                'aria-required' => 'true',
                'type' => 'text',
                'required' => 'required',
                'placeholder' => 'Add title',
                'id' => 'title',
                'name' => 'title',
                'data-validity-message' => 'This field cannot be left empty',
                'oninvalid' => 'this.setCustomValidity(&#039;&#039;); if (!this.value) this.setCustomValidity(this.dataset.validityMessage)',
                'oninput' => 'this.setCustomValidity(&#039;&#039;)',
            ],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testControlLabelFalse method
     *
     * Test the label option being set to false.
     */
    public function testControlLabelFalse(): void
    {
        $this->Form->create($this->article);
        $result = $this->Form->control('title', ['label' => false]);
        $expected = [
            'div' => ['class' => 'input text required'],
            'input' => [
                'aria-required' => 'true',
                'type' => 'text',
                'required' => 'required',
                'id' => 'title',
                'name' => 'title',
                'data-validity-message' => 'This field cannot be left empty',
                'oninvalid' => 'this.setCustomValidity(&#039;&#039;); if (!this.value) this.setCustomValidity(this.dataset.validityMessage)',
                'oninput' => 'this.setCustomValidity(&#039;&#039;)',
            ],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $this->Form->create($this->article);
        $result = $this->Form->control('title', ['label' => false, 'placeholder' => 'Add title']);
        $expected['input'] += [
            'placeholder' => 'Add title',
            'aria-label' => 'Add title',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testTextArea method
     *
     * Test generation of a textarea input.
     */
    public function testTextArea(): void
    {
        $this->View->setRequest($this->View->getRequest()->withData('field', 'some test data'));
        $result = $this->Form->textarea('field');
        $expected = [
            'textarea' => ['name' => 'field', 'rows' => 5],
            'some test data',
            '/textarea',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->textarea('user.bio');
        $expected = [
            'textarea' => ['name' => 'user[bio]', 'rows' => 5],
            '/textarea',
        ];
        $this->assertHtml($expected, $result);

        $this->View->setRequest($this->View->getRequest()
            ->withData('field', 'some <strong>test</strong> data with <a href="#">HTML</a> chars'));
        $this->Form->create();
        $result = $this->Form->textarea('field');
        $expected = [
            'textarea' => ['name' => 'field', 'rows' => 5],
            htmlentities('some <strong>test</strong> data with <a href="#">HTML</a> chars'),
            '/textarea',
        ];
        $this->assertHtml($expected, $result);

        $this->View->setRequest($this->View->getRequest()->withData(
            'Model.field',
            'some <strong>test</strong> data with <a href="#">HTML</a> chars'
        ));
        $this->Form->create();
        $result = $this->Form->textarea('Model.field', ['escape' => false]);
        $expected = [
            'textarea' => ['name' => 'Model[field]', 'rows' => 5],
            'some <strong>test</strong> data with <a href="#">HTML</a> chars',
            '/textarea',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->textarea('0.OtherModel.field');
        $expected = [
            'textarea' => ['name' => '0[OtherModel][field]', 'rows' => 5],
            '/textarea',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testTextAreaWithStupidCharacters method
     *
     * Test text area with non-ascii characters.
     */
    public function testTextAreaWithStupidCharacters(): void
    {
        $result = $this->Form->textarea('Post.content', [
            'value' => 'GREAT®',
            'rows' => '15',
            'cols' => '75',
        ]);
        $expected = [
            'textarea' => ['name' => 'Post[content]', 'rows' => '15', 'cols' => '75'],
            'GREAT®',
            '/textarea',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testTextAreaMaxLength method
     *
     * Test textareas maxlength read from schema.
     */
    public function testTextAreaMaxLength(): void
    {
        $this->Form->create([
            'schema' => [
                'stuff' => ['type' => 'string', 'length' => 10],
            ],
        ]);
        $result = $this->Form->control('other', ['type' => 'textarea']);
        $expected = [
            'div' => ['class' => 'input textarea'],
            'label' => ['for' => 'other'],
            'Other',
            '/label',
            'textarea' => ['name' => 'other', 'id' => 'other', 'rows' => 5],
            '/textarea',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('stuff', ['type' => 'textarea']);
        $expected = [
            'div' => ['class' => 'input textarea'],
            'label' => ['for' => 'stuff'],
            'Stuff',
            '/label',
            'textarea' => ['name' => 'stuff', 'maxlength' => 10, 'id' => 'stuff', 'rows' => 5],
            '/textarea',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testHiddenField method
     *
     * Test generation of a hidden input.
     */
    public function testHidden(): void
    {
        $this->article['errors'] = [
            'field' => true,
        ];
        $this->View->setRequest($this->View->getRequest()->withData('field', 'test'));
        $this->Form->create($this->article);
        $result = $this->Form->hidden('field', ['id' => 'theID']);
        $expected = [
            'input' => ['type' => 'hidden', 'class' => 'form-error', 'name' => 'field', 'id' => 'theID', 'value' => 'test']];
        $this->assertHtml($expected, $result);

        $result = $this->Form->hidden('field', ['value' => 'my value']);
        $expected = [
            'input' => ['type' => 'hidden', 'class' => 'form-error', 'name' => 'field', 'value' => 'my value'],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test hidden() with various boolean values.
     */
    public function testHiddenBooleanValues(): void
    {
        $this->Form->create($this->article);
        $result = $this->Form->hidden('field', ['value' => null]);
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'field'],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->hidden('field', ['value' => true]);
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'field', 'value' => '1'],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->hidden('field', ['value' => false]);
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'field', 'value' => '0'],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFileUploadField method
     *
     * Test generation of a file upload input.
     */
    public function testFileUploadField(): void
    {
        $expected = ['input' => ['type' => 'file', 'name' => 'Model[upload]']];

        $result = $this->Form->file('Model.upload');
        $this->assertHtml($expected, $result);

        $this->View->setRequest($this->View->getRequest()->withData('Model.upload', [
            'name' => '', 'type' => '', 'tmp_name' => '',
            'error' => 4, 'size' => 0,
        ]));
        $result = $this->Form->file('Model.upload');
        $this->assertHtml($expected, $result);

        $this->View->setRequest(
            $this->View->getRequest()->withData('Model.upload', 'no data should be set in value')
        );
        $result = $this->Form->file('Model.upload');
        $this->assertHtml($expected, $result);
    }

    /**
     * testFileUploadOnOtherModel method
     *
     * Test File upload input on a model not used in create().
     */
    public function testFileUploadOnOtherModel(): void
    {
        $this->Form->create($this->article, ['type' => 'file']);
        $result = $this->Form->file('ValidateProfile.city');
        $expected = [
            'input' => ['type' => 'file', 'name' => 'ValidateProfile[city]'],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testButton method
     *
     * Test generation of a form button.
     */
    public function testButton(): void
    {
        $result = $this->Form->button('Hi');
        $expected = ['button' => ['type' => 'submit'], 'Hi', '/button'];
        $this->assertHtml($expected, $result);

        $result = $this->Form->button('Clear Form >', ['type' => 'reset', 'escapeTitle' => false]);
        $expected = ['button' => ['type' => 'reset'], 'Clear Form >', '/button'];
        $this->assertHtml($expected, $result);

        $result = $this->Form->button('Clear Form >', ['type' => 'reset', 'id' => 'clearForm', 'escapeTitle' => false]);
        $expected = ['button' => ['type' => 'reset', 'id' => 'clearForm'], 'Clear Form >', '/button'];
        $this->assertHtml($expected, $result);

        $result = $this->Form->button('<Clear Form>', ['type' => 'reset']);
        $expected = ['button' => ['type' => 'reset'], '&lt;Clear Form&gt;', '/button'];
        $this->assertHtml($expected, $result);

        $result = $this->Form->button('No type', ['type' => false]);
        $expected = ['button' => [], 'No type', '/button'];
        $this->assertHtml($expected, $result);

        $result = $this->Form->button('Upload Text', [
            'onClick' => "$('#postAddForm').ajaxSubmit({target: '#postTextUpload', url: '/posts/text'});return false;'",
            'escape' => false,
        ]);
        $this->assertDoesNotMatchRegularExpression('/\&039/', $result);
    }

    /**
     * testButtonUnlockedByDefault method
     *
     * Test that button() makes unlocked fields by default.
     */
    public function testButtonUnlockedByDefault(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));
        $this->Form->create();

        $this->Form->button('Save', ['name' => 'save']);
        $this->Form->button('Clear');

        $result = $this->Form->getFormProtector()->__debugInfo()['unlockedFields'];
        $this->assertEquals(['save'], $result);
    }

    /**
     * Test generation of a form button with confirm message.
     */
    public function testButtonWithConfirm(): void
    {
        $result = $this->Form->button('Hi', ['confirm' => 'Confirm me!']);
        $expected = ['button' => [
            'type' => 'submit',
            'data-confirm-message' => 'Confirm me!',
            'onclick' => 'if (confirm(this.dataset.confirmMessage)) { return true; } return false;',
        ], 'Hi', '/button'];
        $this->assertHtml($expected, $result);

        $result = $this->Form->button('Hi', ['escape' => false, 'confirm' => 'Confirm "me"!']);
        $expected = ['button' => [
            'type' => 'submit',
            'data-confirm-message' => 'Confirm "me"!',
            'onclick' => 'if (confirm(this.dataset.confirmMessage)) { return true; } return false;',
        ], 'Hi', '/button'];
        $this->assertHtml($expected, $result);
    }

    /**
     * testPostButton method
     */
    public function testPostButton(): void
    {
        $result = $this->Form->postButton('Hi', '/controller/action');
        $expected = [
            'form' => ['method' => 'post', 'action' => '/controller/action', 'accept-charset' => 'utf-8'],
            'button' => ['type' => 'submit'],
            'Hi',
            '/button',
            '/form',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->postButton('Send', '/', ['data' => ['extra' => 'value']]);
        $this->assertStringContainsString('<input type="hidden" name="extra" value="value"', $result);
    }

    /**
     * testPostButtonMethodType method
     */
    public function testPostButtonMethodType(): void
    {
        $result = $this->Form->postButton('Hi', '/controller/action', ['method' => 'patch']);
        $expected = [
            'form' => ['method' => 'post', 'action' => '/controller/action', 'accept-charset' => 'utf-8'],
            'div' => ['style' => 'display:none;'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'PATCH'],
            '/div',
            'button' => ['type' => 'submit'],
            'Hi',
            '/button',
            '/form',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testPostButtonFormOptions method
     */
    public function testPostButtonFormOptions(): void
    {
        $result = $this->Form->postButton('Hi', '/controller/action', ['form' => ['class' => 'inline']]);
        $expected = [
            'form' => ['method' => 'post', 'action' => '/controller/action', 'accept-charset' => 'utf-8', 'class' => 'inline'],
            'button' => ['type' => 'submit'],
            'Hi',
            '/button',
            '/form',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testPostButtonNestedData method
     *
     * Test using postButton with N dimensional data.
     */
    public function testPostButtonNestedData(): void
    {
        $data = [
            'one' => [
                'two' => [
                    3, 4, 5,
                ],
            ],
        ];
        $result = $this->Form->postButton('Send', '/', ['data' => $data]);
        $this->assertStringContainsString('<input type="hidden" name="one[two][0]" value="3"', $result);
        $this->assertStringContainsString('<input type="hidden" name="one[two][1]" value="4"', $result);
        $this->assertStringContainsString('<input type="hidden" name="one[two][2]" value="5"', $result);
    }

    /**
     * testSecurePostButton method
     *
     * Test that postButton adds _Token fields.
     */
    public function testSecurePostButton(): void
    {
        $this->View->setRequest($this->View->getRequest()
            ->withAttribute('csrfToken', 'testkey')
            ->withAttribute('formTokenData', ['unlockedFields' => []]));

        $result = $this->Form->postButton('Delete', '/posts/delete/1');
        $tokenDebug = urlencode(json_encode([
                '/posts/delete/1',
                [],
                [],
            ]));

        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/posts/delete/1', 'accept-charset' => 'utf-8',
            ],
            ['div' => ['style' => 'display:none;']],
            ['input' => ['type' => 'hidden', 'name' => '_csrfToken', 'value' => 'testkey', 'autocomplete' => 'off']],
            '/div',
            'button' => ['type' => 'submit'],
            'Delete',
            '/button',
            ['div' => ['style' => 'display:none;']],
            ['input' => ['type' => 'hidden', 'name' => '_Token[fields]', 'value' => 'preg:/[\w\d%]+/', 'autocomplete' => 'off']],
            ['input' => ['type' => 'hidden', 'name' => '_Token[unlocked]', 'value' => '', 'autocomplete' => 'off']],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[debug]',
                'value' => $tokenDebug,
                'autocomplete' => 'off',
            ]],
            '/div',
            '/form',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testPostLink method
     */
    public function testPostLink(): void
    {
        $result = $this->Form->postLink('Delete', '/posts/delete/1');
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/posts/delete/1',
                'name' => 'preg:/post_\w+/', 'style' => 'display:none;',
            ],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/form',
            'a' => ['href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'],
            'Delete',
            '/a',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->postLink('Delete', '/posts/delete/1', ['method' => 'delete']);
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/posts/delete/1',
                'name' => 'preg:/post_\w+/', 'style' => 'display:none;',
            ],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'DELETE'],
            '/form',
            'a' => ['href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'],
            'Delete',
            '/a',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->postLink(
            'Delete',
            '/posts/delete/1',
            ['target' => '_blank', 'class' => 'btn btn-danger']
        );
        $expected = [
            'form' => [
                'method' => 'post', 'target' => '_blank', 'action' => '/posts/delete/1',
                'name' => 'preg:/post_\w+/', 'style' => 'display:none;',
            ],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/form',
            'a' => ['class' => 'btn btn-danger', 'href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'],
            'Delete',
            '/a',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testPostLinkWithConfirm method
     *
     * Test the confirm option for postLink().
     */
    public function testPostLinkWithConfirm(): void
    {
        $result = $this->Form->postLink('Delete', '/posts/delete/1', ['confirm' => 'Confirm?']);
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/posts/delete/1',
                'name' => 'preg:/post_\w+/', 'style' => 'display:none;',
            ],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/form',
            'a' => [
                'href' => '#',
                'data-confirm-message' => 'Confirm?',
                'onclick' => 'preg:/if \(confirm\(this.dataset.confirmMessage\)\) \{ document\.post_\w+\.submit\(\); \} event\.returnValue = false; return false;/',
            ],
            'Delete',
            '/a',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->postLink(
            'Delete',
            '/posts/delete/1',
            ['confirm' => "'Confirm'\nthis \"deletion\"?"]
        );
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/posts/delete/1',
                'name' => 'preg:/post_\w+/', 'style' => 'display:none;',
            ],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/form',
            'a' => [
                'href' => '#',
                'data-confirm-message' => "&#039;Confirm&#039;\nthis &quot;deletion&quot;?",
                'onclick' => "preg:/if \(confirm\(this.dataset.confirmMessage\)\) \{ document\.post_\w+\.submit\(\); \} event\.returnValue = false; return false;/",
            ],
            'Delete',
            '/a',
        ];
        $this->assertHtml($expected, $result);

        $this->Form->setTemplates(['confirmJs' => 'if (confirm(this.dataset.confirmMessage)) { $(\'form[name="{{formName}}"]\').submit();};']);
        $result = $this->Form->postLink(
            'Delete',
            '/posts/delete/1',
            ['escape' => false, 'confirm' => 'Confirm this deletion?']
        );
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/posts/delete/1',
                'name' => 'preg:/post_\w+/', 'style' => 'display:none;',
            ],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/form',
            'a' => [
                'href' => '#',
                'data-confirm-message' => 'Confirm this deletion?',
                'onclick' => 'preg:/if \(confirm\(this.dataset.confirmMessage\)\) \{ \$\(\'form\[name="post_\w+"\]\'\)\.submit\(\);\};/',
            ],
            'Delete',
            '/a',
        ];

        $this->assertHtml($expected, $result);
    }

    /**
     * testPostLinkWithQuery method
     *
     * Test postLink() with query string args.
     */
    public function testPostLinkWithQuery(): void
    {
        $result = $this->Form->postLink(
            'Delete',
            ['controller' => 'Posts', 'action' => 'delete', 1, '?' => ['a' => 'b', 'c' => 'd']]
        );
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/Posts/delete/1?a=b&amp;c=d',
                'name' => 'preg:/post_\w+/', 'style' => 'display:none;',
            ],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/form',
            'a' => ['href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'],
            'Delete',
            '/a',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testPostLinkWithData method
     *
     * Test postLink with additional data.
     */
    public function testPostLinkWithData(): void
    {
        $result = $this->Form->postLink('Delete', '/posts/delete', ['data' => ['id' => 1]]);
        $this->assertStringContainsString('<input type="hidden" name="id" value="1"', $result);

        $entity = new Entity(['name' => 'no show'], ['source' => 'Articles']);
        $this->Form->create($entity);
        $this->Form->end();
        $result = $this->Form->postLink('Delete', '/posts/delete', ['data' => ['name' => 'show']]);
        $this->assertStringContainsString(
            '<input type="hidden" name="name" value="show"',
            $result,
            'should not contain entity data.'
        );
    }

    /**
     * testPostLinkSecurityHash method
     *
     * Test that security hashes for postLink include the url.
     */
    public function testPostLinkSecurityHash(): void
    {
        $hash = hash_hmac('sha1', '/posts/delete/1' . serialize(['id' => '1']) . session_id(), Security::getSalt());
        $hash .= '%3Aid';
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', ['key' => 'test']));

        $result = $this->Form->postLink(
            'Delete',
            '/posts/delete/1',
            ['data' => ['id' => 1]]
        );
        $tokenDebug = urlencode(json_encode([
            '/posts/delete/1',
            [
                'id' => 1,
            ],
            [],
        ]));
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/posts/delete/1',
                'name', 'style' => 'display:none;',
            ],
            ['input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST']],
            ['input' => ['type' => 'hidden', 'name' => 'id', 'value' => '1']],
            'div' => ['style' => 'display:none;'],
            ['input' => ['type' => 'hidden', 'name' => '_Token[fields]', 'value' => $hash, 'autocomplete' => 'off']],
            ['input' => ['type' => 'hidden', 'name' => '_Token[unlocked]', 'value' => '', 'autocomplete' => 'off']],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[debug]',
                'value' => $tokenDebug,
                'autocomplete' => 'off',
            ]],
            '/div',
            '/form',
            'a' => ['href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'],
            'Delete',
            '/a',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testPostLinkSecurityHashBlockMode method
     *
     * Test that postLink doesn't modify the fields in the containing form.
     *
     * postLink() calls inside open forms should not modify the field list
     * for the form.
     */
    public function testPostLinkSecurityHashBlockMode(): void
    {
        $hash = hash_hmac('sha1', '/posts/delete/1' . serialize([]) . session_id(), Security::getSalt());
        $hash .= '%3A';
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', ['key' => 'test']));

        $this->Form->create(null, ['url' => ['action' => 'add']]);
        $this->Form->control('title');
        $this->Form->postLink('Delete', '/posts/delete/1', ['block' => true]);
        $result = $this->View->fetch('postLink');

        $fields = $this->Form->getFormProtector()->__debugInfo()['fields'];
        $this->assertEquals(['title'], $fields);
        $this->assertStringContainsString($hash, $result, 'Should contain the correct hash.');
        $reflect = new ReflectionProperty($this->Form, '_lastAction');
        $reflect->setAccessible(true);
        $this->assertSame('/Articles/add', $reflect->getValue($this->Form), 'lastAction was should be restored.');
    }

    /**
     * testPostLinkSecurityHashNoDebugMode method
     *
     * Test that security does not include debug token if debug is false.
     */
    public function testPostLinkSecurityHashNoDebugMode(): void
    {
        Configure::write('debug', false);
        $hash = hash_hmac('sha1', '/posts/delete/1' . serialize(['id' => '1']) . session_id(), Security::getSalt());
        $hash .= '%3Aid';
        $this->View->setRequest($this->View->getRequest()
            ->withAttribute('formTokenData', ['key' => 'test']));

        $result = $this->Form->postLink(
            'Delete',
            '/posts/delete/1',
            ['data' => ['id' => 1]]
        );
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/posts/delete/1',
                'name', 'style' => 'display:none;',
            ],
            ['input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST']],
            ['input' => ['type' => 'hidden', 'name' => 'id', 'value' => '1']],
            'div' => ['style' => 'display:none;'],
            ['input' => ['type' => 'hidden', 'name' => '_Token[fields]', 'value' => $hash, 'autocomplete' => 'off']],
            ['input' => ['type' => 'hidden', 'name' => '_Token[unlocked]', 'value' => '', 'autocomplete' => 'off']],
            '/div',
            '/form',
            'a' => ['href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'],
            'Delete',
            '/a',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testPostLinkNestedData method
     *
     * Test using postLink with N dimensional data.
     */
    public function testPostLinkNestedData(): void
    {
        $data = [
            'one' => [
                'two' => [
                    3, 4, 5,
                ],
            ],
        ];
        $result = $this->Form->postLink('Send', '/', ['data' => $data]);
        $this->assertStringContainsString('<input type="hidden" name="one[two][0]" value="3"', $result);
        $this->assertStringContainsString('<input type="hidden" name="one[two][1]" value="4"', $result);
        $this->assertStringContainsString('<input type="hidden" name="one[two][2]" value="5"', $result);
    }

    /**
     * testPostLinkAfterGetForm method
     *
     * Test creating postLinks after a GET form.
     */
    public function testPostLinkAfterGetForm(): void
    {
        $this->View->setRequest($this->View->getRequest()
            ->withAttribute('csrfToken', 'testkey')
            ->withAttribute('formTokenData', []));

        $this->Form->create($this->article, ['type' => 'get']);
        $this->Form->end();

        $result = $this->Form->postLink('Delete', '/posts/delete/1');
        $tokenDebug = urlencode(json_encode([
            '/posts/delete/1',
            [],
            [],
        ]));
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/posts/delete/1',
                'name' => 'preg:/post_\w+/', 'style' => 'display:none;',
            ],
            ['input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST']],
            ['input' => ['type' => 'hidden', 'name' => '_csrfToken', 'value' => 'testkey', 'autocomplete' => 'off']],
            'div' => ['style' => 'display:none;'],
            ['input' => ['type' => 'hidden', 'name' => '_Token[fields]', 'value' => 'preg:/[\w\d%]+/', 'autocomplete' => 'off']],
            ['input' => ['type' => 'hidden', 'name' => '_Token[unlocked]', 'value' => '', 'autocomplete' => 'off']],
            ['input' => [
                'type' => 'hidden', 'name' => '_Token[debug]',
                'value' => $tokenDebug,
                'autocomplete' => 'off',
            ]],
            '/div',
            '/form',
            'a' => ['href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'],
            'Delete',
            '/a',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testPostLinkFormBuffer method
     *
     * Test that postLink adds form tags to view block.
     */
    public function testPostLinkFormBuffer(): void
    {
        $result = $this->Form->postLink('Delete', '/posts/delete/1', ['block' => true]);
        $expected = [
            'a' => ['href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'],
            'Delete',
            '/a',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->View->fetch('postLink');
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/posts/delete/1',
                'name' => 'preg:/post_\w+/', 'style' => 'display:none;',
            ],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/form',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->postLink(
            'Delete',
            '/posts/delete/2',
            ['block' => true, 'method' => 'DELETE']
        );
        $expected = [
            'a' => ['href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'],
            'Delete',
            '/a',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->View->fetch('postLink');
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/posts/delete/1',
                'name' => 'preg:/post_\w+/', 'style' => 'display:none;',
            ],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/form',
            [
                'form' => [
                    'method' => 'post', 'action' => '/posts/delete/2',
                    'name' => 'preg:/post_\w+/', 'style' => 'display:none;',
                ],
            ],
            ['input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'DELETE']],
            '/form',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->postLink('Delete', '/posts/delete/1', ['block' => 'foobar']);
        $expected = [
            'a' => ['href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'],
            'Delete',
            '/a',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->View->fetch('foobar');
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/posts/delete/1',
                'name' => 'preg:/post_\w+/', 'style' => 'display:none;',
            ],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/form',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testSubmitButton method
     */
    public function testSubmitButton(): void
    {
        $result = $this->Form->submit('');
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'submit', 'value' => ''],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->submit('Test Submit');
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'submit', 'value' => 'Test Submit'],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->submit('Next >');
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'submit', 'value' => 'Next &gt;'],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->submit('Next >', ['escape' => false]);
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'submit', 'value' => 'Next >'],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->submit('Reset!', ['type' => 'reset']);
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'reset', 'value' => 'Reset!'],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testSubmitImage method
     *
     * Test image submit types.
     */
    public function testSubmitImage(): void
    {
        $result = $this->Form->submit('http://example.com/cake.power.gif');
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'image', 'src' => 'http://example.com/cake.power.gif'],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->submit('/relative/cake.power.gif');
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'image', 'src' => 'relative/cake.power.gif'],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->submit('cake.power.gif');
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'image', 'src' => 'img/cake.power.gif'],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->submit('Not.an.image');
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'submit', 'value' => 'Not.an.image'],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testSubmitUnlockedByDefault method
     *
     * Submit buttons should be unlocked by default as there could be multiples, and only one will
     * be submitted at a time.
     */
    public function testSubmitUnlockedByDefault(): void
    {
        $this->View->setRequest($this->View->getRequest()->withAttribute('formTokenData', []));
        $this->Form->create();
        $this->Form->submit('Go go');
        $this->Form->submit('Save', ['name' => 'save']);

        $result = $this->Form->getFormProtector()->__debugInfo()['unlockedFields'];
        $this->assertEquals(['save'], $result, 'Only submits with name attributes should be unlocked.');
    }

    /**
     * testSubmitImageTimestamp method
     *
     * Test submit image with timestamps.
     */
    public function testSubmitImageTimestamp(): void
    {
        Configure::write('Asset.timestamp', 'force');

        $result = $this->Form->submit('cake.power.gif');
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'image', 'src' => 'preg:/img\/cake\.power\.gif\?\d*/'],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testDateTimeWithGetForms method
     *
     * Test that datetime() works with GET style forms.
     */
    public function testDateTimeWithGetForms(): void
    {
        $this->Form->create($this->article, ['type' => 'get']);
        $result = $this->Form->datetime('created');
        $expected = [
            'input' => [
                'type' => 'datetime-local',
                'name' => 'created',
                'value' => '',
                'step' => '1',
            ],
        ];

        $this->assertHtml($expected, $result);

        $result = $this->Form->datetime('created', ['default' => true]);
        $expected = [
            'input' => [
                'type' => 'datetime-local',
                'name' => 'created',
                'value' => 'preg:/' . date('Y-m-d') . 'T\d{2}:\d{2}:\d{2}/',
                'step' => '1',
            ],
        ];

        $this->assertHtml($expected, $result);
    }

    /**
     * Provides fractional schema types
     *
     * @return array
     */
    public function fractionalTypeProvider(): array
    {
        return [
            ['datetimefractional'],
            ['timestampfractional'],
            ['timestamptimezone'],
        ];
    }

    /**
     * testDateTimeWithFractional method
     *
     * Test that datetime() works with datetimefractional.
     *
     * @dataProvider fractionalTypeProvider
     */
    public function testDateTimeWithFractional(string $type): void
    {
        $this->Form->create([
            'schema' => [
                'created' => ['type' => $type],
            ],
        ]);
        $result = $this->Form->datetime('created', [
            'val' => new FrozenTime('2019-09-27 02:52:43.123'),
        ]);
        $expected = [
            'input' => [
                'type' => 'datetime-local',
                'name' => 'created',
                'value' => '2019-09-27T02:52:43.123',
                'step' => '0.001',
            ],
        ];

        $this->assertHtml($expected, $result);
    }

    /**
     * testControlWithFractional method
     *
     * Test that control() works with datetimefractional.
     *
     * @dataProvider fractionalTypeProvider
     */
    public function testControlWithFractional(string $type): void
    {
        $this->Form->create([
            'schema' => [
                'created' => ['type' => $type],
            ],
        ]);
        $result = $this->Form->control('created', [
            'val' => new FrozenTime('2019-09-27 02:52:43.123'),
        ]);
        $expected = [
            'div' => ['class' => 'input datetime'],
            'label' => ['for' => 'created'],
            'Created',
            '/label',
            'input' => [
                'type' => 'datetime-local',
                'name' => 'created',
                'id' => 'created',
                'value' => '2019-09-27T02:52:43.123',
                'step' => '0.001',
            ],
            '/div',
        ];

        $this->assertHtml($expected, $result);
    }

    /**
     * testForMagicControlNonExistentNotValidated method
     */
    public function testForMagicControlNonExistentNotValidated(): void
    {
        $this->Form->create($this->article);
        $this->Form->setTemplates(['inputContainer' => '{{content}}']);
        $result = $this->Form->control('nonexistent_not_validated');
        $expected = [
            'label' => ['for' => 'nonexistent-not-validated'],
            'Nonexistent Not Validated',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'nonexistent_not_validated',
                'id' => 'nonexistent-not-validated',
            ],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('nonexistent_not_validated', [
            'val' => 'my value',
        ]);
        $expected = [
            'label' => ['for' => 'nonexistent-not-validated'],
            'Nonexistent Not Validated',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'nonexistent_not_validated',
                'value' => 'my value', 'id' => 'nonexistent-not-validated',
            ],
        ];
        $this->assertHtml($expected, $result);

        $this->View->setRequest(
            $this->View->getRequest()->withData('nonexistent_not_validated', 'CakePHP magic')
        );
        $this->Form->create($this->article);
        $result = $this->Form->control('nonexistent_not_validated');
        $expected = [
            'label' => ['for' => 'nonexistent-not-validated'],
            'Nonexistent Not Validated',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'nonexistent_not_validated',
                'value' => 'CakePHP magic', 'id' => 'nonexistent-not-validated',
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormMagicControlLabel method
     */
    public function testFormMagicControlLabel(): void
    {
        $this->getTableLocator()->get('Contacts', [
            'className' => ContactsTable::class,
        ]);
        $this->Form->create([], ['context' => ['table' => 'Contacts']]);
        $this->Form->setTemplates(['inputContainer' => '{{content}}']);

        $result = $this->Form->control('Contacts.name', ['label' => 'My label']);
        $expected = [
            'label' => ['for' => 'contacts-name'],
            'My label',
            '/label',
            'input' => [
                'type' => 'text',
                'name' => 'Contacts[name]',
                'id' => 'contacts-name',
                'maxlength' => '255',
            ],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('name', [
            'label' => ['class' => 'mandatory'],
        ]);
        $expected = [
            'label' => ['for' => 'name', 'class' => 'mandatory'],
            'Name',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'name',
                'id' => 'name', 'maxlength' => '255',
            ],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('name', [
            'div' => false,
            'label' => ['class' => 'mandatory', 'text' => 'My label'],
        ]);
        $expected = [
            'label' => ['for' => 'name', 'class' => 'mandatory'],
            'My label',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'name',
                'id' => 'name', 'maxlength' => '255',
            ],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('Contact.name', [
            'div' => false, 'id' => 'my_id', 'label' => ['for' => 'my_id'],
        ]);
        $expected = [
            'label' => ['for' => 'my_id'],
            'Name',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'Contact[name]',
                'id' => 'my_id', 'maxlength' => '255',
            ],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('1.id');
        $expected = ['input' => [
            'type' => 'hidden', 'name' => '1[id]',
            'id' => '1-id',
        ]];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('1.name');
        $expected = [
            'label' => ['for' => '1-name'],
            'Name',
            '/label',
            'input' => [
                'type' => 'text', 'name' => '1[name]',
                'id' => '1-name', 'maxlength' => '255',
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormEnd method
     */
    public function testFormEnd(): void
    {
        $this->assertSame('</form>', $this->Form->end());
    }

    /**
     * testMultiRecordForm method
     *
     * Test the generation of fields for a multi record form.
     */
    public function testMultiRecordForm(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->hasMany('Comments');

        $comment = new Entity(['comment' => 'Value']);
        $article = new Article(['comments' => [$comment]]);
        $this->Form->create([$article]);
        $result = $this->Form->control('0.comments.1.comment');
        // phpcs:disable
        $expected = [
            'div' => ['class' => 'input textarea'],
                'label' => ['for' => '0-comments-1-comment'],
                    'Comment',
                '/label',
                'textarea' => [
                    'name',
                    'id' => '0-comments-1-comment',
                    'rows' => 5
                ],
                '/textarea',
            '/div'
        ];
        // phpcs:enable
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('0.comments.0.comment');
        // phpcs:disable
        $expected = [
            'div' => ['class' => 'input textarea'],
                'label' => ['for' => '0-comments-0-comment'],
                    'Comment',
                '/label',
                'textarea' => [
                    'name',
                    'id' => '0-comments-0-comment',
                    'rows' => 5
                ],
                'Value',
                '/textarea',
            '/div'
        ];
        // phpcs:enable
        $this->assertHtml($expected, $result);

        $comment->setError('comment', ['Not valid']);
        $result = $this->Form->control('0.comments.0.comment');
        // phpcs:disable
        $expected = [
            'div' => ['class' => 'input textarea error'],
                'label' => ['for' => '0-comments-0-comment'],
                    'Comment',
                '/label',
                'textarea' => [
                    'name',
                    'class' => 'form-error',
                    'id' => '0-comments-0-comment',
                    'aria-invalid' => 'true',
                    'aria-describedby' => '0-comments-0-comment-error',
                    'rows' => 5
                ],
                'Value',
                '/textarea',
                ['div' => ['class' => 'error-message', 'id' => '0-comments-0-comment-error']],
                'Not valid',
                '/div',
            '/div'
        ];
        // phpcs:enable
        $this->assertHtml($expected, $result);

        $this->getTableLocator()->get('Comments')
            ->getValidator('default')
            ->allowEmptyString('comment', null, false);
        $result = $this->Form->control('0.comments.1.comment');
        // phpcs:disable
        $expected = [
            'div' => ['class' => 'input textarea required'],
                'label' => ['for' => '0-comments-1-comment'],
                    'Comment',
                '/label',
                'textarea' => [
                    'name',
                    'aria-required' => 'true',
                    'required' => 'required',
                    'id' => '0-comments-1-comment',
                    'rows' => 5,
                    'data-validity-message' => 'This field cannot be left empty',
                    'oninvalid' => 'this.setCustomValidity(&#039;&#039;); if (!this.value) this.setCustomValidity(this.dataset.validityMessage)',
                    'oninput' => 'this.setCustomValidity(&#039;&#039;)',
                ],
                '/textarea',
            '/div'
        ];
        // phpcs:enable
        $this->assertHtml($expected, $result);
    }

    /**
     * testHtml5Controls method
     *
     * Test that some html5 inputs + FormHelper::__call() work.
     */
    public function testHtml5Controls(): void
    {
        $result = $this->Form->email('User.email');
        $expected = [
            'input' => ['type' => 'email', 'name' => 'User[email]'],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->search('User.query');
        $expected = [
            'input' => ['type' => 'search', 'name' => 'User[query]'],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->search('User.query', ['value' => 'test']);
        $expected = [
            'input' => ['type' => 'search', 'name' => 'User[query]', 'value' => 'test'],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->search('User.query', ['type' => 'text', 'value' => 'test']);
        $expected = [
            'input' => ['type' => 'text', 'name' => 'User[query]', 'value' => 'test'],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testHtml5ControlWithControl method
     *
     * Test accessing html5 inputs through control().
     */
    public function testHtml5ControlWithControl(): void
    {
        $this->Form->create();
        $this->Form->setTemplates(['inputContainer' => '{{content}}']);
        $result = $this->Form->control('website', [
            'type' => 'url',
            'val' => 'http://domain.tld',
            'label' => false,
        ]);
        $expected = [
            'input' => ['type' => 'url', 'name' => 'website', 'id' => 'website', 'value' => 'http://domain.tld'],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testHtml5ControlException method
     *
     * Test errors when field name is missing.
     */
    public function testHtml5ControlException(): void
    {
        $this->expectException(CakeException::class);
        $this->Form->email();
    }

    /**
     * tests fields that are required use custom validation messages
     */
    public function testHtml5ErrorMessage(): void
    {
        $this->Form->setConfig('autoSetCustomValidity', true);

        $validator = (new Validator())
            ->notEmptyString('email', 'Custom error message')
            ->requirePresence('password')
            ->alphaNumeric('password')
            ->notBlank('phone');

        $table = $this->getTableLocator()->get('Contacts', [
            'className' => ContactsTable::class,
        ]);
        $table->setValidator('default', $validator);
        $contact = new Entity();

        $this->Form->create($contact, ['context' => ['table' => 'Contacts']]);
        $this->Form->setTemplates(['inputContainer' => '{{content}}']);

        $result = $this->Form->control('password');
        $expected = [
            'label' => ['for' => 'password'],
            'Password',
            '/label',
            'input' => [
                'aria-required' => 'true',
                'id' => 'password',
                'name' => 'password',
                'type' => 'password',
                'value' => '',
                'required' => 'required',
                'data-validity-message' => 'This field cannot be left empty',
                'oninput' => 'this.setCustomValidity(&#039;&#039;)',
                'oninvalid' => 'this.setCustomValidity(&#039;&#039;); if (!this.value) this.setCustomValidity(this.dataset.validityMessage)',
            ],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('phone');
        $expected = [
            'label' => ['for' => 'phone'],
            'Phone',
            '/label',
            'input' => [
                'aria-required' => 'true',
                'id' => 'phone',
                'name' => 'phone',
                'type' => 'tel',
                'value' => '',
                'maxlength' => 255,
                'required' => 'required',
                'data-validity-message' => 'This field cannot be left empty',
                'oninput' => 'this.setCustomValidity(&#039;&#039;)',
                'oninvalid' => 'this.setCustomValidity(&#039;&#039;); if (!this.value) this.setCustomValidity(this.dataset.validityMessage)',
            ],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('email');
        $expected = [
            'label' => ['for' => 'email'],
            'Email',
            '/label',
            'input' => [
                'aria-required' => 'true',
                'id' => 'email',
                'name' => 'email',
                'type' => 'email',
                'value' => '',
                'maxlength' => 255,
                'required' => 'required',
                'data-validity-message' => 'Custom error message',
                'oninput' => 'this.setCustomValidity(&#039;&#039;)',
                'oninvalid' => 'this.setCustomValidity(&#039;&#039;); if (!this.value) this.setCustomValidity(this.dataset.validityMessage)',
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * tests that custom validation messages are in templateVars
     */
    public function testHtml5ErrorMessageInTemplateVars(): void
    {
        $validator = (new Validator())
            ->notEmptyString('email', 'Custom error "message" & entities')
            ->requirePresence('password')
            ->alphaNumeric('password')
            ->notBlank('phone');

        $table = $this->getTableLocator()->get('Contacts', [
            'className' => ContactsTable::class,
        ]);
        $table->setValidator('default', $validator);
        $contact = new Entity();

        $this->Form->setConfig('autoSetCustomValidity', false);
        $this->Form->create($contact, ['context' => ['table' => 'Contacts']]);
        $this->Form->setTemplates([
            'input' => '<input type="{{type}}" name="{{name}}"{{attrs}} data-message="{{customValidityMessage}}" {{custom}}/>',
            'inputContainer' => '{{content}}',
        ]);

        $result = $this->Form->control('password');
        $expected = [
            'label' => ['for' => 'password'],
            'Password',
            '/label',
            'input' => [
                'aria-required' => 'true',
                'id' => 'password',
                'name' => 'password',
                'type' => 'password',
                'value' => '',
                'required' => 'required',
                'data-message' => 'This field cannot be left empty',
            ],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('phone');
        $expected = [
            'label' => ['for' => 'phone'],
            'Phone',
            '/label',
            'input' => [
                'aria-required' => 'true',
                'id' => 'phone',
                'name' => 'phone',
                'type' => 'tel',
                'value' => '',
                'maxlength' => 255,
                'required' => 'required',
                'data-message' => 'This field cannot be left empty',
            ],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('email', [
            'templateVars' => [
                'custom' => 'data-custom="1"',
            ],
        ]);
        $expected = [
            'label' => ['for' => 'email'],
            'Email',
            '/label',
            'input' => [
                'aria-required' => 'true',
                'id' => 'email',
                'name' => 'email',
                'type' => 'email',
                'value' => '',
                'maxlength' => 255,
                'required' => 'required',
                'data-message' => 'Custom error &quot;message&quot; &amp; entities',
                'data-custom' => '1',
            ],
        ];
        $this->assertHtml($expected, $result);

        $this->Form->setConfig('autoSetCustomValidity', true);
    }

    /**
     * testRequiredAttribute method
     *
     * Tests that formhelper sets required attributes.
     */
    public function testRequiredAttribute(): void
    {
        $this->article['required'] = [
            'title' => true,
            'body' => false,
        ];
        $this->Form->create($this->article);

        $result = $this->Form->control('title');
        $expected = [
            'div' => ['class' => 'input text required'],
            'label' => ['for' => 'title'],
            'Title',
            '/label',
            'input' => [
                'type' => 'text',
                'name' => 'title',
                'id' => 'title',
                'aria-required' => 'true',
                'required' => 'required',
                'data-validity-message' => 'This field cannot be left empty',
                'oninvalid' => 'this.setCustomValidity(&#039;&#039;); if (!this.value) this.setCustomValidity(this.dataset.validityMessage)',
                'oninput' => 'this.setCustomValidity(&#039;&#039;)',
            ],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('title', ['required' => false]);
        $this->assertStringNotContainsString('required', $result);

        $result = $this->Form->control('body');
        $expected = [
            'div' => ['class' => 'input text'],
            'label' => ['for' => 'body'],
            'Body',
            '/label',
            'input' => [
                'type' => 'text',
                'name' => 'body',
                'id' => 'body',
            ],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('body', ['required' => true]);
        $this->assertStringContainsString('required', $result);
    }

    /**
     * testControlsNotNested method
     *
     * Tests that it is possible to put inputs outside of the label.
     */
    public function testControlsNotNested(): void
    {
        $this->Form->setTemplates([
            'nestingLabel' => '{{hidden}}{{input}}<label{{attrs}}>{{text}}</label>',
            'formGroup' => '{{input}}{{label}}',
        ]);
        $result = $this->Form->control('foo', ['type' => 'checkbox']);
        $expected = [
            'div' => ['class' => 'input checkbox'],
            ['input' => ['type' => 'hidden', 'name' => 'foo', 'value' => '0']],
            ['input' => ['type' => 'checkbox', 'name' => 'foo', 'id' => 'foo', 'value' => '1']],
            'label' => ['for' => 'foo'],
                'Foo',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('foo', ['type' => 'checkbox', 'label' => false]);
        $expected = [
            'div' => ['class' => 'input checkbox'],
            ['input' => ['type' => 'hidden', 'name' => 'foo', 'value' => '0']],
            ['input' => ['type' => 'checkbox', 'name' => 'foo', 'id' => 'foo', 'value' => '1']],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('confirm', [
            'type' => 'radio',
            'options' => ['Y' => 'Yes', 'N' => 'No'],
        ]);
        $expected = [
            'div' => ['class' => 'input radio'],
            ['input' => ['type' => 'hidden', 'name' => 'confirm', 'value' => '', 'id' => 'confirm']],
            ['input' => ['type' => 'radio', 'name' => 'confirm', 'id' => 'confirm-y', 'value' => 'Y']],
            ['label' => ['for' => 'confirm-y']],
            'Yes',
            '/label',
            ['input' => ['type' => 'radio', 'name' => 'confirm', 'id' => 'confirm-n', 'value' => 'N']],
            ['label' => ['for' => 'confirm-n']],
            'No',
            '/label',
            '<label',
            'Confirm',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->select('category', ['1', '2'], [
            'multiple' => 'checkbox',
            'name' => 'fish',
        ]);
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'fish', 'value' => '', 'id' => 'category'],
            ['div' => ['class' => 'checkbox']],
                ['input' => ['type' => 'checkbox', 'name' => 'fish[]', 'value' => '0', 'id' => 'fish-0']],
                ['label' => ['for' => 'fish-0']],
                    '1',
                '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
                ['input' => ['type' => 'checkbox', 'name' => 'fish[]', 'value' => '1', 'id' => 'fish-1']],
                ['label' => ['for' => 'fish-1']],
                    '2',
                '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testControlContainerTemplates method
     *
     * Test that *Container templates are used by input.
     */
    public function testControlContainerTemplates(): void
    {
        $this->Form->setTemplates([
            'checkboxContainer' => '<div class="check">{{content}}</div>',
            'radioContainer' => '<div class="rad">{{content}}</div>',
            'radioContainerError' => '<div class="rad err">{{content}}</div>',
        ]);

        $this->article['errors'] = [
            'Article' => ['published' => 'error message'],
        ];
        $this->Form->create($this->article);

        $result = $this->Form->control('accept', [
            'type' => 'checkbox',
        ]);
        $expected = [
            'div' => ['class' => 'check'],
            ['input' => ['type' => 'hidden', 'name' => 'accept', 'value' => 0]],
            'label' => ['for' => 'accept'],
            ['input' => ['id' => 'accept', 'type' => 'checkbox', 'name' => 'accept', 'value' => 1]],
            'Accept',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('accept', [
            'type' => 'radio',
            'options' => ['Y', 'N'],
        ]);
        $this->assertStringContainsString('<div class="rad">', $result);

        $result = $this->Form->control('Article.published', [
            'type' => 'radio',
            'options' => ['Y', 'N'],
        ]);
        $this->assertStringContainsString('<div class="rad err">', $result);
    }

    /**
     * testFormGroupTemplates method
     *
     * Test that *Container templates are used by input.
     */
    public function testFormGroupTemplates(): void
    {
        $this->Form->setTemplates([
            'radioFormGroup' => '<div class="radio">{{label}}{{input}}</div>',
        ]);

        $this->Form->create($this->article);

        $result = $this->Form->control('accept', [
            'type' => 'radio',
            'options' => ['Y', 'N'],
        ]);
        $this->assertStringContainsString('<div class="radio">', $result);
    }

    /**
     * testResetTemplates method
     *
     * Test resetting templates.
     */
    public function testResetTemplates(): void
    {
        $this->Form->setTemplates(['input' => '<input/>']);
        $this->assertSame('<input/>', $this->Form->templater()->get('input'));

        $this->Form->resetTemplates();
        $this->assertNotEquals('<input/>', $this->Form->templater()->get('input'));
    }

    /**
     * testContext method
     *
     * Test the context method.
     */
    public function testContext(): void
    {
        $result = $this->Form->context();
        $this->assertInstanceOf('Cake\View\Form\ContextInterface', $result);

        $mock = $this->getMockBuilder('Cake\View\Form\ContextInterface')->getMock();
        $this->assertSame($mock, $this->Form->context($mock));
        $this->assertSame($mock, $this->Form->context());
    }

    /**
     * testAutoDomId method
     */
    public function testAutoDomId(): void
    {
        $result = $this->Form->text('field', ['id' => true]);
        $expected = [
            'input' => ['type' => 'text', 'name' => 'field', 'id' => 'field'],
        ];
        $this->assertHtml($expected, $result);

        // Ensure id => doesn't cause problem when multiple inputs are generated.
        $result = $this->Form->radio('field', ['option A', 'option B'], ['id' => true]);
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'field', 'value' => '', 'id' => 'field'],
            ['label' => ['for' => 'field-0']],
            ['input' => ['type' => 'radio', 'name' => 'field', 'value' => '0', 'id' => 'field-0']],
            'option A',
            '/label',
            ['label' => ['for' => 'field-1']],
            ['input' => ['type' => 'radio', 'name' => 'field', 'value' => '1', 'id' => 'field-1']],
            'option B',
            '/label',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->select(
            'multi_field',
            ['first', 'second'],
            ['multiple' => 'checkbox', 'id' => true]
        );
        $expected = [
            'input' => [
                'type' => 'hidden', 'name' => 'multi_field', 'value' => '', 'id' => 'multi-field',
            ],
            ['div' => ['class' => 'checkbox']],
                ['label' => ['for' => 'multi-field-0']],
                    ['input' => [
                        'type' => 'checkbox', 'name' => 'multi_field[]',
                        'value' => '0', 'id' => 'multi-field-0',
                    ]],
                    'first',
                    '/label',
                    '/div',
                    ['div' => ['class' => 'checkbox']],
                    ['label' => ['for' => 'multi-field-1']],
                    ['input' => [
                        'type' => 'checkbox', 'name' => 'multi_field[]',
                        'value' => '1', 'id' => 'multi-field-1',
                    ]],
                    'second',
                    '/label',
                    '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test the basic setters and getters for value sources
     */
    public function testFormValueSourcesSettersGetters(): void
    {
        $this->View->setRequest($this->View->getRequest()
            ->withData('id', '1')
            ->withQueryParams(['id' => '2']));

        $expected = ['data', 'context'];
        $result = $this->Form->getValueSources();
        $this->assertEquals($expected, $result);

        $this->Form->setValueSources(['context']);
        $result = $this->Form->getSourceValue('id');
        $this->assertNull($result);

        $this->Form->setValueSources('query');
        $expected = ['query'];
        $result = $this->Form->getValueSources();
        $this->assertEquals($expected, $result);

        $expected = '2';
        $result = $this->Form->getSourceValue('id');
        $this->assertSame($expected, $result);

        $this->Form->setValueSources(['data']);
        $expected = '1';
        $result = $this->Form->getSourceValue('id');
        $this->assertSame($expected, $result);

        $this->Form->setValueSources(['query', 'data']);
        $expected = '2';
        $result = $this->Form->getSourceValue('id');
        $this->assertSame($expected, $result);
    }

    public function testValueSourcesValidation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value source(s): invalid, foo. Valid values are: context, data, query');

        $this->Form->setValueSources(['query', 'data', 'invalid', 'context', 'foo']);
    }

    /**
     * Tests the different input rendering values based on sources values switching
     */
    public function testFormValueSourcesSingleSwitchRendering(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $article = new Article();
        $articles->patchEntity($article, ['id' => '3']);

        $this->Form->create($article);
        $this->Form->setValueSources(['context']);
        $result = $this->Form->control('id');
        $expected = [
            ['input' => ['type' => 'hidden', 'name' => 'id', 'id' => 'id', 'value' => '3']],
        ];
        $this->assertHtml($expected, $result);

        $this->View->setRequest($this->View->getRequest()->withQueryParams(['id' => 5]));
        $this->Form->setValueSources(['query']);
        $result = $this->Form->control('id');
        $expected = [
            ['input' => ['type' => 'hidden', 'name' => 'id', 'id' => 'id', 'value' => '5']],
        ];
        $this->assertHtml($expected, $result);

        $this->View->setRequest($this->View->getRequest()
            ->withQueryParams(['id' => '5a'])
            ->withData('id', '5b'));

        $this->Form->setValueSources(['context']);
        $this->Form->create($article);
        $result = $this->Form->control('id');
        $expected = [
            ['input' => ['type' => 'hidden', 'name' => 'id', 'id' => 'id', 'value' => '3']],
        ];
        $this->assertHtml($expected, $result);

        $this->Form->setValueSources(['data']);
        $this->Form->create($article);
        $result = $this->Form->control('id');
        $expected = [
            ['input' => ['type' => 'hidden', 'name' => 'id', 'id' => 'id', 'value' => '5b']],
        ];
        $this->assertHtml($expected, $result);

        $this->Form->setValueSources(['query']);
        $result = $this->Form->control('id');
        $expected = [
            ['input' => ['type' => 'hidden', 'name' => 'id', 'id' => 'id', 'value' => '5a']],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Tests the different input rendering values based on sources values switching while supplying
     * an entity (base context) and multiple sources (such as data, query)
     */
    public function testFormValueSourcesListSwitchRendering(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $article = new Article();
        $articles->patchEntity($article, ['id' => '3']);
        $this->View->setRequest($this->View->getRequest()->withQueryParams(['id' => '9']));

        $this->Form->create($article);
        $this->Form->setValueSources(['context', 'query']);
        $result = $this->Form->control('id');
        $expected = [
            ['input' => ['type' => 'hidden', 'name' => 'id', 'id' => 'id', 'value' => '3']],
        ];
        $this->assertHtml($expected, $result);

        $this->Form->setValueSources(['query', 'context']);
        $result = $this->Form->control('id');
        $expected = [
            ['input' => ['type' => 'hidden', 'name' => 'id', 'id' => 'id', 'value' => '9']],
        ];
        $this->assertHtml($expected, $result);

        $this->Form->setValueSources(['data', 'query', 'context']);
        $result = $this->Form->control('id');
        $expected = [
            ['input' => ['type' => 'hidden', 'name' => 'id', 'id' => 'id', 'value' => '9']],
        ];
        $this->assertHtml($expected, $result);

        $this->View->setRequest($this->View->getRequest()
            ->withData('id', '8')
            ->withQueryParams(['id' => '9']));
        $this->Form->setValueSources(['data', 'query', 'context']);
        $result = $this->Form->control('id');
        $expected = [
            ['input' => ['type' => 'hidden', 'name' => 'id', 'id' => 'id', 'value' => '8']],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test the different form input renderings based on values sources switchings through form options
     */
    public function testFormValueSourcesSwitchViaOptionsRendering(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $article = new Article();
        $articles->patchEntity($article, ['id' => '3']);

        $this->View->setRequest(
            $this->View->getRequest()->withData('id', '4')->withQueryParams(['id' => '5'])
        );

        $this->Form->create($article, ['valueSources' => 'query']);
        $result = $this->Form->control('id');
        $expected = [
            ['input' => ['type' => 'hidden', 'name' => 'id', 'id' => 'id', 'value' => '5']],
        ];
        $this->assertHtml($expected, $result);
        $result = $this->Form->getSourceValue('id');
        $this->assertSame('5', $result);

        $this->Form->setValueSources(['context']);
        $this->Form->create($article, ['valueSources' => 'query']);
        $result = $this->Form->control('id');
        $expected = [
            ['input' => ['type' => 'hidden', 'name' => 'id', 'id' => 'id', 'value' => '5']],
        ];
        $this->assertHtml($expected, $result);
        $result = $this->Form->getSourceValue('id');
        $this->assertSame('5', $result);

        $this->Form->setValueSources(['query']);
        $this->Form->create($article, ['valueSources' => 'data']);
        $result = $this->Form->control('id');
        $expected = [
            ['input' => ['type' => 'hidden', 'name' => 'id', 'id' => 'id', 'value' => '4']],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->getSourceValue('id');
        $this->assertSame('4', $result);

        $this->Form->setValueSources(['query']);
        $this->Form->create($article, ['valueSources' => ['context', 'data']]);
        $result = $this->Form->control('id');
        $expected = [
            ['input' => ['type' => 'hidden', 'name' => 'id', 'id' => 'id', 'value' => '3']],
        ];
        $this->assertHtml($expected, $result);
        $result = $this->Form->getSourceValue('id');
        $this->assertSame(3, $result);
    }

    /**
     * Test the different form input renderings based on values sources switchings through form options
     */
    public function testFormValueSourcesSwitchViaOptionsAndSetterRendering(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $article = new Article();
        $articles->patchEntity($article, ['id' => '3']);

        $this->View->setRequest(
            $this->View->getRequest()->withData('id', '10')->withQueryParams(['id' => '11'])
        );

        $this->Form->setValueSources(['context'])
            ->create($article, ['valueSources' => ['query', 'data']]);
        $result = $this->Form->control('id');
        $expected = [
            ['input' => ['type' => 'hidden', 'name' => 'id', 'id' => 'id', 'value' => '11']],
        ];
        $this->assertHtml($expected, $result);
        $result = $this->Form->getSourceValue('id');
        $this->assertSame('11', $result);

        $this->View->setRequest($this->View->getRequest()->withQueryParams([]));
        $this->Form->setValueSources(['context'])
            ->create($article, ['valueSources' => ['query', 'data']]);
        $result = $this->Form->control('id');
        $expected = [
            ['input' => ['type' => 'hidden', 'name' => 'id', 'id' => 'id', 'value' => '10']],
        ];
        $this->assertHtml($expected, $result);
        $result = $this->Form->getSourceValue('id');
        $this->assertSame('10', $result);
    }

    /**
     * Test the different form values sources resetting through From::end();
     */
    public function testFormValueSourcesResetViaEnd(): void
    {
        $expected = ['data', 'context'];
        $result = $this->Form->getValueSources();
        $this->assertEquals($expected, $result);

        $expected = ['query', 'context', 'data'];
        $this->Form->setValueSources(['query', 'context', 'data']);

        $result = $this->Form->getValueSources();
        $this->assertEquals($expected, $result);

        $this->Form->create();
        $result = $this->Form->getValueSources();
        $this->assertEquals($expected, $result);

        $this->Form->end();
        $result = $this->Form->getValueSources();
        $this->assertEquals(['data', 'context'], $result);
    }

    /**
     * Test sources values defaults handling
     */
    public function testFormValueSourcesDefaults(): void
    {
        $this->View->setRequest(
            $this->View->getRequest()->withQueryParams(['password' => 'open Sesame'])
        );
        $this->Form->create();

        $result = $this->Form->password('password');
        $expected = ['input' => ['type' => 'password', 'name' => 'password']];
        $this->assertHtml($expected, $result);

        $result = $this->Form->password('password', ['default' => 'helloworld']);
        $expected = ['input' => ['type' => 'password', 'name' => 'password', 'value' => 'helloworld']];
        $this->assertHtml($expected, $result);

        $this->Form->setValueSources('query');
        $result = $this->Form->password('password', ['default' => 'helloworld']);
        $expected = ['input' => ['type' => 'password', 'name' => 'password', 'value' => 'open Sesame']];
        $this->assertHtml($expected, $result);

        $this->Form->setValueSources('data');
        $result = $this->Form->password('password', ['default' => 'helloworld']);
        $expected = ['input' => ['type' => 'password', 'name' => 'password', 'value' => 'helloworld']];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test sources values schema defaults handling
     */
    public function testSourcesValueDoesntExistPassThrough(): void
    {
        $this->View->setRequest($this->View->getRequest()->withQueryParams(['category' => 'sesame-cookies']));

        $articles = $this->getTableLocator()->get('Articles');
        $entity = $articles->newEmptyEntity();
        $this->Form->create($entity);

        $this->Form->setValueSources(['query', 'context']);
        $result = $this->Form->getSourceValue('category');
        $this->assertSame('sesame-cookies', $result);

        $this->Form->setValueSources(['context', 'query']);
        $result = $this->Form->getSourceValue('category');
        $this->assertSame('sesame-cookies', $result);
    }

    /**
     * testNestedLabelInput method
     *
     * Test the `nestedInput` parameter
     */
    public function testNestedLabelInput(): void
    {
        $result = $this->Form->control('foo', ['nestedInput' => true]);
        $expected = [
            'div' => ['class' => 'input text'],
            'label' => ['for' => 'foo'],
            ['input' => [
                'type' => 'text',
                'name' => 'foo',
                'id' => 'foo',
            ]],
            'Foo',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Tests to make sure `labelOptions` is rendered correctly by MultiCheckboxWidget and RadioWidget
     *
     * This test makes sure `false` excludes the label from the render
     */
    public function testControlLabelManipulationDisableLabels(): void
    {
        $result = $this->Form->control('test', [
            'type' => 'radio',
            'options' => ['A', 'B'],
            'labelOptions' => false,
        ]);
        $expected = [
            ['div' => ['class' => 'input radio']],
            '<label',
            'Test',
            '/label',
            ['input' => ['type' => 'hidden', 'name' => 'test', 'value' => '', 'id' => 'test']],
            ['input' => ['type' => 'radio', 'name' => 'test', 'value' => '0', 'id' => 'test-0']],
            ['input' => ['type' => 'radio', 'name' => 'test', 'value' => '1', 'id' => 'test-1']],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('checkbox1', [
            'label' => 'My checkboxes',
            'multiple' => 'checkbox',
            'type' => 'select',
            'options' => [
                ['text' => 'First Checkbox', 'value' => 1],
                ['text' => 'Second Checkbox', 'value' => 2],
            ],
            'labelOptions' => false,
        ]);
        $expected = [
            ['div' => ['class' => 'input select']],
            ['label' => ['for' => 'checkbox1']],
            'My checkboxes',
            '/label',
            'input' => ['type' => 'hidden', 'name' => 'checkbox1', 'value' => '', 'id' => 'checkbox1'],
            ['div' => ['class' => 'checkbox']],
            ['input' => ['type' => 'checkbox', 'name' => 'checkbox1[]', 'value' => '1', 'id' => 'checkbox1-1']],
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['input' => ['type' => 'checkbox', 'name' => 'checkbox1[]', 'value' => '2', 'id' => 'checkbox1-2']],
            '/div',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Tests to make sure `labelOptions` is rendered correctly by RadioWidget
     *
     * This test checks rendering of class (as string and array) also makes sure 'selected' is
     * added to the class if checked.
     *
     * Also checks to make sure any custom attributes are rendered correctly
     */
    public function testControlLabelManipulationRadios(): void
    {
        $result = $this->Form->control('test', [
            'type' => 'radio',
            'options' => ['A', 'B'],
            'labelOptions' => ['class' => 'custom-class'],
        ]);
        $expected = [
            ['div' => ['class' => 'input radio']],
            '<label',
            'Test',
            '/label',
            ['input' => ['type' => 'hidden', 'name' => 'test', 'value' => '', 'id' => 'test']],
            ['label' => ['for' => 'test-0', 'class' => 'custom-class']],
            ['input' => ['type' => 'radio', 'name' => 'test', 'value' => '0', 'id' => 'test-0']],
            'A',
            '/label',
            ['label' => ['for' => 'test-1', 'class' => 'custom-class']],
            ['input' => ['type' => 'radio', 'name' => 'test', 'value' => '1', 'id' => 'test-1']],
            'B',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('test', [
            'type' => 'radio',
            'options' => ['A', 'B'],
            'value' => 1,
            'labelOptions' => ['class' => 'custom-class'],
        ]);
        $expected = [
            ['div' => ['class' => 'input radio']],
            '<label',
            'Test',
            '/label',
            ['input' => ['type' => 'hidden', 'name' => 'test', 'value' => '', 'id' => 'test']],
            ['label' => ['for' => 'test-0', 'class' => 'custom-class']],
            ['input' => ['type' => 'radio', 'name' => 'test', 'value' => '0', 'id' => 'test-0']],
            'A',
            '/label',
            ['label' => ['for' => 'test-1', 'class' => 'custom-class selected']],
            ['input' => ['type' => 'radio', 'name' => 'test', 'value' => '1', 'id' => 'test-1', 'checked' => 'checked']],
            'B',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('test', [
            'type' => 'radio',
            'options' => ['A', 'B'],
            'value' => 1,
            'labelOptions' => ['class' => ['custom-class', 'custom-class-array']],
        ]);
        $expected = [
            ['div' => ['class' => 'input radio']],
            '<label',
            'Test',
            '/label',
            ['input' => ['type' => 'hidden', 'name' => 'test', 'value' => '', 'id' => 'test']],
            ['label' => ['for' => 'test-0', 'class' => 'custom-class custom-class-array']],
            ['input' => ['type' => 'radio', 'name' => 'test', 'value' => '0', 'id' => 'test-0']],
            'A',
            '/label',
            ['label' => ['for' => 'test-1', 'class' => 'custom-class custom-class-array selected']],
            ['input' => ['type' => 'radio', 'name' => 'test', 'value' => '1', 'id' => 'test-1', 'checked' => 'checked']],
            'B',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->radio('test', ['A', 'B'], [
            'label' => [
                'class' => ['custom-class', 'another-class'],
                'data-name' => 'bob',
            ],
            'value' => 1,
        ]);
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'test', 'value' => '', 'id' => 'test'],
            ['label' => ['class' => 'custom-class another-class', 'data-name' => 'bob', 'for' => 'test-0']],
            ['input' => ['type' => 'radio', 'name' => 'test', 'value' => '0', 'id' => 'test-0']],
            'A',
            '/label',
            ['label' => ['class' => 'custom-class another-class selected', 'data-name' => 'bob', 'for' => 'test-1']],
            ['input' => [
                'type' => 'radio',
                'name' => 'test',
                'value' => '1',
                'id' => 'test-1',
                'checked' => 'checked',
            ]],
            'B',
            '/label',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Tests to make sure `labelOptions` is rendered correctly by MultiCheckboxWidget
     *
     * This test checks rendering of class (as string and array) also makes sure 'selected' is
     * added to the class if checked.
     *
     * Also checks to make sure any custom attributes are rendered correctly
     */
    public function testControlLabelManipulationCheckboxes(): void
    {
        $result = $this->Form->control('checkbox1', [
            'label' => 'My checkboxes',
            'multiple' => 'checkbox',
            'type' => 'select',
            'options' => [
                ['text' => 'First Checkbox', 'value' => 1],
                ['text' => 'Second Checkbox', 'value' => 2],
            ],
            'labelOptions' => ['class' => 'custom-class'],
            'value' => ['1'],
        ]);
        $expected = [
            ['div' => ['class' => 'input select']],
            ['label' => ['for' => 'checkbox1']],
            'My checkboxes',
            '/label',
            'input' => ['type' => 'hidden', 'name' => 'checkbox1', 'value' => '', 'id' => 'checkbox1'],
            ['div' => ['class' => 'checkbox']],
            ['label' => [
                'class' => 'custom-class selected',
                'for' => 'checkbox1-1',
            ]],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'checkbox1[]',
                'value' => '1',
                'id' => 'checkbox1-1',
                'checked' => 'checked',
            ]],
            'First Checkbox',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['label' => [
                'class' => 'custom-class',
                'for' => 'checkbox1-2',
            ]],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'checkbox1[]',
                'value' => '2',
                'id' => 'checkbox1-2',
            ]],
            'Second Checkbox',
            '/label',
            '/div',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->control('checkbox1', [
            'label' => 'My checkboxes',
            'multiple' => 'checkbox',
            'type' => 'select',
            'options' => [
                ['text' => 'First Checkbox', 'value' => 1],
                ['text' => 'Second Checkbox', 'value' => 2],
            ],
            'labelOptions' => ['class' => ['custom-class', 'another-class'], 'data-name' => 'bob'],
            'value' => ['1'],
        ]);
        $expected = [
            ['div' => ['class' => 'input select']],
            ['label' => ['for' => 'checkbox1']],
            'My checkboxes',
            '/label',
            'input' => ['type' => 'hidden', 'name' => 'checkbox1', 'value' => '', 'id' => 'checkbox1'],
            ['div' => ['class' => 'checkbox']],
            ['label' => [
                'class' => 'custom-class another-class selected',
                'data-name' => 'bob',
                'for' => 'checkbox1-1',
            ]],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'checkbox1[]',
                'value' => '1',
                'id' => 'checkbox1-1',
                'checked' => 'checked',
            ]],
            'First Checkbox',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['label' => [
                'class' => 'custom-class another-class',
                'data-name' => 'bob',
                'for' => 'checkbox1-2',
            ]],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'checkbox1[]',
                'value' => '2',
                'id' => 'checkbox1-2',
            ]],
            'Second Checkbox',
            '/label',
            '/div',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testControlMaxLengthArrayContext method
     *
     * Test control() with maxlength attribute in Array Context.
     */
    public function testControlMaxLengthArrayContext(): void
    {
        $this->article['schema'] = [
            'title' => ['length' => 10],
        ];

        $this->Form->create($this->article);
        $result = $this->Form->control('title');
        $expected = [
            'div' => ['class'],
            'label' => ['for'],
            'Title',
            '/label',
            'input' => [
                'aria-required' => 'true',
                'id',
                'name' => 'title',
                'type' => 'text',
                'required' => 'required',
                'maxlength' => 10,
                'data-validity-message' => 'This field cannot be left empty',
                'oninvalid' => 'this.setCustomValidity(&#039;&#039;); if (!this.value) this.setCustomValidity(this.dataset.validityMessage)',
                'oninput' => 'this.setCustomValidity(&#039;&#039;)',
            ],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testControlMaxLengthEntityContext method
     *
     * Test control() with maxlength attribute in Entity Context.
     */
    public function testControlMaxLengthEntityContext(): void
    {
        $this->article['schema']['title']['length'] = 45;

        $validator = new Validator();
        $validator->maxLength('title', 10);
        $article = new EntityContext(
            [
                'entity' => new Entity($this->article),
                'table' => new Table([
                    'alias' => 'Articles',
                    'schema' => $this->article['schema'],
                    'validator' => $validator,
                ]),
            ]
        );

        $this->Form->create($article);
        $result = $this->Form->control('title');
        $expected = [
            'div' => ['class'],
            'label' => ['for'],
            'Title',
            '/label',
            'input' => [
                'aria-required' => 'true',
                'id',
                'name' => 'title',
                'type' => 'text',
                'required' => 'required',
                'maxlength' => 10,
                'data-validity-message' => 'This field cannot be left empty',
                'oninvalid' => 'this.setCustomValidity(&#039;&#039;); if (!this.value) this.setCustomValidity(this.dataset.validityMessage)',
                'oninput' => 'this.setCustomValidity(&#039;&#039;)',
            ],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $this->article['schema']['title']['length'] = 45;
        $validator = new Validator();
        $validator->maxLength('title', 55);
        $article = new EntityContext(
            [
                'entity' => new Entity($this->article),
                'table' => new Table([
                    'schema' => $this->article['schema'],
                    'validator' => $validator,
                    'alias' => 'Articles',
                ]),

            ]
        );

        $this->Form->create($article);
        $result = $this->Form->control('title');
        $expected = [
            'div' => ['class'],
            'label' => ['for'],
            'Title',
            '/label',
            'input' => [
                'aria-required' => 'true',
                'id',
                'name' => 'title',
                'type' => 'text',
                'required' => 'required',
                'maxlength' => 55, // Length set in validator should take precedence over schema.
                'data-validity-message' => 'This field cannot be left empty',
                'oninvalid' => 'this.setCustomValidity(&#039;&#039;); if (!this.value) this.setCustomValidity(this.dataset.validityMessage)',
                'oninput' => 'this.setCustomValidity(&#039;&#039;)',
            ],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $this->article['schema']['title']['length'] = 45;
        $validator = new Validator();
        $validator->maxLength('title', 55);
        $article = new EntityContext(
            [
                'entity' => new Entity($this->article),
                'table' => new Table([
                    'schema' => $this->article['schema'],
                    'validator' => $validator,
                    'alias' => 'Articles',
                ]),

            ]
        );

        $this->Form->create($article);
        $result = $this->Form->control('title', ['maxlength' => 10]);
        $expected = [
            'div' => ['class'],
            'label' => ['for'],
            'Title',
            '/label',
            'input' => [
                'aria-required' => 'true',
                'id',
                'name' => 'title',
                'type' => 'text',
                'required' => 'required',
                'maxlength' => 10, // Length set in options should take highest precedence.
                'data-validity-message' => 'This field cannot be left empty',
                'oninvalid' => 'this.setCustomValidity(&#039;&#039;); if (!this.value) this.setCustomValidity(this.dataset.validityMessage)',
                'oninput' => 'this.setCustomValidity(&#039;&#039;)',
            ],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testControlMinMaxLengthEntityContext method
     *
     * Test control() with maxlength attribute in Entity Context sets the minimum val.
     */
    public function testControlMinMaxLengthEntityContext(): void
    {
        $validator = new Validator();
        $validator->maxLength('title', 10);
        $article = new EntityContext(
            [
                'entity' => new Entity($this->article),
                'table' => new Table([
                    'alias' => 'Articles',
                    'schema' => $this->article['schema'],
                    'validator' => $validator,
                ]),
            ]
        );

        $this->Form->create($article);
        $result = $this->Form->control('title');
        $expected = [
            'div' => ['class'],
            'label' => ['for'],
            'Title',
            '/label',
            'input' => [
                'aria-required' => 'true',
                'id',
                'name' => 'title',
                'type' => 'text',
                'required' => 'required',
                'maxlength' => 10,
                'data-validity-message' => 'This field cannot be left empty',
                'oninvalid' => 'this.setCustomValidity(&#039;&#039;); if (!this.value) this.setCustomValidity(this.dataset.validityMessage)',
                'oninput' => 'this.setCustomValidity(&#039;&#039;)',
            ],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testControlMaxLengthFormContext method
     *
     * Test control() with maxlength attribute in Form Context.
     */
    public function testControlMaxLengthFormContext(): void
    {
        $validator = new Validator();
        $validator->maxLength('title', 10);
        $form = new Form();
        $form->setValidator('default', $validator);

        $this->Form->create($form);
        $result = $this->Form->control('title');
        $expected = [
            'div' => ['class'],
            'label' => ['for'],
            'Title',
            '/label',
            'input' => [
                'aria-required' => 'true',
                'id',
                'name' => 'title',
                'type' => 'text',
                'required' => 'required',
                'maxlength' => 10,
                'data-validity-message' => 'This field cannot be left empty',
                'oninvalid' => 'this.setCustomValidity(&#039;&#039;); if (!this.value) this.setCustomValidity(this.dataset.validityMessage)',
                'oninput' => 'this.setCustomValidity(&#039;&#039;)',
            ],
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }
}
