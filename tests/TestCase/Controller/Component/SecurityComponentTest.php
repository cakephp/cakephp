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
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\Component\SecurityComponent;
use Cake\Controller\Controller;
use Cake\Controller\Exception\SecurityException;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Session;
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

        $session = new Session();
        $request = $this->getMockBuilder('Cake\Network\Request')
            ->setMethods(['here'])
            ->setConstructorArgs(['posts/index'])
            ->getMock();
        $request->addParams(['controller' => 'posts', 'action' => 'index']);
        $request->session($session);
        $request->expects($this->any())
            ->method('here')
            ->will($this->returnValue('/articles/index'));

        $this->Controller = new SecurityTestController($request);
        $this->Controller->Security = $this->Controller->TestSecurity;
        $this->Controller->Security->config('blackHoleCallback', 'fail');
        $this->Security = $this->Controller->Security;
        $this->Security->session = $session;
        Security::salt('foo!');
    }

    /**
     * tearDown method
     *
     * Resets environment state.
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        $this->Security->session->delete('_Token');
        unset($this->Controller->Security);
        unset($this->Controller->Component);
        unset($this->Controller);
    }

    public function validatePost($expectedException = null, $expectedExceptionMessage = null)
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
     * @expectedException \Cake\Network\Exception\BadRequestException
     * @return void
     * @triggers Controller.startup $Controller, $this->Controller
     */
    public function testBlackholeWithBrokenCallback()
    {
        $request = new Request([
            'url' => 'posts/index',
            'session' => $this->Security->session
        ]);
        $request->addParams([
            'controller' => 'posts',
            'action' => 'index'
        ]);
        $Controller = new \TestApp\Controller\SomePagesController($request);
        $event = new Event('Controller.startup', $Controller);
        $Security = new SecurityComponent($Controller->components());
        $Security->config('blackHoleCallback', '_fail');
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
        $this->Controller->request->addParams([
            'controller' => 'posts',
            'action' => 'fail'
        ]);
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
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->Controller->request['action'] = 'posted';
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
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['REQUEST_METHOD'] = 'Secure';
        $this->Controller->request['action'] = 'posted';
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
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->Controller->request['action'] = 'posted';
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
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['REQUEST_METHOD'] = 'Secure';
        $this->Controller->request['action'] = 'posted';
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Security->requireSecure();
        $this->Controller->Security->startup($event);
        $this->assertFalse($this->Controller->failed);
    }

    /**
     * testRequireAuthFail method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testRequireAuthFail()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $_SERVER['REQUEST_METHOD'] = 'AUTH';
        $this->Controller->request['action'] = 'posted';
        $this->Controller->request->data = ['username' => 'willy', 'password' => 'somePass'];
        $this->Security->requireAuth(['posted']);
        $this->Security->startup($event);
        $this->assertTrue($this->Controller->failed);

        $this->Security->session->write('_Token', ['allowedControllers' => []]);
        $this->Controller->request->data = ['username' => 'willy', 'password' => 'somePass'];
        $this->Controller->request['action'] = 'posted';
        $this->Security->requireAuth('posted');
        $this->Security->startup($event);
        $this->assertTrue($this->Controller->failed);

        $this->Security->session->write('_Token', [
            'allowedControllers' => ['SecurityTest'], 'allowedActions' => ['posted2']
        ]);
        $this->Controller->request->data = ['username' => 'willy', 'password' => 'somePass'];
        $this->Controller->request['action'] = 'posted';
        $this->Security->requireAuth('posted');
        $this->Security->startup($event);
        $this->assertTrue($this->Controller->failed);
    }

    /**
     * testRequireAuthSucceed method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testRequireAuthSucceed()
    {
        $_SERVER['REQUEST_METHOD'] = 'AUTH';
        $this->Controller->Security->config('validatePost', false);

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

        $fields = '68730b0747d4889ec2766f9117405f9635f5fd5e%3AModel.valid';
        $unlocked = '';
        $debug = '';

        $this->Controller->request->data = [
            'Model' => ['username' => 'nate', 'password' => 'foo', 'valid' => '0'],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
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

        $this->Controller->request->env('REQUEST_METHOD', 'GET');
        $this->Controller->request->data = [
            'Model' => ['username' => 'nate', 'password' => 'foo', 'valid' => '0'],
            '_Token' => compact('fields', 'unlocked', 'debug')
        ];
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

        $this->Controller->request->data = [
            'Model' => ['username' => 'nate', 'password' => 'foo', 'valid' => '0'],
            '_Token' => compact('fields', 'unlocked', 'debug')
        ];
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

        $this->Controller->request->data = [
            'Model' => ['username' => 'nate', 'password' => 'foo', 'valid' => '0'],
            '_Token' => compact('fields')
        ];
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

        $this->Controller->request->data = [
            'Model' => ['username' => 'nate', 'password' => 'foo', 'valid' => '0'],
            '_Token' => compact('unlocked')
        ];
        $result = $this->validatePost('AuthSecurityException', '\'_Token.fields\' was not found in request data.');
        $this->assertFalse($result, 'validatePost passed when fields were missing. %s');
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

        $this->Controller->request->data = [
            'Model' => ['username' => 'mark', 'password' => 'foo', 'valid' => '0'],
            '_Token' => compact('fields', 'unlocked', 'debug')
        ];
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

        $fields = '8e26ef05379e5402c2c619f37ee91152333a0264%3A';
        $unlocked = '';
        $debug = 'not used';

        $this->Controller->request->data = [
            '_csrfToken' => 'abc123',
            'Model' => ['multi_field' => ['1', '3']],
            '_Token' => compact('fields', 'unlocked', 'debug')
        ];
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

        $fields = '8e26ef05379e5402c2c619f37ee91152333a0264%3A';
        $unlocked = '';
        $debug = urlencode(json_encode([
            'some-action',
            [],
            []
        ]));

        $this->Controller->request->data = [
            'Model' => ['multi_field' => ['1', '3']],
            '_Token' => compact('fields', 'unlocked', 'debug')
        ];
        $this->assertTrue($this->validatePost());

        $this->Controller->request->data = [
            'Model' => ['multi_field' => [12 => '1', 20 => '3']],
            '_Token' => compact('fields', 'unlocked', 'debug')
        ];
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

        $fields = '4a221010dd7a23f7166cb10c38bc21d81341c387%3A';
        $unlocked = '';
        $debug = urlencode(json_encode([
            'some-action',
            [],
            []
        ]));

        $this->Controller->request->data = [
            1 => 'value,',
            '_Token' => compact('fields', 'unlocked', 'debug')
        ];
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

        $fields = 'a1c3724b7ba85e7022413611e30ba2c6181d5aba%3A';
        $unlocked = '';
        $debug = 'not used';

        $this->Controller->request->data = [
            'anything' => 'some_data',
            '_Token' => compact('fields', 'unlocked', 'debug')
        ];

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

        $fields = 'b0914d06dfb04abf1fada53e16810e87d157950b%3A';
        $unlocked = '';
        $debug = 'not used';

        $this->Controller->request->data = [
            'Model' => ['username' => '', 'password' => ''],
            '_Token' => compact('fields', 'unlocked', 'debug')
        ];

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

        $fields = 'b65c7463e44a61d8d2eaecce2c265b406c9c4742%3AAddresses.0.id%7CAddresses.1.id';
        $unlocked = '';
        $debug = 'not used';

        $this->Controller->request->data = [
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
        ];
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

        $fields = '8d8da68ba03b3d6e7e145b948abfe26741422169%3A';
        $unlocked = '';
        $debug = 'not used';

        $this->Controller->request->data = [
            'Tag' => ['Tag' => [1, 2]],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
        $result = $this->validatePost();
        $this->assertTrue($result);

        $this->Controller->request->data = [
            'Tag' => ['Tag' => [1, 2, 3]],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
        $result = $this->validatePost();
        $this->assertTrue($result);

        $this->Controller->request->data = [
            'Tag' => ['Tag' => [1, 2, 3, 4]],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
        $result = $this->validatePost();
        $this->assertTrue($result);

        $fields = 'eae2adda1628b771a30cc133342d16220c6520fe%3A';
        $this->Controller->request->data = [
            'User.password' => 'bar', 'User.name' => 'foo', 'User.is_valid' => '1',
            'Tag' => ['Tag' => [1]],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
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
        $fields = '68730b0747d4889ec2766f9117405f9635f5fd5e%3AModel.valid';
        $unlocked = '';
        $debug = 'not used';

        $this->Controller->request->data = [
            'Model' => ['username' => '', 'password' => '', 'valid' => '0'],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];

        $result = $this->validatePost();
        $this->assertTrue($result);

        $fields = 'f63e4a69b2edd31f064e8e602a04dd59307cfe9c%3A';

        $this->Controller->request->data = [
            'Model' => ['username' => '', 'password' => '', 'valid' => '0'],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];

        $result = $this->validatePost();
        $this->assertTrue($result);

        $this->Controller->request->data = [];
        $this->Security->startup($event);

        $this->Controller->request->data = [
            'Model' => ['username' => '', 'password' => '', 'valid' => '0'],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];

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
        $fields = '973a8939a68ac014cc6f7666cec9aa6268507350%3AModel.hidden%7CModel.other_hidden';
        $unlocked = '';
        $debug = 'not used';

        $this->Controller->request->data = [
            'Model' => [
                'username' => '', 'password' => '', 'hidden' => '0',
                'other_hidden' => 'some hidden value'
            ],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
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
        $this->Security->config('disabledFields', ['Model.username', 'Model.password']);
        $this->Security->startup($event);
        $fields = '1c59acfbca98bd870c11fb544d545cbf23215880%3AModel.hidden';
        $unlocked = '';
        $debug = 'not used';

        $this->Controller->request->data = [
            'Model' => [
                'username' => '', 'password' => '', 'hidden' => '0'
            ],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];

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
        $fields = urlencode(Security::hash('/articles/index' . serialize($fields) . $unlocked . Security::salt()));
        $debug = 'not used';

        $this->Controller->request->data = [
            'Model' => [
                'username' => 'mark',
                'password' => 'sekret',
                'hidden' => '0'
            ],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];

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
        $fields = urlencode(Security::hash(serialize($fields) . Security::salt()));

        $this->Controller->request->data = [
            'Model' => [
                'username' => 'mark',
                'password' => 'sekret',
                'hidden' => '0'
            ],
            '_Token' => compact('fields')
        ];

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
        $fields = urlencode(Security::hash(serialize($fields) . Security::salt()));
        $unlocked = '';

        $this->Controller->request->data = [
            'Model' => [
                'username' => 'mark',
                'password' => 'sekret',
                'hidden' => '0'
            ],
            '_Token' => compact('fields', 'unlocked')
        ];

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
        $fields = urlencode(Security::hash(serialize($fields) . Security::salt()));
        $unlocked = '';

        $this->Controller->request->data = [
            'Model' => [
                'username' => 'mark',
                'password' => 'sekret',
                'hidden' => '0'
            ],
            '_Token' => compact('fields', 'unlocked')
        ];
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
        $fields = urlencode(Security::hash(serialize($fields) . $unlocked . Security::salt()));
        $debug = urlencode(json_encode([
            '/articles/index',
            ['Model.hidden', 'Model.password'],
            ['Model.username']
        ]));

        // Tamper the values.
        $unlocked = 'Model.username|Model.password';

        $this->Controller->request->data = [
            'Model' => [
                'username' => 'mark',
                'password' => 'sekret',
                'hidden' => '0'
            ],
            '_Token' => compact('fields', 'unlocked', 'debug')
        ];

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
        $fields = '075ca6c26c38a09a78d871201df89faf52cbbeb8%3AModel.valid%7CModel2.valid%7CModel3.valid';
        $unlocked = '';
        $debug = 'not used';

        $this->Controller->request->data = [
            'Model' => ['username' => '', 'password' => '', 'valid' => '0'],
            'Model2' => ['valid' => '0'],
            'Model3' => ['valid' => '0'],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
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
        $fields = '24a753fb62ef7839389987b58e3f7108f564e529%3AModel.0.hidden%7CModel.0.valid';
        $fields .= '%7CModel.1.hidden%7CModel.1.valid';
        $unlocked = '';
        $debug = 'not used';

        $this->Controller->request->data = [
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
        ];

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
        $fields = '8f7d82bf7656cf068822d9bdab109ebed1be1825%3AAddress.0.id%7CAddress.0.primary%7C';
        $fields .= 'Address.1.id%7CAddress.1.primary';
        $unlocked = '';
        $debug = 'not used';

        $this->Controller->request->data = [
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
        ];

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
        $fields = urlencode(Security::hash('/articles/index' . serialize($hashFields) . $unlocked . Security::salt()));
        $debug = 'not used';

        $this->Controller->request->data = [
            'TaxonomyData' => [
                1 => [[2]],
                2 => [[3]]
            ],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
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

        $this->Controller->request->data = [
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
        ];
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
        $fields = '9da2b3fa2b5b8ac0bfbc1bbce145e58059629125%3An%3A0%3A%7B%7D';
        $unlocked = '';
        $debug = urlencode(json_encode([
            '/articles/index',
            [],
            []
        ]));

        $this->Controller->request->data = [
            'MyModel' => ['name' => 'some data'],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
        $result = $this->validatePost('SecurityException', 'Unexpected field \'MyModel.name\' in POST data');
        $this->assertFalse($result);

        $this->Security->startup($event);
        $this->Security->config('disabledFields', ['MyModel.name']);

        $this->Controller->request->data = [
            'MyModel' => ['name' => 'some data'],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];

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
        $fields = 'c2226a8879c3f4b513691295fc2519a29c44c8bb%3An%3A0%3A%7B%7D';
        $unlocked = '';
        $debug = urlencode(json_encode([
            '/articles/index',
            [],
            []
        ]));

        $this->Controller->request->data = [
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
        $result = $this->validatePost('SecurityException', 'Bad Request');
        $this->assertFalse($result);

        $this->Controller->request->data = [
            '_Token' => compact('fields', 'unlocked', 'debug'),
            'Test' => ['test' => '']
        ];
        $result = $this->validatePost();
        $this->assertTrue($result);

        $this->Controller->request->data = [
            '_Token' => compact('fields', 'unlocked', 'debug'),
            'Test' => ['test' => '1']
        ];
        $result = $this->validatePost();
        $this->assertTrue($result);

        $this->Controller->request->data = [
            '_Token' => compact('fields', 'unlocked', 'debug'),
            'Test' => ['test' => '2']
        ];
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

        $fields = 'b0914d06dfb04abf1fada53e16810e87d157950b%3A';
        $unlocked = '';
        $debug = urlencode(json_encode([
            'another-url',
            ['Model.username', 'Model.password'],
            []
        ]));

        $this->Controller->request->data = [
            'Model' => ['username' => '', 'password' => ''],
            '_Token' => compact('fields', 'unlocked', 'debug')
        ];
        $this->assertTrue($this->validatePost());

        $request = $this->getMockBuilder('Cake\Network\Request')
            ->setMethods(['here'])
            ->getMock();
        $request->expects($this->at(0))
            ->method('here')
            ->will($this->returnValue('/posts/index?page=1'));
        $request->expects($this->at(1))
            ->method('here')
            ->will($this->returnValue('/posts/edit/1'));
        $request->data = $this->Controller->request->data;
        $this->Controller->request = $request;

        $this->assertFalse($this->validatePost('SecurityException', 'URL mismatch in POST data (expected \'another-url\' but found \'/posts/index?page=1\')'));
        $this->assertFalse($this->validatePost('SecurityException', 'URL mismatch in POST data (expected \'another-url\' but found \'/posts/edit/1\')'));
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
        $this->Security->generateToken($request);

        $this->assertNotEmpty($request->params['_Token']);
        $this->assertTrue(isset($request->params['_Token']['unlockedFields']));
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
        $this->Controller->request->data = ['data'];
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
        $fields = urlencode(Security::hash(serialize($fields) . $unlocked . Security::salt()));
        $debug = urlencode(json_encode([
            '/articles/index',
            ['Model.hidden', 'Model.password'],
            ['Model.username'],
            ['not expected']
        ]));

        $this->Controller->request->data = [
            'Model' => [
                'username' => 'mark',
                'password' => 'sekret',
                'hidden' => '0'
            ],
            '_Token' => compact('fields', 'unlocked', 'debug')
        ];

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
     * @expectedException \Cake\Controller\Exception\SecurityException
     * @expectedExceptionMessage error description
     * @return void
     */
    public function testBlackholeThrowsException()
    {
        $this->Security->config('blackHoleCallback', '');
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
        $this->Security->config('blackHoleCallback', '');
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
        $fields = urlencode(Security::hash(serialize($fields) . $unlocked . Security::salt()));
        $fields .= urlencode(':Model.hidden|Model.id');
        $this->Controller->request->data = [
            'Model' => [
                'hidden' => 'tampered',
                'id' => '1',
            ],
            '_Token' => compact('fields', 'unlocked', 'debug')
        ];

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
        $fields = urlencode(Security::hash(serialize($fields) . $unlocked . Security::salt()));
        $fields .= urlencode(':Model.hidden|Model.id');
        $this->Controller->request->data = [
            'Model' => [
                'hidden' => ['some-key' => 'some-value'],
                'id' => '1',
            ],
            '_Token' => compact('fields', 'unlocked', 'debug')
        ];

        $result = $this->validatePost('SecurityException', 'Unexpected field \'Model.hidden.some-key\' in POST data, Missing field \'Model.hidden\' in POST data');
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
        $fields = urlencode(Security::hash(serialize($fields) . $unlocked . Security::salt()));
        $fields .= urlencode(':Model.hidden|Model.id');
        $this->Controller->request->data = [
            'Model' => [
                'hidden' => ['some-key' => 'some-value'],
                'id' => '1',
            ],
            '_Token' => compact('fields', 'unlocked', 'debug')
        ];
        Configure::write('debug', false);
        $result = $this->validatePost('SecurityException', 'Unexpected \'_Token.debug\' found in request data');
        $this->assertFalse($result);
    }

    /**
     * testAuthRequiredThrowsExceptionTokenNotFoundPost method
     *
     * Auth required throws exception token not found.
     *
     * @return void
     * @expectedException \Cake\Controller\Exception\AuthSecurityException
     * @expectedExceptionMessage '_Token' was not found in request data.
     * @triggers Controller.startup $this->Controller
     */
    public function testAuthRequiredThrowsExceptionTokenNotFoundPost()
    {
        $this->Security->config('requireAuth', ['protected']);
        $this->Controller->request->params['action'] = 'protected';
        $this->Controller->request->data = 'notEmpty';
        $this->Security->authRequired($this->Controller);
    }

    /**
     * testAuthRequiredThrowsExceptionTokenNotFoundSession method
     *
     * Auth required throws exception token not found in Session.
     *
     * @return void
     * @expectedException \Cake\Controller\Exception\AuthSecurityException
     * @expectedExceptionMessage '_Token' was not found in session.
     * @triggers Controller.startup $this->Controller
     */
    public function testAuthRequiredThrowsExceptionTokenNotFoundSession()
    {
        $this->Security->config('requireAuth', ['protected']);
        $this->Controller->request->params['action'] = 'protected';
        $this->Controller->request->data = ['_Token' => 'not empty'];
        $this->Security->authRequired($this->Controller);
    }

    /**
     * testAuthRequiredThrowsExceptionControllerNotAllowed method
     *
     * Auth required throws exception controller not allowed.
     *
     * @return void
     * @expectedException \Cake\Controller\Exception\AuthSecurityException
     * @expectedExceptionMessage Controller 'NotAllowed' was not found in allowed controllers: 'Allowed, AnotherAllowed'.
     * @triggers Controller.startup $this->Controller
     */
    public function testAuthRequiredThrowsExceptionControllerNotAllowed()
    {
        $this->Security->config('requireAuth', ['protected']);
        $this->Controller->request->params['controller'] = 'NotAllowed';
        $this->Controller->request->params['action'] = 'protected';
        $this->Controller->request->data = ['_Token' => 'not empty'];
        $this->Controller->request->session()->write('_Token', [
            'allowedControllers' => ['Allowed', 'AnotherAllowed']
        ]);
        $this->Security->authRequired($this->Controller);
    }

    /**
     * testAuthRequiredThrowsExceptionActionNotAllowed method
     *
     * Auth required throws exception controller not allowed.
     *
     * @return void
     * @expectedException \Cake\Controller\Exception\AuthSecurityException
     * @expectedExceptionMessage Action 'NotAllowed::protected' was not found in allowed actions: 'index, view'.
     * @triggers Controller.startup $this->Controller
     */
    public function testAuthRequiredThrowsExceptionActionNotAllowed()
    {
        $this->Security->config('requireAuth', ['protected']);
        $this->Controller->request->params['controller'] = 'NotAllowed';
        $this->Controller->request->params['action'] = 'protected';
        $this->Controller->request->data = ['_Token' => 'not empty'];
        $this->Controller->request->session()->write('_Token', [
            'allowedActions' => ['index', 'view']
        ]);
        $this->Security->authRequired($this->Controller);
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
        $this->Security->config('requireAuth', ['protected']);
        $this->Controller->request->params['controller'] = 'Allowed';
        $this->Controller->request->params['action'] = 'protected';
        $this->Controller->request->data = ['_Token' => 'not empty'];
        $this->Controller->request->session()->write('_Token', [
            'allowedActions' => ['protected'],
            'allowedControllers' => ['Allowed'],
        ]);
        $this->assertTrue($this->Security->authRequired($this->Controller));
    }
}
