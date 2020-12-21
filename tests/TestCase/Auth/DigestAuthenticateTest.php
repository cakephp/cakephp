<?php
declare(strict_types=1);

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
use Cake\Http\Exception\UnauthorizedException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\Time;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;
use TestApp\Model\Entity\ProtectedUser;

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
    protected $fixtures = ['core.AuthUsers', 'core.Users'];

    /**
     * @var \Cake\Controller\ComponentRegistry
     */
    protected $collection;

    /**
     * @var \Cake\Auth\DigestAuthenticate
     */
    protected $auth;

    /**
     * setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->collection = new ComponentRegistry();
        $this->auth = new DigestAuthenticate($this->collection, [
            'realm' => 'localhost',
            'nonce' => 123,
            'opaque' => '123abc',
            'secret' => Security::getSalt(),
            'passwordHasher' => 'ShouldNeverTryToUsePasswordHasher',
        ]);

        $password = DigestAuthenticate::password('mariano', 'cake', 'localhost');
        $User = $this->getTableLocator()->get('Users');
        $User->updateAll(['password' => $password], []);
    }

    /**
     * test applying settings in the constructor
     *
     * @return void
     */
    public function testConstructor(): void
    {
        $object = new DigestAuthenticate($this->collection, [
            'userModel' => 'AuthUser',
            'fields' => ['username' => 'user', 'password' => 'pass'],
            'nonce' => 123456,
        ]);
        $this->assertSame('AuthUser', $object->getConfig('userModel'));
        $this->assertEquals(['username' => 'user', 'password' => 'pass'], $object->getConfig('fields'));
        $this->assertSame(123456, $object->getConfig('nonce'));
        $this->assertEquals(env('SERVER_NAME'), $object->getConfig('realm'));
    }

    /**
     * test the authenticate method
     *
     * @return void
     */
    public function testAuthenticateNoData(): void
    {
        $request = new ServerRequest(['url' => 'posts/index']);

        $this->assertFalse($this->auth->getUser($request));
    }

    /**
     * test the authenticate method
     *
     * @return void
     */
    public function testAuthenticateWrongUsername(): void
    {
        $request = new ServerRequest(['url' => 'posts/index']);

        $data = [
            'username' => 'incorrect_user',
            'realm' => 'localhost',
            'nonce' => $this->generateNonce(),
            'uri' => '/dir/index.html',
            'qop' => 'auth',
            'nc' => 0000001,
            'cnonce' => '0a4f113b',
        ];
        $data['response'] = $this->auth->generateResponseHash($data, '09faa9931501bf30f0d4253fa7763022', 'GET');
        $request = $request->withEnv('PHP_AUTH_DIGEST', $this->digestHeader($data));

        $this->assertFalse($this->auth->authenticate($request, new Response()));

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionCode(401);
        $this->auth->unauthenticated($request, new Response());
    }

    /**
     * test that challenge headers are sent when no credentials are found.
     *
     * @return void
     */
    public function testAuthenticateChallenge(): void
    {
        $request = new ServerRequest([
            'url' => 'posts/index',
            'environment' => ['REQUEST_METHOD' => 'GET'],
        ]);

        try {
            $this->auth->unauthenticated($request, new Response());
        } catch (UnauthorizedException $e) {
        }

        $this->assertNotEmpty($e);

        $header = $e->getHeaders();
        $this->assertMatchesRegularExpression(
            '/^Digest realm="localhost",qop="auth",nonce="[a-zA-Z0-9=]+",opaque="123abc"$/',
            $header['WWW-Authenticate']
        );
    }

    /**
     * test that challenge headers include stale when the nonce is stale
     *
     * @return void
     */
    public function testAuthenticateChallengeIncludesStaleAttributeOnStaleNonce(): void
    {
        $request = new ServerRequest([
            'url' => 'posts/index',
            'environment' => ['REQUEST_METHOD' => 'GET'],
        ]);
        $data = [
            'uri' => '/dir/index.html',
            'nonce' => $this->generateNonce(null, 5, strtotime('-10 minutes')),
            'nc' => 1,
            'cnonce' => '123',
            'qop' => 'auth',
        ];
        $data['response'] = $this->auth->generateResponseHash($data, '09faa9931501bf30f0d4253fa7763022', 'GET');
        $request = $request->withEnv('PHP_AUTH_DIGEST', $this->digestHeader($data));

        try {
            $this->auth->unauthenticated($request, new Response());
        } catch (UnauthorizedException $e) {
        }
        $this->assertNotEmpty($e);

        $header = $e->getHeaders()['WWW-Authenticate'];
        $this->assertStringContainsString('stale=true', $header);
    }

    /**
     * Test that authentication fails when a nonce is stale
     *
     * @return void
     */
    public function testAuthenticateFailsOnStaleNonce(): void
    {
        $request = new ServerRequest([
            'url' => 'posts/index',
            'environment' => ['REQUEST_METHOD' => 'GET'],
        ]);

        $data = [
            'uri' => '/dir/index.html',
            'nonce' => $this->generateNonce(null, 5, strtotime('-10 minutes')),
            'nc' => 1,
            'cnonce' => '123',
            'qop' => 'auth',
        ];
        $data['response'] = $this->auth->generateResponseHash($data, '09faa9931501bf30f0d4253fa7763022', 'GET');
        $request = $request->withEnv('PHP_AUTH_DIGEST', $this->digestHeader($data));
        $result = $this->auth->authenticate($request, new Response());
        $this->assertFalse($result, 'Stale nonce should fail');
    }

    /**
     * Test that nonces are required.
     *
     * @return void
     */
    public function testAuthenticateValidUsernamePasswordNoNonce(): void
    {
        $request = new ServerRequest([
            'url' => 'posts/index',
            'environment' => ['REQUEST_METHOD' => 'GET'],
        ]);

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
        $request = $request->withEnv('PHP_AUTH_DIGEST', $this->digestHeader($data));
        $result = $this->auth->authenticate($request, new Response());
        $this->assertFalse($result, 'Empty nonce should fail');
    }

    /**
     * test authenticate success
     *
     * @return void
     */
    public function testAuthenticateSuccess(): void
    {
        $request = new ServerRequest([
            'url' => 'posts/index',
            'environment' => ['REQUEST_METHOD' => 'GET'],
        ]);

        $data = [
            'uri' => '/dir/index.html',
            'nonce' => $this->generateNonce(),
            'nc' => 1,
            'cnonce' => '123',
            'qop' => 'auth',
        ];
        $data['response'] = $this->auth->generateResponseHash($data, '09faa9931501bf30f0d4253fa7763022', 'GET');
        $request = $request->withEnv('PHP_AUTH_DIGEST', $this->digestHeader($data));

        $result = $this->auth->authenticate($request, new Response());
        $expected = [
            'id' => 1,
            'username' => 'mariano',
            'created' => new Time('2007-03-17 01:16:23'),
            'updated' => new Time('2007-03-17 01:18:31'),
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test authenticate success even when digest 'password' is a hidden field.
     *
     * @return void
     */
    public function testAuthenticateSuccessHiddenPasswordField(): void
    {
        $User = $this->getTableLocator()->get('Users');
        $User->setEntityClass(ProtectedUser::class);

        $request = new ServerRequest([
            'url' => 'posts/index',
            'environment' => ['REQUEST_METHOD' => 'GET'],
        ]);

        $data = [
            'uri' => '/dir/index.html',
            'nonce' => $this->generateNonce(),
            'nc' => 1,
            'cnonce' => '123',
            'qop' => 'auth',
        ];
        $data['response'] = $this->auth->generateResponseHash($data, '09faa9931501bf30f0d4253fa7763022', 'GET');
        $request = $request->withEnv('PHP_AUTH_DIGEST', $this->digestHeader($data));

        $result = $this->auth->authenticate($request, new Response());
        $expected = [
            'id' => 1,
            'username' => 'mariano',
            'created' => new Time('2007-03-17 01:16:23'),
            'updated' => new Time('2007-03-17 01:18:31'),
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test authenticate success
     *
     * @return void
     */
    public function testAuthenticateSuccessSimulatedRequestMethod(): void
    {
        $request = new ServerRequest([
            'url' => 'posts/index',
            'post' => ['_method' => 'PUT'],
            'environment' => ['REQUEST_METHOD' => 'GET'],
        ]);

        $data = [
            'username' => 'mariano',
            'uri' => '/dir/index.html',
            'nonce' => $this->generateNonce(),
            'nc' => 1,
            'cnonce' => '123',
            'qop' => 'auth',
        ];
        $data['response'] = $this->auth->generateResponseHash($data, '09faa9931501bf30f0d4253fa7763022', 'GET');
        $request = $request->withEnv('PHP_AUTH_DIGEST', $this->digestHeader($data));

        $result = $this->auth->authenticate($request, new Response());
        $expected = [
            'id' => 1,
            'username' => 'mariano',
            'created' => new Time('2007-03-17 01:16:23'),
            'updated' => new Time('2007-03-17 01:18:31'),
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * testLoginHeaders method
     *
     * @return void
     */
    public function testLoginHeaders(): void
    {
        $request = new ServerRequest([
            'environment' => ['SERVER_NAME' => 'localhost'],
        ]);
        $this->auth = new DigestAuthenticate($this->collection, [
            'realm' => 'localhost',
        ]);
        $result = $this->auth->loginHeaders($request);

        $this->assertMatchesRegularExpression(
            '/^Digest realm="localhost",qop="auth",nonce="[a-zA-Z0-9=]+",opaque="[a-f0-9]+"$/',
            $result['WWW-Authenticate']
        );
    }

    /**
     * testParseDigestAuthData method
     *
     * @return void
     */
    public function testParseAuthData(): void
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
            'opaque' => '5ccc069c403ebaf9f0171e9517f40e41',
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
    public function testParseAuthDataFullUri(): void
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
    public function testParseAuthEmailAddress(): void
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
            'opaque' => '5ccc069c403ebaf9f0171e9517f40e41',
        ];
        $result = $this->auth->parseAuthData($digest);
        $this->assertSame($expected, $result);
    }

    /**
     * test password hashing
     *
     * @return void
     */
    public function testPassword(): void
    {
        $result = DigestAuthenticate::password('mark', 'password', 'localhost');
        $expected = md5('mark:localhost:password');
        $this->assertSame($expected, $result);
    }

    /**
     * Generate a nonce for testing.
     *
     * @param string $secret The secret to use.
     * @param int $expires Time to live
     * @param int $time Current time in microseconds
     * @return string
     */
    protected function generateNonce(?string $secret = null, ?int $expires = 300, ?int $time = null): string
    {
        $secret = $secret ?: Security::getSalt();
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
    protected function digestHeader(array $data): string
    {
        $data += [
            'username' => 'mariano',
            'realm' => 'localhost',
            'opaque' => '123abc',
        ];
        $digest = <<<DIGEST
Digest username="{$data['username']}",
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
