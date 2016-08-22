<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Network\Http\Auth;

use Cake\Network\Http\Auth\Oauth;
use Cake\Network\Http\Request;
use Cake\TestSuite\TestCase;

/**
 * Oauth test.
 */
class OauthTest extends TestCase
{

    private $privateKeyString = '-----BEGIN RSA PRIVATE KEY-----
MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBALRiMLAh9iimur8V
A7qVvdqxevEuUkW4K+2KdMXmnQbG9Aa7k7eBjK1S+0LYmVjPKlJGNXHDGuy5Fw/d
7rjVJ0BLB+ubPK8iA/Tw3hLQgXMRRGRXXCn8ikfuQfjUS1uZSatdLB81mydBETlJ
hI6GH4twrbDJCR2Bwy/XWXgqgGRzAgMBAAECgYBYWVtleUzavkbrPjy0T5FMou8H
X9u2AC2ry8vD/l7cqedtwMPp9k7TubgNFo+NGvKsl2ynyprOZR1xjQ7WgrgVB+mm
uScOM/5HVceFuGRDhYTCObE+y1kxRloNYXnx3ei1zbeYLPCHdhxRYW7T0qcynNmw
rn05/KO2RLjgQNalsQJBANeA3Q4Nugqy4QBUCEC09SqylT2K9FrrItqL2QKc9v0Z
zO2uwllCbg0dwpVuYPYXYvikNHHg+aCWF+VXsb9rpPsCQQDWR9TT4ORdzoj+Nccn
qkMsDmzt0EfNaAOwHOmVJ2RVBspPcxt5iN4HI7HNeG6U5YsFBb+/GZbgfBT3kpNG
WPTpAkBI+gFhjfJvRw38n3g/+UeAkwMI2TJQS4n8+hid0uus3/zOjDySH3XHCUno
cn1xOJAyZODBo47E+67R4jV1/gzbAkEAklJaspRPXP877NssM5nAZMU0/O/NGCZ+
3jPgDUno6WbJn5cqm8MqWhW1xGkImgRk+fkDBquiq4gPiT898jusgQJAd5Zrr6Q8
AO/0isr/3aa6O6NLQxISLKcPDk2NOccAfS/xOtfOz4sJYM3+Bs4Io9+dZGSDCA54
Lw03eHTNQghS0A==
-----END RSA PRIVATE KEY-----';

    private $privateKeyStringEnc = '-----BEGIN RSA PRIVATE KEY-----
Proc-Type: 4,ENCRYPTED
DEK-Info: DES-CBC,E65DB7AE7A05EF23

QCXAQ/Uj1+7uQp0MyDUPlKvW/28PhbT4GxflBYmU6SxKZ2CVFPk0M8RgB6gkJyVv
mwjo1Ch2Tlt7/VrNfLWGIh1XPhsC3gatv8Wv+g0keWWifaHlhXulgMGREJ7QeJg0
5THvdFuIs2qQnOzPCAwONjM6yMxPb2qxvwq0UKAL5V/CYVFWS6PYdR25f9ogXxBz
c3QjvvnhQ7ipNjpjVp/XKYMYnZPCYkNYvRX+BcsWlqYtclO3m+xPG+mPAFs9hnBI
wHI4yC2fl52giRc7XnSl7NNjun6RpHT/Cn7JDH6ql86pgMO0dw6PDzPf0KY9DCrR
ldQyzQ8WjN3FU55+En+8zmSnxUu7EbdqZwhVEF+UwfJ7IqJUnHll0aDTUA/qq0dk
DqtMKIXvRnDVZJqKxHyRvARf8Zp8USsq3cVdlA9PhtcKrs4CbTDL0lJ3eWj1bDS1
kIHXYo19lBqcS1oX+6TqvEs69oW/aG8UZIONN0Xh5TbxuJMedXD1dexV9oOA9lGR
cS6Ye0wC7fCdnA6jfAmHFJ5t2qk7FOzcFZwap7m+EWn11z+72GVqz3BDSe5qH2m2
XOHl59rVtJsZFtjyQEV34IFYyb2qBHHqUUdKwIwT1JOZIq+IdTJxaieIb1mnlmDw
DDf4Kwr0C9tti1R1IsPaAmjF7eH0PGbDGAB3fJSCXbHf7EXTz1AUdknd2MHXQ7wO
UBABkD2ETB+EotdHTly5FQt0jwbHfF2najBmezxtEjIygCnDb02Rtuei4HTansBu
shqoyFXJvizZzje7HaTQv/eJTuA6rUOzu/sAv/eBx2YAPkA8oa3qUw==
-----END RSA PRIVATE KEY-----';

    /**
     * @expectedException \Cake\Core\Exception\Exception
     */
    public function testExceptionUnknownSigningMethod()
    {
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
     *
     * @return void
     */
    public function testPlainTextSigning()
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
        $this->assertContains('OAuth', $result);
        $this->assertContains('oauth_version="1.0"', $result);
        $this->assertContains('oauth_token="a%20token%20value"', $result);
        $this->assertContains('oauth_consumer_key="a%20key"', $result);
        $this->assertContains('oauth_signature_method="PLAINTEXT"', $result);
        $this->assertContains('oauth_signature="it%20is%20secret%26also%20secret"', $result);
        $this->assertContains('oauth_timestamp=', $result);
        $this->assertContains('oauth_nonce=', $result);
    }

