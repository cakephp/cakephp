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
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\Component\FormProtectionComponent;
use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Http\Session;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;

/**
 * FormProtectionComponentTest class
 */
class FormProtectionComponentTest extends TestCase
{
    /**
     * @var \Cake\Controller\Controller
     */
    protected $Controller;

    /**
     * @var \Cake\Controller\Component\FormProtectionComponent
     */
    protected $FormProtection;

    /**
     * setUp method
     *
     * Initializes environment state.
     */
    public function setUp(): void
    {
        parent::setUp();

        $session = new Session();
        $session->id('cli');
        $request = new ServerRequest([
            'url' => '/articles/index',
            'session' => $session,
            'params' => ['controller' => 'Articles', 'action' => 'index'],
        ]);

        $this->Controller = new Controller($request);
        $this->Controller->loadComponent('FormProtection');
        $this->FormProtection = $this->Controller->FormProtection;

        Security::setSalt('foo!');
    }

    public function testConstructorSettingProperties(): void
    {
        $settings = [
            'requireSecure' => ['update_account'],
            'validatePost' => false,
        ];
        $FormProtection = new FormProtectionComponent($this->Controller->components(), $settings);
        $this->assertEquals($FormProtection->validatePost, $settings['validatePost']);
    }

    public function testValidation(): void
    {
        $fields = '4697b45f7f430ff3ab73018c20f315eecb0ba5a6%3AModel.valid';
        $unlocked = '';
        $debug = '';

        $this->Controller->setRequest($this->Controller->getRequest()->withParsedBody([
            'Model' => ['username' => 'nate', 'password' => 'foo', 'valid' => '0'],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ]));

        $event = new Event('Controller.startup', $this->Controller);

        $this->assertNull($this->FormProtection->startup($event));
    }

    public function testValidationWithBaseUrl(): void
    {
        $session = new Session();
        $session->id('cli');
        $request = new ServerRequest([
            'url' => '/articles/index',
            'base' => '/subfolder',
            'webroot' => '/subfolder/',
            'session' => $session,
            'params' => ['controller' => 'Articles', 'action' => 'index'],
        ]);
        Router::setRequest($request);
        $this->Controller->setRequest($request);

        $unlocked = '';
        $fields = ['id' => '1'];
        $debug = urlencode(json_encode([
            '/subfolder/articles/index',
            $fields,
            [],
        ]));
        $fields = hash_hmac(
            'sha1',
            '/subfolder/articles/index' . serialize($fields) . $unlocked . 'cli',
            Security::getSalt()
        );
        $fields .= urlencode(':id');

        $this->Controller->setRequest($this->Controller->getRequest()->withParsedBody([
            'id' => '1',
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ]));

        $event = new Event('Controller.startup', $this->Controller);
        $this->assertNull($this->FormProtection->startup($event));
    }

    public function testValidationOnGetWithData(): void
    {
        $fields = 'an-invalid-token';
        $unlocked = '';
        $debug = urlencode(json_encode([
            'some-action',
            [],
            [],
        ]));

        $this->Controller->setRequest($this->Controller->getRequest()
            ->withEnv('REQUEST_METHOD', 'GET')
            ->withData('Model', ['username' => 'nate', 'password' => 'foo', 'valid' => '0'])
            ->withData('_Token', compact('fields', 'unlocked', 'debug')));

        $event = new Event('Controller.startup', $this->Controller);

        $this->expectException(BadRequestException::class);
        $this->FormProtection->startup($event);
    }

    public function testValidationNoSession(): void
    {
        $unlocked = '';
        $debug = urlencode(json_encode([
            '/articles/index',
            [],
            [],
        ]));

        $fields = 'a5475372b40f6e3ccbf9f8af191f20e1642fd877%3AModel.valid';

        $this->Controller->setRequest($this->Controller->getRequest()->withParsedBody([
            'Model' => ['username' => 'nate', 'password' => 'foo', 'valid' => '0'],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ]));

        $event = new Event('Controller.startup', $this->Controller);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Unexpected field `Model.password` in POST data, Unexpected field `Model.username` in POST data');
        $this->FormProtection->startup($event);
    }

    public function testValidationEmptyForm(): void
    {
        $this->Controller->setRequest($this->Controller->getRequest()
            ->withEnv('REQUEST_METHOD', 'POST')
            ->withParsedBody([]));

        $event = new Event('Controller.startup', $this->Controller);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('`_Token` was not found in request data.');
        $this->FormProtection->startup($event);
    }

