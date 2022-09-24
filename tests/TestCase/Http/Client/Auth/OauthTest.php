<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\Client\Auth;

use Cake\Core\Exception\CakeException;
use Cake\Http\Client\Auth\Oauth;
use Cake\Http\Client\Request;
use Cake\TestSuite\TestCase;
use RuntimeException;

/**
 * Oauth test.
 */
class OauthTest extends TestCase
{
    /**
     * @var string
     */
    private $privateKeyString;

    /**
     * @var string
     */
    private $privateKeyStringEnc;

    /**
     * Setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->privateKeyString = file_get_contents(TEST_APP . DS . 'config' . DS . 'key.pem');
        $this->privateKeyStringEnc = file_get_contents(TEST_APP . DS . 'config' . DS . 'key_with_passphrase.pem');
    }

    public function testExceptionUnknownSigningMethod(): void
    {
        $this->expectException(CakeException::class);
        $auth = new Oauth();
        $creds = [
            'consumerSecret' => 'it is secret',
            'consumerKey' => 'a key',
            'token' => 'a token value',
            'tokenSecret' => 'also secret',
            'method' => 'silly goose',
        ];
        $request = new Request();
        $auth->authentication($request, $creds);
    }

    /**
     * Test plain-text signing.
     */
    public function testPlainTextSigning(): void
    {
        $auth = new Oauth();
        $creds = [
            'consumerSecret' => 'it is secret',
            'consumerKey' => 'a key',
            'token' => 'a token value',
            'tokenSecret' => 'also secret',
            'method' => 'plaintext',
        ];
        $request = new Request();
        $request = $auth->authentication($request, $creds);

        $result = $request->getHeaderLine('Authorization');
        $this->assertStringContainsString('OAuth', $result);
        $this->assertStringContainsString('oauth_version="1.0"', $result);
        $this->assertStringContainsString('oauth_token="a%20token%20value"', $result);
        $this->assertStringContainsString('oauth_consumer_key="a%20key"', $result);
        $this->assertStringContainsString('oauth_signature_method="PLAINTEXT"', $result);
        $this->assertStringContainsString('oauth_signature="it%20is%20secret%26also%20secret"', $result);
        $this->assertStringContainsString('oauth_timestamp=', $result);
        $this->assertStringContainsString('oauth_nonce=', $result);
    }

    /**
     * Test that baseString() normalizes the URL.
     */
    public function testBaseStringNormalizeUrl(): void
    {
        $request = new Request('HTTP://exAmple.com:80/parts/foo');

        $auth = new Oauth();
        $creds = [];
        $result = $auth->baseString($request, $creds);
        $this->assertStringContainsString('GET&', $result, 'method was missing.');
        $this->assertStringContainsString('http%3A%2F%2Fexample.com%2Fparts%2Ffoo', $result);
    }

    /**
     * Test that the query string is stripped from the normalized host.
     */
    public function testBaseStringWithQueryString(): void
    {
        $request = new Request('http://example.com/search?q=pogo&cat=2');

        $auth = new Oauth();
        $values = [
            'oauth_version' => '1.0',
            'oauth_nonce' => uniqid(),
            'oauth_timestamp' => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_token' => 'token',
            'oauth_consumer_key' => 'consumer-key',
        ];
        $result = $auth->baseString($request, $values);
        $this->assertStringContainsString('GET&', $result, 'method was missing.');
        $this->assertStringContainsString(
            'http%3A%2F%2Fexample.com%2Fsearch&',
            $result
        );
        $this->assertStringContainsString(
            'cat%3D2%26oauth_consumer_key%3Dconsumer-key' .
            '%26oauth_nonce%3D' . $values['oauth_nonce'] .
            '%26oauth_signature_method%3DHMAC-SHA1' .
            '%26oauth_timestamp%3D' . $values['oauth_timestamp'] .
            '%26oauth_token%3Dtoken' .
            '%26oauth_version%3D1.0' .
            '%26q%3Dpogo',
            $result
        );
    }

