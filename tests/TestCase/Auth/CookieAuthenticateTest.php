<?php
NAmespace Cake\Auth\Test\TestCase\Auth;

use Cake\Auth\BasicAuthenticate;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\I18n\Time;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;
use Cake\Auth\CookieAuthenticate;

/**
 * Test case for FormAuthentication
 */
class CookieAuthenticateTest extends TestCase
{

    public $fixtures = ['core.cookie_users'];

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->request = new Request('posts/index');
        Router::setRequestInfo($this->request);
        $this->response = $this->getMock('Cake\Network\Response');

        Security::salt('this_is_a_random_key_that_is_at_least_256_bits_long');
        $this->Registry = new ComponentRegistry(new Controller($this->request, $this->response));
        $this->Registry->load('Cookie');
        $this->Registry->load('Auth');
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        $this->Registry->Cookie->delete('RememberMe');
    }

    /**
     *
     * test authenticate with token and token expiration
     *
     * @group cookie
     *
     * @return void
     */
    public function testCookieAuthenticate()
    {
        $config = [
            'fields' => [
                'identifier' => 'uuid',
                'token' => 'remember_me_token',
                'tokenCreated' => 'remember_me_token_created',
            ],
            'userModel' => 'CookieUsers',
        ];
        
        $cookieData = [
            'uuid' => 'e99a6234-22d0-4676-b4e1-4c58b9c937d5',
            'remember_me_token' => 'a4e4243a-946f-44b4-8250-886c4e068de2'
        ];
        
        $this->Registry->Cookie->write('RememberMe', $cookieData);

        $expected = [
            'id' => 1,
            'user_name' => 'mariano',
            'email' => 'mariano@example.com',
            'token' => '12345',
            'created' => new Time('2007-03-17 01:16:23'),
            'updated' => new Time('2007-03-17 01:18:31'),
            'uuid' => 'e99a6234-22d0-4676-b4e1-4c58b9c937d5',
            'remember_me_token_created' => new Time('2015-05-31 16:01:03')
        ];

        $CookieUsers = TableRegistry::get('CookieUsers');
        $user = $CookieUsers->get(1);
        $user->remember_me_token = password_hash($cookieData['remember_me_token'], PASSWORD_DEFAULT);
        $CookieUsers->save($user);
        
        $this->auth = new CookieAuthenticate($this->Registry, $config);
        
        $result = $this->auth->authenticate($this->request, $this->response);
        $this->assertEquals($expected, $result);

        // Ensure Cookie expiry tampering does not succeed
        // todo
    }
}