    public function testValidationFailTampering(): void
    {
        $unlocked = '';
        $fields = ['Model.hidden' => 'value', 'Model.id' => '1'];
        $debug = urlencode(json_encode([
            '/articles/index',
            $fields,
            [],
        ]));
        $fields = hash_hmac('sha1', '/articles/index' . serialize($fields) . $unlocked . 'cli', Security::getSalt());
        $fields .= urlencode(':Model.hidden|Model.id');

        $this->Controller->setRequest($this->Controller->getRequest()->withParsedBody([
            'Model' => [
                'hidden' => 'tampered',
                'id' => '1',
            ],
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ]));

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Tampered field `Model.hidden` in POST data (expected value `value` but found `tampered`)');

        $event = new Event('Controller.startup', $this->Controller);
        $this->FormProtection->startup($event);
    }

    public function testValidationUnlockedFieldsMismatch(): void
    {
        // Unlocked is empty when the token is created.
        $unlocked = '';
        $fields = ['open', 'title'];
        $debug = urlencode(json_encode([
            '/articles/index',
            $fields,
            [''],
        ]));
        $fields = hash_hmac('sha1', '/articles/index' . serialize($fields) . $unlocked . 'cli', Security::getSalt());

        $this->Controller->setRequest($this->Controller->getRequest()->withParsedBody([
            'open' => 'yes',
            'title' => 'yay',
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ]));

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Missing unlocked field');

        $event = new Event('Controller.startup', $this->Controller);
        $this->FormProtection->setConfig('unlockedFields', ['open']);
        $this->FormProtection->startup($event);
    }

    public function testValidationUnlockedFieldsSuccess(): void
    {
        $unlocked = 'open';
        $fields = ['title'];
        $debug = urlencode(json_encode([
            '/articles/index',
            $fields,
            ['open'],
        ]));
        $fields = hash_hmac('sha1', '/articles/index' . serialize($fields) . $unlocked . 'cli', Security::getSalt());

        $this->Controller->setRequest($this->Controller->getRequest()->withParsedBody([
            'title' => 'yay',
            'open' => 'yes',
            '_Token' => compact('fields', 'unlocked', 'debug'),
        ]));

        $event = new Event('Controller.startup', $this->Controller);
        $this->FormProtection->setConfig('unlockedFields', ['open']);
        $result = $this->FormProtection->startup($event);
        $this->assertNull($result);
    }

    public function testCallbackReturnResponse(): void
    {
        $this->FormProtection->setConfig('validationFailureCallback', function (BadRequestException $exception) {
            return new Response(['body' => 'from callback']);
        });

        $this->Controller->setRequest($this->Controller->getRequest()
            ->withEnv('REQUEST_METHOD', 'POST')
            ->withParsedBody([]));

        $event = new Event('Controller.startup', $this->Controller);

        $result = $this->FormProtection->startup($event);
        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame('from callback', (string)$result->getBody());
    }

    public function testUnlockedActions(): void
    {
        $this->Controller->setRequest($this->Controller->getRequest()->withParsedBody(['data']));

        $this->FormProtection->setConfig('unlockedActions', ['index']);

        $event = new Event('Controller.startup', $this->Controller);
        $result = $this->Controller->FormProtection->startup($event);

        $this->assertNull($result);
    }

    public function testCallbackThrowsException(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('error description');

        $this->FormProtection->setConfig('validationFailureCallback', function (BadRequestException $exception): void {
            throw new NotFoundException('error description');
        });

        $this->Controller->setRequest($this->Controller->getRequest()->withParsedBody(['data']));
        $event = new Event('Controller.startup', $this->Controller);

        $this->Controller->FormProtection->startup($event);
    }

    public function testSettingTokenDataAsRequestAttribute(): void
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->FormProtection->startup($event);

        $securityToken = $this->Controller->getRequest()->getAttribute('formTokenData');
        $this->assertNotEmpty($securityToken);
        $this->assertSame([], $securityToken['unlockedFields']);
    }

    public function testClearingOfTokenFromRequestData(): void
    {
        $this->Controller->setRequest($this->Controller->getRequest()->withParsedBody(['_Token' => 'data']));

        $this->FormProtection->setConfig('validate', false);

        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->FormProtection->startup($event);

        $this->assertSame([], $this->Controller->getRequest()->getParsedBody());
    }
}
