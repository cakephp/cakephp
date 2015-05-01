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
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\Component\SecurityComponent;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Session;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;

/**
 * TestSecurityComponent
 *
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
}

/**
 * SecurityTestController
 *
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
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $session = new Session();
        $request = $this->getMock('Cake\Network\Request', ['here'], ['posts/index']);
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
     * Tear-down method. Resets environment state.
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

    /**
     * Test that requests are still blackholed when controller has incorrect
     * visibility keyword in the blackhole callback
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
        $event = new Event('Controller.startup', $Controller, $this->Controller);
        $Security = new SecurityComponent($Controller->components());
        $Security->config('blackHoleCallback', '_fail');
        $Security->startup($event);
        $Security->blackHole($Controller, 'csrf');
    }

    /**
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
     * test that initialize can set properties.
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
        $this->Controller->Security->requireAuth(['posted']);
        $this->Controller->Security->startup($event);
        $this->assertTrue($this->Controller->failed);

        $this->Security->session->write('_Token', ['allowedControllers' => []]);
        $this->Controller->request->data = ['username' => 'willy', 'password' => 'somePass'];
        $this->Controller->request['action'] = 'posted';
        $this->Controller->Security->requireAuth('posted');
        $this->Controller->Security->startup($event);
        $this->assertTrue($this->Controller->failed);

        $this->Security->session->write('_Token', [
            'allowedControllers' => ['SecurityTest'], 'allowedActions' => ['posted2']
        ]);
        $this->Controller->request->data = ['username' => 'willy', 'password' => 'somePass'];
        $this->Controller->request['action'] = 'posted';
        $this->Controller->Security->requireAuth('posted');
        $this->Controller->Security->startup($event);
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
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->request['action'] = 'posted';
        $this->Controller->Security->requireAuth('posted');
        $this->Controller->Security->startup($event);
        $this->assertFalse($this->Controller->failed);

        $this->Controller->Security->session->write('_Token', [
            'allowedControllers' => ['SecurityTest'], 'allowedActions' => ['posted']
        ]);
        $this->Controller->request['controller'] = 'SecurityTest';
        $this->Controller->request['action'] = 'posted';

        $this->Controller->request->data = [
            'username' => 'willy', 'password' => 'somePass', '_Token' => ''
        ];
        $this->Controller->action = 'posted';
        $this->Controller->Security->requireAuth('posted');
        $this->Controller->Security->startup($event);
        $this->assertFalse($this->Controller->failed);
    }

    /**
     * Simple hash validation test
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePost()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Security->startup($event);

        $fields = '68730b0747d4889ec2766f9117405f9635f5fd5e%3AModel.valid';
        $unlocked = '';

        $this->Controller->request->data = [
            'Model' => ['username' => 'nate', 'password' => 'foo', 'valid' => '0'],
            '_Token' => compact('fields', 'unlocked')
        ];
        $this->assertTrue($this->Controller->Security->validatePost($this->Controller));
    }

    /**
     * Test that validatePost fails if you are missing the session information.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostNoSession()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Security->startup($event);
        $this->Security->session->delete('_Token');

        $fields = 'a5475372b40f6e3ccbf9f8af191f20e1642fd877%3AModel.valid';

        $this->Controller->request->data = [
            'Model' => ['username' => 'nate', 'password' => 'foo', 'valid' => '0'],
            '_Token' => compact('fields')
        ];
        $this->assertFalse($this->Controller->Security->validatePost($this->Controller));
    }

    /**
     * test that validatePost fails if any of its required fields are missing.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostFormHacking()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Security->startup($event);
        $unlocked = '';

        $this->Controller->request->data = [
            'Model' => ['username' => 'nate', 'password' => 'foo', 'valid' => '0'],
            '_Token' => compact('unlocked')
        ];
        $result = $this->Controller->Security->validatePost($this->Controller);
        $this->assertFalse($result, 'validatePost passed when fields were missing. %s');
    }

    /**
     * Test that objects can't be passed into the serialized string. This was a vector for RFI and LFI
     * attacks. Thanks to Felix Wilhelm
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostObjectDeserialize()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Security->startup($event);
        $fields = 'a5475372b40f6e3ccbf9f8af191f20e1642fd877';
        $unlocked = '';

        // a corrupted serialized object, so we can see if it ever gets to deserialize
        $attack = 'O:3:"App":1:{s:5:"__map";a:1:{s:3:"foo";s:7:"Hacked!";s:1:"fail"}}';
        $fields .= urlencode(':' . str_rot13($attack));

        $this->Controller->request->data = [
            'Model' => ['username' => 'mark', 'password' => 'foo', 'valid' => '0'],
            '_Token' => compact('fields', 'unlocked')
        ];
        $result = $this->Controller->Security->validatePost($this->Controller);
        $this->assertFalse($result, 'validatePost passed when key was missing. %s');
    }

    /**
     * Tests validation post data ignores `_csrfToken`.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostIgnoresCsrfToken()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Security->startup($event);

        $fields = '8e26ef05379e5402c2c619f37ee91152333a0264%3A';
        $unlocked = '';

        $this->Controller->request->data = [
            '_csrfToken' => 'abc123',
            'Model' => ['multi_field' => ['1', '3']],
            '_Token' => compact('fields', 'unlocked')
        ];
        $this->assertTrue($this->Controller->Security->validatePost($this->Controller));
    }

    /**
     * Tests validation of checkbox arrays
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostArray()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Security->startup($event);

        $fields = '8e26ef05379e5402c2c619f37ee91152333a0264%3A';
        $unlocked = '';

        $this->Controller->request->data = [
            'Model' => ['multi_field' => ['1', '3']],
            '_Token' => compact('fields', 'unlocked')
        ];
        $this->assertTrue($this->Controller->Security->validatePost($this->Controller));

        $this->Controller->request->data = [
            'Model' => ['multi_field' => [12 => '1', 20 => '3']],
            '_Token' => compact('fields', 'unlocked')
        ];
        $this->assertTrue($this->Controller->Security->validatePost($this->Controller));
    }

    /**
     * Tests validation of integer field names.
     *
     * @return void
     */
    public function testValidateIntFieldName()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Security->startup($event);

        $fields = '4a221010dd7a23f7166cb10c38bc21d81341c387%3A';
        $unlocked = '';

        $this->Controller->request->data = [
            1 => 'value,',
            '_Token' => compact('fields', 'unlocked')
        ];
        $this->assertTrue($this->Controller->Security->validatePost($this->Controller));
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
        $this->Controller->Security->startup($event);

        $fields = 'a1c3724b7ba85e7022413611e30ba2c6181d5aba%3A';
        $unlocked = '';

        $this->Controller->request->data = [
            'anything' => 'some_data',
            '_Token' => compact('fields', 'unlocked')
        ];

        $result = $this->Controller->Security->validatePost($this->Controller);
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
        $this->Controller->Security->startup($event);

        $fields = 'b0914d06dfb04abf1fada53e16810e87d157950b%3A';
        $unlocked = '';

        $this->Controller->request->data = [
            'Model' => ['username' => '', 'password' => ''],
            '_Token' => compact('fields', 'unlocked')
        ];

        $result = $this->Controller->Security->validatePost($this->Controller);
        $this->assertTrue($result);
    }

    /**
     * Tests hash validation for multiple records, including locked fields
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostComplex()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Security->startup($event);

        $fields = 'b65c7463e44a61d8d2eaecce2c265b406c9c4742%3AAddresses.0.id%7CAddresses.1.id';
        $unlocked = '';

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
            '_Token' => compact('fields', 'unlocked')
        ];
        $result = $this->Controller->Security->validatePost($this->Controller);
        $this->assertTrue($result);
    }

    /**
     * test ValidatePost with multiple select elements.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostMultipleSelect()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Security->startup($event);

        $fields = '8d8da68ba03b3d6e7e145b948abfe26741422169%3A';
        $unlocked = '';

        $this->Controller->request->data = [
            'Tag' => ['Tag' => [1, 2]],
            '_Token' => compact('fields', 'unlocked'),
        ];
        $result = $this->Controller->Security->validatePost($this->Controller);
        $this->assertTrue($result);

        $this->Controller->request->data = [
            'Tag' => ['Tag' => [1, 2, 3]],
            '_Token' => compact('fields', 'unlocked'),
        ];
        $result = $this->Controller->Security->validatePost($this->Controller);
        $this->assertTrue($result);

        $this->Controller->request->data = [
            'Tag' => ['Tag' => [1, 2, 3, 4]],
            '_Token' => compact('fields', 'unlocked'),
        ];
        $result = $this->Controller->Security->validatePost($this->Controller);
        $this->assertTrue($result);

        $fields = 'eae2adda1628b771a30cc133342d16220c6520fe%3A';
        $this->Controller->request->data = [
            'User.password' => 'bar', 'User.name' => 'foo', 'User.is_valid' => '1',
            'Tag' => ['Tag' => [1]],
            '_Token' => compact('fields', 'unlocked'),
        ];
        $result = $this->Controller->Security->validatePost($this->Controller);
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
        $this->Controller->Security->startup($event);
        $fields = '68730b0747d4889ec2766f9117405f9635f5fd5e%3AModel.valid';
        $unlocked = '';

        $this->Controller->request->data = [
            'Model' => ['username' => '', 'password' => '', 'valid' => '0'],
            '_Token' => compact('fields', 'unlocked')
        ];

        $result = $this->Controller->Security->validatePost($this->Controller);
        $this->assertTrue($result);

        $fields = 'f63e4a69b2edd31f064e8e602a04dd59307cfe9c%3A';

        $this->Controller->request->data = [
            'Model' => ['username' => '', 'password' => '', 'valid' => '0'],
            '_Token' => compact('fields', 'unlocked')
        ];

        $result = $this->Controller->Security->validatePost($this->Controller);
        $this->assertTrue($result);

        $this->Controller->request->data = [];
        $this->Controller->Security->startup($event);

        $this->Controller->request->data = [
            'Model' => ['username' => '', 'password' => '', 'valid' => '0'],
            '_Token' => compact('fields', 'unlocked')
        ];

        $result = $this->Controller->Security->validatePost($this->Controller);
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
        $this->Controller->Security->startup($event);
        $fields = '973a8939a68ac014cc6f7666cec9aa6268507350%3AModel.hidden%7CModel.other_hidden';
        $unlocked = '';

        $this->Controller->request->data = [
            'Model' => [
                'username' => '', 'password' => '', 'hidden' => '0',
                'other_hidden' => 'some hidden value'
            ],
            '_Token' => compact('fields', 'unlocked')
        ];
        $result = $this->Controller->Security->validatePost($this->Controller);
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
        $this->Controller->Security->config('disabledFields', ['Model.username', 'Model.password']);
        $this->Controller->Security->startup($event);
        $fields = '1c59acfbca98bd870c11fb544d545cbf23215880%3AModel.hidden';
        $unlocked = '';

        $this->Controller->request->data = [
            'Model' => [
                'username' => '', 'password' => '', 'hidden' => '0'
            ],
            '_Token' => compact('fields', 'unlocked')
        ];

        $result = $this->Controller->Security->validatePost($this->Controller);
        $this->assertTrue($result);
    }

    /**
     * test validating post data with posted unlocked fields.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostDisabledFieldsInData()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Security->startup($event);
        $unlocked = 'Model.username';
        $fields = ['Model.hidden', 'Model.password'];
        $fields = urlencode(Security::hash('/articles/index' . serialize($fields) . $unlocked . Security::salt()));

        $this->Controller->request->data = [
            'Model' => [
                'username' => 'mark',
                'password' => 'sekret',
                'hidden' => '0'
            ],
            '_Token' => compact('fields', 'unlocked')
        ];

        $result = $this->Controller->Security->validatePost($this->Controller);
        $this->assertTrue($result);
    }

    /**
     * test that missing 'unlocked' input causes failure
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostFailNoDisabled()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Security->startup($event);
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

        $result = $this->Controller->Security->validatePost($this->Controller);
        $this->assertFalse($result);
    }

    /**
     * Test that validatePost fails when unlocked fields are changed.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostFailDisabledFieldTampering()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Security->startup($event);
        $unlocked = 'Model.username';
        $fields = ['Model.hidden', 'Model.password'];
        $fields = urlencode(Security::hash(serialize($fields) . $unlocked . Security::salt()));

        // Tamper the values.
        $unlocked = 'Model.username|Model.password';

        $this->Controller->request->data = [
            'Model' => [
                'username' => 'mark',
                'password' => 'sekret',
                'hidden' => '0'
            ],
            '_Token' => compact('fields', 'unlocked')
        ];

        $result = $this->Controller->Security->validatePost($this->Controller);
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
        $this->Controller->Security->startup($event);
        $fields = '075ca6c26c38a09a78d871201df89faf52cbbeb8%3AModel.valid%7CModel2.valid%7CModel3.valid';
        $unlocked = '';

        $this->Controller->request->data = [
            'Model' => ['username' => '', 'password' => '', 'valid' => '0'],
            'Model2' => ['valid' => '0'],
            'Model3' => ['valid' => '0'],
            '_Token' => compact('fields', 'unlocked')
        ];
        $result = $this->Controller->Security->validatePost($this->Controller);
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
        $this->Controller->Security->startup($event);
        $fields = '24a753fb62ef7839389987b58e3f7108f564e529%3AModel.0.hidden%7CModel.0.valid';
        $fields .= '%7CModel.1.hidden%7CModel.1.valid';
        $unlocked = '';

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
            '_Token' => compact('fields', 'unlocked')
        ];

        $result = $this->Controller->Security->validatePost($this->Controller);
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
        $this->Controller->Security->startup($event);
        $fields = '8f7d82bf7656cf068822d9bdab109ebed1be1825%3AAddress.0.id%7CAddress.0.primary%7C';
        $fields .= 'Address.1.id%7CAddress.1.primary';
        $unlocked = '';

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
            '_Token' => compact('fields', 'unlocked')
        ];

        $result = $this->Controller->Security->validatePost($this->Controller);
        $this->assertTrue($result);
    }

    /**
     * Test that values like Foo.0.1
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidateNestedNumericSets()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Security->startup($event);
        $unlocked = '';
        $hashFields = ['TaxonomyData'];
        $fields = urlencode(Security::hash('/articles/index' . serialize($hashFields) . $unlocked . Security::salt()));

        $this->Controller->request->data = [
            'TaxonomyData' => [
                1 => [[2]],
                2 => [[3]]
            ],
            '_Token' => compact('fields', 'unlocked')
        ];
        $result = $this->Controller->Security->validatePost($this->Controller);
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
        $this->Controller->Security->startup($event);
        $fields = '7a203edb3d345bbf38fe0dccae960da8842e11d7%3AAddress.0.id%7CAddress.0.primary%7C';
        $fields .= 'Address.1.id%7CAddress.1.primary';
        $unlocked = '';

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
            '_Token' => compact('fields', 'unlocked')
        ];

        $result = $this->Controller->Security->validatePost($this->Controller);
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

        $this->Controller->Security->startup($event);
        $fields = '9da2b3fa2b5b8ac0bfbc1bbce145e58059629125%3An%3A0%3A%7B%7D';
        $unlocked = '';

        $this->Controller->request->data = [
            'MyModel' => ['name' => 'some data'],
            '_Token' => compact('fields', 'unlocked')
        ];
        $result = $this->Controller->Security->validatePost($this->Controller);
        $this->assertFalse($result);

        $this->Controller->Security->startup($event);
        $this->Controller->Security->config('disabledFields', ['MyModel.name']);

        $this->Controller->request->data = [
            'MyModel' => ['name' => 'some data'],
            '_Token' => compact('fields', 'unlocked')
        ];

        $result = $this->Controller->Security->validatePost($this->Controller);
        $this->assertTrue($result);
    }

    /**
     * test validatePost with radio buttons
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidatePostRadio()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Security->startup($event);
        $fields = 'c2226a8879c3f4b513691295fc2519a29c44c8bb%3An%3A0%3A%7B%7D';
        $unlocked = '';

        $this->Controller->request->data = [
            '_Token' => compact('fields', 'unlocked')
        ];
        $result = $this->Controller->Security->validatePost($this->Controller);
        $this->assertFalse($result);

        $this->Controller->request->data = [
            '_Token' => compact('fields', 'unlocked'),
            'Test' => ['test' => '']
        ];
        $result = $this->Controller->Security->validatePost($this->Controller);
        $this->assertTrue($result);

        $this->Controller->request->data = [
            '_Token' => compact('fields', 'unlocked'),
            'Test' => ['test' => '1']
        ];
        $result = $this->Controller->Security->validatePost($this->Controller);
        $this->assertTrue($result);

        $this->Controller->request->data = [
            '_Token' => compact('fields', 'unlocked'),
            'Test' => ['test' => '2']
        ];
        $result = $this->Controller->Security->validatePost($this->Controller);
        $this->assertTrue($result);
    }

    /**
     * test validatePost uses here() as a hash input.
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

        $this->Controller->request->data = [
            'Model' => ['username' => '', 'password' => ''],
            '_Token' => compact('fields', 'unlocked')
        ];
        $this->assertTrue($this->Security->validatePost($this->Controller));

        $request = $this->getMock('Cake\Network\Request', ['here']);
        $request->expects($this->at(0))
            ->method('here')
            ->will($this->returnValue('/posts/index?page=1'));
        $request->expects($this->at(1))
            ->method('here')
            ->will($this->returnValue('/posts/edit/1'));
        $request->data = $this->Controller->request->data;
        $this->Controller->request = $request;

        $this->assertFalse($this->Security->validatePost($this->Controller));
        $this->assertFalse($this->Security->validatePost($this->Controller));
    }

    /**
     * test that blackhole doesn't delete the _Token session key so repeat data submissions
     * stay blackholed.
     *
     * @link https://cakephp.lighthouseapp.com/projects/42648/tickets/214
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testBlackHoleNotDeletingSessionInformation()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Security->startup($event);

        $this->Controller->Security->blackHole($this->Controller, 'auth');
        $this->assertTrue($this->Controller->Security->session->check('_Token'), '_Token was deleted by blackHole %s');
    }

    /**
     * Test generateToken()
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
     * Test unlocked actions
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testUnlockedActions()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->request->data = ['data'];
        $this->Controller->Security->unlockedActions = 'index';
        $this->Controller->Security->blackHoleCallback = null;
        $result = $this->Controller->Security->startup($event);
        $this->assertNull($result);
    }
}
