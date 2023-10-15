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
 * @since         3.5.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Cookie;

use Cake\Utility\Hash;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use function Cake\Core\deprecationWarning;
use function Cake\Core\getTypeName;

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
 * @link https://tools.ietf.org/html/draft-ietf-httpbis-rfc6265bis-03
 * @link https://en.wikipedia.org/wiki/HTTP_cookie
 * @see \Cake\Http\Cookie\CookieCollection for working with collections of cookies.
 * @see \Cake\Http\Response::getCookieCollection() for working with response cookies.
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
     * @var array|string
     */
    protected $value = '';

    /**
     * Whether a JSON value has been expanded into an array.
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
    protected $path = '/';

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
     * Samesite
     *
     * @var string|null
     */
    protected $sameSite = null;

    /**
     * Default attributes for a cookie.
     *
     * @var array<string, mixed>
     * @see \Cake\Http\Cookie\Cookie::setDefaults()
     */
    protected static $defaults = [
        'expires' => null,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => false,
        'samesite' => null,
    ];

    /**
     * Constructor
     *
     * The constructors args are similar to the native PHP `setcookie()` method.
     * The only difference is the 3rd argument which excepts null or an
     * DateTime or DateTimeImmutable object instead an integer.
     *
     * @link https://php.net/manual/en/function.setcookie.php
     * @param string $name Cookie name
     * @param array|string $value Value of the cookie
     * @param \DateTime|\DateTimeImmutable|null $expiresAt Expiration time and date
     * @param string|null $path Path
     * @param string|null $domain Domain
     * @param bool|null $secure Is secure
     * @param bool|null $httpOnly HTTP Only
     * @param string|null $sameSite Samesite
     */
    public function __construct(
        string $name,
        $value = '',
        ?DateTimeInterface $expiresAt = null,
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        ?bool $httpOnly = null,
        ?string $sameSite = null
    ) {
        $this->validateName($name);
        $this->name = $name;

        $this->_setValue($value);

        $this->domain = $domain ?? static::$defaults['domain'];
        $this->httpOnly = $httpOnly ?? static::$defaults['httponly'];
        $this->path = $path ?? static::$defaults['path'];
        $this->secure = $secure ?? static::$defaults['secure'];
        if ($sameSite === null) {
            $this->sameSite = static::$defaults['samesite'];
        } else {
            $this->validateSameSiteValue($sameSite);
            $this->sameSite = $sameSite;
        }

        if ($expiresAt) {
            $expiresAt = $expiresAt->setTimezone(new DateTimeZone('GMT'));
        } else {
            $expiresAt = static::$defaults['expires'];
        }
        $this->expiresAt = $expiresAt;
    }

    /**
     * Set default options for the cookies.
     *
     * Valid option keys are:
     *
     * - `expires`: Can be a UNIX timestamp or `strtotime()` compatible string or `DateTimeInterface` instance or `null`.
     * - `path`: A path string. Defauts to `'/'`.
     * - `domain`: Domain name string. Defaults to `''`.
     * - `httponly`: Boolean. Defaults to `false`.
     * - `secure`: Boolean. Defaults to `false`.
     * - `samesite`: Can be one of `CookieInterface::SAMESITE_LAX`, `CookieInterface::SAMESITE_STRICT`,
     *    `CookieInterface::SAMESITE_NONE` or `null`. Defaults to `null`.
     *
     * @param array<string, mixed> $options Default options.
     * @return void
     */
    public static function setDefaults(array $options): void
    {
        if (isset($options['expires'])) {
            $options['expires'] = static::dateTimeInstance($options['expires']);
        }
        if (isset($options['samesite'])) {
            static::validateSameSiteValue($options['samesite']);
        }

        static::$defaults = $options + static::$defaults;
    }

    /**
     * Factory method to create Cookie instances.
     *
     * @param string $name Cookie name
     * @param array|string $value Value of the cookie
     * @param array<string, mixed> $options Cookies options.
     * @return static
     * @see \Cake\Cookie\Cookie::setDefaults()
     */
    public static function create(string $name, $value, array $options = [])
    {
        $options += static::$defaults;
        $options['expires'] = static::dateTimeInstance($options['expires']);

        return new static(
            $name,
            $value,
            $options['expires'],
            $options['path'],
            $options['domain'],
            $options['secure'],
            $options['httponly'],
            $options['samesite']
        );
    }

    /**
     * Converts non null expiry value into DateTimeInterface instance.
     *
     * @param mixed $expires Expiry value.
     * @return \DateTime|\DateTimeImmutable|null
     */
    protected static function dateTimeInstance($expires): ?DateTimeInterface
    {
        if ($expires === null) {
            return null;
        }

        if ($expires instanceof DateTimeInterface) {
            /** @psalm-suppress UndefinedInterfaceMethod */
            return $expires->setTimezone(new DateTimeZone('GMT'));
        }

        if (!is_string($expires) && !is_int($expires)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid type `%s` for expires. Expected an string, integer or DateTime object.',
                getTypeName($expires)
            ));
        }

        if (!is_numeric($expires)) {
            $expires = strtotime($expires) ?: null;
        }

        if ($expires !== null) {
            $expires = new DateTimeImmutable('@' . (string)$expires);
        }

        return $expires;
    }

    /**
     * Create Cookie instance from "set-cookie" header string.
     *
     * @param string $cookie Cookie header string.
     * @param array<string, mixed> $defaults Default attributes.
     * @return static
     * @see \Cake\Http\Cookie\Cookie::setDefaults()
     */
    public static function createFromHeaderString(string $cookie, array $defaults = [])
    {
        if (strpos($cookie, '";"') !== false) {
            $cookie = str_replace('";"', '{__cookie_replace__}', $cookie);
            $parts = str_replace('{__cookie_replace__}', '";"', explode(';', $cookie));
        } else {
            $parts = preg_split('/\;[ \t]*/', $cookie);
        }

        $nameValue = explode('=', array_shift($parts), 2);
        $name = array_shift($nameValue);
        $value = array_shift($nameValue) ?? '';

        $data = [
                'name' => urldecode($name),
                'value' => urldecode($value),
            ] + $defaults;

        foreach ($parts as $part) {
            if (strpos($part, '=') !== false) {
                [$key, $value] = explode('=', $part);
            } else {
                $key = $part;
                $value = true;
            }

            $key = strtolower($key);
            $data[$key] = $value;
        }

        if (isset($data['max-age'])) {
            $data['expires'] = time() + (int)$data['max-age'];
            unset($data['max-age']);
        }

        if (isset($data['samesite'])) {
            // Ignore invalid value when parsing headers
            // https://tools.ietf.org/html/draft-west-first-party-cookies-07#section-4.1
            if (!in_array($data['samesite'], CookieInterface::SAMESITE_VALUES, true)) {
                unset($data['samesite']);
            }
        }

        $name = (string)$data['name'];
        $value = (string)$data['value'];
        unset($data['name'], $data['value']);

        return Cookie::create(
            $name,
            $value,
            $data
        );
    }

    /**
     * Returns a header value as string
     *
     * @return string
     */
    public function toHeaderValue(): string
    {
        $value = $this->value;
        if ($this->isExpanded) {
            /** @psalm-suppress PossiblyInvalidArgument */
            $value = $this->_flatten($this->value);
        }
        $headerValue = [];
        /** @psalm-suppress PossiblyInvalidArgument */
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
        if ($this->sameSite) {
            $headerValue[] = sprintf('samesite=%s', $this->sameSite);
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
     * @inheritDoc
     */
    public function withName(string $name)
    {
        $this->validateName($name);
        $new = clone $this;
        $new->name = $name;

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return "{$this->name};{$this->domain};{$this->path}";
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
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
    protected function validateName(string $name): void
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
     * @inheritDoc
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Gets the cookie value as a string.
     *
     * This will collapse any complex data in the cookie with json_encode()
     *
     * @return mixed
     * @deprecated 4.0.0 Use {@link getScalarValue()} instead.
     */
    public function getStringValue()
    {
        deprecationWarning('Cookie::getStringValue() is deprecated. Use getScalarValue() instead.');

        return $this->getScalarValue();
    }

    /**
     * @inheritDoc
     */
    public function getScalarValue()
    {
        if ($this->isExpanded) {
            /** @psalm-suppress PossiblyInvalidArgument */
            return $this->_flatten($this->value);
        }

        return $this->value;
    }

    /**
     * @inheritDoc
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
     * @param array|string $value The value to store.
     * @return void
     */
    protected function _setValue($value): void
    {
        $this->isExpanded = is_array($value);
        $this->value = $value;
    }

    /**
     * @inheritDoc
     */
    public function withPath(string $path)
    {
        $new = clone $this;
        $new->path = $path;

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @inheritDoc
     */
    public function withDomain(string $domain)
    {
        $new = clone $this;
        $new->domain = $domain;

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * @inheritDoc
     */
    public function isSecure(): bool
    {
        return $this->secure;
    }

    /**
     * @inheritDoc
     */
    public function withSecure(bool $secure)
    {
        $new = clone $this;
        $new->secure = $secure;

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withHttpOnly(bool $httpOnly)
    {
        $new = clone $this;
        $new->httpOnly = $httpOnly;

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    /**
     * @inheritDoc
     */
    public function withExpiry($dateTime)
    {
        $new = clone $this;
        $new->expiresAt = $dateTime->setTimezone(new DateTimeZone('GMT'));

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function getExpiry()
    {
        return $this->expiresAt;
    }

    /**
     * @inheritDoc
     */
    public function getExpiresTimestamp(): ?int
    {
        if (!$this->expiresAt) {
            return null;
        }

        return (int)$this->expiresAt->format('U');
    }

    /**
     * @inheritDoc
     */
    public function getFormattedExpires(): string
    {
        if (!$this->expiresAt) {
            return '';
        }

        return $this->expiresAt->format(static::EXPIRES_FORMAT);
    }

    /**
     * @inheritDoc
     */
    public function isExpired($time = null): bool
    {
        $time = $time ?: new DateTimeImmutable('now', new DateTimeZone('UTC'));
        if (!$this->expiresAt) {
            return false;
        }

        return $this->expiresAt < $time;
    }

    /**
     * @inheritDoc
     */
    public function withNeverExpire()
    {
        $new = clone $this;
        $new->expiresAt = new DateTimeImmutable('2038-01-01');

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withExpired()
    {
        $new = clone $this;
        $new->expiresAt = new DateTimeImmutable('@1');

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function getSameSite(): ?string
    {
        return $this->sameSite;
    }

    /**
     * @inheritDoc
     */
    public function withSameSite(?string $sameSite)
    {
        if ($sameSite !== null) {
            $this->validateSameSiteValue($sameSite);
        }

        $new = clone $this;
        $new->sameSite = $sameSite;

        return $new;
    }

    /**
     * Check that value passed for SameSite is valid.
     *
     * @param string $sameSite SameSite value
     * @return void
     * @throws \InvalidArgumentException
     */
    protected static function validateSameSiteValue(string $sameSite)
    {
        if (!in_array($sameSite, CookieInterface::SAMESITE_VALUES, true)) {
            throw new InvalidArgumentException(
                'Samesite value must be either of: ' . implode(', ', CookieInterface::SAMESITE_VALUES)
            );
        }
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
    public function check(string $path): bool
    {
        if ($this->isExpanded === false) {
            /** @psalm-suppress PossiblyInvalidArgument */
            $this->value = $this->_expand($this->value);
        }

        /** @psalm-suppress PossiblyInvalidArgument */
        return Hash::check($this->value, $path);
    }

    /**
     * Create a new cookie with updated data.
     *
     * @param string $path Path to write to
     * @param mixed $value Value to write
     * @return static
     */
    public function withAddedValue(string $path, $value)
    {
        $new = clone $this;
        if ($new->isExpanded === false) {
            /** @psalm-suppress PossiblyInvalidArgument */
            $new->value = $new->_expand($new->value);
        }

        /** @psalm-suppress PossiblyInvalidArgument */
        $new->value = Hash::insert($new->value, $path, $value);

        return $new;
    }

    /**
     * Create a new cookie without a specific path
     *
     * @param string $path Path to remove
     * @return static
     */
    public function withoutAddedValue(string $path)
    {
        $new = clone $this;
        if ($new->isExpanded === false) {
            /** @psalm-suppress PossiblyInvalidArgument */
            $new->value = $new->_expand($new->value);
        }

        /** @psalm-suppress PossiblyInvalidArgument */
        $new->value = Hash::remove($new->value, $path);

        return $new;
    }

    /**
     * Read data from the cookie
     *
     * This method will expand serialized complex data,
     * on first use.
     *
     * @param string|null $path Path to read the data from
     * @return mixed
     */
    public function read(?string $path = null)
    {
        if ($this->isExpanded === false) {
            /** @psalm-suppress PossiblyInvalidArgument */
            $this->value = $this->_expand($this->value);
        }

        if ($path === null) {
            return $this->value;
        }

        /** @psalm-suppress PossiblyInvalidArgument */
        return Hash::get($this->value, $path);
    }

    /**
     * Checks if the cookie value was expanded
     *
     * @return bool
     */
    public function isExpanded(): bool
    {
        return $this->isExpanded;
    }

    /**
     * @inheritDoc
     */
    public function getOptions(): array
    {
        $options = [
            'expires' => (int)$this->getExpiresTimestamp(),
            'path' => $this->path,
            'domain' => $this->domain,
            'secure' => $this->secure,
            'httponly' => $this->httpOnly,
        ];

        if ($this->sameSite !== null) {
            $options['samesite'] = $this->sameSite;
        }

        return $options;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'value' => $this->getScalarValue(),
        ] + $this->getOptions();
    }

    /**
     * Implode method to keep keys are multidimensional arrays
     *
     * @param array $array Map of key and values
     * @return string A JSON encoded string.
     */
    protected function _flatten(array $array): string
    {
        return json_encode($array);
    }

    /**
     * Explode method to return array from string set in CookieComponent::_flatten()
     * Maintains reading backwards compatibility with 1.x CookieComponent::_flatten().
     *
     * @param string $string A string containing JSON encoded data, or a bare string.
     * @return array|string Map of key and values
     */
    protected function _expand(string $string)
    {
        $this->isExpanded = true;
        $first = substr($string, 0, 1);
        if ($first === '{' || $first === '[') {
            $ret = json_decode($string, true);

            return $ret ?? $string;
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
