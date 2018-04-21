<?php
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
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\Component\SecurityComponent;
use Cake\Controller\Controller;
use Cake\Controller\Exception\SecurityException;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\Http\Session;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;

/**
 * TestSecurityComponent
 */
class TestSecurityComponent extends SecurityComponent
{

    /**
     * validatePost method
     *
     * @param Controller $controller
     * @return bool
     */
    public function validatePost(Controller $controller)
    {
        return $this->_validatePost($controller);
    }

    /**
     * authRequired method
     *
     * @param Controller $controller
     * @return bool
     */
    public function authRequired(Controller $controller)
    {
        return $this->_authRequired($controller);
    }
}

/**
 * SecurityTestController
 */
class SecurityTestController extends Controller
{

    /**
     * components property
     *
     * @var array
     */
    public $components = [
        'TestSecurity' => ['className' => 'Cake\Test\TestCase\Controller\Component\TestSecurityComponent']
    ];

    /**
     * failed property
     *
     * @var bool
     */
    public $failed = false;

    /**
     * Used for keeping track of headers in test
     *
     * @var array
     */
    public $testHeaders = [];

    /**
     * fail method
     *
     * @return void
     */
    public function fail()
    {
        $this->failed = true;
    }

    /**
     * redirect method
     *
     * @param string|array $url
     * @param mixed $status
     * @param mixed $exit
     * @return void
     */
    public function redirect($url, $status = null, $exit = true)
    {
        return $status;
    }

    /**
     * Convenience method for header()
     *
     * @param string $status
     * @return void
     */
    public function header($status)
    {
        $this->testHeaders[] = $status;
    }
}

/**
 * SecurityComponentTest class
 *
 * @property SecurityComponent Security
 * @property SecurityTestController Controller
 */
class SecurityComponentTest extends TestCase
{
    /**
     * SERVER variable backup.
     *
     * @var array
     */
    protected $server = [];

    /**
     * Controller property
     *
     * @var SecurityTestController
     */
    public $Controller;

    /**
     * oldSalt property
     *
     * @var string
     */
    public $oldSalt;

    /**
     * setUp method
     *
     * Initializes environment state.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->server = $_SERVER;
        $session = new Session();
        $request = new ServerRequest([
            'url' => '/articles/index',
            'session' => $session,
            'params' => ['controller' => 'articles', 'action' => 'index']
        ]);

        $this->Controller = new SecurityTestController($request);
        $this->Controller->Security = $this->Controller->TestSecurity;
        $this->Controller->Security->setConfig('blackHoleCallback', 'fail');
        $this->Security = $this->Controller->Security;
        $this->Security->session = $session;
        Security::setSalt('foo!');
    }

    /**
     *
     * Resets environment state.
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        $_SERVER = $this->server;
        $this->Security->session->delete('_Token');
        unset($this->Controller->Security);
        unset($this->Controller->Component);
        unset($this->Controller);
    }

    public function validatePost($expectedException = 'SecurityException', $expectedExceptionMessage = null)
    {
        try {
            return $this->Controller->Security->validatePost($this->Controller);
        } catch (SecurityException $ex) {
            $this->assertInstanceOf('Cake\\Controller\\Exception\\' . $expectedException, $ex);
            $this->assertEquals($expectedExceptionMessage, $ex->getMessage());

            return false;
        }
    }

    /**
     * testBlackholeWithBrokenCallback method
     *
     * Test that requests are still blackholed when controller has incorrect
     * visibility keyword in the blackhole callback.
     *
     * @return void
     * @triggers Controller.startup $Controller, $this->Controller
     */
    public function testBlackholeWithBrokenCallback()
    {
        $this->expectException(\Cake\Http\Exception\BadRequestException::class);
        $request = new ServerRequest([
            'url' => 'posts/index',
            'session' => $this->Security->session,
            'params' => [
                'controller' => 'posts',
                'action' => 'index'
            ]
        ]);
        $Controller = new \TestApp\Controller\SomePagesController($request);
        $event = new Event('Controller.startup', $Controller);
        $Security = new SecurityComponent($Controller->components());
        $Security->setConfig('blackHoleCallback', '_fail');
        $Security->startup($event);
        $Security->blackHole($Controller, 'csrf');
    }

    /**
     * testExceptionWhenActionIsBlackholeCallback method
     *
     * Ensure that directly requesting the blackholeCallback as the controller
     * action results in an exception.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testExceptionWhenActionIsBlackholeCallback()
    {
        $this->Controller->request = $this->Controller->request
            ->withParam('controller', 'posts')
            ->withParam('action', 'fail');

        $event = new Event('Controller.startup', $this->Controller);
        $this->assertFalse($this->Controller->failed);
        $this->Controller->Security->startup($event);
        $this->assertTrue($this->Controller->failed, 'Request was blackholed.');
    }

    /**
     * testConstructorSettingProperties method
     *
     * Test that initialize can set properties.
     *
     * @return void
     */
    public function testConstructorSettingProperties()
    {
        $settings = [
            'requireSecure' => ['update_account'],
            'validatePost' => false,
        ];
        $Security = new SecurityComponent($this->Controller->components(), $settings);
        $this->assertEquals($Security->validatePost, $settings['validatePost']);
    }

