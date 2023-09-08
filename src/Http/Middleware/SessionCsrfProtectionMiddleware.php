<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.2.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Middleware;

use ArrayAccess;
use Cake\Core\Exception\CakeException;
use Cake\Http\Exception\InvalidCsrfTokenException;
use Cake\Http\ServerRequest;
use Cake\Http\Session;
use Cake\Utility\Hash;
use Cake\Utility\Security;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function Cake\I18n\__d;

/**
 * Provides CSRF protection via session based tokens.
 *
 * This middleware adds a CSRF token to the session. Each request must
 * contain a token in request data, or the X-CSRF-Token header on each PATCH, POST,
 * PUT, or DELETE request. This follows a 'synchronizer token' pattern.
 *
 * If the request data is missing or does not match the session data,
 * an InvalidCsrfTokenException will be raised.
 *
 * This middleware integrates with the FormHelper automatically and when
 * used together your forms will have CSRF tokens automatically added
 * when `$this->Form->create(...)` is used in a view.
 *
 * If you use this middleware *do not* also use CsrfProtectionMiddleware.
 *
 * @see https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html#synchronizer-token-pattern
 */
class SessionCsrfProtectionMiddleware implements MiddlewareInterface
{
    /**
     * Config for the CSRF handling.
     *
     *  - `key` The session key to use. Defaults to `csrfToken`
     *  - `field` The form field to check. Changing this will also require configuring
     *    FormHelper.
     *
     * @var array<string, mixed>
     */
    protected array $_config = [
        'key' => 'csrfToken',
        'field' => '_csrfToken',
    ];

    /**
     * Callback for deciding whether to skip the token check for particular request.
     *
     * CSRF protection token check will be skipped if the callback returns `true`.
     *
     * @var callable|null
     */
    protected $skipCheckCallback;

    /**
     * @var int
     */
    public const TOKEN_VALUE_LENGTH = 32;

    /**
     * Constructor
     *
     * @param array<string, mixed> $config Config options. See $_config for valid keys.
     */
    public function __construct(array $config = [])
    {
        $this->_config = $config + $this->_config;
    }

    /**
     * Checks and sets the CSRF token depending on the HTTP verb.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $request->getMethod();
        $hasData = in_array($method, ['PUT', 'POST', 'DELETE', 'PATCH'], true)
            || $request->getParsedBody();

        if (
            $hasData
            && $this->skipCheckCallback !== null
            && call_user_func($this->skipCheckCallback, $request) === true
        ) {
            $request = $this->unsetTokenField($request);

            return $handler->handle($request);
        }

        $session = $request->getAttribute('session');
        if (!$session || !($session instanceof Session)) {
            throw new CakeException('You must have a `session` attribute to use session based CSRF tokens');
        }

        $token = $session->read($this->_config['key']);
        if ($token === null) {
            $token = $this->createToken();
            $session->write($this->_config['key'], $token);
        }
        $request = $request->withAttribute('csrfToken', $this->saltToken($token));

        if ($method === 'GET') {
            return $handler->handle($request);
        }

        if ($hasData) {
            $this->validateToken($request, $session);
            $request = $this->unsetTokenField($request);
        }

        return $handler->handle($request);
    }

    /**
     * Set callback for allowing to skip token check for particular request.
     *
     * The callback will receive request instance as argument and must return
     * `true` if you want to skip token check for the current request.
     *
     * @param callable $callback A callable.
     * @return $this
     */
    public function skipCheckCallback(callable $callback)
    {
        $this->skipCheckCallback = $callback;

        return $this;
    }

    /**
     * Apply entropy to a CSRF token
     *
     * To avoid BREACH apply a random salt value to a token
     * When the token is compared to the session the token needs
     * to be unsalted.
     *
     * @param string $token The token to salt.
     * @return string The salted token with the salt appended.
     */
    public function saltToken(string $token): string
    {
        $decoded = base64_decode($token);
        $length = strlen($decoded);
        $salt = Security::randomBytes($length);
        $salted = '';
        for ($i = 0; $i < $length; $i++) {
            // XOR the token and salt together so that we can reverse it later.
            $salted .= chr(ord($decoded[$i]) ^ ord($salt[$i]));
        }

        return base64_encode($salted . $salt);
    }

