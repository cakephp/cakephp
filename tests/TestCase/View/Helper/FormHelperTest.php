<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View\Helper;

use Cake\Collection\Collection;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;
use Cake\View\Helper\FormHelper;
use Cake\View\Helper\HtmlHelper;
use Cake\View\View;

/**
 * Test stub.
 */
class Article extends Entity {
}

/**
 * Contact class
 *
 */
class ContactsTable extends Table {

/**
 * Default schema
 *
 * @var array
 */
	protected $_schema = array(
		'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'email' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'phone' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'password' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'published' => array('type' => 'date', 'null' => true, 'default' => null, 'length' => null),
		'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
		'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null),
		'age' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => null),
		'_constraints' => array('primary' => ['type' => 'primary', 'columns' => ['id']])
	);

/**
 * Initializes the schema
 *
 * @return void
 */
	public function initialize(array $config) {
		$this->schema($this->_schema);
	}

}

/**
 * ValidateUser class
 *
 */
class ValidateUsersTable extends Table {

/**
 * schema method
 *
 * @var array
 */
	protected $_schema = array(
		'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'email' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'balance' => array('type' => 'float', 'null' => false, 'length' => 5, 'precision' => 2),
		'cost_decimal' => array('type' => 'decimal', 'null' => false, 'length' => 6, 'precision' => 3),
		'ratio' => array('type' => 'decimal', 'null' => false, 'length' => 10, 'precision' => 6),
		'population' => array('type' => 'decimal', 'null' => false, 'length' => 15, 'precision' => 0),
		'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
		'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null),
		'_constraints' => array('primary' => ['type' => 'primary', 'columns' => ['id']])
	);

/**
 * Initializes the schema
 *
 * @return void
 */
	public function initialize(array $config) {
		$this->schema($this->_schema);
	}

}

/**
 * FormHelperTest class
 *
 * @property FormHelper $Form
 */
class FormHelperTest extends TestCase {

/**
 * Fixtures to be used
 *
 * @var array
 */
	public $fixtures = array('core.article', 'core.comment');

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
	public function setUp() {
		parent::setUp();

		Configure::write('Config.language', 'eng');
		Configure::write('App.base', '');
		Configure::write('App.namespace', 'Cake\Test\TestCase\View\Helper');
		Configure::delete('Asset');
		$this->View = new View();

		$this->Form = new FormHelper($this->View);
		$this->Form->request = new Request('articles/add');
		$this->Form->request->here = '/articles/add';
		$this->Form->request['controller'] = 'articles';
		$this->Form->request['action'] = 'add';
		$this->Form->request->webroot = '';
		$this->Form->request->base = '';

		$this->dateRegex = array(
			'daysRegex' => 'preg:/(?:<option value="0?([\d]+)">\\1<\/option>[\r\n]*)*/',
			'monthsRegex' => 'preg:/(?:<option value="[\d]+">[\w]+<\/option>[\r\n]*)*/',
			'yearsRegex' => 'preg:/(?:<option value="([\d]+)">\\1<\/option>[\r\n]*)*/',
			'hoursRegex' => 'preg:/(?:<option value="0?([\d]+)">\\1<\/option>[\r\n]*)*/',
			'minutesRegex' => 'preg:/(?:<option value="([\d]+)">0?\\1<\/option>[\r\n]*)*/',
			'meridianRegex' => 'preg:/(?:<option value="(am|pm)">\\1<\/option>[\r\n]*)*/',
		);

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

		Configure::write('Security.salt', 'foo!');
		Router::connect('/:controller', array('action' => 'index'));
		Router::connect('/:controller/:action/*');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Form, $this->Controller, $this->View);
		TableRegistry::clear();
	}

/**
 * Test construct() with the templates option.
 *
 * @return void
 */
	public function testConstructTemplatesFile() {
		$helper = new FormHelper($this->View, [
			'templates' => 'htmlhelper_tags.php'
		]);
		$result = $helper->input('name');
		$this->assertContains('<input', $result);
	}

/**
 * Test registering a new widget class and rendering it.
 *
 * @return void
 */
	public function testAddWidgetAndRenderWidget() {
		$data = [
			'val' => 1
		];
		$mock = $this->getMock('Cake\View\Widget\WidgetInterface');
		$this->assertNull($this->Form->addWidget('test', $mock));
		$mock->expects($this->once())
			->method('render')
			->with($data)
			->will($this->returnValue('HTML'));
		$result = $this->Form->widget('test', $data);
		$this->assertEquals('HTML', $result);
	}

/**
 * Test registering an invalid widget class.
 *
 * @expectedException \RuntimeException
 * @return void
 */
	public function testAddWidgetInvalid() {
		$mock = new \StdClass();
		$this->Form->addWidget('test', $mock);
		$this->Form->widget('test');
	}

/**
 * Test adding a new context class.
 *
 * @return void
 */
	public function testAddContextProvider() {
		$context = 'My data';
		$this->Form->addContextProvider('test', function ($request, $data) use ($context) {
			$this->assertInstanceOf('Cake\Network\Request', $request);
			$this->assertEquals($context, $data['entity']);
			return $this->getMock('Cake\View\Form\ContextInterface');
		});
		$this->Form->create($context);
	}