    /**
     * testStartup method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testStartup()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Security->startup($event);
        $this->assertTrue($this->Security->session->check('_Token'));
    }

    /**
     * testRequireSecureFail method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testRequireSecureFail()
    {
        $this->Controller->request = $this->Controller->request
            ->withParam('action', 'posted')
            ->withEnv('HTTPS', 'off')
            ->withEnv('REQUEST_METHOD', 'POST');

        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Security->requireSecure(['posted']);
        $this->Controller->Security->startup($event);
        $this->assertTrue($this->Controller->failed);
    }

    /**
     * testRequireSecureSucceed method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testRequireSecureSucceed()
    {
        $this->Controller->request = $this->Controller->request
            ->withParam('action', 'posted')
            ->withEnv('HTTPS', 'on')
            ->withEnv('REQUEST_METHOD', 'Secure');
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Security->requireSecure('posted');
        $this->Controller->Security->startup($event);
        $this->assertFalse($this->Controller->failed);
    }

    /**
     * testRequireSecureEmptyFail method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testRequireSecureEmptyFail()
    {
        $this->Controller->request = $this->Controller->request
            ->withParam('action', 'posted')
            ->withEnv('HTTPS', 'off')
            ->withEnv('REQUEST_METHOD', 'POST');
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Security->requireSecure();
        $this->Controller->Security->startup($event);
        $this->assertTrue($this->Controller->failed);
    }

    /**
     * testRequireSecureEmptySucceed method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testRequireSecureEmptySucceed()
    {
        $this->Controller->request = $this->Controller->request
            ->withParam('action', 'posted')
            ->withEnv('HTTPS', 'on')
            ->withEnv('REQUEST_METHOD', 'Secure');
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Security->requireSecure();
        $this->Controller->Security->startup($event);
        $this->assertFalse($this->Controller->failed);
    }

    /**
     * testRequireAuthFail method
     *
     * @group deprecated
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testRequireAuthFail()
    {
        $this->deprecated(function () {
            $event = new Event('Controller.startup', $this->Controller);
            $this->Controller->request = $this->Controller->request
                ->withParam('action', 'posted')
                ->withData('username', 'willy')
                ->withData('password', 'somePass')
                ->withEnv('REQUEST_METHOD', 'AUTH');
            $this->Security->requireAuth(['posted']);
            $this->Security->startup($event);
            $this->assertTrue($this->Controller->failed);

            $this->Controller->request->getSession()->write('_Token', ['allowedControllers' => []]);
            $this->Security->requireAuth('posted');
            $this->Security->startup($event);
            $this->assertTrue($this->Controller->failed);

            $this->Controller->request->getSession()->write('_Token', [
                'allowedControllers' => ['SecurityTest'], 'allowedActions' => ['posted2']
            ]);
            $this->Security->requireAuth('posted');
            $this->Security->startup($event);
            $this->assertTrue($this->Controller->failed);
        });
    }

    /**
     * testRequireAuthSucceed method
     *
     * @group deprecated
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testRequireAuthSucceed()
    {
        $this->deprecated(function () {
            $_SERVER['REQUEST_METHOD'] = 'AUTH';
            $this->Controller->Security->setConfig('validatePost', false);

            $event = new Event('Controller.startup', $this->Controller);
            $this->Controller->request->addParams([
                'action' => 'posted'
            ]);
            $this->Security->requireAuth('posted');
            $this->Security->startup($event);
            $this->assertFalse($this->Controller->failed);

            $this->Controller->Security->session->write('_Token', [
                'allowedControllers' => ['SecurityTest'],
                'allowedActions' => ['posted'],
            ]);
            $this->Controller->request->addParams([
                'controller' => 'SecurityTest',
                'action' => 'posted'
            ]);

            $this->Controller->request->data = [
                'username' => 'willy',
                'password' => 'somePass',
                '_Token' => ''
            ];
            $this->Controller->action = 'posted';
            $this->Controller->Security->requireAuth('posted');
            $this->Controller->Security->startup($event);
            $this->assertFalse($this->Controller->failed);
        });
    }

    /**
     * testValidatePost method
     *
     * Simple hash validation test
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePost()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);

        $fields = '4697b45f7f430ff3ab73018c20f315eecb0ba5a6%3AModel.valid';
        $unlocked = '';
        $debug = '';

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Model' => ['username' => 'nate', 'password' => 'foo', 'valid' => '0'],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ]);
        $this->assertTrue($this->validatePost());
    }

    /**
     * testValidatePostOnGetWithData method
     *
     * Test that validatePost fires on GET with request data.
     * This could happen when method overriding is used.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostOnGetWithData()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);

        $fields = 'an-invalid-token';
        $unlocked = '';
        $debug = urlencode(json_encode([
            'some-action',
            [],
            []
        ]));

        $this->Controller->request = $this->Controller->request
            ->withEnv('REQUEST_METHOD', 'GET')
            ->withData('Model', ['username' => 'nate', 'password' => 'foo', 'valid' => '0'])
            ->withData('_Token', compact('fields', 'unlocked', 'debug'));

        $this->Security->startup($event);
        $this->assertTrue($this->Controller->failed);
    }

    /**
     * testValidatePostNoSession method
     *
     * Test that validatePost fails if you are missing the session information.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostNoSession()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);
        $this->Security->session->delete('_Token');
        $unlocked = '';
        $debug = urlencode(json_encode([
            '/articles/index',
            [],
            []
        ]));

        $fields = 'a5475372b40f6e3ccbf9f8af191f20e1642fd877%3AModel.valid';

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Model' => ['username' => 'nate', 'password' => 'foo', 'valid' => '0'],
            '_Token' => compact('fields', 'unlocked', 'debug')
        ]);
        $this->assertFalse($this->validatePost('AuthSecurityException', 'Unexpected field \'Model.password\' in POST data, Unexpected field \'Model.username\' in POST data'));
    }

    /**
     * testValidatePostNoUnlockedInRequestData method
     *
     * Test that validatePost fails if you are missing unlocked in request data.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostNoUnlockedInRequestData()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);
        $this->Security->session->delete('_Token');

        $fields = 'a5475372b40f6e3ccbf9f8af191f20e1642fd877%3AModel.valid';

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Model' => ['username' => 'nate', 'password' => 'foo', 'valid' => '0'],
            '_Token' => compact('fields')
        ]);
        $this->assertFalse($this->validatePost('AuthSecurityException', '\'_Token.unlocked\' was not found in request data.'));
    }

    /**
     * testValidatePostFormHacking method
     *
     * Test that validatePost fails if any of its required fields are missing.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostFormHacking()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);
        $unlocked = '';

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Model' => ['username' => 'nate', 'password' => 'foo', 'valid' => '0'],
            '_Token' => compact('unlocked')
        ]);
        $result = $this->validatePost('AuthSecurityException', '\'_Token.fields\' was not found in request data.');
        $this->assertFalse($result, 'validatePost passed when fields were missing. %s');
    }

    /**
     * testValidatePostEmptyForm method
     *
     * Test that validatePost fails if empty form is submitted.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostEmptyForm()
    {
        $this->Controller->request = $this->Controller->request
            ->withEnv('REQUEST_METHOD', 'POST')
            ->withParsedBody([]);
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);
        $result = $this->validatePost('AuthSecurityException', '\'_Token\' was not found in request data.');
        $this->assertFalse($result, 'validatePost passed when empty form is submitted');
    }

    /**
     * testValidatePostObjectDeserialize
     *
     * Test that objects can't be passed into the serialized string. This was a vector for RFI and LFI
     * attacks. Thanks to Felix Wilhelm
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostObjectDeserialize()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);
        $fields = 'a5475372b40f6e3ccbf9f8af191f20e1642fd877';
        $unlocked = '';
        $debug = urlencode(json_encode([
            '/articles/index',
            ['Model.password', 'Model.username', 'Model.valid'],
            []
        ]));

        // a corrupted serialized object, so we can see if it ever gets to deserialize
        $attack = 'O:3:"App":1:{s:5:"__map";a:1:{s:3:"foo";s:7:"Hacked!";s:1:"fail"}}';
        $fields .= urlencode(':' . str_rot13($attack));

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Model' => ['username' => 'mark', 'password' => 'foo', 'valid' => '0'],
            '_Token' => compact('fields', 'unlocked', 'debug')
        ]);
        $result = $this->validatePost('SecurityException', 'Bad Request');
        $this->assertFalse($result, 'validatePost passed when key was missing. %s');
    }

    /**
     * testValidatePostIgnoresCsrfToken method
     *
     * Tests validation post data ignores `_csrfToken`.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostIgnoresCsrfToken()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);

        $fields = 'f95b472a63f1d883b9eaacaf8a8e36e325e3fe82%3A';
        $unlocked = '';
        $debug = 'not used';

        $this->Controller->request = $this->Controller->request->withParsedBody([
            '_csrfToken' => 'abc123',
            'Model' => ['multi_field' => ['1', '3']],
            '_Token' => compact('fields', 'unlocked', 'debug')
        ]);
        $this->assertTrue($this->validatePost());
    }

    /**
     * testValidatePostArray method
     *
     * Tests validation of checkbox arrays.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostArray()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);

        $fields = 'f95b472a63f1d883b9eaacaf8a8e36e325e3fe82%3A';
        $unlocked = '';
        $debug = urlencode(json_encode([
            'some-action',
            [],
            []
        ]));

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Model' => ['multi_field' => ['1', '3']],
            '_Token' => compact('fields', 'unlocked', 'debug')
        ]);
        $this->assertTrue($this->validatePost());

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Model' => ['multi_field' => [12 => '1', 20 => '3']],
            '_Token' => compact('fields', 'unlocked', 'debug')
        ]);
        $this->assertTrue($this->validatePost());
    }

    /**
     * testValidateIntFieldName method
     *
     * Tests validation of integer field names.
     *
     * @return void
     */
    public function testValidateIntFieldName()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);

        $fields = '11f87a5962db9ac26405e460cd3063bb6ff76cf8%3A';
        $unlocked = '';
        $debug = urlencode(json_encode([
            'some-action',
            [],
            []
        ]));

        $this->Controller->request = $this->Controller->request->withParsedBody([
            1 => 'value,',
            '_Token' => compact('fields', 'unlocked', 'debug')
        ]);
        $this->assertTrue($this->validatePost());
    }

    /**
     * testValidatePostNoModel method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostNoModel()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);

        $fields = 'a2a942f587deb20e90241c51b59d901d8a7f796b%3A';
        $unlocked = '';
        $debug = 'not used';

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'anything' => 'some_data',
            '_Token' => compact('fields', 'unlocked', 'debug')
        ]);

        $result = $this->validatePost();
        $this->assertTrue($result);
    }

    /**
     * testValidatePostSimple method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostSimple()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);

        $fields = 'de2ca3670dd06c29558dd98482c8739e86da2c7c%3A';
        $unlocked = '';
        $debug = 'not used';

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Model' => ['username' => '', 'password' => ''],
            '_Token' => compact('fields', 'unlocked', 'debug')
        ]);

        $result = $this->validatePost();
        $this->assertTrue($result);
    }

    /**
     * test validatePost uses full URL
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostSubdirectory()
    {
        // set the base path.
        $this->Controller->request = $this->Controller->request
            ->withAttribute('base', 'subdir')
            ->withAttributE('webroot', 'subdir/');
        Router::pushRequest($this->Controller->request);

        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);

        // Differs from testValidatePostSimple because of base url
        $fields = 'cc9b6af3f33147235ae8f8037b0a71399a2425f2%3A';
        $unlocked = '';
        $debug = '';

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Model' => ['username' => '', 'password' => ''],
            '_Token' => compact('fields', 'unlocked', 'debug')
        ]);

        $result = $this->validatePost();
        $this->assertTrue($result);
    }

    /**
     * testValidatePostComplex method
     *
     * Tests hash validation for multiple records, including locked fields.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostComplex()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);

        $fields = 'b00b7e5c2e3bf8bc474fb7cfde6f9c2aa06ab9bc%3AAddresses.0.id%7CAddresses.1.id';
        $unlocked = '';
        $debug = 'not used';

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Addresses' => [
                '0' => [
                    'id' => '123456', 'title' => '', 'first_name' => '', 'last_name' => '',
                    'address' => '', 'city' => '', 'phone' => '', 'primary' => ''
                ],
                '1' => [
                    'id' => '654321', 'title' => '', 'first_name' => '', 'last_name' => '',
                    'address' => '', 'city' => '', 'phone' => '', 'primary' => ''
                ]
            ],
            '_Token' => compact('fields', 'unlocked', 'debug')
        ]);
        $result = $this->validatePost();
        $this->assertTrue($result);
    }

    /**
     * testValidatePostMultipleSelect method
     *
     * Test ValidatePost with multiple select elements.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostMultipleSelect()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);

        $fields = '28dd05f0af314050784b18b3366857e8e8c78e73%3A';
        $unlocked = '';
        $debug = 'not used';

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Tag' => ['Tag' => [1, 2]],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ]);
        $result = $this->validatePost();
        $this->assertTrue($result);

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Tag' => ['Tag' => [1, 2, 3]],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ]);
        $result = $this->validatePost();
        $this->assertTrue($result);

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Tag' => ['Tag' => [1, 2, 3, 4]],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ]);
        $result = $this->validatePost();
        $this->assertTrue($result);

        $fields = '1e4c9269b64756e9b141d364497c5f037b428a37%3A';
        $this->Controller->request = $this->Controller->request->withParsedBody([
            'User.password' => 'bar', 'User.name' => 'foo', 'User.is_valid' => '1',
            'Tag' => ['Tag' => [1]],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ]);
        $result = $this->validatePost();
        $this->assertTrue($result);
    }

    /**
     * testValidatePostCheckbox method
     *
     * First block tests un-checked checkbox
     * Second block tests checked checkbox
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostCheckbox()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);
        $fields = '4697b45f7f430ff3ab73018c20f315eecb0ba5a6%3AModel.valid';
        $unlocked = '';
        $debug = 'not used';

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Model' => ['username' => '', 'password' => '', 'valid' => '0'],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ]);

        $result = $this->validatePost();
        $this->assertTrue($result);

        $fields = '3f368401f9a8610bcace7746039651066cdcdc38%3A';

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Model' => ['username' => '', 'password' => '', 'valid' => '0'],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ]);

        $result = $this->validatePost();
        $this->assertTrue($result);

        $this->Controller->request = $this->Controller->request->withParsedBody([]);
        $this->Security->startup($event);

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Model' => ['username' => '', 'password' => '', 'valid' => '0'],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ]);

        $result = $this->validatePost();
        $this->assertTrue($result);
    }

    /**
     * testValidatePostHidden method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostHidden()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);
        $fields = '96e61bded2b62b0c420116a0eb06a3b3acddb8f1%3AModel.hidden%7CModel.other_hidden';
        $unlocked = '';
        $debug = 'not used';

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Model' => [
                'username' => '', 'password' => '', 'hidden' => '0',
                'other_hidden' => 'some hidden value'
            ],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ]);
        $result = $this->validatePost();
        $this->assertTrue($result);
    }

    /**
     * testValidatePostWithDisabledFields method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostWithDisabledFields()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->setConfig('disabledFields', ['Model.username', 'Model.password']);
        $this->Security->startup($event);
        $fields = '0313460e1136e1227044399fe10a6edc0cbca279%3AModel.hidden';
        $unlocked = '';
        $debug = 'not used';

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Model' => [
                'username' => '', 'password' => '', 'hidden' => '0'
            ],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ]);

        $result = $this->validatePost();
        $this->assertTrue($result);
    }

    /**
     * testValidatePostDisabledFieldsInData method
     *
     * Test validating post data with posted unlocked fields.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostDisabledFieldsInData()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);
        $unlocked = 'Model.username';
        $fields = ['Model.hidden', 'Model.password'];
        $fields = urlencode(
            hash_hmac('sha1', '/articles/index' . serialize($fields) . $unlocked . 'cli', Security::getSalt())
        );
        $debug = 'not used';

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Model' => [
                'username' => 'mark',
                'password' => 'sekret',
                'hidden' => '0'
            ],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ]);

        $result = $this->validatePost();
        $this->assertTrue($result);
    }

    /**
     * testValidatePostFailNoDisabled method
     *
     * Test that missing 'unlocked' input causes failure.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostFailNoDisabled()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);
        $fields = ['Model.hidden', 'Model.password', 'Model.username'];
        $fields = urlencode(Security::hash(serialize($fields) . Security::getSalt()));

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Model' => [
                'username' => 'mark',
                'password' => 'sekret',
                'hidden' => '0'
            ],
            '_Token' => compact('fields')
        ]);

        $result = $this->validatePost('SecurityException', '\'_Token.unlocked\' was not found in request data.');
        $this->assertFalse($result);
    }

    /**
     * testValidatePostFailNoDebug method
     *
     * Test that missing 'debug' input causes failure.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostFailNoDebug()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);
        $fields = ['Model.hidden', 'Model.password', 'Model.username'];
        $fields = urlencode(Security::hash(serialize($fields) . Security::getSalt()));
        $unlocked = '';

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Model' => [
                'username' => 'mark',
                'password' => 'sekret',
                'hidden' => '0'
            ],
            '_Token' => compact('fields', 'unlocked')
        ]);

        $result = $this->validatePost('SecurityException', '\'_Token.debug\' was not found in request data.');
        $this->assertFalse($result);
    }

    /**
     * testValidatePostFailNoDebugMode method
     *
     * Test that missing 'debug' input is not the problem when debug mode disabled.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostFailNoDebugMode()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);
        $fields = ['Model.hidden', 'Model.password', 'Model.username'];
        $fields = urlencode(Security::hash(serialize($fields) . Security::getSalt()));
        $unlocked = '';

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Model' => [
                'username' => 'mark',
                'password' => 'sekret',
                'hidden' => '0'
            ],
            '_Token' => compact('fields', 'unlocked')
        ]);
        Configure::write('debug', false);
        $result = $this->validatePost('SecurityException', 'The request has been black-holed');
    }

    /**
     * testValidatePostFailDisabledFieldTampering method
     *
     * Test that validatePost fails when unlocked fields are changed.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostFailDisabledFieldTampering()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);
        $unlocked = 'Model.username';
        $fields = ['Model.hidden', 'Model.password'];
        $fields = urlencode(Security::hash(serialize($fields) . $unlocked . Security::getSalt()));
        $debug = urlencode(json_encode([
            '/articles/index',
            ['Model.hidden', 'Model.password'],
            ['Model.username']
        ]));

        // Tamper the values.
        $unlocked = 'Model.username|Model.password';

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Model' => [
                'username' => 'mark',
                'password' => 'sekret',
                'hidden' => '0'
            ],
            '_Token' => compact('fields', 'unlocked', 'debug')
        ]);

        $result = $this->validatePost('SecurityException', 'Missing field \'Model.password\' in POST data, Unexpected unlocked field \'Model.password\' in POST data');
        $this->assertFalse($result);
    }

    /**
     * testValidateHiddenMultipleModel method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidateHiddenMultipleModel()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);
        $fields = '642b7a6db3b848fab88952b86ea36c572f93df40%3AModel.valid%7CModel2.valid%7CModel3.valid';
        $unlocked = '';
        $debug = 'not used';

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Model' => ['username' => '', 'password' => '', 'valid' => '0'],
            'Model2' => ['valid' => '0'],
            'Model3' => ['valid' => '0'],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ]);
        $result = $this->validatePost();
        $this->assertTrue($result);
    }

    /**
     * testValidateHasManyModel method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidateHasManyModel()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);
        $fields = '792324c8a374772ad82acfb28f0e77e70f8ed3af%3AModel.0.hidden%7CModel.0.valid';
        $fields .= '%7CModel.1.hidden%7CModel.1.valid';
        $unlocked = '';
        $debug = 'not used';

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Model' => [
                [
                    'username' => 'username', 'password' => 'password',
                    'hidden' => 'value', 'valid' => '0'
                ],
                [
                    'username' => 'username', 'password' => 'password',
                    'hidden' => 'value', 'valid' => '0'
                ]
            ],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ]);

        $result = $this->validatePost();
        $this->assertTrue($result);
    }

    /**
     * testValidateHasManyRecordsPass method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidateHasManyRecordsPass()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);
        $fields = '7f4bff67558e25ebeea44c84ea4befa8d50b080c%3AAddress.0.id%7CAddress.0.primary%7C';
        $fields .= 'Address.1.id%7CAddress.1.primary';
        $unlocked = '';
        $debug = 'not used';

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Address' => [
                0 => [
                    'id' => '123',
                    'title' => 'home',
                    'first_name' => 'Bilbo',
                    'last_name' => 'Baggins',
                    'address' => '23 Bag end way',
                    'city' => 'the shire',
                    'phone' => 'N/A',
                    'primary' => '1',
                ],
                1 => [
                    'id' => '124',
                    'title' => 'home',
                    'first_name' => 'Frodo',
                    'last_name' => 'Baggins',
                    'address' => '50 Bag end way',
                    'city' => 'the shire',
                    'phone' => 'N/A',
                    'primary' => '1'
                ]
            ],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ]);

        $result = $this->validatePost();
        $this->assertTrue($result);
    }

    /**
     * testValidateNestedNumericSets method
     *
     * Test that values like Foo.0.1
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidateNestedNumericSets()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);
        $unlocked = '';
        $hashFields = ['TaxonomyData'];
        $fields = urlencode(
            hash_hmac('sha1', '/articles/index' . serialize($hashFields) . $unlocked . 'cli', Security::getSalt())
        );
        $debug = 'not used';

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'TaxonomyData' => [
                1 => [[2]],
                2 => [[3]]
            ],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ]);
        $result = $this->validatePost();
        $this->assertTrue($result);
    }

    /**
     * testValidateHasManyRecords method
     *
     * validatePost should fail, hidden fields have been changed.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidateHasManyRecordsFail()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);
        $fields = '7a203edb3d345bbf38fe0dccae960da8842e11d7%3AAddress.0.id%7CAddress.0.primary%7C';
        $fields .= 'Address.1.id%7CAddress.1.primary';
        $unlocked = '';
        $debug = urlencode(json_encode([
            '/articles/index',
            [
                'Address.0.address',
                'Address.0.city',
                'Address.0.first_name',
                'Address.0.last_name',
                'Address.0.phone',
                'Address.0.title',
                'Address.1.address',
                'Address.1.city',
                'Address.1.first_name',
                'Address.1.last_name',
                'Address.1.phone',
                'Address.1.title',
                'Address.0.id' => '123',
                'Address.0.primary' => '5',
                'Address.1.id' => '124',
                'Address.1.primary' => '1'
            ],
            []
        ]));

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Address' => [
                0 => [
                    'id' => '123',
                    'title' => 'home',
                    'first_name' => 'Bilbo',
                    'last_name' => 'Baggins',
                    'address' => '23 Bag end way',
                    'city' => 'the shire',
                    'phone' => 'N/A',
                    'primary' => '5',
                ],
                1 => [
                    'id' => '124',
                    'title' => 'home',
                    'first_name' => 'Frodo',
                    'last_name' => 'Baggins',
                    'address' => '50 Bag end way',
                    'city' => 'the shire',
                    'phone' => 'N/A',
                    'primary' => '1'
                ]
            ],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ]);
        $result = $this->validatePost('SecurityException', 'Bad Request');
        $this->assertFalse($result);
    }

    /**
     * testFormDisabledFields method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testFormDisabledFields()
    {
        $event = new Event('Controller.startup', $this->Controller);

        $this->Security->startup($event);
        $fields = '4eaf5c6b93d5140c24171d5fdce16ed5b904f288%3An%3A0%3A%7B%7D';
        $unlocked = '';
        $debug = urlencode(json_encode([
            '/articles/index',
            [],
            []
        ]));

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'MyModel' => ['name' => 'some data'],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ]);
        $result = $this->validatePost('SecurityException', 'Unexpected field \'MyModel.name\' in POST data');
        $this->assertFalse($result);

        $this->Security->startup($event);
        $this->Security->setConfig('disabledFields', ['MyModel.name']);

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'MyModel' => ['name' => 'some data'],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ]);

        $result = $this->validatePost();
        $this->assertTrue($result);
    }

    /**
     * testValidatePostRadio method
     *
     * Test validatePost with radio buttons.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostRadio()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);
        $fields = 'a709dfdee0a0cce52c4c964a1b8a56159bb081b4%3An%3A0%3A%7B%7D';
        $unlocked = '';
        $debug = urlencode(json_encode([
            '/articles/index',
            [],
            []
        ]));

        $this->Controller->request = $this->Controller->request->withParsedBody([
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ]);
        $result = $this->validatePost('SecurityException', 'Bad Request');
        $this->assertFalse($result);

        $this->Controller->request = $this->Controller->request->withParsedBody([
            '_Token' => compact('fields', 'unlocked', 'debug'),
            'Test' => ['test' => '']
        ]);
        $result = $this->validatePost();
        $this->assertTrue($result);

        $this->Controller->request = $this->Controller->request->withParsedBody([
            '_Token' => compact('fields', 'unlocked', 'debug'),
            'Test' => ['test' => '1']
        ]);
        $result = $this->validatePost();
        $this->assertTrue($result);

        $this->Controller->request = $this->Controller->request->withParsedBody([
            '_Token' => compact('fields', 'unlocked', 'debug'),
            'Test' => ['test' => '2']
        ]);
        $result = $this->validatePost();
        $this->assertTrue($result);
    }

    /**
     * testValidatePostUrlAsHashInput method
     *
     * Test validatePost uses here() as a hash input.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostUrlAsHashInput()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);

        $fields = 'de2ca3670dd06c29558dd98482c8739e86da2c7c%3A';
        $unlocked = '';
        $debug = urlencode(json_encode([
            'another-url',
            ['Model.username', 'Model.password'],
            []
        ]));

        $this->Controller->request = $this->Controller->request
            ->withData('Model', ['username' => '', 'password' => ''])
            ->withData('_Token', compact('fields', 'unlocked', 'debug'));
        $this->assertTrue($this->validatePost());

        $this->Controller->request = $this->Controller->request
            ->withRequestTarget('/posts/index?page=1');
        $this->assertFalse($this->validatePost(
            'SecurityException',
            'URL mismatch in POST data (expected \'another-url\' but found \'/posts/index?page=1\')'
        ));

        $this->Controller->request = $this->Controller->request
            ->withRequestTarget('/posts/edit/1');
        $this->assertFalse($this->validatePost(
            'SecurityException',
            'URL mismatch in POST data (expected \'another-url\' but found \'/posts/edit/1\')'
        ));
    }

    /**
     * testBlackHoleNotDeletingSessionInformation method
     *
     * Test that blackhole doesn't delete the _Token session key so repeat data submissions
     * stay blackholed.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testBlackHoleNotDeletingSessionInformation()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);

        $this->Security->blackHole($this->Controller, 'auth');
        $this->assertTrue($this->Controller->Security->session->check('_Token'), '_Token was deleted by blackHole %s');
    }

    /**
     * testGenerateToken method
     *
     * Test generateToken().
     *
     * @return void
     */
    public function testGenerateToken()
    {
        $request = $this->Controller->request;
        $request = $this->Security->generateToken($request);

        $this->assertNotEmpty($request->getParam('_Token'));
        $this->assertSame([], $request->getParam('_Token.unlockedFields'));
    }

    /**
     * testUnlockedActions method
     *
     * Test unlocked actions.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testUnlockedActions()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->request = $this->Controller->request->withParsedBody(['data']);
        $this->Security->unlockedActions = 'index';
        $this->Security->blackHoleCallback = null;
        $result = $this->Controller->Security->startup($event);
        $this->assertNull($result);
    }

    /**
     * testValidatePostDebugFormat method
     *
     * Test that debug token format is right.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostDebugFormat()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);
        $unlocked = 'Model.username';
        $fields = ['Model.hidden', 'Model.password'];
        $fields = urlencode(Security::hash(serialize($fields) . $unlocked . Security::getSalt()));
        $debug = urlencode(json_encode([
            '/articles/index',
            ['Model.hidden', 'Model.password'],
            ['Model.username'],
            ['not expected']
        ]));

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Model' => [
                'username' => 'mark',
                'password' => 'sekret',
                'hidden' => '0'
            ],
            '_Token' => compact('fields', 'unlocked', 'debug')
        ]);

        $result = $this->validatePost('SecurityException', 'Invalid security debug token.');
        $this->assertFalse($result);

        $debug = urlencode(json_encode('not an array'));
        $result = $this->validatePost('SecurityException', 'Invalid security debug token.');
        $this->assertFalse($result);
    }

    /**
     * testBlackholeThrowsException method
     *
     * Test blackhole will now throw passed exception if debug enabled.
     *
     * @return void
     */
    public function testBlackholeThrowsException()
    {
        $this->expectException(\Cake\Controller\Exception\SecurityException::class);
        $this->expectExceptionMessage('error description');
        $this->Security->setConfig('blackHoleCallback', '');
        $this->Security->blackHole($this->Controller, 'auth', new SecurityException('error description'));
    }

    /**
     * testBlackholeThrowsBadRequest method
     *
     * Test blackhole will throw BadRequest if debug disabled.
     *
     * @return void
     */
    public function testBlackholeThrowsBadRequest()
    {
        $this->Security->setConfig('blackHoleCallback', '');
        $message = '';

        Configure::write('debug', false);
        try {
            $this->Security->blackHole($this->Controller, 'auth', new SecurityException('error description'));
        } catch (SecurityException $ex) {
            $message = $ex->getMessage();
            $reason = $ex->getReason();
        }
        $this->assertEquals('The request has been black-holed', $message);
        $this->assertEquals('error description', $reason);
    }

    /**
     * testValidatePostFailTampering method
     *
     * Test that validatePost fails with tampered fields and explanation.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostFailTampering()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);
        $unlocked = '';
        $fields = ['Model.hidden' => 'value', 'Model.id' => '1'];
        $debug = urlencode(json_encode([
            '/articles/index',
            $fields,
            []
        ]));
        $fields = urlencode(Security::hash(serialize($fields) . $unlocked . Security::getSalt()));
        $fields .= urlencode(':Model.hidden|Model.id');
        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Model' => [
                'hidden' => 'tampered',
                'id' => '1',
            ],
            '_Token' => compact('fields', 'unlocked', 'debug')
        ]);

        $result = $this->validatePost('SecurityException', 'Tampered field \'Model.hidden\' in POST data (expected value \'value\' but found \'tampered\')');
        $this->assertFalse($result);
    }

    /**
     * testValidatePostFailTamperingMutatedIntoArray method
     *
     * Test that validatePost fails with tampered fields and explanation.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostFailTamperingMutatedIntoArray()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);
        $unlocked = '';
        $fields = ['Model.hidden' => 'value', 'Model.id' => '1'];
        $debug = urlencode(json_encode([
            '/articles/index',
            $fields,
            []
        ]));
        $fields = urlencode(Security::hash(serialize($fields) . $unlocked . Security::getSalt()));
        $fields .= urlencode(':Model.hidden|Model.id');
        $this->Controller->request = $this->Controller->request->withData('Model', [
            'hidden' => ['some-key' => 'some-value'],
            'id' => '1',
        ])->withData('_Token', compact('fields', 'unlocked', 'debug'));

        $result = $this->validatePost(
            'SecurityException',
            'Unexpected field \'Model.hidden.some-key\' in POST data, Missing field \'Model.hidden\' in POST data'
        );
        $this->assertFalse($result);
    }

    /**
     * testValidatePostUnexpectedDebugToken method
     *
     * Test that debug token should not be sent if debug is disabled.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostUnexpectedDebugToken()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Security->startup($event);
        $unlocked = '';
        $fields = ['Model.hidden' => 'value', 'Model.id' => '1'];
        $debug = urlencode(json_encode([
            '/articles/index',
            $fields,
            []
        ]));
        $fields = urlencode(Security::hash(serialize($fields) . $unlocked . Security::getSalt()));
        $fields .= urlencode(':Model.hidden|Model.id');
        $this->Controller->request = $this->Controller->request->withParsedBody([
            'Model' => [
                'hidden' => ['some-key' => 'some-value'],
                'id' => '1',
            ],
            '_Token' => compact('fields', 'unlocked', 'debug')
        ]);
        Configure::write('debug', false);
        $result = $this->validatePost('SecurityException', 'Unexpected \'_Token.debug\' found in request data');
        $this->assertFalse($result);
    }

    /**
     * testAuthRequiredThrowsExceptionTokenNotFoundPost method
     *
     * Auth required throws exception token not found.
     *
     * @group deprecated
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testAuthRequiredThrowsExceptionTokenNotFoundPost()
    {
        $this->expectException(\Cake\Controller\Exception\AuthSecurityException::class);
        $this->expectExceptionMessage('\'_Token\' was not found in request data.');
        $this->deprecated(function () {
            $this->Security->setConfig('requireAuth', ['protected']);
            $this->Controller->request->params['action'] = 'protected';
            $this->Controller->request->data = 'notEmpty';
            $this->Security->authRequired($this->Controller);
        });
    }

    /**
     * testAuthRequiredThrowsExceptionTokenNotFoundSession method
     *
     * Auth required throws exception token not found in Session.
     *
     * @group deprecated
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testAuthRequiredThrowsExceptionTokenNotFoundSession()
    {
        $this->expectException(\Cake\Controller\Exception\AuthSecurityException::class);
        $this->expectExceptionMessage('\'_Token\' was not found in session.');
        $this->deprecated(function () {
            $this->Security->setConfig('requireAuth', ['protected']);
            $this->Controller->request->params['action'] = 'protected';
            $this->Controller->request->data = ['_Token' => 'not empty'];
            $this->Security->authRequired($this->Controller);
        });
    }

    /**
     * testAuthRequiredThrowsExceptionControllerNotAllowed method
     *
     * Auth required throws exception controller not allowed.
     *
     * @group deprecated
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testAuthRequiredThrowsExceptionControllerNotAllowed()
    {
        $this->expectException(\Cake\Controller\Exception\AuthSecurityException::class);
        $this->expectExceptionMessage('Controller \'NotAllowed\' was not found in allowed controllers: \'Allowed, AnotherAllowed\'.');
        $this->deprecated(function () {
            $this->Security->setConfig('requireAuth', ['protected']);
            $this->Controller->request->params['controller'] = 'NotAllowed';
            $this->Controller->request->params['action'] = 'protected';
            $this->Controller->request->data = ['_Token' => 'not empty'];
            $this->Controller->request->session()->write('_Token', [
                'allowedControllers' => ['Allowed', 'AnotherAllowed']
            ]);
            $this->Security->authRequired($this->Controller);
        });
    }

    /**
     * testAuthRequiredThrowsExceptionActionNotAllowed method
     *
     * Auth required throws exception controller not allowed.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testAuthRequiredThrowsExceptionActionNotAllowed()
    {
        $this->expectException(\Cake\Controller\Exception\AuthSecurityException::class);
        $this->expectExceptionMessage('Action \'NotAllowed::protected\' was not found in allowed actions: \'index, view\'.');
        $this->deprecated(function () {
            $this->Security->setConfig('requireAuth', ['protected']);
            $this->Controller->request->params['controller'] = 'NotAllowed';
            $this->Controller->request->params['action'] = 'protected';
            $this->Controller->request->data = ['_Token' => 'not empty'];
            $this->Controller->request->session()->write('_Token', [
                'allowedActions' => ['index', 'view']
            ]);
            $this->Security->authRequired($this->Controller);
        });
    }

    /**
     * testAuthRequired method
     *
     * Auth required throws exception controller not allowed.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testAuthRequired()
    {
        $this->deprecated(function () {
            $this->Security->setConfig('requireAuth', ['protected']);
            $this->Controller->request->params['controller'] = 'Allowed';
            $this->Controller->request->params['action'] = 'protected';
            $this->Controller->request->data = ['_Token' => 'not empty'];
            $this->Controller->request->session()->write('_Token', [
                'allowedActions' => ['protected'],
                'allowedControllers' => ['Allowed'],
            ]);
            $this->assertTrue($this->Security->authRequired($this->Controller));
        });
    }
}