    /**
     * Test that baseString() normalizes the URL.
     *
     * @return void
     */
    public function testBaseStringNormalizeUrl()
    {
        $request = new Request();
        $request->url('HTTP://exAmple.com:80/parts/foo');

        $auth = new Oauth();
        $creds = [];
        $result = $auth->baseString($request, $creds);
        $this->assertContains('GET&', $result, 'method was missing.');
        $this->assertContains('http%3A%2F%2Fexample.com%2Fparts%2Ffoo', $result);
    }

    /**
     * Test that the query string is stripped from the normalized host.
     *
     * @return void
     */
    public function testBaseStringWithQueryString()
    {
        $request = new Request();
        $request->url('http://example.com/search?q=pogo&cat=2');

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
        $this->assertContains('GET&', $result, 'method was missing.');
        $this->assertContains(
            'http%3A%2F%2Fexample.com%2Fsearch&',
            $result
        );
        $this->assertContains(
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
     * http://wiki.oauth.net/w/page/12238556/TestCases
     *
     * @return void
     */
    public function testBaseStringWithPostData()
    {
        $request = new Request();
        $request->url('http://example.com/search?q=pogo')
            ->method(Request::METHOD_POST)
            ->body([
                'address' => 'post',
                'tags' => ['oauth', 'cake'],
                'zed' => 'last'
            ]);

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

        $this->assertContains('POST&', $result, 'method was missing.');
        $this->assertContains(
            'http%3A%2F%2Fexample.com%2Fsearch&',
            $result
        );
        $this->assertContains(
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
     * Test HMAC-SHA1 signing
     *
     * Hash result + parameters taken from
     * http://wiki.oauth.net/w/page/12238556/TestCases
     *
     * @return void
     */
    public function testHmacSigning()
    {
        $request = new Request();
        $request->url('http://photos.example.net/photos')
            ->body([
                'file' => 'vacation.jpg',
                'size' => 'original'
            ]);

        $options = [
            'consumerKey' => 'dpf43f3p2l4k3l03',
            'consumerSecret' => 'kd94hf93k423kf44',
            'tokenSecret' => 'pfkkdhi9sl3r4s00',
            'token' => 'nnch734d00sl2jdk',
            'nonce' => 'kllo9940pd9333jh',
            'timestamp' => '1191242096'
        ];
        $auth = new Oauth();
        $request = $auth->authentication($request, $options);

        $result = $request->getHeaderLine('Authorization');
        $expected = 'tR3+Ty81lMeYAr/Fid0kMTYa/WM=';
        $this->assertContains(
            'oauth_signature="' . $expected . '"',
            urldecode($result)
        );
    }

    /**
     * Test RSA-SHA1 signing with a private key string
     *
     * Hash result + parameters taken from
     * http://wiki.oauth.net/w/page/12238556/TestCases
     *
     * @return void
     */
    public function testRsaSigningString()
    {
        $request = new Request();
        $request->url('http://photos.example.net/photos')
            ->body([
                       'file' => 'vacaction.jpg',
                       'size' => 'original'
                   ]);
        $privateKey = $this->privateKeyString;

        $options = [
            'method' => 'RSA-SHA1',
            'consumerKey' => 'dpf43f3p2l4k3l03',
            'nonce' => '13917289812797014437',
            'timestamp' => '1196666512',
            'privateKey' => $privateKey
        ];
        $auth = new Oauth();
        $request = $auth->authentication($request, $options);

        $result = $request->header('Authorization');
        $expected = 'jvTp/wX1TYtByB1m+Pbyo0lnCOLIsyGCH7wke8AUs3BpnwZJtAuEJkvQL2/9n4s5wUmUl4aCI4BwpraNx4RtEXMe5qg5T1LVTGliMRpKasKsW//e+RinhejgCuzoH26dyF8iY2ZZ/5D1ilgeijhV/vBka5twt399mXwaYdCwFYE=';
        $this->assertContains(
            'oauth_signature="' . $expected . '"',
            urldecode($result)
        );
    }

    /**
     * Test RSA-SHA1 signing with a private key file
     *
     * Hash result + parameters taken from
     * http://wiki.oauth.net/w/page/12238556/TestCases
     *
     * @return void
     */
    public function testRsaSigningFile()
    {
        $request = new Request();
        $request->url('http://photos.example.net/photos')
            ->body([
                       'file' => 'vacaction.jpg',
                       'size' => 'original'
                   ]);
        $privateKey = fopen(TEST_APP . DS . 'config' . DS . 'key.pem', 'r');

        $options = [
            'method' => 'RSA-SHA1',
            'consumerKey' => 'dpf43f3p2l4k3l03',
            'nonce' => '13917289812797014437',
            'timestamp' => '1196666512',
            'privateKey' => $privateKey
        ];
        $auth = new Oauth();
        $request = $auth->authentication($request, $options);

        $result = $request->header('Authorization');
        $expected = 'jvTp/wX1TYtByB1m+Pbyo0lnCOLIsyGCH7wke8AUs3BpnwZJtAuEJkvQL2/9n4s5wUmUl4aCI4BwpraNx4RtEXMe5qg5T1LVTGliMRpKasKsW//e+RinhejgCuzoH26dyF8iY2ZZ/5D1ilgeijhV/vBka5twt399mXwaYdCwFYE=';
        $this->assertContains(
            'oauth_signature="' . $expected . '"',
            urldecode($result)
        );
    }

    /**
     * Test RSA-SHA1 signing with a private key file passphrase string
     *
     * Hash result + parameters taken from
     * http://wiki.oauth.net/w/page/12238556/TestCases
     *
     * @return void
     */
    public function testRsaSigningWithPassphraseString()
    {
        $request = new Request();
        $request->url('http://photos.example.net/photos')
            ->body([
                       'file' => 'vacaction.jpg',
                       'size' => 'original'
                   ]);
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

        $result = $request->header('Authorization');
        $expected = 'jvTp/wX1TYtByB1m+Pbyo0lnCOLIsyGCH7wke8AUs3BpnwZJtAuEJkvQL2/9n4s5wUmUl4aCI4BwpraNx4RtEXMe5qg5T1LVTGliMRpKasKsW//e+RinhejgCuzoH26dyF8iY2ZZ/5D1ilgeijhV/vBka5twt399mXwaYdCwFYE=';
        $this->assertContains(
            'oauth_signature="' . $expected . '"',
            urldecode($result)
        );
    }

    /**
     * Test RSA-SHA1 signing with a private key string and passphrase string
     *
     * Hash result + parameters taken from
     * http://wiki.oauth.net/w/page/12238556/TestCases
     *
     * @return void
     */
    public function testRsaSigningStringWithPassphraseString()
    {
        $request = new Request();
        $request->url('http://photos.example.net/photos')
            ->body([
                       'file' => 'vacaction.jpg',
                       'size' => 'original'
                   ]);
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

        $result = $request->header('Authorization');
        $expected = 'jvTp/wX1TYtByB1m+Pbyo0lnCOLIsyGCH7wke8AUs3BpnwZJtAuEJkvQL2/9n4s5wUmUl4aCI4BwpraNx4RtEXMe5qg5T1LVTGliMRpKasKsW//e+RinhejgCuzoH26dyF8iY2ZZ/5D1ilgeijhV/vBka5twt399mXwaYdCwFYE=';
        $this->assertContains(
            'oauth_signature="' . $expected . '"',
            urldecode($result)
        );
    }

    /**
     * Test RSA-SHA1 signing with passphrase file
     *
     * Hash result + parameters taken from
     * http://wiki.oauth.net/w/page/12238556/TestCases
     *
     * @return void
     */
    public function testRsaSigningWithPassphraseFile()
    {
        $this->skipIf(PHP_EOL != "\n", 'Just the line ending "\n" is supported. You can run the test again e.g. on a linux system.');

        $request = new Request();
        $request->url('http://photos.example.net/photos')
            ->body([
                       'file' => 'vacaction.jpg',
                       'size' => 'original'
                   ]);
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

        $result = $request->header('Authorization');
        $expected = 'jvTp/wX1TYtByB1m+Pbyo0lnCOLIsyGCH7wke8AUs3BpnwZJtAuEJkvQL2/9n4s5wUmUl4aCI4BwpraNx4RtEXMe5qg5T1LVTGliMRpKasKsW//e+RinhejgCuzoH26dyF8iY2ZZ/5D1ilgeijhV/vBka5twt399mXwaYdCwFYE=';
        $this->assertContains(
            'oauth_signature="' . $expected . '"',
            urldecode($result)
        );
        $expected = 0;
        $this->assertEquals($expected, ftell($passphrase));
    }

    /**
     * Test RSA-SHA1 signing with a private key string and passphrase file
     *
     * Hash result + parameters taken from
     * http://wiki.oauth.net/w/page/12238556/TestCases
     *
     * @return void
     */
    public function testRsaSigningStringWithPassphraseFile()
    {
        $this->skipIf(PHP_EOL != "\n", 'Just the line ending "\n" is supported. You can run the test again e.g. on a linux system.');

        $request = new Request();
        $request->url('http://photos.example.net/photos')
            ->body([
                       'file' => 'vacaction.jpg',
                       'size' => 'original'
                   ]);
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

        $result = $request->header('Authorization');
        $expected = 'jvTp/wX1TYtByB1m+Pbyo0lnCOLIsyGCH7wke8AUs3BpnwZJtAuEJkvQL2/9n4s5wUmUl4aCI4BwpraNx4RtEXMe5qg5T1LVTGliMRpKasKsW//e+RinhejgCuzoH26dyF8iY2ZZ/5D1ilgeijhV/vBka5twt399mXwaYdCwFYE=';
        $this->assertContains(
            'oauth_signature="' . $expected . '"',
            urldecode($result)
        );
        $expected = 0;
        $this->assertEquals($expected, ftell($passphrase));
    }
}