    /**
     * Remove the salt from a CSRF token.
     *
     * If the token is not TOKEN_VALUE_LENGTH * 2 it is an old
     * unsalted value that is supported for backwards compatibility.
     *
     * @param string $token The token that could be salty.
     * @return string An unsalted token.
     */
    protected function unsaltToken(string $token): string
    {
        $decoded = base64_decode($token, true);
        if ($decoded === false || strlen($decoded) !== static::TOKEN_VALUE_LENGTH * 2) {
            return $token;
        }
        $salted = substr($decoded, 0, static::TOKEN_VALUE_LENGTH);
        $salt = substr($decoded, static::TOKEN_VALUE_LENGTH);

        $unsalted = '';
        for ($i = 0; $i < static::TOKEN_VALUE_LENGTH; $i++) {
            // Reverse the XOR to desalt.
            $unsalted .= chr(ord($salted[$i]) ^ ord($salt[$i]));
        }

        return base64_encode($unsalted);
    }

    /**
     * Remove CSRF protection token from request data.
     *
     * This ensures that the token does not cause failures during
     * form tampering protection.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request object.
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    protected function unsetTokenField(ServerRequestInterface $request): ServerRequestInterface
    {
        $body = $request->getParsedBody();
        if (is_array($body)) {
            unset($body[$this->_config['field']]);
            $request = $request->withParsedBody($body);
        }

        return $request;
    }

    /**
     * Create a new token to be used for CSRF protection
     *
     * This token is a simple unique random value as the compare
     * value is stored in the session where it cannot be tampered with.
     *
     * @return string
     */
    public function createToken(): string
    {
        return base64_encode(Security::randomBytes(static::TOKEN_VALUE_LENGTH));
    }

    /**
     * Validate the request data against the cookie token.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request to validate against.
     * @param \Cake\Http\Session $session The session instance.
     * @return void
     * @throws \Cake\Http\Exception\InvalidCsrfTokenException When the CSRF token is invalid or missing.
     */
    protected function validateToken(ServerRequestInterface $request, Session $session): void
    {
        $token = $session->read($this->_config['key']);
        if (!$token || !is_string($token)) {
            throw new InvalidCsrfTokenException(__d('cake', 'Missing or incorrect CSRF session key'));
        }

        $body = $request->getParsedBody();
        if (is_array($body) || $body instanceof ArrayAccess) {
            $post = (string)Hash::get($body, $this->_config['field']);
            $post = $this->unsaltToken($post);
            if (hash_equals($post, $token)) {
                return;
            }
        }

        $header = $request->getHeaderLine('X-CSRF-Token');
        $header = $this->unsaltToken($header);
        if (hash_equals($header, $token)) {
            return;
        }

        throw new InvalidCsrfTokenException(__d(
            'cake',
            'CSRF token from either the request body or request headers did not match or is missing.'
        ));
    }

    /**
     * Replace the token in the provided request.
     *
     * Replace the token in the session and request attribute. Replacing
     * tokens is a good idea during privilege escalation or privilege reduction.
     *
     * @param \Cake\Http\ServerRequest $request The request to update
     * @param string $key The session key/attribute to set.
     * @return \Cake\Http\ServerRequest An updated request.
     */
    public static function replaceToken(ServerRequest $request, string $key = 'csrfToken'): ServerRequest
    {
        $middleware = new SessionCsrfProtectionMiddleware(['key' => $key]);

        $token = $middleware->createToken();
        $request->getSession()->write($key, $token);

        return $request->withAttribute($key, $middleware->saltToken($token));
    }
}
