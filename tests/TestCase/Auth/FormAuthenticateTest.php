<?php
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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Auth;

use Cake\Auth\FormAuthenticate;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Plugin;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\Time;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;

/**
 * Test case for FormAuthentication
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
        $this->Collection = $this->getMockBuilder(ComponentRegistry::class)->getMock();
        $this->auth = new FormAuthenticate($this->Collection, [
            'userModel' => 'Users'
        ]);
        $password = password_hash('password', PASSWORD_DEFAULT);

        $this->getTableLocator()->clear();
        $Users = $this->getTableLocator()->get('Users');
        $Users->updateAll(['password' => $password], []);

        $AuthUsers = $this->getTableLocator()->get('AuthUsers', [
            'className' => 'TestApp\Model\Table\AuthUsersTable'
        ]);
        $AuthUsers->updateAll(['password' => $password], []);

        $this->response = $this->getMockBuilder(Response::class)->getMock();
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
        $this->assertEquals('AuthUsers', $object->getConfig('userModel'));
        $this->assertEquals(['username' => 'user', 'password' => 'password'], $object->getConfig('fields'));
    }

    /**
     * test the authenticate method
     *
     * @return void
     */
    public function testAuthenticateNoData()
    {
        $request = new ServerRequest([
            'url' => 'posts/index',
            'post' => [],
        ]);
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * test the authenticate method
     *
     * @return void
     */
    public function testAuthenticateNoUsername()
    {
        $request = new ServerRequest([
            'url' => 'posts/index',
            'post' => ['password' => 'foobar'],
        ]);
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * test the authenticate method
     *
     * @return void
     */
    public function testAuthenticateNoPassword()
    {
        $request = new ServerRequest([
            'url' => 'posts/index',
            'post' => ['username' => 'mariano'],
        ]);
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * test authenticate password is false method
     *
     * @return void
     */
    public function testAuthenticatePasswordIsFalse()
    {
        $request = new ServerRequest('posts/index', false);
        $request = new ServerRequest([
            'url' => 'posts/index',
            'post' => [
                'username' => 'mariano',
                'password' => null
            ],
        ]);
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
        $request = new ServerRequest([
            'url' => 'posts/index',
            'post' => [
                'username' => 'mariano',
                'password' => ''
            ],
        ]);

        $this->auth = $this->getMockBuilder(FormAuthenticate::class)
            ->setMethods(['_checkFields'])
            ->setConstructorArgs([
                $this->Collection,
                ['userModel' => 'Users']
            ])
            ->getMock();

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
        $request = new ServerRequest([
            'url' => 'posts/index',
            'post' => [
                'username' => ['mariano', 'phpnut'],
                'password' => 'my password'
            ],
        ]);
        $this->assertFalse($this->auth->authenticate($request, $this->response));

        $request = new ServerRequest([
            'url' => 'posts/index',
            'post' => [
                'username' => 'mariano',
            'password' => ['password1', 'password2']
            ],
        ]);
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * test the authenticate method
     *
     * @return void
     */
    public function testAuthenticateInjection()
    {
        $request = new ServerRequest([
            'url' => 'posts/index',
            'post' => [
                'username' => '> 1',
                'password' => "' OR 1 = 1"
            ],
        ]);
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * test authenticate success
     *
     * @return void
     */
    public function testAuthenticateSuccess()
    {
        $request = new ServerRequest([
            'url' => 'posts/index',
            'post' => [
                'username' => 'mariano',
                'password' => 'password'
            ],
        ]);
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
        $users = $this->getTableLocator()->get('Users');
        $users->setEntityClass('TestApp\Model\Entity\VirtualUser');

        $request = new ServerRequest([
            'url' => 'posts/index',
            'post' => [
                'username' => 'mariano',
                'password' => 'password'
            ],
        ]);
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

        $PluginModel = $this->getTableLocator()->get('TestPlugin.AuthUsers');
        $user['id'] = 1;
        $user['username'] = 'gwoo';
        $user['password'] = password_hash(Security::getSalt() . 'cake', PASSWORD_BCRYPT);
        $PluginModel->save(new Entity($user));

        $this->auth->setConfig('userModel', 'TestPlugin.AuthUsers');

        $request = new ServerRequest([
            'url' => 'posts/index',
            'post' => [
                'username' => 'gwoo',
                'password' => 'cake'
            ],
        ]);

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
        $request = new ServerRequest([
            'url' => 'posts/index',
            'post' => [
                'username' => 'mariano',
                'password' => 'password'
            ],
        ]);

        $this->auth->setConfig([
            'userModel' => 'AuthUsers',
            'finder' => 'auth'
        ]);

        $result = $this->auth->authenticate($request, $this->response);
        $expected = [
            'id' => 1,
            'username' => 'mariano',
        ];
        $this->assertEquals($expected, $result, 'Result should not contain "created" and "modified" fields');

        $this->auth->setConfig([
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
     * Test using custom finder
     *
     * @return void
     */
    public function testFinderOptions()
    {
        $request = new ServerRequest([
            'url' => 'posts/index',
            'post' => [
                'username' => 'mariano',
                'password' => 'password'
            ],
        ]);

        $this->auth->setConfig([
            'userModel' => 'AuthUsers',
            'finder' => 'username'
        ]);

        $result = $this->auth->authenticate($request, $this->response);
        $expected = [
            'id' => 1,
            'username' => 'mariano',
        ];
        $this->assertEquals($expected, $result);

        $this->auth->setConfig([
            'finder' => ['username' => ['username' => 'nate']]
        ]);

        $result = $this->auth->authenticate($request, $this->response);
        $expected = [
            'id' => 5,
            'username' => 'nate',
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
        $this->auth->setConfig('passwordHasher', [
            'className' => 'Default',
            'hashType' => PASSWORD_BCRYPT
        ]);

        $passwordHasher = $this->auth->passwordHasher();
        $result = $passwordHasher->getConfig();
        $this->assertEquals(PASSWORD_BCRYPT, $result['hashType']);

        $hash = password_hash('mypass', PASSWORD_BCRYPT);
        $User = $this->getTableLocator()->get('Users');
        $User->updateAll(
            ['password' => $hash],
            ['username' => 'mariano']
        );

        $request = new ServerRequest([
            'url' => 'posts/index',
            'post' => [
                'username' => 'mariano',
                'password' => 'mypass'
            ],
        ]);

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
        $this->auth->setConfig('passwordHasher', [
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
        $request = new ServerRequest([
            'url' => 'posts/index',
            'post' => [
                'username' => 'mariano',
                'password' => 'password'
            ],
        ]);
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
        $this->getTableLocator()->get('Users')->updateAll(['password' => $password], []);

        $request = new ServerRequest([
            'url' => 'posts/index',
            'post' => [
                'username' => 'mariano',
                'password' => 'password'
            ],
        ]);
        $result = $this->auth->authenticate($request, $this->response);
        $this->assertNotEmpty($result);
        $this->assertTrue($this->auth->needsPasswordRehash());
    }
}
