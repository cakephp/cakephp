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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View\Helper;

use Cake\Collection\Collection;
use Cake\Core\Configure;
use Cake\Form\Form;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;
use Cake\View\Helper\FormHelper;
use Cake\View\View;

/**
 * Test stub.
 */
class Article extends Entity
{
}

/**
 * Contact class
 */
class ContactsTable extends Table
{

    /**
     * Default schema
     *
     * @var array
     */
    protected $_schema = [
        'id' => ['type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'],
        'name' => ['type' => 'string', 'null' => '', 'default' => '', 'length' => '255'],
        'email' => ['type' => 'string', 'null' => '', 'default' => '', 'length' => '255'],
        'phone' => ['type' => 'string', 'null' => '', 'default' => '', 'length' => '255'],
        'password' => ['type' => 'string', 'null' => '', 'default' => '', 'length' => '255'],
        'published' => ['type' => 'date', 'null' => true, 'default' => null, 'length' => null],
        'created' => ['type' => 'date', 'null' => '1', 'default' => '', 'length' => ''],
        'updated' => ['type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null],
        'age' => ['type' => 'integer', 'null' => '', 'default' => '', 'length' => null],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
    ];

    /**
     * Initializes the schema
     *
     * @return void
     */
    public function initialize(array $config)
    {
        $this->schema($this->_schema);
    }
}

/**
 * ValidateUser class
 */
class ValidateUsersTable extends Table
{

    /**
     * schema method
     *
     * @var array
     */
    protected $_schema = [
        'id' => ['type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'],
        'name' => ['type' => 'string', 'null' => '', 'default' => '', 'length' => '255'],
        'email' => ['type' => 'string', 'null' => '', 'default' => '', 'length' => '255'],
        'balance' => ['type' => 'float', 'null' => false, 'length' => 5, 'precision' => 2],
        'cost_decimal' => ['type' => 'decimal', 'null' => false, 'length' => 6, 'precision' => 3],
        'null_decimal' => ['type' => 'decimal', 'null' => false, 'length' => null, 'precision' => null],
        'ratio' => ['type' => 'decimal', 'null' => false, 'length' => 10, 'precision' => 6],
        'population' => ['type' => 'decimal', 'null' => false, 'length' => 15, 'precision' => 0],
        'created' => ['type' => 'date', 'null' => '1', 'default' => '', 'length' => ''],
        'updated' => ['type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
    ];

    /**
     * Initializes the schema
     *
     * @return void
     */
    public function initialize(array $config)
    {
        $this->schema($this->_schema);
    }
}

/**
 * FormHelperTest class
 *
 * @property FormHelper $Form
 */
class FormHelperTest extends TestCase
{

    /**
     * Fixtures to be used
     *
     * @var array
     */
    public $fixtures = ['core.articles', 'core.comments'];

    /**
     * Do not load the fixtures by default
     *
     * @var bool
     */
    public $autoFixtures = false;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        Configure::write('Config.language', 'eng');
        Configure::write('App.base', '');
        Configure::write('App.namespace', 'Cake\Test\TestCase\View\Helper');
        Configure::delete('Asset');
        $this->View = new View();

        $this->Form = new FormHelper($this->View);
        $request = new Request('articles/add');
        $request->here = '/articles/add';
        $request['controller'] = 'articles';
        $request['action'] = 'add';
        $request->webroot = '';
        $request->base = '';
        $this->Form->Url->request = $this->Form->request = $request;

        $this->dateRegex = [
            'daysRegex' => 'preg:/(?:<option value="0?([\d]+)">\\1<\/option>[\r\n]*)*/',
            'monthsRegex' => 'preg:/(?:<option value="[\d]+">[\w]+<\/option>[\r\n]*)*/',
            'yearsRegex' => 'preg:/(?:<option value="([\d]+)">\\1<\/option>[\r\n]*)*/',
            'hoursRegex' => 'preg:/(?:<option value="0?([\d]+)">\\1<\/option>[\r\n]*)*/',
            'minutesRegex' => 'preg:/(?:<option value="([\d]+)">0?\\1<\/option>[\r\n]*)*/',
            'meridianRegex' => 'preg:/(?:<option value="(am|pm)">\\1<\/option>[\r\n]*)*/',
        ];

        $this->article = [
            'schema' => [
                'id' => ['type' => 'integer'],
                'author_id' => ['type' => 'integer', 'null' => true],
                'title' => ['type' => 'string', 'null' => true],
                'body' => 'text',
                'published' => ['type' => 'string', 'length' => 1, 'default' => 'N'],
                '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
            ],
            'required' => [
                'author_id' => true,
                'title' => true,
            ]
        ];

        Security::salt('foo!');
        Router::connect('/:controller', ['action' => 'index']);
        Router::connect('/:controller/:action/*');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->Form, $this->Controller, $this->View);
        TableRegistry::clear();
    }

    /**
     * Test construct() with the templates option.
     *
     * @return void
     */
    public function testConstructTemplatesFile()
    {
        $helper = new FormHelper($this->View, [
            'templates' => 'htmlhelper_tags'
        ]);
        $result = $helper->input('name');
        $this->assertContains('<input', $result);
    }

    /**
     * Test that when specifying custom widgets the config array for that widget
     * is overwritten instead of merged.
     *
     * @return void
     */
    public function testConstructWithWidgets()
    {
        $config = [
            'widgets' => [
                'datetime' => ['Cake\View\Widget\LabelWidget', 'select']
            ]
        ];
        $helper = new FormHelper($this->View, $config);
        $registry = $helper->widgetRegistry();
        $this->assertInstanceOf('Cake\View\Widget\LabelWidget', $registry->get('datetime'));
    }

    /**
     * Test that when specifying custom widgets config file and it should be
     * added to widgets array. WidgetRegistry will load widgets in constructor.
     *
     * @return void
     */
    public function testConstructWithWidgetsConfig()
    {
        $helper = new FormHelper($this->View, ['widgets' => ['test_widgets']]);
        $registry = $helper->widgetRegistry();
        $this->assertInstanceOf('Cake\View\Widget\LabelWidget', $registry->get('text'));
    }

    /**
     * Test registering a new widget class and rendering it.
     *
     * @return void
     */
    public function testAddWidgetAndRenderWidget()
    {
        $data = [
            'val' => 1
        ];
        $mock = $this->getMockBuilder('Cake\View\Widget\WidgetInterface')->getMock();
        $this->assertNull($this->Form->addWidget('test', $mock));
        $mock->expects($this->once())
            ->method('render')
            ->with($data)
            ->will($this->returnValue('HTML'));
        $result = $this->Form->widget('test', $data);
        $this->assertEquals('HTML', $result);
    }

    /**
     * Test that secureFields() of widget is called after calling render(),
     * not before.
     *
     * @return void
     */
    public function testOrderForRenderingWidgetAndFetchingSecureFields()
    {
        $data = [
            'val' => 1,
            'name' => 'test'
        ];
        $mock = $this->getMockBuilder('Cake\View\Widget\WidgetInterface')->getMock();
        $this->assertNull($this->Form->addWidget('test', $mock));

        $mock->expects($this->at(0))
            ->method('render')
            ->with($data)
            ->will($this->returnValue('HTML'));

        $mock->expects($this->at(1))
            ->method('secureFields')
            ->with($data)
            ->will($this->returnValue(['test']));

        $result = $this->Form->widget('test', $data + ['secure' => true]);
        $this->assertEquals('HTML', $result);
    }

    /**
     * Test that empty string is not added to secure fields list when
     * rendering input widget without name.
     *
     * @return void
     */
    public function testRenderingWidgetWithEmptyName()
    {
        $this->assertEquals([], $this->Form->fields);

        $result = $this->Form->widget('select', ['secure' => true, 'name' => '']);
        $this->assertEquals('<select name=""></select>', $result);
        $this->assertEquals([], $this->Form->fields);

        $result = $this->Form->widget('select', ['secure' => true, 'name' => '0']);
        $this->assertEquals('<select name="0"></select>', $result);
        $this->assertEquals(['0'], $this->Form->fields);
    }

    /**
     * Test registering an invalid widget class.
     *
     * @expectedException \RuntimeException
     * @return void
     */
    public function testAddWidgetInvalid()
    {
        $mock = new \StdClass();
        $this->Form->addWidget('test', $mock);
        $this->Form->widget('test');
    }

    /**
     * Test adding a new context class.
     *
     * @return void
     */
    public function testAddContextProvider()
    {
        $context = 'My data';
        $stub = $this->getMockBuilder('Cake\View\Form\ContextInterface')->getMock();
        $this->Form->addContextProvider('test', function ($request, $data) use ($context, $stub) {
            $this->assertInstanceOf('Cake\Network\Request', $request);
            $this->assertEquals($context, $data['entity']);

            return $stub;
        });
        $this->Form->create($context);
        $result = $this->Form->context();
        $this->assertSame($stub, $result);
    }

    /**
     * Test replacing a context class.
     *
     * @return void
     */
    public function testAddContextProviderReplace()
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
     *
     * @return void
     */
    public function testAddContextProviderAdd()
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
     * Test adding an invalid context class.
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Context objects must implement Cake\View\Form\ContextInterface
     * @return void
     */
    public function testAddContextProviderInvalid()
    {
        $context = 'My data';
        $this->Form->addContextProvider('test', function ($request, $data) use ($context) {
            return new \StdClass();
        });
        $this->Form->create($context);
    }

    /**
     * Provides context options for create().
     *
     * @return array
     */
    public function contextSelectionProvider()
    {
        $entity = new Article();
        $collection = new Collection([$entity]);
        $emptyCollection = new Collection([]);
        $emptyArray = [];
        $arrayObject = new \ArrayObject([]);
        $data = [
            'schema' => [
                'title' => ['type' => 'string']
            ]
        ];
        $form = new Form();

        return [
            'entity' => [$entity, 'Cake\View\Form\EntityContext'],
            'collection' => [$collection, 'Cake\View\Form\EntityContext'],
            'empty_collection' => [$emptyCollection, 'Cake\View\Form\NullContext'],
            'array' => [$data, 'Cake\View\Form\ArrayContext'],
            'array_object' => [$arrayObject, 'Cake\View\Form\NullContext'],
            'form' => [$form, 'Cake\View\Form\FormContext'],
            'none' => [null, 'Cake\View\Form\NullContext'],
            'false' => [false, 'Cake\View\Form\NullContext'],
        ];
    }

    /**
     * Test default context selection in create()
     *
     * @dataProvider contextSelectionProvider
     * @return void
     */
    public function testCreateContextSelectionBuiltIn($data, $class)
    {
        $this->loadFixtures('Articles');
        $this->Form->create($data);
        $this->assertInstanceOf($class, $this->Form->context());
    }

