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
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Cookie;

use Cake\Chronos\Chronos;
use Cake\Utility\Hash;
use DateTimeInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * Cookie object to build a cookie and turn it into a header value
 *
 * An HTTP cookie (also called web cookie, Internet cookie, browser cookie or
 * simply cookie) is a small piece of data sent from a website and stored on
 * the user's computer by the user's web browser while the user is browsing.
 *
 * Cookies were designed to be a reliable mechanism for websites to remember
 * stateful information (such as items added in the shopping cart in an online
 * store) or to record the user's browsing activity (including clicking
 * particular buttons, logging in, or recording which pages were visited in
 * the past). They can also be used to remember arbitrary pieces of information
 * that the user previously entered into form fields such as names, and preferences.
 *
 * Cookie objects are immutable, and you must re-assign variables when modifying
 * cookie objects:
 *
 * ```
 * $cookie = $cookie->withValue('0');
 * ```
 *
 * @link https://tools.ietf.org/html/rfc6265
 * @link https://en.wikipedia.org/wiki/HTTP_cookie
 */
class Cookie implements CookieInterface
{

    use CookieCryptTrait;

    /**
     * Cookie name
     *
     * @var string
     */
    protected $name = '';

    /**
     * Raw Cookie value.
     *
     * @var string|array
     */
    protected $value = '';

    /**
     * Whether or not a JSON value has been expanded into an array.
     *
     * @var bool
     */
    protected $isExpanded = false;

    /**
     * Expiration time
     *
     * @var int
     */
    protected $expiresAt = 0;

    /**
     * Path
     *
     * @var string
     */
    protected $path = '';

    /**
     * Domain
     *
     * @var string
     */
    protected $domain = '';

    /**
     * Secure
     *
     * @var bool
     */
    protected $secure = false;

    /**
     * HTTP only
     *
     * @var bool
     */
    protected $httpOnly = false;

    /**
     * Constructor
     *
     * The constructors args are similar to the native PHP `setcookie()` method.
     * The only difference is the 3rd argument which excepts null or an object
     * implementing \DateTimeInterface instead an integer.
     *
     * @link http://php.net/manual/en/function.setcookie.php
     * @param string $name Cookie name
     * @param string|array $value Value of the cookie
     * @param \DateTimeInterface|null $expiresAt Expiration time and date
     * @param string $path Path
     * @param string $domain Domain
     * @param bool $secure Is secure
     * @param bool $httpOnly HTTP Only
     */
    public function __construct(
        $name,
        $value = '',
        DateTimeInterface $expiresAt = null,
        $path = '',
        $domain = '',
        $secure = false,
        $httpOnly = false
    ) {
        $this->validateName($name);
        $this->name = $name;

        $this->_setValue($value);

        $this->validateString($domain);
        $this->domain = $domain;

        $this->validateBool($httpOnly);
        $this->httpOnly = $httpOnly;

        $this->validateString($path);
        $this->path = $path;

        $this->validateBool($secure);
        $this->secure = $secure;

        if ($expiresAt !== null) {
            $this->expiresAt = (int)$expiresAt->format('U');
        }
    }

    /**
     * Builds the expiration value part of the header string
     *
     * @return string
     */
    protected function _buildExpirationValue()
    {
        return sprintf(
            'expires=%s',
            gmdate('D, d-M-Y H:i:s T', $this->expiresAt)
        );
    }

    /**
     * Returns a header value as string
     *
     * @return string
     */
    public function toHeaderValue()
    {
        $value = $this->value;
        if ($this->isExpanded) {
            $value = $this->_flatten($this->value);
        }
        $headerValue[] = sprintf('%s=%s', $this->name, urlencode($value));

        if ($this->expiresAt !== 0) {
            $headerValue[] = $this->_buildExpirationValue();
        }
        if ($this->path !== '') {
            $headerValue[] = sprintf('path=%s', $this->path);
        }
        if ($this->domain !== '') {
            $headerValue[] = sprintf('domain=%s', $this->domain);
        }
        if ($this->secure) {
            $headerValue[] = 'secure';
        }
        if ($this->httpOnly) {
            $headerValue[] = 'httponly';
        }

        return implode('; ', $headerValue);
    }