    /**
     * Ensure that post data is sorted and encoded.
     *
     * Keys with array values have to be serialized using
     * a more standard HTTP approach. PHP flavoured HTTP
     * is not part of the Oauth spec.
     *
     * See Normalize Request Parameters (section 9.1.1)
     */
    public function testBaseStringWithPostDataNestedArrays(): void
    {
        $request = new Request(
            'http://example.com/search?q=pogo',
            Request::METHOD_POST,
            [],
            [
                'search' => [
                    'filters' => [
                        'field' => 'date',
                        'value' => 'one two',
                    ],
                ],
            ]
        );

        $auth = new Oauth();
        $values = [
            'oauth_version' => '1.0',
            'oauth_nonce' => uniqid(),
            'oauth_timestamp' => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_token' => 'token',
            'oauth_consumer_key' => 'consumer-key',
        ];
        $result = $auth->baseString($request, $values);

        $this->assertStringContainsString('POST&', $result, 'method was missing.');
        $this->assertStringContainsString(
            'http%3A%2F%2Fexample.com%2Fsearch&',
            $result
        );
        $this->assertStringContainsString(
            '&oauth_consumer_key%3Dconsumer-key' .
            '%26oauth_nonce%3D' . $values['oauth_nonce'] .
            '%26oauth_signature_method%3DHMAC-SHA1' .
            '%26oauth_timestamp%3D' . $values['oauth_timestamp'] .
            '%26oauth_token%3Dtoken' .
            '%26oauth_version%3D1.0' .
            '%26q%3Dpogo' .
            '%26search%5Bfilters%5D%5Bfield%5D%3Ddate' .
            '%26search%5Bfilters%5D%5Bvalue%5D%3Done%20two',
            $result
        );
    }

    /**
     * Ensure that post data is sorted and encoded.
     *
     * Keys with array values have to be serialized using
     * a more standard HTTP approach. PHP flavoured HTTP
     * is not part of the Oauth spec.
     *
     * See Normalize Request Parameters (section 9.1.1)
     * https://wiki.oauth.net/w/page/12238556/TestCases
     */
    public function testBaseStringWithPostData(): void
    {
        $request = new Request(
            'http://example.com/search?q=pogo',
            Request::METHOD_POST,
            [],
            [
                'address' => 'post',
                'zed' => 'last',
                'tags' => ['oauth', 'cake'],
            ]
        );

        $auth = new Oauth();
        $values = [
            'oauth_version' => '1.0',
            'oauth_nonce' => uniqid(),
            'oauth_timestamp' => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_token' => 'token',
            'oauth_consumer_key' => 'consumer-key',
        ];
        $result = $auth->baseString($request, $values);

        $this->assertStringContainsString('POST&', $result, 'method was missing.');
        $this->assertStringContainsString(
            'http%3A%2F%2Fexample.com%2Fsearch&',
            $result
        );
        $this->assertStringContainsString(
            '&address%3Dpost' .
            '%26oauth_consumer_key%3Dconsumer-key' .
            '%26oauth_nonce%3D' . $values['oauth_nonce'] .
            '%26oauth_signature_method%3DHMAC-SHA1' .
            '%26oauth_timestamp%3D' . $values['oauth_timestamp'] .
            '%26oauth_token%3Dtoken' .
            '%26oauth_version%3D1.0' .
            '%26q%3Dpogo' .
            '%26tags%3Dcake' .
            '%26tags%3Doauth' .
            '%26zed%3Dlast',
            $result
        );
    }

    /**
     * Ensure that non-urlencoded post data is not included.
     *
     * Keys with array values have to be serialized using
     * a more standard HTTP approach. PHP flavoured HTTP
     * is not part of the Oauth spec.
     *
     * See Normalize Request Parameters (section 9.1.1)
     */
    public function testBaseStringWithXmlPostData(): void
    {
        $request = new Request(
            'http://example.com/search?q=pogo',
            Request::METHOD_POST,
            [
                'Content-Type' => 'application/xml',
            ],
            '<xml>stuff</xml>'
        );

        $auth = new Oauth();
        $values = [
            'oauth_version' => '1.0',
            'oauth_nonce' => uniqid(),
            'oauth_timestamp' => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_token' => 'token',
            'oauth_consumer_key' => 'consumer-key',
        ];
        $result = $auth->baseString($request, $values);

        $this->assertStringContainsString('POST&', $result, 'method was missing.');
        $this->assertStringContainsString(
            'http%3A%2F%2Fexample.com%2Fsearch&',
            $result
        );
        $this->assertStringContainsString(
            'oauth_consumer_key%3Dconsumer-key' .
            '%26oauth_nonce%3D' . $values['oauth_nonce'] .
            '%26oauth_signature_method%3DHMAC-SHA1' .
            '%26oauth_timestamp%3D' . $values['oauth_timestamp'] .
            '%26oauth_token%3Dtoken' .
            '%26oauth_version%3D1.0' .
            '%26q%3Dpogo',
            $result
        );
    }

