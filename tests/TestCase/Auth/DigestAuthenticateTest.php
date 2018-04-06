<?php
/**
 * DigestAuthenticateTest file
 *
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

use Cake\Auth\DigestAuthenticate;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\Time;
use Cake\Network\Exception\UnauthorizedException;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Entity for testing with hidden fields.
 */
class ProtectedUser extends Entity
{
    protected $_hidden = ['password'];
}

/**
 * Test case for DigestAuthentication
 */
class DigestAuthenticateTest extends TestCase
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
        $this->auth = new DigestAuthenticate($this->Collection, [
            'realm' => 'localhost',
            'nonce' => 123,
            'opaque' => '123abc'
        ]);

        $password = DigestAuthenticate::password('mariano', 'cake', 'localhost');
        $User = TableRegistry::get('Users');
        $User->updateAll(['password' => $password], []);

        $this->response = $this->getMockBuilder(Response::class)->getMock();
    }

    /**
     * test applying settings in the constructor
     *
     * @return void
     */
    public function testConstructor()
    {
        $object = new DigestAuthenticate($this->Collection, [
            'userModel' => 'AuthUser',
            'fields' => ['username' => 'user', 'password' => 'pass'],
            'nonce' => 123456
        ]);
        $this->assertEquals('AuthUser', $object->config('userModel'));
        $this->assertEquals(['username' => 'user', 'password' => 'pass'], $object->config('fields'));
        $this->assertEquals(123456, $object->config('nonce'));
        $this->assertEquals(env('SERVER_NAME'), $object->config('realm'));
    }

    /**
     * test the authenticate method
     *
     * @return void
     */
    public function testAuthenticateNoData()
    {
        $request = new ServerRequest('posts/index');

        $this->response->expects($this->never())
            ->method('header');

        $this->assertFalse($this->auth->getUser($request, $this->response));
    }

    /**
     * test the authenticate method
     *
     * @return void
     */
    public function testAuthenticateWrongUsername()
    {
        $this->expectException(\Cake\Network\Exception\UnauthorizedException::class);
        $this->expectExceptionCode(401);
        $request = new ServerRequest('posts/index');
        $request->addParams(['pass' => []]);

        $data = [
            'username' => 'incorrect_user',
            'realm' => 'localhost',
            'nonce' => $this->generateNonce(),
            'uri' => '/dir/index.html',
            'qop' => 'auth',
            'nc' => 0000001,
            'cnonce' => '0a4f113b'
        ];
        $data['response'] = $this->auth->generateResponseHash($data, '09faa9931501bf30f0d4253fa7763022', 'GET');
        $request->env('PHP_AUTH_DIGEST', $this->digestHeader($data));

        $this->auth->unauthenticated($request, $this->response);
    }

    /**
     * test that challenge headers are sent when no credentials are found.
     *
     * @return void
     */
    public function testAuthenticateChallenge()
    {
        $request = new ServerRequest([
            'url' => 'posts/index',
            'environment' => ['REQUEST_METHOD' => 'GET']
        ]);
        $request->addParams(['pass' => []]);

        try {
            $this->auth->unauthenticated($request, $this->response);
        } catch (UnauthorizedException $e) {
        }

        $this->assertNotEmpty($e);

        $header = $e->responseHeader()[0];
        $this->assertRegexp(
            '/^WWW\-Authenticate: Digest realm="localhost",qop="auth",nonce="[a-zA-Z0-9=]+",opaque="123abc"$/',
            $e->responseHeader()[0]
        );
    }

    /**
     * test that challenge headers include stale when the nonce is stale
     *
     * @return void
     */
    public function testAuthenticateChallengeIncludesStaleAttributeOnStaleNonce()
    {
        $request = new ServerRequest([
            'url' => 'posts/index',
            'environment' => ['REQUEST_METHOD' => 'GET']
        ]);
        $request->addParams(['pass' => []]);
        $data = [
            'uri' => '/dir/index.html',
            'nonce' => $this->generateNonce(null, 5, strtotime('-10 minutes')),
            'nc' => 1,
            'cnonce' => '123',
            'qop' => 'auth',
        ];
        $data['response'] = $this->auth->generateResponseHash($data, '09faa9931501bf30f0d4253fa7763022', 'GET');
        $request->env('PHP_AUTH_DIGEST', $this->digestHeader($data));

        try {
            $this->auth->unauthenticated($request, $this->response);
        } catch (UnauthorizedException $e) {
        }
        $this->assertNotEmpty($e);

        $header = $e->responseHeader()[0];
        $this->assertContains('stale=true', $header);
    }

    /**
     * Test that authentication fails when a nonce is stale
     *
     * @return void
     */
    public function testAuthenticateFailsOnStaleNonce()
    {
        $request = new ServerRequest([
            'url' => 'posts/index',
            'environment' => ['REQUEST_METHOD' => 'GET']
        ]);
        $request->addParams(['pass' => []]);

        $data = [
            'uri' => '/dir/index.html',
            'nonce' => $this->generateNonce(null, 5, strtotime('-10 minutes')),
            'nc' => 1,
            'cnonce' => '123',
            'qop' => 'auth',
        ];
        $data['response'] = $this->auth->generateResponseHash($data, '09faa9931501bf30f0d4253fa7763022', 'GET');
        $request->env('PHP_AUTH_DIGEST', $this->digestHeader($data));
        $result = $this->auth->authenticate($request, $this->response);
        $this->assertFalse($result, 'Stale nonce should fail');
    }

    /**
     * Test that nonces are required.
     *
     * @return void
     */
    public function testAuthenticateValidUsernamePasswordNoNonce()
    {
        $request = new ServerRequest([
            'url' => 'posts/index',
            'environment' => ['REQUEST_METHOD' => 'GET']
        ]);
        $request->addParams(['pass' => []]);

        $data = [
            'username' => 'mariano',
            'realm' => 'localhos',
            'uri' => '/dir/index.html',
            'nonce' => '',
            'nc' => 1,
            'cnonce' => '123',
            'qop' => 'auth',
        ];
        $data['response'] = $this->auth->generateResponseHash($data, '09faa9931501bf30f0d4253fa7763022', 'GET');
        $request->env('PHP_AUTH_DIGEST', $this->digestHeader($data));
        $result = $this->auth->authenticate($request, $this->response);
        $this->assertFalse($result, 'Empty nonce should fail');
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
            'environment' => ['REQUEST_METHOD' => 'GET']
        ]);
        $request->addParams(['pass' => []]);

        $data = [
            'uri' => '/dir/index.html',
            'nonce' => $this->generateNonce(),
            'nc' => 1,
            'cnonce' => '123',
            'qop' => 'auth',
        ];
        $data['response'] = $this->auth->generateResponseHash($data, '09faa9931501bf30f0d4253fa7763022', 'GET');
        $request->env('PHP_AUTH_DIGEST', $this->digestHeader($data));

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
     * test authenticate success even when digest 'password' is a hidden field.
     *
     * @return void
     */
    public function testAuthenticateSuccessHiddenPasswordField()
    {
        $User = TableRegistry::get('Users');
        $User->setEntityClass(ProtectedUser::class);

        $request = new ServerRequest([
            'url' => 'posts/index',
            'environment' => ['REQUEST_METHOD' => 'GET']
        ]);
        $request->addParams(['pass' => []]);

        $data = [
            'uri' => '/dir/index.html',
            'nonce' => $this->generateNonce(),
            'nc' => 1,
            'cnonce' => '123',
            'qop' => 'auth',
        ];
        $data['response'] = $this->auth->generateResponseHash($data, '09faa9931501bf30f0d4253fa7763022', 'GET');
        $request->env('PHP_AUTH_DIGEST', $this->digestHeader($data));

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
     * test authenticate success
     *
     * @return void
     */
    public function testAuthenticateSuccessSimulatedRequestMethod()
    {
        $request = new ServerRequest([
            'url' => 'posts/index',
            'post' => ['_method' => 'PUT'],
            'environment' => ['REQUEST_METHOD' => 'GET']
        ]);
        $request->addParams(['pass' => []]);

        $data = [
            'username' => 'mariano',
            'uri' => '/dir/index.html',
            'nonce' => $this->generateNonce(),
            'nc' => 1,
            'cnonce' => '123',
            'qop' => 'auth',
        ];
        $data['response'] = $this->auth->generateResponseHash($data, '09faa9931501bf30f0d4253fa7763022', 'GET');
        $request->env('PHP_AUTH_DIGEST', $this->digestHeader($data));

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
     * test scope failure.
     *
     * @return void
     */
    public function testAuthenticateFailReChallenge()
    {
        $this->expectException(\Cake\Network\Exception\UnauthorizedException::class);
        $this->expectExceptionCode(401);
        $this->auth->config('scope.username', 'nate');
        $request = new ServerRequest([
            'url' => 'posts/index',
            'environment' => ['REQUEST_METHOD' => 'GET']
        ]);
        $request->addParams(['pass' => []]);

        $data = [
            'username' => 'invalid',
            'uri' => '/dir/index.html',
            'nonce' => $this->generateNonce(),
            'nc' => 1,
            'cnonce' => '123',
            'qop' => 'auth',
        ];
        $data['response'] = $this->auth->generateResponseHash($data, '09faa9931501bf30f0d4253fa7763022', 'GET');
        $request->env('PHP_AUTH_DIGEST', $this->digestHeader($data));
        $this->auth->unauthenticated($request, $this->response);
    }

    /**
     * testLoginHeaders method
     *
     * @return void
     */
    public function testLoginHeaders()
    {
        $request = new ServerRequest([
            'environment' => ['SERVER_NAME' => 'localhost']
        ]);
        $this->auth = new DigestAuthenticate($this->Collection, [
            'realm' => 'localhost',
        ]);
        $result = $this->auth->loginHeaders($request);

        $this->assertRegexp(
            '/^WWW\-Authenticate: Digest realm="localhost",qop="auth",nonce="[a-zA-Z0-9=]+",opaque="[a-f0-9]+"$/',
            $result
        );
    }

    /**
     * testParseDigestAuthData method
     *
     * @return void
     */
    public function testParseAuthData()
    {
        $digest = <<<DIGEST
            Digest username="Mufasa",
            realm="testrealm@host.com",
            nonce="dcd98b7102dd2f0e8b11d0f600bfb0c093",
            uri="/dir/index.html?query=string&value=some%20value",
            qop=auth,
            nc=00000001,
            cnonce="0a4f113b",
            response="6629fae49393a05397450978507c4ef1",
            opaque="5ccc069c403ebaf9f0171e9517f40e41"
DIGEST;
        $expected = [
            'username' => 'Mufasa',
            'realm' => 'testrealm@host.com',
            'nonce' => 'dcd98b7102dd2f0e8b11d0f600bfb0c093',
            'uri' => '/dir/index.html?query=string&value=some%20value',
            'qop' => 'auth',
            'nc' => '00000001',
            'cnonce' => '0a4f113b',
            'response' => '6629fae49393a05397450978507c4ef1',
            'opaque' => '5ccc069c403ebaf9f0171e9517f40e41'
        ];
        $result = $this->auth->parseAuthData($digest);
        $this->assertSame($expected, $result);

        $result = $this->auth->parseAuthData('');
        $this->assertNull($result);
    }

    /**
     * Test parsing a full URI. While not part of the spec some mobile clients will do it wrong.
     *
     * @return void
     */
    public function testParseAuthDataFullUri()
    {
        $digest = <<<DIGEST
            Digest username="admin",
            realm="192.168.0.2",
            nonce="53a7f9b83f61b",
            uri="http://192.168.0.2/pvcollection/sites/pull/HFD%200001.json#fragment",
            qop=auth,
            nc=00000001,
            cnonce="b85ff144e496e6e18d1c73020566ea3b",
            response="5894f5d9cd41d012bac09eeb89d2ddf2",
            opaque="6f65e91667cf98dd13464deaf2739fde"
DIGEST;

        $expected = 'http://192.168.0.2/pvcollection/sites/pull/HFD%200001.json#fragment';
        $result = $this->auth->parseAuthData($digest);
        $this->assertSame($expected, $result['uri']);
    }

    /**
     * test parsing digest information with email addresses
     *
     * @return void
     */
    public function testParseAuthEmailAddress()
    {
        $digest = <<<DIGEST
            Digest username="mark@example.com",
            realm="testrealm@host.com",
            nonce="dcd98b7102dd2f0e8b11d0f600bfb0c093",
            uri="/dir/index.html",
            qop=auth,
            nc=00000001,
            cnonce="0a4f113b",
            response="6629fae49393a05397450978507c4ef1",
            opaque="5ccc069c403ebaf9f0171e9517f40e41"
DIGEST;
        $expected = [
            'username' => 'mark@example.com',
            'realm' => 'testrealm@host.com',
            'nonce' => 'dcd98b7102dd2f0e8b11d0f600bfb0c093',
            'uri' => '/dir/index.html',
            'qop' => 'auth',
            'nc' => '00000001',
            'cnonce' => '0a4f113b',
            'response' => '6629fae49393a05397450978507c4ef1',
            'opaque' => '5ccc069c403ebaf9f0171e9517f40e41'
        ];
        $result = $this->auth->parseAuthData($digest);
        $this->assertSame($expected, $result);
    }

    /**
     * test password hashing
     *
     * @return void
     */
    public function testPassword()
    {
        $result = DigestAuthenticate::password('mark', 'password', 'localhost');
        $expected = md5('mark:localhost:password');
        $this->assertEquals($expected, $result);
    }

    /**
     * Generate a nonce for testing.
     *
     * @param string $secret The secret to use.
     * @param int $expires Time to live
     * @return string
     */
    protected function generateNonce($secret = null, $expires = 300, $time = null)
    {
        $secret = $secret ?: Configure::read('Security.salt');
        $time = $time ?: microtime(true);
        $expiryTime = $time + $expires;
        $signatureValue = hash_hmac('sha256', $expiryTime . ':' . $secret, $secret);
        $nonceValue = $expiryTime . ':' . $signatureValue;

        return base64_encode($nonceValue);
    }

    /**
     * Create a digest header string from an array of data.
     *
     * @param array $data the data to convert into a header.
     * @return string
     */
    protected function digestHeader($data)
    {
        $data += [
            'username' => 'mariano',
            'realm' => 'localhost',
            'opaque' => '123abc'
        ];
        $digest = <<<DIGEST
Digest username="mariano",
realm="{$data['realm']}",
nonce="{$data['nonce']}",
uri="{$data['uri']}",
qop={$data['qop']},
nc={$data['nc']},
cnonce="{$data['cnonce']}",
response="{$data['response']}",
opaque="{$data['opaque']}"
DIGEST;

        return $digest;
    }
}