    /**
     * Create a cookie with an updated name
     *
     * @param string $name Name of the cookie
     * @return static
     */
    public function withName($name)
    {
        $this->validateName($name);
        $new = clone $this;
        $new->name = $name;

        return $new;
    }

    /**
     * Get the id for a cookie
     *
     * Cookies are unique across name, domain, path tuples.
     *
     * @return string
     */
    public function getId()
    {
        $name = mb_strtolower($this->name);
        return "{$name};{$this->domain};{$this->path}";
    }

    /**
     * Gets the cookie name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Validates the cookie name
     *
     * @param string $name Name of the cookie
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function validateName($name)
    {
        if (preg_match("/[=,; \t\r\n\013\014]/", $name)) {
            throw new InvalidArgumentException(
                sprintf('The cookie name `%s` contains invalid characters.', $name)
            );
        }

        if (empty($name)) {
            throw new InvalidArgumentException('The cookie name cannot be empty.');
        }
    }

    /**
     * Gets the cookie value
     *
     * @return string|array
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Create a cookie with an updated value.
     *
     * @param string|array $value Value of the cookie to set
     * @return static
     */
    public function withValue($value)
    {
        $new = clone $this;
        $new->_setValue($value);

        return $new;
    }

    /**
     * Setter for the value attribute.
     *
     * @param mixed $value The value to store.
     * @return void
     */
    protected function _setValue($value)
    {
        $this->isExpanded = is_array($value);
        $this->value = $value;
    }

    /**
     * Create a new cookie with an updated path
     *
     * @param string $path Sets the path
     * @return static
     */
    public function withPath($path)
    {
        $this->validateString($path);
        $new = clone $this;
        $new->path = $path;

        return $new;
    }

    /**
     * Get the path attribute.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Create a cookie with an updated domain
     *
     * @param string $domain Domain to set
     * @return static
     */
    public function withDomain($domain)
    {
        $this->validateString($domain);
        $new = clone $this;
        $new->domain = $domain;

        return $new;
    }

    /**
     * Get the domain attribute.
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Validate that an argument is a string
     *
     * @param string $value The value to validate.
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function validateString($value)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException(sprintf(
                'The provided arg must be of type `string` but `%s` given',
                gettype($value)
            ));
        }
    }

    /**
     * Check if the cookie is secure
     *
     * @return bool
     */
    public function isSecure()
    {
        return $this->secure;
    }

    /**
     * Create a cookie with Secure updated
     *
     * @param bool $secure Secure attribute value
     * @return static
     */
    public function withSecure($secure)
    {
        $this->validateBool($secure);
        $new = clone $this;
        $new->secure = $secure;

        return $new;
    }

    /**
     * Create a cookie with HTTP Only updated
     *
     * @param bool $httpOnly HTTP Only
     * @return static
     */
    public function withHttpOnly($httpOnly)
    {
        $this->validateBool($httpOnly);
        $new = clone $this;
        $new->httpOnly = $httpOnly;

        return $new;
    }

    /**
     * Validate that an argument is a boolean
     *
     * @param bool $value The value to validate.
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function validateBool($value)
    {
        if (!is_bool($value)) {
            throw new InvalidArgumentException(sprintf(
                'The provided arg must be of type `bool` but `%s` given',
                gettype($value)
            ));
        }
    }

    /**
     * Check if the cookie is HTTP only
     *
     * @return bool
     */
    public function isHttpOnly()
    {
        return $this->httpOnly;
    }

    /**
     * Create a cookie with an updated expiration date
     *
     * @param \DateTimeInterface $dateTime Date time object
     * @return static
     */
    public function withExpiry(DateTimeInterface $dateTime)
    {
        $new = clone $this;
        $new->expiresAt = (int)$dateTime->format('U');

        return $new;
    }