    /**
     * Test HMAC-SHA1 signing
     *
     * Hash result + parameters taken from
     * https://wiki.oauth.net/w/page/12238556/TestCases
     */
    public function testHmacSigning(): void
    {
        $request = new Request(
            'http://photos.example.net/photos',
            'GET',
            [],
            ['file' => 'vacation.jpg', 'size' => 'original']
        );

        $options = [
            'consumerKey' => 'dpf43f3p2l4k3l03',
            'consumerSecret' => 'kd94hf93k423kf44',
            'tokenSecret' => 'pfkkdhi9sl3r4s00',
            'token' => 'nnch734d00sl2jdk',
            'nonce' => 'kllo9940pd9333jh',
            'timestamp' => '1191242096',
        ];
        $auth = new Oauth();
        $request = $auth->authentication($request, $options);

        $result = $request->getHeaderLine('Authorization');
        $this->assertSignatureFormat($result);
    }

    /**
     * Test HMAC-SHA1 signing with a base64 consumer key
     */
    public function testHmacBase64Signing(): void
    {
        $request = new Request(
            'http://photos.example.net/photos',
            'GET'
        );

        $options = [
            'consumerKey' => 'ZHBmNDNmM3AybDRrM2wwMw==',
            'consumerSecret' => 'kd94hf93k423kf44',
            'tokenSecret' => 'pfkkdhi9sl3r4s00',
            'token' => 'nnch734d00sl2jdk',
            'nonce' => 'kllo9940pd9333jh',
            'timestamp' => '1191242096',
        ];
        $auth = new Oauth();
        $request = $auth->authentication($request, $options);

        $result = $request->getHeaderLine('Authorization');
        $this->assertSignatureFormat($result);
    }

    /**
     * Test RSA-SHA1 signing with a private key string
     *
     * Hash result + parameters taken from
     * https://wiki.oauth.net/w/page/12238556/TestCases
     */
    public function testRsaSigningString(): void
    {
        $request = new Request(
            'http://photos.example.net/photos',
            'GET',
            [],
            ['file' => 'vacaction.jpg', 'size' => 'original']
        );
        $privateKey = $this->privateKeyString;

        $options = [
            'method' => 'RSA-SHA1',
            'consumerKey' => 'dpf43f3p2l4k3l03',
            'nonce' => '13917289812797014437',
            'timestamp' => '1196666512',
            'privateKey' => $privateKey,
        ];
        $auth = new Oauth();
        try {
            $request = $auth->authentication($request, $options);
            $result = $request->getHeaderLine('Authorization');
            $this->assertSignatureFormat($result);
        } catch (RuntimeException $e) {
            // Handle 22.04 + OpenSSL bug. This should be safe to remove in the future.
            if (strpos($e->getMessage(), 'unexpected eof while reading') !== false) {
                $this->markTestSkipped('Skipping because of OpenSSL bug.');
            }
            throw $e;
        }
    }

    public function testRsaSigningInvalidKey(): void
    {
        $request = new Request(
            'http://photos.example.net/photos',
            'GET',
            [],
            ['file' => 'vacaction.jpg', 'size' => 'original']
        );

        $options = [
            'method' => 'RSA-SHA1',
            'consumerKey' => 'dpf43f3p2l4k3l03',
            'nonce' => '13917289812797014437',
            'timestamp' => '1196666512',
            'privateKey' => 'not a private key',
        ];
        $auth = new Oauth();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('openssl error');
        $auth->authentication($request, $options);
    }

    /**
     * Test RSA-SHA1 signing with a private key file
     *
     * Hash result + parameters taken from
     * https://wiki.oauth.net/w/page/12238556/TestCases
     */
    public function testRsaSigningFile(): void
    {
        $request = new Request(
            'http://photos.example.net/photos',
            'GET',
            [],
            ['file' => 'vacaction.jpg', 'size' => 'original']
        );
        $privateKey = fopen(TEST_APP . DS . 'config' . DS . 'key.pem', 'r');

        $options = [
            'method' => 'RSA-SHA1',
            'consumerKey' => 'dpf43f3p2l4k3l03',
            'nonce' => '13917289812797014437',
            'timestamp' => '1196666512',
            'privateKey' => $privateKey,
        ];
        $auth = new Oauth();
        $request = $auth->authentication($request, $options);

        $result = $request->getHeaderLine('Authorization');
        $this->assertSignatureFormat($result);
    }

