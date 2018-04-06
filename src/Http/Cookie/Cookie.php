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
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

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
 * @see Cake\Http\Cookie\CookieCollection for working with collections of cookies.
 * @see Cake\Http\Response::getCookieCollection() for working with response cookies.
 */
class Cookie implements CookieInterface
{

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
     * @var \DateTime|\DateTimeImmutable|null
     */
    protected $expiresAt;

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
     * The only difference is the 3rd argument which excepts null or an
     * DateTime or DateTimeImmutable object instead an integer.
     *
     * @link http://php.net/manual/en/function.setcookie.php
     * @param string $name Cookie name
     * @param string|array $value Value of the cookie
     * @param \DateTime|\DateTimeImmutable|null $expiresAt Expiration time and date
     * @param string $path Path
     * @param string $domain Domain
     * @param bool $secure Is secure
     * @param bool $httpOnly HTTP Only
     */
    public function __construct(
        $name,
        $value = '',
        $expiresAt = null,
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
        if ($expiresAt) {
            $expiresAt = $expiresAt->setTimezone(new DateTimeZone('GMT'));
        }
        $this->expiresAt = $expiresAt;
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
        $headerValue[] = sprintf('%s=%s', $this->name, rawurlencode($value));

        if ($this->expiresAt) {
            $headerValue[] = sprintf('expires=%s', $this->getFormattedExpires());
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
     * {@inheritDoc}
     */
    public function withName($name)
    {
        $this->validateName($name);
        $new = clone $this;
        $new->name = $name;

        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        $name = mb_strtolower($this->name);

        return "{$name};{$this->domain};{$this->path}";
    }

    /**
     * {@inheritDoc}
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
     * @link https://tools.ietf.org/html/rfc2616#section-2.2 Rules for naming cookies.
     */
    protected function validateName($name)
    {
        if (preg_match("/[=,;\t\r\n\013\014]/", $name)) {
            throw new InvalidArgumentException(
                sprintf('The cookie name `%s` contains invalid characters.', $name)
            );
        }

        if (empty($name)) {
            throw new InvalidArgumentException('The cookie name cannot be empty.');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * {@inheritDoc}
     */
    public function getStringValue()
    {
        if ($this->isExpanded) {
            return $this->_flatten($this->value);
        }

        return $this->value;
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function withPath($path)
    {
        $this->validateString($path);
        $new = clone $this;
        $new->path = $path;

        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * {@inheritDoc}
     */
    public function withDomain($domain)
    {
        $this->validateString($domain);
        $new = clone $this;
        $new->domain = $domain;

        return $new;
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function isSecure()
    {
        return $this->secure;
    }

    /**
     * {@inheritDoc}
     */
    public function withSecure($secure)
    {
        $this->validateBool($secure);
        $new = clone $this;
        $new->secure = $secure;

        return $new;
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function isHttpOnly()
    {
        return $this->httpOnly;
    }

    /**
     * {@inheritDoc}
     */
    public function withExpiry($dateTime)
    {
        $new = clone $this;
        $new->expiresAt = $dateTime->setTimezone(new DateTimeZone('GMT'));

        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getExpiry()
    {
        return $this->expiresAt;
    }

    /**
     * {@inheritDoc}
     */
    public function getExpiresTimestamp()
    {
        if (!$this->expiresAt) {
            return null;
        }

        return $this->expiresAt->format('U');
    }

    /**
     * {@inheritDoc}
     */
    public function getFormattedExpires()
    {
        if (!$this->expiresAt) {
            return '';
        }

        return $this->expiresAt->format(static::EXPIRES_FORMAT);
    }

    /**
     * {@inheritDoc}
     */
    public function isExpired($time = null)
    {
        $time = $time ?: new DateTimeImmutable('now', new DateTimeZone('UTC'));
        if (!$this->expiresAt) {
            return false;
        }

        return $this->expiresAt < $time;
    }

    /**
     * {@inheritDoc}
     */
    public function withNeverExpire()
    {
        $new = clone $this;
        $new->expiresAt = Chronos::createFromDate(2038, 1, 1);

        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function withExpired()
    {
        $new = clone $this;
        $new->expiresAt = Chronos::createFromTimestamp(1);

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
