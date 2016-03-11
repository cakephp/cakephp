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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Auth;

use Cake\Auth\FormAuthenticate;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\I18n\Time;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;

/**
 * Test case for FormAuthentication
 *
 */
class FormAuthenticateTest extends TestCase
{

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = ['core.auth_users', 'core.users'];

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->Collection = $this->getMock('Cake\Controller\ComponentRegistry');
        $this->auth = new FormAuthenticate($this->Collection, [
            'userModel' => 'Users'
        ]);
        $password = password_hash('password', PASSWORD_DEFAULT);

        TableRegistry::clear();
        $Users = TableRegistry::get('Users');
        $Users->updateAll(['password' => $password], []);

        $AuthUsers = TableRegistry::get('AuthUsers', [
            'className' => 'TestApp\Model\Table\AuthUsersTable'
        ]);
        $AuthUsers->updateAll(['password' => $password], []);

        $this->response = $this->getMock('Cake\Network\Response');
    }

    /**
     * test applying settings in the constructor
     *
     * @return void
     */
    public function testConstructor()
    {
        $object = new FormAuthenticate($this->Collection, [
            'userModel' => 'AuthUsers',
            'fields' => ['username' => 'user', 'password' => 'password']
        ]);
        $this->assertEquals('AuthUsers', $object->config('userModel'));
        $this->assertEquals(['username' => 'user', 'password' => 'password'], $object->config('fields'));
    }

    /**
     * test the authenticate method
     *
     * @return void
     */
    public function testAuthenticateNoData()
    {
        $request = new Request('posts/index');
        $request->data = [];
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * test the authenticate method
     *
     * @return void
     */
    public function testAuthenticateNoUsername()
    {
        $request = new Request('posts/index');
        $request->data = ['password' => 'foobar'];
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * test the authenticate method
     *
     * @return void
     */
    public function testAuthenticateNoPassword()
    {
        $request = new Request('posts/index');
        $request->data = ['username' => 'mariano'];
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * test authenticate password is false method
     *
     * @return void
     */
    public function testAuthenticatePasswordIsFalse()
    {
        $request = new Request('posts/index', false);
        $request->data = [
            'username' => 'mariano',
            'password' => null
        ];
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * Test for password as empty string with _checkFields() call skipped
     * Refs https://github.com/cakephp/cakephp/pull/2441
     *
     * @return void
     */
    public function testAuthenticatePasswordIsEmptyString()
    {
        $request = new Request('posts/index', false);
        $request->data = [
            'username' => 'mariano',
            'password' => ''
        ];

        $this->auth = $this->getMock(
            'Cake\Auth\FormAuthenticate',
            ['_checkFields'],
            [
                $this->Collection,
                [
                    'userModel' => 'Users'
                ]
            ]
        );

        // Simulate that check for ensuring password is not empty is missing.
        $this->auth->expects($this->once())
            ->method('_checkFields')
            ->will($this->returnValue(true));

        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * test authenticate field is not string
     *
     * @return void
     */
    public function testAuthenticateFieldsAreNotString()
    {
        $request = new Request('posts/index', false);
        $request->data = [
            'username' => ['mariano', 'phpnut'],
            'password' => 'my password'
        ];
        $this->assertFalse($this->auth->authenticate($request, $this->response));

        $request->data = [
            'username' => 'mariano',
            'password' => ['password1', 'password2']
        ];
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * test the authenticate method
     *
     * @return void
     */
    public function testAuthenticateInjection()
    {
        $request = new Request('posts/index');
        $request->data = [
            'username' => '> 1',
            'password' => "' OR 1 = 1"
        ];
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * test authenticate success
     *
     * @return void
     */
    public function testAuthenticateSuccess()
    {
        $request = new Request('posts/index');
        $request->data = [
            'username' => 'mariano',
            'password' => 'password'
        ];
        $result = $this->auth->authenticate($request, $this->response);
        $expected = [
            'id' => 1,
            'username' => 'mariano',
            'created' => new Time('2007-03-17 01:16:23'),
            'updated' => new Time('2007-03-17 01:18:31')
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that authenticate() includes virtual fields.
     *
     * @return void
     */
    public function testAuthenticateIncludesVirtualFields()
    {
        $users = TableRegistry::get('Users');
        $users->entityClass('TestApp\Model\Entity\VirtualUser');

        $request = new Request('posts/index');
        $request->data = [
            'username' => 'mariano',
            'password' => 'password'
        ];
        $result = $this->auth->authenticate($request, $this->response);
        $expected = [
            'id' => 1,
            'username' => 'mariano',
            'bonus' => 'bonus',
            'created' => new Time('2007-03-17 01:16:23'),
            'updated' => new Time('2007-03-17 01:18:31')
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test a model in a plugin.
     *
     * @return void
     */
    public function testPluginModel()
    {
        Plugin::load('TestPlugin');

        $PluginModel = TableRegistry::get('TestPlugin.AuthUsers');
        $user['id'] = 1;
        $user['username'] = 'gwoo';
        $user['password'] = password_hash(Security::salt() . 'cake', PASSWORD_BCRYPT);
        $PluginModel->save(new Entity($user));

        $this->auth->config('userModel', 'TestPlugin.AuthUsers');

        $request = new Request('posts/index');
        $request->data = [
            'username' => 'gwoo',
            'password' => 'cake'
        ];

        $result = $this->auth->authenticate($request, $this->response);
        $expected = [
            'id' => 1,
            'username' => 'gwoo',
            'created' => new Time('2007-03-17 01:16:23'),
            'updated' => new Time('2007-03-17 01:18:31')
        ];
        $this->assertEquals($expected, $result);
        Plugin::unload();
    }

    /**
     * Test using custom finder
     *
     * @return void
     */
    public function testFinder()
    {
        $request = new Request('posts/index');
        $request->data = [
            'username' => 'mariano',
            'password' => 'password'
        ];

        $this->auth->config([
            'userModel' => 'AuthUsers',
            'finder' => 'auth'
        ]);

        $result = $this->auth->authenticate($request, $this->response);
        $expected = [
            'id' => 1,
            'username' => 'mariano',
        ];
        $this->assertEquals($expected, $result, 'Result should not contain "created" and "modified" fields');

        $this->auth->config([
            'finder' => ['auth' => ['return_created' => true]]
        ]);

        $result = $this->auth->authenticate($request, $this->response);
        $expected = [
            'id' => 1,
            'username' => 'mariano',
            'created' => new Time('2007-03-17 01:16:23'),
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test password hasher settings
     *
     * @return void
     */
    public function testPasswordHasherSettings()
    {
        $this->auth->config('passwordHasher', [
            'className' => 'Default',
            'hashType' => PASSWORD_BCRYPT
        ]);

        $passwordHasher = $this->auth->passwordHasher();
        $result = $passwordHasher->config();
        $this->assertEquals(PASSWORD_BCRYPT, $result['hashType']);

        $hash = password_hash('mypass', PASSWORD_BCRYPT);
        $User = TableRegistry::get('Users');
        $User->updateAll(
            ['password' => $hash],
            ['username' => 'mariano']
        );

        $request = new Request('posts/index');
        $request->data = [
            'username' => 'mariano',
            'password' => 'mypass'
        ];

        $result = $this->auth->authenticate($request, $this->response);
        $expected = [
            'id' => 1,
            'username' => 'mariano',
            'created' => new Time('2007-03-17 01:16:23'),
            'updated' => new Time('2007-03-17 01:18:31')
        ];
        $this->assertEquals($expected, $result);

        $this->auth = new FormAuthenticate($this->Collection, [
            'fields' => ['username' => 'username', 'password' => 'password'],
            'userModel' => 'Users'
        ]);
        $this->auth->config('passwordHasher', [
            'className' => 'Default'
        ]);
        $this->assertEquals($expected, $this->auth->authenticate($request, $this->response));

        $User->updateAll(
            ['password' => '$2y$10$/G9GBQDZhWUM4w/WLes3b.XBZSK1hGohs5dMi0vh/oen0l0a7DUyK'],
            ['username' => 'mariano']
        );
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * Tests that using default means password don't need to be rehashed
     *
     * @return void
     */
    public function testAuthenticateNoRehash()
    {
        $request = new Request('posts/index');
        $request->data = [
            'username' => 'mariano',
            'password' => 'password'
        ];
        $result = $this->auth->authenticate($request, $this->response);
        $this->assertNotEmpty($result);
        $this->assertFalse($this->auth->needsPasswordRehash());
    }

    /**
     * Tests that not using the Default password hasher means that the password
     * needs to be rehashed
     *
     * @return void
     */
    public function testAuthenticateRehash()
    {
        $this->auth = new FormAuthenticate($this->Collection, [
            'userModel' => 'Users',
            'passwordHasher' => 'Weak'
        ]);
        $password = $this->auth->passwordHasher()->hash('password');
        TableRegistry::get('Users')->updateAll(['password' => $password], []);

        $request = new Request('posts/index');
        $request->data = [
            'username' => 'mariano',
            'password' => 'password'
        ];
        $result = $this->auth->authenticate($request, $this->response);
        $this->assertNotEmpty($result);
        $this->assertTrue($this->auth->needsPasswordRehash());
    }
}