    /**
     * Test RSA-SHA1 signing with a private key file passphrase string
     *
     * Hash result + parameters taken from
     * https://wiki.oauth.net/w/page/12238556/TestCases
     */
    public function testRsaSigningWithPassphraseString(): void
    {
        $request = new Request(
            'http://photos.example.net/photos',
            'GET',
            [],
            ['file' => 'vacaction.jpg', 'size' => 'original']
        );
        $privateKey = fopen(TEST_APP . DS . 'config' . DS . 'key_with_passphrase.pem', 'r');
        $passphrase = 'fancy-cakephp-passphrase';

        $options = [
            'method' => 'RSA-SHA1',
            'consumerKey' => 'dpf43f3p2l4k3l03',
            'nonce' => '13917289812797014437',
            'timestamp' => '1196666512',
            'privateKey' => $privateKey,
            'privateKeyPassphrase' => $passphrase,
        ];
        $auth = new Oauth();
        $request = $auth->authentication($request, $options);

        $result = $request->getHeaderLine('Authorization');
        $this->assertSignatureFormat($result);
    }

    /**
     * Test RSA-SHA1 signing with a private key string and passphrase string
     *
     * Hash result + parameters taken from
     * https://wiki.oauth.net/w/page/12238556/TestCases
     */
    public function testRsaSigningStringWithPassphraseString(): void
    {
        $request = new Request(
            'http://photos.example.net/photos',
            'GET',
            [],
            ['file' => 'vacaction.jpg', 'size' => 'original']
        );
        $privateKey = $this->privateKeyStringEnc;
        $passphrase = 'fancy-cakephp-passphrase';

        $options = [
            'method' => 'RSA-SHA1',
            'consumerKey' => 'dpf43f3p2l4k3l03',
            'nonce' => '13917289812797014437',
            'timestamp' => '1196666512',
            'privateKey' => $privateKey,
            'privateKeyPassphrase' => $passphrase,
        ];
        $auth = new Oauth();
        $request = $auth->authentication($request, $options);

        $result = $request->getHeaderLine('Authorization');
        $this->assertSignatureFormat($result);
    }

    /**
     * Test RSA-SHA1 signing with passphrase file
     *
     * Hash result + parameters taken from
     * https://wiki.oauth.net/w/page/12238556/TestCases
     */
    public function testRsaSigningWithPassphraseFile(): void
    {
        $this->skipIf(PHP_EOL !== "\n", 'Just the line ending "\n" is supported. You can run the test again e.g. on a linux system.');

        $request = new Request(
            'http://photos.example.net/photos',
            'GET',
            [],
            ['file' => 'vacaction.jpg', 'size' => 'original']
        );
        $privateKey = fopen(TEST_APP . DS . 'config' . DS . 'key_with_passphrase.pem', 'r');
        $passphrase = fopen(TEST_APP . DS . 'config' . DS . 'key_passphrase_lf', 'r');

        $options = [
            'method' => 'RSA-SHA1',
            'consumerKey' => 'dpf43f3p2l4k3l03',
            'nonce' => '13917289812797014437',
            'timestamp' => '1196666512',
            'privateKey' => $privateKey,
            'privateKeyPassphrase' => $passphrase,
        ];
        $auth = new Oauth();
        $request = $auth->authentication($request, $options);

        $result = $request->getHeaderLine('Authorization');
        $this->assertSignatureFormat($result);
        $expected = 0;
        $this->assertSame($expected, ftell($passphrase));
    }

    /**
     * Test RSA-SHA1 signing with a private key string and passphrase file
     *
     * Hash result + parameters taken from
     * https://wiki.oauth.net/w/page/12238556/TestCases
     */
    public function testRsaSigningStringWithPassphraseFile(): void
    {
        $this->skipIf(PHP_EOL !== "\n", 'Just the line ending "\n" is supported. You can run the test again e.g. on a linux system.');

        $request = new Request(
            'http://photos.example.net/photos',
            'GET',
            [],
            ['file' => 'vacaction.jpg', 'size' => 'original']
        );
        $privateKey = $this->privateKeyStringEnc;
        $passphrase = fopen(TEST_APP . DS . 'config' . DS . 'key_passphrase_lf', 'r');

        $options = [
            'method' => 'RSA-SHA1',
            'consumerKey' => 'dpf43f3p2l4k3l03',
            'nonce' => '13917289812797014437',
            'timestamp' => '1196666512',
            'privateKey' => $privateKey,
            'privateKeyPassphrase' => $passphrase,
        ];
        $auth = new Oauth();
        $request = $auth->authentication($request, $options);

        $result = $request->getHeaderLine('Authorization');
        $this->assertSignatureFormat($result);
        $expected = 0;
        $this->assertSame($expected, ftell($passphrase));
    }

    protected function assertSignatureFormat($result)
    {
        $this->assertMatchesRegularExpression(
            '/oauth_signature="[a-zA-Z0-9\/=+]+"/',
            urldecode($result)
        );
    }
}
