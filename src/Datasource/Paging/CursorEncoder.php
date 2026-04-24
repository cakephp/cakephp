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
 * @since         5.4.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource\Paging;

use Cake\Datasource\Paging\Exception\InvalidCursorException;
use Cake\Utility\Security;
use JsonException;

/**
 * Encodes and decodes opaque, signed cursor tokens for seek pagination.
 *
 * Tokens have the shape `<base64url(json)>.<base64url(hmac)>` where the HMAC
 * is computed over the JSON payload using `Security::getSalt()` (or an
 * explicit secret) as the key. Decoding verifies the signature in
 * constant time before returning the payload.
 */
class CursorEncoder
{
    /**
     * Hash algorithm used for cursor signatures.
     *
     * @var string
     */
    protected const HASH_ALGO = 'sha256';

    /**
     * Encode a structured cursor into a signed opaque token.
     *
     * @param array<string, mixed> $cursor Cursor key/value pairs.
     * @param string|null $secret Override secret. Defaults to `Security::getSalt()`.
     * @return string Signed token safe for URL transport.
     * @throws \Cake\Datasource\Paging\Exception\InvalidCursorException
     */
    public static function encode(array $cursor, ?string $secret = null): string
    {
        try {
            $json = json_encode($cursor, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $e) {
            throw new InvalidCursorException('Cursor payload is not JSON encodable.', null, $e);
        }

        $payload = self::base64UrlEncode($json);
        $signature = self::base64UrlEncode(
            hash_hmac(self::HASH_ALGO, $payload, $secret ?? Security::getSalt(), true),
        );

        return $payload . '.' . $signature;
    }

    /**
     * Decode and verify a signed cursor token.
     *
     * @param string $token Signed token produced by {@see self::encode()}.
     * @param string|null $secret Override secret. Defaults to `Security::getSalt()`.
     * @return array<string, mixed> Decoded cursor payload.
     * @throws \Cake\Datasource\Paging\Exception\InvalidCursorException
     *   If the token is malformed or the signature does not verify.
     */
    public static function decode(string $token, ?string $secret = null): array
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new InvalidCursorException('Cursor token is malformed.');
        }

        [$payload, $signature] = $parts;
        $expected = self::base64UrlEncode(
            hash_hmac(self::HASH_ALGO, $payload, $secret ?? Security::getSalt(), true),
        );

        if (!hash_equals($expected, $signature)) {
            throw new InvalidCursorException('Cursor token signature is invalid.');
        }

        $json = self::base64UrlDecode($payload);
        if ($json === null) {
            throw new InvalidCursorException('Cursor token payload is malformed.');
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidCursorException('Cursor token payload is not valid JSON.', null, $e);
        }

        if (!is_array($decoded)) {
            throw new InvalidCursorException('Cursor token payload must decode to an array.');
        }

        /** @var array<string, mixed> */
        return $decoded;
    }

    /**
     * URL-safe base64 encode.
     *
     * @param string $value Raw value.
     * @return string
     */
    protected static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /**
     * URL-safe base64 decode.
     *
     * @param string $value Encoded value.
     * @return string|null Decoded bytes or `null` if malformed.
     */
    protected static function base64UrlDecode(string $value): ?string
    {
        $padded = strtr($value, '-_', '+/');
        $remainder = strlen($padded) % 4;
        if ($remainder > 0) {
            $padded .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode($padded, true);
        if ($decoded === false) {
            return null;
        }

        return $decoded;
    }
}
