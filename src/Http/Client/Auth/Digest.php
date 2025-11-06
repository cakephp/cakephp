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
namespace Cake\Http\Client\Auth;

use Cake\Http\Client;
use Cake\Http\Client\Request;
use Cake\Http\HeaderUtility;
use Cake\Utility\Hash;
use InvalidArgumentException;

/**
 * Digest authentication adapter for Cake\Http\Client
 *
 * Generally not directly constructed, but instead used by {@link \Cake\Http\Client}
 * when $options['auth']['type'] is 'digest'
 */
class Digest
{
    /**
     * Algorithms
     */
    public const ALGO_MD5 = 'MD5';
    public const ALGO_SHA_256 = 'SHA-256';
    public const ALGO_SHA_512_256 = 'SHA-512-256';
    public const ALGO_MD5_SESS = 'MD5-sess';
    public const ALGO_SHA_256_SESS = 'SHA-256-sess';
    public const ALGO_SHA_512_256_SESS = 'SHA-512-256-sess';

    /**
     * QOP
     */
    public const QOP_AUTH = 'auth';
    public const QOP_AUTH_INT = 'auth-int';

    /**
     * Algorithms <-> Hash type
     */
    public const HASH_ALGORITHMS = [
        self::ALGO_MD5 => 'md5',
        self::ALGO_SHA_256 => 'sha256',
        self::ALGO_SHA_512_256 => 'sha512/256',
        self::ALGO_MD5_SESS => 'md5',
        self::ALGO_SHA_256_SESS => 'sha256',
        self::ALGO_SHA_512_256_SESS => 'sha512/256',
    ];
    /**
     * Instance of Cake\Http\Client
     *
     * @var \Cake\Http\Client
     */
    protected Client $_client;

    /**
     * Algorithm
     *
     * @var string
     */
    protected string $algorithm;

    /**
     * Hash type
     *
     * @var string
     */
    protected string $hashType;

    /**
     * Is Sess algorithm
     *
     * @var bool
     */
    protected bool $isSessAlgorithm = false;

    /**
     * Constructor
     *
     * Deprecated: $options list is unused and will be removed in 6.0.
     *
     * @param \Cake\Http\Client $client Http client object.
     * @param array|null $options Options list.
     */
    public function __construct(Client $client, ?array $options = null)
    {
        $this->_client = $client;
    }

    /**
     * Set algorithm based on credentials
     *
     * @param array $credentials authentication params
     * @return void
     */
    protected function setAlgorithm(array $credentials): void
    {
        $algorithm = $credentials['algorithm'] ?? self::ALGO_MD5;
        if (!isset(self::HASH_ALGORITHMS[$algorithm])) {
            throw new InvalidArgumentException('Invalid Algorithm. Valid ones are: ' .
                implode(',', array_keys(self::HASH_ALGORITHMS)));
        }
        $this->algorithm = $algorithm;
        $this->isSessAlgorithm = str_contains($this->algorithm, '-sess');
        $this->hashType = Hash::get(self::HASH_ALGORITHMS, $this->algorithm);
    }

    /**
     * Add Authorization header to the request.
     *
     * @param \Cake\Http\Client\Request $request The request object.
     * @param array<string, mixed> $credentials Authentication credentials.
     * @return \Cake\Http\Client\Request The updated request.
     * @see https://www.ietf.org/rfc/rfc2617.txt
     */
    public function authentication(Request $request, array $credentials): Request
    {
        if (!isset($credentials['username'], $credentials['password'])) {
            return $request;
        }
        if (!isset($credentials['realm'])) {
            $credentials = $this->_getServerInfo($request, $credentials);
        }
        if (!isset($credentials['realm'])) {
            return $request;
        }

        $this->setAlgorithm($credentials);
        $value = $this->_generateHeader($request, $credentials);

        return $request->withHeader('Authorization', $value);
    }

