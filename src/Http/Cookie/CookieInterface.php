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

use DateTimeInterface;

/**
 * Cookie Interface
 */
interface CookieInterface
{
    /**
     * Expires attribute format.
     *
     * @var string
     */
    public const EXPIRES_FORMAT = 'D, d-M-Y H:i:s T';

    /**
     * SameSite attribute value: Lax
     *
     * @var string
     */
    public const SAMESITE_LAX = 'Lax';

    /**
     * SameSite attribute value: Strict
     *
     * @var string
     */
    public const SAMESITE_STRICT = 'Strict';

    /**
     * SameSite attribute value: None
     *
     * @var string
     */
    public const SAMESITE_NONE = 'None';

    /**
     * Valid values for "SameSite" attribute.
     *
     * @var array<string>
     */
    public const SAMESITE_VALUES = [
        self::SAMESITE_LAX,
        self::SAMESITE_STRICT,
        self::SAMESITE_NONE,
    ];

    /**
     * Sets the cookie name
     *
     * @param string $name Name of the cookie
     */
    public function withName(string $name): static;

    /**
     * Gets the cookie name
     */
    public function getName(): string;

    /**
     * Gets the cookie value
     */
    public function getValue(): array|string;

    /**
     * Gets the cookie value as scalar.
     *
     * This will collapse any complex data in the cookie with json_encode()
     */
    public function getScalarValue(): string;

    /**
     * Create a cookie with an updated value.
     *
     * @param array|string|float|int|bool $value Value of the cookie to set
     */
    public function withValue(array|string|float|int|bool $value): static;

    /**
     * Get the id for a cookie
     *
     * Cookies are unique across name, domain, path tuples.
     */
    public function getId(): string;

    /**
     * Get the path attribute.
     */
    public function getPath(): string;

    /**
     * Create a new cookie with an updated path
     *
     * @param string $path Sets the path
     */
    public function withPath(string $path): static;

    /**
     * Get the domain attribute.
     */
    public function getDomain(): string;

    /**
     * Create a cookie with an updated domain
     *
     * @param string $domain Domain to set
     */
    public function withDomain(string $domain): static;

    /**
     * Get the current expiry time
     *
     * @return \DateTimeInterface|null Timestamp of expiry or null
     */
    public function getExpiry(): ?DateTimeInterface;

    /**
     * Get the timestamp from the expiration time
     *
     * @return int|null The expiry time as an integer.
     */
    public function getExpiresTimestamp(): ?int;

    /**
     * Builds the expiration value part of the header string
     */
    public function getFormattedExpires(): string;

    /**
     * Create a cookie with an updated expiration date
     *
     * @param \DateTimeInterface $dateTime Date time object
     */
    public function withExpiry(DateTimeInterface $dateTime): static;

    /**
     * Create a new cookie that will virtually never expire.
     */
    public function withNeverExpire(): static;

    /**
     * Create a new cookie that will expire/delete the cookie from the browser.
     *
     * This is done by setting the expiration time to 1 year ago
     */
    public function withExpired(): static;

    /**
     * Check if a cookie is expired when compared to $time
     *
     * Cookies without an expiration date always return false.
     *
     * @param \DateTimeInterface|null $time The time to test against. Defaults to 'now' in UTC.
     */
    public function isExpired(?DateTimeInterface $time = null): bool;

    /**
     * Check if the cookie is HTTP only
     */
    public function isHttpOnly(): bool;

    /**
     * Create a cookie with HTTP Only updated
     *
     * @param bool $httpOnly HTTP Only
     */
    public function withHttpOnly(bool $httpOnly): static;

    /**
     * Check if the cookie is secure
     */
    public function isSecure(): bool;

    /**
     * Create a cookie with Secure updated
     *
     * @param bool $secure Secure attribute value
     */
    public function withSecure(bool $secure): static;

    /**
     * Get the SameSite attribute.
     */
    public function getSameSite(): ?SameSiteEnum;

    /**
     * Create a cookie with an updated SameSite option.
     *
     * @param \Cake\Http\Cookie\SameSiteEnum|string|null $sameSite Value for to set for Samesite option.
     */
    public function withSameSite(SameSiteEnum|string|null $sameSite): static;

    /**
     * Get cookie options
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array;

    /**
     * Get cookie data as array.
     *
     * @return array<string, mixed> With keys `name`, `value`, `expires` etc. options.
     */
    public function toArray(): array;

    /**
     * Returns the cookie as header value
     */
    public function toHeaderValue(): string;
}
