<?php
/**
 * FormHelperTest file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View\Helper;

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
 * ContactTestController class
 *
 */
class ContactTestController extends Controller {

/**
 * uses property
 *
 * @var mixed null
 */
	public $uses = null;
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
		'age' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => null)
	);

/**
 * validate property
 *
 * @var array
 */
	public $validate = array(
		'non_existing' => array(),
		'idontexist' => array(),
		'imrequired' => array('_allowEmpty' => false, array('rule' => array('between', 5, 30))),
		'imrequiredonupdate' => array('notEmpty' => array('rule' => 'alphaNumeric', 'on' => 'update')),
		'imrequiredoncreate' => array('required' => array('rule' => 'alphaNumeric', 'on' => 'create')),
		'imrequiredonboth' => array(
			'required' => array('rule' => 'alphaNumeric'),
		),
		'string_required' => 'notEmpty',
		'imalsorequired' => array('_allowEmpty' => false, array('rule' => 'alphaNumeric')),
		'imrequiredtoo' => array('rule' => 'notEmpty'),
		'required_one' => array('required' => array('rule' => array('notEmpty'))),
		'imnotrequired' => array(
			'_allowEmpty' => true,
			array('rule' => 'alphaNumeric')
		),
		'imalsonotrequired' => array(
			'_allowEmpty' => true,
			'alpha' => array('rule' => 'alphaNumeric'),
			'between' => array('rule' => array('between', 5, 30)),
		),
		'imnotrequiredeither' => array(
			'_allowEmpty' => true,
			array('rule' => array('between', 5, 30))
		),
		'iamrequiredalways' => array(
			'email' => array('rule' => 'email'),
			'rule_on_create' => array('rule' => array('maxLength', 50), 'on' => 'create'),
			'rule_on_update' => array('rule' => array('between', 1, 50), 'on' => 'update'),
		),
		'boolean_field' => array('rule' => 'boolean')
	);

/**
 * schema method
 *
 * @return void
 */
	public function setSchema($schema) {
		$this->_schema = $schema;
	}

/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $hasAndBelongsToMany = array(
		'ContactTag' => array(
			'className' => 'Cake\Test\TestCase\View\Helper\ContactTag',
			'with' => 'Cake\Test\TestCase\View\Helper\ContactTagsContact'
		)
	);

/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $belongsTo = array(
		'User' => array('className' => 'UserForm'
	));
}

/**
 * ContactTagsContact class
 *
 */
class ContactTagsContactsTable extends Table {

/**
 * Default schema
 *
 * @var array
 */
	protected $_schema = array(
		'contact_id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'contact_tag_id' => array(
			'type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'
		)
	);

/**
 * schema method
 *
 * @return void
 */
	public function setSchema($schema) {
		$this->_schema = $schema;
	}

}

/**
 * ContactNonStandardPk class
 *
 */
class ContactNonStandardPk extends ContactsTable {

/**
 * primaryKey property
 *
 * @var string
 */
	public $primaryKey = 'pk';

/**
 * schema method
 *
 * @return void
 */
	public function schema($field = false) {
		$this->_schema = parent::schema();
		$this->_schema['pk'] = $this->_schema['id'];
		unset($this->_schema['id']);
		return $this->_schema;
	}

}

/**
 * ContactTag class
 *
 */
class ContactTagsTable extends Table {

/**
 * schema definition
 *
 * @var array
 */
	protected $_schema = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => '', 'length' => '8'),
		'name' => array('type' => 'string', 'null' => false, 'default' => '', 'length' => '255'),
		'created' => array('type' => 'date', 'null' => true, 'default' => '', 'length' => ''),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => '', 'length' => null)
	);
}

/**
 * UserForm class
 *
 */
class UserFormsTable extends Table {

/**
 * hasMany property
 *
 * @var array
 */
	public $hasMany = array(
		'OpenidUrl' => array('className' => 'OpenidUrl', 'foreignKey' => 'user_form_id'
	));

/**
 * schema definition
 *
 * @var array
 */
	protected $_schema = array(
		'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'published' => array('type' => 'date', 'null' => true, 'default' => null, 'length' => null),
		'other' => array('type' => 'text', 'null' => true, 'default' => null, 'length' => null),
		'stuff' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 10),
		'something' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 255),
		'active' => array('type' => 'boolean', 'null' => false, 'default' => false),
		'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
		'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
	);
}

/**
 * OpenidUrl class
 *
 */
class OpenidUrlsTable extends Table {

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('UserForm' => array(
		'className' => 'UserForm', 'foreignKey' => 'user_form_id'
	));

/**
 * validate property
 *
 * @var array
 */
	public $validate = array('openid_not_registered' => array());

/**
 * schema method
 *
 * @var array
 */
	protected $_schema = array(
		'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'user_form_id' => array(
			'type' => 'user_form_id', 'null' => '', 'default' => '', 'length' => '8'
		),
		'url' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
	);

/**
 * beforeValidate method
 *
 * @return void
 */
	public function beforeValidate($options = array()) {
		$this->invalidate('openid_not_registered');
		return true;
	}

}

/**
 * ValidateUser class
 *
 */
class ValidateUsersTable extends Table {

/**
 * hasOne property
 *
 * @var array
 */
	public $hasOne = array('ValidateProfile' => array(
		'className' => 'ValidateProfile', 'foreignKey' => 'user_id'
	));

/**
 * schema method
 *
 * @var array
 */
	protected $_schema = array(
		'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'email' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'balance' => array('type' => 'float', 'null' => false, 'length' => '5,2'),
		'cost_decimal' => array('type' => 'decimal', 'null' => false, 'length' => '6,3'),
		'ratio' => array('type' => 'decimal', 'null' => false, 'length' => '10,6'),
		'population' => array('type' => 'decimal', 'null' => false, 'length' => '15,0'),
		'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
		'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
	);

/**
 * beforeValidate method
 *
 * @param array $options
 * @return void
 */
	public function beforeValidate($options = array()) {
		$this->invalidate('email');
		return false;
	}

}

/**
 * ValidateProfile class
 *
 */
class ValidateProfilesTable extends Table {

/**
 * useTable property
 *
 * @var boolean
 */
	public $useTable = false;

/**
 * schema property
 *
 * @var array
 */
	protected $_schema = array(
		'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'user_id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'full_name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'city' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
		'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
	);

/**
 * hasOne property
 *
 * @var array
 */
	public $hasOne = array('ValidateItem' => array(
		'className' => 'ValidateItem', 'foreignKey' => 'profile_id'
	));

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('ValidateUser' => array(
		'className' => 'ValidateUser', 'foreignKey' => 'user_id'
	));

/**
 * beforeValidate method
 *
 * @return void
 */
	public function beforeValidate($options = array()) {
		$this->invalidate('full_name');
		$this->invalidate('city');
		return false;
	}

}

/**
 * ValidateItem class
 *
 */
class ValidateItemsTable extends Table {

/**
 * schema property
 *
 * @var array
 */
	protected $_schema = array(
		'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'profile_id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'name' => array('type' => 'text', 'null' => '', 'default' => '', 'length' => '255'),
		'description' => array(
			'type' => 'string', 'null' => '', 'default' => '', 'length' => '255'
		),
		'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
		'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
	);

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('ValidateProfile' => array('foreignKey' => 'profile_id'));

/**
 * beforeValidate method
 *
 * @return void
 */
	public function beforeValidate($options = array()) {
		$this->invalidate('description');
		return false;
	}

}

/**
 * TestMail class
 *
 */