    /**
     * Retrieve information about the authentication
     *
     * Will get the realm and other tokens by performing
     * another request without authentication to get authentication
     * challenge.
     *
     * @param \Cake\Http\Client\Request $request The request object.
     * @param array $credentials Authentication credentials.
     * @return array modified credentials.
     */
    protected function _getServerInfo(Request $request, array $credentials): array
    {
        $response = $this->_client->get(
            (string)$request->getUri(),
            [],
            ['auth' => ['type' => null]],
        );

        $header = $response->getHeader('WWW-Authenticate');
        if (!$header) {
            return [];
        }
        $matches = HeaderUtility::parseWwwAuthenticate($header[0]);
        $credentials = array_merge($credentials, $matches);

        if (($this->isSessAlgorithm || !empty($credentials['qop'])) && empty($credentials['nc'])) {
            $credentials['nc'] = 1;
        }

        return $credentials;
    }

    /**
     * @return string
     */
    protected function generateCnonce(): string
    {
        return uniqid();
    }

    /**
     * Generate the header Authorization
     *
     * @param \Cake\Http\Client\Request $request The request object.
     * @param array<string, mixed> $credentials Authentication credentials.
     * @return string
     */
    protected function _generateHeader(Request $request, array $credentials): string
    {
        $path = $request->getRequestTarget();

        if ($this->isSessAlgorithm) {
            $credentials['cnonce'] = $this->generateCnonce();
            $a1 = hash($this->hashType, $credentials['username'] . ':' .
                    $credentials['realm'] . ':' . $credentials['password']) . ':' .
                $credentials['nonce'] . ':' . $credentials['cnonce'];
        } else {
            $a1 = $credentials['username'] . ':' . $credentials['realm'] . ':' . $credentials['password'];
        }
        $ha1 = hash($this->hashType, $a1);
        $a2 = $request->getMethod() . ':' . $path;
        $nc = sprintf('%08x', $credentials['nc'] ?? 1);

        if (empty($credentials['qop'])) {
            $ha2 = hash($this->hashType, $a2);
            $response = hash($this->hashType, $ha1 . ':' . $credentials['nonce'] . ':' . $ha2);
        } else {
            if (!in_array($credentials['qop'], [self::QOP_AUTH, self::QOP_AUTH_INT])) {
                throw new InvalidArgumentException('Invalid QOP parameter. Valid types are: ' .
                    implode(',', [self::QOP_AUTH, self::QOP_AUTH_INT]));
            }
            if ($credentials['qop'] === self::QOP_AUTH_INT) {
                $a2 = $request->getMethod() . ':' . $path . ':' . hash($this->hashType, (string)$request->getBody());
            }
            if (empty($credentials['cnonce'])) {
                $credentials['cnonce'] = $this->generateCnonce();
            }
            $ha2 = hash($this->hashType, $a2);
            $response = hash(
                $this->hashType,
                $ha1 . ':' . $credentials['nonce'] . ':' . $nc . ':' .
                $credentials['cnonce'] . ':' . $credentials['qop'] . ':' . $ha2,
            );
        }

        $authHeader = 'Digest ';
        $authHeader .= 'username="' . str_replace(['\\', '"'], ['\\\\', '\\"'], $credentials['username']) . '", ';
        $authHeader .= 'realm="' . $credentials['realm'] . '", ';
        $authHeader .= 'nonce="' . $credentials['nonce'] . '", ';
        $authHeader .= 'uri="' . $path . '", ';
        $authHeader .= 'algorithm="' . $this->algorithm . '"';

        if (!empty($credentials['qop'])) {
            $authHeader .= ', qop=' . $credentials['qop'];
        }
        if ($this->isSessAlgorithm || !empty($credentials['qop'])) {
            $authHeader .= ', nc=' . $nc . ', cnonce="' . $credentials['cnonce'] . '"';
        }
        $authHeader .= ', response="' . $response . '"';

        if (!empty($credentials['opaque'])) {
            $authHeader .= ', opaque="' . $credentials['opaque'] . '"';
        }

        return $authHeader;
    }
}
