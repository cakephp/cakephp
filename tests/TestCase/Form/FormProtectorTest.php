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
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Form;

use Cake\Core\Configure;
use Cake\Form\FormProtector;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;

/**
 * FormProtectorTest class
 */
class FormProtectorTest extends TestCase
{
    /**
     * @var string
     */
    protected $url = '/articles/index';

    /**
     * @var string
     */
    protected $sessionId = 'cli';

    public function setUp(): void
    {
        parent::setUp();

        Security::setSalt('foo!');

        // $this->protector = new FormProtector('http://localhost/articles/index', 'cli');
    }

    /**
     * Helper function for validation.
     *
     * @param array $data
     * @param string|null $errorMessage
     * @return void
     */
    public function validate($data, $errorMessage = null)
    {
        $protector = new FormProtector();
        $result = $protector->validate($data, $this->url, $this->sessionId);

        if ($errorMessage === null) {
            $this->assertTrue($result);
        } else {
            $this->assertFalse($result);
            $this->assertSame($errorMessage, $protector->getError());
        }
    }

    /**
     * testValidate method
     *
     * Simple hash validation test
     *
     * @return void
     */
    public function testValidate(): void
    {
        $fields = '4697b45f7f430ff3ab73018c20f315eecb0ba5a6%3AModel.valid';
        $unlocked = '';
        $debug = '';

        $data = [
            'Model' => ['username' => 'nate', 'password' => 'foo', 'valid' => '0'],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
        $this->validate($data);
    }

    /**
     * testValidateNoUnlockedInRequestData method
     *
     * Test that validate fails if you are missing unlocked in request data.
     *
     * @return void
     */
    public function testValidateNoUnlockedInRequestData(): void
    {
        $fields = 'a5475372b40f6e3ccbf9f8af191f20e1642fd877%3AModel.valid';

        $data = [
            'Model' => ['username' => 'nate', 'password' => 'foo', 'valid' => '0'],
            '_Token' => compact('fields'),
        ];

        $this->validate($data, '`_Token.unlocked` was not found in request data.');
    }

    /**
     * testValidateFormHacking method
     *
     * Test that validate fails if any of its required fields are missing.
     *
     * @return void
     */
    public function testValidateFormHacking(): void
    {
        $unlocked = '';

        $data = [
            'Model' => ['username' => 'nate', 'password' => 'foo', 'valid' => '0'],
            '_Token' => compact('unlocked'),
        ];

        $this->validate($data, '`_Token.fields` was not found in request data.');
    }

    /**
     * testValidateEmptyForm method
     *
     * Test that validate fails if empty form is submitted.
     *
     * @return void
     */
    public function testValidateEmptyForm(): void
    {
        $this->validate([], '`_Token` was not found in request data.');
    }

    /**
     * testValidate array fields method
     *
     * Test that validate fails if empty form is submitted.
     *
     * @return void
     */
    public function testValidateInvalidFields(): void
    {
        $data = [
            '_Token' => [
                'debug' => '',
                'unlocked' => '',
                'fields' => [],
            ],
        ];
        $this->validate($data, '`_Token.fields` is invalid.');
    }

    /**
     * testValidateObjectDeserialize
     *
     * Test that objects can't be passed into the serialized string. This was a vector for RFI and LFI
     * attacks. Thanks to Felix Wilhelm
     *
     * @return void
     */
    public function testValidateObjectDeserialize(): void
    {
        $fields = 'a5475372b40f6e3ccbf9f8af191f20e1642fd877';
        $unlocked = '';
        $debug = urlencode(json_encode([
            '/articles/index',
            ['Model.password', 'Model.username', 'Model.valid'],
            [],
        ]));

        // a corrupted serialized object, so we can see if it ever gets to deserialize
        $attack = 'O:3:"App":1:{s:5:"__map";a:1:{s:3:"foo";s:7:"Hacked!";s:1:"fail"}}';
        $fields .= urlencode(':' . str_rot13($attack));

        $data = [
            'Model' => ['username' => 'mark', 'password' => 'foo', 'valid' => '0'],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];

        $protector = new FormProtector();
        $result = $protector->validate($data, $this->url, $this->sessionId);
        $this->assertFalse($result);
    }

    /**
     * testValidateArray method
     *
     * Tests validation of checkbox arrays.
     *
     * @return void
     */
    public function testValidateArray(): void
    {
        $fields = 'f95b472a63f1d883b9eaacaf8a8e36e325e3fe82%3A';
        $unlocked = '';
        $debug = urlencode(json_encode([
            'some-action',
            [],
            [],
        ]));

        $data = [
            'Model' => ['multi_field' => ['1', '3']],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
        $this->validate($data);

        $data = [
            'Model' => ['multi_field' => [12 => '1', 20 => '3']],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
        $this->validate($data);
    }

    /**
     * testValidateIntFieldName method
     *
     * Tests validation of integer field names.
     *
     * @return void
     */
    public function testValidateIntFieldName(): void
    {
        $fields = '11f87a5962db9ac26405e460cd3063bb6ff76cf8%3A';
        $unlocked = '';
        $debug = urlencode(json_encode([
            'some-action',
            [],
            [],
        ]));

        $data = [
            1 => 'value,',
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
        $this->validate($data);
    }

    /**
     * testValidateNoModel method
     *
     * @return void
     */
    public function testValidateNoModel(): void
    {
        $fields = 'a2a942f587deb20e90241c51b59d901d8a7f796b%3A';
        $unlocked = '';
        $debug = 'not used';

        $data = [
            'anything' => 'some_data',
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];

        $this->validate($data);
    }

    /**
     * test validate uses full URL
     *
     * @return void
     */
    public function testValidateSubdirectory(): void
    {
        $this->url = '/subdir' . $this->url;

        $fields = 'cc9b6af3f33147235ae8f8037b0a71399a2425f2%3A';
        $unlocked = '';
        $debug = '';

        $data = [
            'Model' => ['username' => '', 'password' => ''],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];

        $this->validate($data);
    }

    /**
     * testValidateComplex method
     *
     * Tests hash validation for multiple records, including locked fields.
     *
     * @return void
     */
    public function testValidateComplex(): void
    {
        $fields = 'b00b7e5c2e3bf8bc474fb7cfde6f9c2aa06ab9bc%3AAddresses.0.id%7CAddresses.1.id';
        $unlocked = '';
        $debug = 'not used';

        $data = [
            'Addresses' => [
                '0' => [
                    'id' => '123456', 'title' => '', 'first_name' => '', 'last_name' => '',
                    'address' => '', 'city' => '', 'phone' => '', 'primary' => '',
                ],
                '1' => [
                    'id' => '654321', 'title' => '', 'first_name' => '', 'last_name' => '',
                    'address' => '', 'city' => '', 'phone' => '', 'primary' => '',
                ],
            ],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
        $this->validate($data);
    }

    /**
     * testValidateMultipleSelect method
     *
     * Test ValidatePost with multiple select elements.
     *
     * @return void
     */
    public function testValidateMultipleSelect(): void
    {
        $fields = '28dd05f0af314050784b18b3366857e8e8c78e73%3A';
        $unlocked = '';
        $debug = 'not used';

        $data = [
            'Tag' => ['Tag' => [1, 2]],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
        $this->validate($data);

        $data = [
            'Tag' => ['Tag' => [1, 2, 3]],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
        $this->validate($data);

        $data = [
            'Tag' => ['Tag' => [1, 2, 3, 4]],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
        $this->validate($data);

        $fields = '1e4c9269b64756e9b141d364497c5f037b428a37%3A';
        $data = [
            'User.password' => 'bar', 'User.name' => 'foo', 'User.is_valid' => '1',
            'Tag' => ['Tag' => [1]],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
        $this->validate($data);
    }

    /**
     * testValidateCheckbox method
     *
     * First block tests un-checked checkbox
     * Second block tests checked checkbox
     *
     * @return void
     */
    public function testValidateCheckbox(): void
    {
        $fields = '4697b45f7f430ff3ab73018c20f315eecb0ba5a6%3AModel.valid';
        $unlocked = '';
        $debug = 'not used';

        $data = [
            'Model' => ['username' => '', 'password' => '', 'valid' => '0'],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
        $this->validate($data);

        $fields = '3f368401f9a8610bcace7746039651066cdcdc38%3A';

        $data = [
            'Model' => ['username' => '', 'password' => '', 'valid' => '0'],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
        $this->validate($data);

        $data = [
            'Model' => ['username' => '', 'password' => '', 'valid' => '0'],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
        $this->validate($data);
    }

    /**
     * testValidateHidden method
     *
     * @return void
     */
    public function testValidateHidden(): void
    {
        $fields = '96e61bded2b62b0c420116a0eb06a3b3acddb8f1%3AModel.hidden%7CModel.other_hidden';
        $unlocked = '';
        $debug = 'not used';

        $data = [
            'Model' => [
                'username' => '', 'password' => '', 'hidden' => '0',
                'other_hidden' => 'some hidden value',
            ],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
        $this->validate($data);
    }

    /**
     * testValidateDisabledFieldsInData method
     *
     * Test validating post data with posted unlocked fields.
     *
     * @return void
     */
    public function testValidateDisabledFieldsInData(): void
    {
        $unlocked = 'Model.username';
        $fields = ['Model.hidden', 'Model.password'];
        $fields = urlencode(
            hash_hmac('sha1', '/articles/index' . serialize($fields) . $unlocked . 'cli', Security::getSalt())
        );
        $debug = 'not used';

        $data = [
            'Model' => [
                'username' => 'mark',
                'password' => 'sekret',
                'hidden' => '0',
            ],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];

        $this->validate($data);
    }

    /**
     * testValidateFailNoDisabled method
     *
     * Test that missing 'unlocked' input causes failure.
     *
     * @return void
     */
    public function testValidateFailNoDisabled(): void
    {
        $fields = ['Model.hidden', 'Model.password', 'Model.username'];
        $fields = urlencode(Security::hash(serialize($fields) . Security::getSalt()));

        $data = [
            'Model' => [
                'username' => 'mark',
                'password' => 'sekret',
                'hidden' => '0',
            ],
            '_Token' => compact('fields'),
        ];

        $this->validate($data, '`_Token.unlocked` was not found in request data.');
    }

    /**
     * testValidateFailNoDebug method
     *
     * Test that missing 'debug' input causes failure.
     *
     * @return void
     */
    public function testValidateFailNoDebug(): void
    {
        $fields = ['Model.hidden', 'Model.password', 'Model.username'];
        $fields = urlencode(Security::hash(serialize($fields) . Security::getSalt()));
        $unlocked = '';

        $data = [
            'Model' => [
                'username' => 'mark',
                'password' => 'sekret',
                'hidden' => '0',
            ],
            '_Token' => compact('fields', 'unlocked'),
        ];

        $this->validate($data, '`_Token.debug` was not found in request data.');
    }

    /**
     * testValidateFailNoDebugMode method
     *
     * Test that missing 'debug' input is not the problem when debug mode disabled.
     *
     * @return void
     */
    public function testValidateFailNoDebugMode(): void
    {
        $fields = ['Model.hidden', 'Model.password', 'Model.username'];
        $fields = urlencode(Security::hash(serialize($fields) . Security::getSalt()));
        $unlocked = '';

        $data = [
            'Model' => [
                'username' => 'mark',
                'password' => 'sekret',
                'hidden' => '0',
            ],
            '_Token' => compact('fields', 'unlocked'),
        ];
        Configure::write('debug', false);
        $protector = new FormProtector();
        $result = $protector->validate($data, $this->url, $this->sessionId);
        $this->assertFalse($result);
    }

    /**
     * testValidateFailDisabledFieldTampering method
     *
     * Test that validate fails when unlocked fields are changed.
     *
     * @return void
     */
    public function testValidateFailDisabledFieldTampering(): void
    {
        $unlocked = 'Model.username';
        $fields = ['Model.hidden', 'Model.password'];
        $fields = urlencode(Security::hash(serialize($fields) . $unlocked . Security::getSalt()));
        $debug = urlencode(json_encode([
            '/articles/index',
            ['Model.hidden', 'Model.password'],
            ['Model.username'],
        ]));

        // Tamper the values.
        $unlocked = 'Model.username|Model.password';

        $data = [
            'Model' => [
                'username' => 'mark',
                'password' => 'sekret',
                'hidden' => '0',
            ],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];

        $this->validate($data, 'Missing field `Model.password` in POST data, Unexpected unlocked field `Model.password` in POST data');
    }

    /**
     * testValidateHiddenMultipleModel method
     *
     * @return void
     */
    public function testValidateHiddenMultipleModel(): void
    {
        $fields = '642b7a6db3b848fab88952b86ea36c572f93df40%3AModel.valid%7CModel2.valid%7CModel3.valid';
        $unlocked = '';
        $debug = 'not used';

        $data = [
            'Model' => ['username' => '', 'password' => '', 'valid' => '0'],
            'Model2' => ['valid' => '0'],
            'Model3' => ['valid' => '0'],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
        $this->validate($data);
    }

    /**
     * testValidateHasManyModel method
     *
     * @return void
     */
    public function testValidateHasManyModel(): void
    {
        $fields = '792324c8a374772ad82acfb28f0e77e70f8ed3af%3AModel.0.hidden%7CModel.0.valid';
        $fields .= '%7CModel.1.hidden%7CModel.1.valid';
        $unlocked = '';
        $debug = 'not used';

        $data = [
            'Model' => [
                [
                    'username' => 'username', 'password' => 'password',
                    'hidden' => 'value', 'valid' => '0',
                ],
                [
                    'username' => 'username', 'password' => 'password',
                    'hidden' => 'value', 'valid' => '0',
                ],
            ],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];

        $this->validate($data);
    }

    /**
     * testValidateHasManyRecordsPass method
     *
     * @return void
     */
    public function testValidateHasManyRecordsPass(): void
    {
        $fields = '7f4bff67558e25ebeea44c84ea4befa8d50b080c%3AAddress.0.id%7CAddress.0.primary%7C';
        $fields .= 'Address.1.id%7CAddress.1.primary';
        $unlocked = '';
        $debug = 'not used';

        $data = [
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
                    'primary' => '1',
                ],
            ],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];

        $this->validate($data);
    }

    /**
     * testValidateHasManyRecords method
     *
     * validate should fail, hidden fields have been changed.
     *
     * @return void
     */
    public function testValidateHasManyRecordsFail(): void
    {
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
                'Address.1.primary' => '1',
            ],
            [],
        ]));

        $data = [
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
                    'primary' => '1',
                ],
            ],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];

        $protector = new FormProtector();
        $result = $protector->validate($data, $this->url, $this->sessionId);
        $this->assertFalse($result);
    }

    /**
     * testValidateRadio method
     *
     * Test validate with radio buttons.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testValidateRadio(): void
    {
        $fields = 'a709dfdee0a0cce52c4c964a1b8a56159bb081b4%3An%3A0%3A%7B%7D';
        $unlocked = '';
        $debug = urlencode(json_encode([
            '/articles/index',
            [],
            [],
        ]));

        $data = [
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
        $protector = new FormProtector();
        $result = $protector->validate($data, $this->url, $this->sessionId);
        $this->assertFalse($result);

        $data = [
            '_Token' => compact('fields', 'unlocked', 'debug'),
            'Test' => ['test' => ''],
        ];
        $this->validate($data);

        $data = [
            '_Token' => compact('fields', 'unlocked', 'debug'),
            'Test' => ['test' => '1'],
        ];
        $this->validate($data);

        $data = [
            '_Token' => compact('fields', 'unlocked', 'debug'),
            'Test' => ['test' => '2'],
        ];
        $this->validate($data);
    }

    /**
     * testValidateUrlAsHashInput method
     *
     * Test validate uses here() as a hash input.
     *
     * @return void
     */
    public function testValidateUrlAsHashInput(): void
    {
        $fields = 'de2ca3670dd06c29558dd98482c8739e86da2c7c%3A';
        $unlocked = '';
        $debug = urlencode(json_encode([
            'another-url',
            ['Model.username', 'Model.password'],
            [],
        ]));

        $data = [
            'Model' => ['username' => '', 'password' => ''],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
        $this->validate($data);

        $this->url = '/posts/index?page=1';
        $this->validate(
            $data,
            'URL mismatch in POST data (expected `another-url` but found `/posts/index?page=1`)'
        );

        $this->url = '/posts/edit/1';
        $this->validate(
            $data,
            'URL mismatch in POST data (expected `another-url` but found `/posts/edit/1`)'
        );
    }

    /**
     * testValidateDebugFormat method
     *
     * Test that debug token format is right.
     *
     * @return void
     */
    public function testValidateDebugFormat(): void
    {
        $unlocked = 'Model.username';
        $fields = ['Model.hidden', 'Model.password'];
        $fields = urlencode(Security::hash(serialize($fields) . $unlocked . Security::getSalt()));
        $debug = urlencode(json_encode([
            '/articles/index',
            ['Model.hidden', 'Model.password'],
            ['Model.username'],
            ['not expected'],
        ]));

        $data = [
            'Model' => [
                'username' => 'mark',
                'password' => 'sekret',
                'hidden' => '0',
            ],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];

        $this->validate($data, 'Invalid form protection debug token.');

        $debug = urlencode(json_encode('not an array'));
        $this->validate($data, 'Invalid form protection debug token.');
    }

    /**
     * testValidateFailTampering method
     *
     * Test that validate fails with tampered fields and explanation.
     *
     * @return void
     */
    public function testValidateFailTampering(): void
    {
        $unlocked = '';
        $fields = ['Model.hidden' => 'value', 'Model.id' => '1'];
        $debug = urlencode(json_encode([
            '/articles/index',
            $fields,
            [],
        ]));
        $fields = urlencode(Security::hash(serialize($fields) . $unlocked . Security::getSalt()));
        $fields .= urlencode(':Model.hidden|Model.id');
        $data = [
            'Model' => [
                'hidden' => 'tampered',
                'id' => '1',
            ],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];

        $this->validate($data, 'Tampered field `Model.hidden` in POST data (expected value `value` but found `tampered`)');
    }

    /**
     * testValidateFailTamperingMutatedIntoArray method
     *
     * Test that validate fails with tampered fields and explanation.
     *
     * @return void
     */
    public function testValidateFailTamperingMutatedIntoArray(): void
    {
        $unlocked = '';
        $fields = ['Model.hidden' => 'value', 'Model.id' => '1'];
        $debug = urlencode(json_encode([
            '/articles/index',
            $fields,
            [],
        ]));
        $fields = urlencode(Security::hash(serialize($fields) . $unlocked . Security::getSalt()));
        $fields .= urlencode(':Model.hidden|Model.id');
        $data = [
            'Model' => [
                'hidden' => ['some-key' => 'some-value'],
                'id' => '1',
            ],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];

        $this->validate(
            $data,
            'Unexpected field `Model.hidden.some-key` in POST data, Missing field `Model.hidden` in POST data'
        );
    }

    /**
     * testValidateUnexpectedDebugToken method
     *
     * Test that debug token should not be sent if debug is disabled.
     *
     * @return void
     */
    public function testValidateUnexpectedDebugToken(): void
    {
        $unlocked = '';
        $fields = ['Model.hidden' => 'value', 'Model.id' => '1'];
        $debug = urlencode(json_encode([
            '/articles/index',
            $fields,
            [],
        ]));
        $fields = urlencode(Security::hash(serialize($fields) . $unlocked . Security::getSalt()));
        $fields .= urlencode(':Model.hidden|Model.id');
        $data = [
            'Model' => [
                'hidden' => ['some-key' => 'some-value'],
                'id' => '1',
            ],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ];
        Configure::write('debug', false);
        $this->validate($data, 'Unexpected `_Token.debug` found in request data');
    }
}