    /**
     * Get the current expiry time
     *
     * @return int|null Timestamp of expiry or null
     */
    public function getExpiry()
    {
        return $this->expiresAt;
    }

    /**
     * Create a new cookie that will virtually never expire.
     *
     * @return static
     */
    public function withNeverExpire()
    {
        $new = clone $this;
        $new->expiresAt = Chronos::createFromDate(2038, 1, 1)->format('U');

        return $new;
    }

    /**
     * Create a new cookie that will expire/delete the cookie from the browser.
     *
     * This is done by setting the expiration time to 1 year ago
     *
     * @return static
     */
    public function withExpired()
    {
        $new = clone $this;
        $new->expiresAt = Chronos::parse('-1 year')->format('U');

        return $new;
    }

    /**
     * Checks if a value exists in the cookie data.
     *
     * This method will expand serialized complex data,
     * on first use.
     *
     * @param string $path Path to check
     * @return bool
     */
    public function check($path)
    {
        if ($this->isExpanded === false) {
            $this->value = $this->_expand($this->value);
        }

        return Hash::check($this->value, $path);
    }

    /**
     * Create a new cookie with updated data.
     *
     * @param string $path Path to write to
     * @param mixed $value Value to write
     * @return static
     */
    public function withAddedValue($path, $value)
    {
        $new = clone $this;
        if ($new->isExpanded === false) {
            $new->value = $new->_expand($new->value);
        }
        $new->value = Hash::insert($new->value, $path, $value);

        return $new;
    }

    /**
     * Create a new cookie without a specific path
     *
     * @param string $path Path to remove
     * @return static
     */
    public function withoutAddedValue($path)
    {
        $new = clone $this;
        if ($new->isExpanded === false) {
            $new->value = $new->_expand($new->value);
        }
        $new->value = Hash::remove($new->value, $path);

        return $new;
    }

    /**
     * Read data from the cookie
     *
     * This method will expand serialized complex data,
     * on first use.
     *
     * @param string $path Path to read the data from
     * @return mixed
     */
    public function read($path = null)
    {
        if ($this->isExpanded === false) {
            $this->value = $this->_expand($this->value);
        }

        if ($path === null) {
            return $this->value;
        }

        return Hash::get($this->value, $path);
    }

    /**
     * Encrypts the cookie value
     *
     * @param string|null $key Encryption key
     * @return $this
     */
    public function encrypt($key = null)
    {
        if ($key !== null) {
            $this->setEncryptionKey($key);
        }

        $this->value = $this->_encrypt($this->value);

        return $this;
    }

    /**
     * Decrypts the cookie value
     *
     * @param string|null $key Encryption key
     * @return $this
     */
    public function decrypt($key = null)
    {
        if ($key !== null) {
            $this->setEncryptionKey($key);
        }

        $this->value = $this->_decrypt($this->value);

        return $this;
    }

    /**
     * Checks if the cookie value was expanded
     *
     * @return bool
     */
    public function isExpanded()
    {
        return $this->isExpanded;
    }

    /**
     * Implode method to keep keys are multidimensional arrays
     *
     * @param array $array Map of key and values
     * @return string A json encoded string.
     */
    protected function _flatten(array $array)
    {
        return json_encode($array);
    }

    /**
     * Explode method to return array from string set in CookieComponent::_flatten()
     * Maintains reading backwards compatibility with 1.x CookieComponent::_flatten().
     *
     * @param string $string A string containing JSON encoded data, or a bare string.
     * @return string|array Map of key and values
     */
    protected function _expand($string)
    {
        $this->isExpanded = true;
        $first = substr($string, 0, 1);
        if ($first === '{' || $first === '[') {
            $ret = json_decode($string, true);

            return ($ret !== null) ? $ret : $string;
        }

        $array = [];
        foreach (explode(',', $string) as $pair) {
            $key = explode('|', $pair);
            if (!isset($key[1])) {
                return $key[0];
            }
            $array[$key[0]] = $key[1];
        }

        return $array;
    }
}
