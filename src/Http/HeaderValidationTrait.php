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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 *
 * Parts of this file are derived from laminas-diactoros
 * @copyright Copyright (c) 2020 Laminas Project a Series of LF Projects, LLC. (https://getlaminas.org/)
 * @license   https://github.com/laminas/laminas-diactoros/blob/3.9.x/LICENSE.md New BSD License
 */
namespace Cake\Http;

use InvalidArgumentException;

/**
 * Shared validation for PSR-7 message header mutations.
 */
trait HeaderValidationTrait
{
    /**
     * Validate a header name.
     *
     * @param string $name The header name.
     * @return void
     * @phpstan-assert non-empty-string $name
     * @throws \InvalidArgumentException When the name isn't a valid RFC 7230 token.
     */
    protected function assertHeaderName(string $name): void
    {
        if (!preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/D', $name)) {
            throw new InvalidArgumentException(sprintf('`%s` is not a valid header name.', $name));
        }
    }

    /**
     * Validate and normalize header values.
     *
     * @param mixed $values A header value or list of header values.
     * @return non-empty-list<string>
     * @throws \InvalidArgumentException When a value is invalid.
     */
    protected function filterHeaderValue(mixed $values): array
    {
        if (!is_array($values)) {
            $values = [$values];
        }
        if ($values === []) {
            throw new InvalidArgumentException(
                'Invalid header value: must be a string or array of strings; cannot be an empty array.',
            );
        }

        $result = [];
        foreach ($values as $value) {
            if (!is_string($value) && !is_int($value) && !is_float($value)) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid header value type; must be a string or numeric; received `%s`.',
                    get_debug_type($value),
                ));
            }
            $value = (string)$value;

            // Reject CRLF injection and non-visible characters. Only obsolete line
            // folding (CRLF followed by space or tab) is accepted (RFC 7230 section 3.2).
            if (
                preg_match("#(?:(?<!\r)\n|\r(?!\n)|\r\n(?![ \t]))#", $value) ||
                preg_match('/[^\x09\x0a\x0d\x20-\x7E\x80-\xFF]/', $value)
            ) {
                throw new InvalidArgumentException(sprintf('`%s` is not a valid header value.', $value));
            }

            // Normalize line folding to a single space and trim optional whitespace.
            $result[] = trim(str_replace(["\r\n\t", "\r\n "], ' ', $value), "\t ");
        }

        return $result;
    }
}