/**
 * Test adding an invalid context class.
 *
 * @expectedException RuntimeException
 * @expectedExceptionMessage Context objects must implement Cake\View\Form\ContextInterface
 * @return void
 */
	public function testAddContextProviderInvalid() {
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
	public function contextSelectionProvider() {
		$entity = new Article();
		$collection = $this->getMock('Cake\Collection\Collection', ['extract'], [[$entity]]);
		$data = [
			'schema' => [
				'title' => ['type' => 'string']
			]
		];

		return [
			'entity' => [$entity, 'Cake\View\Form\EntityContext'],
			'collection' => [$collection, 'Cake\View\Form\EntityContext'],
			'array' => [$data, 'Cake\View\Form\ArrayContext'],
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
	public function testCreateContextSelectionBuiltIn($data, $class) {
		$this->loadFixtures('Article');
		$this->Form->create($data);
		$this->assertInstanceOf($class, $this->Form->context());
	}

/**
 * Data provider for type option.
 *
 * @return array
 */
	public static function requestTypeProvider() {
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
	public function testCreateFile() {
		$encoding = strtolower(Configure::read('App.encoding'));
		$result = $this->Form->create(false, array('type' => 'file'));
		$expected = array(
			'form' => array(
				'method' => 'post', 'action' => '/articles/add',
				'accept-charset' => $encoding, 'enctype' => 'multipart/form-data'
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test creating GET forms.
 *
 * @return void
 */
	public function testCreateGet() {
		$encoding = strtolower(Configure::read('App.encoding'));
		$result = $this->Form->create(false, array('type' => 'get'));
		$expected = array('form' => array(
			'method' => 'get', 'action' => '/articles/add',
			'accept-charset' => $encoding
		));
		$this->assertTags($result, $expected);
	}

/**
 * Test create() with the templates option.
 *
 * @return void
 */
	public function testCreateTemplatesArray() {
		$result = $this->Form->create($this->article, [
			'templates' => [
				'formstart' => '<form class="form-horizontal"{{attrs}}>',
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
		$this->assertTags($result, $expected);
	}

/**
 * Test create() with the templates option.
 *
 * @return void
 */
	public function testCreateTemplatesFile() {
		$result = $this->Form->create($this->article, [
			'templates' => 'htmlhelper_tags.php',
		]);
		$expected = [
			'start form',
			'div' => ['class' => 'hidden'],
			'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
			'/div'
		];
		$this->assertTags($result, $expected);
	}

/**
 * test the create() method
 *
 * @dataProvider requestTypeProvider
 * @return void
 */
	public function testCreateTypeOptions($type, $method, $override) {
		$encoding = strtolower(Configure::read('App.encoding'));
		$result = $this->Form->create(false, array('type' => $type));
		$expected = array(
			'form' => array(
				'method' => $method, 'action' => '/articles/add',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => $override),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test opening a form for an update operation.
 *
 * @return void
 */
	public function testCreateUpdateForm() {
		$encoding = strtolower(Configure::read('App.encoding'));

		$this->Form->request->here = '/articles/edit/1';
		$this->Form->request['action'] = 'edit';

		$this->article['defaults']['id'] = 1;

		$result = $this->Form->create($this->article);
		$expected = array(
			'form' => array(
				'method' => 'post', 'action' => '/articles/edit/1',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'PUT'),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test create() with automatic url generation
 *
 * @return void
 */
	public function testCreateAutoUrl() {
		$encoding = strtolower(Configure::read('App.encoding'));

		$this->Form->request['action'] = 'delete';
		$this->Form->request->here = '/articles/delete/10';
		$this->Form->request->base = '';
		$result = $this->Form->create($this->article);
		$expected = array(
			'form' => array(
				'method' => 'post', 'action' => '/articles/delete/10',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->article['defaults'] = ['id' => 1];
		$this->Form->request->here = '/articles/edit/1';
		$this->Form->request['action'] = 'delete';
		$result = $this->Form->create($this->article, ['action' => 'edit']);
		$expected = array(
			'form' => array(
				'method' => 'post',
				'action' => '/articles/edit/1',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'PUT'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->request['action'] = 'add';
		$result = $this->Form->create($this->article, ['url' => ['action' => 'publish']]);
		$expected = array(
			'form' => array(
				'method' => 'post',
				'action' => '/articles/publish/1',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'PUT'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->create($this->article, array('url' => '/articles/publish'));
		$expected = array(
			'form' => array('method' => 'post', 'action' => '/articles/publish', 'accept-charset' => $encoding),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'PUT'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->request['controller'] = 'pages';
		$result = $this->Form->create($this->article, array('action' => 'signup'));
		$expected = array(
			'form' => array(
				'method' => 'post', 'action' => '/pages/signup/1',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'PUT'),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test create() with a custom route
 *
 * @return void
 */
	public function testCreateCustomRoute() {
		Router::connect('/login', array('controller' => 'users', 'action' => 'login'));
		$encoding = strtolower(Configure::read('App.encoding'));

		$this->Form->request['controller'] = 'users';

		$result = $this->Form->create(false, array('action' => 'login'));
		$expected = array(
			'form' => array(
				'method' => 'post', 'action' => '/login',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test automatic accept-charset overriding
 *
 * @return void
 */
	public function testCreateWithAcceptCharset() {
		$result = $this->Form->create($this->article, array(
				'type' => 'post', 'action' => 'index', 'encoding' => 'iso-8859-1'
			)
		);
		$expected = array(
			'form' => array(
				'method' => 'post', 'action' => '/articles',
				'accept-charset' => 'iso-8859-1'
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test base form URL when url param is passed with multiple parameters (&)
 *
 */
	public function testCreateQuerystringrequest() {
		$encoding = strtolower(Configure::read('App.encoding'));
		$result = $this->Form->create($this->article, array(
			'type' => 'post',
			'escape' => false,
			'url' => array(
				'controller' => 'controller',
				'action' => 'action',
				'?' => array('param1' => 'value1', 'param2' => 'value2')
			)
		));
		$expected = array(
			'form' => array(
				'method' => 'post',
				'action' => '/controller/action?param1=value1&amp;param2=value2',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->create($this->article, array(
			'type' => 'post',
			'url' => array(
				'controller' => 'controller',
				'action' => 'action',
				'?' => array('param1' => 'value1', 'param2' => 'value2')
			)
		));
		$this->assertTags($result, $expected);
	}

/**
 * test that create() doesn't cause errors by multiple id's being in the primary key
 * as could happen with multiple select or checkboxes.
 *
 * @return void
 */
	public function testCreateWithMultipleIdInData() {
		$encoding = strtolower(Configure::read('App.encoding'));

		$this->Form->request->data['Article']['id'] = array(1, 2);
		$result = $this->Form->create($this->article);
		$expected = array(
			'form' => array(
				'method' => 'post',
				'action' => '/articles/add',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that create() doesn't add in extra passed params.
 *
 * @return void
 */
	public function testCreatePassedArgs() {
		$encoding = strtolower(Configure::read('App.encoding'));
		$this->Form->request->data['Article']['id'] = 1;
		$result = $this->Form->create($this->article, array(
			'type' => 'post',
			'escape' => false,
			'url' => array(
				'action' => 'edit',
				'myparam'
			)
		));
		$expected = array(
			'form' => array(
				'method' => 'post',
				'action' => '/articles/edit/myparam',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test creating a get form, and get form inputs.
 *
 * @return void
 */
	public function testGetFormCreate() {
		$encoding = strtolower(Configure::read('App.encoding'));
		$result = $this->Form->create($this->article, array('type' => 'get'));
		$this->assertTags($result, array('form' => array(
			'method' => 'get', 'action' => '/articles/add',
			'accept-charset' => $encoding
		)));

		$result = $this->Form->text('title');
		$this->assertTags($result, array('input' => array(
			'name' => 'title', 'type' => 'text', 'required' => 'required'
		)));

		$result = $this->Form->password('password');
		$this->assertTags($result, array('input' => array(
			'name' => 'password', 'type' => 'password'
		)));
		$this->assertNotRegExp('/<input[^<>]+[^id|name|type|value]=[^<>]*>$/', $result);

		$result = $this->Form->text('user_form');
		$this->assertTags($result, array('input' => array(
			'name' => 'user_form', 'type' => 'text'
		)));
	}

/**
 * test get form, and inputs when the model param is false
 *
 * @return void
 */
	public function testGetFormWithFalseModel() {
		$encoding = strtolower(Configure::read('App.encoding'));
		$this->Form->request['controller'] = 'contact_test';
		$result = $this->Form->create(false, array(
			'type' => 'get', 'url' => array('controller' => 'contact_test')
		));

		$expected = array('form' => array(
			'method' => 'get', 'action' => '/contact_test/add',
			'accept-charset' => $encoding
		));
		$this->assertTags($result, $expected);

		$result = $this->Form->text('reason');
		$expected = array(
			'input' => array('type' => 'text', 'name' => 'reason')
		);
		$this->assertTags($result, $expected);
	}

/**
 * testFormCreateWithSecurity method
 *
 * Test form->create() with security key.
 *
 * @return void
 */
	public function testCreateWithSecurity() {
		$this->Form->request->params['_csrfToken'] = 'testKey';
		$encoding = strtolower(Configure::read('App.encoding'));
		$result = $this->Form->create($this->article, [
			'url' => '/articles/publish',
		]);
		$expected = array(
			'form' => array('method' => 'post', 'action' => '/articles/publish', 'accept-charset' => $encoding),
			'div' => array('style' => 'display:none;'),
			array('input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST')),
			array('input' => array(
				'type' => 'hidden', 'name' => '_csrfToken', 'value' => 'testKey'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->create($this->article, ['url' => '/articles/publish', 'id' => 'MyForm']);
		$expected['form']['id'] = 'MyForm';
		$this->assertTags($result, $expected);
	}

/**
 * testFormCreateGetNoSecurity method
 *
 * Test form->create() with no security key as its a get form
 *
 * @return void
 */
	public function testCreateEndGetNoSecurity() {
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
	public function testCreateClearingFields() {
		$this->Form->fields = array('model_id');
		$this->Form->create($this->article);
		$this->assertEquals(array(), $this->Form->fields);
	}

/**
 * Tests form hash generation with model-less data
 *
 * @return void
 */
	public function testValidateHashNoModel() {
		$this->Form->request->params['_Token'] = 'foo';

		$result = $this->Form->secure(array('anything'));
		$this->assertRegExp('/540ac9c60d323c22bafe997b72c0790f39a8bdef/', $result);
	}

/**
 * Tests that hidden fields generated for checkboxes don't get locked
 *
 * @return void
 */
	public function testNoCheckboxLocking() {
		$this->Form->request->params['_Token'] = 'foo';
		$this->assertSame([], $this->Form->fields);

		$this->Form->checkbox('check', array('value' => '1'));
		$this->assertSame($this->Form->fields, array('check'));
	}

/**
 * testFormSecurityFields method
 *
 * Test generation of secure form hash generation.
 *
 * @return void
 */
	public function testFormSecurityFields() {
		$fields = array('Model.password', 'Model.username', 'Model.valid' => '0');

		$this->Form->request->params['_Token'] = 'testKey';
		$result = $this->Form->secure($fields);

		$hash = Security::hash(serialize($fields) . Configure::read('Security.salt'));
		$hash .= ':' . 'Model.valid';
		$hash = urlencode($hash);

		$expected = array(
			'div' => array('style' => 'display:none;'),
			array('input' => array(
				'type' => 'hidden',
				'name' => '_Token[fields]',
				'value' => $hash
			)),
			array('input' => array(
				'type' => 'hidden',
				'name' => '_Token[unlocked]',
				'value' => '',
			)),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Tests correct generation of number fields for double and float fields
 *
 * @return void
 */
	public function testTextFieldGenerationForFloats() {
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
		$expected = array(
			'div' => array('class' => 'input number'),
			'label' => array('for' => 'foo'),
			'Foo',
			'/label',
			array('input' => array(
				'type' => 'number',
				'name' => 'foo',
				'id' => 'foo',
				'step' => 'any'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('foo', array('step' => 0.5));
		$expected = array(
			'div' => array('class' => 'input number'),
			'label' => array('for' => 'foo'),
			'Foo',
			'/label',
			array('input' => array(
				'type' => 'number',
				'name' => 'foo',
				'id' => 'foo',
				'step' => '0.5'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Tests correct generation of number fields for integer fields
 *
 * @return void
 */
	public function testTextFieldTypeNumberGenerationForIntegers() {
		TableRegistry::get('Contacts', [
			'className' => __NAMESPACE__ . '\ContactsTable'
		]);
		$this->Form->create([], ['context' => ['table' => 'Contacts']]);
		$result = $this->Form->input('age');
		$expected = array(
			'div' => array('class' => 'input number'),
			'label' => array('for' => 'age'),
			'Age',
			'/label',
			array('input' => array(
				'type' => 'number', 'name' => 'age',
				'id' => 'age'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Tests correct generation of file upload fields for binary fields
 *
 * @return void
 */
	public function testFileUploadFieldTypeGenerationForBinaries() {
		$table = TableRegistry::get('Contacts', [
			'className' => __NAMESPACE__ . '\ContactsTable'
		]);
		$table->schema(array('foo' => array(
			'type' => 'binary',
			'null' => false,
			'default' => null,
			'length' => 1024
		)));
		$this->Form->create([], ['context' => ['table' => 'Contacts']]);

		$result = $this->Form->input('foo');
		$expected = array(
			'div' => array('class' => 'input file'),
			'label' => array('for' => 'foo'),
			'Foo',
			'/label',
			array('input' => array(
				'type' => 'file', 'name' => 'foo',
				'id' => 'foo'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testFormSecurityMultipleFields method
 *
 * Test secure() with multiple row form. Ensure hash is correct.
 *
 * @return void
 */
	public function testFormSecurityMultipleFields() {
		$this->Form->request->params['_Token'] = 'foo';

		$fields = array(
			'Model.0.password', 'Model.0.username', 'Model.0.hidden' => 'value',
			'Model.0.valid' => '0', 'Model.1.password', 'Model.1.username',
			'Model.1.hidden' => 'value', 'Model.1.valid' => '0'
		);
		$result = $this->Form->secure($fields);

		$hash = '51e3b55a6edd82020b3f29c9ae200e14bbeb7ee5%3AModel.0.hidden%7CModel.0.valid';
		$hash .= '%7CModel.1.hidden%7CModel.1.valid';

		$expected = array(
			'div' => array('style' => 'display:none;'),
			array('input' => array(
				'type' => 'hidden', 'name' => '_Token[fields]',
				'value' => $hash
			)),
			array('input' => array(
				'type' => 'hidden', 'name' => '_Token[unlocked]',
				'value' => ''
			)),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testFormSecurityMultipleSubmitButtons
 *
 * test form submit generation and ensure that _Token is only created on end()
 *
 * @return void
 */
	public function testFormSecurityMultipleSubmitButtons() {
		$this->Form->request->params['_Token'] = 'testKey';

		$this->Form->create($this->article);
		$this->Form->text('Address.title');
		$this->Form->text('Address.first_name');

		$result = $this->Form->submit('Save', array('name' => 'save'));
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'name' => 'save', 'value' => 'Save'),
			'/div',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('Cancel', array('name' => 'cancel'));
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'name' => 'cancel', 'value' => 'Cancel'),
			'/div',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->end();
		$expected = array(
			'div' => array('style' => 'display:none;'),
			array('input' => array(
				'type' => 'hidden',
				'name' => '_Token[fields]',
				'value'
			)),
			array('input' => array(
				'type' => 'hidden',
				'name' => '_Token[unlocked]',
				'value' => 'cancel%7Csave'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test that buttons created with foo[bar] name attributes are unlocked correctly.
 *
 * @return void
 */
	public function testSecurityButtonNestedNamed() {
		$key = 'testKey';
		$this->Form->request->params['_csrfToken'] = $key;

		$this->Form->create('Addresses');
		$this->Form->button('Test', array('type' => 'submit', 'name' => 'Address[button]'));
		$result = $this->Form->unlockField();
		$this->assertEquals(array('Address.button'), $result);
	}

/**
 * Test that submit inputs created with foo[bar] name attributes are unlocked correctly.
 *
 * @return void
 */
	public function testSecuritySubmitNestedNamed() {
		$this->Form->request->params['_Token'] = 'testKey';

		$this->Form->create($this->article);
		$this->Form->submit('Test', array('type' => 'submit', 'name' => 'Address[button]'));
		$result = $this->Form->unlockField();
		$this->assertEquals(array('Address.button'), $result);
	}

/**
 * Test that the correct fields are unlocked for image submits with no names.
 *
 * @return void
 */
	public function testSecuritySubmitImageNoName() {
		$key = 'testKey';
		$this->Form->request->params['_Token'] = 'testKey';

		$this->Form->create(false);
		$result = $this->Form->submit('save.png');
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'image', 'src' => 'img/save.png'),
			'/div'
		);
		$this->assertTags($result, $expected);
		$this->assertEquals(array('x', 'y'), $this->Form->unlockField());
	}

/**
 * Test that the correct fields are unlocked for image submits with names.
 *
 * @return void
 */
	public function testSecuritySubmitImageName() {
		$this->Form->request->params['_Token'] = 'testKey';

		$this->Form->create(null);
		$result = $this->Form->submit('save.png', array('name' => 'test'));
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'image', 'name' => 'test', 'src' => 'img/save.png'),
			'/div'
		);
		$this->assertTags($result, $expected);
		$this->assertEquals(array('test', 'test_x', 'test_y'), $this->Form->unlockField());
	}

/**
 * testFormSecurityMultipleInputFields method
 *
 * Test secure form creation with multiple row creation. Checks hidden, text, checkbox field types
 *
 * @return void
 */
	public function testFormSecurityMultipleInputFields() {
		$this->Form->request->params['_Token'] = 'testKey';
		$this->Form->create();

		$this->Form->hidden('Addresses.0.id', array('value' => '123456'));
		$this->Form->input('Addresses.0.title');
		$this->Form->input('Addresses.0.first_name');
		$this->Form->input('Addresses.0.last_name');
		$this->Form->input('Addresses.0.address');
		$this->Form->input('Addresses.0.city');
		$this->Form->input('Addresses.0.phone');
		$this->Form->input('Addresses.0.primary', array('type' => 'checkbox'));

		$this->Form->hidden('Addresses.1.id', array('value' => '654321'));
		$this->Form->input('Addresses.1.title');
		$this->Form->input('Addresses.1.first_name');
		$this->Form->input('Addresses.1.last_name');
		$this->Form->input('Addresses.1.address');
		$this->Form->input('Addresses.1.city');
		$this->Form->input('Addresses.1.phone');
		$this->Form->input('Addresses.1.primary', array('type' => 'checkbox'));

		$result = $this->Form->secure($this->Form->fields);

		$hash = '8bd3911b07b507408b1a969b31ee90c47b7d387e%3AAddresses.0.id%7CAddresses.1.id';

		$expected = array(
			'div' => array('style' => 'display:none;'),
			array('input' => array(
				'type' => 'hidden', 'name' => '_Token[fields]',
				'value' => $hash
			)),
			array('input' => array(
				'type' => 'hidden', 'name' => '_Token[unlocked]',
				'value' => ''
			)),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test form security with Model.field.0 style inputs
 *
 * @return void
 */
	public function testFormSecurityArrayFields() {
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
 * test secure form generation with multiple records and disabled fields.
 *
 * @return void
 */
	public function testFormSecurityMultipleInputDisabledFields() {
		$this->Form->request->params['_Token'] = array(
			'unlockedFields' => array('first_name', 'address')
		);
		$this->Form->create();

		$this->Form->hidden('Addresses.0.id', array('value' => '123456'));
		$this->Form->text('Addresses.0.title');
		$this->Form->text('Addresses.0.first_name');
		$this->Form->text('Addresses.0.last_name');
		$this->Form->text('Addresses.0.address');
		$this->Form->text('Addresses.0.city');
		$this->Form->text('Addresses.0.phone');
		$this->Form->hidden('Addresses.1.id', array('value' => '654321'));
		$this->Form->text('Addresses.1.title');
		$this->Form->text('Addresses.1.first_name');
		$this->Form->text('Addresses.1.last_name');
		$this->Form->text('Addresses.1.address');
		$this->Form->text('Addresses.1.city');
		$this->Form->text('Addresses.1.phone');

		$result = $this->Form->secure($this->Form->fields);
		$hash = '4fb10b46873df4ddd4ef5c3a19944a2f29b38991%3AAddresses.0.id%7CAddresses.1.id';

		$expected = array(
			'div' => array('style' => 'display:none;'),
			array('input' => array(
				'type' => 'hidden',
				'name' => '_Token[fields]',
				'value' => $hash
			)),
			array('input' => array(
				'type' => 'hidden',
				'name' => '_Token[unlocked]',
				'value' => 'address%7Cfirst_name',
			)),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testFormSecurityInputDisabledFields method
 *
 * Test single record form with disabled fields.
 *
 * @return void
 */
	public function testFormSecurityInputUnlockedFields() {
		$this->Form->request['_Token'] = array(
			'unlockedFields' => array('first_name', 'address')
		);
		$this->Form->create();
		$this->assertEquals($this->Form->request['_Token']['unlockedFields'], $this->Form->unlockField());

		$this->Form->hidden('Addresses.id', array('value' => '123456'));
		$this->Form->text('Addresses.title');
		$this->Form->text('Addresses.first_name');
		$this->Form->text('Addresses.last_name');
		$this->Form->text('Addresses.address');
		$this->Form->text('Addresses.city');
		$this->Form->text('Addresses.phone');

		$result = $this->Form->fields;
		$expected = array(
			'Addresses.id' => '123456', 'Addresses.title', 'Addresses.last_name',
			'Addresses.city', 'Addresses.phone'
		);
		$this->assertEquals($expected, $result);

		$result = $this->Form->secure($expected, ['data-foo' => 'bar']);

		$hash = 'a303becbdd99cb42ca14a1cf7e63dfd48696a3c5%3AAddresses.id';
		$expected = array(
			'div' => array('style' => 'display:none;'),
			array('input' => array(
				'type' => 'hidden',
				'name' => '_Token[fields]',
				'value' => $hash,
				'data-foo' => 'bar',
			)),
			array('input' => array(
				'type' => 'hidden',
				'name' => '_Token[unlocked]',
				'value' => 'address%7Cfirst_name',
				'data-foo' => 'bar',
			)),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test securing inputs with custom name attributes.
 *
 * @return void
 */
	public function testFormSecureWithCustomNameAttribute() {
		$this->Form->request->params['_Token'] = 'testKey';

		$this->Form->text('UserForm.published', array('name' => 'User[custom]'));
		$this->assertEquals('User.custom', $this->Form->fields[0]);

		$this->Form->text('UserForm.published', array('name' => 'User[custom][another][value]'));
		$this->assertEquals('User.custom.another.value', $this->Form->fields[1]);
	}

/**
 * testFormSecuredInput method
 *
 * Test generation of entire secure form, assertions made on input() output.
 *
 * @return void
 */
	public function testFormSecuredInput() {
		$this->Form->request->params['_csrfToken'] = 'testKey';
		$this->Form->request->params['_Token'] = 'stuff';
		$this->article['schema'] = [
			'ratio' => ['type' => 'decimal', 'length' => 5, 'precision' => 6],
			'population' => ['type' => 'decimal', 'length' => 15, 'precision' => 0],
		];

		$result = $this->Form->create($this->article, array('url' => '/articles/add'));
		$encoding = strtolower(Configure::read('App.encoding'));
		$expected = array(
			'form' => array('method' => 'post', 'action' => '/articles/add', 'accept-charset' => $encoding),
			'div' => array('style' => 'display:none;'),
			array('input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST')),
			array('input' => array(
				'type' => 'hidden',
				'name' => '_csrfToken',
				'value' => 'testKey'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('ratio');
		$expected = array(
			'div' => array('class'),
			'label' => array('for'),
			'Ratio',
			'/label',
			'input' => array('name', 'type' => 'number', 'step' => '0.000001', 'id'),
			'/div',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('population');
		$expected = array(
			'div' => array('class'),
			'label' => array('for'),
			'Population',
			'/label',
			'input' => array('name', 'type' => 'number', 'step' => '1', 'id'),
			'/div',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('published', array('type' => 'text'));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'published'),
			'Published',
			'/label',
			array('input' => array(
				'type' => 'text',
				'name' => 'published',
				'id' => 'published'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('other', array('type' => 'text'));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'other'),
			'Other',
			'/label',
			array('input' => array(
				'type' => 'text',
				'name' => 'other',
				'id',
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->hidden('stuff');
		$expected = array(
			'input' => array(
				'type' => 'hidden',
				'name' => 'stuff',
		));
		$this->assertTags($result, $expected);

		$result = $this->Form->hidden('hidden', array('value' => '0'));
		$expected = array('input' => array(
			'type' => 'hidden',
			'name' => 'hidden',
			'value' => '0'
		));
		$this->assertTags($result, $expected);

		$result = $this->Form->input('something', array('type' => 'checkbox'));
		$expected = array(
			'div' => array('class' => 'input checkbox'),
			array('input' => array(
				'type' => 'hidden',
				'name' => 'something',
				'value' => '0'
			)),
			array('input' => array(
				'type' => 'checkbox',
				'name' => 'something',
				'value' => '1',
				'id' => 'something'
			)),
			'label' => array('for' => 'something'),
			'Something',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->fields;
		$expected = array(
			'ratio', 'population', 'published', 'other',
			'stuff' => '',
			'hidden' => '0',
			'something'
		);
		$this->assertEquals($expected, $result);

		$result = $this->Form->secure($this->Form->fields);
		$expected = array(
			'div' => array('style' => 'display:none;'),
			array('input' => array(
				'type' => 'hidden',
				'name' => '_Token[fields]',
				'value'
			)),
			array('input' => array(
				'type' => 'hidden',
				'name' => '_Token[unlocked]',
				'value' => ''
			)),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test secured inputs with custom names.
 *
 * @return void
 */
	public function testSecuredInputCustomName() {
		$this->Form->request->params['_Token'] = 'testKey';
		$this->assertEquals(array(), $this->Form->fields);

		$this->Form->text('text_input', array(
			'name' => 'Option[General.default_role]',
		));
		$expected = array('Option.General.default_role');
		$this->assertEquals($expected, $this->Form->fields);

		$this->Form->select('select_box', [1, 2], [
			'name' => 'Option[General.select_role]',
		]);
		$expected = ['Option.General.default_role', 'Option.General.select_role'];
		$this->assertEquals($expected, $this->Form->fields);
	}

/**
 * Tests that the correct keys are added to the field hash index
 *
 * @return void
 */
	public function testFormSecuredFileInput() {
		$this->assertEquals(array(), $this->Form->fields);

		$this->Form->file('Attachment.file');
		$expected = array(
			'Attachment.file.name', 'Attachment.file.type',
			'Attachment.file.tmp_name', 'Attachment.file.error',
			'Attachment.file.size'
		);
		$this->assertEquals($expected, $this->Form->fields);
	}

/**
 * test that multiple selects keys are added to field hash
 *
 * @return void
 */
	public function testFormSecuredMultipleSelect() {
		$this->Form->request->params['_csrfToken'] = 'testKey';
		$this->assertEquals(array(), $this->Form->fields);
		$options = array('1' => 'one', '2' => 'two');

		$this->Form->select('Model.select', $options);
		$expected = array('Model.select');
		$this->assertEquals($expected, $this->Form->fields);

		$this->Form->fields = array();
		$this->Form->select('Model.select', $options, array('multiple' => true));
		$this->assertEquals($expected, $this->Form->fields);
	}

/**
 * testFormSecuredRadio method
 *
 * @return void
 */
	public function testFormSecuredRadio() {
		$this->Form->request->params['_Token'] = 'testKey';

		$this->assertEquals(array(), $this->Form->fields);
		$options = array('1' => 'option1', '2' => 'option2');

		$this->Form->radio('Test.test', $options);
		$expected = array('Test.test');
		$this->assertEquals($expected, $this->Form->fields);
	}

/**
 * Test that when disabled is in a list based attribute array it works.
 *
 * @return void
 */
	public function testFormSecuredAndDisabledNotAssoc() {
		$this->Form->request->params['_Token'] = 'testKey';

		$this->Form->select('Model.select', array(1, 2), array('disabled'));
		$this->Form->checkbox('Model.checkbox', array('disabled'));
		$this->Form->text('Model.text', array('disabled'));
		$this->Form->textarea('Model.textarea', array('disabled'));
		$this->Form->password('Model.password', array('disabled'));
		$this->Form->radio('Model.radio', array(1, 2), array('disabled'));

		$expected = array(
			'Model.radio' => ''
		);
		$this->assertEquals($expected, $this->Form->fields);
	}

/**
 * test that forms with disabled inputs + secured forms leave off the inputs from the form
 * hashing.
 *
 * @return void
 */
	public function testFormSecuredAndDisabled() {
		$this->Form->request->params['_Token'] = 'testKey';

		$this->Form->checkbox('Model.checkbox', array('disabled' => true));
		$this->Form->text('Model.text', array('disabled' => true));
		$this->Form->password('Model.text', array('disabled' => true));
		$this->Form->textarea('Model.textarea', array('disabled' => true));
		$this->Form->select('Model.select', array(1, 2), array('disabled' => true));
		$this->Form->radio('Model.radio', array(1, 2), array('disabled' => array(1, 2)));
		$this->Form->year('Model.year', array('disabled' => true));
		$this->Form->month('Model.month', array('disabled' => true));
		$this->Form->day('Model.day', array('disabled' => true));
		$this->Form->hour('Model.hour', array('disabled' => true));
		$this->Form->minute('Model.minute', array('disabled' => true));
		$this->Form->meridian('Model.meridian', array('disabled' => true));

		$expected = array(
			'Model.radio' => ''
		);
		$this->assertEquals($expected, $this->Form->fields);
	}

/**
 * testDisableSecurityUsingForm method
 *
 * @return void
 */
	public function testDisableSecurityUsingForm() {
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
 * test disableField
 *
 * @return void
 */
	public function testUnlockFieldAddsToList() {
		$this->Form->request['_Token'] = array(
			'unlockedFields' => array()
		);
		$this->Form->unlockField('Contact.name');
		$this->Form->text('Contact.name');

		$this->assertEquals(array('Contact.name'), $this->Form->unlockField());
		$this->assertEquals(array(), $this->Form->fields);
	}

/**
 * test unlockField removing from fields array.
 *
 * @return void
 */
	public function testUnlockFieldRemovingFromFields() {
		$this->Form->request['_Token'] = array(
			'unlockedFields' => array()
		);
		$this->Form->create($this->article);
		$this->Form->hidden('Article.id', array('value' => 1));
		$this->Form->text('Article.title');

		$this->assertEquals(1, $this->Form->fields['Article.id'], 'Hidden input should be secured.');
		$this->assertTrue(in_array('Article.title', $this->Form->fields), 'Field should be secured.');

		$this->Form->unlockField('Article.title');
		$this->Form->unlockField('Article.id');
		$this->assertEquals(array(), $this->Form->fields);
	}

/**
 * test error message display
 *
 * @return void
 */
	public function testErrorMessageDisplay() {
		$this->article['errors'] = [
			'Article' => ['title' => 'error message']
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
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Article.title', [
			'templates' => [
				'groupContainerError' => '<div class="input {{type}}{{required}} error">{{content}}</div>'
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
		$this->assertTags($result, $expected);
	}

/**
 * Test validation errors, when validation message is an empty string.
 *
 * @return void
 */
	public function testEmptyErrorValidation() {
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
		$this->assertTags($result, $expected);
	}

/**
 * Test validation errors, when calling input() overriding validation message by an empty string.
 *
 * @return void
 */
	public function testEmptyInputErrorValidation() {
		$this->article['errors'] = [
			'Article' => ['title' => 'error message']
		];
		$this->Form->create($this->article);

		$result = $this->Form->input('Article.title', array('error' => ''));
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
		$this->assertTags($result, $expected);
	}

/**
 * Tests displaying errors for nested entities
 *
 * @return void
 */
	public function testFormValidationAssociated() {
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
 * test form error display with associated model.
 *
 * @return void
 */
	public function testFormValidationAssociatedSecondLevel() {
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
 * test form error display with multiple records.
 *
 * @return void
 */
	public function testFormValidationMultiRecord() {
		$one = new Entity;
		$two = new Entity;
		TableRegistry::get('Contacts', [
			'className' => __NAMESPACE__ . '\ContactsTable'
		]);
		$one->errors('email', ['invalid email']);
		$two->errors('name', ['This is wrong']);
		$this->Form->create([$one, $two], ['context' => ['table' => 'Contacts']]);

		$result = $this->Form->input('Contacts.0.email');
		$expected = array(
			'div' => array('class' => 'input email error'),
			'label' => array('for' => 'contacts-0-email'),
			'Email',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contacts[0][email]', 'id' => 'contacts-0-email',
				'class' => 'form-error', 'maxlength' => 255
			),
			array('div' => array('class' => 'error-message')),
			'invalid email',
			'/div',
			'/div'
		);

		$result = $this->Form->input('Contacts.1.name');
		$expected = array(
			'div' => array('class' => 'input text error'),
			'label' => array('for' => 'contacts-1-name'),
			'Name',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contacts[1][name]', 'id' => 'contacts-1-name',
				'class' => 'form-error', 'maxlength' => 255
			),
			array('div' => array('class' => 'error-message')),
			'This is wrong',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testInput method
 *
 * Test various incarnations of input().
 *
 * @return void
 */
	public function testInput() {
		TableRegistry::get('ValidateUsers', [
			'className' => __NAMESPACE__ . '\ValidateUsersTable'
		]);
		$this->Form->create([], ['context' => ['table' => 'ValidateUsers']]);
		$result = $this->Form->input('ValidateUsers.balance');
		$expected = array(
			'div' => array('class'),
			'label' => array('for'),
			'Balance',
			'/label',
			'input' => array('name', 'type' => 'number', 'id', 'step'),
			'/div',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('ValidateUser.cost_decimal');
		$expected = array(
			'div' => array('class'),
			'label' => array('for'),
			'Cost Decimal',
			'/label',
			'input' => array('name', 'type' => 'number', 'step' => '0.001', 'id'),
			'/div',
		);
		$this->assertTags($result, $expected);
	}

/**
 * Tests the input method and passing custom options
 *
 * @return void
 */
	public function testInputCustomization() {
		TableRegistry::get('Contacts', [
			'className' => __NAMESPACE__ . '\ContactsTable'
		]);
		$this->Form->create([], ['context' => ['table' => 'Contacts']]);
		$result = $this->Form->input('Contact.email', array('id' => 'custom'));
		$expected = array(
			'div' => array('class' => 'input email'),
			'label' => array('for' => 'custom'),
			'Email',
			'/label',
			array('input' => array(
				'type' => 'email', 'name' => 'Contact[email]',
				'id' => 'custom', 'maxlength' => 255
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.email', array(
			'templates' => ['groupContainer' => '<div>{{content}}</div>']
		));
		$expected = array(
			'<div',
			'label' => array('for' => 'contact-email'),
			'Email',
			'/label',
			array('input' => array(
				'type' => 'email', 'name' => 'Contact[email]',
				'id' => 'contact-email', 'maxlength' => 255
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.email', array('type' => 'text'));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'contact-email'),
			'Email',
			'/label',
			array('input' => array(
				'type' => 'text', 'name' => 'Contact[email]',
				'id' => 'contact-email', 'maxlength' => '255'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.5.email', array('type' => 'text'));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'contact-5-email'),
			'Email',
			'/label',
			array('input' => array(
				'type' => 'text', 'name' => 'Contact[5][email]',
				'id' => 'contact-5-email', 'maxlength' => '255'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.password');
		$expected = array(
			'div' => array('class' => 'input password'),
			'label' => array('for' => 'contact-password'),
			'Password',
			'/label',
			array('input' => array(
				'type' => 'password', 'name' => 'Contact[password]',
				'id' => 'contact-password'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.email', array(
			'type' => 'file', 'class' => 'textbox'
		));
		$expected = array(
			'div' => array('class' => 'input file'),
			'label' => array('for' => 'contact-email'),
			'Email',
			'/label',
			array('input' => array(
				'type' => 'file', 'name' => 'Contact[email]', 'class' => 'textbox',
				'id' => 'contact-email'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$entity = new Entity(['phone' => 'Hello & World > weird chars']);
		$this->Form->create($entity, ['context' => ['table' => 'Contacts']]);
		$result = $this->Form->input('Contact.phone');
		$expected = array(
			'div' => array('class' => 'input tel'),
			'label' => array('for' => 'contact-phone'),
			'Phone',
			'/label',
			array('input' => array(
				'type' => 'tel', 'name' => 'Contact[phone]',
				'value' => 'Hello &amp; World &gt; weird chars',
				'id' => 'contact-phone', 'maxlength' => 255
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Model']['0']['OtherModel']['field'] = 'My value';
		$this->Form->create();
		$result = $this->Form->input('Model.0.OtherModel.field', array('id' => 'myId'));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'myId'),
			'Field',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Model[0][OtherModel][field]',
				'value' => 'My value', 'id' => 'myId'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data = [];

		$entity->errors('field', 'Badness!');
		$this->Form->create($entity, ['context' => ['table' => 'Contacts']]);
		$result = $this->Form->input('Contact.field');
		$expected = array(
			'div' => array('class' => 'input text error'),
			'label' => array('for' => 'contact-field'),
			'Field',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[field]',
				'id' => 'contact-field', 'class' => 'form-error'
			),
			array('div' => array('class' => 'error-message')),
			'Badness!',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.field', array(
			'templates' => [
				'groupContainerError' => '{{content}}{{error}}',
				'error' => '<span class="error-message">{{content}}</span>'
			]
		));
		$expected = array(
			'label' => array('for' => 'contact-field'),
			'Field',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[field]',
				'id' => 'contact-field', 'class' => 'form-error'
			),
			array('span' => array('class' => 'error-message')),
			'Badness!',
			'/span'
		);
		$this->assertTags($result, $expected);

		$entity->errors('field', ['minLength']);
		$result = $this->Form->input('Contact.field', array(
			'error' => array(
				'minLength' => 'Le login doit contenir au moins 2 caractères',
				'maxLength' => 'login too large'
			)
		));
		$expected = array(
			'div' => array('class' => 'input text error'),
			'label' => array('for' => 'contact-field'),
			'Field',
			'/label',
			'input' => array('type' => 'text', 'name' => 'Contact[field]', 'id' => 'contact-field', 'class' => 'form-error'),
			array('div' => array('class' => 'error-message')),
			'Le login doit contenir au moins 2 caractères',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);

		$entity->errors('field', ['maxLength']);
		$result = $this->Form->input('Contact.field', array(
			'error' => array(
				'minLength' => 'Le login doit contenir au moins 2 caractères',
				'maxLength' => 'login too large',
			)
		));
		$expected = array(
			'div' => array('class' => 'input text error'),
			'label' => array('for' => 'contact-field'),
			'Field',
			'/label',
			'input' => array('type' => 'text', 'name' => 'Contact[field]', 'id' => 'contact-field', 'class' => 'form-error'),
			array('div' => array('class' => 'error-message')),
			'login too large',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test id prefix
 *
 * @return void
 */
	public function testCreateIdPrefix() {
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
		$this->assertTags($result, $expected);

		$result = $this->Form->input('field', ['id' => 'custom-id']);
		$expected = [
			'div' => ['class' => 'input text'],
			'label' => ['for' => 'custom-id'],
			'Field',
			'/label',
			'input' => ['type' => 'text', 'name' => 'field', 'id' => 'custom-id'],
			'/div'
		];
		$this->assertTags($result, $expected);

		$result = $this->Form->radio('Model.field', ['option A']);
		$expected = [
			'input' => ['type' => 'hidden', 'name' => 'Model[field]', 'value' => ''],
			['input' => [
				'type' => 'radio',
				'name' => 'Model[field]',
				'value' => '0',
				'id' => 'prefix-model-field-0'
			]],
			'label' => ['for' => 'prefix-model-field-0'],
			'option A',
			'/label'
		];
		$this->assertTags($result, $expected);

		$result = $this->Form->radio('Model.field', ['option A', 'option']);
		$expected = [
			'input' => ['type' => 'hidden', 'name' => 'Model[field]', 'value' => ''],
			['input' => [
				'type' => 'radio',
				'name' => 'Model[field]',
				'value' => '0',
				'id' => 'prefix-model-field-0'
			]],
			'label' => ['for' => 'prefix-model-field-0'],
			'option A',
			'/label'
		];
		$this->assertTags($result, $expected);

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
			['input' => [
				'type' => 'checkbox', 'name' => 'Model[multi_field][]',
				'value' => '0', 'id' => 'prefix-model-multi-field-0'
			]],
			['label' => ['for' => 'prefix-model-multi-field-0']],
			'first',
			'/label',
			'/div',
		];
		$this->assertTags($result, $expected);

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
		$this->assertTags($result, $expected);
	}

/**
 * Test that inputs with 0 can be created.
 *
 * @return void
 */
	public function testInputZero() {
		TableRegistry::get('Contacts', [
			'className' => __NAMESPACE__ . '\ContactsTable'
		]);
		$this->Form->create([], ['context' => ['table' => 'Contacts']]);
		$result = $this->Form->input('0');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => '0'), '/label',
			'input' => array('type' => 'text', 'name' => '0', 'id' => '0'),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test input() with checkbox creation
 *
 * @return void
 */
	public function testInputCheckbox() {
		$result = $this->Form->input('User.active', array('label' => false, 'checked' => true));
		$expected = array(
			'div' => array('class' => 'input checkbox'),
			'input' => array('type' => 'hidden', 'name' => 'User[active]', 'value' => '0'),
			array('input' => array('type' => 'checkbox', 'name' => 'User[active]', 'value' => '1', 'id' => 'user-active', 'checked' => 'checked')),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('User.active', array('label' => false, 'checked' => 1));
		$expected = array(
			'div' => array('class' => 'input checkbox'),
			'input' => array('type' => 'hidden', 'name' => 'User[active]', 'value' => '0'),
			array('input' => array('type' => 'checkbox', 'name' => 'User[active]', 'value' => '1', 'id' => 'user-active', 'checked' => 'checked')),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('User.active', array('label' => false, 'checked' => '1'));
		$expected = array(
			'div' => array('class' => 'input checkbox'),
			'input' => array('type' => 'hidden', 'name' => 'User[active]', 'value' => '0'),
			array('input' => array('type' => 'checkbox', 'name' => 'User[active]', 'value' => '1', 'id' => 'user-active', 'checked' => 'checked')),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('User.disabled', array(
			'label' => 'Disabled',
			'type' => 'checkbox',
			'data-foo' => 'disabled'
		));
		$expected = array(
			'div' => array('class' => 'input checkbox'),
			'input' => array('type' => 'hidden', 'name' => 'User[disabled]', 'value' => '0'),
			array('input' => array(
				'type' => 'checkbox',
				'name' => 'User[disabled]',
				'value' => '1',
				'id' => 'user-disabled',
				'data-foo' => 'disabled'
			)),
			'label' => array('for' => 'user-disabled'),
			'Disabled',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test that input() does not create wrapping div and label tag for hidden fields
 *
 * @return void
 */
	public function testInputHidden() {
		TableRegistry::get('ValidateUsers', [
			'className' => __NAMESPACE__ . '\ValidateUsersTable'
		]);
		$this->Form->create([], ['context' => ['table' => 'ValidateUsers']]);

		$result = $this->Form->input('ValidateUser.id');
		$expected = array(
			'input' => array('name', 'type' => 'hidden', 'id')
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('ValidateUser.custom', ['type' => 'hidden']);
		$expected = array(
			'input' => array('name', 'type' => 'hidden', 'id')
		);
		$this->assertTags($result, $expected);
	}

/**
 * test form->input() with datetime
 *
 * @return void
 */
	public function testInputDatetime() {
		$this->Form = $this->getMock(
			'Cake\View\Helper\FormHelper',
			['datetime'],
			[new View()]
		);
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
			])
			->will($this->returnValue('This is it!'));
		$result = $this->Form->input('prueba', array(
			'type' => 'datetime', 'timeFormat' => 24, 'minYear' => 2008,
			'maxYear' => 2011, 'interval' => 15
		));
		$expected = array(
			'div' => array('class' => 'input datetime'),
			'label' => array('for' => 'prueba'),
			'Prueba',
			'/label',
			'This is it!',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test form->input() with datetime with id prefix
 *
 * @return void
 */
	public function testInputDatetimeIdPrefix() {
		$this->Form = $this->getMock(
			'Cake\View\Helper\FormHelper',
			['datetime'],
			[new View()]
		);

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
			])
			->will($this->returnValue('This is it!'));
		$result = $this->Form->input('prueba', array(
			'type' => 'datetime', 'timeFormat' => 24, 'minYear' => 2008,
			'maxYear' => 2011, 'interval' => 15
		));
		$expected = array(
			'div' => array('class' => 'input datetime'),
			'label' => array('for' => 'prefix-prueba'),
			'Prueba',
			'/label',
			'This is it!',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test generating checkboxes with disabled elements.
 *
 * @return void
 */
	public function testInputCheckboxWithDisabledElements() {
		$options = array(1 => 'One', 2 => 'Two', '3' => 'Three');
		$result = $this->Form->input('Contact.multiple', array(
			'multiple' => 'checkbox',
			'disabled' => 'disabled',
			'options' => $options
		));

		$expected = array(
			array('div' => array('class' => 'input select')),
			array('label' => array('for' => "contact-multiple")),
			'Multiple',
			'/label',
			array('input' => array('type' => 'hidden', 'name' => "Contact[multiple]", 'value' => '')),
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => "Contact[multiple][]", 'value' => 1, 'disabled' => 'disabled', 'id' => "contact-multiple-1")),
			array('label' => array('for' => "contact-multiple-1")),
			'One',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => "Contact[multiple][]", 'value' => 2, 'disabled' => 'disabled', 'id' => "contact-multiple-2")),
			array('label' => array('for' => "contact-multiple-2")),
			'Two',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => "Contact[multiple][]", 'value' => 3, 'disabled' => 'disabled', 'id' => "contact-multiple-3")),
			array('label' => array('for' => "contact-multiple-3")),
			'Three',
			'/label',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);

		// make sure 50 does only disable 50, and not 50f5c0cf
		$options = array('50' => 'Fifty', '50f5c0cf' => 'Stringy');
		$disabled = array(50);

		$expected = array(
			array('div' => array('class' => 'input select')),
			array('label' => array('for' => "contact-multiple")),
			'Multiple',
			'/label',
			array('input' => array('type' => 'hidden', 'name' => "Contact[multiple]", 'value' => '')),
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => "Contact[multiple][]", 'value' => 50, 'disabled' => 'disabled', 'id' => "contact-multiple-50")),
			array('label' => array('for' => "contact-multiple-50")),
			'Fifty',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => "Contact[multiple][]", 'value' => '50f5c0cf', 'id' => "contact-multiple-50f5c0cf")),
			array('label' => array('for' => "contact-multiple-50f5c0cf")),
			'Stringy',
			'/label',
			'/div',
			'/div'
		);
		$result = $this->Form->input('Contact.multiple', array('multiple' => 'checkbox', 'disabled' => $disabled, 'options' => $options));
		$this->assertTags($result, $expected);
	}

/**
 * test input name with leading integer, ensure attributes are generated correctly.
 *
 * @return void
 */
	public function testInputWithLeadingInteger() {
		$result = $this->Form->text('0.Node.title');
		$expected = array(
			'input' => array('name' => '0[Node][title]', 'type' => 'text')
		);
		$this->assertTags($result, $expected);
	}

/**
 * test form->input() with select type inputs.
 *
 * @return void
 */
	public function testInputSelectType() {
		$result = $this->Form->input('email', array(
			'options' => array('è' => 'Firést', 'é' => 'Secoènd'), 'empty' => true)
		);
		$expected = array(
			'div' => array('class' => 'input select'),
			'label' => array('for' => 'email'),
			'Email',
			'/label',
			array('select' => array('name' => 'email', 'id' => 'email')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => 'è')),
			'Firést',
			'/option',
			array('option' => array('value' => 'é')),
			'Secoènd',
			'/option',
			'/select',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('email', array(
			'options' => array('First', 'Second'), 'empty' => true)
		);
		$expected = array(
			'div' => array('class' => 'input select'),
			'label' => array('for' => 'email'),
			'Email',
			'/label',
			array('select' => array('name' => 'email', 'id' => 'email')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '0')),
			'First',
			'/option',
			array('option' => array('value' => '1')),
			'Second',
			'/option',
			'/select',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('email', [
			'type' => 'select',
			'options' => new \ArrayObject(['First', 'Second']),
			'empty' => true
		]);
		$this->assertTags($result, $expected);

		$this->View->viewVars['users'] = array('value' => 'good', 'other' => 'bad');
		$this->Form->request->data = array('Model' => array('user_id' => 'value'));

		$result = $this->Form->input('Model.user_id', array('empty' => true));
		$expected = array(
			'div' => array('class' => 'input select'),
			'label' => array('for' => 'model-user-id'),
			'User',
			'/label',
			'select' => array('name' => 'Model[user_id]', 'id' => 'model-user-id'),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => 'value', 'selected' => 'selected')),
			'good',
			'/option',
			array('option' => array('value' => 'other')),
			'bad',
			'/option',
			'/select',
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->View->viewVars['users'] = array('value' => 'good', 'other' => 'bad');
		$this->Form->request->data = array('Thing' => array('user_id' => null));
		$result = $this->Form->input('Thing.user_id', array('empty' => 'Some Empty'));
		$expected = array(
			'div' => array('class' => 'input select'),
			'label' => array('for' => 'thing-user-id'),
			'User',
			'/label',
			'select' => array('name' => 'Thing[user_id]', 'id' => 'thing-user-id'),
			array('option' => array('value' => '')),
			'Some Empty',
			'/option',
			array('option' => array('value' => 'value')),
			'good',
			'/option',
			array('option' => array('value' => 'other')),
			'bad',
			'/option',
			'/select',
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->View->viewVars['users'] = array('value' => 'good', 'other' => 'bad');
		$this->Form->request->data = array('Thing' => array('user_id' => 'value'));
		$result = $this->Form->input('Thing.user_id', array('empty' => 'Some Empty'));
		$expected = array(
			'div' => array('class' => 'input select'),
			'label' => array('for' => 'thing-user-id'),
			'User',
			'/label',
			'select' => array('name' => 'Thing[user_id]', 'id' => 'thing-user-id'),
			array('option' => array('value' => '')),
			'Some Empty',
			'/option',
			array('option' => array('value' => 'value', 'selected' => 'selected')),
			'good',
			'/option',
			array('option' => array('value' => 'other')),
			'bad',
			'/option',
			'/select',
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->data = array();
		$result = $this->Form->input('Publisher.id', array(
				'label' => 'Publisher',
				'type' => 'select',
				'multiple' => 'checkbox',
				'options' => array('Value 1' => 'Label 1', 'Value 2' => 'Label 2')
		));
		$expected = array(
			array('div' => array('class' => 'input select')),
				array('label' => array('for' => 'publisher-id')),
				'Publisher',
				'/label',
				'input' => array('type' => 'hidden', 'name' => 'Publisher[id]', 'value' => ''),
				array('div' => array('class' => 'checkbox')),
				array('input' => array('type' => 'checkbox', 'name' => 'Publisher[id][]', 'value' => 'Value 1', 'id' => 'publisher-id-value-1')),
				array('label' => array('for' => 'publisher-id-value-1')),
				'Label 1',
				'/label',
				'/div',
				array('div' => array('class' => 'checkbox')),
				array('input' => array('type' => 'checkbox', 'name' => 'Publisher[id][]', 'value' => 'Value 2', 'id' => 'publisher-id-value-2')),
				array('label' => array('for' => 'publisher-id-value-2')),
				'Label 2',
				'/label',
				'/div',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that input() and a non standard primary key makes a hidden input by default.
 *
 * @return void
 */
	public function testInputWithNonStandardPrimaryKeyMakesHidden() {
		$this->article['schema']['_constraints']['primary']['columns'] = ['title'];
		$this->Form->create($this->article);
		$result = $this->Form->input('title');
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'title', 'id' => 'title'),
		);
		$this->assertTags($result, $expected);

		$this->article['schema']['_constraints']['primary']['columns'] = ['title', 'body'];
		$this->Form->create($this->article);
		$result = $this->Form->input('title');
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'title', 'id' => 'title'),
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('body');
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'body', 'id' => 'body'),
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that overriding the magic select type widget is possible
 *
 * @return void
 */
	public function testInputOverridingMagicSelectType() {
		$this->View->viewVars['users'] = array('value' => 'good', 'other' => 'bad');
		$result = $this->Form->input('Model.user_id', array('type' => 'text'));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'model-user-id'), 'User', '/label',
			'input' => array('name' => 'Model[user_id]', 'type' => 'text', 'id' => 'model-user-id'),
			'/div'
		);
		$this->assertTags($result, $expected);

		//Check that magic types still work for plural/singular vars
		$this->View->viewVars['types'] = array('value' => 'good', 'other' => 'bad');
		$result = $this->Form->input('Model.type');
		$expected = array(
			'div' => array('class' => 'input select'),
			'label' => array('for' => 'model-type'), 'Type', '/label',
			'select' => array('name' => 'Model[type]', 'id' => 'model-type'),
			array('option' => array('value' => 'value')), 'good', '/option',
			array('option' => array('value' => 'other')), 'bad', '/option',
			'/select',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test that inferred types do not override developer input
 *
 * @return void
 */
	public function testInputMagicTypeDoesNotOverride() {
		$this->View->viewVars['users'] = array('value' => 'good', 'other' => 'bad');
		$result = $this->Form->input('Model.user', array('type' => 'checkbox'));
		$expected = array(
			'div' => array('class' => 'input checkbox'),
			array('input' => array(
				'type' => 'hidden',
				'name' => 'Model[user]',
				'value' => 0,
			)),
			array('input' => array(
				'name' => 'Model[user]',
				'type' => 'checkbox',
				'id' => 'model-user',
				'value' => 1
			)),
			'label' => array('for' => 'model-user'), 'User', '/label',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test that magic input() selects are created for type=number
 *
 * @return void
 */
	public function testInputMagicSelectForTypeNumber() {
		TableRegistry::get('ValidateUsers', [
			'className' => __NAMESPACE__ . '\ValidateUsersTable'
		]);
		$entity = new Entity(['balance' => 1]);
		$this->Form->create($entity, ['context' => ['table' => 'ValidateUsers']]);
		$this->View->viewVars['balances'] = array(0 => 'nothing', 1 => 'some', 100 => 'a lot');
		$result = $this->Form->input('ValidateUser.balance');
		$expected = array(
			'div' => array('class' => 'input select'),
			'label' => array('for' => 'validateuser-balance'),
			'Balance',
			'/label',
			'select' => array('name' => 'ValidateUser[balance]', 'id' => 'validateuser-balance'),
			array('option' => array('value' => '0')),
			'nothing',
			'/option',
			array('option' => array('value' => '1', 'selected' => 'selected')),
			'some',
			'/option',
			array('option' => array('value' => '100')),
			'a lot',
			'/option',
			'/select',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test that magic input() selects can easily be converted into radio types without error.
 *
 * @return void
 */
	public function testInputMagicSelectChangeToRadio() {
		$this->View->viewVars['users'] = array('value' => 'good', 'other' => 'bad');
		$result = $this->Form->input('Model.user_id', array('type' => 'radio'));
		$this->assertContains('input type="radio"', $result);
	}

/**
 * testFormInputs method
 *
 * test correct results from form::inputs().
 *
 * @return void
 */
	public function testFormInputsLegendFieldset() {
		$this->Form->create($this->article);
		$result = $this->Form->inputs([], array('legend' => 'The Legend'));
		$expected = array(
			'<fieldset',
			'<legend',
			'The Legend',
			'/legend',
			'*/fieldset',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->inputs([], array('fieldset' => true, 'legend' => 'Field of Dreams'));
		$this->assertContains('<legend>Field of Dreams</legend>', $result);
		$this->assertContains('<fieldset>', $result);

		$result = $this->Form->inputs([], array('fieldset' => false, 'legend' => false));
		$this->assertNotContains('<legend>', $result);
		$this->assertNotContains('<fieldset>', $result);

		$result = $this->Form->inputs([], array('fieldset' => false, 'legend' => 'Hello'));
		$this->assertNotContains('<legend>', $result);
		$this->assertNotContains('<fieldset>', $result);

		$this->Form->create($this->article);
		$this->Form->request->params['prefix'] = 'admin';
		$this->Form->request->params['action'] = 'admin_edit';
		$this->Form->request->params['controller'] = 'articles';
		$result = $this->Form->inputs();
		$expected = [
			'<fieldset',
			'<legend',
			'New Article',
			'/legend',
			'*/fieldset',
		];
		$this->assertTags($result, $expected);
	}

/**
 * Test the inputs() method.
 *
 * @return void
 */
	public function testFormInputs() {
		$this->Form->create($this->article);
		$result = $this->Form->inputs();
		$expected = array(
			'<fieldset',
			'<legend', 'New Article', '/legend',
			'input' => array('type' => 'hidden', 'name' => 'id', 'id' => 'id'),
			array('div' => array('class' => 'input select required')),
			'*/div',
			array('div' => array('class' => 'input text required')),
			'*/div',
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input text')),
			'*/div',
			'/fieldset',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->inputs([
			'published' => ['type' => 'boolean']
		]);
		$expected = array(
			'<fieldset',
			'<legend', 'New Article', '/legend',
			'input' => array('type' => 'hidden', 'name' => 'id', 'id' => 'id'),
			array('div' => array('class' => 'input select required')),
			'*/div',
			array('div' => array('class' => 'input text required')),
			'*/div',
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input boolean')),
			'*/div',
			'/fieldset',
		);
		$this->assertTags($result, $expected);

		$this->Form->create($this->article);
		$result = $this->Form->inputs([], ['legend' => 'Hello']);
		$expected = array(
			'fieldset' => array(),
			'legend' => array(),
			'Hello',
			'/legend',
			'input' => array('type' => 'hidden', 'name' => 'id', 'id' => 'id'),
			array('div' => array('class' => 'input select required')),
			'*/div',
			array('div' => array('class' => 'input text required')),
			'*/div',
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input text')),
			'*/div',
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$this->Form->create(false);
		$expected = array(
			'fieldset' => array(),
			array('div' => array('class' => 'input text')),
			'label' => array('for' => 'foo'),
			'Foo',
			'/label',
			'input' => array('type' => 'text', 'name' => 'foo', 'id' => 'foo'),
			'*/div',
			'/fieldset'
		);
		$result = $this->Form->inputs(
			array('foo' => array('type' => 'text')),
			array('legend' => false)
		);
		$this->assertTags($result, $expected);
	}

/**
 * testFormInputsBlacklist
 *
 * @return void
 */
	public function testFormInputsBlacklist() {
		$this->Form->create($this->article);
		$result = $this->Form->inputs([
			'id' => false
		]);
		$expected = array(
			'<fieldset',
			'<legend', 'New Article', '/legend',
			array('div' => array('class' => 'input select required')),
			'*/div',
			array('div' => array('class' => 'input text required')),
			'*/div',
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input text')),
			'*/div',
			'/fieldset',
		);
		$this->assertTags($result, $expected);

		$this->Form->create($this->article);
		$result = $this->Form->inputs([
			'id' => []
		]);
		$expected = array(
			'<fieldset',
			'<legend', 'New Article', '/legend',
			'input' => array('type' => 'hidden', 'name' => 'id', 'id' => 'id'),
			array('div' => array('class' => 'input select required')),
			'*/div',
			array('div' => array('class' => 'input text required')),
			'*/div',
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input text')),
			'*/div',
			'/fieldset',
		);
		$this->assertTags($result, $expected, 'A falsey value (array) should not remove the input');
	}

/**
 * testSelectAsCheckbox method
 *
 * test multi-select widget with checkbox formatting.
 *
 * @return void
 */
	public function testSelectAsCheckbox() {
		$result = $this->Form->select(
			'Model.multi_field',
			array('first', 'second', 'third'),
			array('multiple' => 'checkbox', 'value' => array(0, 1))
		);
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => ''),
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'Model[multi_field][]', 'checked' => 'checked', 'value' => '0', 'id' => 'model-multi-field-0')),
			array('label' => array('for' => 'model-multi-field-0', 'class' => 'selected')),
			'first',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'Model[multi_field][]', 'checked' => 'checked', 'value' => '1', 'id' => 'model-multi-field-1')),
			array('label' => array('for' => 'model-multi-field-1', 'class' => 'selected')),
			'second',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'Model[multi_field][]', 'value' => '2', 'id' => 'model-multi-field-2')),
			array('label' => array('for' => 'model-multi-field-2')),
			'third',
			'/label',
			'/div',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->select(
			'Model.multi_field',
			array('1/2' => 'half'),
			array('multiple' => 'checkbox')
		);
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => ''),
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'Model[multi_field][]', 'value' => '1/2', 'id' => 'model-multi-field-1-2')),
			array('label' => array('for' => 'model-multi-field-1-2')),
			'half',
			'/label',
			'/div',
		);
		$this->assertTags($result, $expected);
	}

/**
 * testLabel method
 *
 * test label generation.
 *
 * @return void
 */
	public function testLabel() {
		$result = $this->Form->label('Person.name');
		$this->assertTags($result, array('label' => array('for' => 'person-name'), 'Name', '/label'));

		$result = $this->Form->label('Person.name');
		$this->assertTags($result, array('label' => array('for' => 'person-name'), 'Name', '/label'));

		$result = $this->Form->label('Person.first_name');
		$this->assertTags($result, array('label' => array('for' => 'person-first-name'), 'First Name', '/label'));

		$result = $this->Form->label('Person.first_name', 'Your first name');
		$this->assertTags($result, array('label' => array('for' => 'person-first-name'), 'Your first name', '/label'));

		$result = $this->Form->label('Person.first_name', 'Your first name', array('class' => 'my-class'));
		$this->assertTags($result, array('label' => array('for' => 'person-first-name', 'class' => 'my-class'), 'Your first name', '/label'));

		$result = $this->Form->label('Person.first_name', 'Your first name', array('class' => 'my-class', 'id' => 'LabelID'));
		$this->assertTags($result, array('label' => array('for' => 'person-first-name', 'class' => 'my-class', 'id' => 'LabelID'), 'Your first name', '/label'));

		$result = $this->Form->label('Person.first_name', '');
		$this->assertTags($result, array('label' => array('for' => 'person-first-name'), '/label'));

		$result = $this->Form->label('Person.2.name', '');
		$this->assertTags($result, array('label' => array('for' => 'person-2-name'), '/label'));
	}

/**
 * testTextbox method
 *
 * test textbox element generation
 *
 * @return void
 */
	public function testTextbox() {
		$result = $this->Form->text('Model.field');
		$this->assertTags($result, array('input' => array('type' => 'text', 'name' => 'Model[field]')));

		$result = $this->Form->text('Model.field', array('type' => 'password'));
		$this->assertTags($result, array('input' => array('type' => 'password', 'name' => 'Model[field]')));

		$result = $this->Form->text('Model.field', array('id' => 'theID'));
		$this->assertTags($result, array('input' => array('type' => 'text', 'name' => 'Model[field]', 'id' => 'theID')));
	}

/**
 * Test that text() hooks up with request data and error fields.
 *
 * @return void
 */
	public function testTextBoxDataAndError() {
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
		$this->assertTags($result, $expected);

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
		$this->assertTags($result, $expected);
	}

/**
 * testDefaultValue method
 *
 * Test default value setting
 *
 * @return void
 */
	public function testTextDefaultValue() {
		$this->Form->request->data['Model']['field'] = 'test';
		$result = $this->Form->text('Model.field', array('default' => 'default value'));
		$expected = ['input' => ['type' => 'text', 'name' => 'Model[field]', 'value' => 'test']];
		$this->assertTags($result, $expected);

		unset($this->Form->request->data['Model']['field']);
		$result = $this->Form->text('Model.field', array('default' => 'default value'));
		$expected = ['input' => ['type' => 'text', 'name' => 'Model[field]', 'value' => 'default value']];
		$this->assertTags($result, $expected);
	}

/**
 * testError method
 *
 * Test field error generation
 *
 * @return void
 */
	public function testError() {
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
		$this->assertTags($result, $expected);

		$result = $this->Form->error('Article.field', "<strong>Badness!</strong>");
		$expected = [
			['div' => ['class' => 'error-message']],
			'&lt;strong&gt;Badness!&lt;/strong&gt;',
			'/div',
		];
		$this->assertTags($result, $expected);

		$result = $this->Form->error('Article.field', "<strong>Badness!</strong>", ['escape' => false]);
		$expected = [
			['div' => ['class' => 'error-message']],
			'<strong', 'Badness!', '/strong',
			'/div',
		];
		$this->assertTags($result, $expected);
	}

/**
 * Test error with nested lists.
 *
 * @return void
 */
	public function testErrorMessages() {
		$this->article['errors'] = [
			'Article' => ['field' => 'email']
		];
		$this->Form->create($this->article);

		$result = $this->Form->error('Article.field', array(
			'email' => 'No good!'
		));
		$expected = array(
			'div' => array('class' => 'error-message'),
			'No good!',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test error() with multiple messages.
 *
 * @return void
 */
	public function testErrorMultipleMessages() {
		$this->article['errors'] = [
			'field' => ['notEmpty', 'email', 'Something else']
		];
		$this->Form->create($this->article);

		$result = $this->Form->error('field', array(
			'notEmpty' => 'Cannot be empty',
			'email' => 'No good!'
		));
		$expected = array(
			'div' => array('class' => 'error-message'),
				'ul' => array(),
					'<li', 'Cannot be empty', '/li',
					'<li', 'No good!', '/li',
					'<li', 'Something else', '/li',
				'/ul',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testPassword method
 *
 * Test password element generation
 *
 * @return void
 */
	public function testPassword() {
		$this->article['errors'] = [
			'Contact' => [
				'passwd' => 1
			]
		];
		$this->Form->create($this->article);

		$result = $this->Form->password('Contact.field');
		$this->assertTags($result, array('input' => array('type' => 'password', 'name' => 'Contact[field]')));

		$this->Form->request->data['Contact']['passwd'] = 'test';
		$result = $this->Form->password('Contact.passwd', array('id' => 'theID'));
		$this->assertTags($result, array('input' => array('type' => 'password', 'name' => 'Contact[passwd]', 'value' => 'test', 'id' => 'theID', 'class' => 'form-error')));
	}

/**
 * testRadio method
 *
 * Test radio element set generation
 *
 * @return void
 */
	public function testRadio() {
		$result = $this->Form->radio('Model.field', array('option A'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'Model[field]', 'value' => ''),
			array('input' => array('type' => 'radio', 'name' => 'Model[field]', 'value' => '0', 'id' => 'model-field-0')),
			'label' => array('for' => 'model-field-0'),
			'option A',
			'/label'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->radio('Model.field', new Collection(['option A']));
		$this->assertTags($result, $expected);

		$result = $this->Form->radio('Model.field', array('option A', 'option B'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'Model[field]', 'value' => ''),
			array('input' => array('type' => 'radio', 'name' => 'Model[field]', 'value' => '0', 'id' => 'model-field-0')),
			array('label' => array('for' => 'model-field-0')),
			'option A',
			'/label',
			array('input' => array('type' => 'radio', 'name' => 'Model[field]', 'value' => '1', 'id' => 'model-field-1')),
			array('label' => array('for' => 'model-field-1')),
			'option B',
			'/label',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->radio(
			'Employee.gender',
			array('male' => 'Male', 'female' => 'Female'),
			['form' => 'my-form']
		);
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'Employee[gender]', 'value' => '', 'form' => 'my-form'),
			array('input' => array('type' => 'radio', 'name' => 'Employee[gender]', 'value' => 'male', 'id' => 'employee-gender-male', 'form' => 'my-form')),
			array('label' => array('for' => 'employee-gender-male')),
			'Male',
			'/label',
			array('input' => array('type' => 'radio', 'name' => 'Employee[gender]', 'value' => 'female', 'id' => 'employee-gender-female', 'form' => 'my-form')),
			array('label' => array('for' => 'employee-gender-female')),
			'Female',
			'/label',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->radio('Model.field', array('option A', 'option B'), array('name' => 'Model[custom]'));
		$expected = array(
			array('input' => array('type' => 'hidden', 'name' => 'Model[custom]', 'value' => '')),
			array('input' => array('type' => 'radio', 'name' => 'Model[custom]', 'value' => '0', 'id' => 'model-custom-0')),
			array('label' => array('for' => 'model-custom-0')),
			'option A',
			'/label',
			array('input' => array('type' => 'radio', 'name' => 'Model[custom]', 'value' => '1', 'id' => 'model-custom-1')),
			array('label' => array('for' => 'model-custom-1')),
			'option B',
			'/label',
		);
		$this->assertTags($result, $expected);
	}

/**
 * test generating radio input inside label ala twitter bootstrap
 *
 * @return void
 */
	public function testRadioInputInsideLabel() {
		$this->Form->templates([
			'label' => '<label{{attrs}}>{{input}}{{text}}</label>',
			'radioContainer' => '{{label}}'
		]);

		$result = $this->Form->radio('Model.field', ['option A', 'option B']);
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
		$this->assertTags($result, $expected);
	}

/**
 * test disabling the hidden input for radio buttons
 *
 * @return void
 */
	public function testRadioHiddenInputDisabling() {
		$result = $this->Form->radio('Model.1.field', array('option A'), array('hiddenField' => false));
		$expected = array(
			'input' => array('type' => 'radio', 'name' => 'Model[1][field]', 'value' => '0', 'id' => 'model-1-field-0'),
			'label' => array('for' => 'model-1-field-0'),
			'option A',
			'/label'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testSelect method
 *
 * Test select element generation.
 *
 * @return void
 */
	public function testSelect() {
		$result = $this->Form->select('Model.field', array());
		$expected = array(
			'select' => array('name' => 'Model[field]'),
			'/select'
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data = array('Model' => array('field' => 'value'));
		$result = $this->Form->select('Model.field', array('value' => 'good', 'other' => 'bad'));
		$expected = array(
			'select' => array('name' => 'Model[field]'),
			array('option' => array('value' => 'value', 'selected' => 'selected')),
			'good',
			'/option',
			array('option' => array('value' => 'other')),
			'bad',
			'/option',
			'/select'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->select('Model.field', new Collection(['value' => 'good', 'other' => 'bad']));
		$this->assertTags($result, $expected);

		$this->Form->request->data = array();
		$result = $this->Form->select('Model.field', array('value' => 'good', 'other' => 'bad'));
		$expected = array(
			'select' => array('name' => 'Model[field]'),
			array('option' => array('value' => 'value')),
			'good',
			'/option',
			array('option' => array('value' => 'other')),
			'bad',
			'/option',
			'/select'
		);
		$this->assertTags($result, $expected);

		$options = array(
			array('value' => 'first', 'text' => 'First'),
			array('value' => 'first', 'text' => 'Another First'),
		);
		$result = $this->Form->select(
			'Model.field',
			$options,
			array('escape' => false, 'empty' => false)
		);
		$expected = array(
			'select' => array('name' => 'Model[field]'),
			array('option' => array('value' => 'first')),
			'First',
			'/option',
			array('option' => array('value' => 'first')),
			'Another First',
			'/option',
			'/select'
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data = array('Model' => array('contact_id' => 228));
		$result = $this->Form->select(
			'Model.contact_id',
			array('228' => '228 value', '228-1' => '228-1 value', '228-2' => '228-2 value'),
			array('escape' => false, 'empty' => 'pick something')
		);

		$expected = array(
			'select' => array('name' => 'Model[contact_id]'),
			array('option' => array('value' => '')), 'pick something', '/option',
			array('option' => array('value' => '228', 'selected' => 'selected')), '228 value', '/option',
			array('option' => array('value' => '228-1')), '228-1 value', '/option',
			array('option' => array('value' => '228-2')), '228-2 value', '/option',
			'/select'
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Model']['field'] = 0;
		$result = $this->Form->select('Model.field', array('0' => 'No', '1' => 'Yes'));
		$expected = array(
			'select' => array('name' => 'Model[field]'),
			array('option' => array('value' => '0', 'selected' => 'selected')), 'No', '/option',
			array('option' => array('value' => '1')), 'Yes', '/option',
			'/select'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test that select() escapes HTML.
 *
 * @return void
 */
	public function testSelectEscapeHtml() {
		$result = $this->Form->select(
			'Model.field', array('first' => 'first "html" <chars>', 'second' => 'value'),
			array('empty' => false)
		);
		$expected = array(
			'select' => array('name' => 'Model[field]'),
			array('option' => array('value' => 'first')),
			'first &quot;html&quot; &lt;chars&gt;',
			'/option',
			array('option' => array('value' => 'second')),
			'value',
			'/option',
			'/select'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->select(
			'Model.field',
			array('first' => 'first "html" <chars>', 'second' => 'value'),
			array('escape' => false, 'empty' => false)
		);
		$expected = array(
			'select' => array('name' => 'Model[field]'),
			array('option' => array('value' => 'first')),
			'first "html" <chars>',
			'/option',
			array('option' => array('value' => 'second')),
			'value',
			'/option',
			'/select'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test select() with required and disabled attributes.
 *
 * @return void
 */
	public function testSelectRequired() {
		$this->article['required'] = [
			'user_id' => true
		];
		$this->Form->create($this->article);
		$result = $this->Form->select('user_id', array('option A'));
		$expected = array(
			'select' => array(
				'name' => 'user_id',
				'required' => 'required'
			),
			array('option' => array('value' => '0')), 'option A', '/option',
			'/select'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->select('user_id', array('option A'), array('disabled' => true));
		$expected = array(
			'select' => array(
				'name' => 'user_id',
				'disabled' => 'disabled'
			),
			array('option' => array('value' => '0')), 'option A', '/option',
			'/select'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testNestedSelect method
 *
 * test select element generation with optgroups
 *
 * @return void
 */
	public function testNestedSelect() {
		$result = $this->Form->select(
			'Model.field',
			array(1 => 'One', 2 => 'Two', 'Three' => array(
				3 => 'Three', 4 => 'Four', 5 => 'Five'
			)), array('empty' => false)
		);
		$expected = array(
			'select' => array('name' => 'Model[field]'),
			array('option' => array('value' => 1)),
			'One',
			'/option',
			array('option' => array('value' => 2)),
			'Two',
			'/option',
			array('optgroup' => array('label' => 'Three')),
				array('option' => array('value' => 3)),
				'Three',
				'/option',
				array('option' => array('value' => 4)),
				'Four',
				'/option',
				array('option' => array('value' => 5)),
				'Five',
				'/option',
			'/optgroup',
			'/select'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testSelectMultiple method
 *
 * test generation of multiple select elements
 *
 * @return void
 */
	public function testSelectMultiple() {
		$options = array('first', 'second', 'third');
		$result = $this->Form->select(
			'Model.multi_field',
			$options,
			['form' => 'my-form', 'multiple' => true]
		);
		$expected = array(
			'input' => array(
				'type' => 'hidden',
				'name' => 'Model[multi_field]',
				'value' => '',
				'form' => 'my-form',
			),
			'select' => array(
				'name' => 'Model[multi_field][]',
				'multiple' => 'multiple',
				'form' => 'my-form',
			),
			array('option' => array('value' => '0')),
			'first',
			'/option',
			array('option' => array('value' => '1')),
			'second',
			'/option',
			array('option' => array('value' => '2')),
			'third',
			'/option',
			'/select'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->select(
			'Model.multi_field',
			$options,
			['multiple' => 'multiple', 'form' => 'my-form']
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test that a checkbox can have 0 for the value and 1 for the hidden input.
 *
 * @return void
 */
	public function testCheckboxZeroValue() {
		$result = $this->Form->input('User.get_spam', array(
			'type' => 'checkbox',
			'value' => '0',
			'hiddenField' => '1',
		));
		$expected = array(
			'div' => array('class' => 'input checkbox'),
			array('input' => array(
				'type' => 'hidden', 'name' => 'User[get_spam]',
				'value' => '1'
			)),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'User[get_spam]',
				'value' => '0', 'id' => 'user-get-spam'
			)),
			'label' => array('for' => 'user-get-spam'),
			'Get Spam',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test generation of habtm select boxes.
 *
 * @return void
 */
	public function testHabtmSelectBox() {
		$this->loadFixtures('Article');
		$options = array(
			1 => 'blue',
			2 => 'red',
			3 => 'green'
		);
		$tags = [
			new Entity(['id' => 1, 'name' => 'blue']),
			new Entity(['id' => 3, 'name' => 'green'])
		];
		$article = new Article(['tags' => $tags]);
		$this->Form->create($article);
		$result = $this->Form->input('tags._ids', ['options' => $options]);
		$expected = array(
			'div' => array('class' => 'input select'),
			'label' => array('for' => 'tags-ids'),
			'Tags',
			'/label',
			'input' => array('type' => 'hidden', 'name' => 'tags[_ids]', 'value' => ''),
			'select' => array(
				'name' => 'tags[_ids][]', 'id' => 'tags-ids',
				'multiple' => 'multiple'
			),
			array('option' => array('value' => '1', 'selected' => 'selected')),
			'blue',
			'/option',
			array('option' => array('value' => '2')),
			'red',
			'/option',
			array('option' => array('value' => '3', 'selected' => 'selected')),
			'green',
			'/option',
			'/select',
			'/div'
		);
		$this->assertTags($result, $expected);

		// make sure only 50 is selected, and not 50f5c0cf
		$options = array(
			'1' => 'blue',
			'50f5c0cf' => 'red',
			'50' => 'green'
		);
		$tags = [
			new Entity(['id' => 1, 'name' => 'blue']),
			new Entity(['id' => 50, 'name' => 'green'])
		];
		$article = new Article(['tags' => $tags]);
		$this->Form->create($article);
		$result = $this->Form->input('tags._ids', ['options' => $options]);
		$expected = array(
			'div' => array('class' => 'input select'),
			'label' => array('for' => 'tags-ids'),
			'Tags',
			'/label',
			'input' => array('type' => 'hidden', 'name' => 'tags[_ids]', 'value' => ''),
			'select' => array(
				'name' => 'tags[_ids][]', 'id' => 'tags-ids',
				'multiple' => 'multiple'
			),
			array('option' => array('value' => '1', 'selected' => 'selected')),
			'blue',
			'/option',
			array('option' => array('value' => '50f5c0cf')),
			'red',
			'/option',
			array('option' => array('value' => '50', 'selected' => 'selected')),
			'green',
			'/option',
			'/select',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test generation of multi select elements in checkbox format
 *
 * @return void
 */
	public function testSelectMultipleCheckboxes() {
		$result = $this->Form->select(
			'Model.multi_field',
			array('first', 'second', 'third'),
			array('multiple' => 'checkbox')
		);

		$expected = array(
			'input' => array(
				'type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => ''
			),
			array('div' => array('class' => 'checkbox')),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'Model[multi_field][]',
				'value' => '0', 'id' => 'model-multi-field-0'
			)),
			array('label' => array('for' => 'model-multi-field-0')),
			'first',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'Model[multi_field][]',
				'value' => '1', 'id' => 'model-multi-field-1'
			)),
			array('label' => array('for' => 'model-multi-field-1')),
			'second',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'Model[multi_field][]',
				'value' => '2', 'id' => 'model-multi-field-2'
			)),
			array('label' => array('for' => 'model-multi-field-2')),
			'third',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->select(
			'Model.multi_field',
			array('a+' => 'first', 'a++' => 'second', 'a+++' => 'third'),
			array('multiple' => 'checkbox')
		);
		$expected = array(
			'input' => array(
				'type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => ''
			),
			array('div' => array('class' => 'checkbox')),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'Model[multi_field][]',
				'value' => 'a+', 'id' => 'model-multi-field-a+'
			)),
			array('label' => array('for' => 'model-multi-field-a+')),
			'first',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'Model[multi_field][]',
				'value' => 'a++', 'id' => 'model-multi-field-a++'
			)),
			array('label' => array('for' => 'model-multi-field-a++')),
			'second',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'Model[multi_field][]',
				'value' => 'a+++', 'id' => 'model-multi-field-a+++'
			)),
			array('label' => array('for' => 'model-multi-field-a+++')),
			'third',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->select(
			'Model.multi_field',
			array('a>b' => 'first', 'a<b' => 'second', 'a"b' => 'third'),
			array('multiple' => 'checkbox')
		);
		$expected = array(
			'input' => array(
				'type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => ''
			),
			array('div' => array('class' => 'checkbox')),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'Model[multi_field][]',
				'value' => 'a&gt;b', 'id' => 'model-multi-field-a-b'
			)),
			array('label' => array('for' => 'model-multi-field-a-b')),
			'first',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'Model[multi_field][]',
				'value' => 'a&lt;b', 'id' => 'model-multi-field-a-b1'
			)),
			array('label' => array('for' => 'model-multi-field-a-b1')),
			'second',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'Model[multi_field][]',
				'value' => 'a&quot;b', 'id' => 'model-multi-field-a-b2'
			)),
			array('label' => array('for' => 'model-multi-field-a-b2')),
			'third',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Ensure that multiCheckbox reads from the request data.
 *
 * @return void
 */
	public function testSelectMultipleCheckboxRequestData() {
		$this->Form->request->data = array('Model' => array('tags' => array(1)));
		$result = $this->Form->select(
			'Model.tags', array('1' => 'first', 'Array' => 'Array'), array('multiple' => 'checkbox')
		);
		$expected = array(
			'input' => array(
				'type' => 'hidden', 'name' => 'Model[tags]', 'value' => ''
			),
			array('div' => array('class' => 'checkbox')),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'Model[tags][]',
				'value' => '1', 'id' => 'model-tags-1', 'checked' => 'checked'
			)),
			array('label' => array('for' => 'model-tags-1', 'class' => 'selected')),
			'first',
			'/label',
			'/div',

			array('div' => array('class' => 'checkbox')),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'Model[tags][]',
				'value' => 'Array', 'id' => 'model-tags-array'
			)),
			array('label' => array('for' => 'model-tags-array')),
			'Array',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Checks the security hash array generated for multiple-input checkbox elements
 *
 * @return void
 */
	public function testSelectMultipleCheckboxSecurity() {
		$this->Form->request->params['_Token'] = 'testKey';
		$this->assertEquals(array(), $this->Form->fields);

		$result = $this->Form->select(
			'Model.multi_field', array('1' => 'first', '2' => 'second', '3' => 'third'),
			array('multiple' => 'checkbox')
		);
		$this->assertEquals(array('Model.multi_field'), $this->Form->fields);

		$result = $this->Form->secure($this->Form->fields);
		$key = 'f7d573650a295b94e0938d32b323fde775e5f32b%3A';
		$this->assertRegExp('/"' . $key . '"/', $result);
	}

/**
 * Multiple select elements should always be secured as they always participate
 * in the POST data.
 *
 * @return void
 */
	public function testSelectMultipleSecureWithNoOptions() {
		$this->assertEquals(array(), $this->Form->fields);

		$this->Form->select(
			'Model.select',
			array(),
			array('multiple' => true)
		);
		$this->assertEquals(array('Model.select'), $this->Form->fields);
	}

/**
 * When a select box has no options it should not be added to the fields list
 * as it always fail post validation.
 *
 * @return void
 */
	public function testSelectNoSecureWithNoOptions() {
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
		$this->assertEquals(array('Model.user_id'), $this->Form->fields);
	}

/**
 * testInputMultipleCheckboxes method
 *
 * test input() resulting in multi select elements being generated.
 *
 * @return void
 */
	public function testInputMultipleCheckboxes() {
		$result = $this->Form->input('Model.multi_field', array(
			'options' => array('first', 'second', 'third'),
			'multiple' => 'checkbox'
		));
		$expected = array(
			array('div' => array('class' => 'input select')),
			array('label' => array('for' => 'model-multi-field')),
			'Multi Field',
			'/label',
			'input' => array('type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => ''),
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'Model[multi_field][]', 'value' => '0', 'id' => 'model-multi-field-0')),
			array('label' => array('for' => 'model-multi-field-0')),
			'first',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'Model[multi_field][]', 'value' => '1', 'id' => 'model-multi-field-1')),
			array('label' => array('for' => 'model-multi-field-1')),
			'second',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'Model[multi_field][]', 'value' => '2', 'id' => 'model-multi-field-2')),
			array('label' => array('for' => 'model-multi-field-2')),
			'third',
			'/label',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Model.multi_field', array(
			'options' => array('a' => 'first', 'b' => 'second', 'c' => 'third'),
			'multiple' => 'checkbox'
		));
		$expected = array(
			array('div' => array('class' => 'input select')),
			array('label' => array('for' => 'model-multi-field')),
			'Multi Field',
			'/label',
			'input' => array('type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => ''),
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'Model[multi_field][]', 'value' => 'a', 'id' => 'model-multi-field-a')),
			array('label' => array('for' => 'model-multi-field-a')),
			'first',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'Model[multi_field][]', 'value' => 'b', 'id' => 'model-multi-field-b')),
			array('label' => array('for' => 'model-multi-field-b')),
			'second',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'Model[multi_field][]', 'value' => 'c', 'id' => 'model-multi-field-c')),
			array('label' => array('for' => 'model-multi-field-c')),
			'third',
			'/label',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testSelectHiddenFieldOmission method
 *
 * test that select() with 'hiddenField' => false omits the hidden field
 *
 * @return void
 */
	public function testSelectHiddenFieldOmission() {
		$result = $this->Form->select('Model.multi_field',
			array('first', 'second'),
			array('multiple' => 'checkbox', 'hiddenField' => false, 'value' => null)
		);
		$this->assertNotContains('type="hidden"', $result);
	}

/**
 * test that select() with multiple = checkbox works with overriding name attribute.
 *
 * @return void
 */
	public function testSelectCheckboxMultipleOverrideName() {
		$result = $this->Form->select('category', ['1', '2'], [
			'multiple' => 'checkbox',
			'name' => 'fish',
		]);
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'fish', 'value' => ''),
			array('div' => array('class' => 'checkbox')),
				array('input' => array('type' => 'checkbox', 'name' => 'fish[]', 'value' => '0', 'id' => 'fish-0')),
				array('label' => array('for' => 'fish-0')), '1', '/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
				array('input' => array('type' => 'checkbox', 'name' => 'fish[]', 'value' => '1', 'id' => 'fish-1')),
				array('label' => array('for' => 'fish-1')), '2', '/label',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->multiCheckbox(
			'category',
			new Collection(['1', '2']),
			['name' => 'fish']
		);
		$result = $this->Form->multiCheckbox('category', ['1', '2'], [
			'name' => 'fish',
		]);
		$this->assertTags($result, $expected);
	}

/**
 * testCheckbox method
 *
 * Test generation of checkboxes
 *
 * @return void
 */
	public function testCheckbox() {
		$result = $this->Form->checkbox('Model.field');
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'Model[field]', 'value' => '0'),
			array('input' => array('type' => 'checkbox', 'name' => 'Model[field]', 'value' => '1'))
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->checkbox('Model.field', array(
			'id' => 'theID',
			'value' => 'myvalue',
			'form' => 'my-form',
		));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'Model[field]', 'value' => '0', 'form' => 'my-form'),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'Model[field]',
				'value' => 'myvalue', 'id' => 'theID',
				'form' => 'my-form',
			))
		);
		$this->assertTags($result, $expected);
	}

/**
 * testCheckboxDefaultValue method
 *
 * Test default value setting on checkbox() method
 *
 * @return void
 */
	public function testCheckboxDefaultValue() {
		$this->Form->request->data['Model']['field'] = false;
		$result = $this->Form->checkbox('Model.field', array('default' => true, 'hiddenField' => false));
		$this->assertTags($result, array('input' => array('type' => 'checkbox', 'name' => 'Model[field]', 'value' => '1')));

		unset($this->Form->request->data['Model']['field']);
		$result = $this->Form->checkbox('Model.field', array('default' => true, 'hiddenField' => false));
		$this->assertTags($result, array('input' => array('type' => 'checkbox', 'name' => 'Model[field]', 'value' => '1', 'checked' => 'checked')));

		$this->Form->request->data['Model']['field'] = true;
		$result = $this->Form->checkbox('Model.field', array('default' => false, 'hiddenField' => false));
		$this->assertTags($result, array('input' => array('type' => 'checkbox', 'name' => 'Model[field]', 'value' => '1', 'checked' => 'checked')));

		unset($this->Form->request->data['Model']['field']);
		$result = $this->Form->checkbox('Model.field', array('default' => false, 'hiddenField' => false));
		$this->assertTags($result, array('input' => array('type' => 'checkbox', 'name' => 'Model[field]', 'value' => '1')));
	}

/**
 * Test checkbox being checked or having errors.
 *
 * @return void
 */
	public function testCheckboxCheckedAndError() {
		$this->article['errors'] = [
			'published' => true
		];
		$this->Form->request->data['published'] = 'myvalue';
		$this->Form->create($this->article);

		$result = $this->Form->checkbox('published', array('id' => 'theID', 'value' => 'myvalue'));
		$expected = array(
			'input' => array('type' => 'hidden', 'class' => 'form-error', 'name' => 'published', 'value' => '0'),
			array('input' => array(
				'type' => 'checkbox',
				'name' => 'published',
				'value' => 'myvalue',
				'id' => 'theID',
				'checked' => 'checked',
				'class' => 'form-error'
			))
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['published'] = '';
		$result = $this->Form->checkbox('published');
		$expected = array(
			'input' => array('type' => 'hidden', 'class' => 'form-error', 'name' => 'published', 'value' => '0'),
			array('input' => array('type' => 'checkbox', 'name' => 'published', 'value' => '1', 'class' => 'form-error'))
		);
		$this->assertTags($result, $expected);
	}

/**
 * test checkbox() with a custom name attribute
 *
 * @return void
 */
	public function testCheckboxCustomNameAttribute() {
		$result = $this->Form->checkbox('Test.test', array('name' => 'myField'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'myField', 'value' => '0'),
			array('input' => array('type' => 'checkbox', 'name' => 'myField', 'value' => '1'))
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test that the hidden input for checkboxes can be omitted or set to a
 * specific value.
 *
 * @return void
 */
	public function testCheckboxHiddenField() {
		$result = $this->Form->checkbox('UserForm.something', array(
			'hiddenField' => false
		));
		$expected = array(
			'input' => array(
				'type' => 'checkbox',
				'name' => 'UserForm[something]',
				'value' => '1'
			),
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->checkbox('UserForm.something', array(
			'value' => 'Y',
			'hiddenField' => 'N',
		));
		$expected = array(
			array('input' => array(
				'type' => 'hidden', 'name' => 'UserForm[something]',
				'value' => 'N'
			)),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'UserForm[something]',
				'value' => 'Y'
			)),
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test the time type.
 *
 * @return void
 */
	public function testTime() {
		$result = $this->Form->time('start_time', array(
			'timeFormat' => 12,
			'interval' => 5,
			'value' => array('hour' => '4', 'minute' => '30', 'meridian' => 'pm')
		));
		$this->assertContains('<option value="04" selected="selected">4</option>', $result);
		$this->assertContains('<option value="30" selected="selected">30</option>', $result);
		$this->assertContains('<option value="pm" selected="selected">pm</option>', $result);
		$this->assertNotContains('year', $result);
		$this->assertNotContains('month', $result);
		$this->assertNotContains('day', $result);

		$result = $this->Form->time('start_time', array(
			'timeFormat' => 12,
			'interval' => 5,
			'value' => '2014-03-08 16:30:00'
		));
		$this->assertContains('<option value="04" selected="selected">4</option>', $result);
		$this->assertContains('<option value="30" selected="selected">30</option>', $result);
		$this->assertContains('<option value="pm" selected="selected">pm</option>', $result);
		$this->assertNotContains('year', $result);
		$this->assertNotContains('month', $result);
		$this->assertNotContains('day', $result);
	}

/**
 * Ensure that timeFormat=24 has no merdian.
 *
 * @return void.
 */
	public function testTimeFormat24NoMeridian() {
		$result = $this->Form->time('start_time', array(
			'timeFormat' => 24,
			'interval' => 5,
			'value' => '2014-03-08 16:30:00'
		));
		$this->assertContains('<option value="16" selected="selected">16</option>', $result);
		$this->assertContains('<option value="30" selected="selected">30</option>', $result);
		$this->assertNotContains('meridian', $result);
		$this->assertNotContains('pm', $result);
		$this->assertNotContains('year', $result);
		$this->assertNotContains('month', $result);
		$this->assertNotContains('day', $result);
	}

/**
 * Test the date type.
 *
 * @return void
 */
	public function testDate() {
		$result = $this->Form->date('start_day', array(
			'value' => array('year' => '2014', 'month' => '03', 'day' => '08')
		));
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
 * Test generation of date/time select elements
 *
 * @return void
 */
	public function testDateTime() {
		extract($this->dateRegex);

		$result = $this->Form->dateTime('Contact.date', array('empty' => false));
		$now = strtotime('now');
		$expected = array(
			array('select' => array('name' => 'Contact[date][year]')),
			$yearsRegex,
			array('option' => array('value' => date('Y', $now), 'selected' => 'selected')),
			date('Y', $now),
			'/option',
			'*/select',

			array('select' => array('name' => 'Contact[date][month]')),
			$monthsRegex,
			array('option' => array('value' => date('m', $now), 'selected' => 'selected')),
			date('F', $now),
			'/option',
			'*/select',

			array('select' => array('name' => 'Contact[date][day]')),
			$daysRegex,
			array('option' => array('value' => date('d', $now), 'selected' => 'selected')),
			date('j', $now),
			'/option',
			'*/select',

			array('select' => array('name' => 'Contact[date][hour]')),
			$hoursRegex,
			array('option' => array('value' => date('H', $now), 'selected' => 'selected')),
			date('G', $now),
			'/option',
			'*/select',

			array('select' => array('name' => 'Contact[date][minute]')),
			$minutesRegex,
			array('option' => array('value' => date('i', $now), 'selected' => 'selected')),
			date('i', $now),
			'/option',
			'*/select',
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test empty defaulting to true for datetime.
 *
 * @return void
 */
	public function testDatetimeEmpty() {
		extract($this->dateRegex);
		$now = strtotime('now');

		$result = $this->Form->dateTime('Contact.date', array(
			'timeFormat' => 12,
			'empty' => true,
		));
		$expected = array(
			array('select' => array('name' => 'Contact[date][year]')),
			$yearsRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',

			array('select' => array('name' => 'Contact[date][month]')),
			$monthsRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',

			array('select' => array('name' => 'Contact[date][day]')),
			$daysRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',

			array('select' => array('name' => 'Contact[date][hour]')),
			$hoursRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',

			array('select' => array('name' => 'Contact[date][minute]')),
			$minutesRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select',

			array('select' => array('name' => 'Contact[date][meridian]')),
			$meridianRegex,
			array('option' => array('value' => '')),
			'/option',
			'*/select'
		);
		$this->assertTags($result, $expected);
		$this->assertNotRegExp('/<option[^<>]+value=""[^<>]+selected="selected"[^>]*>/', $result);
	}

/**
 * Test datetime with interval option.
 *
 * @return void
 */
	public function testDatetimeMinuteInterval() {
		extract($this->dateRegex);
		$now = strtotime('now');

		$result = $this->Form->dateTime('Contact.date', array(
			'interval' => 5,
			'value' => ''
		));
		$expected = array(
			array('select' => array('name' => 'Contact[date][year]')),
			$yearsRegex,
			array('option' => array('selected' => 'selected', 'value' => '')),
			'/option',
			'*/select',

			array('select' => array('name' => 'Contact[date][month]')),
			$monthsRegex,
			array('option' => array('selected' => 'selected', 'value' => '')),
			'/option',
			'*/select',

			array('select' => array('name' => 'Contact[date][day]')),
			$daysRegex,
			array('option' => array('selected' => 'selected', 'value' => '')),
			'/option',
			'*/select',

			array('select' => array('name' => 'Contact[date][hour]')),
			$hoursRegex,
			array('option' => array('selected' => 'selected', 'value' => '')),
			'/option',
			'*/select',

			array('select' => array('name' => 'Contact[date][minute]')),
			$minutesRegex,
			array('option' => array('selected' => 'selected', 'value' => '')),
			'/option',
			array('option' => array('value' => '00')),
			'00',
			'/option',
			array('option' => array('value' => '05')),
			'05',
			'/option',
			array('option' => array('value' => '10')),
			'10',
			'/option',
			'*/select',
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test dateTime with rounding
 *
 * @return void
 */
	public function testDateTimeRounding() {
		$this->Form->request->data['Contact'] = array(
			'date' => array(
				'day' => '13',
				'month' => '12',
				'year' => '2010',
				'hour' => '04',
				'minute' => '19',
				'meridian' => 'AM'
			)
		);

		$result = $this->Form->dateTime('Contact.date', array('interval' => 15));
		$this->assertTextContains('<option value="15" selected="selected">15</option>', $result);

		$result = $this->Form->dateTime('Contact.date', array('interval' => 15, 'round' => 'up'));
		$this->assertTextContains('<option value="30" selected="selected">30</option>', $result);

		$result = $this->Form->dateTime('Contact.date', array('interval' => 5, 'round' => 'down'));
		$this->assertTextContains('<option value="15" selected="selected">15</option>', $result);
	}

/**
 * test that datetime() and default values work.
 *
 * @return void
 */
	public function testDatetimeWithDefault() {
		$result = $this->Form->dateTime('Contact.updated', array('value' => '2009-06-01 11:15:30'));
		$this->assertRegExp('/<option[^<>]+value="2009"[^<>]+selected="selected"[^>]*>2009<\/option>/', $result);
		$this->assertRegExp('/<option[^<>]+value="01"[^<>]+selected="selected"[^>]*>1<\/option>/', $result);
		$this->assertRegExp('/<option[^<>]+value="06"[^<>]+selected="selected"[^>]*>June<\/option>/', $result);

		$result = $this->Form->dateTime('Contact.updated', array(
			'default' => '2009-06-01 11:15:30'
		));
		$this->assertRegExp('/<option[^<>]+value="2009"[^<>]+selected="selected"[^>]*>2009<\/option>/', $result);
		$this->assertRegExp('/<option[^<>]+value="01"[^<>]+selected="selected"[^>]*>1<\/option>/', $result);
		$this->assertRegExp('/<option[^<>]+value="06"[^<>]+selected="selected"[^>]*>June<\/option>/', $result);
	}

/**
 * testDateTime all zeros
 *
 * @return void
 */
	public function testDateTimeAllZeros() {
		$result = $this->Form->dateTime('Contact.date', array(
			'timeFormat' => false,
			'empty' => array('day' => '-', 'month' => '-', 'year' => '-'),
			'value' => '0000-00-00'
		));

		$this->assertRegExp('/<option value="">-<\/option>/', $result);
		$this->assertNotRegExp('/<option value="0" selected="selected">0<\/option>/', $result);
	}

/**
 * testDateTimeEmptyAsArray
 *
 * @return void
 */
	public function testDateTimeEmptyAsArray() {
		$result = $this->Form->dateTime('Contact.date', array(
			'empty' => array(
				'day' => 'DAY',
				'month' => 'MONTH',
				'year' => 'YEAR',
				'hour' => 'HOUR',
				'minute' => 'MINUTE',
				'meridian' => false
			)
		));

		$this->assertRegExp('/<option value="">DAY<\/option>/', $result);
		$this->assertRegExp('/<option value="">MONTH<\/option>/', $result);
		$this->assertRegExp('/<option value="">YEAR<\/option>/', $result);
		$this->assertRegExp('/<option value="">HOUR<\/option>/', $result);
		$this->assertRegExp('/<option value="">MINUTE<\/option>/', $result);
		$this->assertNotRegExp('/<option value=""><\/option>/', $result);

		$result = $this->Form->dateTime('Contact.date', array(
			'empty' => array('day' => 'DAY', 'month' => 'MONTH', 'year' => 'YEAR')
		));

		$this->assertRegExp('/<option value="">DAY<\/option>/', $result);
		$this->assertRegExp('/<option value="">MONTH<\/option>/', $result);
		$this->assertRegExp('/<option value="">YEAR<\/option>/', $result);
	}

/**
 * testFormDateTimeMulti method
 *
 * test multiple datetime element generation
 *
 * @return void
 */
	public function testFormDateTimeMulti() {
		extract($this->dateRegex);

		$result = $this->Form->dateTime('Contact.1.updated');
		$this->assertContains('Contact[1][updated][month]', $result);
		$this->assertContains('Contact[1][updated][day]', $result);
		$this->assertContains('Contact[1][updated][year]', $result);
		$this->assertContains('Contact[1][updated][hour]', $result);
		$this->assertContains('Contact[1][updated][minute]', $result);
	}

/**
 * When changing the date format, the label should always focus the first select box when
 * clicked.
 *
 * @return void
 */
	public function testDateTimeLabelIdMatchesFirstInput() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$result = $this->Form->input('Model.date', array('type' => 'date'));
		$this->assertContains('label for="ModelDateMonth"', $result);

		$result = $this->Form->input('Model.date', array('type' => 'date', 'dateFormat' => 'DMY'));
		$this->assertContains('label for="ModelDateDay"', $result);

		$result = $this->Form->input('Model.date', array('type' => 'date', 'dateFormat' => 'YMD'));
		$this->assertContains('label for="ModelDateYear"', $result);
	}

/**
 * testMonth method
 *
 * @return void
 */
	public function testMonth() {
		$result = $this->Form->month('Model.field', ['value' => '']);
		$expected = array(
			array('select' => array('name' => 'Model[field][month]')),
			array('option' => array('value' => '', 'selected' => 'selected')),
			'/option',
			array('option' => array('value' => '01')),
			date('F', strtotime('2008-01-01 00:00:00')),
			'/option',
			array('option' => array('value' => '02')),
			date('F', strtotime('2008-02-01 00:00:00')),
			'/option',
			'*/select',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->month('Model.field', ['empty' => true, 'value' => '']);
		$expected = array(
			array('select' => array('name' => 'Model[field][month]')),
			array('option' => array('selected' => 'selected', 'value' => '')),
			'/option',
			array('option' => array('value' => '01')),
			date('F', strtotime('2008-01-01 00:00:00')),
			'/option',
			array('option' => array('value' => '02')),
			date('F', strtotime('2008-02-01 00:00:00')),
			'/option',
			'*/select',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->month('Model.field', ['value' => '', 'monthNames' => false]);
		$expected = array(
			array('select' => array('name' => 'Model[field][month]')),
			array('option' => array('selected' => 'selected', 'value' => '')),
			'/option',
			array('option' => array('value' => '01')),
			'1',
			'/option',
			array('option' => array('value' => '02')),
			'2',
			'/option',
			'*/select',
		);
		$this->assertTags($result, $expected);

		$monthNames = [
			'01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'May', '06' => 'Jun',
			'07' => 'Jul', '08' => 'Aug', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec'
		];
		$result = $this->Form->month('Model.field', array('value' => '1', 'monthNames' => $monthNames));
		$expected = array(
			array('select' => array('name' => 'Model[field][month]')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '01', 'selected' => 'selected')),
			'Jan',
			'/option',
			array('option' => array('value' => '02')),
			'Feb',
			'/option',
			'*/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Project']['release'] = '2050-02-10';
		$result = $this->Form->month('Project.release');

		$expected = array(
			array('select' => array('name' => 'Project[release][month]')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '01')),
			'January',
			'/option',
			array('option' => array('value' => '02', 'selected' => 'selected')),
			'February',
			'/option',
			'*/select',
		);
		$this->assertTags($result, $expected);
	}

/**
 * testDay method
 *
 * @return void
 */
	public function testDay() {
		extract($this->dateRegex);

		$result = $this->Form->day('Model.field', array('value' => false));
		$expected = array(
			array('select' => array('name' => 'Model[field][day]')),
			array('option' => array('selected' => 'selected', 'value' => '')),
			'/option',
			array('option' => array('value' => '01')),
			'1',
			'/option',
			array('option' => array('value' => '02')),
			'2',
			'/option',
			$daysRegex,
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Model']['field'] = '2006-10-10 23:12:32';
		$result = $this->Form->day('Model.field');
		$expected = array(
			array('select' => array('name' => 'Model[field][day]')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '01')),
			'1',
			'/option',
			array('option' => array('value' => '02')),
			'2',
			'/option',
			$daysRegex,
			array('option' => array('value' => '10', 'selected' => 'selected')),
			'10',
			'/option',
			$daysRegex,
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Model']['field'] = '';
		$result = $this->Form->day('Model.field', array('value' => '10'));
		$expected = array(
			array('select' => array('name' => 'Model[field][day]')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '01')),
			'1',
			'/option',
			array('option' => array('value' => '02')),
			'2',
			'/option',
			$daysRegex,
			array('option' => array('value' => '10', 'selected' => 'selected')),
			'10',
			'/option',
			$daysRegex,
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Project']['release'] = '2050-10-10';
		$result = $this->Form->day('Project.release');

		$expected = array(
			array('select' => array('name' => 'Project[release][day]')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '01')),
			'1',
			'/option',
			array('option' => array('value' => '02')),
			'2',
			'/option',
			$daysRegex,
			array('option' => array('value' => '10', 'selected' => 'selected')),
			'10',
			'/option',
			$daysRegex,
			'/select',
		);
		$this->assertTags($result, $expected);
	}

/**
 * testMinute method
 *
 * @return void
 */
	public function testMinute() {
		extract($this->dateRegex);

		$result = $this->Form->minute('Model.field', ['value' => '']);
		$expected = array(
			array('select' => array('name' => 'Model[field][minute]')),
			array('option' => array('selected' => 'selected', 'value' => '')),
			'/option',
			array('option' => array('value' => '00')),
			'00',
			'/option',
			array('option' => array('value' => '01')),
			'01',
			'/option',
			array('option' => array('value' => '02')),
			'02',
			'/option',
			$minutesRegex,
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Model']['field'] = '2006-10-10 00:12:32';
		$result = $this->Form->minute('Model.field');
		$expected = array(
			array('select' => array('name' => 'Model[field][minute]')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '00')),
			'00',
			'/option',
			array('option' => array('value' => '01')),
			'01',
			'/option',
			array('option' => array('value' => '02')),
			'02',
			'/option',
			$minutesRegex,
			array('option' => array('value' => '12', 'selected' => 'selected')),
			'12',
			'/option',
			$minutesRegex,
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Model']['field'] = '';
		$result = $this->Form->minute('Model.field', array('interval' => 5));
		$expected = array(
			array('select' => array('name' => 'Model[field][minute]')),
			array('option' => array('selected' => 'selected', 'value' => '')),
			'/option',
			array('option' => array('value' => '00')),
			'00',
			'/option',
			array('option' => array('value' => '05')),
			'05',
			'/option',
			array('option' => array('value' => '10')),
			'10',
			'/option',
			$minutesRegex,
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Model']['field'] = '2006-10-10 00:10:32';
		$result = $this->Form->minute('Model.field', array('interval' => 5));
		$expected = array(
			array('select' => array('name' => 'Model[field][minute]')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '00')),
			'00',
			'/option',
			array('option' => array('value' => '05')),
			'05',
			'/option',
			array('option' => array('value' => '10', 'selected' => 'selected')),
			'10',
			'/option',
			$minutesRegex,
			'/select',
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test generating an input for the meridian.
 *
 * @return void
 */
	public function testMeridian() {
		extract($this->dateRegex);

		$now = time();
		$result = $this->Form->meridian('Model.field', ['value' => 'am']);
		$expected = [
			array('select' => array('name' => 'Model[field][meridian]')),
			array('option' => array('value' => '')),
			'/option',
			$meridianRegex,
			array('option' => array('value' => date('a', $now), 'selected' => 'selected')),
			date('a', $now),
			'/option',
			'*/select'
		];
		$this->assertTags($result, $expected);
	}

/**
 * testHour method
 *
 * @return void
 */
	public function testHour() {
		extract($this->dateRegex);

		$result = $this->Form->hour('Model.field', ['format' => 12, 'value' => '']);
		$expected = array(
			array('select' => array('name' => 'Model[field][hour]')),
			array('option' => array('selected' => 'selected', 'value' => '')),
			'/option',
			array('option' => array('value' => '01')),
			'1',
			'/option',
			array('option' => array('value' => '02')),
			'2',
			'/option',
			$hoursRegex,
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Model']['field'] = '2006-10-10 00:12:32';
		$result = $this->Form->hour('Model.field', ['format' => 12]);
		$expected = array(
			array('select' => array('name' => 'Model[field][hour]')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '01')),
			'1',
			'/option',
			array('option' => array('value' => '02')),
			'2',
			'/option',
			$hoursRegex,
			array('option' => array('value' => '12', 'selected' => 'selected')),
			'12',
			'/option',
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Model']['field'] = '';
		$result = $this->Form->hour('Model.field', array('format' => 24, 'value' => '23'));
		$this->assertContains('<option value="23" selected="selected">23</option>', $result);

		$result = $this->Form->hour('Model.field', array('format' => 12, 'value' => '23'));
		$this->assertContains('<option value="11" selected="selected">11</option>', $result);

		$this->Form->request->data['Model']['field'] = '2006-10-10 00:12:32';
		$result = $this->Form->hour('Model.field', ['format' => 24]);
		$expected = array(
			array('select' => array('name' => 'Model[field][hour]')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '00', 'selected' => 'selected')),
			'0',
			'/option',
			array('option' => array('value' => '01')),
			'1',
			'/option',
			array('option' => array('value' => '02')),
			'2',
			'/option',
			$hoursRegex,
			'/select',
		);
		$this->assertTags($result, $expected);

		unset($this->Form->request->data['Model']['field']);
		$result = $this->Form->hour('Model.field', array('format' => 24, 'value' => 'now'));
		$thisHour = date('H');
		$optValue = date('G');
		$this->assertRegExp('/<option value="' . $thisHour . '" selected="selected">' . $optValue . '<\/option>/', $result);

		$this->Form->request->data['Model']['field'] = '2050-10-10 01:12:32';
		$result = $this->Form->hour('Model.field', ['format' => 24]);
		$expected = array(
			array('select' => array('name' => 'Model[field][hour]')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '00')),
			'0',
			'/option',
			array('option' => array('value' => '01', 'selected' => 'selected')),
			'1',
			'/option',
			array('option' => array('value' => '02')),
			'2',
			'/option',
			$hoursRegex,
			'/select',
		);
		$this->assertTags($result, $expected);
	}

/**
 * testYear method
 *
 * @return void
 */
	public function testYear() {
		$result = $this->Form->year('Model.field', ['value' => '', 'minYear' => 2006, 'maxYear' => 2007]);
		$expected = array(
			array('select' => array('name' => 'Model[field][year]')),
			array('option' => array('selected' => 'selected', 'value' => '')),
			'/option',
			array('option' => array('value' => '2007')),
			'2007',
			'/option',
			array('option' => array('value' => '2006')),
			'2006',
			'/option',
			'/select',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->year('Model.field', [
			'value' => '',
			'minYear' => 2006,
			'maxYear' => 2007,
			'orderYear' => 'asc'
		]);
		$expected = array(
			array('select' => array('name' => 'Model[field][year]')),
			array('option' => array('selected' => 'selected', 'value' => '')),
			'/option',
			array('option' => array('value' => '2006')),
			'2006',
			'/option',
			array('option' => array('value' => '2007')),
			'2007',
			'/option',
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Contact']['published'] = '2006-10-10';
		$result = $this->Form->year('Contact.published', [
			'empty' => false,
			'minYear' => 2006,
			'maxYear' => 2007,
		]);
		$expected = array(
			array('select' => array('name' => 'Contact[published][year]')),
			array('option' => array('value' => '2007')),
			'2007',
			'/option',
			array('option' => array('value' => '2006', 'selected' => 'selected')),
			'2006',
			'/option',
			'/select',
		);
		$this->assertTags($result, $expected);
	}

/**
 * testYearAutoExpandRange method
 *
 * @return void
 */
	public function testYearAutoExpandRange() {
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
 * Test that input() accepts the type of date and passes options in.
 *
 * @return void
 */
	public function testInputDate() {
		$this->Form->request->data = array(
			'month_year' => array('month' => date('m')),
		);
		$this->Form->create($this->article);
		$result = $this->Form->input('month_year', array(
				'label' => false,
				'type' => 'date',
				'minYear' => 2006,
				'maxYear' => 2008
		));
		$this->assertContains('value="' . date('m') . '" selected="selected"', $result);
		$this->assertNotContains('value="2008" selected="selected"', $result);
	}

/**
 * testInputDateMaxYear method
 *
 * Let's say we want to only allow users born from 2006 to 2008 to register
 * This being the first singup page, we still don't have any data
 *
 * @return void
 */
	public function testInputDateMaxYear() {
		$this->Form->request->data = [];
		$this->Form->create($this->article);
		$result = $this->Form->input('birthday', array(
			'label' => false,
			'type' => 'date',
			'minYear' => 2006,
			'maxYear' => 2008
		));
		$this->assertContains('value="2008" selected="selected"', $result);
	}

/**
 * testTextArea method
 *
 * @return void
 */
	public function testTextArea() {
		$this->Form->request->data = array('field' => 'some test data');
		$result = $this->Form->textarea('field');
		$expected = array(
			'textarea' => array('name' => 'field'),
			'some test data',
			'/textarea',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->textarea('user.bio');
		$expected = array(
			'textarea' => array('name' => 'user[bio]'),
			'/textarea',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data = array('field' => 'some <strong>test</strong> data with <a href="#">HTML</a> chars');
		$result = $this->Form->textarea('field');
		$expected = array(
			'textarea' => array('name' => 'field'),
			htmlentities('some <strong>test</strong> data with <a href="#">HTML</a> chars'),
			'/textarea',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data = [
			'Model' => ['field' => 'some <strong>test</strong> data with <a href="#">HTML</a> chars']
		];
		$result = $this->Form->textarea('Model.field', ['escape' => false]);
		$expected = array(
			'textarea' => array('name' => 'Model[field]'),
			'some <strong>test</strong> data with <a href="#">HTML</a> chars',
			'/textarea',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->textarea('0.OtherModel.field');
		$expected = array(
			'textarea' => array('name' => '0[OtherModel][field]'),
			'/textarea'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testTextAreaWithStupidCharacters method
 *
 * test text area with non-ascii characters
 *
 * @return void
 */
	public function testTextAreaWithStupidCharacters() {
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
		$this->assertTags($result, $expected);
	}

/**
 * testHiddenField method
 *
 * @return void
 */
	public function testHiddenField() {
		$this->article['errors'] = [
			'field' => true
		];
		$this->Form->request->data['field'] = 'test';
		$this->Form->create($this->article);
		$result = $this->Form->hidden('field', array('id' => 'theID'));
		$this->assertTags($result, array(
			'input' => array('type' => 'hidden', 'class' => 'form-error', 'name' => 'field', 'id' => 'theID', 'value' => 'test'))
		);

		$result = $this->Form->hidden('field', ['value' => 'my value']);
		$expected = [
			'input' => ['type' => 'hidden', 'class' => 'form-error', 'name' => 'field', 'value' => 'my value']
		];
		$this->assertTags($result, $expected);
	}

/**
 * testFileUploadField method
 *
 * @return void
 */
	public function testFileUploadField() {
		$expected = ['input' => ['type' => 'file', 'name' => 'Model[upload]']];

		$result = $this->Form->file('Model.upload');
		$this->assertTags($result, $expected);

		$this->Form->request->data['Model']['upload'] = [
			'name' => '', 'type' => '', 'tmp_name' => '',
			'error' => 4, 'size' => 0
		];
		$result = $this->Form->file('Model.upload');
		$this->assertTags($result, $expected);

		$this->Form->request->data['Model']['upload'] = 'no data should be set in value';
		$result = $this->Form->file('Model.upload');
		$this->assertTags($result, $expected);
	}

/**
 * test File upload input on a model not used in create();
 *
 * @return void
 */
	public function testFileUploadOnOtherModel() {
		$this->Form->create($this->article, array('type' => 'file'));
		$result = $this->Form->file('ValidateProfile.city');
		$expected = array(
			'input' => array('type' => 'file', 'name' => 'ValidateProfile[city]')
		);
		$this->assertTags($result, $expected);
	}

/**
 * testButton method
 *
 * @return void
 */
	public function testButton() {
		$result = $this->Form->button('Hi');
		$this->assertTags($result, array('button' => array('type' => 'submit'), 'Hi', '/button'));

		$result = $this->Form->button('Clear Form >', array('type' => 'reset'));
		$this->assertTags($result, array('button' => array('type' => 'reset'), 'Clear Form >', '/button'));

		$result = $this->Form->button('Clear Form >', array('type' => 'reset', 'id' => 'clearForm'));
		$this->assertTags($result, array('button' => array('type' => 'reset', 'id' => 'clearForm'), 'Clear Form >', '/button'));

		$result = $this->Form->button('<Clear Form>', array('type' => 'reset', 'escape' => true));
		$this->assertTags($result, array('button' => array('type' => 'reset'), '&lt;Clear Form&gt;', '/button'));

		$result = $this->Form->button('No type', array('type' => false));
		$this->assertTags($result, array('button' => array(), 'No type', '/button'));

		$result = $this->Form->button('Upload Text', array(
			'onClick' => "$('#postAddForm').ajaxSubmit({target: '#postTextUpload', url: '/posts/text'});return false;'",
			'escape' => false
		));
		$this->assertNotRegExp('/\&039/', $result);
	}

/**
 * Test that button() makes unlocked fields by default.
 *
 * @return void
 */
	public function testButtonUnlockedByDefault() {
		$this->Form->request->params['_csrfToken'] = 'secured';
		$this->Form->button('Save', array('name' => 'save'));
		$this->Form->button('Clear');

		$result = $this->Form->unlockField();
		$this->assertEquals(array('save'), $result);
	}

/**
 * testPostButton method
 *
 * @return void
 */
	public function testPostButton() {
		$result = $this->Form->postButton('Hi', '/controller/action');
		$this->assertTags($result, array(
			'form' => array('method' => 'post', 'action' => '/controller/action', 'accept-charset' => 'utf-8'),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div',
			'button' => array('type' => 'submit'),
			'Hi',
			'/button',
			'/form'
		));

		$result = $this->Form->postButton('Send', '/', array('data' => array('extra' => 'value')));
		$this->assertTrue(strpos($result, '<input type="hidden" name="extra" value="value"') !== false);
	}

/**
 * Test using postButton with N dimensional data.
 *
 * @return void
 */
	public function testPostButtonNestedData() {
		$data = array(
			'one' => array(
				'two' => array(
					3, 4, 5
				)
			)
		);
		$result = $this->Form->postButton('Send', '/', array('data' => $data));
		$this->assertContains('<input type="hidden" name="one[two][0]" value="3"', $result);
		$this->assertContains('<input type="hidden" name="one[two][1]" value="4"', $result);
		$this->assertContains('<input type="hidden" name="one[two][2]" value="5"', $result);
	}

/**
 * Test that postButton adds _Token fields.
 *
 * @return void
 */
	public function testSecurePostButton() {
		$this->Form->request->params['_csrfToken'] = 'testkey';
		$this->Form->request->params['_Token'] = ['unlockedFields' => []];

		$result = $this->Form->postButton('Delete', '/posts/delete/1');
		$expected = array(
			'form' => array(
				'method' => 'post', 'action' => '/posts/delete/1', 'accept-charset' => 'utf-8',
			),
			array('div' => array('style' => 'display:none;')),
			array('input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST')),
			array('input' => array('type' => 'hidden', 'name' => '_csrfToken', 'value' => 'testkey')),
			'/div',
			'button' => array('type' => 'submit'),
			'Delete',
			'/button',
			array('div' => array('style' => 'display:none;')),
			array('input' => array('type' => 'hidden', 'name' => '_Token[fields]', 'value' => 'preg:/[\w\d%]+/')),
			array('input' => array('type' => 'hidden', 'name' => '_Token[unlocked]', 'value' => '')),
			'/div',
			'/form',
		);
		$this->assertTags($result, $expected);
	}

/**
 * testPostLink method
 *
 * @return void
 */
	public function testPostLink() {
		$result = $this->Form->postLink('Delete', '/posts/delete/1');
		$this->assertTags($result, array(
			'form' => array(
				'method' => 'post', 'action' => '/posts/delete/1',
				'name' => 'preg:/post_\w+/', 'style' => 'display:none;'
			),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/form',
			'a' => array('href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'),
			'Delete',
			'/a'
		));

		$result = $this->Form->postLink('Delete', '/posts/delete/1', array('method' => 'delete'));
		$this->assertTags($result, array(
			'form' => array(
				'method' => 'post', 'action' => '/posts/delete/1',
				'name' => 'preg:/post_\w+/', 'style' => 'display:none;'
			),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'DELETE'),
			'/form',
			'a' => array('href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'),
			'Delete',
			'/a'
		));

		$result = $this->Form->postLink('Delete', '/posts/delete/1', array(), 'Confirm?');
		$this->assertTags($result, array(
			'form' => array(
				'method' => 'post', 'action' => '/posts/delete/1',
				'name' => 'preg:/post_\w+/', 'style' => 'display:none;'
			),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/form',
			'a' => array('href' => '#', 'onclick' => 'preg:/if \(confirm\(&quot;Confirm\?&quot;\)\) \{ document\.post_\w+\.submit\(\); \} event\.returnValue = false; return false;/'),
			'Delete',
			'/a'
		));

		$result = $this->Form->postLink('Delete', '/posts/delete/1', array('escape' => false), '\'Confirm\' this "deletion"?');
		$this->assertTags($result, array(
			'form' => array(
				'method' => 'post', 'action' => '/posts/delete/1',
				'name' => 'preg:/post_\w+/', 'style' => 'display:none;'
			),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/form',
			'a' => array('href' => '#', 'onclick' => 'preg:/if \(confirm\(&quot;&#039;Confirm&#039; this \\\\&quot;deletion\\\\&quot;\?&quot;\)\) \{ document\.post_\w+\.submit\(\); \} event\.returnValue = false; return false;/'),
			'Delete',
			'/a'
		));

		$result = $this->Form->postLink('Delete', '/posts/delete', array('data' => array('id' => 1)));
		$this->assertContains('<input type="hidden" name="id" value="1"', $result);

		$result = $this->Form->postLink('Delete', '/posts/delete/1', array('target' => '_blank'));
		$this->assertTags($result, array(
			'form' => array(
				'method' => 'post', 'target' => '_blank', 'action' => '/posts/delete/1',
				'name' => 'preg:/post_\w+/', 'style' => 'display:none;'
			),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/form',
			'a' => array('href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'),
			'Delete',
			'/a'
		));

		$result = $this->Form->postLink(
			'',
			array('controller' => 'items', 'action' => 'delete', 10),
			array('class' => 'btn btn-danger', 'escape' => false),
			'Confirm thing'
		);
		$this->assertTags($result, array(
			'form' => array(
				'method' => 'post', 'action' => '/items/delete/10',
				'name' => 'preg:/post_\w+/', 'style' => 'display:none;'
			),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/form',
			'a' => array('class' => 'btn btn-danger', 'href' => '#', 'onclick' => 'preg:/if \(confirm\(\&quot\;Confirm thing\&quot\;\)\) \{ document\.post_\w+\.submit\(\); \} event\.returnValue = false; return false;/'),
			'/a'
		));
	}

/**
 * Test that security hashes for postLink include the url.
 *
 * @return void
 */
	public function testPostLinkSecurityHash() {
		$hash = Security::hash(
			'/posts/delete/1' .
			serialize(array()) .
			'' .
			Configure::read('Security.salt')
		);
		$hash .= '%3A';
		$this->Form->request->params['_Token']['key'] = 'test';

		$result = $this->Form->postLink('Delete', '/posts/delete/1');
		$expected = array(
			'form' => array(
				'method' => 'post', 'action' => '/posts/delete/1',
				'name', 'style' => 'display:none;'
			),
			array('input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST')),
			'div' => array('style' => 'display:none;'),
			array('input' => array('type' => 'hidden', 'name' => '_Token[fields]', 'value' => $hash)),
			array('input' => array('type' => 'hidden', 'name' => '_Token[unlocked]', 'value' => '')),
			'/div',
			'/form',
			'a' => array('href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'),
			'Delete',
			'/a'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test using postLink with N dimensional data.
 *
 * @return void
 */
	public function testPostLinkNestedData() {
		$data = array(
			'one' => array(
				'two' => array(
					3, 4, 5
				)
				)
		);
		$result = $this->Form->postLink('Send', '/', array('data' => $data));
		$this->assertContains('<input type="hidden" name="one[two][0]" value="3"', $result);
		$this->assertContains('<input type="hidden" name="one[two][1]" value="4"', $result);
		$this->assertContains('<input type="hidden" name="one[two][2]" value="5"', $result);
	}

/**
 * test creating postLinks after a GET form.
 *
 * @return void
 */
	public function testPostLinkAfterGetForm() {
		$this->Form->request->params['_csrfToken'] = 'testkey';
		$this->Form->request->params['_Token'] = 'val';

		$this->Form->create($this->article, array('type' => 'get'));
		$this->Form->end();

		$result = $this->Form->postLink('Delete', '/posts/delete/1');
		$this->assertTags($result, array(
			'form' => array(
				'method' => 'post', 'action' => '/posts/delete/1',
				'name' => 'preg:/post_\w+/', 'style' => 'display:none;'
			),
			array('input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST')),
			array('input' => array('type' => 'hidden', 'name' => '_csrfToken', 'value' => 'testkey')),
			'div' => array('style' => 'display:none;'),
			array('input' => array('type' => 'hidden', 'name' => '_Token[fields]', 'value' => 'preg:/[\w\d%]+/')),
			array('input' => array('type' => 'hidden', 'name' => '_Token[unlocked]', 'value' => '')),
			'/div',
			'/form',
			'a' => array('href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'),
			'Delete',
			'/a'
		));
	}

/**
 * Test that postLink adds form tags to view block
 *
 * @return void
 */
	public function testPostLinkFormBuffer() {
		$result = $this->Form->postLink('Delete', '/posts/delete/1', array('block' => true));
		$this->assertTags($result, array(
			'a' => array('href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'),
			'Delete',
			'/a'
		));

		$result = $this->View->fetch('postLink');
		$this->assertTags($result, array(
			'form' => array(
				'method' => 'post', 'action' => '/posts/delete/1',
				'name' => 'preg:/post_\w+/', 'style' => 'display:none;'
			),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/form'
		));

		$result = $this->Form->postLink('Delete', '/posts/delete/2',
			array('block' => true, 'method' => 'DELETE')
		);
		$this->assertTags($result, array(
			'a' => array('href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'),
			'Delete',
			'/a'
		));

		$result = $this->View->fetch('postLink');
		$this->assertTags($result, array(
			'form' => array(
				'method' => 'post', 'action' => '/posts/delete/1',
				'name' => 'preg:/post_\w+/', 'style' => 'display:none;'
			),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/form',
			array(
				'form' => array(
					'method' => 'post', 'action' => '/posts/delete/2',
					'name' => 'preg:/post_\w+/', 'style' => 'display:none;'
				),
			),
			array('input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'DELETE')),
			'/form'
		));

		$result = $this->Form->postLink('Delete', '/posts/delete/1', array('block' => 'foobar'));
		$this->assertTags($result, array(
			'a' => array('href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'),
			'Delete',
			'/a'
		));

		$result = $this->View->fetch('foobar');
		$this->assertTags($result, array(
			'form' => array(
				'method' => 'post', 'action' => '/posts/delete/1',
				'name' => 'preg:/post_\w+/', 'style' => 'display:none;'
			),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/form'
		));
	}

/**
 * testSubmitButton method
 *
 * @return void
 */
	public function testSubmitButton() {
		$result = $this->Form->submit('');
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'value' => ''),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('Test Submit');
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'value' => 'Test Submit'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('Next >');
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'value' => 'Next &gt;'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('Next >', array('escape' => false));
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'value' => 'Next >'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('Reset!', array('type' => 'reset'));
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'reset', 'value' => 'Reset!'),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test image submit types.
 *
 * @return void
 */
	public function testSubmitImage() {
		$result = $this->Form->submit('http://example.com/cake.power.gif');
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'image', 'src' => 'http://example.com/cake.power.gif'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('/relative/cake.power.gif');
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'image', 'src' => 'relative/cake.power.gif'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('cake.power.gif');
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'image', 'src' => 'img/cake.power.gif'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('Not.an.image');
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'value' => 'Not.an.image'),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Submit buttons should be unlocked by default as there could be multiples, and only one will
 * be submitted at a time.
 *
 * @return void
 */
	public function testSubmitUnlockedByDefault() {
		$this->Form->request->params['_Token'] = 'secured';
		$this->Form->submit('Go go');
		$this->Form->submit('Save', array('name' => 'save'));

		$result = $this->Form->unlockField();
		$this->assertEquals(array('save'), $result, 'Only submits with name attributes should be unlocked.');
	}

/**
 * Test submit image with timestamps.
 *
 * @return void
 */
	public function testSubmitImageTimestamp() {
		Configure::write('Asset.timestamp', 'force');

		$result = $this->Form->submit('cake.power.gif');
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'image', 'src' => 'preg:/img\/cake\.power\.gif\?\d*/'),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that datetime() works with GET style forms.
 *
 * @return void
 */
	public function testDateTimeWithGetForms() {
		extract($this->dateRegex);
		$this->Form->create($this->article, array('type' => 'get'));
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
	public function testForMagicInputNonExistingNorValidated() {
		$result = $this->Form->create($this->article);
		$this->Form->templates(['groupContainer' => '{{content}}']);
		$result = $this->Form->input('non_existing_nor_validated');
		$expected = array(
			'label' => array('for' => 'non-existing-nor-validated'),
			'Non Existing Nor Validated',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'non_existing_nor_validated',
				'id' => 'non-existing-nor-validated'
			)
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('non_existing_nor_validated', array(
			'val' => 'my value'
		));
		$expected = array(
			'label' => array('for' => 'non-existing-nor-validated'),
			'Non Existing Nor Validated',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'non_existing_nor_validated',
				'value' => 'my value', 'id' => 'non-existing-nor-validated'
			)
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data = array('non_existing_nor_validated' => 'CakePHP magic');
		$result = $this->Form->input('non_existing_nor_validated');
		$expected = array(
			'label' => array('for' => 'non-existing-nor-validated'),
			'Non Existing Nor Validated',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'non_existing_nor_validated',
				'value' => 'CakePHP magic', 'id' => 'non-existing-nor-validated'
			)
		);
		$this->assertTags($result, $expected);
	}

/**
 * testFormMagicInputLabel method
 *
 * @return void
 */
	public function testFormMagicInputLabel() {
		TableRegistry::get('Contacts', [
			'className' => __NAMESPACE__ . '\ContactsTable'
		]);
		$this->Form->create([], ['context' => ['table' => 'Contacts']]);
		$this->Form->templates(['groupContainer' => '{{content}}']);

		$result = $this->Form->input('Contacts.name', array('label' => 'My label'));
		$expected = array(
			'label' => array('for' => 'contacts-name'),
			'My label',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contacts[name]',
				'id' => 'contacts-name', 'maxlength' => '255'
			)
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('name', array(
			'label' => array('class' => 'mandatory')
		));
		$expected = array(
			'label' => array('for' => 'name', 'class' => 'mandatory'),
			'Name',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'name',
				'id' => 'name', 'maxlength' => '255'
			)
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('name', array(
			'div' => false,
			'label' => array('class' => 'mandatory', 'text' => 'My label')
		));
		$expected = array(
			'label' => array('for' => 'name', 'class' => 'mandatory'),
			'My label',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'name',
				'id' => 'name', 'maxlength' => '255'
			)
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.name', array(
			'div' => false, 'id' => 'my_id', 'label' => array('for' => 'my_id')
		));
		$expected = array(
			'label' => array('for' => 'my_id'),
			'Name',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[name]',
				'id' => 'my_id', 'maxlength' => '255'
			)
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('1.id');
		$this->assertTags($result, array('input' => array(
			'type' => 'hidden', 'name' => '1[id]',
			'id' => '1-id'
		)));

		$result = $this->Form->input("1.name");
		$expected = array(
			'label' => array('for' => '1-name'),
			'Name',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => '1[name]',
				'id' => '1-name', 'maxlength' => '255'
			)
		);
		$this->assertTags($result, $expected);
	}

/**
 * testFormEnd method
 *
 * @return void
 */
	public function testFormEnd() {
		$this->assertEquals('</form>', $this->Form->end());
	}

/**
 * Test the generation of fields for a multi record form.
 *
 * @return void
 */
	public function testMultiRecordForm() {
		$this->loadFixtures('Article', 'Comment');
		$articles = TableRegistry::get('Articles');
		$articles->hasMany('Comments');

		$comment = new Entity(['comment' => 'Value']);
		$article = new Article(['comments' => [$comment]]);
		$this->Form->create([$article]);
		$result = $this->Form->input('0.comments.1.comment');
		$expected = array(
			'div' => array('class' => 'input textarea'),
				'label' => array('for' => '0-comments-1-comment'),
					'Comment',
				'/label',
				'textarea' => array(
					'name',
					'type',
					'id' => '0-comments-1-comment',
				),
				'/textarea',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('0.comments.0.comment');
		$expected = array(
			'div' => array('class' => 'input textarea'),
				'label' => array('for' => '0-comments-0-comment'),
					'Comment',
				'/label',
				'textarea' => array(
					'name',
					'type',
					'id' => '0-comments-0-comment'
				),
				'Value',
				'/textarea',
			'/div'
		);
		$this->assertTags($result, $expected);

		$comment->errors('comment', ['Not valid']);
		$result = $this->Form->input('0.comments.0.comment');
		$expected = array(
			'div' => array('class' => 'input textarea error'),
				'label' => array('for' => '0-comments-0-comment'),
					'Comment',
				'/label',
				'textarea' => array(
					'name',
					'type',
					'class' => 'form-error',
					'id' => '0-comments-0-comment'
				),
				'Value',
				'/textarea',
				array('div' => array('class' => 'error-message')),
				'Not valid',
				'/div',
			'/div'
		);
		$this->assertTags($result, $expected);

		TableRegistry::get('Comments')
			->validator('default')
			->allowEmpty('comment', false);
		$result = $this->Form->input('0.comments.1.comment');
		$expected = array(
			'div' => array('class' => 'input textarea required'),
				'label' => array('for' => '0-comments-1-comment'),
					'Comment',
				'/label',
				'textarea' => array(
					'name',
					'type',
					'required' => 'required',
					'id' => '0-comments-1-comment'
				),
				'/textarea',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that some html5 inputs + FormHelper::__call() work
 *
 * @return void
 */
	public function testHtml5Inputs() {
		$result = $this->Form->email('User.email');
		$expected = array(
			'input' => array('type' => 'email', 'name' => 'User[email]')
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->search('User.query');
		$expected = array(
			'input' => array('type' => 'search', 'name' => 'User[query]')
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->search('User.query', array('value' => 'test'));
		$expected = array(
			'input' => array('type' => 'search', 'name' => 'User[query]', 'value' => 'test')
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->search('User.query', array('type' => 'text', 'value' => 'test'));
		$expected = array(
			'input' => array('type' => 'text', 'name' => 'User[query]', 'value' => 'test')
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test accessing html5 inputs through input().
 *
 * @return void
 */
	public function testHtml5InputWithInput() {
		$this->Form->create();
		$this->Form->templates(['groupContainer' => '{{content}}']);
		$result = $this->Form->input('website', array(
			'type' => 'url',
			'val' => 'http://domain.tld',
			'label' => false
		));
		$expected = array(
			'input' => array('type' => 'url', 'name' => 'website', 'id' => 'website', 'value' => 'http://domain.tld')
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test errors when field name is missing.
 *
 * @expectedException \Cake\Error\Exception
 * @return void
 */
	public function testHtml5InputException() {
		$this->Form->email();
	}

/**
 * Tests that formhelper sets required attributes.
 *
 * @return void
 */
	public function testRequiredAttribute() {
		$this->article['required'] = [
			'title' => true,
			'body' => false,
		];
		$this->Form->create($this->article);

		$result = $this->Form->input('title');
		$expected = array(
			'div' => array('class' => 'input text required'),
			'label' => array('for' => 'title'),
			'Title',
			'/label',
			'input' => array(
				'type' => 'text',
				'name' => 'title',
				'id' => 'title',
				'required' => 'required',
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('title', ['required' => false]);
		$this->assertNotContains('required', $result);

		$result = $this->Form->input('body');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'body'),
			'Body',
			'/label',
			'input' => array(
				'type' => 'text',
				'name' => 'body',
				'id' => 'body',
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('body', ['required' => true]);
		$this->assertContains('required', $result);
	}

/**
 * Tests that it is possible to nest inputs inside labels
 *
 * @return void
 */
	public function testNestInputInLabel() {
		$this->Form->templates([
			'label' => '<label{{attrs}}>{{text}}{{input}}</label>',
			'formGroup' => '{{label}}'
		]);
		$result = $this->Form->input('foo');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'foo'),
				'Foo',
				'input' => array('type' => 'text', 'name' => 'foo', 'id' => 'foo'),
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test resetting templates.
 *
 * @return void
 */
	public function testResetTemplates() {
		$this->Form->templates(['input' => '<input>']);
		$this->assertEquals('<input>', $this->Form->templater()->get('input'));

		$this->assertNull($this->Form->resetTemplates());
		$this->assertNotEquals('<input>', $this->Form->templater()->get('input'));
	}

}