class TestMailsTable extends Table {

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
	public $fixtures = array('core.article');

/**
 * Do not load the fixtures by default
 *
 * @var boolean
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
		$this->View = new View(null);

		$this->Form = new FormHelper($this->View);
		$this->Form->Html = new HtmlHelper($this->View);
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
		unset($this->Form->Html, $this->Form, $this->Controller, $this->View);
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
 * Test the onsubmit option for create()
 *
 * @return void
 */
	public function testCreateOnSubmit() {
		$this->Form->request->data = [];
		$this->Form->request['controller'] = 'articles';
		$result = $this->Form->create($this->article, ['url' => ['action' => 'index', 'param'], 'default' => false]);
		$expected = array(
			'form' => array(
				'method' => 'post', 'onsubmit' => 'event.returnValue = false; return false;', 'action' => '/articles/index/param',
				'accept-charset' => 'utf-8'
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data = [];
		$this->Form->request['controller'] = 'articles';
		$result = $this->Form->create($this->article, array(
			'url' => array('action' => 'index', 'param'),
			'default' => false,
			'onsubmit' => 'someFunction();'
		));

		$expected = array(
			'form' => array(
				'method' => 'post',
				'onsubmit' => 'someFunction();event.returnValue = false; return false;',
				'action' => '/articles/index/param',
				'accept-charset' => 'utf-8'
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
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
 * test that inputDefaults are stored and used.
 *
 * @return void
 */
	public function testCreateWithInputDefaults() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->Form->create('User', array(
			'inputDefaults' => array(
				'div' => false,
				'label' => false,
				'error' => array('attributes' => array('wrap' => 'small', 'class' => 'error')),
				'format' => array('before', 'label', 'between', 'input', 'after', 'error')
			)
		));
		$result = $this->Form->input('username');
		$expected = array(
			'input' => array('type' => 'text', 'name' => 'User[username]', 'id' => 'UserUsername')
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('username', array('div' => true, 'label' => 'username'));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'UserUsername'), 'username', '/label',
			'input' => array('type' => 'text', 'name' => 'User[username]', 'id' => 'UserUsername'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('username', array('label' => 'Username', 'format' => array('input', 'label')));
		$expected = array(
			'input' => array('type' => 'text', 'name' => 'User[username]', 'id' => 'UserUsername'),
			'label' => array('for' => 'UserUsername'), 'Username', '/label',
		);
		$this->assertTags($result, $expected);

		$this->Form->create('User', array(
			'inputDefaults' => array(
				'div' => false,
				'label' => array('class' => 'nice', 'for' => 'changed'),
			)
		));
		$result = $this->Form->input('username', array('div' => true));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'changed', 'class' => 'nice'), 'Username', '/label',
			'input' => array('type' => 'text', 'name' => 'User[username]', 'id' => 'UserUsername'),
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

		$result = $this->Form->end('Save');
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
 * Tests that models with identical field names get resolved properly
 *
 * @return void
 */
	public function testDuplicateFieldNameResolution() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$result = $this->Form->create('ValidateUser');
		$this->assertEquals(array('ValidateUser'), $this->Form->entity());

		$result = $this->Form->input('ValidateItem.name');
		$this->assertEquals(array('ValidateItem', 'name'), $this->Form->entity());

		$result = $this->Form->input('ValidateUser.name');
		$this->assertEquals(array('ValidateUser', 'name'), $this->Form->entity());
		$this->assertContains('name="ValidateUser[name]"', $result);
		$this->assertContains('type="text"', $result);

		$result = $this->Form->input('ValidateItem.name');
		$this->assertEquals(array('ValidateItem', 'name'), $this->Form->entity());
		$this->assertContains('name="ValidateItem[name]"', $result);
		$this->assertContains('<textarea', $result);

		$result = $this->Form->input('name');
		$this->assertEquals(array('ValidateUser', 'name'), $this->Form->entity());
		$this->assertContains('name="ValidateUser[name]"', $result);
		$this->assertContains('type="text"', $result);
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
		$this->markTestIncomplete('Need to revisit once models work again.');
		$key = 'testKey';
		$fields = array('Model.password', 'Model.username', 'Model.valid' => '0');

		$this->Form->request->params['_csrfToken'] = $key;
		$result = $this->Form->secure($fields);

		$hash = Security::hash(serialize($fields) . Configure::read('Security.salt'));
		$hash .= ':' . 'Model.valid';
		$hash = urlencode($hash);

		$expected = array(
			'div' => array('style' => 'display:none;'),
			array('input' => array(
				'type' => 'hidden', 'name' => '_Token[fields]',
				'value' => $hash
			)),
			array('input' => array(
				'type' => 'hidden', 'name' => '_Token[unlocked]',
				'value' => '', 'id' => 'preg:/TokenUnlocked\d+/'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$path = CAKE . 'Test/TestApp/Config/';
		$this->Form->Html->loadConfig('htmlhelper_tags', $path);
		$result = $this->Form->secure($fields);
		$expected = array(
			'div' => array('class' => 'hidden'),
			array('input' => array(
				'type' => 'hidden', 'name' => '_Token[fields]',
				'value' => $hash
			)),
			array('input' => array(
				'type' => 'hidden', 'name' => '_Token[unlocked]',
				'value' => '', 'id' => 'preg:/TokenUnlocked\d+/'
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
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->article['schema'] = [
			'foo' => [
				'type' => 'float',
				'null' => false,
				'default' => null,
				'length' => 10
			]
		];

		$this->Form->create('Contact');
		$result = $this->Form->input('foo');
		$expected = array(
			'div' => array('class' => 'input number'),
			'label' => array('for' => 'ContactFoo'),
			'Foo',
			'/label',
			array('input' => array(
				'type' => 'number',
				'name' => 'Contact[foo]',
				'id' => 'ContactFoo',
				'step' => 'any'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('foo', array('step' => 0.5));
		$expected = array(
			'div' => array('class' => 'input number'),
			'label' => array('for' => 'ContactFoo'),
			'Foo',
			'/label',
			array('input' => array(
				'type' => 'number',
				'name' => 'Contact[foo]',
				'id' => 'ContactFoo',
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
		$this->markTestIncomplete('Need to revisit once models work again.');
		$model->setSchema(array('foo' => array(
			'type' => 'integer',
			'null' => false,
			'default' => null,
			'length' => null
		)));

		$this->Form->create('Contact');
		$result = $this->Form->input('foo');
		$expected = array(
			'div' => array('class' => 'input number'),
			'label' => array('for' => 'ContactFoo'),
			'Foo',
			'/label',
			array('input' => array(
				'type' => 'number', 'name' => 'Contact[foo]',
				'id' => 'ContactFoo'
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
		$this->markTestIncomplete('Need to revisit once models work again.');
		$model->setSchema(array('foo' => array(
			'type' => 'binary',
			'null' => false,
			'default' => null,
			'length' => 1024
		)));

		$this->Form->create('Contact');
		$result = $this->Form->input('foo');
		$expected = array(
			'div' => array('class' => 'input file'),
			'label' => array('for' => 'ContactFoo'),
			'Foo',
			'/label',
			array('input' => array(
				'type' => 'file', 'name' => 'data[Contact][foo]',
				'id' => 'ContactFoo'
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
		$this->markTestIncomplete('Need to revisit once models work again.');
		$key = 'testKey';
		$this->Form->request->params['_csrfToken'] = $key;

		$this->Form->create('Addresses');
		$this->Form->input('Address.title');
		$this->Form->input('Address.first_name');

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
		$result = $this->Form->end(null);

		$expected = array(
			'div' => array('style' => 'display:none;'),
			array('input' => array(
				'type' => 'hidden', 'name' => '_Token[fields]',
				'value' => 'preg:/.+/'
			)),
			array('input' => array(
				'type' => 'hidden', 'name' => '_Token[unlocked]',
				'value' => 'cancel%7Csave', 'id' => 'preg:/TokenUnlocked\d+/'
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
		$this->markTestIncomplete('Need to revisit once models work again.');
		$key = 'testKey';
		$this->Form->request->params['_csrfToken'] = $key;
		$this->Form->create('Addresses');

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

		$hash = 'c9118120e680a7201b543f562e5301006ccfcbe2%3AAddresses.0.id%7CAddresses.1.id';

		$expected = array(
			'div' => array('style' => 'display:none;'),
			array('input' => array(
				'type' => 'hidden', 'name' => '_Token[fields]',
				'value' => $hash
			)),
			array('input' => array(
				'type' => 'hidden', 'name' => '_Token[unlocked]',
				'value' => '', 'id' => 'preg:/TokenUnlocked\d+/'
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
		$this->markTestIncomplete('Need to revisit once models work again.');
		$key = 'testKey';
		$this->Form->request->params['_csrfToken'] = $key;

		$this->Form->create('Address');
		$this->Form->input('Address.primary.1');
		$this->assertEquals('Address.primary', $this->Form->fields[0]);

		$this->Form->input('Address.secondary.1.0');
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
		$this->markTestIncomplete('Need to revisit once models work again.');
		$key = 'testKey';
		$this->Form->request->params['_csrfToken'] = $key;
		$this->Form->request->params['_Token'] = array(
			'unlockedFields' => array('first_name', 'address')
		);
		$this->Form->create();

		$this->Form->hidden('Addresses.0.id', array('value' => '123456'));
		$this->Form->input('Addresses.0.title');
		$this->Form->input('Addresses.0.first_name');
		$this->Form->input('Addresses.0.last_name');
		$this->Form->input('Addresses.0.address');
		$this->Form->input('Addresses.0.city');
		$this->Form->input('Addresses.0.phone');
		$this->Form->hidden('Addresses.1.id', array('value' => '654321'));
		$this->Form->input('Addresses.1.title');
		$this->Form->input('Addresses.1.first_name');
		$this->Form->input('Addresses.1.last_name');
		$this->Form->input('Addresses.1.address');
		$this->Form->input('Addresses.1.city');
		$this->Form->input('Addresses.1.phone');

		$result = $this->Form->secure($this->Form->fields);
		$hash = '629b6536dcece48aa41a117045628ce602ccbbb2%3AAddresses.0.id%7CAddresses.1.id';

		$expected = array(
			'div' => array('style' => 'display:none;'),
			array('input' => array(
				'type' => 'hidden', 'name' => '_Token[fields]',
				'value' => $hash
			)),
			array('input' => array(
				'type' => 'hidden', 'name' => '_Token[unlocked]',
				'value' => 'address%7Cfirst_name', 'id' => 'preg:/TokenUnlocked\d+/'
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
		$this->markTestIncomplete('Need to revisit once models work again.');
		$key = 'testKey';
		$this->Form->request->params['_csrfToken'] = $key;
		$this->Form->request['_Token'] = array(
			'unlockedFields' => array('first_name', 'address')
		);
		$this->Form->create();
		$this->assertEquals($this->Form->request['_Token']['unlockedFields'], $this->Form->unlockField());

		$this->Form->hidden('Addresses.id', array('value' => '123456'));
		$this->Form->input('Addresses.title');
		$this->Form->input('Addresses.first_name');
		$this->Form->input('Addresses.last_name');
		$this->Form->input('Addresses.address');
		$this->Form->input('Addresses.city');
		$this->Form->input('Addresses.phone');

		$result = $this->Form->fields;
		$expected = array(
			'Addresses.id' => '123456', 'Addresses.title', 'Addresses.last_name',
			'Addresses.city', 'Addresses.phone'
		);
		$this->assertEquals($expected, $result);

		$result = $this->Form->secure($expected);

		$hash = '2981c38990f3f6ba935e6561dc77277966fabd6d%3AAddresses.id';
		$expected = array(
			'div' => array('style' => 'display:none;'),
			array('input' => array(
				'type' => 'hidden', 'name' => '_Token[fields]',
				'value' => $hash
			)),
			array('input' => array(
				'type' => 'hidden', 'name' => '_Token[unlocked]',
				'value' => 'address%7Cfirst_name', 'id' => 'preg:/TokenUnlocked\d+/'
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
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->Form->request->params['_csrfToken'] = 'testKey';

		$result = $this->Form->create('Contact', array('url' => '/contacts/add'));
		$encoding = strtolower(Configure::read('App.encoding'));
		$expected = array(
			'form' => array('method' => 'post', 'action' => '/contacts/add', 'accept-charset' => $encoding, 'id' => 'ContactAddForm'),
			'div' => array('style' => 'display:none;'),
			array('input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST')),
			array('input' => array(
				'type' => 'hidden', 'name' => '_csrfToken',
				'value' => 'testKey', 'id' => 'preg:/Token\d+/'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('ValidateUser.ratio');
		$expected = array(
			'div' => array('class'),
			'label' => array('for'),
			'Ratio',
			'/label',
			'input' => array('name', 'type' => 'number', 'step' => '0.000001', 'id'),
			'/div',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('ValidateUser.population');
		$expected = array(
			'div' => array('class'),
			'label' => array('for'),
			'Population',
			'/label',
			'input' => array('name', 'type' => 'number', 'step' => '1', 'id'),
			'/div',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('UserForm.published', array('type' => 'text'));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'UserFormPublished'),
			'Published',
			'/label',
			array('input' => array(
				'type' => 'text', 'name' => 'UserForm[published]',
				'id' => 'UserFormPublished'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('UserForm.other', array('type' => 'text'));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'UserFormOther'),
			'Other',
			'/label',
			array('input' => array(
				'type' => 'text', 'name' => 'UserForm[other]',
				'id' => 'UserFormOther'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->hidden('UserForm.stuff');
		$expected = array(
			'input' => array(
				'type' => 'hidden', 'name' => 'UserForm[stuff]',
				'id' => 'UserFormStuff'
		));
		$this->assertTags($result, $expected);

		$result = $this->Form->hidden('UserForm.hidden', array('value' => '0'));
		$expected = array('input' => array(
			'type' => 'hidden', 'name' => 'UserForm[hidden]',
			'value' => '0', 'id' => 'UserFormHidden'
		));
		$this->assertTags($result, $expected);

		$result = $this->Form->input('UserForm.something', array('type' => 'checkbox'));
		$expected = array(
			'div' => array('class' => 'input checkbox'),
			array('input' => array(
				'type' => 'hidden', 'name' => 'UserForm[something]',
				'value' => '0', 'id' => 'UserFormSomething_'
			)),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'UserForm[something]',
				'value' => '1', 'id' => 'UserFormSomething'
			)),
			'label' => array('for' => 'UserFormSomething'),
			'Something',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->fields;
		$expected = array(
			'UserForm.published', 'UserForm.other', 'UserForm.stuff' => '',
			'UserForm.hidden' => '0', 'UserForm.something'
		);
		$this->assertEquals($expected, $result);

		$hash = 'bd7c4a654e5361f9a433a43f488ff9a1065d0aaf%3AUserForm.hidden%7CUserForm.stuff';

		$result = $this->Form->secure($this->Form->fields);
		$expected = array(
			'div' => array('style' => 'display:none;'),
			array('input' => array(
				'type' => 'hidden', 'name' => '_Token[fields]',
				'value' => $hash
			)),
			array('input' => array(
				'type' => 'hidden', 'name' => '_Token[unlocked]',
				'value' => '', 'id' => 'preg:/TokenUnlocked\d+/'
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
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->Form->request->params['_csrfToken'] = 'testKey';
		$this->assertEquals(array(), $this->Form->fields);

		$this->Form->input('text_input', array(
			'name' => 'data[Option][General.default_role]',
		));
		$expected = array('Option.General.default_role');
		$this->assertEquals($expected, $this->Form->fields);

		$this->Form->input('select_box', array(
			'name' => 'data[Option][General.select_role]',
			'type' => 'select',
			'options' => array(1, 2),
		));
		$expected = array('Option.General.default_role', 'Option.General.select_role');
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
		$this->Form->year('Model.year', null, null, array('disabled' => true));
		$this->Form->month('Model.month', array('disabled' => true));
		$this->Form->day('Model.day', array('disabled' => true));
		$this->Form->hour('Model.hour', false, array('disabled' => true));
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
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->Form->request->params['_csrfToken'] = 'testKey';
		$this->Form->request['_Token'] = array(
			'disabledFields' => array()
		);
		$this->Form->create();

		$this->Form->hidden('Addresses.id', array('value' => '123456'));
		$this->Form->input('Addresses.title');
		$this->Form->input('Addresses.first_name', array('secure' => false));
		$this->Form->input('Addresses.city', array('type' => 'textarea', 'secure' => false));
		$this->Form->input('Addresses.zip', array(
			'type' => 'select', 'options' => array(1, 2), 'secure' => false
		));

		$result = $this->Form->fields;
		$expected = array(
			'Addresses.id' => '123456', 'Addresses.title',
		);
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
 * Test validation errors.
 *
 * @return void
 */
	public function testPasswordValidation() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$Contact->validationErrors['password'] = array('Please provide a password');

		$result = $this->Form->input('Contact.password');
		$expected = array(
			'div' => array('class' => 'input password error'),
			'label' => array('for' => 'ContactPassword'),
			'Password',
			'/label',
			'input' => array(
				'type' => 'password', 'name' => 'Contact[password]',
				'id' => 'ContactPassword', 'class' => 'form-error'
			),
			array('div' => array('class' => 'error-message')),
			'Please provide a password',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.password', array('errorMessage' => false));
		$expected = array(
			'div' => array('class' => 'input password error'),
			'label' => array('for' => 'ContactPassword'),
			'Password',
			'/label',
			'input' => array(
				'type' => 'password', 'name' => 'Contact[password]',
				'id' => 'ContactPassword', 'class' => 'form-error'
			),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test validation errors, when validation message is an empty string.
 *
 * @return void
 */
	public function testEmptyErrorValidation() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->Form->validationErrors['Contact']['password'] = '';

		$result = $this->Form->input('Contact.password');
		$expected = array(
			'div' => array('class' => 'input password error'),
			'label' => array('for' => 'ContactPassword'),
			'Password',
			'/label',
			'input' => array(
				'type' => 'password', 'name' => 'Contact[password]',
				'id' => 'ContactPassword', 'class' => 'form-error'
			),
			array('div' => array('class' => 'error-message')),
			array(),
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.password', array('errorMessage' => false));
		$expected = array(
			'div' => array('class' => 'input password error'),
			'label' => array('for' => 'ContactPassword'),
			'Password',
			'/label',
			'input' => array(
				'type' => 'password', 'name' => 'Contact[password]',
				'id' => 'ContactPassword', 'class' => 'form-error'
			),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test validation errors, when calling input() overriding validation message by an empty string.
 *
 * @return void
 */
	public function testEmptyInputErrorValidation() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->Form->validationErrors['Contact']['password'] = 'Please provide a password';

		$result = $this->Form->input('Contact.password', array('error' => ''));
		$expected = array(
			'div' => array('class' => 'input password error'),
			'label' => array('for' => 'ContactPassword'),
			'Password',
			'/label',
			'input' => array(
				'type' => 'password', 'name' => 'Contact[password]',
				'id' => 'ContactPassword', 'class' => 'form-error'
			),
			array('div' => array('class' => 'error-message')),
			array(),
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.password', array('error' => '', 'errorMessage' => false));
		$expected = array(
			'div' => array('class' => 'input password error'),
			'label' => array('for' => 'ContactPassword'),
			'Password',
			'/label',
			'input' => array(
				'type' => 'password', 'name' => 'Contact[password]',
				'id' => 'ContactPassword', 'class' => 'form-error'
			),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testFormValidationAssociated method
 *
 * test display of form errors in conjunction with model::validates.
 *
 * @return void
 */
	public function testFormValidationAssociated() {
		$this->markTestIncomplete('Need to revisit once models work again.');

		$data = array(
			'UserForm' => array('name' => 'user'),
			'OpenidUrl' => array('url' => 'http://www.cakephp.org')
		);

		$result = $this->UserForm->OpenidUrl->create($data);
		$this->assertFalse(empty($result));
		$this->assertFalse($this->UserForm->OpenidUrl->validates());

		$result = $this->Form->create('UserForm', array('type' => 'post', 'action' => 'login'));
		$encoding = strtolower(Configure::read('App.encoding'));
		$expected = array(
			'form' => array(
				'method' => 'post', 'action' => '/user_forms/login', 'id' => 'UserFormLoginForm',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->error(
			'OpenidUrl.openid_not_registered', 'Error, not registered', array('wrap' => false)
		);
		$this->assertEquals('Error, not registered', $result);

		unset($this->UserForm->OpenidUrl, $this->UserForm);
	}

/**
 * testFormValidationAssociatedFirstLevel method
 *
 * test form error display with associated model.
 *
 * @return void
 */
	public function testFormValidationAssociatedFirstLevel() {
		$this->markTestIncomplete('Need to revisit once models work again.');

		$data = array(
			'ValidateUser' => array('name' => 'mariano'),
			'ValidateProfile' => array('full_name' => 'Mariano Iglesias')
		);

		$result = $this->ValidateUser->create($data);
		$this->assertFalse(empty($result));
		$this->assertFalse($this->ValidateUser->validates());
		$this->assertFalse($this->ValidateUser->ValidateProfile->validates());

		$result = $this->Form->create('ValidateUser', array('type' => 'post', 'action' => 'add'));
		$encoding = strtolower(Configure::read('App.encoding'));
		$expected = array(
			'form' => array('method' => 'post', 'action' => '/validate_users/add', 'id', 'accept-charset' => $encoding),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->error(
			'ValidateUser.email', 'Invalid email', array('wrap' => false)
		);
		$this->assertEquals('Invalid email', $result);

		$result = $this->Form->error(
			'ValidateProfile.full_name', 'Invalid name', array('wrap' => false)
		);
		$this->assertEquals('Invalid name', $result);

		$result = $this->Form->error(
			'ValidateProfile.city', 'Invalid city', array('wrap' => false)
		);
		$this->assertEquals('Invalid city', $result);

		unset($this->ValidateUser->ValidateProfile);
		unset($this->ValidateUser);
	}

/**
 * testFormValidationAssociatedSecondLevel method
 *
 * test form error display with associated model.
 *
 * @return void
 */
	public function testFormValidationAssociatedSecondLevel() {
		$this->markTestIncomplete('Need to revisit once models work again.');

		$data = array(
			'ValidateUser' => array('name' => 'mariano'),
			'ValidateProfile' => array('full_name' => 'Mariano Iglesias'),
			'ValidateItem' => array('name' => 'Item')
		);

		$result = $this->ValidateUser->create($data);
		$this->assertFalse(empty($result));
		$this->assertFalse($this->ValidateUser->validates());
		$this->assertFalse($this->ValidateUser->ValidateProfile->validates());
		$this->assertFalse($this->ValidateUser->ValidateProfile->ValidateItem->validates());

		$result = $this->Form->create('ValidateUser', array('type' => 'post', 'action' => 'add'));
		$encoding = strtolower(Configure::read('App.encoding'));
		$expected = array(
			'form' => array('method' => 'post', 'action' => '/validate_users/add', 'id', 'accept-charset' => $encoding),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->error(
			'ValidateUser.email', 'Invalid email', array('wrap' => false)
		);
		$this->assertEquals('Invalid email', $result);

		$result = $this->Form->error(
			'ValidateProfile.full_name', 'Invalid name', array('wrap' => false)
		);
		$this->assertEquals('Invalid name', $result);

		$result = $this->Form->error(
			'ValidateProfile.city', 'Invalid city', array('wrap' => false)
		);

		$result = $this->Form->error(
			'ValidateItem.description', 'Invalid description', array('wrap' => false)
		);
		$this->assertEquals('Invalid description', $result);

		unset($this->ValidateUser->ValidateProfile->ValidateItem);
		unset($this->ValidateUser->ValidateProfile);
		unset($this->ValidateUser);
	}

/**
 * testFormValidationMultiRecord method
 *
 * test form error display with multiple records.
 *
 * @return void
 */
	public function testFormValidationMultiRecord() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$Contact->validationErrors[2] = array(
			'name' => array('The provided value is invalid')
		);
		$result = $this->Form->input('Contact.2.name');
		$expected = array(
			'div' => array('class' => 'input text error'),
			'label' => array('for' => 'Contact2Name'),
			'Name',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[2][name]', 'id' => 'Contact2Name',
				'class' => 'form-error', 'maxlength' => 255
			),
			array('div' => array('class' => 'error-message')),
			'The provided value is invalid',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testMultipleInputValidation method
 *
 * test multiple record form validation error display.
 *
 * @return void
 */
	public function testMultipleInputValidation() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$Address->validationErrors[0] = array(
			'title' => array('This field cannot be empty'),
			'first_name' => array('This field cannot be empty')
		);
		$Address->validationErrors[1] = array(
			'last_name' => array('You must have a last name')
		);
		$this->Form->create();

		$result = $this->Form->input('Address.0.title');
		$expected = array(
			'div' => array('class'),
			'label' => array('for'),
			'preg:/[^<]+/',
			'/label',
			'input' => array(
				'type' => 'text', 'name', 'id', 'class' => 'form-error'
			),
			array('div' => array('class' => 'error-message')),
			'This field cannot be empty',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Address.0.first_name');
		$expected = array(
			'div' => array('class'),
			'label' => array('for'),
			'preg:/[^<]+/',
			'/label',
			'input' => array('type' => 'text', 'name', 'id', 'class' => 'form-error'),
			array('div' => array('class' => 'error-message')),
			'This field cannot be empty',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Address.0.last_name');
		$expected = array(
			'div' => array('class'),
			'label' => array('for'),
			'preg:/[^<]+/',
			'/label',
			'input' => array('type' => 'text', 'name', 'id'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Address.1.last_name');
		$expected = array(
			'div' => array('class'),
			'label' => array('for'),
			'preg:/[^<]+/',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'preg:/[^<]+/',
				'id' => 'preg:/[^<]+/', 'class' => 'form-error'
			),
			array('div' => array('class' => 'error-message')),
			'You must have a last name',
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
		$this->markTestIncomplete('Need to revisit once models work again.');
		$result = $this->Form->input('ValidateUser.balance');
		$expected = array(
			'div' => array('class'),
			'label' => array('for'),
			'Balance',
			'/label',
			'input' => array('name', 'type' => 'number', 'id'),
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

		$result = $this->Form->input('Contact.email', array('div' => array('class' => false)));
		$expected = array(
			'<div',
			'label' => array('for' => 'ContactEmail'),
			'Email',
			'/label',
			array('input' => array(
				'type' => 'email', 'name' => 'Contact[email]',
				'id' => 'ContactEmail', 'maxlength' => 255
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->hidden('Contact.idontexist');
		$expected = array('input' => array(
				'type' => 'hidden', 'name' => 'Contact[idontexist]',
				'id' => 'ContactIdontexist'
		));
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.email', array('type' => 'text'));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ContactEmail'),
			'Email',
			'/label',
			array('input' => array(
				'type' => 'text', 'name' => 'Contact[email]',
				'id' => 'ContactEmail'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.5.email', array('type' => 'text'));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'Contact5Email'),
			'Email',
			'/label',
			array('input' => array(
				'type' => 'text', 'name' => 'Contact[5][email]',
				'id' => 'Contact5Email'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.password');
		$expected = array(
			'div' => array('class' => 'input password'),
			'label' => array('for' => 'ContactPassword'),
			'Password',
			'/label',
			array('input' => array(
				'type' => 'password', 'name' => 'Contact[password]',
				'id' => 'ContactPassword'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.email', array(
			'type' => 'file', 'class' => 'textbox'
		));
		$expected = array(
			'div' => array('class' => 'input file'),
			'label' => array('for' => 'ContactEmail'),
			'Email',
			'/label',
			array('input' => array(
				'type' => 'file', 'name' => 'Contact[email]', 'class' => 'textbox',
				'id' => 'ContactEmail'
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data = array('Contact' => array('phone' => 'Hello & World > weird chars'));
		$result = $this->Form->input('Contact.phone');
		$expected = array(
			'div' => array('class' => 'input tel'),
			'label' => array('for' => 'ContactPhone'),
			'Phone',
			'/label',
			array('input' => array(
				'type' => 'tel', 'name' => 'Contact[phone]',
				'value' => 'Hello &amp; World &gt; weird chars',
				'id' => 'ContactPhone', 'maxlength' => 255
			)),
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Model']['0']['OtherModel']['field'] = 'My value';
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

		unset($this->Form->request->data);

		$Contact->validationErrors['field'] = array('Badness!');
		$result = $this->Form->input('Contact.field');
		$expected = array(
			'div' => array('class' => 'input text error'),
			'label' => array('for' => 'ContactField'),
			'Field',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[field]',
				'id' => 'ContactField', 'class' => 'form-error'
			),
			array('div' => array('class' => 'error-message')),
			'Badness!',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.field', array(
			'div' => false, 'error' => array('attributes' => array('wrap' => 'span'))
		));
		$expected = array(
			'label' => array('for' => 'ContactField'),
			'Field',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[field]',
				'id' => 'ContactField', 'class' => 'form-error'
			),
			array('span' => array('class' => 'error-message')),
			'Badness!',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.field', array(
			'type' => 'text', 'error' => array('attributes' => array('class' => 'error'))
		));
		$expected = array(
			'div' => array('class' => 'input text error'),
			'label' => array('for' => 'ContactField'),
			'Field',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[field]',
				'id' => 'ContactField', 'class' => 'form-error'
			),
			array('div' => array('class' => 'error')),
			'Badness!',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.field', array(
			'div' => array('tag' => 'span'), 'error' => array('attributes' => array('wrap' => false))
		));
		$expected = array(
			'span' => array('class' => 'input text error'),
			'label' => array('for' => 'ContactField'),
			'Field',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[field]',
				'id' => 'ContactField', 'class' => 'form-error'
			),
			'Badness!',
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.field', array('after' => 'A message to you, Rudy'));
		$expected = array(
			'div' => array('class' => 'input text error'),
			'label' => array('for' => 'ContactField'),
			'Field',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[field]',
				'id' => 'ContactField', 'class' => 'form-error'
			),
			'A message to you, Rudy',
			array('div' => array('class' => 'error-message')),
			'Badness!',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->setEntity(null);
		$this->Form->setEntity('Contact.field');
		$result = $this->Form->input('Contact.field', array(
			'after' => 'A message to you, Rudy', 'error' => false
		));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ContactField'),
			'Field',
			'/label',
			'input' => array('type' => 'text', 'name' => 'Contact[field]', 'id' => 'ContactField', 'class' => 'form-error'),
			'A message to you, Rudy',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Object.field', array('after' => 'A message to you, Rudy'));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ObjectField'),
			'Field',
			'/label',
			'input' => array('type' => 'text', 'name' => 'Object[field]', 'id' => 'ObjectField'),
			'A message to you, Rudy',
			'/div'
		);
		$this->assertTags($result, $expected);

		$Contact->validationErrors['field'] = array('minLength');
		$result = $this->Form->input('Contact.field', array(
			'error' => array(
				'minLength' => 'Le login doit contenir au moins 2 caractères',
				'maxLength' => 'login too large'
			)
		));
		$expected = array(
			'div' => array('class' => 'input text error'),
			'label' => array('for' => 'ContactField'),
			'Field',
			'/label',
			'input' => array('type' => 'text', 'name' => 'Contact[field]', 'id' => 'ContactField', 'class' => 'form-error'),
			array('div' => array('class' => 'error-message')),
			'Le login doit contenir au moins 2 caractères',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);

		$Contact->validationErrors['field'] = array('maxLength');
		$result = $this->Form->input('Contact.field', array(
			'error' => array(
				'attributes' => array('wrap' => 'span', 'rel' => 'fake'),
				'minLength' => 'Le login doit contenir au moins 2 caractères',
				'maxLength' => 'login too large',
			)
		));
		$expected = array(
			'div' => array('class' => 'input text error'),
			'label' => array('for' => 'ContactField'),
			'Field',
			'/label',
			'input' => array('type' => 'text', 'name' => 'Contact[field]', 'id' => 'ContactField', 'class' => 'form-error'),
			array('span' => array('class' => 'error-message', 'rel' => 'fake')),
			'login too large',
			'/span',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test that inputs with 0 can be created.
 *
 * @return void
 */
	public function testInputZero() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->Form->create('User');
		$result = $this->Form->input('0');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'User0'), '/label',
			'input' => array('type' => 'text', 'name' => 'User[0]', 'id' => 'User0'),
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
		$this->markTestIncomplete('Need to revisit once models work again.');
		$result = $this->Form->input('User.active', array('label' => false, 'checked' => true));
		$expected = array(
			'div' => array('class' => 'input checkbox'),
			'input' => array('type' => 'hidden', 'name' => 'User[active]', 'value' => '0', 'id' => 'UserActive_'),
			array('input' => array('type' => 'checkbox', 'name' => 'User[active]', 'value' => '1', 'id' => 'UserActive', 'checked' => 'checked')),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('User.active', array('label' => false, 'checked' => 1));
		$expected = array(
			'div' => array('class' => 'input checkbox'),
			'input' => array('type' => 'hidden', 'name' => 'User[active]', 'value' => '0', 'id' => 'UserActive_'),
			array('input' => array('type' => 'checkbox', 'name' => 'User[active]', 'value' => '1', 'id' => 'UserActive', 'checked' => 'checked')),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('User.active', array('label' => false, 'checked' => '1'));
		$expected = array(
			'div' => array('class' => 'input checkbox'),
			'input' => array('type' => 'hidden', 'name' => 'User[active]', 'value' => '0', 'id' => 'UserActive_'),
			array('input' => array('type' => 'checkbox', 'name' => 'User[active]', 'value' => '1', 'id' => 'UserActive', 'checked' => 'checked')),
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
			'input' => array('type' => 'hidden', 'name' => 'data[User][disabled]', 'value' => '0', 'id' => 'UserDisabled_'),
			array('input' => array(
				'type' => 'checkbox',
				'name' => 'data[User][disabled]',
				'value' => '1',
				'id' => 'UserDisabled',
				'data-foo' => 'disabled'
			)),
			'label' => array('for' => 'UserDisabled'),
			'Disabled',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test form->input() with time types.
 *
 */
	public function testInputTime() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		extract($this->dateRegex);
		$result = $this->Form->input('Contact.created', array('type' => 'time', 'timeFormat' => 24));
		$result = explode(':', $result);
		$this->assertRegExp('/option value="23"/', $result[0]);
		$this->assertNotRegExp('/option value="24"/', $result[0]);

		$result = $this->Form->input('Contact.created', array('type' => 'time', 'timeFormat' => 24));
		$result = explode(':', $result);
		$this->assertRegExp('/option value="23"/', $result[0]);
		$this->assertNotRegExp('/option value="24"/', $result[0]);

		$result = $this->Form->input('Model.field', array(
			'type' => 'time', 'timeFormat' => 24, 'interval' => 15
		));
		$result = explode(':', $result);
		$this->assertNotRegExp('#<option value="12"[^>]*>12</option>#', $result[1]);
		$this->assertNotRegExp('#<option value="50"[^>]*>50</option>#', $result[1]);
		$this->assertRegExp('#<option value="15"[^>]*>15</option>#', $result[1]);

		$result = $this->Form->input('Model.field', array(
			'type' => 'time', 'timeFormat' => 12, 'interval' => 15
		));
		$result = explode(':', $result);
		$this->assertNotRegExp('#<option value="12"[^>]*>12</option>#', $result[1]);
		$this->assertNotRegExp('#<option value="50"[^>]*>50</option>#', $result[1]);
		$this->assertRegExp('#<option value="15"[^>]*>15</option>#', $result[1]);

		$result = $this->Form->input('prueba', array(
			'type' => 'time', 'timeFormat' => 24, 'dateFormat' => 'DMY', 'minYear' => 2008,
			'maxYear' => date('Y') + 1, 'interval' => 15
		));
		$result = explode(':', $result);
		$this->assertNotRegExp('#<option value="12"[^>]*>12</option>#', $result[1]);
		$this->assertNotRegExp('#<option value="50"[^>]*>50</option>#', $result[1]);
		$this->assertRegExp('#<option value="15"[^>]*>15</option>#', $result[1]);
		$this->assertRegExp('#<option value="30"[^>]*>30</option>#', $result[1]);

		$result = $this->Form->input('Random.start_time', array(
			'type' => 'time',
			'selected' => '18:15'
		));
		$this->assertContains('<option value="06" selected="selected">6</option>', $result);
		$this->assertContains('<option value="15" selected="selected">15</option>', $result);
		$this->assertContains('<option value="pm" selected="selected">pm</option>', $result);

		$result = $this->Form->input('published', array('type' => 'time'));
		$now = strtotime('now');
		$this->assertContains('<option value="' . date('h', $now) . '" selected="selected">' . date('g', $now) . '</option>', $result);

		$now = strtotime('2013-03-09 00:42:21');
		$result = $this->Form->input('published', array('type' => 'time', 'selected' => $now));
		$this->assertContains('<option value="12" selected="selected">12</option>', $result);
		$this->assertContains('<option value="42" selected="selected">42</option>', $result);
	}

/**
 * Test interval + selected near the hour roll over.
 *
 * @return void
 */
	public function testTimeSelectedWithInterval() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$result = $this->Form->input('Model.start_time', array(
			'type' => 'time',
			'interval' => 15,
			'selected' => array('hour' => '3', 'min' => '57', 'meridian' => 'pm')
		));
		$this->assertContains('<option value="04" selected="selected">4</option>', $result);
		$this->assertContains('<option value="00" selected="selected">00</option>', $result);
		$this->assertContains('<option value="pm" selected="selected">pm</option>', $result);

		$result = $this->Form->input('Model.start_time', array(
			'type' => 'time',
			'interval' => 15,
			'selected' => '2012-10-23 15:57:00'
		));
		$this->assertContains('<option value="04" selected="selected">4</option>', $result);
		$this->assertContains('<option value="00" selected="selected">00</option>', $result);
		$this->assertContains('<option value="pm" selected="selected">pm</option>', $result);

		$result = $this->Form->input('Model.start_time', array(
			'timeFormat' => 24,
			'type' => 'time',
			'interval' => 15,
			'selected' => '15:57'
		));
		$this->assertContains('<option value="16" selected="selected">16</option>', $result);
		$this->assertContains('<option value="00" selected="selected">00</option>', $result);

		$result = $this->Form->input('Model.start_time', array(
			'timeFormat' => 24,
			'type' => 'time',
			'interval' => 15,
			'selected' => '23:57'
		));
		$this->assertContains('<option value="00" selected="selected">0</option>', $result);
		$this->assertContains('<option value="00" selected="selected">00</option>', $result);

		$result = $this->Form->input('Model.created', array(
			'timeFormat' => 24,
			'type' => 'datetime',
			'interval' => 15,
			'selected' => '2012-09-30 23:56'
		));
		$this->assertContains('<option value="2012" selected="selected">2012</option>', $result);
		$this->assertContains('<option value="10" selected="selected">October</option>', $result);
		$this->assertContains('<option value="01" selected="selected">1</option>', $result);
		$this->assertContains('<option value="00" selected="selected">0</option>', $result);
		$this->assertContains('<option value="00" selected="selected">00</option>', $result);
	}

/**
 * Test time with selected values around 12:xx:xx
 *
 * @return void
 */
	public function testTimeSelectedWithIntervalTwelve() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$result = $this->Form->input('Model.start_time', array(
			'type' => 'time',
			'timeFormat' => 12,
			'interval' => 15,
			'selected' => '00:00:00'
		));
		$this->assertContains('<option value="12" selected="selected">12</option>', $result);
		$this->assertContains('<option value="00" selected="selected">00</option>', $result);
		$this->assertContains('<option value="am" selected="selected">am</option>', $result);

		$result = $this->Form->input('Model.start_time', array(
			'type' => 'time',
			'timeFormat' => 12,
			'interval' => 15,
			'selected' => '12:00:00'
		));
		$this->assertContains('<option value="12" selected="selected">12</option>', $result);
		$this->assertContains('<option value="00" selected="selected">00</option>', $result);
		$this->assertContains('<option value="pm" selected="selected">pm</option>', $result);

		$result = $this->Form->input('Model.start_time', array(
			'type' => 'time',
			'timeFormat' => 12,
			'interval' => 15,
			'selected' => '12:15:00'
		));
		$this->assertContains('<option value="12" selected="selected">12</option>', $result);
		$this->assertContains('<option value="15" selected="selected">15</option>', $result);
		$this->assertContains('<option value="pm" selected="selected">pm</option>', $result);
	}

/**
 * Test interval & timeFormat = 12
 *
 * @return void
 */
	public function testInputTimeWithIntervalAnd12HourFormat() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$result = $this->Form->input('Model.start_time', array(
			'type' => 'time',
			'timeFormat' => 12,
			'interval' => 5,
			'selected' => array('hour' => '4', 'min' => '30', 'meridian' => 'pm')
		));
		$this->assertContains('<option value="04" selected="selected">4</option>', $result);
		$this->assertContains('<option value="30" selected="selected">30</option>', $result);
		$this->assertContains('<option value="pm" selected="selected">pm</option>', $result);

		$result = $this->Form->input('Model.start_time', array(
			'type' => 'time',
			'timeFormat' => '12',
			'interval' => 5,
			'selected' => '2013-04-19 16:30:00'
		));
		$this->assertContains('<option value="04" selected="selected">4</option>', $result);
		$this->assertContains('<option value="30" selected="selected">30</option>', $result);
		$this->assertContains('<option value="pm" selected="selected">pm</option>', $result);

		$result = $this->Form->input('Model.start_time', array(
			'type' => 'time',
			'timeFormat' => '12',
			'interval' => 10,
			'selected' => '2013-05-19 00:33:00'
		));
		$this->assertContains('<option value="12" selected="selected">12</option>', $result);
		$this->assertContains('<option value="30" selected="selected">30</option>', $result);
		$this->assertContains('<option value="am" selected="selected">am</option>', $result);

		$result = $this->Form->input('Model.start_time', array(
			'type' => 'time',
			'timeFormat' => '12',
			'interval' => 10,
			'selected' => '2013-05-19 13:33:00'
		));
		$this->assertContains('<option value="01" selected="selected">1</option>', $result);
		$this->assertContains('<option value="30" selected="selected">30</option>', $result);
		$this->assertContains('<option value="pm" selected="selected">pm</option>', $result);

		$result = $this->Form->input('Model.start_time', array(
			'type' => 'time',
			'timeFormat' => '12',
			'interval' => 10,
			'selected' => '2013-05-19 01:33:00'
		));
		$this->assertContains('<option value="01" selected="selected">1</option>', $result);
		$this->assertContains('<option value="30" selected="selected">30</option>', $result);
		$this->assertContains('<option value="am" selected="selected">am</option>', $result);
	}

/**
 * test form->input() with datetime, date and time types
 *
 * @return void
 */
	public function testInputDatetime() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		extract($this->dateRegex);
		$result = $this->Form->input('prueba', array(
			'type' => 'datetime', 'timeFormat' => 24, 'dateFormat' => 'DMY', 'minYear' => 2008,
			'maxYear' => date('Y') + 1, 'interval' => 15
		));
		$result = explode(':', $result);
		$this->assertNotRegExp('#<option value="12"[^>]*>12</option>#', $result[1]);
		$this->assertNotRegExp('#<option value="50"[^>]*>50</option>#', $result[1]);
		$this->assertRegExp('#<option value="15"[^>]*>15</option>#', $result[1]);
		$this->assertRegExp('#<option value="30"[^>]*>30</option>#', $result[1]);

		//related to ticket #5013
		$result = $this->Form->input('Contact.date', array(
			'type' => 'date', 'class' => 'customClass', 'onChange' => 'function(){}'
		));
		$this->assertRegExp('/class="customClass"/', $result);
		$this->assertRegExp('/onChange="function\(\)\{\}"/', $result);

		$result = $this->Form->input('Contact.date', array(
			'type' => 'date', 'id' => 'customId', 'onChange' => 'function(){}'
		));
		$this->assertRegExp('/id="customIdDay"/', $result);
		$this->assertRegExp('/id="customIdMonth"/', $result);
		$this->assertRegExp('/onChange="function\(\)\{\}"/', $result);

		$result = $this->Form->input('Model.field', array(
			'type' => 'datetime', 'timeFormat' => 24, 'id' => 'customID'
		));
		$this->assertRegExp('/id="customIDDay"/', $result);
		$this->assertRegExp('/id="customIDHour"/', $result);
		$result = explode('</select><select', $result);
		$result = explode(':', $result[1]);
		$this->assertRegExp('/option value="23"/', $result[0]);
		$this->assertNotRegExp('/option value="24"/', $result[0]);

		$result = $this->Form->input('Model.field', array(
			'type' => 'datetime', 'timeFormat' => 12
		));
		$result = explode('</select><select', $result);
		$result = explode(':', $result[1]);
		$this->assertRegExp('/option value="12"/', $result[0]);
		$this->assertNotRegExp('/option value="13"/', $result[0]);

		$this->Form->request->data = array('Contact' => array('created' => null));
		$result = $this->Form->input('Contact.created', array('empty' => 'Date Unknown'));
		$expected = array(
			'div' => array('class' => 'input date'),
			'label' => array('for' => 'ContactCreatedMonth'),
			'Created',
			'/label',
			array('select' => array('name' => 'Contact[created][month]', 'id' => 'ContactCreatedMonth')),
			array('option' => array('value' => '')), 'Date Unknown', '/option',
			$monthsRegex,
			'/select', '-',
			array('select' => array('name' => 'Contact[created][day]', 'id' => 'ContactCreatedDay')),
			array('option' => array('value' => '')), 'Date Unknown', '/option',
			$daysRegex,
			'/select', '-',
			array('select' => array('name' => 'Contact[created][year]', 'id' => 'ContactCreatedYear')),
			array('option' => array('value' => '')), 'Date Unknown', '/option',
			$yearsRegex,
			'/select',
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data = array('Contact' => array('created' => null));
		$result = $this->Form->input('Contact.created', array('type' => 'datetime', 'dateFormat' => 'NONE'));
		$this->assertRegExp('/for\="ContactCreatedHour"/', $result);

		$this->Form->request->data = array('Contact' => array('created' => null));
		$result = $this->Form->input('Contact.created', array('type' => 'datetime', 'timeFormat' => 'NONE'));
		$this->assertRegExp('/for\="ContactCreatedMonth"/', $result);

		$result = $this->Form->input('Contact.created', array(
			'type' => 'date',
			'id' => array('day' => 'created-day', 'month' => 'created-month', 'year' => 'created-year'),
			'timeFormat' => 'NONE'
		));
		$this->assertRegExp('/for\="created-month"/', $result);
	}

/**
 * Test generating checkboxes with disabled elements.
 *
 * @return void
 */
	public function testInputCheckboxWithDisabledElements() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$options = array(1 => 'One', 2 => 'Two', '3' => 'Three');
		$result = $this->Form->input('Contact.multiple', array('multiple' => 'checkbox', 'disabled' => 'disabled', 'options' => $options));

		$expected = array(
			array('div' => array('class' => 'input select')),
			array('label' => array('for' => "ContactMultiple")),
			'Multiple',
			'/label',
			array('input' => array('type' => 'hidden', 'name' => "Contact[multiple]", 'value' => '', 'id' => "ContactMultiple")),
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => "Contact[multiple][]", 'value' => 1, 'disabled' => 'disabled', 'id' => "ContactMultiple1")),
			array('label' => array('for' => "ContactMultiple1")),
			'One',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => "Contact[multiple][]", 'value' => 2, 'disabled' => 'disabled', 'id' => "ContactMultiple2")),
			array('label' => array('for' => "ContactMultiple2")),
			'Two',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => "Contact[multiple][]", 'value' => 3, 'disabled' => 'disabled', 'id' => "ContactMultiple3")),
			array('label' => array('for' => "ContactMultiple3")),
			'Three',
			'/label',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->radio('Model.field', array('1/2' => 'half'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'Model[field]', 'value' => '', 'id' => 'ModelField_'),
			array('input' => array('type' => 'radio', 'name' => 'Model[field]', 'value' => '1/2', 'id' => 'ModelField12')),
			'label' => array('for' => 'ModelField12'),
			'half',
			'/label'
		);
		$this->assertTags($result, $expected);

		$disabled = array('2', 3);

		$expected = array(
			array('div' => array('class' => 'input select')),
			array('label' => array('for' => "ContactMultiple")),
			'Multiple',
			'/label',
			array('input' => array('type' => 'hidden', 'name' => "Contact[multiple]", 'value' => '', 'id' => "ContactMultiple")),
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => "Contact[multiple][]", 'value' => 1, 'id' => "ContactMultiple1")),
			array('label' => array('for' => "ContactMultiple1")),
			'One',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => "Contact[multiple][]", 'value' => 2, 'disabled' => 'disabled', 'id' => "ContactMultiple2")),
			array('label' => array('for' => "ContactMultiple2")),
			'Two',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => "Contact[multiple][]", 'value' => 3, 'disabled' => 'disabled', 'id' => "ContactMultiple3")),
			array('label' => array('for' => "ContactMultiple3")),
			'Three',
			'/label',
			'/div',
			'/div'
		);
		$result = $this->Form->input('Contact.multiple', array('multiple' => 'checkbox', 'disabled' => $disabled, 'options' => $options));
		$this->assertTags($result, $expected);

		// make sure 50 does only disable 50, and not 50f5c0cf
		$options = array('50' => 'Fifty', '50f5c0cf' => 'Stringy');
		$disabled = array(50);

		$expected = array(
			array('div' => array('class' => 'input select')),
			array('label' => array('for' => "ContactMultiple")),
			'Multiple',
			'/label',
			array('input' => array('type' => 'hidden', 'name' => "Contact[multiple]", 'value' => '', 'id' => "ContactMultiple")),
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => "Contact[multiple][]", 'value' => 50, 'disabled' => 'disabled', 'id' => "ContactMultiple50")),
			array('label' => array('for' => "ContactMultiple50")),
			'Fifty',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => "Contact[multiple][]", 'value' => '50f5c0cf', 'id' => "ContactMultiple50f5c0cf")),
			array('label' => array('for' => "ContactMultiple50f5c0cf")),
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
		$this->markTestIncomplete('Need to revisit once models work again.');
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

		$this->View->viewVars['users'] = array('value' => 'good', 'other' => 'bad');
		$this->Form->request->data = array('Model' => array('user_id' => 'value'));

		$result = $this->Form->input('Model.user_id', array('empty' => true));
		$expected = array(
			'div' => array('class' => 'input select'),
			'label' => array('for' => 'ModelUserId'),
			'User',
			'/label',
			'select' => array('name' => 'Model[user_id]', 'id' => 'ModelUserId'),
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
			'label' => array('for' => 'ThingUserId'),
			'User',
			'/label',
			'select' => array('name' => 'Thing[user_id]', 'id' => 'ThingUserId'),
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
			'label' => array('for' => 'ThingUserId'),
			'User',
			'/label',
			'select' => array('name' => 'Thing[user_id]', 'id' => 'ThingUserId'),
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

		$this->View->viewVars['users'] = array('value' => 'good', 'other' => 'bad');
		$this->Form->request->data = array('User' => array('User' => array('value')));
		$result = $this->Form->input('User.User', array('empty' => true));
		$expected = array(
			'div' => array('class' => 'input select'),
			'label' => array('for' => 'UserUser'),
			'User',
			'/label',
			'input' => array('type' => 'hidden', 'name' => 'User[User]', 'value' => '', 'id' => 'UserUser_'),
			'select' => array('name' => 'User[User][]', 'id' => 'UserUser', 'multiple' => 'multiple'),
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

		$this->Form->data = array();
		$result = $this->Form->input('Publisher.id', array(
				'label'		=> 'Publisher',
				'type'		=> 'select',
				'multiple'	=> 'checkbox',
				'options'	=> array('Value 1' => 'Label 1', 'Value 2' => 'Label 2')
		));
		$expected = array(
			array('div' => array('class' => 'input select')),
				array('label' => array('for' => 'PublisherId')),
				'Publisher',
				'/label',
				'input' => array('type' => 'hidden', 'name' => 'Publisher[id]', 'value' => '', 'id' => 'PublisherId'),
				array('div' => array('class' => 'checkbox')),
				array('input' => array('type' => 'checkbox', 'name' => 'Publisher[id][]', 'value' => 'Value 1', 'id' => 'PublisherIdValue1')),
				array('label' => array('for' => 'PublisherIdValue1')),
				'Label 1',
				'/label',
				'/div',
				array('div' => array('class' => 'checkbox')),
				array('input' => array('type' => 'checkbox', 'name' => 'Publisher[id][]', 'value' => 'Value 2', 'id' => 'PublisherIdValue2')),
				array('label' => array('for' => 'PublisherIdValue2')),
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
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->Form->create('User');
		$this->Form->fieldset = array(
			'User' => array(
				'fields' => array(
					'model_id' => array('type' => 'integer')
				),
				'validates' => array(),
				'key' => 'model_id'
			)
		);
		$result = $this->Form->input('model_id');
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'User[model_id]', 'id' => 'UserModelId'),
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that overriding the magic select type widget is possible
 *
 * @return void
 */
	public function testInputOverridingMagicSelectType() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->View->viewVars['users'] = array('value' => 'good', 'other' => 'bad');
		$result = $this->Form->input('Model.user_id', array('type' => 'text'));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ModelUserId'), 'User', '/label',
			'input' => array('name' => 'Model[user_id]', 'type' => 'text', 'id' => 'ModelUserId'),
			'/div'
		);
		$this->assertTags($result, $expected);

		//Check that magic types still work for plural/singular vars
		$this->View->viewVars['types'] = array('value' => 'good', 'other' => 'bad');
		$result = $this->Form->input('Model.type');
		$expected = array(
			'div' => array('class' => 'input select'),
			'label' => array('for' => 'ModelType'), 'Type', '/label',
			'select' => array('name' => 'Model[type]', 'id' => 'ModelType'),
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
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->View->viewVars['users'] = array('value' => 'good', 'other' => 'bad');
		$result = $this->Form->input('Model.user', array('type' => 'checkbox'));
		$expected = array(
			'div' => array('class' => 'input checkbox'),
			array('input' => array(
				'type' => 'hidden',
				'name' => 'data[Model][user]',
				'id' => 'ModelUser_',
				'value' => 0,
			)),
			array('input' => array(
				'name' => 'data[Model][user]',
				'type' => 'checkbox',
				'id' => 'ModelUser',
				'value' => 1
			)),
			'label' => array('for' => 'ModelUser'), 'User', '/label',
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
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->View->viewVars['balances'] = array(0 => 'nothing', 1 => 'some', 100 => 'a lot');
		$this->Form->request->data = array('ValidateUser' => array('balance' => 1));
		$result = $this->Form->input('ValidateUser.balance');
		$expected = array(
			'div' => array('class' => 'input select'),
			'label' => array('for' => 'ValidateUserBalance'),
			'Balance',
			'/label',
			'select' => array('name' => 'ValidateUser[balance]', 'id' => 'ValidateUserBalance'),
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
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->View->viewVars['users'] = array('value' => 'good', 'other' => 'bad');
		$result = $this->Form->input('Model.user_id', array('type' => 'radio'));
		$this->assertRegExp('/input type="radio"/', $result);
	}

/**
 * fields with the same name as the model should work.
 *
 * @return void
 */
	public function testInputWithMatchingFieldAndModelName() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->Form->create('User');
		$this->Form->fieldset = array(
			'User' => array(
				'fields' => array(
					'User' => array('type' => 'text')
				),
				'validates' => array(),
				'key' => 'id'
			)
		);
		$this->Form->request->data['User']['User'] = 'ABC, Inc.';
		$result = $this->Form->input('User', array('type' => 'text'));
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'UserUser'), 'User', '/label',
			'input' => array('name' => 'User[User]', 'type' => 'text', 'id' => 'UserUser', 'value' => 'ABC, Inc.'),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testFormInputs method
 *
 * test correct results from form::inputs().
 *
 * @return void
 */
	public function testFormInputs() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->Form->create('Cake\Test\TestCase\View\Helper\Contact');
		$result = $this->Form->inputs('The Legend');
		$expected = array(
			'<fieldset',
			'<legend',
			'The Legend',
			'/legend',
			'*/fieldset',
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->inputs(array('legend' => 'Field of Dreams', 'fieldset' => 'classy-stuff'));
		$expected = array(
			'fieldset' => array('class' => 'classy-stuff'),
			'<legend',
			'Field of Dreams',
			'/legend',
			'*/fieldset'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->inputs(null, null, array('legend' => 'Field of Dreams', 'fieldset' => 'classy-stuff'));
		$this->assertTags($result, $expected);

		$result = $this->Form->inputs('Field of Dreams', null, array('fieldset' => 'classy-stuff'));
		$this->assertTags($result, $expected);

		$this->Form->create('Contact');
		$this->Form->request['prefix'] = 'admin';
		$this->Form->request['action'] = 'admin_edit';
		$result = $this->Form->inputs();
		$expected = array(
			'<fieldset',
			'<legend',
			'Edit Contact',
			'/legend',
			'*/fieldset',
		);
		$this->assertTags($result, $expected);

		$this->Form->create('Contact');
		$result = $this->Form->inputs(false);
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'Contact[id]', 'id' => 'ContactId'),
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input email')),
			'*/div',
			array('div' => array('class' => 'input tel')),
			'*/div',
			array('div' => array('class' => 'input password')),
			'*/div',
			array('div' => array('class' => 'input date')),
			'*/div',
			array('div' => array('class' => 'input date')),
			'*/div',
			array('div' => array('class' => 'input datetime')),
			'*/div',
			array('div' => array('class' => 'input number')),
			'*/div',
			array('div' => array('class' => 'input select')),
			'*/div',
		);
		$this->assertTags($result, $expected);

		$this->Form->create('Contact');
		$result = $this->Form->inputs(array('fieldset' => false, 'legend' => false));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'Contact[id]', 'id' => 'ContactId'),
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input email')),
			'*/div',
			array('div' => array('class' => 'input tel')),
			'*/div',
			array('div' => array('class' => 'input password')),
			'*/div',
			array('div' => array('class' => 'input date')),
			'*/div',
			array('div' => array('class' => 'input date')),
			'*/div',
			array('div' => array('class' => 'input datetime')),
			'*/div',
			array('div' => array('class' => 'input number')),
			'*/div',
			array('div' => array('class' => 'input select')),
			'*/div',
		);
		$this->assertTags($result, $expected);

		$this->Form->create('Contact');
		$result = $this->Form->inputs(null, null, array('fieldset' => false));
		$this->assertTags($result, $expected);

		$this->Form->create('Contact');
		$result = $this->Form->inputs(array('fieldset' => true, 'legend' => false));
		$expected = array(
			'fieldset' => array(),
			'input' => array('type' => 'hidden', 'name' => 'Contact[id]', 'id' => 'ContactId'),
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input email')),
			'*/div',
			array('div' => array('class' => 'input tel')),
			'*/div',
			array('div' => array('class' => 'input password')),
			'*/div',
			array('div' => array('class' => 'input date')),
			'*/div',
			array('div' => array('class' => 'input date')),
			'*/div',
			array('div' => array('class' => 'input datetime')),
			'*/div',
			array('div' => array('class' => 'input number')),
			'*/div',
			array('div' => array('class' => 'input select')),
			'*/div',
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$this->Form->create('Contact');
		$result = $this->Form->inputs(array('fieldset' => false, 'legend' => 'Hello'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'Contact[id]', 'id' => 'ContactId'),
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input email')),
			'*/div',
			array('div' => array('class' => 'input tel')),
			'*/div',
			array('div' => array('class' => 'input password')),
			'*/div',
			array('div' => array('class' => 'input date')),
			'*/div',
			array('div' => array('class' => 'input date')),
			'*/div',
			array('div' => array('class' => 'input datetime')),
			'*/div',
			array('div' => array('class' => 'input number')),
			'*/div',
			array('div' => array('class' => 'input select')),
			'*/div',
		);
		$this->assertTags($result, $expected);

		$this->Form->create('Contact');
		$result = $this->Form->inputs(null, null, array('fieldset' => false, 'legend' => 'Hello'));
		$this->assertTags($result, $expected);

		$this->Form->create('Contact');
		$result = $this->Form->inputs('Hello');
		$expected = array(
			'fieldset' => array(),
			'legend' => array(),
			'Hello',
			'/legend',
			'input' => array('type' => 'hidden', 'name' => 'Contact[id]', 'id' => 'ContactId'),
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input email')),
			'*/div',
			array('div' => array('class' => 'input tel')),
			'*/div',
			array('div' => array('class' => 'input password')),
			'*/div',
			array('div' => array('class' => 'input date')),
			'*/div',
			array('div' => array('class' => 'input date')),
			'*/div',
			array('div' => array('class' => 'input datetime')),
			'*/div',
			array('div' => array('class' => 'input number')),
			'*/div',
			array('div' => array('class' => 'input select')),
			'*/div',
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$this->Form->create('Contact');
		$result = $this->Form->inputs(array('legend' => 'Hello'));
		$expected = array(
			'fieldset' => array(),
			'legend' => array(),
			'Hello',
			'/legend',
			'input' => array('type' => 'hidden', 'name' => 'Contact[id]', 'id' => 'ContactId'),
			array('div' => array('class' => 'input text')),
			'*/div',
			array('div' => array('class' => 'input email')),
			'*/div',
			array('div' => array('class' => 'input tel')),
			'*/div',
			array('div' => array('class' => 'input password')),
			'*/div',
			array('div' => array('class' => 'input date')),
			'*/div',
			array('div' => array('class' => 'input date')),
			'*/div',
			array('div' => array('class' => 'input datetime')),
			'*/div',
			array('div' => array('class' => 'input number')),
			'*/div',
			array('div' => array('class' => 'input select')),
			'*/div',
			'/fieldset'
		);
		$this->assertTags($result, $expected);

		$this->Form->create('Contact');
		$result = $this->Form->inputs(null, null, array('legend' => 'Hello'));
		$this->assertTags($result, $expected);
		$this->Form->end();

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
			array(),
			array('legend' => false)
		);
		$this->assertTags($result, $expected);
	}

/**
 * Tests inputs() works with plugin models
 *
 * @return void
 */
	public function testInputsPluginModel() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->loadFixtures('Post');
		Plugin::load('TestPlugin');
		$this->Form->request['models'] = array(
			'TestPluginPost' => array('plugin' => 'TestPlugin', 'className' => 'TestPluginPost')
		);
		$this->Form->create('TestPlugin.TestPluginPost');
		$result = $this->Form->inputs();

		$this->assertContains('TestPluginPost[id]', $result);
		$this->assertContains('TestPluginPost[author_id]', $result);
		$this->assertContains('TestPluginPost[title]', $result);
		$this->assertEquals('TestPluginPost', $this->Form->model());
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
 * test error options when using form->input();
 *
 * @return void
 */
	public function testInputErrorEscape() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->Form->create('ValidateProfile');
		$ValidateProfile->validationErrors['city'] = array('required<br>');
		$result = $this->Form->input('city', array('error' => array('attributes' => array('escape' => true))));
		$this->assertRegExp('/required&lt;br&gt;/', $result);

		$result = $this->Form->input('city', array('error' => array('attributes' => array('escape' => false))));
		$this->assertRegExp('/required<br>/', $result);
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

		$result = $this->Form->radio('Employee.gender', array('male' => 'Male', 'female' => 'Female'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'Employee[gender]', 'value' => ''),
			array('input' => array('type' => 'radio', 'name' => 'Employee[gender]', 'value' => 'male', 'id' => 'employee-gender-male')),
			array('label' => array('for' => 'employee-gender-male')),
			'Male',
			'/label',
			array('input' => array('type' => 'radio', 'name' => 'Employee[gender]', 'value' => 'female', 'id' => 'employee-gender-female')),
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
 * testDomIdSuffix method
 *
 * @return void
 */
	public function testDomIdSuffix() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$result = $this->Form->domIdSuffix('1 string with 1$-dollar signs');
		$this->assertEquals('1StringWith1$-dollarSigns', $result);

		$result = $this->Form->domIdSuffix('<abc x="foo" y=\'bar\'>');
		$this->assertEquals('AbcX=FooY=Bar', $result);

		$result = $this->Form->domIdSuffix('1 string with 1$-dollar signs', 'xhtml');
		$this->assertEquals('1StringWith1-dollarSigns', $result);

		$result = $this->Form->domIdSuffix('<abc x="foo" y=\'bar\'>', 'xhtml');
		$this->assertEquals('AbcXFooYBar', $result);
	}

/**
 * testDomIdSuffixCollisionResolvement()
 *
 * @return void
 */
	public function testDomIdSuffixCollisionResolvement() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$result = $this->Form->domIdSuffix('a>b');
		$this->assertEquals('AB', $result);

		$result = $this->Form->domIdSuffix('a<b');
		$this->assertEquals('AB1', $result);

		$result = $this->Form->domIdSuffix('a\'b');
		$this->assertEquals('AB2', $result);

		$result = $this->Form->domIdSuffix('1 string with 1$-dollar', 'xhtml');
		$this->assertEquals('1StringWith1-dollar', $result);

		$result = $this->Form->domIdSuffix('1 string with 1€-dollar', 'xhtml');
		$this->assertEquals('1StringWith1-dollar1', $result);

		$result = $this->Form->domIdSuffix('1 string with 1$-dollar', 'xhtml');
		$this->assertEquals('1StringWith1-dollar2', $result);
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
			array('multiple' => true)
		);
		$expected = array(
			'input' => array(
				'type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => ''
			),
			'select' => array(
				'name' => 'Model[multi_field][]',
				'multiple' => 'multiple'
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
			array('multiple' => 'multiple')
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test that a checkbox can have 0 for the value and 1 for the hidden input.
 *
 * @return void
 */
	public function testCheckboxZeroValue() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$result = $this->Form->input('User.get_spam', array(
			'type' => 'checkbox',
			'value' => '0',
			'hiddenField' => '1',
		));
		$expected = array(
			'div' => array('class' => 'input checkbox'),
			array('input' => array(
				'type' => 'hidden', 'name' => 'data[User][get_spam]',
				'value' => '1', 'id' => 'UserGetSpam_'
			)),
			array('input' => array(
				'type' => 'checkbox', 'name' => 'data[User][get_spam]',
				'value' => '0', 'id' => 'UserGetSpam'
			)),
			'label' => array('for' => 'UserGetSpam'),
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
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->View->viewVars['contactTags'] = array(
			1 => 'blue',
			2 => 'red',
			3 => 'green'
		);
		$this->Form->request->data = array(
			'Contact' => array(),
			'ContactTag' => array(
				array(
					'id' => '1',
					'name' => 'blue'
				),
				array(
					'id' => 3,
					'name' => 'green'
				)
			)
		);
		$this->Form->create('Contact');
		$result = $this->Form->input('ContactTag', array('div' => false, 'label' => false));
		$expected = array(
			'input' => array(
				'type' => 'hidden', 'name' => 'ContactTag[ContactTag]', 'value' => '', 'id' => 'ContactTagContactTag_'
			),
			'select' => array(
				'name' => 'ContactTag[ContactTag][]', 'id' => 'ContactTagContactTag',
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
			'/select'
		);
		$this->assertTags($result, $expected);

		// make sure only 50 is selected, and not 50f5c0cf
		$this->View->viewVars['contactTags'] = array(
			'1' => 'blue',
			'50f5c0cf' => 'red',
			'50' => 'green'
		);
		$this->Form->request->data = array(
			'Contact' => array(),
			'ContactTag' => array(
				array(
					'id' => 1,
					'name' => 'blue'
				),
				array(
					'id' => 50,
					'name' => 'green'
				)
			)
		);
		$this->Form->create('Contact');
		$result = $this->Form->input('ContactTag', array('div' => false, 'label' => false));
		$expected = array(
			'input' => array(
				'type' => 'hidden', 'name' => 'ContactTag[ContactTag]', 'value' => '', 'id' => 'ContactTagContactTag_'
			),
			'select' => array(
				'name' => 'ContactTag[ContactTag][]', 'id' => 'ContactTagContactTag',
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
			'/select'
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
		$this->markTestIncomplete('Need to revisit once models work again.');
		$result = $this->Form->input('Model.multi_field', array(
			'options' => array('first', 'second', 'third'),
			'multiple' => 'checkbox'
		));
		$expected = array(
			array('div' => array('class' => 'input select')),
			array('label' => array('for' => 'ModelMultiField')),
			'Multi Field',
			'/label',
			'input' => array('type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => '', 'id' => 'ModelMultiField'),
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'Model[multi_field][]', 'value' => '0', 'id' => 'ModelMultiField0')),
			array('label' => array('for' => 'ModelMultiField0')),
			'first',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'Model[multi_field][]', 'value' => '1', 'id' => 'ModelMultiField1')),
			array('label' => array('for' => 'ModelMultiField1')),
			'second',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'Model[multi_field][]', 'value' => '2', 'id' => 'ModelMultiField2')),
			array('label' => array('for' => 'ModelMultiField2')),
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
			array('label' => array('for' => 'ModelMultiField')),
			'Multi Field',
			'/label',
			'input' => array('type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => '', 'id' => 'ModelMultiField'),
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'Model[multi_field][]', 'value' => 'a', 'id' => 'ModelMultiFieldA')),
			array('label' => array('for' => 'ModelMultiFieldA')),
			'first',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'Model[multi_field][]', 'value' => 'b', 'id' => 'ModelMultiFieldB')),
			array('label' => array('for' => 'ModelMultiFieldB')),
			'second',
			'/label',
			'/div',
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'Model[multi_field][]', 'value' => 'c', 'id' => 'ModelMultiFieldC')),
			array('label' => array('for' => 'ModelMultiFieldC')),
			'third',
			'/label',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Model.multi_field', array(
			'options' => array('1' => 'first'),
			'multiple' => 'checkbox',
			'label' => false,
			'div' => false
		));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => '', 'id' => 'ModelMultiField'),
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'Model[multi_field][]', 'value' => '1', 'id' => 'ModelMultiField1')),
			array('label' => array('for' => 'ModelMultiField1')),
			'first',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Model.multi_field', array(
			'options' => array('2' => 'second'),
			'multiple' => 'checkbox',
			'label' => false,
			'div' => false
		));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'Model[multi_field]', 'value' => '', 'id' => 'ModelMultiField'),
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'checkbox', 'name' => 'Model[multi_field][]', 'value' => '2', 'id' => 'ModelMultiField2')),
			array('label' => array('for' => 'ModelMultiField2')),
			'second',
			'/label',
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

		$result = $this->Form->checkbox('Model.field', array('id' => 'theID', 'value' => 'myvalue'));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'Model[field]', 'value' => '0'),
			array('input' => array('type' => 'checkbox', 'name' => 'Model[field]', 'value' => 'myvalue', 'id' => 'theID'))
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

			array('select' => array('name' => 'Contact[date][year]')),
			$yearsRegex,
			array('option' => array('value' => date('Y', $now), 'selected' => 'selected')),
			date('Y', $now),
			'/option',
			'*/select',

			array('select' => array('name' => 'Contact[date][hour]')),
			$hoursRegex,
			array('option' => array('value' => date('h', $now), 'selected' => 'selected')),
			date('g', $now),
			'/option',
			'*/select',

			array('select' => array('name' => 'Contact[date][minute]')),
			$minutesRegex,
			array('option' => array('value' => date('i', $now), 'selected' => 'selected')),
			date('i', $now),
			'/option',
			'*/select',

			array('select' => array('name' => 'Contact[date][meridian]')),
			$meridianRegex,
			array('option' => array('value' => date('a', $now), 'selected' => 'selected')),
			date('a', $now),
			'/option',
			'*/select'
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
			'empty' => true,
		));
		$expected = array(
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

			array('select' => array('name' => 'Contact[date][year]')),
			$yearsRegex,
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

			array('select' => array('name' => 'Contact[date][year]')),
			$yearsRegex,
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
		$this->markTestIncomplete('Need to revisit soon.');
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
		$this->assertContains('Contact[1][updated][meridian]', $result);
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
			array('option' => array('selected' => 'selected',  'value' => '')),
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
			array('option' => array('selected' => 'selected',  'value' => '')),
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
		$this->markTestIncomplete('Need to revisit once models work again.');
		extract($this->dateRegex);

		$result = $this->Form->minute('Model.field');
		$expected = array(
			array('select' => array('name' => 'Model[field][min]', 'id' => 'ModelFieldMin')),
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
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Model']['field'] = '2006-10-10 00:12:32';
		$result = $this->Form->minute('Model.field');
		$expected = array(
			array('select' => array('name' => 'Model[field][min]', 'id' => 'ModelFieldMin')),
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
			array('select' => array('name' => 'Model[field][min]', 'id' => 'ModelFieldMin')),
			array('option' => array('value' => '')),
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
			array('select' => array('name' => 'Model[field][min]', 'id' => 'ModelFieldMin')),
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
 * testHour method
 *
 * @return void
 */
	public function testHour() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		extract($this->dateRegex);

		$result = $this->Form->hour('Model.field', false);
		$expected = array(
			array('select' => array('name' => 'Model[field][hour]', 'id' => 'ModelFieldHour')),
			array('option' => array('value' => '')),
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
		$result = $this->Form->hour('Model.field', false);
		$expected = array(
			array('select' => array('name' => 'Model[field][hour]', 'id' => 'ModelFieldHour')),
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
		$result = $this->Form->hour('Model.field', true, array('value' => '23'));
		$this->assertContains('<option value="23" selected="selected">23</option>', $result);

		$result = $this->Form->hour('Model.field', false, array('value' => '23'));
		$this->assertContains('<option value="11" selected="selected">11</option>', $result);

		$this->Form->request->data['Model']['field'] = '2006-10-10 00:12:32';
		$result = $this->Form->hour('Model.field', true);
		$expected = array(
			array('select' => array('name' => 'Model[field][hour]', 'id' => 'ModelFieldHour')),
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
		$result = $this->Form->hour('Model.field', true, array('value' => 'now'));
		$thisHour = date('H');
		$optValue = date('G');
		$this->assertRegExp('/<option value="' . $thisHour . '" selected="selected">' . $optValue . '<\/option>/', $result);

		$this->Form->request->data['Model']['field'] = '2050-10-10 01:12:32';
		$result = $this->Form->hour('Model.field', true);
		$expected = array(
			array('select' => array('name' => 'Model[field][hour]', 'id' => 'ModelFieldHour')),
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
		$this->markTestIncomplete('Need to revisit once models work again.');
		$result = $this->Form->year('Model.field', 2006, 2007);
		$expected = array(
			array('select' => array('name' => 'Model[field][year]', 'id' => 'ModelFieldYear')),
			array('option' => array('value' => '')),
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

		$result = $this->Form->year('Model.field', 2006, 2007, array('orderYear' => 'asc'));
		$expected = array(
			array('select' => array('name' => 'Model[field][year]', 'id' => 'ModelFieldYear')),
			array('option' => array('value' => '')),
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

		$this->request->data['Contact']['published'] = '';
		$result = $this->Form->year('Contact.published', 2006, 2007, array('class' => 'year'));
		$expected = array(
			array('select' => array('name' => 'Contact[published][year]', 'id' => 'ContactPublishedYear', 'class' => 'year')),
			array('option' => array('value' => '')),
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

		$this->Form->request->data['Contact']['published'] = '2006-10-10';
		$result = $this->Form->year('Contact.published', 2006, 2007, array('empty' => false));
		$expected = array(
			array('select' => array('name' => 'Contact[published][year]', 'id' => 'ContactPublishedYear')),
			array('option' => array('value' => '2007')),
			'2007',
			'/option',
			array('option' => array('value' => '2006', 'selected' => 'selected')),
			'2006',
			'/option',
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Contact']['published'] = '';
		$result = $this->Form->year('Contact.published', 2006, 2007, array('value' => false));
		$expected = array(
			array('select' => array('name' => 'Contact[published][year]', 'id' => 'ContactPublishedYear')),
			array('option' => array('value' => '')),
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

		$this->Form->request->data['Contact']['published'] = '2006-10-10';
		$result = $this->Form->year('Contact.published', 2006, 2007, array('empty' => false, 'value' => false));
		$expected = array(
			array('select' => array('name' => 'Contact[published][year]', 'id' => 'ContactPublishedYear')),
			array('option' => array('value' => '2007')),
			'2007',
			'/option',
			array('option' => array('value' => '2006', 'selected' => 'selected')),
			'2006',
			'/option',
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Contact']['published'] = '';
		$result = $this->Form->year('Contact.published', 2006, 2007, array('value' => 2007));
		$expected = array(
			array('select' => array('name' => 'Contact[published][year]', 'id' => 'ContactPublishedYear')),
			array('option' => array('value' => '')),
			'/option',
			array('option' => array('value' => '2007', 'selected' => 'selected')),
			'2007',
			'/option',
			array('option' => array('value' => '2006')),
			'2006',
			'/option',
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Contact']['published'] = '2006-10-10';
		$result = $this->Form->year('Contact.published', 2006, 2007, array('empty' => false, 'value' => 2007));
		$expected = array(
			array('select' => array('name' => 'Contact[published][year]', 'id' => 'ContactPublishedYear')),
			array('option' => array('value' => '2007', 'selected' => 'selected')),
			'2007',
			'/option',
			array('option' => array('value' => '2006')),
			'2006',
			'/option',
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Contact']['published'] = '';
		$result = $this->Form->year('Contact.published', 2006, 2008, array('empty' => false, 'value' => 2007));
		$expected = array(
			array('select' => array('name' => 'Contact[published][year]', 'id' => 'ContactPublishedYear')),
			array('option' => array('value' => '2008')),
			'2008',
			'/option',
			array('option' => array('value' => '2007', 'selected' => 'selected')),
			'2007',
			'/option',
			array('option' => array('value' => '2006')),
			'2006',
			'/option',
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data['Contact']['published'] = '2006-10-10';
		$result = $this->Form->year('Contact.published', 2006, 2008, array('empty' => false));
		$expected = array(
			array('select' => array('name' => 'Contact[published][year]', 'id' => 'ContactPublishedYear')),
			array('option' => array('value' => '2008')),
			'2008',
			'/option',
			array('option' => array('value' => '2007')),
			'2007',
			'/option',
			array('option' => array('value' => '2006', 'selected' => 'selected')),
			'2006',
			'/option',
			'/select',
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data = array();
		$this->Form->create('Contact');
		$result = $this->Form->year('published', 2006, 2008, array('empty' => false));
		$expected = array(
			array('select' => array('name' => 'Contact[published][year]', 'id' => 'ContactPublishedYear')),
			array('option' => array('value' => '2008')),
			'2008',
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

		$result = $this->Form->year('published', array(), array(), array('empty' => false));
		$this->assertContains('Contact[published][year]', $result);
	}

/**
 * testYearAutoExpandRange method
 *
 * @return void
 */
	public function testYearAutoExpandRange() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->Form->request->data['User']['birthday'] = '1930-10-10';
		$result = $this->Form->year('User.birthday');
		preg_match_all('/<option value="([\d]+)"/', $result, $matches);

		$result = $matches[1];
		$expected = range(date('Y') + 20, 1930);
		$this->assertEquals($expected, $result);

		$this->Form->request->data['Project']['release'] = '2050-10-10';
		$result = $this->Form->year('Project.release');
		preg_match_all('/<option value="([\d]+)"/', $result, $matches);

		$result = $matches[1];
		$expected = range(2050, date('Y') - 20);
		$this->assertEquals($expected, $result);

		$this->Form->request->data['Project']['release'] = '1881-10-10';
		$result = $this->Form->year('Project.release', 1890, 1900);
		preg_match_all('/<option value="([\d]+)"/', $result, $matches);

		$result = $matches[1];
		$expected = range(1900, 1881);
		$this->assertEquals($expected, $result);
	}

/**
 * testInputDate method
 *
 * Test various inputs with type date and different dateFormat values.
 * Failing to provide a dateFormat key should not error.
 * It should simply not pre-select any value then.
 *
 * @return void
 */
	public function testInputDate() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->Form->request->data = array(
			'User' => array(
				'month_year' => array('month' => date('m')),
				'just_year' => array('month' => date('m')),
				'just_month' => array('year' => date('Y')),
				'just_day' => array('month' => date('m')),
			)
		);
		$this->Form->create('User');
		$result = $this->Form->input('month_year',
				array(
					'label' => false,
					'div' => false,
					'type' => 'date',
					'dateFormat' => 'MY',
					'minYear' => 2006,
					'maxYear' => 2008
				)
		);
		$this->assertContains('value="' . date('m') . '" selected="selected"', $result);
		$this->assertNotContains('value="2008" selected="selected"', $result);

		$result = $this->Form->input('just_year',
			array(
				'type' => 'date',
				'label' => false,
				'dateFormat' => 'Y',
				'minYear' => date('Y'),
				'maxYear' => date('Y', strtotime('+20 years'))
			)
		);
		$this->assertNotContains('value="' . date('Y') . '" selected="selected"', $result);

		$result = $this->Form->input('just_month',
			array(
				'type' => 'date',
				'label' => false,
				'dateFormat' => 'M',
				'empty' => false,
			)
		);
		$this->assertNotContains('value="' . date('m') . '" selected="selected"', $result);

		$result = $this->Form->input('just_day',
			array(
				'type' => 'date',
				'label' => false,
				'dateFormat' => 'D',
				'empty' => false,
			)
		);
		$this->assertNotContains('value="' . date('d') . '" selected="selected"', $result);
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
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->Form->request->data = array();
		$this->Form->create('User');
		$result = $this->Form->input('birthday',
				array(
					'label' => false,
					'div' => false,
					'type' => 'date',
					'dateFormat' => 'DMY',
					'minYear' => 2006,
					'maxYear' => 2008
				)
		);
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
		$result = $this->Form->postLink('Delete', '/posts/delete/1', array('inline' => false));
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
			array('inline' => false, 'method' => 'DELETE')
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

		$result = $this->Form->submit('Test Submit', array('div' => array('tag' => 'span')));
		$expected = array(
			'span' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'value' => 'Test Submit'),
			'/span'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('Test Submit', array('class' => 'save', 'div' => false));
		$expected = array('input' => array('type' => 'submit', 'value' => 'Test Submit', 'class' => 'save'));
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('Test Submit', array('div' => array('id' => 'SaveButton')));
		$expected = array(
			'div' => array('class' => 'submit', 'id' => 'SaveButton'),
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

		$before = '--before--';
		$after = '--after--';
		$result = $this->Form->submit('Test', array('before' => $before));
		$expected = array(
			'div' => array('class' => 'submit'),
			'--before--',
			'input' => array('type' => 'submit', 'value' => 'Test'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('Test', array('after' => $after));
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'submit', 'value' => 'Test'),
			'--after--',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('Test', array('before' => $before, 'after' => $after));
		$expected = array(
			'div' => array('class' => 'submit'),
			'--before--',
			'input' => array('type' => 'submit', 'value' => 'Test'),
			'--after--',
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

		$after = '--after--';
		$before = '--before--';
		$result = $this->Form->submit('cake.power.gif', array('after' => $after));
		$expected = array(
			'div' => array('class' => 'submit'),
			'input' => array('type' => 'image', 'src' => 'img/cake.power.gif'),
			'--after--',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('cake.power.gif', array('before' => $before));
		$expected = array(
			'div' => array('class' => 'submit'),
			'--before--',
			'input' => array('type' => 'image', 'src' => 'img/cake.power.gif'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('cake.power.gif', array('before' => $before, 'after' => $after));
		$expected = array(
			'div' => array('class' => 'submit'),
			'--before--',
			'input' => array('type' => 'image', 'src' => 'img/cake.power.gif'),
			'--after--',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->submit('Not.an.image', array('before' => $before, 'after' => $after));
		$expected = array(
			'div' => array('class' => 'submit'),
			'--before--',
			'input' => array('type' => 'submit', 'value' => 'Not.an.image'),
			'--after--',
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
		$this->markTestIncomplete('Need to revisit once models work again.');
		extract($this->dateRegex);
		$this->Form->create('Contact', array('type' => 'get'));
		$result = $this->Form->datetime('created');

		$this->assertRegExp('/name="created\[year\]"/', $result, 'year name attribute is wrong.');
		$this->assertRegExp('/name="created\[month\]"/', $result, 'month name attribute is wrong.');
		$this->assertRegExp('/name="created\[day\]"/', $result, 'day name attribute is wrong.');
		$this->assertRegExp('/name="created\[hour\]"/', $result, 'hour name attribute is wrong.');
		$this->assertRegExp('/name="created\[min\]"/', $result, 'min name attribute is wrong.');
		$this->assertRegExp('/name="created\[meridian\]"/', $result, 'meridian name attribute is wrong.');
	}

/**
 * testEditFormWithData method
 *
 * test auto populating form elements from submitted data.
 *
 * @return void
 */
	public function testEditFormWithData() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->Form->request->data = array('Person' => array(
			'id' => 1,
			'first_name' => 'Nate',
			'last_name' => 'Abele',
			'email' => 'nate@example.com'
		));
		$this->Form->request->addParams(array(
			'models' => array('Person'),
			'controller' => 'people',
			'action' => 'add'
		));
		$options = array(1 => 'Nate', 2 => 'Garrett', 3 => 'Larry');

		$this->Form->create();
		$result = $this->Form->select('People.People', $options, array('multiple' => true));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'People[People]', 'value' => '', 'id' => 'PeoplePeople_'),
			'select' => array(
				'name' => 'People[People][]', 'multiple' => 'multiple', 'id' => 'PeoplePeople'
			),
			array('option' => array('value' => 1)), 'Nate', '/option',
			array('option' => array('value' => 2)), 'Garrett', '/option',
			array('option' => array('value' => 3)), 'Larry', '/option',
			'/select'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test that required fields are created for various types of validation.
 *
 * @return void
 */
	public function testFormInputRequiredDetection() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->Form->create('Contact');

		$result = $this->Form->input('Contact.non_existing');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ContactNonExisting'),
			'Non Existing',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[non_existing]',
				'id' => 'ContactNonExisting'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.imrequired');
		$expected = array(
			'div' => array('class' => 'input text required'),
			'label' => array('for' => 'ContactImrequired'),
			'Imrequired',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[imrequired]',
				'id' => 'ContactImrequired',
				'required' => 'required'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.imalsorequired');
		$expected = array(
			'div' => array('class' => 'input text required'),
			'label' => array('for' => 'ContactImalsorequired'),
			'Imalsorequired',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[imalsorequired]',
				'id' => 'ContactImalsorequired',
				'required' => 'required'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.imrequiredtoo');
		$expected = array(
			'div' => array('class' => 'input text required'),
			'label' => array('for' => 'ContactImrequiredtoo'),
			'Imrequiredtoo',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[imrequiredtoo]',
				'id' => 'ContactImrequiredtoo',
				'required' => 'required'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.required_one');
		$expected = array(
			'div' => array('class' => 'input text required'),
			'label' => array('for' => 'ContactRequiredOne'),
			'Required One',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[required_one]',
				'id' => 'ContactRequiredOne',
				'required' => 'required'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.string_required');
		$expected = array(
			'div' => array('class' => 'input text required'),
			'label' => array('for' => 'ContactStringRequired'),
			'String Required',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[string_required]',
				'id' => 'ContactStringRequired',
				'required' => 'required'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.imnotrequired');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ContactImnotrequired'),
			'Imnotrequired',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[imnotrequired]',
				'id' => 'ContactImnotrequired'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.imalsonotrequired');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ContactImalsonotrequired'),
			'Imalsonotrequired',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[imalsonotrequired]',
				'id' => 'ContactImalsonotrequired'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.imalsonotrequired2');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ContactImalsonotrequired2'),
			'Imalsonotrequired2',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[imalsonotrequired2]',
				'id' => 'ContactImalsonotrequired2'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.imnotrequiredeither');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ContactImnotrequiredeither'),
			'Imnotrequiredeither',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[imnotrequiredeither]',
				'id' => 'ContactImnotrequiredeither'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.iamrequiredalways');
		$expected = array(
			'div' => array('class' => 'input text required'),
			'label' => array('for' => 'ContactIamrequiredalways'),
			'Iamrequiredalways',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[iamrequiredalways]',
				'id' => 'ContactIamrequiredalways',
				'required' => 'required'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.boolean_field', array('type' => 'checkbox'));
		$expected = array(
			'div' => array('class' => 'input checkbox required'),
			array('input' => array(
				'type' => 'hidden',
				'name' => 'Contact[boolean_field]',
				'id' => 'ContactBooleanField_',
				'value' => '0'
			)),
			array('input' => array(
				'type' => 'checkbox',
				'name' => 'Contact[boolean_field]',
				'value' => '1',
				'id' => 'ContactBooleanField'
			)),
			'label' => array('for' => 'ContactBooleanField'),
			'Boolean Field',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.boolean_field', array('type' => 'checkbox', 'required' => true));
		$expected = array(
			'div' => array('class' => 'input checkbox required'),
			array('input' => array(
				'type' => 'hidden',
				'name' => 'Contact[boolean_field]',
				'id' => 'ContactBooleanField_',
				'value' => '0'
			)),
			array('input' => array(
				'type' => 'checkbox',
				'name' => 'Contact[boolean_field]',
				'value' => '1',
				'id' => 'ContactBooleanField',
				'required' => 'required'
			)),
			'label' => array('for' => 'ContactBooleanField'),
			'Boolean Field',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.iamrequiredalways', array('type' => 'file'));
		$expected = array(
			'div' => array('class' => 'input file required'),
			'label' => array('for' => 'ContactIamrequiredalways'),
			'Iamrequiredalways',
			'/label',
			'input' => array(
				'type' => 'file',
				'name' => 'data[Contact][iamrequiredalways]',
				'id' => 'ContactIamrequiredalways',
				'required' => 'required'
			),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test that required fields are created when only using ModelValidator::add().
 *
 * @return void
 */
	public function testFormInputRequiredDetectionModelValidator() {
		$this->markTestIncomplete('Need to revisit once models work again.');

		$this->Form->create('ContactTag');
		$result = $this->Form->input('ContactTag.iwillberequired');
		$expected = array(
			'div' => array('class' => 'input text required'),
			'label' => array('for' => 'ContactTagIwillberequired'),
			'Iwillberequired',
			'/label',
			'input' => array(
				'name' => 'data[ContactTag][iwillberequired]',
				'type' => 'text',
				'id' => 'ContactTagIwillberequired',
				'required' => 'required'
			),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testFormMagicInput method
 *
 * @return void
 */
	public function testFormMagicInput() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$encoding = strtolower(Configure::read('App.encoding'));
		$result = $this->Form->create('Contact');
		$expected = array(
			'form' => array(
				'id' => 'ContactAddForm', 'method' => 'post', 'action' => '/contacts/add',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('name');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ContactName'),
			'Name',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[name]',
				'id' => 'ContactName', 'maxlength' => '255'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('non_existing_field_in_contact_model');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ContactNonExistingFieldInContactModel'),
			'Non Existing Field In Contact Model',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[non_existing_field_in_contact_model]',
				'id' => 'ContactNonExistingFieldInContactModel'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Address.street');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'AddressStreet'),
			'Street',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Address[street]',
				'id' => 'AddressStreet'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Address.non_existing_field_in_model');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'AddressNonExistingFieldInModel'),
			'Non Existing Field In Model',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Address[non_existing_field_in_model]',
				'id' => 'AddressNonExistingFieldInModel'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('name', array('div' => false));
		$expected = array(
			'label' => array('for' => 'ContactName'),
			'Name',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[name]',
				'id' => 'ContactName', 'maxlength' => '255'
			)
		);
		$this->assertTags($result, $expected);

		extract($this->dateRegex);
		$now = strtotime('now');

		$result = $this->Form->input('Contact.published', array('div' => false));
		$expected = array(
			'label' => array('for' => 'ContactPublishedMonth'),
			'Published',
			'/label',
			array('select' => array(
				'name' => 'Contact[published][month]', 'id' => 'ContactPublishedMonth'
			)),
			$monthsRegex,
			array('option' => array('value' => date('m', $now), 'selected' => 'selected')),
			date('F', $now),
			'/option',
			'*/select',
			'-',
			array('select' => array(
				'name' => 'Contact[published][day]', 'id' => 'ContactPublishedDay'
			)),
			$daysRegex,
			array('option' => array('value' => date('d', $now), 'selected' => 'selected')),
			date('j', $now),
			'/option',
			'*/select',
			'-',
			array('select' => array(
				'name' => 'Contact[published][year]', 'id' => 'ContactPublishedYear'
			)),
			$yearsRegex,
			array('option' => array('value' => date('Y', $now), 'selected' => 'selected')),
			date('Y', $now),
			'*/select'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.updated', array('div' => false));
		$expected = array(
			'label' => array('for' => 'ContactUpdatedMonth'),
			'Updated',
			'/label',
			array('select' => array(
				'name' => 'Contact[updated][month]', 'id' => 'ContactUpdatedMonth'
			)),
			$monthsRegex,
			array('option' => array('value' => date('m', $now), 'selected' => 'selected')),
			date('F', $now),
			'/option',
			'*/select',
			'-',
			array('select' => array(
				'name' => 'Contact[updated][day]', 'id' => 'ContactUpdatedDay'
			)),
			$daysRegex,
			array('option' => array('value' => date('d', $now), 'selected' => 'selected')),
			date('j', $now),
			'/option',
			'*/select',
			'-',
			array('select' => array(
				'name' => 'Contact[updated][year]', 'id' => 'ContactUpdatedYear'
			)),
			$yearsRegex,
			array('option' => array('value' => date('Y', $now), 'selected' => 'selected')),
			date('Y', $now),
			'*/select'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('UserForm.stuff');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'UserFormStuff'),
			'Stuff',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'UserForm[stuff]',
				'id' => 'UserFormStuff', 'maxlength' => 10
			),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testForMagicInputNonExistingNorValidated method
 *
 * @return void
 */
	public function testForMagicInputNonExistingNorValidated() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$encoding = strtolower(Configure::read('App.encoding'));
		$result = $this->Form->create('Contact');
		$expected = array(
			'form' => array(
				'id' => 'ContactAddForm', 'method' => 'post', 'action' => '/contacts/add',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.non_existing_nor_validated', array('div' => false));
		$expected = array(
			'label' => array('for' => 'ContactNonExistingNorValidated'),
			'Non Existing Nor Validated',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[non_existing_nor_validated]',
				'id' => 'ContactNonExistingNorValidated'
			)
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.non_existing_nor_validated', array(
			'div' => false, 'value' => 'my value'
		));
		$expected = array(
			'label' => array('for' => 'ContactNonExistingNorValidated'),
			'Non Existing Nor Validated',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[non_existing_nor_validated]',
				'value' => 'my value', 'id' => 'ContactNonExistingNorValidated'
			)
		);
		$this->assertTags($result, $expected);

		$this->Form->request->data = array(
			'Contact' => array('non_existing_nor_validated' => 'CakePHP magic'
		));
		$result = $this->Form->input('Contact.non_existing_nor_validated', array('div' => false));
		$expected = array(
			'label' => array('for' => 'ContactNonExistingNorValidated'),
			'Non Existing Nor Validated',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[non_existing_nor_validated]',
				'value' => 'CakePHP magic', 'id' => 'ContactNonExistingNorValidated'
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
		$this->markTestIncomplete('Need to revisit once models work again.');
		$encoding = strtolower(Configure::read('App.encoding'));
		$result = $this->Form->create('Contact');
		$expected = array(
			'form' => array(
				'id' => 'ContactAddForm', 'method' => 'post', 'action' => '/contacts/add',
				'accept-charset' => $encoding
			),
			'div' => array('style' => 'display:none;'),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.name', array('div' => false, 'label' => false));
		$this->assertTags($result, array('input' => array(
			'name' => 'Contact[name]', 'type' => 'text',
			'id' => 'ContactName', 'maxlength' => '255')
		));

		$result = $this->Form->input('Contact.name', array('div' => false, 'label' => 'My label'));
		$expected = array(
			'label' => array('for' => 'ContactName'),
			'My label',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[name]',
				'id' => 'ContactName', 'maxlength' => '255'
			)
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.name', array(
			'div' => false, 'label' => array('class' => 'mandatory')
		));
		$expected = array(
			'label' => array('for' => 'ContactName', 'class' => 'mandatory'),
			'Name',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[name]',
				'id' => 'ContactName', 'maxlength' => '255'
			)
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.name', array(
			'div' => false, 'label' => array('class' => 'mandatory', 'text' => 'My label')
		));
		$expected = array(
			'label' => array('for' => 'ContactName', 'class' => 'mandatory'),
			'My label',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[name]',
				'id' => 'ContactName', 'maxlength' => '255'
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
			'type' => 'hidden', 'name' => 'Contact[1][id]',
			'id' => 'Contact1Id'
		)));

		$result = $this->Form->input("1.name");
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'Contact1Name'),
			'Name',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[1][name]',
				'id' => 'Contact1Name', 'maxlength' => '255'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.1.id');
		$this->assertTags($result, array(
			'input' => array(
				'type' => 'hidden', 'name' => 'Contact[1][id]',
				'id' => 'Contact1Id'
			)
		));

		$result = $this->Form->input("Model.1.name");
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'Model1Name'),
			'Name',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Model[1][name]',
				'id' => 'Model1Name'
			),
			'/div'
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
 * testMultipleFormWithIdFields method
 *
 * @return void
 */
	public function testMultipleFormWithIdFields() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->Form->create('UserForm');

		$result = $this->Form->input('id');
		$this->assertTags($result, array('input' => array(
			'type' => 'hidden', 'name' => 'UserForm[id]', 'id' => 'UserFormId'
		)));

		$result = $this->Form->input('ValidateItem.id');
		$this->assertTags($result, array('input' => array(
			'type' => 'hidden', 'name' => 'ValidateItem[id]',
			'id' => 'ValidateItemId'
		)));

		$result = $this->Form->input('ValidateUser.id');
		$this->assertTags($result, array('input' => array(
			'type' => 'hidden', 'name' => 'ValidateUser[id]',
			'id' => 'ValidateUserId'
		)));
	}

/**
 * testDbLessModel method
 *
 * @return void
 */
	public function testDbLessModel() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->Form->create('TestMail');

		$result = $this->Form->input('name');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'TestMailName'),
			'Name',
			'/label',
			'input' => array(
				'name' => 'TestMail[name]', 'type' => 'text',
				'id' => 'TestMailName'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->create('TestMail');
		$result = $this->Form->input('name');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'TestMailName'),
			'Name',
			'/label',
			'input' => array(
				'name' => 'TestMail[name]', 'type' => 'text',
				'id' => 'TestMailName'
			),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * testBrokenness method
 *
 * @return void
 */
	public function testBrokenness() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		/*
		 * #4 This test has two parents and four children. By default (as of r7117) both
		 * parents are show but the first parent is missing a child. This is the inconsistency
		 * in the default behaviour - one parent has all children, the other does not - dependent
		 * on the data values.
		 */
		$result = $this->Form->select('Model.field', array(
			'Fred' => array(
				'freds_son_1' => 'Fred',
				'freds_son_2' => 'Freddie'
			),
			'Bert' => array(
				'berts_son_1' => 'Albert',
				'berts_son_2' => 'Bertie')
			),
			array('showParents' => true, 'empty' => false)
		);

		$expected = array(
			'select' => array('name' => 'Model[field]', 'id' => 'ModelField'),
				array('optgroup' => array('label' => 'Fred')),
					array('option' => array('value' => 'freds_son_1')),
						'Fred',
					'/option',
					array('option' => array('value' => 'freds_son_2')),
						'Freddie',
					'/option',
				'/optgroup',
				array('optgroup' => array('label' => 'Bert')),
					array('option' => array('value' => 'berts_son_1')),
						'Albert',
					'/option',
					array('option' => array('value' => 'berts_son_2')),
						'Bertie',
					'/option',
				'/optgroup',
			'/select'
		);
		$this->assertTags($result, $expected);

		/*
		 * #2 This is structurally identical to the test above (#1) - only the parent name has
		 * changed, so we should expect the same select list data, just with a different name
		 * for the parent. As of #7117, this test fails because option 3 => 'Three' disappears.
		 * This is where data corruption can occur, because when a select value is missing from
		 * a list a form will substitute the first value in the list - without the user knowing.
		 * If the optgroup name 'Parent' (above) is updated to 'Three' (below), this should not
		 * affect the availability of 3 => 'Three' as a valid option.
		 */
		$options = array(1 => 'One', 2 => 'Two', 'Three' => array(
			3 => 'Three', 4 => 'Four', 5 => 'Five'
		));
		$result = $this->Form->select(
			'Model.field', $options, array('showParents' => true, 'empty' => false)
		);

		$expected = array(
			'select' => array('name' => 'Model[field]', 'id' => 'ModelField'),
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
 * Test the generation of fields for a multi record form.
 *
 * @return void
 */
	public function testMultiRecordForm() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->Form->create('ValidateProfile');
		$this->Form->request->data['ValidateProfile'][1]['ValidateItem'][2]['name'] = 'Value';
		$result = $this->Form->input('ValidateProfile.1.ValidateItem.2.name');
		$expected = array(
			'div' => array('class' => 'input textarea'),
				'label' => array('for' => 'ValidateProfile1ValidateItem2Name'),
					'Name',
				'/label',
				'textarea' => array(
					'id' => 'ValidateProfile1ValidateItem2Name',
					'name' => 'ValidateProfile[1][ValidateItem][2][name]',
					'cols' => 30,
					'rows' => 6
				),
				'Value',
				'/textarea',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('ValidateProfile.1.ValidateItem.2.created', array('empty' => true));
		$expected = array(
			'div' => array('class' => 'input date'),
			'label' => array('for' => 'ValidateProfile1ValidateItem2CreatedMonth'),
			'Created',
			'/label',
			array('select' => array(
				'name' => 'ValidateProfile[1][ValidateItem][2][created][month]',
				'id' => 'ValidateProfile1ValidateItem2CreatedMonth'
				)
			),
			array('option' => array('value' => '')), '/option',
			$this->dateRegex['monthsRegex'],
			'/select', '-',
			array('select' => array(
				'name' => 'ValidateProfile[1][ValidateItem][2][created][day]',
				'id' => 'ValidateProfile1ValidateItem2CreatedDay'
				)
			),
			array('option' => array('value' => '')), '/option',
			$this->dateRegex['daysRegex'],
			'/select', '-',
			array('select' => array(
				'name' => 'ValidateProfile[1][ValidateItem][2][created][year]',
				'id' => 'ValidateProfile1ValidateItem2CreatedYear'
				)
			),
			array('option' => array('value' => '')), '/option',
			$this->dateRegex['yearsRegex'],
			'/select',
			'/div'
		);
		$this->assertTags($result, $expected);

		$ValidateProfile->validationErrors[1]['ValidateItem'][2]['profile_id'] = 'Error';
		$this->Form->request->data['ValidateProfile'][1]['ValidateItem'][2]['profile_id'] = '1';
		$result = $this->Form->input('ValidateProfile.1.ValidateItem.2.profile_id');
		$expected = array(
			'div' => array('class' => 'input select error'),
			'label' => array('for' => 'ValidateProfile1ValidateItem2ProfileId'),
			'Profile',
			'/label',
			'select' => array(
				'name' => 'ValidateProfile[1][ValidateItem][2][profile_id]',
				'id' => 'ValidateProfile1ValidateItem2ProfileId',
				'class' => 'form-error'
			),
			'/select',
			array('div' => array('class' => 'error-message')),
			'Error',
			'/div',
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test the correct display of multi-record form validation errors.
 *
 * @return void
 */
	public function testMultiRecordFormValidationErrors() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->Form->create('ValidateProfile');
		$ValidateProfile->validationErrors[2]['ValidateItem'][1]['name'] = array('Error in field name');
		$result = $this->Form->error('ValidateProfile.2.ValidateItem.1.name');
		$this->assertTags($result, array('div' => array('class' => 'error-message'), 'Error in field name', '/div'));

		$ValidateProfile->validationErrors[2]['city'] = array('Error in field city');
		$result = $this->Form->error('ValidateProfile.2.city');
		$this->assertTags($result, array('div' => array('class' => 'error-message'), 'Error in field city', '/div'));

		$result = $this->Form->error('2.city');
		$this->assertTags($result, array('div' => array('class' => 'error-message'), 'Error in field city', '/div'));
	}

/**
 * test the correct display of multi-record form validation errors.
 *
 * @return void
 */
	public function testSaveManyRecordFormValidationErrors() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->Form->create('ValidateUser');
		$ValidateUser->validationErrors[0]['ValidateItem']['name'] = array('Error in field name');

		$result = $this->Form->error('0.ValidateUser.ValidateItem.name');
		$this->assertTags($result, array('div' => array('class' => 'error-message'), 'Error in field name', '/div'));

		$ValidateUser->validationErrors[0]['city'] = array('Error in field city');
		$result = $this->Form->error('ValidateUser.0.city');
		$this->assertTags($result, array('div' => array('class' => 'error-message'), 'Error in field city', '/div'));
	}

/**
 * tests the ability to change the order of the form input placeholder "input", "label", "before", "between", "after", "error"
 *
 * @return void
 */
	public function testInputTemplate() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$result = $this->Form->input('Contact.email', array(
			'type' => 'text', 'format' => array('input')
		));
		$expected = array(
			'div' => array('class' => 'input text'),
			'input' => array(
				'type' => 'text', 'name' => 'Contact[email]',
				'id' => 'ContactEmail'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.email', array(
			'type' => 'text', 'format' => array('input', 'label'),
			'label' => '<em>Email (required)</em>'
		));
		$expected = array(
			'div' => array('class' => 'input text'),
			array('input' => array(
				'type' => 'text', 'name' => 'Contact[email]',
				'id' => 'ContactEmail'
			)),
			'label' => array('for' => 'ContactEmail'),
			'em' => array(),
			'Email (required)',
			'/em',
			'/label',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.email', array(
			'type' => 'text', 'format' => array('input', 'between', 'label', 'after'),
			'between' => '<div>Something in the middle</div>',
			'after' => '<span>Some text at the end</span>'
		));
		$expected = array(
			'div' => array('class' => 'input text'),
			array('input' => array(
				'type' => 'text', 'name' => 'Contact[email]',
				'id' => 'ContactEmail'
			)),
			array('div' => array()),
			'Something in the middle',
			'/div',
			'label' => array('for' => 'ContactEmail'),
			'Email',
			'/label',
			'span' => array(),
			'Some text at the end',
			'/span',
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.method', array(
			'type' => 'radio',
			'options' => array('email' => 'Email', 'pigeon' => 'Pigeon'),
			'between' => 'I am between',
		));
		$expected = array(
			'div' => array('class' => 'input radio'),
			'fieldset' => array(),
			'legend' => array(),
			'Method',
			'/legend',
			'I am between',
			'input' => array(
				'type' => 'hidden', 'name' => 'Contact[method]',
				'value' => '', 'id' => 'ContactMethod_'
			),
			array('input' => array(
				'type' => 'radio', 'name' => 'Contact[method]',
				'value' => 'email', 'id' => 'ContactMethodEmail'
			)),
			array('label' => array('for' => 'ContactMethodEmail')),
			'Email',
			'/label',
			array('input' => array(
				'type' => 'radio', 'name' => 'Contact[method]',
				'value' => 'pigeon', 'id' => 'ContactMethodPigeon'
			)),
			array('label' => array('for' => 'ContactMethodPigeon')),
			'Pigeon',
			'/label',
			'/fieldset',
			'/div',
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
		$this->markTestIncomplete('Need to revisit once models work again.');
		$result = $this->Form->input('User.website', array(
			'type' => 'url',
			'value' => 'http://domain.tld',
			'div' => false,
			'label' => false));
		$expected = array(
			'input' => array('type' => 'url', 'name' => 'User[website]', 'id' => 'UserWebsite', 'value' => 'http://domain.tld')
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
 * Tests that a model can be loaded from the model names passed in the request object
 *
 * @return void
 */
	public function testIntrospectModelFromRequest() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->loadFixtures('Post');
		App::build(array(
			'Plugin' => array(CAKE . 'Test/TestApp/Plugin/')
		));
		Plugin::load('TestPlugin');
		$this->Form->request['models'] = array(
			'TestPluginPost' => array(
				'plugin' => 'TestPlugin',
				'className' => 'TestPluginPost'
			)
		);

		$this->Form->create('TestPluginPost');

		Plugin::unload();
	}

/**
 * Tests that it is possible to set the validation errors directly in the helper for a field
 *
 * @return void
 */
	public function testCustomValidationErrors() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->Form->validationErrors['Thing']['field'] = 'Badness!';
		$result = $this->Form->error('Thing.field', null, array('wrap' => false));
		$this->assertEquals('Badness!', $result);
	}

/**
 * Tests that the 'on' key validates as expected on create
 *
 * @return void
 */
	public function testRequiredOnCreate() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->Form->create('Contact');

		$result = $this->Form->input('Contact.imrequiredonupdate');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ContactImrequiredonupdate'),
			'Imrequiredonupdate',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[imrequiredonupdate]',
				'id' => 'ContactImrequiredonupdate'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.imrequiredoncreate');
		$expected = array(
			'div' => array('class' => 'input text required'),
			'label' => array('for' => 'ContactImrequiredoncreate'),
			'Imrequiredoncreate',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[imrequiredoncreate]',
				'id' => 'ContactImrequiredoncreate',
				'required' => 'required'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.imrequiredonboth');
		$expected = array(
			'div' => array('class' => 'input text required'),
			'label' => array('for' => 'ContactImrequiredonboth'),
			'Imrequiredonboth',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[imrequiredonboth]',
				'id' => 'ContactImrequiredonboth',
				'required' => 'required'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->inputDefaults(array('required' => false));
		$result = $this->Form->input('Contact.imrequired');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ContactImrequired'),
			'Imrequired',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[imrequired]',
				'id' => 'ContactImrequired'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.imrequired', array('required' => false));
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.imrequired', array('required' => true));
		$expected = array(
			'div' => array('class' => 'input text required'),
			'label' => array('for' => 'ContactImrequired'),
			'Imrequired',
			'/label',
			'input' => array(
				'required' => 'required', 'type' => 'text', 'name' => 'data[Contact][imrequired]',
				'id' => 'ContactImrequired'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.imrequired', array('required' => null));
		$this->assertTags($result, $expected);
	}

/**
 * Tests that the 'on' key validates as expected on update
 *
 * @return void
 */
	public function testRequiredOnUpdate() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->Form->request->data['Contact']['id'] = 1;
		$this->Form->create('Contact');

		$result = $this->Form->input('Contact.imrequiredonupdate');
		$expected = array(
			'div' => array('class' => 'input text required'),
			'label' => array('for' => 'ContactImrequiredonupdate'),
			'Imrequiredonupdate',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[imrequiredonupdate]',
				'id' => 'ContactImrequiredonupdate',
				'required' => 'required'
			),
			'/div'
		);
		$this->assertTags($result, $expected);
		$result = $this->Form->input('Contact.imrequiredoncreate');
		$expected = array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ContactImrequiredoncreate'),
			'Imrequiredoncreate',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[imrequiredoncreate]',
				'id' => 'ContactImrequiredoncreate'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.imrequiredonboth');
		$expected = array(
			'div' => array('class' => 'input text required'),
			'label' => array('for' => 'ContactImrequiredonboth'),
			'Imrequiredonboth',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[imrequiredonboth]',
				'id' => 'ContactImrequiredonboth',
				'required' => 'required'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->input('Contact.imrequired');
		$expected = array(
			'div' => array('class' => 'input text required'),
			'label' => array('for' => 'ContactImrequired'),
			'Imrequired',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[imrequired]',
				'id' => 'ContactImrequired',
				'required' => 'required'
			),
			'/div'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test inputDefaults setter and getter
 *
 * @return void
 */
	public function testInputDefaults() {
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->Form->create('Contact');

		$this->Form->inputDefaults(array(
			'label' => false,
			'div' => array(
				'style' => 'color: #000;'
			)
		));
		$result = $this->Form->input('Contact.field1');
		$expected = array(
			'div' => array('class' => 'input text', 'style' => 'color: #000;'),
			'input' => array(
				'type' => 'text', 'name' => 'Contact[field1]',
				'id' => 'ContactField1'
			),
			'/div'
		);
		$this->assertTags($result, $expected);

		$this->Form->inputDefaults(array(
			'div' => false,
			'label' => 'Label',
		));
		$result = $this->Form->input('Contact.field1');
		$expected = array(
			'label' => array('for' => 'ContactField1'),
			'Label',
			'/label',
			'input' => array(
				'type' => 'text', 'name' => 'Contact[field1]',
				'id' => 'ContactField1'
			),
		);
		$this->assertTags($result, $expected);

		$this->Form->inputDefaults(array(
			'label' => false,
		), true);
		$result = $this->Form->input('Contact.field1');
		$expected = array(
			'input' => array(
				'type' => 'text', 'name' => 'Contact[field1]',
				'id' => 'ContactField1'
			),
		);
		$this->assertTags($result, $expected);

		$result = $this->Form->inputDefaults();
		$expected = array(
			'div' => false,
			'label' => false,
		);
		$this->assertEquals($expected, $result);
	}

}