    /**
     * Data provider for type option.
     *
     * @return array
     */
    public static function requestTypeProvider()
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
     *
     * @return void
     */
    public function testCreateFile()
    {
        $encoding = strtolower(Configure::read('App.encoding'));
        $result = $this->Form->create(false, ['type' => 'file']);
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/articles/add',
                'accept-charset' => $encoding, 'enctype' => 'multipart/form-data'
            ],
            'div' => ['style' => 'display:none;'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test creating GET forms.
     *
     * @return void
     */
    public function testCreateGet()
    {
        $encoding = strtolower(Configure::read('App.encoding'));
        $result = $this->Form->create(false, ['type' => 'get']);
        $expected = ['form' => [
            'method' => 'get', 'action' => '/articles/add',
            'accept-charset' => $encoding
        ]];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test explicit method/enctype options.
     *
     * Explicit method overwrites inferred method from 'type'
     *
     * @return void
     */
    public function testCreateExplicitMethodEnctype()
    {
        $encoding = strtolower(Configure::read('App.encoding'));
        $result = $this->Form->create(false, [
            'type' => 'get',
            'method' => 'put',
            'enctype' => 'multipart/form-data'
        ]);
        $expected = ['form' => [
            'method' => 'put',
            'action' => '/articles/add',
            'enctype' => 'multipart/form-data',
            'accept-charset' => $encoding
        ]];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test create() with the templates option.
     *
     * @return void
     */
    public function testCreateTemplatesArray()
    {
        $result = $this->Form->create($this->article, [
            'templates' => [
                'formStart' => '<form class="form-horizontal"{{attrs}}>',
            ]
        ]);
        $expected = [
            'form' => [
                'class' => 'form-horizontal',
                'method' => 'post',
                'action' => '/articles/add',
                'accept-charset' => 'utf-8'
            ]
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test create() with the templates option.
     *
     * @return void
     */
    public function testCreateTemplatesFile()
    {
        $result = $this->Form->create($this->article, [
            'templates' => 'htmlhelper_tags',
        ]);
        $expected = [
            'start form',
            'div' => ['class' => 'hidden'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test that create() and end() restore templates.
     *
     * @return void
     */
    public function testCreateEndRestoreTemplates()
    {
        $this->Form->create($this->article, [
            'templates' => ['input' => 'custom input element']
        ]);
        $this->Form->end();
        $this->assertNotEquals('custom input element', $this->Form->templater()->get('input'));
    }

    /**
     * Test using template vars in various templates used by input() method.
     *
     * @return void
     */
    public function testInputTemplateVars()
    {
        $result = $this->Form->input('text', [
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
                'forcontainer' => 'in-container'
            ]
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
     * during input().
     *
     * @return void
     */
    public function testInputTemplatesFromFile()
    {
        $result = $this->Form->input('title', [
            'templates' => 'test_templates',
            'templateVars' => [
                'forcontainer' => 'container-data'
            ]
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
     *
     * @return void
     */
    public function testSubmitTemplateVars()
    {
        $this->Form->templates([
            'inputSubmit' => '<input custom="{{forinput}}" type="{{type}}"{{attrs}}/>',
            'submitContainer' => '<div class="submit">{{content}}{{forcontainer}}</div>'
        ]);
        $result = $this->Form->submit('Submit', [
            'templateVars' => [
                'forinput' => 'in-input',
                'forcontainer' => 'in-container'
            ]
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
     * @return void
     */
    public function testCreateTypeOptions($type, $method, $override)
    {
        $encoding = strtolower(Configure::read('App.encoding'));
        $result = $this->Form->create(false, ['type' => $type]);
        $expected = [
            'form' => [
                'method' => $method, 'action' => '/articles/add',
                'accept-charset' => $encoding
            ],
            'div' => ['style' => 'display:none;'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => $override],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test using template vars in Create (formStart template)
     *
     * @return void
     */
    public function testCreateTemplateVars()
    {
        $result = $this->Form->create($this->article, [
            'templates' => [
                'formStart' => '<h4 class="mb">{{header}}</h4><form{{attrs}}>',
            ],
            'templateVars' => ['header' => 'headertext']
        ]);
        $expected = [
            'h4' => ['class'],
            'headertext',
            '/h4',
            'form' => [
                'method' => 'post',
                'action' => '/articles/add',
                'accept-charset' => 'utf-8'
            ]
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test opening a form for an update operation.
     *
     * @return void
     */
    public function testCreateUpdateForm()
    {
        $encoding = strtolower(Configure::read('App.encoding'));

        $this->Form->request->here = '/articles/edit/1';
        $this->Form->request['action'] = 'edit';

        $this->article['defaults']['id'] = 1;

        $result = $this->Form->create($this->article);
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/articles/edit/1',
                'accept-charset' => $encoding
            ],
            'div' => ['style' => 'display:none;'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'PUT'],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test create() with automatic url generation
     *
     * @return void
     */
    public function testCreateAutoUrl()
    {
        $encoding = strtolower(Configure::read('App.encoding'));

        $this->Form->request['action'] = 'delete';
        $this->Form->request->here = '/articles/delete/10';
        $this->Form->request->base = '';
        $result = $this->Form->create($this->article);
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/articles/delete/10',
                'accept-charset' => $encoding
            ],
            'div' => ['style' => 'display:none;'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $this->article['defaults'] = ['id' => 1];
        $this->Form->request->here = '/articles/edit/1';
        $this->Form->request['action'] = 'delete';
        $result = $this->Form->create($this->article, ['url' => ['action' => 'edit']]);
        $expected = [
            'form' => [
                'method' => 'post',
                'action' => '/articles/edit/1',
                'accept-charset' => $encoding
            ],
            'div' => ['style' => 'display:none;'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'PUT'],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $this->Form->request['action'] = 'add';
        $result = $this->Form->create($this->article, ['url' => ['action' => 'publish']]);
        $expected = [
            'form' => [
                'method' => 'post',
                'action' => '/articles/publish/1',
                'accept-charset' => $encoding
            ],
            'div' => ['style' => 'display:none;'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'PUT'],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->create($this->article, ['url' => '/articles/publish']);
        $expected = [
            'form' => ['method' => 'post', 'action' => '/articles/publish', 'accept-charset' => $encoding],
            'div' => ['style' => 'display:none;'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'PUT'],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $this->Form->request['controller'] = 'Pages';
        $result = $this->Form->create($this->article, ['url' => ['action' => 'signup']]);
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/Pages/signup/1',
                'accept-charset' => $encoding
            ],
            'div' => ['style' => 'display:none;'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'PUT'],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test create() with no URL (no "action" attribute for <form> tag)
     *
     * @return void
     */
    public function testCreateNoUrl()
    {
        $result = $this->Form->create(false, ['url' => false]);
        $expected = [
            'form' => [
                'method' => 'post',
                'accept-charset' => strtolower(Configure::read('App.encoding'))
            ],
            'div' => ['style' => 'display:none;'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test create() with a custom route
     *
     * @return void
     */
    public function testCreateCustomRoute()
    {
        Router::connect('/login', ['controller' => 'users', 'action' => 'login']);
        $encoding = strtolower(Configure::read('App.encoding'));

        $this->Form->request['controller'] = 'users';

        $result = $this->Form->create(false, ['url' => ['action' => 'login']]);
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/login',
                'accept-charset' => $encoding
            ],
            'div' => ['style' => 'display:none;'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        Router::connect(
            '/new-article',
            ['controller' => 'articles', 'action' => 'myaction'],
            ['_name' => 'my-route']
        );
        $result = $this->Form->create(false, ['url' => ['_name' => 'my-route']]);
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/new-article',
                'accept-charset' => $encoding,
            ],
            'div' => ['style' => 'display:none;'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test automatic accept-charset overriding
     *
     * @return void
     */
    public function testCreateWithAcceptCharset()
    {
        $result = $this->Form->create(
            $this->article,
            [
                'type' => 'post', 'url' => ['action' => 'index'], 'encoding' => 'iso-8859-1'
            ]
        );
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/articles',
                'accept-charset' => 'iso-8859-1'
            ],
            'div' => ['style' => 'display:none;'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test base form URL when url param is passed with multiple parameters (&)
     *
     */
    public function testCreateQuerystringrequest()
    {
        $encoding = strtolower(Configure::read('App.encoding'));
        $result = $this->Form->create($this->article, [
            'type' => 'post',
            'escape' => false,
            'url' => [
                'controller' => 'controller',
                'action' => 'action',
                '?' => ['param1' => 'value1', 'param2' => 'value2']
            ]
        ]);
        $expected = [
            'form' => [
                'method' => 'post',
                'action' => '/controller/action?param1=value1&amp;param2=value2',
                'accept-charset' => $encoding
            ],
            'div' => ['style' => 'display:none;'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->create($this->article, [
            'type' => 'post',
            'url' => [
                'controller' => 'controller',
                'action' => 'action',
                '?' => ['param1' => 'value1', 'param2' => 'value2']
            ]
        ]);
        $this->assertHtml($expected, $result);
    }

    /**
     * test that create() doesn't cause errors by multiple id's being in the primary key
     * as could happen with multiple select or checkboxes.
     *
     * @return void
     */
    public function testCreateWithMultipleIdInData()
    {
        $encoding = strtolower(Configure::read('App.encoding'));

        $this->Form->request->data['Article']['id'] = [1, 2];
        $result = $this->Form->create($this->article);
        $expected = [
            'form' => [
                'method' => 'post',
                'action' => '/articles/add',
                'accept-charset' => $encoding
            ],
            'div' => ['style' => 'display:none;'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test that create() doesn't add in extra passed params.
     *
     * @return void
     */
    public function testCreatePassedArgs()
    {
        $encoding = strtolower(Configure::read('App.encoding'));
        $this->Form->request->data['Article']['id'] = 1;
        $result = $this->Form->create($this->article, [
            'type' => 'post',
            'escape' => false,
            'url' => [
                'action' => 'edit',
                'myparam'
            ]
        ]);
        $expected = [
            'form' => [
                'method' => 'post',
                'action' => '/articles/edit/myparam',
                'accept-charset' => $encoding
            ],
            'div' => ['style' => 'display:none;'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test creating a get form, and get form inputs.
     *
     * @return void
     */
    public function testGetFormCreate()
    {
        $encoding = strtolower(Configure::read('App.encoding'));
        $result = $this->Form->create($this->article, ['type' => 'get']);
        $expected = ['form' => [
            'method' => 'get', 'action' => '/articles/add',
            'accept-charset' => $encoding
        ]];
        $this->assertHtml($expected, $result);

        $result = $this->Form->text('title');
        $expected = ['input' => [
            'name' => 'title', 'type' => 'text', 'required' => 'required'
        ]];
        $this->assertHtml($expected, $result);

        $result = $this->Form->password('password');
        $expected = ['input' => [
            'name' => 'password', 'type' => 'password'
        ]];
        $this->assertHtml($expected, $result);
        $this->assertNotRegExp('/<input[^<>]+[^id|name|type|value]=[^<>]*\/>$/', $result);

        $result = $this->Form->text('user_form');
        $expected = ['input' => [
            'name' => 'user_form', 'type' => 'text'
        ]];
        $this->assertHtml($expected, $result);
    }

    /**
     * test get form, and inputs when the model param is false
     *
     * @return void
     */
    public function testGetFormWithFalseModel()
    {
        $encoding = strtolower(Configure::read('App.encoding'));
        $this->Form->request['controller'] = 'contact_test';
        $result = $this->Form->create(false, [
            'type' => 'get', 'url' => ['controller' => 'contact_test']
        ]);

        $expected = ['form' => [
            'method' => 'get', 'action' => '/contact_test/add',
            'accept-charset' => $encoding
        ]];
        $this->assertHtml($expected, $result);

        $result = $this->Form->text('reason');
        $expected = [
            'input' => ['type' => 'text', 'name' => 'reason']
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormCreateWithSecurity method
     *
     * Test form->create() with security key.
     *
     * @return void
     */
    public function testCreateWithSecurity()
    {
        $this->Form->request->params['_csrfToken'] = 'testKey';
        $encoding = strtolower(Configure::read('App.encoding'));
        $result = $this->Form->create($this->article, [
            'url' => '/articles/publish',
        ]);
        $expected = [
            'form' => ['method' => 'post', 'action' => '/articles/publish', 'accept-charset' => $encoding],
            'div' => ['style' => 'display:none;'],
            ['input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST']],
            ['input' => [
                'type' => 'hidden', 'name' => '_csrfToken', 'value' => 'testKey'
            ]],
            '/div'
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
     *
     * @return void
     */
    public function testCreateEndGetNoSecurity()
    {
        $this->Form->request->params['_csrfToken'] = 'testKey';
        $encoding = strtolower(Configure::read('App.encoding'));
        $article = new Article();
        $result = $this->Form->create($article, [
            'type' => 'get',
            'url' => '/contacts/add'
        ]);
        $this->assertNotContains('testKey', $result);

        $result = $this->Form->end();
        $this->assertNotContains('testKey', $result);
    }

    /**
     * test that create() clears the fields property so it starts fresh
     *
     * @return void
     */
    public function testCreateClearingFields()
    {
        $this->Form->fields = ['model_id'];
        $this->Form->create($this->article);
        $this->assertEquals([], $this->Form->fields);
    }

    /**
     * Tests form hash generation with model-less data
     *
     * @return void
     */
    public function testValidateHashNoModel()
    {
        $this->Form->request->params['_Token'] = 'foo';

        $result = $this->Form->secure(['anything']);
        $this->assertRegExp('/540ac9c60d323c22bafe997b72c0790f39a8bdef/', $result);
    }

    /**
     * Tests that hidden fields generated for checkboxes don't get locked
     *
     * @return void
     */
    public function testNoCheckboxLocking()
    {
        $this->Form->request->params['_Token'] = 'foo';
        $this->assertSame([], $this->Form->fields);

        $this->Form->checkbox('check', ['value' => '1']);
        $this->assertSame($this->Form->fields, ['check']);
    }

    /**
     * testFormSecurityFields method
     *
     * Test generation of secure form hash generation.
     *
     * @return void
     */
    public function testFormSecurityFields()
    {
        $fields = ['Model.password', 'Model.username', 'Model.valid' => '0'];

        $this->Form->request->params['_Token'] = 'testKey';
        $result = $this->Form->secure($fields);

        $hash = Security::hash(serialize($fields) . Security::salt());
        $hash .= ':' . 'Model.valid';
        $hash = urlencode($hash);
        $tokenDebug = urlencode(json_encode([
            '',
            $fields,
            []
        ]));
        $expected = [
            'div' => ['style' => 'display:none;'],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[fields]',
                'value' => $hash
            ]],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[unlocked]',
                'value' => '',
            ]],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[debug]',
                'value' => $tokenDebug
            ]],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormSecurityFields method
     *
     * Test debug token is not generated if debug is false
     *
     * @return void
     */
    public function testFormSecurityFieldsNoDebugMode()
    {
        Configure::write('debug', false);
        $fields = ['Model.password', 'Model.username', 'Model.valid' => '0'];

        $this->Form->request->params['_Token'] = 'testKey';
        $result = $this->Form->secure($fields);

        $hash = Security::hash(serialize($fields) . Security::salt());
        $hash .= ':' . 'Model.valid';
        $hash = urlencode($hash);
        $expected = [
            'div' => ['style' => 'display:none;'],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[fields]',
                'value' => $hash
            ]],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[unlocked]',
                'value' => '',
            ]],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Tests correct generation of number fields for double and float fields
     *
     * @return void
     */
    public function testTextFieldGenerationForFloats()
    {
        $this->article['schema'] = [
            'foo' => [
                'type' => 'float',
                'null' => false,
                'default' => null,
                'length' => 10
            ]
        ];

        $this->Form->create($this->article);
        $result = $this->Form->input('foo');
        $expected = [
            'div' => ['class' => 'input number'],
            'label' => ['for' => 'foo'],
            'Foo',
            '/label',
            ['input' => [
                'type' => 'number',
                'name' => 'foo',
                'id' => 'foo',
                'step' => 'any'
            ]],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('foo', ['step' => 0.5]);
        $expected = [
            'div' => ['class' => 'input number'],
            'label' => ['for' => 'foo'],
            'Foo',
            '/label',
            ['input' => [
                'type' => 'number',
                'name' => 'foo',
                'id' => 'foo',
                'step' => '0.5'
            ]],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Tests correct generation of number fields for integer fields
     *
     * @return void
     */
    public function testTextFieldTypeNumberGenerationForIntegers()
    {
        TableRegistry::get('Contacts', [
            'className' => __NAMESPACE__ . '\ContactsTable'
        ]);
        $this->Form->create([], ['context' => ['table' => 'Contacts']]);
        $result = $this->Form->input('age');
        $expected = [
            'div' => ['class' => 'input number'],
            'label' => ['for' => 'age'],
            'Age',
            '/label',
            ['input' => [
                'type' => 'number', 'name' => 'age',
                'id' => 'age'
            ]],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Tests correct generation of file upload fields for binary fields
     *
     * @return void
     */
    public function testFileUploadFieldTypeGenerationForBinaries()
    {
        $table = TableRegistry::get('Contacts', [
            'className' => __NAMESPACE__ . '\ContactsTable'
        ]);
        $table->schema(['foo' => [
            'type' => 'binary',
            'null' => false,
            'default' => null,
            'length' => 1024
        ]]);
        $this->Form->create([], ['context' => ['table' => 'Contacts']]);

        $result = $this->Form->input('foo');
        $expected = [
            'div' => ['class' => 'input file'],
            'label' => ['for' => 'foo'],
            'Foo',
            '/label',
            ['input' => [
                'type' => 'file', 'name' => 'foo',
                'id' => 'foo'
            ]],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormSecurityMultipleFields method
     *
     * Test secure() with multiple row form. Ensure hash is correct.
     *
     * @return void
     */
    public function testFormSecurityMultipleFields()
    {
        $this->Form->request->params['_Token'] = 'foo';

        $fields = [
            'Model.0.password', 'Model.0.username', 'Model.0.hidden' => 'value',
            'Model.0.valid' => '0', 'Model.1.password', 'Model.1.username',
            'Model.1.hidden' => 'value', 'Model.1.valid' => '0'
        ];
        $result = $this->Form->secure($fields);

        $hash = '51e3b55a6edd82020b3f29c9ae200e14bbeb7ee5%3AModel.0.hidden%7CModel.0.valid';
        $hash .= '%7CModel.1.hidden%7CModel.1.valid';
        $tokenDebug = urlencode(json_encode([
            '',
            $fields,
            []
        ]));

        $expected = [
            'div' => ['style' => 'display:none;'],
            ['input' => [
                'type' => 'hidden', 'name' => '_Token[fields]',
                'value' => $hash
            ]],
            ['input' => [
                'type' => 'hidden', 'name' => '_Token[unlocked]',
                'value' => ''
            ]],
            ['input' => [
                'type' => 'hidden', 'name' => '_Token[debug]',
                'value' => $tokenDebug,
            ]],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormSecurityMultipleSubmitButtons
     *
     * test form submit generation and ensure that _Token is only created on end()
     *
     * @return void
     */
    public function testFormSecurityMultipleSubmitButtons()
    {
        $this->Form->request->params['_Token'] = 'testKey';

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
            ['save', 'cancel']
        ]));

        $expected = [
            'div' => ['style' => 'display:none;'],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[fields]',
                'value'
            ]],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[unlocked]',
                'value' => 'cancel%7Csave'
            ]],
            ['input' => [
                'type' => 'hidden', 'name' => '_Token[debug]',
                'value' => $tokenDebug,
            ]],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test that buttons created with foo[bar] name attributes are unlocked correctly.
     *
     * @return void
     */
    public function testSecurityButtonNestedNamed()
    {
        $key = 'testKey';
        $this->Form->request->params['_csrfToken'] = $key;

        $this->Form->create('Addresses');
        $this->Form->button('Test', ['type' => 'submit', 'name' => 'Address[button]']);
        $result = $this->Form->unlockField();
        $this->assertEquals(['Address.button'], $result);
    }

    /**
     * Test that submit inputs created with foo[bar] name attributes are unlocked correctly.
     *
     * @return void
     */
    public function testSecuritySubmitNestedNamed()
    {
        $this->Form->request->params['_Token'] = 'testKey';

        $this->Form->create($this->article);
        $this->Form->submit('Test', ['type' => 'submit', 'name' => 'Address[button]']);
        $result = $this->Form->unlockField();
        $this->assertEquals(['Address.button'], $result);
    }

    /**
     * Test that the correct fields are unlocked for image submits with no names.
     *
     * @return void
     */
    public function testSecuritySubmitImageNoName()
    {
        $key = 'testKey';
        $this->Form->request->params['_Token'] = 'testKey';

        $this->Form->create(false);
        $result = $this->Form->submit('save.png');
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'image', 'src' => 'img/save.png'],
            '/div'
        ];
        $this->assertHtml($expected, $result);
        $this->assertEquals(['x', 'y'], $this->Form->unlockField());
    }

    /**
     * Test that the correct fields are unlocked for image submits with names.
     *
     * @return void
     */
    public function testSecuritySubmitImageName()
    {
        $this->Form->request->params['_Token'] = 'testKey';

        $this->Form->create(null);
        $result = $this->Form->submit('save.png', ['name' => 'test']);
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'image', 'name' => 'test', 'src' => 'img/save.png'],
            '/div'
        ];
        $this->assertHtml($expected, $result);
        $this->assertEquals(['test', 'test_x', 'test_y'], $this->Form->unlockField());
    }

    /**
     * testFormSecurityMultipleInputFields method
     *
     * Test secure form creation with multiple row creation. Checks hidden, text, checkbox field types
     *
     * @return void
     */
    public function testFormSecurityMultipleInputFields()
    {
        $this->Form->request->params['_Token'] = 'testKey';
        $this->Form->create();

        $this->Form->hidden('Addresses.0.id', ['value' => '123456']);
        $this->Form->input('Addresses.0.title');
        $this->Form->input('Addresses.0.first_name');
        $this->Form->input('Addresses.0.last_name');
        $this->Form->input('Addresses.0.address');
        $this->Form->input('Addresses.0.city');
        $this->Form->input('Addresses.0.phone');
        $this->Form->input('Addresses.0.primary', ['type' => 'checkbox']);

        $this->Form->hidden('Addresses.1.id', ['value' => '654321']);
        $this->Form->input('Addresses.1.title');
        $this->Form->input('Addresses.1.first_name');
        $this->Form->input('Addresses.1.last_name');
        $this->Form->input('Addresses.1.address');
        $this->Form->input('Addresses.1.city');
        $this->Form->input('Addresses.1.phone');
        $this->Form->input('Addresses.1.primary', ['type' => 'checkbox']);

        $result = $this->Form->secure($this->Form->fields);

        $hash = '8bd3911b07b507408b1a969b31ee90c47b7d387e%3AAddresses.0.id%7CAddresses.1.id';
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
            []
        ]));
        $expected = [
            'div' => ['style' => 'display:none;'],
            ['input' => [
                'type' => 'hidden', 'name' => '_Token[fields]',
                'value' => $hash
            ]],
            ['input' => [
                'type' => 'hidden', 'name' => '_Token[unlocked]',
                'value' => ''
            ]],
            ['input' => [
                'type' => 'hidden', 'name' => '_Token[debug]',
                'value' => $tokenDebug,
            ]],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormSecurityArrayFields method
     *
     * Test form security with Model.field.0 style inputs.
     *
     * @return void
     */
    public function testFormSecurityArrayFields()
    {
        $this->Form->request->params['_Token'] = 'testKey';

        $this->Form->create();
        $this->Form->text('Address.primary.1');
        $this->assertEquals('Address.primary', $this->Form->fields[0]);

        $this->Form->text('Address.secondary.1.0');
        $this->assertEquals('Address.secondary', $this->Form->fields[1]);
    }

    /**
     * testFormSecurityMultipleInputDisabledFields method
     *
     * Test secure form generation with multiple records and disabled fields.
     *
     * @return void
     */
    public function testFormSecurityMultipleInputDisabledFields()
    {
        $this->Form->request->params['_Token'] = [
            'unlockedFields' => ['first_name', 'address']
        ];
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

        $result = $this->Form->secure($this->Form->fields);
        $hash = '4fb10b46873df4ddd4ef5c3a19944a2f29b38991%3AAddresses.0.id%7CAddresses.1.id';
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
                    'Addresses.1.phone'
                ],
                [
                    'first_name',
                    'address'
                ]
            ]));

        $expected = [
            'div' => ['style' => 'display:none;'],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[fields]',
                'value' => $hash
            ]],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[unlocked]',
                'value' => 'address%7Cfirst_name',
            ]],
            ['input' => [
                'type' => 'hidden', 'name' => '_Token[debug]',
                'value' => $tokenDebug,
            ]],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormSecurityInputDisabledFields method
     *
     * Test single record form with disabled fields.
     *
     * @return void
     */
    public function testFormSecurityInputUnlockedFields()
    {
        $this->Form->request['_Token'] = [
            'unlockedFields' => ['first_name', 'address']
        ];
        $this->Form->create();
        $this->assertEquals($this->Form->request['_Token']['unlockedFields'], $this->Form->unlockField());

        $this->Form->hidden('Addresses.id', ['value' => '123456']);
        $this->Form->text('Addresses.title');
        $this->Form->text('Addresses.first_name');
        $this->Form->text('Addresses.last_name');
        $this->Form->text('Addresses.address');
        $this->Form->text('Addresses.city');
        $this->Form->text('Addresses.phone');

        $result = $this->Form->fields;
        $expected = [
            'Addresses.id' => '123456', 'Addresses.title', 'Addresses.last_name',
            'Addresses.city', 'Addresses.phone'
        ];
        $this->assertEquals($expected, $result);

        $result = $this->Form->secure($expected, ['data-foo' => 'bar']);

        $hash = 'a303becbdd99cb42ca14a1cf7e63dfd48696a3c5%3AAddresses.id';
        $tokenDebug = urlencode(json_encode([
                '/articles/add',
                [
                    'Addresses.id' => '123456',
                    'Addresses.title',
                    'Addresses.last_name',
                    'Addresses.city',
                    'Addresses.phone'
                ],
                [
                    'first_name',
                    'address'
                ]
            ]));

        $expected = [
            'div' => ['style' => 'display:none;'],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[fields]',
                'value' => $hash,
                'data-foo' => 'bar',
            ]],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[unlocked]',
                'value' => 'address%7Cfirst_name',
                'data-foo' => 'bar',
            ]],
            ['input' => [
                'type' => 'hidden', 'name' => '_Token[debug]',
                'value' => $tokenDebug,
                'data-foo' => 'bar'
            ]],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormSecurityInputUnlockedFieldsDebugSecurityTrue method
     *
     * Test single record form with debugSecurity param.
     *
     * @return void
     */
    public function testFormSecurityInputUnlockedFieldsDebugSecurityTrue()
    {
        $this->Form->request['_Token'] = [
            'unlockedFields' => ['first_name', 'address']
        ];
        $this->Form->create();
        $this->assertEquals($this->Form->request['_Token']['unlockedFields'], $this->Form->unlockField());

        $this->Form->hidden('Addresses.id', ['value' => '123456']);
        $this->Form->text('Addresses.title');
        $this->Form->text('Addresses.first_name');
        $this->Form->text('Addresses.last_name');
        $this->Form->text('Addresses.address');
        $this->Form->text('Addresses.city');
        $this->Form->text('Addresses.phone');

        $result = $this->Form->fields;
        $expected = [
            'Addresses.id' => '123456', 'Addresses.title', 'Addresses.last_name',
            'Addresses.city', 'Addresses.phone'
        ];
        $this->assertEquals($expected, $result);
        $result = $this->Form->secure($expected, ['data-foo' => 'bar', 'debugSecurity' => true]);

        $hash = 'a303becbdd99cb42ca14a1cf7e63dfd48696a3c5%3AAddresses.id';
        $tokenDebug = urlencode(json_encode([
            '/articles/add',
            [
                'Addresses.id' => '123456',
                'Addresses.title',
                'Addresses.last_name',
                'Addresses.city',
                'Addresses.phone'
            ],
            [
                'first_name',
                'address'
            ]
        ]));

        $expected = [
            'div' => ['style' => 'display:none;'],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[fields]',
                'value' => $hash,
                'data-foo' => 'bar',
            ]],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[unlocked]',
                'value' => 'address%7Cfirst_name',
                'data-foo' => 'bar',
            ]],
            ['input' => [
                'type' => 'hidden', 'name' => '_Token[debug]',
                'value' => $tokenDebug,
                'data-foo' => 'bar'
            ]],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormSecurityInputUnlockedFieldsDebugSecurityFalse method
     *
     * Debug is false, debugSecurity is true -> no debug
     *
     * @return void
     */
    public function testFormSecurityInputUnlockedFieldsDebugSecurityDebugFalse()
    {
        $this->Form->request['_Token'] = [
            'unlockedFields' => ['first_name', 'address']
        ];
        $this->Form->create();
        $this->assertEquals($this->Form->request['_Token']['unlockedFields'], $this->Form->unlockField());

        $this->Form->hidden('Addresses.id', ['value' => '123456']);
        $this->Form->text('Addresses.title');
        $this->Form->text('Addresses.first_name');
        $this->Form->text('Addresses.last_name');
        $this->Form->text('Addresses.address');
        $this->Form->text('Addresses.city');
        $this->Form->text('Addresses.phone');

        $result = $this->Form->fields;
        $expected = [
            'Addresses.id' => '123456', 'Addresses.title', 'Addresses.last_name',
            'Addresses.city', 'Addresses.phone'
        ];
        $this->assertEquals($expected, $result);
        Configure::write('debug', false);
        $result = $this->Form->secure($expected, ['data-foo' => 'bar', 'debugSecurity' => true]);

        $hash = 'a303becbdd99cb42ca14a1cf7e63dfd48696a3c5%3AAddresses.id';
        $expected = [
            'div' => ['style' => 'display:none;'],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[fields]',
                'value' => $hash,
                'data-foo' => 'bar',
            ]],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[unlocked]',
                'value' => 'address%7Cfirst_name',
                'data-foo' => 'bar',
            ]],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormSecurityInputUnlockedFieldsDebugSecurityFalse method
     *
     * Test single record form with debugSecurity param.
     *
     * @return void
     */
    public function testFormSecurityInputUnlockedFieldsDebugSecurityFalse()
    {
        $this->Form->request['_Token'] = [
            'unlockedFields' => ['first_name', 'address']
        ];
        $this->Form->create();
        $this->assertEquals($this->Form->request['_Token']['unlockedFields'], $this->Form->unlockField());

        $this->Form->hidden('Addresses.id', ['value' => '123456']);
        $this->Form->text('Addresses.title');
        $this->Form->text('Addresses.first_name');
        $this->Form->text('Addresses.last_name');
        $this->Form->text('Addresses.address');
        $this->Form->text('Addresses.city');
        $this->Form->text('Addresses.phone');

        $result = $this->Form->fields;
        $expected = [
            'Addresses.id' => '123456', 'Addresses.title', 'Addresses.last_name',
            'Addresses.city', 'Addresses.phone'
        ];
        $this->assertEquals($expected, $result);

        $result = $this->Form->secure($expected, ['data-foo' => 'bar', 'debugSecurity' => false]);

        $hash = 'a303becbdd99cb42ca14a1cf7e63dfd48696a3c5%3AAddresses.id';

        $expected = [
            'div' => ['style' => 'display:none;'],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[fields]',
                'value' => $hash,
                'data-foo' => 'bar',
            ]],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[unlocked]',
                'value' => 'address%7Cfirst_name',
                'data-foo' => 'bar',
            ]],
            '/div'
        ];

        $this->assertHtml($expected, $result);
    }

    /**
     * testFormSecureWithCustomNameAttribute method
     *
     * Test securing inputs with custom name attributes.
     *
     * @return void
     */
    public function testFormSecureWithCustomNameAttribute()
    {
        $this->Form->request->params['_Token'] = 'testKey';

        $this->Form->text('UserForm.published', ['name' => 'User[custom]']);
        $this->assertEquals('User.custom', $this->Form->fields[0]);

        $this->Form->text('UserForm.published', ['name' => 'User[custom][another][value]']);
        $this->assertEquals('User.custom.another.value', $this->Form->fields[1]);
    }

    /**
     * testFormSecuredInput method
     *
     * Test generation of entire secure form, assertions made on input() output.
     *
     * @return void
     */
    public function testFormSecuredInput()
    {
        $this->Form->request->params['_csrfToken'] = 'testKey';
        $this->Form->request->params['_Token'] = 'stuff';
        $this->article['schema'] = [
            'ratio' => ['type' => 'decimal', 'length' => 5, 'precision' => 6],
            'population' => ['type' => 'decimal', 'length' => 15, 'precision' => 0],
        ];

        $result = $this->Form->create($this->article, ['url' => '/articles/add']);
        $encoding = strtolower(Configure::read('App.encoding'));
        $expected = [
            'form' => ['method' => 'post', 'action' => '/articles/add', 'accept-charset' => $encoding],
            'div' => ['style' => 'display:none;'],
            ['input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST']],
            ['input' => [
                'type' => 'hidden',
                'name' => '_csrfToken',
                'value' => 'testKey'
            ]],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('ratio');
        $expected = [
            'div' => ['class'],
            'label' => ['for'],
            'Ratio',
            '/label',
            'input' => ['name', 'type' => 'number', 'step' => '0.000001', 'id'],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('population');
        $expected = [
            'div' => ['class'],
            'label' => ['for'],
            'Population',
            '/label',
            'input' => ['name', 'type' => 'number', 'step' => '1', 'id'],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('published', ['type' => 'text']);
        $expected = [
            'div' => ['class' => 'input text'],
            'label' => ['for' => 'published'],
            'Published',
            '/label',
            ['input' => [
                'type' => 'text',
                'name' => 'published',
                'id' => 'published'
            ]],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('other', ['type' => 'text']);
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
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->hidden('stuff');
        $expected = [
            'input' => [
                'type' => 'hidden',
                'name' => 'stuff'
            ]
        ];

        $this->assertHtml($expected, $result);

        $result = $this->Form->hidden('hidden', ['value' => '0']);
        $expected = ['input' => [
            'type' => 'hidden',
            'name' => 'hidden',
            'value' => '0'
        ]];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('something', ['type' => 'checkbox']);
        $expected = [
            'div' => ['class' => 'input checkbox'],
            ['input' => [
                'type' => 'hidden',
                'name' => 'something',
                'value' => '0'
            ]],
            'label' => ['for' => 'something'],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'something',
                'value' => '1',
                'id' => 'something'
            ]],
            'Something',
            '/label',
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->fields;
        $expectedFields = [
            'ratio',
            'population',
            'published',
            'other',
            'stuff' => '',
            'hidden' => '0',
            'something'
        ];
        $this->assertEquals($expectedFields, $result);

        $result = $this->Form->secure($this->Form->fields);
        $tokenDebug = urlencode(json_encode([
            '/articles/add',
            $expectedFields,
            []
        ]));

        $expected = [
            'div' => ['style' => 'display:none;'],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[fields]',
                'value'
            ]],
            ['input' => [
                'type' => 'hidden',
                'name' => '_Token[unlocked]',
                'value' => ''
            ]],
            ['input' => [
                'type' => 'hidden', 'name' => '_Token[debug]',
                'value' => $tokenDebug,
            ]],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testSecuredInputCustomName method
     *
     * Test secured inputs with custom names.
     *
     * @return void
     */
    public function testSecuredInputCustomName()
    {
        $this->Form->request->params['_Token'] = 'testKey';
        $this->assertEquals([], $this->Form->fields);

        $this->Form->text('text_input', [
            'name' => 'Option[General.default_role]',
        ]);
        $expected = ['Option.General.default_role'];
        $this->assertEquals($expected, $this->Form->fields);

        $this->Form->select('select_box', [1, 2], [
            'name' => 'Option[General.select_role]',
        ]);
        $expected[] = 'Option.General.select_role';
        $this->assertEquals($expected, $this->Form->fields);

        $this->Form->text('other.things[]');
        $expected[] = 'other.things';
        $this->assertEquals($expected, $this->Form->fields);
    }

    /**
     * testSecuredInputDuplicate method
     *
     * Test that a hidden field followed by a visible field
     * undoes the hidden field locking.
     *
     * @return void
     */
    public function testSecuredInputDuplicate()
    {
        $this->Form->request->params['_Token'] = ['key' => 'testKey'];
        $this->assertEquals([], $this->Form->fields);

        $this->Form->input('text_val', [
                'type' => 'hidden',
                'value' => 'some text',
        ]);
        $expected = ['text_val' => 'some text'];
        $this->assertEquals($expected, $this->Form->fields);

        $this->Form->input('text_val', [
                'type' => 'text',
        ]);
        $expected = ['text_val'];
        $this->assertEquals($expected, $this->Form->fields);
    }

    /**
     * testFormSecuredFileInput method
     *
     * Tests that the correct keys are added to the field hash index.
     *
     * @return void
     */
    public function testFormSecuredFileInput()
    {
        $this->assertEquals([], $this->Form->fields);

        $this->Form->file('Attachment.file');
        $expected = [
            'Attachment.file.name', 'Attachment.file.type',
            'Attachment.file.tmp_name', 'Attachment.file.error',
            'Attachment.file.size'
        ];
        $this->assertEquals($expected, $this->Form->fields);
    }

    /**
     * testFormSecuredMultipleSelect method
     *
     * Test that multiple selects keys are added to field hash.
     *
     * @return void
     */
    public function testFormSecuredMultipleSelect()
    {
        $this->Form->request->params['_csrfToken'] = 'testKey';
        $this->assertEquals([], $this->Form->fields);
        $options = ['1' => 'one', '2' => 'two'];

        $this->Form->select('Model.select', $options);
        $expected = ['Model.select'];
        $this->assertEquals($expected, $this->Form->fields);

        $this->Form->fields = [];
        $this->Form->select('Model.select', $options, ['multiple' => true]);
        $this->assertEquals($expected, $this->Form->fields);
    }

    /**
     * testFormSecuredRadio method
     *
     * @return void
     */
    public function testFormSecuredRadio()
    {
        $this->Form->request->params['_Token'] = 'testKey';

        $this->assertEquals([], $this->Form->fields);
        $options = ['1' => 'option1', '2' => 'option2'];

        $this->Form->radio('Test.test', $options);
        $expected = ['Test.test'];
        $this->assertEquals($expected, $this->Form->fields);

        $this->Form->radio('Test.all', $options, [
            'disabled' => ['option1', 'option2']
        ]);
        $expected = ['Test.test', 'Test.all' => ''];
        $this->assertEquals($expected, $this->Form->fields);

        $this->Form->radio('Test.some', $options, [
            'disabled' => ['option1']
        ]);
        $expected = ['Test.test', 'Test.all' => '', 'Test.some'];
        $this->assertEquals($expected, $this->Form->fields);
    }

    /**
     * testFormSecuredAndDisabledNotAssoc method
     *
     * Test that when disabled is in a list based attribute array it works.
     *
     * @return void
     */
    public function testFormSecuredAndDisabledNotAssoc()
    {
        $this->Form->request->params['_Token'] = 'testKey';

        $this->Form->select('Model.select', [1, 2], ['disabled']);
        $this->Form->checkbox('Model.checkbox', ['disabled']);
        $this->Form->text('Model.text', ['disabled']);
        $this->Form->textarea('Model.textarea', ['disabled']);
        $this->Form->password('Model.password', ['disabled']);
        $this->Form->radio('Model.radio', [1, 2], ['disabled']);

        $expected = [
            'Model.radio' => ''
        ];
        $this->assertEquals($expected, $this->Form->fields);
    }

    /**
     * testFormSecuredAndDisabled method
     *
     * Test that forms with disabled inputs + secured forms leave off the inputs from the form
     * hashing.
     *
     * @return void
     */
    public function testFormSecuredAndDisabled()
    {
        $this->Form->request->params['_Token'] = 'testKey';

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
            'Model.radio' => ''
        ];
        $this->assertEquals($expected, $this->Form->fields);
    }

    /**
     * testDisableSecurityUsingForm method
     *
     * @return void
     */
    public function testDisableSecurityUsingForm()
    {
        $this->Form->request['_Token'] = [
            'disabledFields' => []
        ];
        $this->Form->create();

        $this->Form->hidden('Addresses.id', ['value' => '123456']);
        $this->Form->text('Addresses.title');
        $this->Form->text('Addresses.first_name', ['secure' => false]);
        $this->Form->textarea('Addresses.city', ['secure' => false]);
        $this->Form->select('Addresses.zip', [1, 2], ['secure' => false]);

        $result = $this->Form->fields;
        $expected = [
            'Addresses.id' => '123456', 'Addresses.title',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * testUnlockFieldAddsToList method
     *
     * Test disableField.
     *
     * @return void
     */
    public function testUnlockFieldAddsToList()
    {
        $this->Form->request['_Token'] = [
            'unlockedFields' => []
        ];
        $this->Form->unlockField('Contact.name');
        $this->Form->text('Contact.name');

        $this->assertEquals(['Contact.name'], $this->Form->unlockField());
        $this->assertEquals([], $this->Form->fields);
    }

    /**
     * testUnlockFieldRemovingFromFields method
     *
     * Test unlockField removing from fields array.
     *
     * @return void
     */
    public function testUnlockFieldRemovingFromFields()
    {
        $this->Form->request['_Token'] = [
            'unlockedFields' => []
        ];
        $this->Form->create($this->article);
        $this->Form->hidden('Article.id', ['value' => 1]);
        $this->Form->text('Article.title');

        $this->assertEquals(1, $this->Form->fields['Article.id'], 'Hidden input should be secured.');
        $this->assertTrue(in_array('Article.title', $this->Form->fields), 'Field should be secured.');

        $this->Form->unlockField('Article.title');
        $this->Form->unlockField('Article.id');
        $this->assertEquals([], $this->Form->fields);
    }

    /**
     * testResetUnlockFields method
     *
     * Test reset unlockFields, when create new form.
     *
     * @return void
     */
    public function testResetUnlockFields()
    {
        $this->Form->request['_Token'] = [
            'key' => 'testKey',
            'unlockedFields' => []
        ];

        $this->Form->unlockField('Contact.id');
        $this->Form->create('Contact');
        $this->Form->hidden('Contact.id', ['value' => 1]);
        $this->assertEmpty($this->Form->fields, 'Field should be unlocked');
        $this->Form->end();

        $this->Form->create('Contact');
        $this->Form->hidden('Contact.id', ['value' => 1]);
        $this->assertEquals(1, $this->Form->fields['Contact.id'], 'Hidden input should be secured.');
    }

    /**
     * testSecuredFormUrlIgnoresHost method
     *
     * Test that only the path + query elements of a form's URL show up in their hash.
     *
     * @return void
     */
    public function testSecuredFormUrlIgnoresHost()
    {
        $this->Form->request['_Token'] = ['key' => 'testKey'];

        $expected = '0ff0c85cd70584d8fd18fa136846d22c66c21e2d%3A';
        $this->Form->create($this->article, [
            'url' => ['controller' => 'articles', 'action' => 'view', 1, '?' => ['page' => 1]]
        ]);
        $result = $this->Form->secure();
        $this->assertContains($expected, $result);

        $this->Form->create($this->article, ['url' => 'http://localhost/articles/view/1?page=1']);
        $result = $this->Form->secure();
        $this->assertContains($expected, $result, 'Full URL should only use path and query.');

        $this->Form->create($this->article, ['url' => '/articles/view/1?page=1']);
        $result = $this->Form->secure();
        $this->assertContains($expected, $result, 'URL path + query should work.');

        $this->Form->create($this->article, ['url' => '/articles/view/1']);
        $result = $this->Form->secure();
        $this->assertNotContains($expected, $result, 'URL is different');
    }

    /**
     * testSecuredFormUrlHasHtmlAndIdentifer method
     *
     * Test that URL, HTML and identifer show up in their hashs.
     *
     * @return void
     */
    public function testSecuredFormUrlHasHtmlAndIdentifer()
    {
        $this->Form->request['_Token'] = ['key' => 'testKey'];

        $expected = 'ece0693fb1b19ca116133db1832ac29baaf41ce5%3A';
        $res = $this->Form->create($this->article, [
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
        $this->assertContains($expected, $result);

        $this->Form->create($this->article, [
            'url' => 'http://localhost/articles/view?page=1&limit=10&html=%3C%3E%22#result'
        ]);
        $result = $this->Form->secure();
        $this->assertContains($expected, $result, 'Full URL should only use path and query.');

        $this->Form->create($this->article, [
            'url' => '/articles/view?page=1&limit=10&html=%3C%3E%22#result'
        ]);
        $result = $this->Form->secure();
        $this->assertContains($expected, $result, 'URL path + query should work.');
    }

    /**
     * testErrorMessageDisplay method
     *
     * Test error message display.
     *
     * @return void
     */
    public function testErrorMessageDisplay()
    {
        $this->article['errors'] = [
            'Article' => [
                'title' => 'error message',
                'content' => 'some <strong>test</strong> data with <a href="#">HTML</a> chars'
            ]
        ];
        $this->Form->create($this->article);

        $result = $this->Form->input('Article.title');
        $expected = [
            'div' => ['class' => 'input text error'],
            'label' => ['for' => 'article-title'],
            'Title',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'Article[title]',
                'id' => 'article-title', 'class' => 'form-error'
            ],
            ['div' => ['class' => 'error-message']],
            'error message',
            '/div',
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('Article.title', [
            'templates' => [
                'inputContainerError' => '<div class="input {{type}}{{required}} error">{{content}}</div>'
            ]
        ]);

        $expected = [
            'div' => ['class' => 'input text error'],
            'label' => ['for' => 'article-title'],
            'Title',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'Article[title]',
                'id' => 'article-title', 'class' => 'form-error'
            ],
            '/div'
        ];
        $this->assertHtml($expected, $result);


        $result = $this->Form->input('Article.content');
        $expected = [
            'div' => ['class' => 'input text error'],
            'label' => ['for' => 'article-content'],
            'Content',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'Article[content]',
                'id' => 'article-content', 'class' => 'form-error'
            ],
            ['div' => ['class' => 'error-message']],
            'some &lt;strong&gt;test&lt;/strong&gt; data with &lt;a href=&quot;#&quot;&gt;HTML&lt;/a&gt; chars',
            '/div',
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('Article.content', ['error' => ['escape' => true]]);
        $expected = [
            'div' => ['class' => 'input text error'],
            'label' => ['for' => 'article-content'],
            'Content',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'Article[content]',
                'id' => 'article-content', 'class' => 'form-error'
            ],
            ['div' => ['class' => 'error-message']],
            'some &lt;strong&gt;test&lt;/strong&gt; data with &lt;a href=&quot;#&quot;&gt;HTML&lt;/a&gt; chars',
            '/div',
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('Article.content', ['error' => ['escape' => false]]);
        $expected = [
            'div' => ['class' => 'input text error'],
            'label' => ['for' => 'article-content'],
            'Content',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'Article[content]',
                'id' => 'article-content', 'class' => 'form-error'
            ],
            ['div' => ['class' => 'error-message']],
            'some <strong>test</strong> data with <a href="#">HTML</a> chars',
            '/div',
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testEmptyErrorValidation method
     *
     * Test validation errors, when validation message is an empty string.
     *
     * @return void
     */
    public function testEmptyErrorValidation()
    {
        $this->article['errors'] = [
            'Article' => ['title' => '']
        ];
        $this->Form->create($this->article);

        $result = $this->Form->input('Article.title');
        $expected = [
            'div' => ['class' => 'input text error'],
            'label' => ['for' => 'article-title'],
            'Title',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'Article[title]',
                'id' => 'article-title', 'class' => 'form-error'
            ],
            ['div' => ['class' => 'error-message']],
            [],
            '/div',
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testEmptyInputErrorValidation method
     *
     * Test validation errors, when calling input() overriding validation message by an empty string.
     *
     * @return void
     */
    public function testEmptyInputErrorValidation()
    {
        $this->article['errors'] = [
            'Article' => ['title' => 'error message']
        ];
        $this->Form->create($this->article);

        $result = $this->Form->input('Article.title', ['error' => '']);
        $expected = [
            'div' => ['class' => 'input text error'],
            'label' => ['for' => 'article-title'],
            'Title',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'Article[title]',
                'id' => 'article-title', 'class' => 'form-error'
            ],
            ['div' => ['class' => 'error-message']],
            [],
            '/div',
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testInputErrorMessage method
     *
     * Test validation errors, when calling input() overriding validation messages.
     *
     * @return void
     */
    public function testInputErrorMessage()
    {
        $this->article['errors'] = [
            'title' => ['error message']
        ];
        $this->Form->create($this->article);

        $result = $this->Form->input('title', [
            'error' => 'Custom error!'
        ]);
        $expected = [
            'div' => ['class' => 'input text required error'],
            'label' => ['for' => 'title'],
            'Title',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'title',
                'id' => 'title', 'class' => 'form-error',
                'required' => 'required',
            ],
            ['div' => ['class' => 'error-message']],
            'Custom error!',
            '/div',
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('title', [
            'error' => ['error message' => 'Custom error!']
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
                'required' => 'required'
            ],
            ['div' => ['class' => 'error-message']],
            'Custom error!',
            '/div',
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormValidationAssociated method
     *
     * Tests displaying errors for nested entities.
     *
     * @return void
     */
    public function testFormValidationAssociated()
    {
        $nested = new Entity(['foo' => 'bar']);
        $nested->errors('foo', ['not a valid bar']);
        $entity = new Entity(['nested' => $nested]);
        $this->Form->create($entity, ['context' => ['table' => 'Articles']]);

        $result = $this->Form->error('nested.foo');
        $this->assertEquals('<div class="error-message">not a valid bar</div>', $result);
    }

    /**
     * testFormValidationAssociatedSecondLevel method
     *
     * Test form error display with associated model.
     *
     * @return void
     */
    public function testFormValidationAssociatedSecondLevel()
    {
        $inner = new Entity(['bar' => 'baz']);
        $nested = new Entity(['foo' => $inner]);
        $entity = new Entity(['nested' => $nested]);
        $inner->errors('bar', ['not a valid one']);
        $this->Form->create($entity, ['context' => ['table' => 'Articles']]);
        $result = $this->Form->error('nested.foo.bar');
        $this->assertEquals('<div class="error-message">not a valid one</div>', $result);
    }

    /**
     * testFormValidationMultiRecord method
     *
     * Test form error display with multiple records.
     *
     * @return void
     */
    public function testFormValidationMultiRecord()
    {
        $one = new Entity();
        $two = new Entity();
        TableRegistry::get('Contacts', [
            'className' => __NAMESPACE__ . '\ContactsTable'
        ]);
        $one->set('email', '');
        $one->errors('email', ['invalid email']);

        $two->set('name', '');
        $two->errors('name', ['This is wrong']);
        $this->Form->create([$one, $two], ['context' => ['table' => 'Contacts']]);

        $result = $this->Form->input('0.email');
        $expected = [
            'div' => ['class' => 'input email error'],
            'label' => ['for' => '0-email'],
            'Email',
            '/label',
            'input' => [
                'type' => 'email', 'name' => '0[email]', 'id' => '0-email',
                'class' => 'form-error', 'maxlength' => 255, 'value' => '',
            ],
            ['div' => ['class' => 'error-message']],
            'invalid email',
            '/div',
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('1.name');
        $expected = [
            'div' => ['class' => 'input text error'],
            'label' => ['for' => '1-name'],
            'Name',
            '/label',
            'input' => [
                'type' => 'text', 'name' => '1[name]', 'id' => '1-name',
                'class' => 'form-error', 'maxlength' => 255, 'value' => ''
            ],
            ['div' => ['class' => 'error-message']],
            'This is wrong',
            '/div',
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testInput method
     *
     * Test various incarnations of input().
     *
     * @return void
     */
    public function testInput()
    {
        TableRegistry::get('ValidateUsers', [
            'className' => __NAMESPACE__ . '\ValidateUsersTable'
        ]);
        $this->Form->create([], ['context' => ['table' => 'ValidateUsers']]);
        $result = $this->Form->input('ValidateUsers.balance');
        $expected = [
            'div' => ['class'],
            'label' => ['for'],
            'Balance',
            '/label',
            'input' => ['name', 'type' => 'number', 'id', 'step'],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('ValidateUser.cost_decimal');
        $expected = [
            'div' => ['class'],
            'label' => ['for'],
            'Cost Decimal',
            '/label',
            'input' => ['name', 'type' => 'number', 'step' => '0.001', 'id'],
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('ValidateUser.null_decimal');
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
     * testInputCustomization method
     *
     * Tests the input method and passing custom options.
     *
     * @return void
     */
    public function testInputCustomization()
    {
        TableRegistry::get('Contacts', [
            'className' => __NAMESPACE__ . '\ContactsTable'
        ]);
        $this->Form->create([], ['context' => ['table' => 'Contacts']]);
        $result = $this->Form->input('Contact.email', ['id' => 'custom']);
        $expected = [
            'div' => ['class' => 'input email'],
            'label' => ['for' => 'custom'],
            'Email',
            '/label',
            ['input' => [
                'type' => 'email', 'name' => 'Contact[email]',
                'id' => 'custom', 'maxlength' => 255
            ]],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('Contact.email', [
            'templates' => ['inputContainer' => '<div>{{content}}</div>']
        ]);
        $expected = [
            '<div',
            'label' => ['for' => 'contact-email'],
            'Email',
            '/label',
            ['input' => [
                'type' => 'email', 'name' => 'Contact[email]',
                'id' => 'contact-email', 'maxlength' => 255
            ]],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('Contact.email', ['type' => 'text']);
        $expected = [
            'div' => ['class' => 'input text'],
            'label' => ['for' => 'contact-email'],
            'Email',
            '/label',
            ['input' => [
                'type' => 'text', 'name' => 'Contact[email]',
                'id' => 'contact-email', 'maxlength' => '255'
            ]],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('Contact.5.email', ['type' => 'text']);
        $expected = [
            'div' => ['class' => 'input text'],
            'label' => ['for' => 'contact-5-email'],
            'Email',
            '/label',
            ['input' => [
                'type' => 'text', 'name' => 'Contact[5][email]',
                'id' => 'contact-5-email', 'maxlength' => '255'
            ]],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('Contact.password');
        $expected = [
            'div' => ['class' => 'input password'],
            'label' => ['for' => 'contact-password'],
            'Password',
            '/label',
            ['input' => [
                'type' => 'password', 'name' => 'Contact[password]',
                'id' => 'contact-password'
            ]],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('Contact.email', [
            'type' => 'file', 'class' => 'textbox'
        ]);
        $expected = [
            'div' => ['class' => 'input file'],
            'label' => ['for' => 'contact-email'],
            'Email',
            '/label',
            ['input' => [
                'type' => 'file', 'name' => 'Contact[email]', 'class' => 'textbox',
                'id' => 'contact-email'
            ]],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $entity = new Entity(['phone' => 'Hello & World > weird chars']);
        $this->Form->create($entity, ['context' => ['table' => 'Contacts']]);
        $result = $this->Form->input('phone');
        $expected = [
            'div' => ['class' => 'input tel'],
            'label' => ['for' => 'phone'],
            'Phone',
            '/label',
            ['input' => [
                'type' => 'tel', 'name' => 'phone',
                'value' => 'Hello &amp; World &gt; weird chars',
                'id' => 'phone', 'maxlength' => 255
            ]],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $this->Form->request->data['Model']['0']['OtherModel']['field'] = 'My value';
        $this->Form->create();
        $result = $this->Form->input('Model.0.OtherModel.field', ['id' => 'myId']);
        $expected = [
            'div' => ['class' => 'input text'],
            'label' => ['for' => 'myId'],
            'Field',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'Model[0][OtherModel][field]',
                'value' => 'My value', 'id' => 'myId'
            ],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $this->Form->request->data = [];

        $entity->errors('field', 'Badness!');
        $this->Form->create($entity, ['context' => ['table' => 'Contacts']]);
        $result = $this->Form->input('field');
        $expected = [
            'div' => ['class' => 'input text error'],
            'label' => ['for' => 'field'],
            'Field',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'field',
                'id' => 'field', 'class' => 'form-error'
            ],
            ['div' => ['class' => 'error-message']],
            'Badness!',
            '/div',
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('field', [
            'templates' => [
                'inputContainerError' => '{{content}}{{error}}',
                'error' => '<span class="error-message">{{content}}</span>'
            ]
        ]);
        $expected = [
            'label' => ['for' => 'field'],
            'Field',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'field',
                'id' => 'field', 'class' => 'form-error'
            ],
            ['span' => ['class' => 'error-message']],
            'Badness!',
            '/span'
        ];
        $this->assertHtml($expected, $result);

        $entity->errors('field', ['minLength'], true);
        $result = $this->Form->input('field', [
            'error' => [
                'minLength' => 'Le login doit contenir au moins 2 caractères',
                'maxLength' => 'login too large'
            ]
        ]);
        $expected = [
            'div' => ['class' => 'input text error'],
            'label' => ['for' => 'field'],
            'Field',
            '/label',
            'input' => ['type' => 'text', 'name' => 'field', 'id' => 'field', 'class' => 'form-error'],
            ['div' => ['class' => 'error-message']],
            'Le login doit contenir au moins 2 caractères',
            '/div',
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $entity->errors('field', ['maxLength'], true);
        $result = $this->Form->input('field', [
            'error' => [
                'minLength' => 'Le login doit contenir au moins 2 caractères',
                'maxLength' => 'login too large',
            ]
        ]);
        $expected = [
            'div' => ['class' => 'input text error'],
            'label' => ['for' => 'field'],
            'Field',
            '/label',
            'input' => ['type' => 'text', 'name' => 'field', 'id' => 'field', 'class' => 'form-error'],
            ['div' => ['class' => 'error-message']],
            'login too large',
            '/div',
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testInputWithTemplateFile method
     *
     * Test that input() accepts a template file.
     *
     * @return void
     */
    public function testInputWithTemplateFile()
    {
        $result = $this->Form->input('field', [
            'templates' => 'htmlhelper_tags'
        ]);
        $expected = [
            'label' => ['for' => 'field'],
            'Field',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'field',
                'id' => 'field'
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testNestedInputsEndWithBrackets method
     *
     * Test that nested inputs end with brackets.
     *
     * @return void
     */
    public function testNestedInputsEndWithBrackets()
    {
        $result = $this->Form->text('nested.text[]');
        $expected = [
            'input' => [
                'type' => 'text', 'name' => 'nested[text][]'
            ],
        ];

        $result = $this->Form->file('nested.file[]');
        $expected = [
            'input' => [
                'type' => 'file', 'name' => 'nested[file][]'
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testCreateIdPrefix method
     *
     * Test id prefix.
     *
     * @return void
     */
    public function testCreateIdPrefix()
    {
        $this->Form->create(false, ['idPrefix' => 'prefix']);

        $result = $this->Form->input('field');
        $expected = [
            'div' => ['class' => 'input text'],
            'label' => ['for' => 'prefix-field'],
            'Field',
            '/label',
            'input' => ['type' => 'text', 'name' => 'field', 'id' => 'prefix-field'],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('field', ['id' => 'custom-id']);
        $expected = [
            'div' => ['class' => 'input text'],
            'label' => ['for' => 'custom-id'],
            'Field',
            '/label',
            'input' => ['type' => 'text', 'name' => 'field', 'id' => 'custom-id'],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->radio('Model.field', ['option A']);
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'Model[field]', 'value' => ''],
            'label' => ['for' => 'prefix-model-field-0'],
            ['input' => [
                'type' => 'radio',
                'name' => 'Model[field]',
                'value' => '0',
                'id' => 'prefix-model-field-0'
            ]],
            'option A',
            '/label'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->radio('Model.field', ['option A', 'option']);
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'Model[field]', 'value' => ''],
            'label' => ['for' => 'prefix-model-field-0'],
            ['input' => [
                'type' => 'radio',
                'name' => 'Model[field]',
                'value' => '0',
                'id' => 'prefix-model-field-0'
            ]],
            'option A',
            '/label'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->select(
            'Model.multi_field',
            ['first'],
            ['multiple' => 'checkbox']
        );
        $expected = [
            'input' => [
                'type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => ''
            ],
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'prefix-model-multi-field-0']],
            ['input' => [
                'type' => 'checkbox', 'name' => 'Model[multi_field][]',
                'value' => '0', 'id' => 'prefix-model-multi-field-0'
            ]],
            'first',
            '/label',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $this->Form->end();
        $result = $this->Form->input('field');
        $expected = [
            'div' => ['class' => 'input text'],
            'label' => ['for' => 'field'],
            'Field',
            '/label',
            'input' => ['type' => 'text', 'name' => 'field', 'id' => 'field'],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testInputZero method
     *
     * Test that inputs with 0 can be created.
     *
     * @return void
     */
    public function testInputZero()
    {
        TableRegistry::get('Contacts', [
            'className' => __NAMESPACE__ . '\ContactsTable'
        ]);
        $this->Form->create([], ['context' => ['table' => 'Contacts']]);
        $result = $this->Form->input('0');
        $expected = [
            'div' => ['class' => 'input text'],
            'label' => ['for' => '0'], '/label',
            'input' => ['type' => 'text', 'name' => '0', 'id' => '0'],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testInputCheckbox method
     *
     * Test input() with checkbox creation.
     *
     * @return void
     */
    public function testInputCheckbox()
    {
        $result = $this->Form->input('User.active', ['label' => false, 'checked' => true]);
        $expected = [
            'div' => ['class' => 'input checkbox'],
            'input' => ['type' => 'hidden', 'name' => 'User[active]', 'value' => '0'],
            ['input' => ['type' => 'checkbox', 'name' => 'User[active]', 'value' => '1', 'id' => 'user-active', 'checked' => 'checked']],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('User.active', ['label' => false, 'checked' => 1]);
        $expected = [
            'div' => ['class' => 'input checkbox'],
            'input' => ['type' => 'hidden', 'name' => 'User[active]', 'value' => '0'],
            ['input' => ['type' => 'checkbox', 'name' => 'User[active]', 'value' => '1', 'id' => 'user-active', 'checked' => 'checked']],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('User.active', ['label' => false, 'checked' => '1']);
        $expected = [
            'div' => ['class' => 'input checkbox'],
            'input' => ['type' => 'hidden', 'name' => 'User[active]', 'value' => '0'],
            ['input' => ['type' => 'checkbox', 'name' => 'User[active]', 'value' => '1', 'id' => 'user-active', 'checked' => 'checked']],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('User.disabled', [
            'label' => 'Disabled',
            'type' => 'checkbox',
            'data-foo' => 'disabled'
        ]);
        $expected = [
            'div' => ['class' => 'input checkbox'],
            'input' => ['type' => 'hidden', 'name' => 'User[disabled]', 'value' => '0'],
            'label' => ['for' => 'user-disabled'],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'User[disabled]',
                'value' => '1',
                'id' => 'user-disabled',
                'data-foo' => 'disabled'
            ]],
            'Disabled',
            '/label',
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('User.confirm', [
            'label' => 'Confirm <b>me</b>!',
            'type' => 'checkbox',
            'escape' => false
        ]);
        $expected = [
            'div' => ['class' => 'input checkbox'],
            'input' => ['type' => 'hidden', 'name' => 'User[confirm]', 'value' => '0'],
            'label' => ['for' => 'user-confirm'],
            ['input' => [
                'type' => 'checkbox',
                'name' => 'User[confirm]',
                'value' => '1',
                'id' => 'user-confirm',
            ]],
            'Confirm <b>me</b>!',
            '/label',
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testInputHidden method
     *
     * Test that input() does not create wrapping div and label tag for hidden fields.
     *
     * @return void
     */
    public function testInputHidden()
    {
        TableRegistry::get('ValidateUsers', [
            'className' => __NAMESPACE__ . '\ValidateUsersTable'
        ]);
        $this->Form->create([], ['context' => ['table' => 'ValidateUsers']]);

        $result = $this->Form->input('ValidateUser.id');
        $expected = [
            'input' => ['name', 'type' => 'hidden', 'id']
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('ValidateUser.custom', ['type' => 'hidden']);
        $expected = [
            'input' => ['name', 'type' => 'hidden', 'id']
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testInputDatetime method
     *
     * Test form->input() with datetime.
     *
     * @return void
     */
    public function testInputDatetime()
    {
        $this->Form = $this->getMockBuilder('Cake\View\Helper\FormHelper')
            ->setMethods(['datetime'])
            ->setConstructorArgs([new View()])
            ->getMock();
        $this->Form->expects($this->once())->method('datetime')
            ->with('prueba', [
                'type' => 'datetime',
                'timeFormat' => 24,
                'minYear' => 2008,
                'maxYear' => 2011,
                'interval' => 15,
                'options' => null,
                'empty' => false,
                'id' => 'prueba',
                'required' => false,
                'templateVars' => []
            ])
            ->will($this->returnValue('This is it!'));
        $result = $this->Form->input('prueba', [
            'type' => 'datetime', 'timeFormat' => 24, 'minYear' => 2008,
            'maxYear' => 2011, 'interval' => 15
        ]);
        $expected = [
            'div' => ['class' => 'input datetime'],
            '<label',
            'Prueba',
            '/label',
            'This is it!',
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testInputDatetimeIdPrefix method
     *
     * Test form->input() with datetime with id prefix.
     *
     * @return void
     */
    public function testInputDatetimeIdPrefix()
    {
        $this->Form = $this->getMockBuilder('Cake\View\Helper\FormHelper')
            ->setMethods(['datetime'])
            ->setConstructorArgs([new View()])
            ->getMock();

        $this->Form->create(false, ['idPrefix' => 'prefix']);

        $this->Form->expects($this->once())->method('datetime')
            ->with('prueba', [
                'type' => 'datetime',
                'timeFormat' => 24,
                'minYear' => 2008,
                'maxYear' => 2011,
                'interval' => 15,
                'options' => null,
                'empty' => false,
                'id' => 'prefix-prueba',
                'required' => false,
                'templateVars' => []
            ])
            ->will($this->returnValue('This is it!'));
        $result = $this->Form->input('prueba', [
            'type' => 'datetime', 'timeFormat' => 24, 'minYear' => 2008,
            'maxYear' => 2011, 'interval' => 15
        ]);
        $expected = [
            'div' => ['class' => 'input datetime'],
            '<label',
            'Prueba',
            '/label',
            'This is it!',
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testInputCheckboxWithDisabledElements method
     *
     * Test generating checkboxes with disabled elements.
     *
     * @return void
     */
    public function testInputCheckboxWithDisabledElements()
    {
        $options = [1 => 'One', 2 => 'Two', '3' => 'Three'];
        $result = $this->Form->input('Contact.multiple', [
            'multiple' => 'checkbox',
            'disabled' => 'disabled',
            'options' => $options
        ]);

        $expected = [
            ['div' => ['class' => 'input select']],
            ['label' => ['for' => "contact-multiple"]],
            'Multiple',
            '/label',
            ['input' => ['type' => 'hidden', 'name' => "Contact[multiple]", 'disabled' => 'disabled', 'value' => '']],
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => "contact-multiple-1"]],
            ['input' => ['type' => 'checkbox', 'name' => "Contact[multiple][]", 'value' => 1, 'disabled' => 'disabled', 'id' => "contact-multiple-1"]],
            'One',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => "contact-multiple-2"]],
            ['input' => ['type' => 'checkbox', 'name' => "Contact[multiple][]", 'value' => 2, 'disabled' => 'disabled', 'id' => "contact-multiple-2"]],
            'Two',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => "contact-multiple-3"]],
            ['input' => ['type' => 'checkbox', 'name' => "Contact[multiple][]", 'value' => 3, 'disabled' => 'disabled', 'id' => "contact-multiple-3"]],
            'Three',
            '/label',
            '/div',
            '/div'
        ];
        $this->assertHtml($expected, $result);

        // make sure 50 does only disable 50, and not 50f5c0cf
        $options = ['50' => 'Fifty', '50f5c0cf' => 'Stringy'];
        $disabled = [50];

        $expected = [
            ['div' => ['class' => 'input select']],
            ['label' => ['for' => "contact-multiple"]],
            'Multiple',
            '/label',
            ['input' => ['type' => 'hidden', 'name' => "Contact[multiple]", 'value' => '']],
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => "contact-multiple-50"]],
            ['input' => ['type' => 'checkbox', 'name' => "Contact[multiple][]", 'value' => 50, 'disabled' => 'disabled', 'id' => "contact-multiple-50"]],
            'Fifty',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => "contact-multiple-50f5c0cf"]],
            ['input' => ['type' => 'checkbox', 'name' => "Contact[multiple][]", 'value' => '50f5c0cf', 'id' => "contact-multiple-50f5c0cf"]],
            'Stringy',
            '/label',
            '/div',
            '/div'
        ];
        $result = $this->Form->input('Contact.multiple', ['multiple' => 'checkbox', 'disabled' => $disabled, 'options' => $options]);
        $this->assertHtml($expected, $result);
    }

    /**
     * testInputWithLeadingInteger method
     *
     * Test input name with leading integer, ensure attributes are generated correctly.
     *
     * @return void
     */
    public function testInputWithLeadingInteger()
    {
        $result = $this->Form->text('0.Node.title');
        $expected = [
            'input' => ['name' => '0[Node][title]', 'type' => 'text']
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testInputSelectType method
     *
     * Test form->input() with select type inputs.
     *
     * @return void
     */
    public function testInputSelectType()
    {
        $result = $this->Form->input(
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
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input(
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
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('email', [
            'type' => 'select',
            'options' => new \ArrayObject(['First', 'Second']),
            'empty' => true
        ]);
        $this->assertHtml($expected, $result);

        $this->View->viewVars['users'] = ['value' => 'good', 'other' => 'bad'];
        $this->Form->request->data = ['Model' => ['user_id' => 'value']];

        $result = $this->Form->input('Model.user_id', ['empty' => true]);
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
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $this->View->viewVars['users'] = ['value' => 'good', 'other' => 'bad'];
        $this->Form->request->data = ['Thing' => ['user_id' => null]];
        $result = $this->Form->input('Thing.user_id', ['empty' => 'Some Empty']);
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
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $this->View->viewVars['users'] = ['value' => 'good', 'other' => 'bad'];
        $this->Form->request->data = ['Thing' => ['user_id' => 'value']];
        $result = $this->Form->input('Thing.user_id', ['empty' => 'Some Empty']);
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
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $this->Form->data = [];
        $result = $this->Form->input('Publisher.id', [
                'label' => 'Publisher',
                'type' => 'select',
                'multiple' => 'checkbox',
                'options' => ['Value 1' => 'Label 1', 'Value 2' => 'Label 2']
        ]);
        $expected = [
            ['div' => ['class' => 'input select']],
                ['label' => ['for' => 'publisher-id']],
                'Publisher',
                '/label',
                'input' => ['type' => 'hidden', 'name' => 'Publisher[id]', 'value' => ''],
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
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testInputWithNonStandardPrimaryKeyMakesHidden method
     *
     * Test that input() and a non standard primary key makes a hidden input by default.
     *
     * @return void
     */
    public function testInputWithNonStandardPrimaryKeyMakesHidden()
    {
        $this->article['schema']['_constraints']['primary']['columns'] = ['title'];
        $this->Form->create($this->article);
        $result = $this->Form->input('title');
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'title', 'id' => 'title'],
        ];
        $this->assertHtml($expected, $result);

        $this->article['schema']['_constraints']['primary']['columns'] = ['title', 'body'];
        $this->Form->create($this->article);
        $result = $this->Form->input('title');
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'title', 'id' => 'title'],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('body');
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'body', 'id' => 'body'],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testInputOverridingMagicSelectType method
     *
     * Test that overriding the magic select type widget is possible.
     *
     * @return void
     */
    public function testInputOverridingMagicSelectType()
    {
        $this->View->viewVars['users'] = ['value' => 'good', 'other' => 'bad'];
        $result = $this->Form->input('Model.user_id', ['type' => 'text']);
        $expected = [
            'div' => ['class' => 'input text'],
            'label' => ['for' => 'model-user-id'], 'User', '/label',
            'input' => ['name' => 'Model[user_id]', 'type' => 'text', 'id' => 'model-user-id'],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        //Check that magic types still work for plural/singular vars
        $this->View->viewVars['types'] = ['value' => 'good', 'other' => 'bad'];
        $result = $this->Form->input('Model.type');
        $expected = [
            'div' => ['class' => 'input select'],
            'label' => ['for' => 'model-type'], 'Type', '/label',
            'select' => ['name' => 'Model[type]', 'id' => 'model-type'],
            ['option' => ['value' => 'value']], 'good', '/option',
            ['option' => ['value' => 'other']], 'bad', '/option',
            '/select',
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testInputMagicTypeDoesNotOverride method
     *
     * Test that inferred types do not override developer input.
     *
     * @return void
     */
    public function testInputMagicTypeDoesNotOverride()
    {
        $this->View->viewVars['users'] = ['value' => 'good', 'other' => 'bad'];
        $result = $this->Form->input('Model.user', ['type' => 'checkbox']);
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
                'value' => 1
            ]],
            'User',
            '/label',
            '/div'
        ];
        $this->assertHtml($expected, $result);

        // make sure that for HABTM the multiple option is not being overwritten in case it's truly
        $options = [
            1 => 'blue',
            2 => 'red'
        ];
        $result = $this->Form->input('tags._ids', ['options' => $options, 'multiple' => 'checkbox']);
        $expected = [
            'div' => ['class' => 'input select'],
            'label' => ['for' => 'tags-ids'],
            'Tags',
            '/label',
            'input' => ['type' => 'hidden', 'name' => 'tags[_ids]', 'value' => ''],

            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'tags-ids-1']],
            ['input' => [
                'id' => 'tags-ids-1', 'type' => 'checkbox',
                'value' => '1', 'name' => 'tags[_ids][]'
            ]],
            'blue',
            '/label',
            '/div',

            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'tags-ids-2']],
            ['input' => [
                'id' => 'tags-ids-2', 'type' => 'checkbox',
                'value' => '2', 'name' => 'tags[_ids][]'
            ]],
            'red',
            '/label',
            '/div',

            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testInputMagicSelectForTypeNumber method
     *
     * Test that magic input() selects are created for type=number.
     *
     * @return void
     */
    public function testInputMagicSelectForTypeNumber()
    {
        TableRegistry::get('ValidateUsers', [
            'className' => __NAMESPACE__ . '\ValidateUsersTable'
        ]);
        $entity = new Entity(['balance' => 1]);
        $this->Form->create($entity, ['context' => ['table' => 'ValidateUsers']]);
        $this->View->viewVars['balances'] = [0 => 'nothing', 1 => 'some', 100 => 'a lot'];
        $result = $this->Form->input('balance');
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
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testInvalidInputTypeOption method
     *
     * Test invalid 'input' type option to input() function.
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Invalid type 'input' used for field 'text'
     * @return void
     */
    public function testInvalidInputTypeOption()
    {
        $this->Form->input('text', ['type' => 'input']);
    }

    /**
     * testInputMagicSelectChangeToRadio method
     *
     * Test that magic input() selects can easily be converted into radio types without error.
     *
     * @return void
     */
    public function testInputMagicSelectChangeToRadio()
    {
        $this->View->viewVars['users'] = ['value' => 'good', 'other' => 'bad'];
        $result = $this->Form->input('Model.user_id', ['type' => 'radio']);
        $this->assertContains('input type="radio"', $result);
    }

    /**
     * testFormInputSubmit method
     *
     * Test correct results for form::input() and type submit.
     *
     * @return void
     */
    public function testFormInputSubmit()
    {
        $result = $this->Form->input('Test Submit', ['type' => 'submit', 'class' => 'foobar']);
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'submit', 'class' => 'foobar', 'id' => 'test-submit', 'value' => 'Test Submit'],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormInputs method
     *
     * Test correct results from form::inputs().
     *
     * @return void
     */
    public function testFormInputsLegendFieldset()
    {
        $this->Form->create($this->article);
        $result = $this->Form->allInputs([], ['legend' => 'The Legend']);
        $expected = [
            '<fieldset',
            '<legend',
            'The Legend',
            '/legend',
            '*/fieldset',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->allInputs([], ['fieldset' => true, 'legend' => 'Field of Dreams']);
        $this->assertContains('<legend>Field of Dreams</legend>', $result);
        $this->assertContains('<fieldset>', $result);

        $result = $this->Form->allInputs([], ['fieldset' => false, 'legend' => false]);
        $this->assertNotContains('<legend>', $result);
        $this->assertNotContains('<fieldset>', $result);

        $result = $this->Form->allInputs([], ['fieldset' => false, 'legend' => 'Hello']);
        $this->assertNotContains('<legend>', $result);
        $this->assertNotContains('<fieldset>', $result);

        $this->Form->create($this->article);
        $this->Form->request->params['prefix'] = 'admin';
        $this->Form->request->params['action'] = 'admin_edit';
        $this->Form->request->params['controller'] = 'articles';
        $result = $this->Form->allInputs();
        $expected = [
            '<fieldset',
            '<legend',
            'New Article',
            '/legend',
            '*/fieldset',
        ];
        $this->assertHtml($expected, $result);

        $this->Form->create($this->article);
        $result = $this->Form->allInputs([], ['fieldset' => [], 'legend' => 'The Legend']);
        $expected = [
            '<fieldset',
            '<legend',
            'The Legend',
            '/legend',
            '*/fieldset',
        ];
        $this->assertHtml($expected, $result);

        $this->Form->create($this->article);
        $result = $this->Form->allInputs([], [
            'fieldset' => [
                'class' => 'some-class some-other-class',
                'disabled' => true,
                'data-param' => 'a-param'
            ],
            'legend' => 'The Legend'
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
     * testFormInputs method
     *
     * Test the inputs() method.
     *
     * @return void
     */
    public function testFormInputs()
    {
        $this->Form->create($this->article);
        $result = $this->Form->allInputs();
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

        $result = $this->Form->allInputs([
            'published' => ['type' => 'boolean']
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
        $result = $this->Form->allInputs([], ['legend' => 'Hello']);
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
            '/fieldset'
        ];
        $this->assertHtml($expected, $result);

        $this->Form->create(false);
        $expected = [
            'fieldset' => [],
            ['div' => ['class' => 'input text']],
            'label' => ['for' => 'foo'],
            'Foo',
            '/label',
            'input' => ['type' => 'text', 'name' => 'foo', 'id' => 'foo'],
            '*/div',
            '/fieldset'
        ];
        $result = $this->Form->allInputs(
            ['foo' => ['type' => 'text']],
            ['legend' => false]
        );
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormInputsBlacklist method
     *
     * @return void
     */
    public function testFormInputsBlacklist()
    {
        $this->Form->create($this->article);
        $result = $this->Form->allInputs([
            'id' => false
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
        $result = $this->Form->allInputs([
            'id' => []
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
        $this->assertHtml($expected, $result, 'A falsey value (array) should not remove the input');
    }

    /**
     * testSelectAsCheckbox method
     *
     * Test multi-select widget with checkbox formatting.
     *
     * @return void
     */
    public function testSelectAsCheckbox()
    {
        $result = $this->Form->select(
            'Model.multi_field',
            ['first', 'second', 'third'],
            ['multiple' => 'checkbox', 'value' => [0, 1]]
        );
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => ''],
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
            'input' => ['type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => ''],
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
     *
     * @return void
     */
    public function testLabel()
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
     * testLabelContainInput method
     *
     * Test that label() can accept an input with the correct template vars.
     *
     * @return void
     */
    public function testLabelContainInput()
    {
        $this->Form->templates([
            'label' => '<label{{attrs}}>{{input}}{{text}}</label>',
        ]);
        $result = $this->Form->label('Person.accept_terms', 'Accept', [
            'input' => '<input type="checkbox" name="accept_tos"/>'
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
     *
     * @return void
     */
    public function testTextbox()
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
     *
     * @return void
     */
    public function testTextBoxDataAndError()
    {
        $this->article['errors'] = [
            'Contact' => ['text' => 'wrong']
        ];
        $this->Form->create($this->article);

        $this->Form->request->data['Model']['text'] = 'test <strong>HTML</strong> values';
        $result = $this->Form->text('Model.text');
        $expected = [
            'input' => [
                'type' => 'text',
                'name' => 'Model[text]',
                'value' => 'test &lt;strong&gt;HTML&lt;/strong&gt; values',
            ]
        ];
        $this->assertHtml($expected, $result);

        $this->Form->request->data['Contact']['text'] = 'test';
        $result = $this->Form->text('Contact.text', ['id' => 'theID']);
        $expected = [
            'input' => [
                'type' => 'text',
                'name' => 'Contact[text]',
                'value' => 'test',
                'id' => 'theID',
                'class' => 'form-error'
            ]
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testDefaultValue method
     *
     * Test default value setting.
     *
     * @return void
     */
    public function testTextDefaultValue()
    {
        $this->Form->request->data['Model']['field'] = 'test';
        $result = $this->Form->text('Model.field', ['default' => 'default value']);
        $expected = ['input' => ['type' => 'text', 'name' => 'Model[field]', 'value' => 'test']];
        $this->assertHtml($expected, $result);

        unset($this->Form->request->data['Model']['field']);
        $result = $this->Form->text('Model.field', ['default' => 'default value']);
        $expected = ['input' => ['type' => 'text', 'name' => 'Model[field]', 'value' => 'default value']];
        $this->assertHtml($expected, $result);

        $Articles = TableRegistry::get('Articles');
        $title = $Articles->schema()->column('title');
        $Articles->schema()->addColumn(
            'title',
            ['default' => 'default title'] + $title
        );

        $entity = $Articles->newEntity();
        $this->Form->create($entity);

        // Get default value from schema
        $result = $this->Form->text('title');
        $expected = ['input' => ['type' => 'text', 'name' => 'title', 'value' => 'default title']];
        $this->assertHtml($expected, $result);

        // Don't get value from schema
        $result = $this->Form->text('title', ['schemaDefault' => false]);
        $expected = ['input' => ['type' => 'text', 'name' => 'title']];
        $this->assertHtml($expected, $result);

        // Custom default value overrides default value from schema
        $result = $this->Form->text('title', ['default' => 'override default']);
        $expected = ['input' => ['type' => 'text', 'name' => 'title', 'value' => 'override default']];
        $this->assertHtml($expected, $result);

        // Default value from schema is used only for new entities.
        $entity->isNew(false);
        $result = $this->Form->text('title');
        $expected = ['input' => ['type' => 'text', 'name' => 'title']];
        $this->assertHtml($expected, $result);
    }

    /**
     * testError method
     *
     * Test field error generation.
     *
     * @return void
     */
    public function testError()
    {
        $this->article['errors'] = [
            'Article' => ['field' => 'email']
        ];
        $this->Form->create($this->article);

        $result = $this->Form->error('Article.field');
        $expected = [
            ['div' => ['class' => 'error-message']],
            'email',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->error('Article.field', "<strong>Badness!</strong>");
        $expected = [
            ['div' => ['class' => 'error-message']],
            '&lt;strong&gt;Badness!&lt;/strong&gt;',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->error('Article.field', "<strong>Badness!</strong>", ['escape' => false]);
        $expected = [
            ['div' => ['class' => 'error-message']],
            '<strong', 'Badness!', '/strong',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testErrorRuleName method
     *
     * Test error translation can use rule names for translating.
     *
     * @return void
     */
    public function testErrorRuleName()
    {
        $this->article['errors'] = [
            'Article' => [
                'field' => ['email' => 'Your email was not good']
            ]
        ];
        $this->Form->create($this->article);

        $result = $this->Form->error('Article.field');
        $expected = [
            ['div' => ['class' => 'error-message']],
            'Your email was not good',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->error('Article.field', ['email' => 'Email in use']);
        $expected = [
            ['div' => ['class' => 'error-message']],
            'Email in use',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->error('Article.field', ['Your email was not good' => 'Email in use']);
        $expected = [
            ['div' => ['class' => 'error-message']],
            'Email in use',
            '/div',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->error('Article.field', [
            'email' => 'Key is preferred',
            'Your email was not good' => 'Email in use'
        ]);
        $expected = [
            ['div' => ['class' => 'error-message']],
            'Key is preferred',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testErrorMessages method
     *
     * Test error with nested lists.
     *
     * @return void
     */
    public function testErrorMessages()
    {
        $this->article['errors'] = [
            'Article' => ['field' => 'email']
        ];
        $this->Form->create($this->article);

        $result = $this->Form->error('Article.field', [
            'email' => 'No good!'
        ]);
        $expected = [
            'div' => ['class' => 'error-message'],
            'No good!',
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testErrorMultipleMessages method
     *
     * Test error() with multiple messages.
     *
     * @return void
     */
    public function testErrorMultipleMessages()
    {
        $this->article['errors'] = [
            'field' => ['notBlank', 'email', 'Something else']
        ];
        $this->Form->create($this->article);

        $result = $this->Form->error('field', [
            'notBlank' => 'Cannot be empty',
            'email' => 'No good!'
        ]);
        $expected = [
            'div' => ['class' => 'error-message'],
            'ul' => [],
            '<li', 'Cannot be empty', '/li',
            '<li', 'No good!', '/li',
            '<li', 'Something else', '/li',
            '/ul',
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testPassword method
     *
     * Test password element generation.
     *
     * @return void
     */
    public function testPassword()
    {
        $this->article['errors'] = [
            'Contact' => [
                'passwd' => 1
            ]
        ];
        $this->Form->create($this->article);

        $result = $this->Form->password('Contact.field');
        $expected = ['input' => ['type' => 'password', 'name' => 'Contact[field]']];
        $this->assertHtml($expected, $result);

        $this->Form->request->data['Contact']['passwd'] = 'test';
        $result = $this->Form->password('Contact.passwd', ['id' => 'theID']);
        $expected = ['input' => ['type' => 'password', 'name' => 'Contact[passwd]', 'value' => 'test', 'id' => 'theID', 'class' => 'form-error']];
        $this->assertHtml($expected, $result);
    }

    /**
     * testRadio method
     *
     * Test radio element set generation.
     *
     * @return void
     */
    public function testRadio()
    {
        $result = $this->Form->radio('Model.field', ['option A']);
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'Model[field]', 'value' => ''],
            'label' => ['for' => 'model-field-0'],
            ['input' => ['type' => 'radio', 'name' => 'Model[field]', 'value' => '0', 'id' => 'model-field-0']],
            'option A',
            '/label'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->radio('Model.field', new Collection(['option A']));
        $this->assertHtml($expected, $result);

        $result = $this->Form->radio('Model.field', ['option A', 'option B']);
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'Model[field]', 'value' => ''],
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
            'input' => ['type' => 'hidden', 'name' => 'Employee[gender]', 'value' => '', 'form' => 'my-form'],
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
            ['input' => ['type' => 'hidden', 'name' => 'Model[custom]', 'value' => '']],
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
            'input' => ['type' => 'hidden', 'name' => 'Employee[gender]', 'value' => ''],
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
     * testRadioDefaultValue method
     *
     * Test default value setting on radio() method.
     *
     * @return void
     */
    public function testRadioDefaultValue()
    {
        $Articles = TableRegistry::get('Articles');
        $title = $Articles->schema()->column('title');
        $Articles->schema()->addColumn(
            'title',
            ['default' => '1'] + $title
        );

        $this->Form->create($Articles->newEntity());

        $result = $this->Form->radio('title', ['option A', 'option B']);
        $expected = [
            ['input' => ['type' => 'hidden', 'name' => 'title', 'value' => '']],
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
     * testInputRadio method
     *
     * Test that input works with radio types.
     *
     * @return void
     */
    public function testInputRadio()
    {
        $result = $this->Form->input('test', [
            'type' => 'radio',
            'options' => ['A', 'B'],
        ]);
        $expected = [
            ['div' => ['class' => 'input radio']],
                '<label',
                'Test',
                '/label',
                ['input' => ['type' => 'hidden', 'name' => 'test', 'value' => '']],
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

        $result = $this->Form->input('test', [
            'type' => 'radio',
            'options' => ['A', 'B'],
            'value' => '0'
        ]);
        $expected = [
            ['div' => ['class' => 'input radio']],
                '<label',
                'Test',
                '/label',
                ['input' => ['type' => 'hidden', 'name' => 'test', 'value' => '']],
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

        $result = $this->Form->input('test', [
            'type' => 'radio',
            'options' => ['A', 'B'],
            'label' => false
        ]);
        $expected = [
            ['div' => ['class' => 'input radio']],
                ['input' => ['type' => 'hidden', 'name' => 'test', 'value' => '']],
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
    }

    /**
     * testRadioNoLabel method
     *
     * Test that radio() works with label = false.
     *
     * @return void
     */
    public function testRadioNoLabel()
    {
        $result = $this->Form->radio('Model.field', ['A', 'B'], ['label' => false]);
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'Model[field]', 'value' => ''],
            ['input' => ['type' => 'radio', 'name' => 'Model[field]', 'value' => '0', 'id' => 'model-field-0']],
            ['input' => ['type' => 'radio', 'name' => 'Model[field]', 'value' => '1', 'id' => 'model-field-1']],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testRadioInputInsideLabel method
     *
     * Test generating radio input inside label ala twitter bootstrap.
     *
     * @return void
     */
    public function testRadioInputInsideLabel()
    {
        $this->Form->templates([
            'label' => '<label{{attrs}}>{{input}}{{text}}</label>',
            'radioWrapper' => '{{label}}'
        ]);

        $result = $this->Form->radio('Model.field', ['option A', 'option B']);
        //@codingStandardsIgnoreStart
        $expected = [
            ['input' => [
                'type' => 'hidden',
                'name' => 'Model[field]',
                'value' => ''
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
        //@codingStandardsIgnoreEnd
        $this->assertHtml($expected, $result);
    }

    /**
     * testRadioHiddenInputDisabling method
     *
     * Test disabling the hidden input for radio buttons.
     *
     * @return void
     */
    public function testRadioHiddenInputDisabling()
    {
        $result = $this->Form->radio('Model.1.field', ['option A'], ['hiddenField' => false]);
        $expected = [
            'label' => ['for' => 'model-1-field-0'],
            'input' => ['type' => 'radio', 'name' => 'Model[1][field]', 'value' => '0', 'id' => 'model-1-field-0'],
            'option A',
            '/label'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testRadioOutOfRange method
     *
     * Test radio element set generation.
     *
     * @return void
     */
    public function testRadioOutOfRange()
    {
        $result = $this->Form->radio('Model.field', ['v' => 'value'], ['value' => 'nope']);
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'Model[field]', 'value' => ''],
            'label' => ['for' => 'model-field-v'],
            ['input' => ['type' => 'radio', 'name' => 'Model[field]', 'value' => 'v', 'id' => 'model-field-v']],
            'value',
            '/label'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testSelect method
     *
     * Test select element generation.
     *
     * @return void
     */
    public function testSelect()
    {
        $result = $this->Form->select('Model.field', []);
        $expected = [
            'select' => ['name' => 'Model[field]'],
            '/select'
        ];
        $this->assertHtml($expected, $result);

        $this->Form->request->data = ['Model' => ['field' => 'value']];
        $result = $this->Form->select('Model.field', ['value' => 'good', 'other' => 'bad']);
        $expected = [
            'select' => ['name' => 'Model[field]'],
            ['option' => ['value' => 'value', 'selected' => 'selected']],
            'good',
            '/option',
            ['option' => ['value' => 'other']],
            'bad',
            '/option',
            '/select'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->select('Model.field', new Collection(['value' => 'good', 'other' => 'bad']));
        $this->assertHtml($expected, $result);

        $this->Form->request->data = [];
        $result = $this->Form->select('Model.field', ['value' => 'good', 'other' => 'bad']);
        $expected = [
            'select' => ['name' => 'Model[field]'],
            ['option' => ['value' => 'value']],
            'good',
            '/option',
            ['option' => ['value' => 'other']],
            'bad',
            '/option',
            '/select'
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
            '/select'
        ];
        $this->assertHtml($expected, $result);

        $this->Form->request->data = ['Model' => ['contact_id' => 228]];
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
            '/select'
        ];
        $this->assertHtml($expected, $result);

        $this->Form->request->data['Model']['field'] = 0;
        $result = $this->Form->select('Model.field', ['0' => 'No', '1' => 'Yes']);
        $expected = [
            'select' => ['name' => 'Model[field]'],
            ['option' => ['value' => '0', 'selected' => 'selected']], 'No', '/option',
            ['option' => ['value' => '1']], 'Yes', '/option',
            '/select'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testSelectEscapeHtml method
     *
     * Test that select() escapes HTML.
     *
     * @return void
     */
    public function testSelectEscapeHtml()
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
            '/select'
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
            '/select'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testSelectRequired method
     *
     * Test select() with required and disabled attributes.
     *
     * @return void
     */
    public function testSelectRequired()
    {
        $this->article['required'] = [
            'user_id' => true
        ];
        $this->Form->create($this->article);
        $result = $this->Form->select('user_id', ['option A']);
        $expected = [
            'select' => [
                'name' => 'user_id',
                'required' => 'required'
            ],
            ['option' => ['value' => '0']], 'option A', '/option',
            '/select'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->select('user_id', ['option A'], ['disabled' => true]);
        $expected = [
            'select' => [
                'name' => 'user_id',
                'disabled' => 'disabled'
            ],
            ['option' => ['value' => '0']], 'option A', '/option',
            '/select'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testNestedSelect method
     *
     * Test select element generation with optgroups.
     *
     * @return void
     */
    public function testNestedSelect()
    {
        $result = $this->Form->select(
            'Model.field',
            [1 => 'One', 2 => 'Two', 'Three' => [
                3 => 'Three', 4 => 'Four', 5 => 'Five'
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
            '/select'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testSelectMultiple method
     *
     * Test generation of multiple select elements.
     *
     * @return void
     */
    public function testSelectMultiple()
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
            '/select'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->select(
            'Model.multi_field',
            $options,
            ['multiple' => 'multiple', 'form' => 'my-form']
        );
        $this->assertHtml($expected, $result);
    }

    /**
     * testCheckboxZeroValue method
     *
     * Test that a checkbox can have 0 for the value and 1 for the hidden input.
     *
     * @return void
     */
    public function testCheckboxZeroValue()
    {
        $result = $this->Form->input('User.get_spam', [
            'type' => 'checkbox',
            'value' => '0',
            'hiddenField' => '1',
        ]);
        $expected = [
            'div' => ['class' => 'input checkbox'],
            'label' => ['for' => 'user-get-spam'],
            ['input' => [
                'type' => 'hidden', 'name' => 'User[get_spam]',
                'value' => '1'
            ]],
            ['input' => [
                'type' => 'checkbox', 'name' => 'User[get_spam]',
                'value' => '0', 'id' => 'user-get-spam'
            ]],
            'Get Spam',
            '/label',
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testHabtmSelectBox method
     *
     * Test generation of habtm select boxes.
     *
     * @return void
     */
    public function testHabtmSelectBox()
    {
        $this->loadFixtures('Articles');
        $options = [
            1 => 'blue',
            2 => 'red',
            3 => 'green'
        ];
        $tags = [
            new Entity(['id' => 1, 'name' => 'blue']),
            new Entity(['id' => 3, 'name' => 'green'])
        ];
        $article = new Article(['tags' => $tags]);
        $this->Form->create($article);
        $result = $this->Form->input('tags._ids', ['options' => $options]);
        $expected = [
            'div' => ['class' => 'input select'],
            'label' => ['for' => 'tags-ids'],
            'Tags',
            '/label',
            'input' => ['type' => 'hidden', 'name' => 'tags[_ids]', 'value' => ''],
            'select' => [
                'name' => 'tags[_ids][]', 'id' => 'tags-ids',
                'multiple' => 'multiple'
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
            '/div'
        ];
        $this->assertHtml($expected, $result);

        // make sure only 50 is selected, and not 50f5c0cf
        $options = [
            '1' => 'blue',
            '50f5c0cf' => 'red',
            '50' => 'green'
        ];
        $tags = [
            new Entity(['id' => 1, 'name' => 'blue']),
            new Entity(['id' => 50, 'name' => 'green'])
        ];
        $article = new Article(['tags' => $tags]);
        $this->Form->create($article);
        $result = $this->Form->input('tags._ids', ['options' => $options]);
        $expected = [
            'div' => ['class' => 'input select'],
            'label' => ['for' => 'tags-ids'],
            'Tags',
            '/label',
            'input' => ['type' => 'hidden', 'name' => 'tags[_ids]', 'value' => ''],
            'select' => [
                'name' => 'tags[_ids][]', 'id' => 'tags-ids',
                'multiple' => 'multiple'
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
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $spacecraft = [
            1 => 'Orion',
            2 => 'Helios'
        ];
        $this->View->viewVars['spacecraft'] = $spacecraft;
        $this->Form->create();
        $result = $this->Form->input('spacecraft._ids');
        $expected = [
            'div' => ['class' => 'input select'],
            'label' => ['for' => 'spacecraft-ids'],
            'Spacecraft',
            '/label',
            'input' => ['type' => 'hidden', 'name' => 'spacecraft[_ids]', 'value' => ''],
            'select' => [
                'name' => 'spacecraft[_ids][]', 'id' => 'spacecraft-ids',
                'multiple' => 'multiple'
            ],
            ['option' => ['value' => '1']],
            'Orion',
            '/option',
            ['option' => ['value' => '2']],
            'Helios',
            '/option',
            '/select',
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testErrorsForBelongsToManySelect method
     *
     * Tests that errors for belongsToMany select fields are being
     * picked up properly.
     *
     * @return void
     */
    public function testErrorsForBelongsToManySelect()
    {
        $spacecraft = [
            1 => 'Orion',
            2 => 'Helios'
        ];
        $this->View->viewVars['spacecraft'] = $spacecraft;

        $article = new Article();
        $article->errors('spacecraft', ['Invalid']);

        $this->Form->create($article);
        $result = $this->Form->input('spacecraft._ids');

        $expected = [
            ['div' => ['class' => 'input select error']],
            'label' => ['for' => 'spacecraft-ids'],
            'Spacecraft',
            '/label',
            'input' => ['type' => 'hidden', 'name' => 'spacecraft[_ids]', 'value' => ''],
            'select' => [
                'name' => 'spacecraft[_ids][]', 'id' => 'spacecraft-ids',
                'multiple' => 'multiple'
            ],
            ['option' => ['value' => '1']],
            'Orion',
            '/option',
            ['option' => ['value' => '2']],
            'Helios',
            '/option',
            '/select',
            ['div' => ['class' => 'error-message']],
            'Invalid',
            '/div',
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testSelectMultipleCheckboxes method
     *
     * Test generation of multi select elements in checkbox format.
     *
     * @return void
     */
    public function testSelectMultipleCheckboxes()
    {
        $result = $this->Form->select(
            'Model.multi_field',
            ['first', 'second', 'third'],
            ['multiple' => 'checkbox']
        );

        $expected = [
            'input' => [
                'type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => ''
            ],
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-0']],
            ['input' => [
                'type' => 'checkbox', 'name' => 'Model[multi_field][]',
                'value' => '0', 'id' => 'model-multi-field-0'
            ]],
            'first',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-1']],
            ['input' => [
                'type' => 'checkbox', 'name' => 'Model[multi_field][]',
                'value' => '1', 'id' => 'model-multi-field-1'
            ]],
            'second',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-2']],
            ['input' => [
                'type' => 'checkbox', 'name' => 'Model[multi_field][]',
                'value' => '2', 'id' => 'model-multi-field-2'
            ]],
            'third',
            '/label',
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->select(
            'Model.multi_field',
            ['a+' => 'first', 'a++' => 'second', 'a+++' => 'third'],
            ['multiple' => 'checkbox']
        );
        $expected = [
            'input' => [
                'type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => ''
            ],
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-a+']],
            ['input' => [
                'type' => 'checkbox', 'name' => 'Model[multi_field][]',
                'value' => 'a+', 'id' => 'model-multi-field-a+'
            ]],
            'first',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-a++']],
            ['input' => [
                'type' => 'checkbox', 'name' => 'Model[multi_field][]',
                'value' => 'a++', 'id' => 'model-multi-field-a++'
            ]],
            'second',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-a+++']],
            ['input' => [
                'type' => 'checkbox', 'name' => 'Model[multi_field][]',
                'value' => 'a+++', 'id' => 'model-multi-field-a+++'
            ]],
            'third',
            '/label',
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->select(
            'Model.multi_field',
            ['a>b' => 'first', 'a<b' => 'second', 'a"b' => 'third'],
            ['multiple' => 'checkbox']
        );
        $expected = [
            'input' => [
                'type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => ''
            ],
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-a-b']],
            ['input' => [
                'type' => 'checkbox', 'name' => 'Model[multi_field][]',
                'value' => 'a&gt;b', 'id' => 'model-multi-field-a-b'
            ]],
            'first',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-a-b1']],
            ['input' => [
                'type' => 'checkbox', 'name' => 'Model[multi_field][]',
                'value' => 'a&lt;b', 'id' => 'model-multi-field-a-b1'
            ]],
            'second',
            '/label',
            '/div',
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-multi-field-a-b2']],
            ['input' => [
                'type' => 'checkbox', 'name' => 'Model[multi_field][]',
                'value' => 'a&quot;b', 'id' => 'model-multi-field-a-b2'
            ]],
            'third',
            '/label',
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testSelectMultipleCheckboxRequestData method
     *
     * Ensure that multiCheckbox reads from the request data.
     *
     * @return void
     */
    public function testSelectMultipleCheckboxRequestData()
    {
        $this->Form->request->data = ['Model' => ['tags' => [1]]];
        $result = $this->Form->select(
            'Model.tags',
            ['1' => 'first', 'Array' => 'Array'],
            ['multiple' => 'checkbox']
        );
        $expected = [
            'input' => [
                'type' => 'hidden', 'name' => 'Model[tags]', 'value' => ''
            ],
            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-tags-1', 'class' => 'selected']],
            ['input' => [
                'type' => 'checkbox', 'name' => 'Model[tags][]',
                'value' => '1', 'id' => 'model-tags-1', 'checked' => 'checked'
            ]],
            'first',
            '/label',
            '/div',

            ['div' => ['class' => 'checkbox']],
            ['label' => ['for' => 'model-tags-array']],
            ['input' => [
                'type' => 'checkbox', 'name' => 'Model[tags][]',
                'value' => 'Array', 'id' => 'model-tags-array'
            ]],
            'Array',
            '/label',
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testSelectMultipleCheckboxSecurity method
     *
     * Checks the security hash array generated for multiple-input checkbox elements.
     *
     * @return void
     */
    public function testSelectMultipleCheckboxSecurity()
    {
        $this->Form->request->params['_Token'] = 'testKey';
        $this->assertEquals([], $this->Form->fields);

        $result = $this->Form->select(
            'Model.multi_field',
            ['1' => 'first', '2' => 'second', '3' => 'third'],
            ['multiple' => 'checkbox']
        );
        $this->assertEquals(['Model.multi_field'], $this->Form->fields);

        $result = $this->Form->secure($this->Form->fields);
        $key = 'f7d573650a295b94e0938d32b323fde775e5f32b%3A';
        $this->assertRegExp('/"' . $key . '"/', $result);
    }

    /**
     * testSelectMultipleSecureWithNoOptions method
     *
     * Multiple select elements should always be secured as they always participate
     * in the POST data.
     *
     * @return void
     */
    public function testSelectMultipleSecureWithNoOptions()
    {
        $this->assertEquals([], $this->Form->fields);

        $this->Form->select(
            'Model.select',
            [],
            ['multiple' => true]
        );
        $this->assertEquals(['Model.select'], $this->Form->fields);
    }

    /**
     * testSelectNoSecureWithNoOptions method
     *
     * When a select box has no options it should not be added to the fields list
     * as it always fail post validation.
     *
     * @return void
     */
    public function testSelectNoSecureWithNoOptions()
    {
        $this->Form->request->params['_Token'] = 'testkey';
        $this->assertEquals([], $this->Form->fields);

        $this->Form->select(
            'Model.select',
            []
        );
        $this->assertEquals([], $this->Form->fields);

        $this->Form->select(
            'Model.user_id',
            [],
            ['empty' => true]
        );
        $this->assertEquals(['Model.user_id'], $this->Form->fields);
    }

    /**
     * testInputMultipleCheckboxes method
     *
     * Test input() resulting in multi select elements being generated.
     *
     * @return void
     */
    public function testInputMultipleCheckboxes()
    {
        $result = $this->Form->input('Model.multi_field', [
            'options' => ['first', 'second', 'third'],
            'multiple' => 'checkbox'
        ]);
        $expected = [
            ['div' => ['class' => 'input select']],
            ['label' => ['for' => 'model-multi-field']],
            'Multi Field',
            '/label',
            'input' => ['type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => ''],
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
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('Model.multi_field', [
            'options' => ['a' => 'first', 'b' => 'second', 'c' => 'third'],
            'multiple' => 'checkbox'
        ]);
        $expected = [
            ['div' => ['class' => 'input select']],
            ['label' => ['for' => 'model-multi-field']],
            'Multi Field',
            '/label',
            'input' => ['type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => ''],
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
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testSelectHiddenFieldOmission method
     *
     * Test that select() with 'hiddenField' => false omits the hidden field.
     *
     * @return void
     */
    public function testSelectHiddenFieldOmission()
    {
        $result = $this->Form->select(
            'Model.multi_field',
            ['first', 'second'],
            ['multiple' => 'checkbox', 'hiddenField' => false, 'value' => null]
        );
        $this->assertNotContains('type="hidden"', $result);
    }

    /**
     * testSelectCheckboxMultipleOverrideName method
     *
     * Test that select() with multiple = checkbox works with overriding name attribute.
     *
     * @return void
     */
    public function testSelectCheckboxMultipleOverrideName()
    {
        $result = $this->Form->select('category', ['1', '2'], [
            'multiple' => 'checkbox',
            'name' => 'fish',
        ]);
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'fish', 'value' => ''],
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
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->multiCheckbox(
            'category',
            new Collection(['1', '2']),
            ['name' => 'fish']
        );
        $result = $this->Form->multiCheckbox('category', ['1', '2'], [
            'name' => 'fish',
        ]);
        $this->assertHtml($expected, $result);
    }

    /**
     * testInputMultiCheckbox method
     *
     * Test that input() works with multicheckbox.
     *
     * @return void
     */
    public function testInputMultiCheckbox()
    {
        $result = $this->Form->input('category', [
            'type' => 'multicheckbox',
            'options' => ['1', '2'],
        ]);
        $expected = [
            ['div' => ['class' => 'input multicheckbox']],
            '<label',
            'Category',
            '/label',
            'input' => ['type' => 'hidden', 'name' => 'category', 'value' => ''],
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
     *
     * @return void
     */
    public function testCheckbox()
    {
        $result = $this->Form->checkbox('Model.field');
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'Model[field]', 'value' => '0'],
            ['input' => ['type' => 'checkbox', 'name' => 'Model[field]', 'value' => '1']]
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
            ]]
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testCheckboxDefaultValue method
     *
     * Test default value setting on checkbox() method.
     *
     * @return void
     */
    public function testCheckboxDefaultValue()
    {
        $this->Form->request->data['Model']['field'] = false;
        $result = $this->Form->checkbox('Model.field', ['default' => true, 'hiddenField' => false]);
        $expected = ['input' => ['type' => 'checkbox', 'name' => 'Model[field]', 'value' => '1']];
        $this->assertHtml($expected, $result);

        unset($this->Form->request->data['Model']['field']);
        $result = $this->Form->checkbox('Model.field', ['default' => true, 'hiddenField' => false]);
        $expected = ['input' => ['type' => 'checkbox', 'name' => 'Model[field]', 'value' => '1', 'checked' => 'checked']];
        $this->assertHtml($expected, $result);

        $this->Form->request->data['Model']['field'] = true;
        $result = $this->Form->checkbox('Model.field', ['default' => false, 'hiddenField' => false]);
        $expected = ['input' => ['type' => 'checkbox', 'name' => 'Model[field]', 'value' => '1', 'checked' => 'checked']];
        $this->assertHtml($expected, $result);

        unset($this->Form->request->data['Model']['field']);
        $result = $this->Form->checkbox('Model.field', ['default' => false, 'hiddenField' => false]);
        $expected = ['input' => ['type' => 'checkbox', 'name' => 'Model[field]', 'value' => '1']];
        $this->assertHtml($expected, $result);

        $Articles = TableRegistry::get('Articles');
        $Articles->schema()->addColumn(
            'published',
            ['type' => 'boolean', 'null' => false, 'default' => true]
        );

        $this->Form->create($Articles->newEntity());
        $result = $this->Form->checkbox('published', ['hiddenField' => false]);
        $expected = ['input' => ['type' => 'checkbox', 'name' => 'published', 'value' => '1', 'checked' => 'checked']];
        $this->assertHtml($expected, $result);
    }

    /**
     * testCheckboxCheckedAndError method
     *
     * Test checkbox being checked or having errors.
     *
     * @return void
     */
    public function testCheckboxCheckedAndError()
    {
        $this->article['errors'] = [
            'published' => true
        ];
        $this->Form->request->data['published'] = 'myvalue';
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
                'class' => 'form-error'
            ]]
        ];
        $this->assertHtml($expected, $result);

        $this->Form->request->data['published'] = '';
        $result = $this->Form->checkbox('published');
        $expected = [
            'input' => ['type' => 'hidden', 'class' => 'form-error', 'name' => 'published', 'value' => '0'],
            ['input' => ['type' => 'checkbox', 'name' => 'published', 'value' => '1', 'class' => 'form-error']]
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testCheckboxCustomNameAttribute method
     *
     * Test checkbox() with a custom name attribute.
     *
     * @return void
     */
    public function testCheckboxCustomNameAttribute()
    {
        $result = $this->Form->checkbox('Test.test', ['name' => 'myField']);
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'myField', 'value' => '0'],
            ['input' => ['type' => 'checkbox', 'name' => 'myField', 'value' => '1']]
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testCheckboxHiddenField method
     *
     * Test that the hidden input for checkboxes can be omitted or set to a
     * specific value.
     *
     * @return void
     */
    public function testCheckboxHiddenField()
    {
        $result = $this->Form->checkbox('UserForm.something', [
            'hiddenField' => false
        ]);
        $expected = [
            'input' => [
                'type' => 'checkbox',
                'name' => 'UserForm[something]',
                'value' => '1'
            ],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->checkbox('UserForm.something', [
            'value' => 'Y',
            'hiddenField' => 'N',
        ]);
        $expected = [
            ['input' => [
                'type' => 'hidden', 'name' => 'UserForm[something]',
                'value' => 'N'
            ]],
            ['input' => [
                'type' => 'checkbox', 'name' => 'UserForm[something]',
                'value' => 'Y'
            ]],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testTime method
     *
     * Test the time type.
     *
     * @return void
     */
    public function testTime()
    {
        $result = $this->Form->time('start_time', [
            'timeFormat' => 12,
            'interval' => 5,
            'value' => ['hour' => '4', 'minute' => '30', 'meridian' => 'pm']
        ]);
        $this->assertContains('<option value="04" selected="selected">4</option>', $result);
        $this->assertContains('<option value="30" selected="selected">30</option>', $result);
        $this->assertContains('<option value="pm" selected="selected">pm</option>', $result);
        $this->assertNotContains('year', $result);
        $this->assertNotContains('month', $result);
        $this->assertNotContains('day', $result);

        $result = $this->Form->time('start_time', [
            'timeFormat' => 12,
            'interval' => 5,
            'value' => '2014-03-08 16:30:00'
        ]);
        $this->assertContains('<option value="04" selected="selected">4</option>', $result);
        $this->assertContains('<option value="30" selected="selected">30</option>', $result);
        $this->assertContains('<option value="pm" selected="selected">pm</option>', $result);
        $this->assertNotContains('year', $result);
        $this->assertNotContains('month', $result);
        $this->assertNotContains('day', $result);
    }

    /**
     * testTimeFormat24NoMeridian method
     *
     * Ensure that timeFormat=24 has no merdian.
     *
     * @return void.
     */
    public function testTimeFormat24NoMeridian()
    {
        $result = $this->Form->time('start_time', [
            'timeFormat' => 24,
            'interval' => 5,
            'value' => '2014-03-08 16:30:00'
        ]);
        $this->assertContains('<option value="16" selected="selected">16</option>', $result);
        $this->assertContains('<option value="30" selected="selected">30</option>', $result);
        $this->assertNotContains('meridian', $result);
        $this->assertNotContains('pm', $result);
        $this->assertNotContains('year', $result);
        $this->assertNotContains('month', $result);
        $this->assertNotContains('day', $result);
    }

    /**
     * testDate method
     *
     * Test the date type.
     *
     * @return void
     */
    public function testDate()
    {
        $result = $this->Form->date('start_day', [
            'value' => ['year' => '2014', 'month' => '03', 'day' => '08']
        ]);
        $this->assertContains('<option value="2014" selected="selected">2014</option>', $result);
        $this->assertContains('<option value="03" selected="selected">March</option>', $result);
        $this->assertContains('<option value="08" selected="selected">8</option>', $result);
        $this->assertNotContains('hour', $result);
        $this->assertNotContains('minute', $result);
        $this->assertNotContains('second', $result);
        $this->assertNotContains('meridian', $result);
    }

    /**
     * testDateTime method
     *
     * Test generation of date/time select elements.
     *
     * @return void
     */
    public function testDateTime()
    {
        extract($this->dateRegex);

        $result = $this->Form->dateTime('Contact.date', ['default' => true]);
        $now = strtotime('now');
        $expected = [
            ['select' => ['name' => 'Contact[date][year]']],
            ['option' => ['value' => '']],
            '/option',
            $yearsRegex,
            ['option' => ['value' => date('Y', $now), 'selected' => 'selected']],
            date('Y', $now),
            '/option',
            '*/select',

            ['select' => ['name' => 'Contact[date][month]']],
            ['option' => ['value' => '']],
            '/option',
            $monthsRegex,
            ['option' => ['value' => date('m', $now), 'selected' => 'selected']],
            date('F', $now),
            '/option',
            '*/select',

            ['select' => ['name' => 'Contact[date][day]']],
            ['option' => ['value' => '']],
            '/option',
            $daysRegex,
            ['option' => ['value' => date('d', $now), 'selected' => 'selected']],
            date('j', $now),
            '/option',
            '*/select',

            ['select' => ['name' => 'Contact[date][hour]']],
            ['option' => ['value' => '']],
            '/option',
            $hoursRegex,
            ['option' => ['value' => date('H', $now), 'selected' => 'selected']],
            date('G', $now),
            '/option',
            '*/select',

            ['select' => ['name' => 'Contact[date][minute]']],
            ['option' => ['value' => '']],
            '/option',
            $minutesRegex,
            ['option' => ['value' => date('i', $now), 'selected' => 'selected']],
            date('i', $now),
            '/option',
            '*/select',
        ];
        $this->assertHtml($expected, $result);

        // Empty=>false implies Default=>true, as selecting the "first" dropdown value is useless
        $result = $this->Form->dateTime('Contact.date', ['empty' => false]);
        $now = strtotime('now');
        $expected = [
            ['select' => ['name' => 'Contact[date][year]']],
            $yearsRegex,
            ['option' => ['value' => date('Y', $now), 'selected' => 'selected']],
            date('Y', $now),
            '/option',
            '*/select',

            ['select' => ['name' => 'Contact[date][month]']],
            $monthsRegex,
            ['option' => ['value' => date('m', $now), 'selected' => 'selected']],
            date('F', $now),
            '/option',
            '*/select',

            ['select' => ['name' => 'Contact[date][day]']],
            $daysRegex,
            ['option' => ['value' => date('d', $now), 'selected' => 'selected']],
            date('j', $now),
            '/option',
            '*/select',

            ['select' => ['name' => 'Contact[date][hour]']],
            $hoursRegex,
            ['option' => ['value' => date('H', $now), 'selected' => 'selected']],
            date('G', $now),
            '/option',
            '*/select',

            ['select' => ['name' => 'Contact[date][minute]']],
            $minutesRegex,
            ['option' => ['value' => date('i', $now), 'selected' => 'selected']],
            date('i', $now),
            '/option',
            '*/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testDateTimeSecured method
     *
     * Test that datetime fields are added to protected fields list.
     *
     * @return void
     */
    public function testDateTimeSecured()
    {
        $this->Form->request->params['_Token'] = ['unlockedFields' => []];
        $this->Form->dateTime('Contact.date');
        $expected = [
            'Contact.date.year',
            'Contact.date.month',
            'Contact.date.day',
            'Contact.date.hour',
            'Contact.date.minute',
        ];
        $this->assertEquals($expected, $this->Form->fields);

        $this->Form->fields = [];
        $this->Form->date('Contact.published');
        $expected = [
            'Contact.published.year',
            'Contact.published.month',
            'Contact.published.day',
        ];
        $this->assertEquals($expected, $this->Form->fields);
    }

    /**
     * testDateTimeSecuredDisabled method
     *
     * Test that datetime fields are added to protected fields list.
     *
     * @return void
     */
    public function testDateTimeSecuredDisabled()
    {
        $this->Form->request->params['_Token'] = ['unlockedFields' => []];
        $this->Form->dateTime('Contact.date', ['secure' => false]);
        $expected = [];
        $this->assertEquals($expected, $this->Form->fields);

        $this->Form->fields = [];
        $this->Form->date('Contact.published', ['secure' => false]);
        $expected = [];
        $this->assertEquals($expected, $this->Form->fields);
    }

    /**
     * testDatetimeEmpty method
     *
     * Test empty defaulting to true for datetime.
     *
     * @return void
     */
    public function testDatetimeEmpty()
    {
        extract($this->dateRegex);
        $now = strtotime('now');

        $result = $this->Form->dateTime('Contact.date', [
            'timeFormat' => 12,
            'empty' => true,
            'default' => true
        ]);
        $expected = [
            ['select' => ['name' => 'Contact[date][year]']],
            $yearsRegex,
            ['option' => ['value' => '']],
            '/option',
            '*/select',

            ['select' => ['name' => 'Contact[date][month]']],
            $monthsRegex,
            ['option' => ['value' => '']],
            '/option',
            '*/select',

            ['select' => ['name' => 'Contact[date][day]']],
            $daysRegex,
            ['option' => ['value' => '']],
            '/option',
            '*/select',

            ['select' => ['name' => 'Contact[date][hour]']],
            $hoursRegex,
            ['option' => ['value' => '']],
            '/option',
            '*/select',

            ['select' => ['name' => 'Contact[date][minute]']],
            $minutesRegex,
            ['option' => ['value' => '']],
            '/option',
            '*/select',

            ['select' => ['name' => 'Contact[date][meridian]']],
            $meridianRegex,
            ['option' => ['value' => '']],
            '/option',
            '*/select'
        ];
        $this->assertHtml($expected, $result);
        $this->assertNotRegExp('/<option[^<>]+value=""[^<>]+selected="selected"[^>]*>/', $result);
    }

    /**
     * testDatetimeMinuteInterval method
     *
     * Test datetime with interval option.
     *
     * @return void
     */
    public function testDatetimeMinuteInterval()
    {
        extract($this->dateRegex);
        $now = strtotime('now');

        $result = $this->Form->dateTime('Contact.date', [
            'interval' => 5,
            'value' => ''
        ]);
        $expected = [
            ['select' => ['name' => 'Contact[date][year]']],
            $yearsRegex,
            ['option' => ['selected' => 'selected', 'value' => '']],
            '/option',
            '*/select',

            ['select' => ['name' => 'Contact[date][month]']],
            $monthsRegex,
            ['option' => ['selected' => 'selected', 'value' => '']],
            '/option',
            '*/select',

            ['select' => ['name' => 'Contact[date][day]']],
            $daysRegex,
            ['option' => ['selected' => 'selected', 'value' => '']],
            '/option',
            '*/select',

            ['select' => ['name' => 'Contact[date][hour]']],
            $hoursRegex,
            ['option' => ['selected' => 'selected', 'value' => '']],
            '/option',
            '*/select',

            ['select' => ['name' => 'Contact[date][minute]']],
            $minutesRegex,
            ['option' => ['selected' => 'selected', 'value' => '']],
            '/option',
            ['option' => ['value' => '00']],
            '00',
            '/option',
            ['option' => ['value' => '05']],
            '05',
            '/option',
            ['option' => ['value' => '10']],
            '10',
            '/option',
            '*/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testDateTimeRounding method
     *
     * Test dateTime with rounding.
     *
     * @return void
     */
    public function testDateTimeRounding()
    {
        $this->Form->request->data['Contact'] = [
            'date' => [
                'day' => '13',
                'month' => '12',
                'year' => '2010',
                'hour' => '04',
                'minute' => '19',
                'meridian' => 'AM'
            ]
        ];

        $result = $this->Form->dateTime('Contact.date', ['interval' => 15]);
        $this->assertTextContains('<option value="15" selected="selected">15</option>', $result);

        $result = $this->Form->dateTime('Contact.date', ['interval' => 15, 'round' => 'up']);
        $this->assertTextContains('<option value="30" selected="selected">30</option>', $result);

        $result = $this->Form->dateTime('Contact.date', ['interval' => 5, 'round' => 'down']);
        $this->assertTextContains('<option value="15" selected="selected">15</option>', $result);
    }

    /**
     * testDatetimeWithDefault method
     *
     * Test that datetime() and default values work.
     *
     * @return void
     */
    public function testDatetimeWithDefault()
    {
        $result = $this->Form->dateTime('Contact.updated', ['value' => '2009-06-01 11:15:30']);
        $this->assertRegExp('/<option[^<>]+value="2009"[^<>]+selected="selected"[^>]*>2009<\/option>/', $result);
        $this->assertRegExp('/<option[^<>]+value="01"[^<>]+selected="selected"[^>]*>1<\/option>/', $result);
        $this->assertRegExp('/<option[^<>]+value="06"[^<>]+selected="selected"[^>]*>June<\/option>/', $result);

        $result = $this->Form->dateTime('Contact.updated', [
            'default' => '2009-06-01 11:15:30'
        ]);
        $this->assertRegExp('/<option[^<>]+value="2009"[^<>]+selected="selected"[^>]*>2009<\/option>/', $result);
        $this->assertRegExp('/<option[^<>]+value="01"[^<>]+selected="selected"[^>]*>1<\/option>/', $result);
        $this->assertRegExp('/<option[^<>]+value="06"[^<>]+selected="selected"[^>]*>June<\/option>/', $result);
    }

    /**
     * testDateTimeAllZeros method
     *
     * Test datetime() with all zeros.
     *
     * @return void
     */
    public function testDateTimeAllZeros()
    {
        $result = $this->Form->dateTime('Contact.date', [
            'timeFormat' => false,
            'empty' => ['day' => '-', 'month' => '-', 'year' => '-'],
            'value' => '0000-00-00'
        ]);

        $this->assertRegExp('/<option value="">-<\/option>/', $result);
        $this->assertNotRegExp('/<option value="0" selected="selected">0<\/option>/', $result);
    }

    /**
     * testDateTimeEmptyAsArray method
     *
     * @return void
     */
    public function testDateTimeEmptyAsArray()
    {
        $result = $this->Form->dateTime('Contact.date', [
            'empty' => [
                'day' => 'DAY',
                'month' => 'MONTH',
                'year' => 'YEAR',
                'hour' => 'HOUR',
                'minute' => 'MINUTE',
                'meridian' => false
            ],
            'default' => true
        ]);

        $this->assertRegExp('/<option value="">DAY<\/option>/', $result);
        $this->assertRegExp('/<option value="">MONTH<\/option>/', $result);
        $this->assertRegExp('/<option value="">YEAR<\/option>/', $result);
        $this->assertRegExp('/<option value="">HOUR<\/option>/', $result);
        $this->assertRegExp('/<option value="">MINUTE<\/option>/', $result);
        $this->assertNotRegExp('/<option value=""><\/option>/', $result);

        $result = $this->Form->dateTime('Contact.date', [
            'empty' => ['day' => 'DAY', 'month' => 'MONTH', 'year' => 'YEAR'],
            'default' => true
        ]);

        $this->assertRegExp('/<option value="">DAY<\/option>/', $result);
        $this->assertRegExp('/<option value="">MONTH<\/option>/', $result);
        $this->assertRegExp('/<option value="">YEAR<\/option>/', $result);
    }

    /**
     * testFormDateTimeMulti method
     *
     * Test multiple datetime element generation.
     *
     * @return void
     */
    public function testFormDateTimeMulti()
    {
        extract($this->dateRegex);

        $result = $this->Form->dateTime('Contact.1.updated');
        $this->assertContains('Contact[1][updated][month]', $result);
        $this->assertContains('Contact[1][updated][day]', $result);
        $this->assertContains('Contact[1][updated][year]', $result);
        $this->assertContains('Contact[1][updated][hour]', $result);
        $this->assertContains('Contact[1][updated][minute]', $result);
    }

    /**
     * testDateTimeLabelIdMatchesFirstInput method
     *
     * When changing the date format, the label should always focus the first select box when
     * clicked.
     *
     * @return void
     */
    public function testDateTimeLabelIdMatchesFirstInput()
    {
        $result = $this->Form->input('Model.date', ['type' => 'date']);
        $this->assertContains('<label>Date</label>', $result);

        $result = $this->Form->input('Model.date', ['type' => 'date', 'dateFormat' => 'DMY']);
        $this->assertContains('<label>Date</label>', $result);

        $result = $this->Form->input('Model.date', ['type' => 'date', 'dateFormat' => 'YMD']);
        $this->assertContains('<label>Date</label>', $result);
    }

    /**
     * testDateTimeSecondOptions method
     *
     * Test datetime second=true.
     *
     * @return void
     */
    public function testDateTimeSecondOptions()
    {
        $result = $this->Form->dateTime('updated', ['second' => true]);
        $this->assertContains('updated[second]', $result, 'Should have seconds');

        $result = $this->Form->dateTime('updated', ['second' => []]);
        $this->assertContains('updated[second]', $result, 'Should have seconds');

        $result = $this->Form->dateTime('updated', ['second' => null]);
        $this->assertNotContains('updated[second]', $result, 'Should not have seconds');

        $result = $this->Form->dateTime('updated', ['second' => false]);
        $this->assertNotContains('updated[second]', $result, 'Should not have seconds');
    }

    /**
     * testMonth method
     *
     * Test generation of a month input.
     *
     * @return void
     */
    public function testMonth()
    {
        $result = $this->Form->month('Model.field', ['value' => '']);
        $expected = [
            ['select' => ['name' => 'Model[field][month]']],
            ['option' => ['value' => '', 'selected' => 'selected']],
            '/option',
            ['option' => ['value' => '01']],
            date('F', strtotime('2008-01-01 00:00:00')),
            '/option',
            ['option' => ['value' => '02']],
            date('F', strtotime('2008-02-01 00:00:00')),
            '/option',
            '*/select',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->month('Model.field', ['empty' => true, 'value' => '']);
        $expected = [
            ['select' => ['name' => 'Model[field][month]']],
            ['option' => ['selected' => 'selected', 'value' => '']],
            '/option',
            ['option' => ['value' => '01']],
            date('F', strtotime('2008-01-01 00:00:00')),
            '/option',
            ['option' => ['value' => '02']],
            date('F', strtotime('2008-02-01 00:00:00')),
            '/option',
            '*/select',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->month('Model.field', ['value' => '', 'monthNames' => false]);
        $expected = [
            ['select' => ['name' => 'Model[field][month]']],
            ['option' => ['selected' => 'selected', 'value' => '']],
            '/option',
            ['option' => ['value' => '01']],
            '1',
            '/option',
            ['option' => ['value' => '02']],
            '2',
            '/option',
            '*/select',
        ];
        $this->assertHtml($expected, $result);

        $monthNames = [
            '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'May', '06' => 'Jun',
            '07' => 'Jul', '08' => 'Aug', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec'
        ];
        $result = $this->Form->month('Model.field', ['value' => '1', 'monthNames' => $monthNames]);
        $expected = [
            ['select' => ['name' => 'Model[field][month]']],
            ['option' => ['value' => '']],
            '/option',
            ['option' => ['value' => '01', 'selected' => 'selected']],
            'Jan',
            '/option',
            ['option' => ['value' => '02']],
            'Feb',
            '/option',
            '*/select',
        ];
        $this->assertHtml($expected, $result);

        $this->Form->request->data['Project']['release'] = '2050-02-10';
        $result = $this->Form->month('Project.release');

        $expected = [
            ['select' => ['name' => 'Project[release][month]']],
            ['option' => ['value' => '']],
            '/option',
            ['option' => ['value' => '01']],
            'January',
            '/option',
            ['option' => ['value' => '02', 'selected' => 'selected']],
            'February',
            '/option',
            '*/select',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->month('Contact.published', [
            'empty' => 'Published on',
        ]);
        $this->assertContains('Published on', $result);
    }

    /**
     * testDay method
     *
     * Test generation of a day input.
     *
     * @return void
     */
    public function testDay()
    {
        extract($this->dateRegex);

        $result = $this->Form->day('Model.field', ['value' => '', 'class' => 'form-control']);
        $expected = [
            ['select' => ['name' => 'Model[field][day]', 'class' => 'form-control']],
            ['option' => ['selected' => 'selected', 'value' => '']],
            '/option',
            ['option' => ['value' => '01']],
            '1',
            '/option',
            ['option' => ['value' => '02']],
            '2',
            '/option',
            $daysRegex,
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $this->Form->request->data['Model']['field'] = '2006-10-10 23:12:32';
        $result = $this->Form->day('Model.field');
        $expected = [
            ['select' => ['name' => 'Model[field][day]']],
            ['option' => ['value' => '']],
            '/option',
            ['option' => ['value' => '01']],
            '1',
            '/option',
            ['option' => ['value' => '02']],
            '2',
            '/option',
            $daysRegex,
            ['option' => ['value' => '10', 'selected' => 'selected']],
            '10',
            '/option',
            $daysRegex,
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $this->Form->request->data['Model']['field'] = '';
        $result = $this->Form->day('Model.field', ['value' => '10']);
        $expected = [
            ['select' => ['name' => 'Model[field][day]']],
            ['option' => ['value' => '']],
            '/option',
            ['option' => ['value' => '01']],
            '1',
            '/option',
            ['option' => ['value' => '02']],
            '2',
            '/option',
            $daysRegex,
            ['option' => ['value' => '10', 'selected' => 'selected']],
            '10',
            '/option',
            $daysRegex,
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $this->Form->request->data['Project']['release'] = '2050-10-10';
        $result = $this->Form->day('Project.release');

        $expected = [
            ['select' => ['name' => 'Project[release][day]']],
            ['option' => ['value' => '']],
            '/option',
            ['option' => ['value' => '01']],
            '1',
            '/option',
            ['option' => ['value' => '02']],
            '2',
            '/option',
            $daysRegex,
            ['option' => ['value' => '10', 'selected' => 'selected']],
            '10',
            '/option',
            $daysRegex,
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->day('Contact.published', [
            'empty' => 'Published on',
        ]);
        $this->assertContains('Published on', $result);
    }

    /**
     * testMinute method
     *
     * Test generation of a minute input.
     *
     * @return void
     */
    public function testMinute()
    {
        extract($this->dateRegex);

        $result = $this->Form->minute('Model.field', ['value' => '']);
        $expected = [
            ['select' => ['name' => 'Model[field][minute]']],
            ['option' => ['selected' => 'selected', 'value' => '']],
            '/option',
            ['option' => ['value' => '00']],
            '00',
            '/option',
            ['option' => ['value' => '01']],
            '01',
            '/option',
            ['option' => ['value' => '02']],
            '02',
            '/option',
            $minutesRegex,
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $this->Form->request->data['Model']['field'] = '2006-10-10 00:12:32';
        $result = $this->Form->minute('Model.field');
        $expected = [
            ['select' => ['name' => 'Model[field][minute]']],
            ['option' => ['value' => '']],
            '/option',
            ['option' => ['value' => '00']],
            '00',
            '/option',
            ['option' => ['value' => '01']],
            '01',
            '/option',
            ['option' => ['value' => '02']],
            '02',
            '/option',
            $minutesRegex,
            ['option' => ['value' => '12', 'selected' => 'selected']],
            '12',
            '/option',
            $minutesRegex,
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $this->Form->request->data['Model']['field'] = '';
        $result = $this->Form->minute('Model.field', ['interval' => 5]);
        $expected = [
            ['select' => ['name' => 'Model[field][minute]']],
            ['option' => ['selected' => 'selected', 'value' => '']],
            '/option',
            ['option' => ['value' => '00']],
            '00',
            '/option',
            ['option' => ['value' => '05']],
            '05',
            '/option',
            ['option' => ['value' => '10']],
            '10',
            '/option',
            $minutesRegex,
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $this->Form->request->data['Model']['field'] = '2006-10-10 00:10:32';
        $result = $this->Form->minute('Model.field', ['interval' => 5]);
        $expected = [
            ['select' => ['name' => 'Model[field][minute]']],
            ['option' => ['value' => '']],
            '/option',
            ['option' => ['value' => '00']],
            '00',
            '/option',
            ['option' => ['value' => '05']],
            '05',
            '/option',
            ['option' => ['value' => '10', 'selected' => 'selected']],
            '10',
            '/option',
            $minutesRegex,
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testMeridian method
     *
     * Test generating an input for the meridian.
     *
     * @return void
     */
    public function testMeridian()
    {
        extract($this->dateRegex);

        $now = new \DateTime();
        $result = $this->Form->meridian('Model.field', ['value' => 'am']);
        $expected = [
            ['select' => ['name' => 'Model[field][meridian]']],
            ['option' => ['value' => '']],
            '/option',
            $meridianRegex,
            ['option' => ['value' => $now->format('a'), 'selected' => 'selected']],
            $now->format('a'),
            '/option',
            '*/select'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testHour method
     *
     * Test generation of an hour input.
     *
     * @return void
     */
    public function testHour()
    {
        extract($this->dateRegex);

        $result = $this->Form->hour('Model.field', ['format' => 12, 'value' => '']);
        $expected = [
            ['select' => ['name' => 'Model[field][hour]']],
            ['option' => ['selected' => 'selected', 'value' => '']],
            '/option',
            ['option' => ['value' => '01']],
            '1',
            '/option',
            ['option' => ['value' => '02']],
            '2',
            '/option',
            $hoursRegex,
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $this->Form->request->data['Model']['field'] = '2006-10-10 00:12:32';
        $result = $this->Form->hour('Model.field', ['format' => 12]);
        $expected = [
            ['select' => ['name' => 'Model[field][hour]']],
            ['option' => ['value' => '']],
            '/option',
            ['option' => ['value' => '01']],
            '1',
            '/option',
            ['option' => ['value' => '02']],
            '2',
            '/option',
            $hoursRegex,
            ['option' => ['value' => '12', 'selected' => 'selected']],
            '12',
            '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $this->Form->request->data['Model']['field'] = '';
        $result = $this->Form->hour('Model.field', ['format' => 24, 'value' => '23']);
        $this->assertContains('<option value="23" selected="selected">23</option>', $result);

        $result = $this->Form->hour('Model.field', ['format' => 12, 'value' => '23']);
        $this->assertContains('<option value="11" selected="selected">11</option>', $result);

        $this->Form->request->data['Model']['field'] = '2006-10-10 00:12:32';
        $result = $this->Form->hour('Model.field', ['format' => 24]);
        $expected = [
            ['select' => ['name' => 'Model[field][hour]']],
            ['option' => ['value' => '']],
            '/option',
            ['option' => ['value' => '00', 'selected' => 'selected']],
            '0',
            '/option',
            ['option' => ['value' => '01']],
            '1',
            '/option',
            ['option' => ['value' => '02']],
            '2',
            '/option',
            $hoursRegex,
            '/select',
        ];
        $this->assertHtml($expected, $result);

        unset($this->Form->request->data['Model']['field']);
        $result = $this->Form->hour('Model.field', ['format' => 24, 'value' => 'now']);
        $thisHour = date('H');
        $optValue = date('G');
        $this->assertRegExp('/<option value="' . $thisHour . '" selected="selected">' . $optValue . '<\/option>/', $result);

        $this->Form->request->data['Model']['field'] = '2050-10-10 01:12:32';
        $result = $this->Form->hour('Model.field', ['format' => 24]);
        $expected = [
            ['select' => ['name' => 'Model[field][hour]']],
            ['option' => ['value' => '']],
            '/option',
            ['option' => ['value' => '00']],
            '0',
            '/option',
            ['option' => ['value' => '01', 'selected' => 'selected']],
            '1',
            '/option',
            ['option' => ['value' => '02']],
            '2',
            '/option',
            $hoursRegex,
            '/select',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testYear method
     *
     * Test generation of a year input.
     *
     * @return void
     */
    public function testYear()
    {
        $result = $this->Form->year('Model.field', ['value' => '', 'minYear' => 2006, 'maxYear' => 2007]);
        $expected = [
            ['select' => ['name' => 'Model[field][year]']],
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

        $result = $this->Form->year('Model.field', [
            'value' => '',
            'minYear' => 2006,
            'maxYear' => 2007,
            'orderYear' => 'asc'
        ]);
        $expected = [
            ['select' => ['name' => 'Model[field][year]']],
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

        $this->Form->request->data['Contact']['published'] = '2006-10-10';
        $result = $this->Form->year('Contact.published', [
            'empty' => false,
            'minYear' => 2006,
            'maxYear' => 2007,
        ]);
        $expected = [
            ['select' => ['name' => 'Contact[published][year]']],
            ['option' => ['value' => '2007']],
            '2007',
            '/option',
            ['option' => ['value' => '2006', 'selected' => 'selected']],
            '2006',
            '/option',
            '/select',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->year('Contact.published', [
            'empty' => 'Published on',
        ]);
        $this->assertContains('Published on', $result);
    }

    /**
     * testInputDatetimePreEpoch method
     *
     * Test minYear being prior to the unix epoch.
     *
     * @return void
     */
    public function testInputDatetimePreEpoch()
    {
        $start = date('Y') - 80;
        $end = date('Y') - 18;
        $result = $this->Form->input('birth_year', [
            'type' => 'date',
            'label' => 'Birth Year',
            'minYear' => $start,
            'maxYear' => $end,
            'month' => false,
            'day' => false,
        ]);
        $this->assertContains('value="' . $start . '">' . $start, $result);
        $this->assertContains('value="' . $end . '" selected="selected">' . $end, $result);
        $this->assertNotContains('value="00">00', $result);
    }

    /**
     * testYearAutoExpandRange method
     *
     * @return void
     */
    public function testYearAutoExpandRange()
    {
        $this->Form->request->data['User']['birthday'] = '1930-10-10';
        $result = $this->Form->year('User.birthday');
        preg_match_all('/<option value="([\d]+)"/', $result, $matches);

        $result = $matches[1];
        $expected = range(date('Y') + 5, 1930);
        $this->assertEquals($expected, $result);

        $this->Form->request->data['Project']['release'] = '2050-10-10';
        $result = $this->Form->year('Project.release');
        preg_match_all('/<option value="([\d]+)"/', $result, $matches);

        $result = $matches[1];
        $expected = range(2050, date('Y') - 5);
        $this->assertEquals($expected, $result);

        $this->Form->request->data['Project']['release'] = '1881-10-10';
        $result = $this->Form->year('Project.release', [
            'minYear' => 1890,
            'maxYear' => 1900
        ]);
        preg_match_all('/<option value="([\d]+)"/', $result, $matches);

        $result = $matches[1];
        $expected = range(1900, 1881);
        $this->assertEquals($expected, $result);
    }

    /**
     * testInputDate method
     *
     * Test that input() accepts the type of date and passes options in.
     *
     * @return void
     */
    public function testInputDate()
    {
        $this->Form->request->data = [
            'month_year' => ['month' => date('m')],
        ];
        $this->Form->create($this->article);
        $result = $this->Form->input('month_year', [
                'label' => false,
                'type' => 'date',
                'minYear' => 2006,
                'maxYear' => 2008
        ]);
        $this->assertContains('value="' . date('m') . '" selected="selected"', $result);
        $this->assertNotContains('value="2008" selected="selected"', $result);
    }

    /**
     * testInputLabelFalse method
     *
     * Test the label option being set to false.
     *
     * @return void
     */
    public function testInputLabelFalse()
    {
        $this->Form->create($this->article);
        $result = $this->Form->input('title', ['label' => false]);
        $expected = [
            'div' => ['class' => 'input text required'],
            'input' => ['type' => 'text', 'required' => 'required', 'id' => 'title', 'name' => 'title'],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testInputDateMaxYear method
     *
     * Let's say we want to only allow users born from 2006 to 2008 to register
     * This being the first signup page, we still don't have any data.
     *
     * @return void
     */
    public function testInputDateMaxYear()
    {
        $this->Form->request->data = [];
        $this->Form->create($this->article);
        $result = $this->Form->input('birthday', [
            'label' => false,
            'type' => 'date',
            'minYear' => 2006,
            'maxYear' => 2008,
            'default' => true
        ]);
        $this->assertContains('value="2008" selected="selected"', $result);
        $this->assertContains('value="2006"', $result);
        $this->assertNotContains('value="2005"', $result);
        $this->assertNotContains('value="2009"', $result);
    }

    /**
     * testTextArea method
     *
     * Test generation of a textarea input.
     *
     * @return void
     */
    public function testTextArea()
    {
        $this->Form->request->data = ['field' => 'some test data'];
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

        $this->Form->request->data = ['field' => 'some <strong>test</strong> data with <a href="#">HTML</a> chars'];
        $result = $this->Form->textarea('field');
        $expected = [
            'textarea' => ['name' => 'field', 'rows' => 5],
            htmlentities('some <strong>test</strong> data with <a href="#">HTML</a> chars'),
            '/textarea',
        ];
        $this->assertHtml($expected, $result);

        $this->Form->request->data = [
            'Model' => ['field' => 'some <strong>test</strong> data with <a href="#">HTML</a> chars']
        ];
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
            '/textarea'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testTextAreaWithStupidCharacters method
     *
     * Test text area with non-ascii characters.
     *
     * @return void
     */
    public function testTextAreaWithStupidCharacters()
    {
        $result = $this->Form->textarea('Post.content', [
            'value' => "GREAT®",
            'rows' => '15',
            'cols' => '75'
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
     *
     * @return void
     */
    public function testTextAreaMaxLength()
    {
        $this->Form->create([
            'schema' => [
                'stuff' => ['type' => 'string', 'length' => 10],
            ]
        ]);
        $result = $this->Form->input('other', ['type' => 'textarea']);
        $expected = [
            'div' => ['class' => 'input textarea'],
            'label' => ['for' => 'other'],
            'Other',
            '/label',
            'textarea' => ['name' => 'other', 'id' => 'other', 'rows' => 5],
            '/textarea',
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('stuff', ['type' => 'textarea']);
        $expected = [
            'div' => ['class' => 'input textarea'],
            'label' => ['for' => 'stuff'],
            'Stuff',
            '/label',
            'textarea' => ['name' => 'stuff', 'maxlength' => 10, 'id' => 'stuff', 'rows' => 5],
            '/textarea',
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testHiddenField method
     *
     * Test generation of a hidden input.
     *
     * @return void
     */
    public function testHiddenField()
    {
        $this->article['errors'] = [
            'field' => true
        ];
        $this->Form->request->data['field'] = 'test';
        $this->Form->create($this->article);
        $result = $this->Form->hidden('field', ['id' => 'theID']);
        $expected = [
            'input' => ['type' => 'hidden', 'class' => 'form-error', 'name' => 'field', 'id' => 'theID', 'value' => 'test']];
        $this->assertHtml($expected, $result);

        $result = $this->Form->hidden('field', ['value' => 'my value']);
        $expected = [
            'input' => ['type' => 'hidden', 'class' => 'form-error', 'name' => 'field', 'value' => 'my value']
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFileUploadField method
     *
     * Test generation of a file upload input.
     *
     * @return void
     */
    public function testFileUploadField()
    {
        $expected = ['input' => ['type' => 'file', 'name' => 'Model[upload]']];

        $result = $this->Form->file('Model.upload');
        $this->assertHtml($expected, $result);

        $this->Form->request->data['Model']['upload'] = [
            'name' => '', 'type' => '', 'tmp_name' => '',
            'error' => 4, 'size' => 0
        ];
        $result = $this->Form->file('Model.upload');
        $this->assertHtml($expected, $result);

        $this->Form->request->data['Model']['upload'] = 'no data should be set in value';
        $result = $this->Form->file('Model.upload');
        $this->assertHtml($expected, $result);
    }

    /**
     * testFileUploadOnOtherModel method
     *
     * Test File upload input on a model not used in create().
     *
     * @return void
     */
    public function testFileUploadOnOtherModel()
    {
        $this->Form->create($this->article, ['type' => 'file']);
        $result = $this->Form->file('ValidateProfile.city');
        $expected = [
            'input' => ['type' => 'file', 'name' => 'ValidateProfile[city]']
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testButton method
     *
     * Test generation of a form button.
     *
     * @return void
     */
    public function testButton()
    {
        $result = $this->Form->button('Hi');
        $expected = ['button' => ['type' => 'submit'], 'Hi', '/button'];
        $this->assertHtml($expected, $result);

        $result = $this->Form->button('Clear Form >', ['type' => 'reset']);
        $expected = ['button' => ['type' => 'reset'], 'Clear Form >', '/button'];
        $this->assertHtml($expected, $result);

        $result = $this->Form->button('Clear Form >', ['type' => 'reset', 'id' => 'clearForm']);
        $expected = ['button' => ['type' => 'reset', 'id' => 'clearForm'], 'Clear Form >', '/button'];
        $this->assertHtml($expected, $result);

        $result = $this->Form->button('<Clear Form>', ['type' => 'reset', 'escape' => true]);
        $expected = ['button' => ['type' => 'reset'], '&lt;Clear Form&gt;', '/button'];
        $this->assertHtml($expected, $result);

        $result = $this->Form->button('No type', ['type' => false]);
        $expected = ['button' => [], 'No type', '/button'];
        $this->assertHtml($expected, $result);

        $result = $this->Form->button('Upload Text', [
            'onClick' => "$('#postAddForm').ajaxSubmit({target: '#postTextUpload', url: '/posts/text'});return false;'",
            'escape' => false
        ]);
        $this->assertNotRegExp('/\&039/', $result);
    }

    /**
     * testButtonUnlockedByDefault method
     *
     * Test that button() makes unlocked fields by default.
     *
     * @return void
     */
    public function testButtonUnlockedByDefault()
    {
        $this->Form->request->params['_csrfToken'] = 'secured';
        $this->Form->button('Save', ['name' => 'save']);
        $this->Form->button('Clear');

        $result = $this->Form->unlockField();
        $this->assertEquals(['save'], $result);
    }

    /**
     * testPostButton method
     *
     * @return void
     */
    public function testPostButton()
    {
        $result = $this->Form->postButton('Hi', '/controller/action');
        $expected = [
            'form' => ['method' => 'post', 'action' => '/controller/action', 'accept-charset' => 'utf-8'],
            'div' => ['style' => 'display:none;'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/div',
            'button' => ['type' => 'submit'],
            'Hi',
            '/button',
            '/form'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->postButton('Send', '/', ['data' => ['extra' => 'value']]);
        $this->assertTrue(strpos($result, '<input type="hidden" name="extra" value="value"') !== false);
    }

    /**
     * testPostButtonMethodType method
     *
     * @return void
     */
    public function testPostButtonMethodType()
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
            '/form'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testPostButtonFormOptions method
     *
     * @return void
     */
    public function testPostButtonFormOptions()
    {
        $result = $this->Form->postButton('Hi', '/controller/action', ['form' => ['class' => 'inline']]);
        $expected = [
            'form' => ['method' => 'post', 'action' => '/controller/action', 'accept-charset' => 'utf-8', 'class' => 'inline'],
            'div' => ['style' => 'display:none;'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/div',
            'button' => ['type' => 'submit'],
            'Hi',
            '/button',
            '/form'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testPostButtonNestedData method
     *
     * Test using postButton with N dimensional data.
     *
     * @return void
     */
    public function testPostButtonNestedData()
    {
        $data = [
            'one' => [
                'two' => [
                    3, 4, 5
                ]
            ]
        ];
        $result = $this->Form->postButton('Send', '/', ['data' => $data]);
        $this->assertContains('<input type="hidden" name="one[two][0]" value="3"', $result);
        $this->assertContains('<input type="hidden" name="one[two][1]" value="4"', $result);
        $this->assertContains('<input type="hidden" name="one[two][2]" value="5"', $result);
    }

    /**
     * testSecurePostButton method
     *
     * Test that postButton adds _Token fields.
     *
     * @return void
     */
    public function testSecurePostButton()
    {
        $this->Form->request->params['_csrfToken'] = 'testkey';
        $this->Form->request->params['_Token'] = ['unlockedFields' => []];

        $result = $this->Form->postButton('Delete', '/posts/delete/1');
        $tokenDebug = urlencode(json_encode([
                '/posts/delete/1',
                [],
                []
            ]));

        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/posts/delete/1', 'accept-charset' => 'utf-8',
            ],
            ['div' => ['style' => 'display:none;']],
            ['input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST']],
            ['input' => ['type' => 'hidden', 'name' => '_csrfToken', 'value' => 'testkey']],
            '/div',
            'button' => ['type' => 'submit'],
            'Delete',
            '/button',
            ['div' => ['style' => 'display:none;']],
            ['input' => ['type' => 'hidden', 'name' => '_Token[fields]', 'value' => 'preg:/[\w\d%]+/']],
            ['input' => ['type' => 'hidden', 'name' => '_Token[unlocked]', 'value' => '']],
            ['input' => [
                'type' => 'hidden', 'name' => '_Token[debug]',
                'value' => $tokenDebug,
            ]],
            '/div',
            '/form',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testPostLink method
     *
     * @return void
     */
    public function testPostLink()
    {
        $result = $this->Form->postLink('Delete', '/posts/delete/1');
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/posts/delete/1',
                'name' => 'preg:/post_\w+/', 'style' => 'display:none;'
            ],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/form',
            'a' => ['href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'],
            'Delete',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->postLink('Delete', '/posts/delete/1', ['method' => 'delete']);
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/posts/delete/1',
                'name' => 'preg:/post_\w+/', 'style' => 'display:none;'
            ],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'DELETE'],
            '/form',
            'a' => ['href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'],
            'Delete',
            '/a'
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
                'name' => 'preg:/post_\w+/', 'style' => 'display:none;'
            ],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/form',
            'a' => ['class' => 'btn btn-danger', 'href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'],
            'Delete',
            '/a'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testPostLinkWithConfirm method
     *
     * Test the confirm option for postLink().
     *
     * @return void
     */
    public function testPostLinkWithConfirm()
    {
        $result = $this->Form->postLink('Delete', '/posts/delete/1', ['confirm' => 'Confirm?']);
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/posts/delete/1',
                'name' => 'preg:/post_\w+/', 'style' => 'display:none;'
            ],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/form',
            'a' => ['href' => '#', 'onclick' => 'preg:/if \(confirm\(&quot;Confirm\?&quot;\)\) \{ document\.post_\w+\.submit\(\); \} event\.returnValue = false; return false;/'],
            'Delete',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->postLink(
            'Delete',
            '/posts/delete/1',
            ['escape' => false, 'confirm' => "'Confirm'\nthis \"deletion\"?"]
        );
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/posts/delete/1',
                'name' => 'preg:/post_\w+/', 'style' => 'display:none;'
            ],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/form',
            'a' => ['href' => '#', 'onclick' => "preg:/if \(confirm\(&quot;&#039;Confirm&#039;\\nthis \\\\&quot;deletion\\\\&quot;\?&quot;\)\) \{ document\.post_\w+\.submit\(\); \} event\.returnValue = false; return false;/"],
            'Delete',
            '/a'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testPostLinkWithQuery method
     *
     * Test postLink() with query string args.
     *
     * @return void
     */
    public function testPostLinkWithQuery()
    {
        $result = $this->Form->postLink(
            'Delete',
            ['controller' => 'posts', 'action' => 'delete', 1, '?' => ['a' => 'b', 'c' => 'd']]
        );
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/posts/delete/1?a=b&amp;c=d',
                'name' => 'preg:/post_\w+/', 'style' => 'display:none;'
            ],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/form',
            'a' => ['href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'],
            'Delete',
            '/a'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testPostLinkWithData method
     *
     * Test postLink with additional data.
     *
     * @return void
     */
    public function testPostLinkWithData()
    {
        $result = $this->Form->postLink('Delete', '/posts/delete', ['data' => ['id' => 1]]);
        $this->assertContains('<input type="hidden" name="id" value="1"', $result);

        $entity = new Entity(['name' => 'no show'], ['source' => 'Articles']);
        $this->Form->create($entity);
        $this->Form->end();
        $result = $this->Form->postLink('Delete', '/posts/delete', ['data' => ['name' => 'show']]);
        $this->assertContains(
            '<input type="hidden" name="name" value="show"',
            $result,
            'should not contain entity data.'
        );
    }

    /**
     * testPostLinkSecurityHash method
     *
     * Test that security hashes for postLink include the url.
     *
     * @return void
     */
    public function testPostLinkSecurityHash()
    {
        $hash = Security::hash(
            '/posts/delete/1' .
            serialize(['id' => '1']) .
            '' .
            Security::salt()
        );
        $hash .= '%3Aid';
        $this->Form->request->params['_Token']['key'] = 'test';

        $result = $this->Form->postLink(
            'Delete',
            '/posts/delete/1',
            ['data' => ['id' => 1]]
        );
        $tokenDebug = urlencode(json_encode([
            '/posts/delete/1',
            [
                'id' => 1
            ],
            []
        ]));
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/posts/delete/1',
                'name', 'style' => 'display:none;'
            ],
            ['input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST']],
            ['input' => ['type' => 'hidden', 'name' => 'id', 'value' => '1']],
            'div' => ['style' => 'display:none;'],
            ['input' => ['type' => 'hidden', 'name' => '_Token[fields]', 'value' => $hash]],
            ['input' => ['type' => 'hidden', 'name' => '_Token[unlocked]', 'value' => '']],
            ['input' => [
                'type' => 'hidden', 'name' => '_Token[debug]',
                'value' => $tokenDebug,
            ]],
            '/div',
            '/form',
            'a' => ['href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'],
            'Delete',
            '/a'
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
     *
     * @return void
     */
    public function testPostLinkSecurityHashBlockMode()
    {
        $hash = Security::hash(
            '/posts/delete/1' .
            serialize([]) .
            '' .
            Security::salt()
        );
        $hash .= '%3A';
        $this->Form->request->params['_Token']['key'] = 'test';

        $this->Form->create('Post', ['url' => ['action' => 'add']]);
        $this->Form->input('title');
        $this->Form->postLink('Delete', '/posts/delete/1', ['block' => true]);
        $result = $this->View->fetch('postLink');

        $this->assertEquals(['title'], $this->Form->fields);
        $this->assertContains($hash, $result, 'Should contain the correct hash.');
        $this->assertAttributeEquals('/articles/add', '_lastAction', $this->Form, 'lastAction was should be restored.');
    }

    /**
     * testPostLinkSecurityHashNoDebugMode method
     *
     * Test that security does not include debug token if debug is false.
     *
     * @return void
     */
    public function testPostLinkSecurityHashNoDebugMode()
    {
        Configure::write('debug', false);
        $hash = Security::hash(
            '/posts/delete/1' .
            serialize(['id' => '1']) .
            '' .
            Security::salt()
        );
        $hash .= '%3Aid';
        $this->Form->request->params['_Token']['key'] = 'test';

        $result = $this->Form->postLink(
            'Delete',
            '/posts/delete/1',
            ['data' => ['id' => 1]]
        );
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/posts/delete/1',
                'name', 'style' => 'display:none;'
            ],
            ['input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST']],
            ['input' => ['type' => 'hidden', 'name' => 'id', 'value' => '1']],
            'div' => ['style' => 'display:none;'],
            ['input' => ['type' => 'hidden', 'name' => '_Token[fields]', 'value' => $hash]],
            ['input' => ['type' => 'hidden', 'name' => '_Token[unlocked]', 'value' => '']],
            '/div',
            '/form',
            'a' => ['href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'],
            'Delete',
            '/a'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testPostLinkNestedData method
     *
     * Test using postLink with N dimensional data.
     *
     * @return void
     */
    public function testPostLinkNestedData()
    {
        $data = [
            'one' => [
                'two' => [
                    3, 4, 5
                ]
                ]
        ];
        $result = $this->Form->postLink('Send', '/', ['data' => $data]);
        $this->assertContains('<input type="hidden" name="one[two][0]" value="3"', $result);
        $this->assertContains('<input type="hidden" name="one[two][1]" value="4"', $result);
        $this->assertContains('<input type="hidden" name="one[two][2]" value="5"', $result);
    }

    /**
     * testPostLinkAfterGetForm method
     *
     * Test creating postLinks after a GET form.
     *
     * @return void
     */
    public function testPostLinkAfterGetForm()
    {
        $this->Form->request->params['_csrfToken'] = 'testkey';
        $this->Form->request->params['_Token'] = 'val';

        $this->Form->create($this->article, ['type' => 'get']);
        $this->Form->end();

        $result = $this->Form->postLink('Delete', '/posts/delete/1');
        $tokenDebug = urlencode(json_encode([
            '/posts/delete/1',
            [],
            []
        ]));
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/posts/delete/1',
                'name' => 'preg:/post_\w+/', 'style' => 'display:none;'
            ],
            ['input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST']],
            ['input' => ['type' => 'hidden', 'name' => '_csrfToken', 'value' => 'testkey']],
            'div' => ['style' => 'display:none;'],
            ['input' => ['type' => 'hidden', 'name' => '_Token[fields]', 'value' => 'preg:/[\w\d%]+/']],
            ['input' => ['type' => 'hidden', 'name' => '_Token[unlocked]', 'value' => '']],
            ['input' => [
                'type' => 'hidden', 'name' => '_Token[debug]',
                'value' => $tokenDebug,
            ]],
            '/div',
            '/form',
            'a' => ['href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'],
            'Delete',
            '/a'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testPostLinkFormBuffer method
     *
     * Test that postLink adds form tags to view block.
     *
     * @return void
     */
    public function testPostLinkFormBuffer()
    {
        $result = $this->Form->postLink('Delete', '/posts/delete/1', ['block' => true]);
        $expected = [
            'a' => ['href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'],
            'Delete',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->View->fetch('postLink');
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/posts/delete/1',
                'name' => 'preg:/post_\w+/', 'style' => 'display:none;'
            ],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/form'
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
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->View->fetch('postLink');
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/posts/delete/1',
                'name' => 'preg:/post_\w+/', 'style' => 'display:none;'
            ],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/form',
            [
                'form' => [
                    'method' => 'post', 'action' => '/posts/delete/2',
                    'name' => 'preg:/post_\w+/', 'style' => 'display:none;'
                ],
            ],
            ['input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'DELETE']],
            '/form'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->postLink('Delete', '/posts/delete/1', ['block' => 'foobar']);
        $expected = [
            'a' => ['href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'],
            'Delete',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->View->fetch('foobar');
        $expected = [
            'form' => [
                'method' => 'post', 'action' => '/posts/delete/1',
                'name' => 'preg:/post_\w+/', 'style' => 'display:none;'
            ],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
            '/form'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testSubmitButton method
     *
     * @return void
     */
    public function testSubmitButton()
    {
        $result = $this->Form->submit('');
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'submit', 'value' => ''],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->submit('Test Submit');
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'submit', 'value' => 'Test Submit'],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->submit('Next >');
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'submit', 'value' => 'Next &gt;'],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->submit('Next >', ['escape' => false]);
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'submit', 'value' => 'Next >'],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->submit('Reset!', ['type' => 'reset']);
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'reset', 'value' => 'Reset!'],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testSubmitImage method
     *
     * Test image submit types.
     *
     * @return void
     */
    public function testSubmitImage()
    {
        $result = $this->Form->submit('http://example.com/cake.power.gif');
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'image', 'src' => 'http://example.com/cake.power.gif'],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->submit('/relative/cake.power.gif');
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'image', 'src' => 'relative/cake.power.gif'],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->submit('cake.power.gif');
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'image', 'src' => 'img/cake.power.gif'],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->submit('Not.an.image');
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'submit', 'value' => 'Not.an.image'],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testSubmitUnlockedByDefault method
     *
     * Submit buttons should be unlocked by default as there could be multiples, and only one will
     * be submitted at a time.
     *
     * @return void
     */
    public function testSubmitUnlockedByDefault()
    {
        $this->Form->request->params['_Token'] = 'secured';
        $this->Form->submit('Go go');
        $this->Form->submit('Save', ['name' => 'save']);

        $result = $this->Form->unlockField();
        $this->assertEquals(['save'], $result, 'Only submits with name attributes should be unlocked.');
    }

    /**
     * testSubmitImageTimestamp method
     *
     * Test submit image with timestamps.
     *
     * @return void
     */
    public function testSubmitImageTimestamp()
    {
        Configure::write('Asset.timestamp', 'force');

        $result = $this->Form->submit('cake.power.gif');
        $expected = [
            'div' => ['class' => 'submit'],
            'input' => ['type' => 'image', 'src' => 'preg:/img\/cake\.power\.gif\?\d*/'],
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testDateTimeWithGetForms method
     *
     * Test that datetime() works with GET style forms.
     *
     * @return void
     */
    public function testDateTimeWithGetForms()
    {
        extract($this->dateRegex);
        $this->Form->create($this->article, ['type' => 'get']);
        $result = $this->Form->datetime('created');

        $this->assertContains('name="created[year]"', $result, 'year name attribute is wrong.');
        $this->assertContains('name="created[month]"', $result, 'month name attribute is wrong.');
        $this->assertContains('name="created[day]"', $result, 'day name attribute is wrong.');
        $this->assertContains('name="created[hour]"', $result, 'hour name attribute is wrong.');
        $this->assertContains('name="created[minute]"', $result, 'min name attribute is wrong.');
    }

    /**
     * testForMagicInputNonExistingNorValidated method
     *
     * @return void
     */
    public function testForMagicInputNonExistingNorValidated()
    {
        $result = $this->Form->create($this->article);
        $this->Form->templates(['inputContainer' => '{{content}}']);
        $result = $this->Form->input('non_existing_nor_validated');
        $expected = [
            'label' => ['for' => 'non-existing-nor-validated'],
            'Non Existing Nor Validated',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'non_existing_nor_validated',
                'id' => 'non-existing-nor-validated'
            ]
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('non_existing_nor_validated', [
            'val' => 'my value'
        ]);
        $expected = [
            'label' => ['for' => 'non-existing-nor-validated'],
            'Non Existing Nor Validated',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'non_existing_nor_validated',
                'value' => 'my value', 'id' => 'non-existing-nor-validated'
            ]
        ];
        $this->assertHtml($expected, $result);

        $this->Form->request->data = ['non_existing_nor_validated' => 'CakePHP magic'];
        $result = $this->Form->input('non_existing_nor_validated');
        $expected = [
            'label' => ['for' => 'non-existing-nor-validated'],
            'Non Existing Nor Validated',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'non_existing_nor_validated',
                'value' => 'CakePHP magic', 'id' => 'non-existing-nor-validated'
            ]
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormMagicInputLabel method
     *
     * @return void
     */
    public function testFormMagicInputLabel()
    {
        TableRegistry::get('Contacts', [
            'className' => __NAMESPACE__ . '\ContactsTable'
        ]);
        $this->Form->create([], ['context' => ['table' => 'Contacts']]);
        $this->Form->templates(['inputContainer' => '{{content}}']);

        $result = $this->Form->input('Contacts.name', ['label' => 'My label']);
        $expected = [
            'label' => ['for' => 'contacts-name'],
            'My label',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'Contacts[name]',
                'id' => 'contacts-name', 'maxlength' => '255'
            ]
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('name', [
            'label' => ['class' => 'mandatory']
        ]);
        $expected = [
            'label' => ['for' => 'name', 'class' => 'mandatory'],
            'Name',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'name',
                'id' => 'name', 'maxlength' => '255'
            ]
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('name', [
            'div' => false,
            'label' => ['class' => 'mandatory', 'text' => 'My label']
        ]);
        $expected = [
            'label' => ['for' => 'name', 'class' => 'mandatory'],
            'My label',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'name',
                'id' => 'name', 'maxlength' => '255'
            ]
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('Contact.name', [
            'div' => false, 'id' => 'my_id', 'label' => ['for' => 'my_id']
        ]);
        $expected = [
            'label' => ['for' => 'my_id'],
            'Name',
            '/label',
            'input' => [
                'type' => 'text', 'name' => 'Contact[name]',
                'id' => 'my_id', 'maxlength' => '255'
            ]
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('1.id');
        $expected = ['input' => [
            'type' => 'hidden', 'name' => '1[id]',
            'id' => '1-id'
        ]];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input("1.name");
        $expected = [
            'label' => ['for' => '1-name'],
            'Name',
            '/label',
            'input' => [
                'type' => 'text', 'name' => '1[name]',
                'id' => '1-name', 'maxlength' => '255'
            ]
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testFormEnd method
     *
     * @return void
     */
    public function testFormEnd()
    {
        $this->assertEquals('</form>', $this->Form->end());
    }

    /**
     * testMultiRecordForm method
     *
     * Test the generation of fields for a multi record form.
     *
     * @return void
     */
    public function testMultiRecordForm()
    {
        $this->loadFixtures('Articles', 'Comments');
        $articles = TableRegistry::get('Articles');
        $articles->hasMany('Comments');

        $comment = new Entity(['comment' => 'Value']);
        $article = new Article(['comments' => [$comment]]);
        $this->Form->create([$article]);
        $result = $this->Form->input('0.comments.1.comment');
        //@codingStandardsIgnoreStart
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
        //@codingStandardsIgnoreEnd
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('0.comments.0.comment');
        //@codingStandardsIgnoreStart
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
        //@codingStandardsIgnoreEnd
        $this->assertHtml($expected, $result);

        $comment->errors('comment', ['Not valid']);
        $result = $this->Form->input('0.comments.0.comment');
        //@codingStandardsIgnoreStart
        $expected = [
            'div' => ['class' => 'input textarea error'],
                'label' => ['for' => '0-comments-0-comment'],
                    'Comment',
                '/label',
                'textarea' => [
                    'name',
                    'class' => 'form-error',
                    'id' => '0-comments-0-comment',
                    'rows' => 5
                ],
                'Value',
                '/textarea',
                ['div' => ['class' => 'error-message']],
                'Not valid',
                '/div',
            '/div'
        ];
        //@codingStandardsIgnoreEnd
        $this->assertHtml($expected, $result);

        TableRegistry::get('Comments')
            ->validator('default')
            ->allowEmpty('comment', false);
        $result = $this->Form->input('0.comments.1.comment');
        //@codingStandardsIgnoreStart
        $expected = [
            'div' => ['class' => 'input textarea required'],
                'label' => ['for' => '0-comments-1-comment'],
                    'Comment',
                '/label',
                'textarea' => [
                    'name',
                    'required' => 'required',
                    'id' => '0-comments-1-comment',
                    'rows' => 5
                ],
                '/textarea',
            '/div'
        ];
        //@codingStandardsIgnoreEnd
        $this->assertHtml($expected, $result);
    }

    /**
     * testHtml5Inputs method
     *
     * Test that some html5 inputs + FormHelper::__call() work.
     *
     * @return void
     */
    public function testHtml5Inputs()
    {
        $result = $this->Form->email('User.email');
        $expected = [
            'input' => ['type' => 'email', 'name' => 'User[email]']
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->search('User.query');
        $expected = [
            'input' => ['type' => 'search', 'name' => 'User[query]']
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->search('User.query', ['value' => 'test']);
        $expected = [
            'input' => ['type' => 'search', 'name' => 'User[query]', 'value' => 'test']
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->search('User.query', ['type' => 'text', 'value' => 'test']);
        $expected = [
            'input' => ['type' => 'text', 'name' => 'User[query]', 'value' => 'test']
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testHtml5InputWithInput method
     *
     * Test accessing html5 inputs through input().
     *
     * @return void
     */
    public function testHtml5InputWithInput()
    {
        $this->Form->create();
        $this->Form->templates(['inputContainer' => '{{content}}']);
        $result = $this->Form->input('website', [
            'type' => 'url',
            'val' => 'http://domain.tld',
            'label' => false
        ]);
        $expected = [
            'input' => ['type' => 'url', 'name' => 'website', 'id' => 'website', 'value' => 'http://domain.tld']
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testHtml5InputException method
     *
     * Test errors when field name is missing.
     *
     * @expectedException \Cake\Core\Exception\Exception
     * @return void
     */
    public function testHtml5InputException()
    {
        $this->Form->email();
    }

    /**
     * testRequiredAttribute method
     *
     * Tests that formhelper sets required attributes.
     *
     * @return void
     */
    public function testRequiredAttribute()
    {
        $this->article['required'] = [
            'title' => true,
            'body' => false,
        ];
        $this->Form->create($this->article);

        $result = $this->Form->input('title');
        $expected = [
            'div' => ['class' => 'input text required'],
            'label' => ['for' => 'title'],
            'Title',
            '/label',
            'input' => [
                'type' => 'text',
                'name' => 'title',
                'id' => 'title',
                'required' => 'required',
            ],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('title', ['required' => false]);
        $this->assertNotContains('required', $result);

        $result = $this->Form->input('body');
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
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('body', ['required' => true]);
        $this->assertContains('required', $result);
    }

    /**
     * testInputsNotNested method
     *
     * Tests that it is possible to put inputs outside of the label.
     *
     * @return void
     */
    public function testInputsNotNested()
    {
        $this->Form->templates([
            'nestingLabel' => '{{hidden}}{{input}}<label{{attrs}}>{{text}}</label>',
            'formGroup' => '{{input}}{{label}}',
        ]);
        $result = $this->Form->input('foo', ['type' => 'checkbox']);
        $expected = [
            'div' => ['class' => 'input checkbox'],
            ['input' => ['type' => 'hidden', 'name' => 'foo', 'value' => '0']],
            ['input' => ['type' => 'checkbox', 'name' => 'foo', 'id' => 'foo', 'value' => '1']],
            'label' => ['for' => 'foo'],
                'Foo',
            '/label',
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('foo', ['type' => 'checkbox', 'label' => false]);
        $expected = [
            'div' => ['class' => 'input checkbox'],
            ['input' => ['type' => 'hidden', 'name' => 'foo', 'value' => '0']],
            ['input' => ['type' => 'checkbox', 'name' => 'foo', 'id' => 'foo', 'value' => '1']],
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('confirm', [
            'type' => 'radio',
            'options' => ['Y' => 'Yes', 'N' => 'No']
        ]);
        $expected = [
            'div' => ['class' => 'input radio'],
            ['input' => ['type' => 'hidden', 'name' => 'confirm', 'value' => '']],
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
            'input' => ['type' => 'hidden', 'name' => 'fish', 'value' => ''],
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
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testInputContainerTemplates method
     *
     * Test that *Container templates are used by input.
     *
     * @return void
     */
    public function testInputContainerTemplates()
    {
        $this->Form->templates([
            'checkboxContainer' => '<div class="check">{{content}}</div>',
            'radioContainer' => '<div class="rad">{{content}}</div>',
            'radioContainerError' => '<div class="rad err">{{content}}</div>',
            'datetimeContainer' => '<div class="dt">{{content}}</div>',
        ]);

        $this->article['errors'] = [
            'Article' => ['published' => 'error message']
        ];
        $this->Form->create($this->article);

        $result = $this->Form->input('accept', [
            'type' => 'checkbox'
        ]);
        $expected = [
            'div' => ['class' => 'check'],
            ['input' => ['type' => 'hidden', 'name' => 'accept', 'value' => 0]],
            'label' => ['for' => 'accept'],
            ['input' => ['id' => 'accept', 'type' => 'checkbox', 'name' => 'accept', 'value' => 1]],
            'Accept',
            '/label',
            '/div'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Form->input('accept', [
            'type' => 'radio',
            'options' => ['Y', 'N']
        ]);
        $this->assertContains('<div class="rad">', $result);

        $result = $this->Form->input('Article.published', [
            'type' => 'radio',
            'options' => ['Y', 'N']
        ]);
        $this->assertContains('<div class="rad err">', $result);

        $result = $this->Form->input('Article.created', [
            'type' => 'datetime',
        ]);
        $this->assertContains('<div class="dt">', $result);
    }

    /**
     * testFormGroupTemplates method
     *
     * Test that *Container templates are used by input.
     *
     * @return void
     */
    public function testFormGroupTemplates()
    {
        $this->Form->templates([
            'radioFormGroup' => '<div class="radio">{{label}}{{input}}</div>',
        ]);

        $this->Form->create($this->article);

        $result = $this->Form->input('accept', [
            'type' => 'radio',
            'options' => ['Y', 'N']
        ]);
        $this->assertContains('<div class="radio">', $result);
    }

    /**
     * testResetTemplates method
     *
     * Test resetting templates.
     *
     * @return void
     */
    public function testResetTemplates()
    {
        $this->Form->templates(['input' => '<input/>']);
        $this->assertEquals('<input/>', $this->Form->templater()->get('input'));

        $this->assertNull($this->Form->resetTemplates());
        $this->assertNotEquals('<input/>', $this->Form->templater()->get('input'));
    }

    /**
     * testContext method
     *
     * Test the context method.
     *
     * @return void
     */
    public function testContext()
    {
        $result = $this->Form->context();
        $this->assertInstanceOf('Cake\View\Form\ContextInterface', $result);

        $mock = $this->getMockBuilder('Cake\View\Form\ContextInterface')->getMock();
        $this->assertSame($mock, $this->Form->context($mock));
        $this->assertSame($mock, $this->Form->context());
    }

    /**
     * testAutoDomId method
     *
     * @return void
     */
    public function testAutoDomId()
    {
        $result = $this->Form->text('field', ['id' => true]);
        $expected = [
            'input' => ['type' => 'text', 'name' => 'field', 'id' => 'field'],
        ];
        $this->assertHtml($expected, $result);

        // Ensure id => doesn't cause problem when multiple inputs are generated.
        $result = $this->Form->radio('field', ['option A', 'option B'], ['id' => true]);
        $expected = [
            'input' => ['type' => 'hidden', 'name' => 'field', 'value' => ''],
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
                'type' => 'hidden', 'name' => 'multi_field', 'value' => ''
            ],
            ['div' => ['class' => 'checkbox']],
                ['label' => ['for' => 'multi-field-0']],
                    ['input' => [
                        'type' => 'checkbox', 'name' => 'multi_field[]',
                        'value' => '0', 'id' => 'multi-field-0'
                    ]],
                    'first',
                    '/label',
                    '/div',
                    ['div' => ['class' => 'checkbox']],
                    ['label' => ['for' => 'multi-field-1']],
                    ['input' => [
                        'type' => 'checkbox', 'name' => 'multi_field[]',
                        'value' => '1', 'id' => 'multi-field-1'
                    ]],
                    'second',
                    '/label',
                    '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testNestedLabelInput method
     *
     * Test the `nestedInput` parameter
     *
     * @return void
     */
    public function testNestedLabelInput()
    {
        $result = $this->Form->input('foo', ['nestedInput' => true]);
        $expected = [
            'div' => ['class' => 'input text'],
            'label' => ['for' => 'foo'],
            ['input' => [
                'type' => 'text',
                'name' => 'foo',
                'id' => 'foo'
            ]],
            'Foo',
            '/label',
            '/div'
        ];
        $this->assertHtml($expected, $result);
    }
}
