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
 */
namespace Cake\I18n\Extractor;

use IntlChar;

/**
 * Decodes quoted PHP literals without evaluating source code.
 *
 * @internal
 */
final class PhpStringLiteral
{
    /**
     * Decode a quoted token into untranslated text for catalog generation.
     *
     * @param string $literal A constant-encapsed-string token.
     * @return string Decoded source text.
     */
    public static function decode(string $literal): string
    {
        $literal = ltrim($literal, 'bB');
        $quote = $literal[0];
        $value = substr($literal, 1, -1);
        if ($quote !== '"') {
            return strtr($value, ["\\'" => "'", '\\\\' => '\\']);
        }

        return (string)preg_replace_callback(
            '/\\\\(n|r|t|v|e|f|\\\\|\$|"|[0-7]{1,3}|x[0-9a-fA-F]{1,2}|u\{[0-9a-fA-F]+\})/',
            static function (array $matches): string {
                $escape = $matches[1];
                if (str_starts_with($escape, 'u{')) {
                    return IntlChar::chr((int)hexdec(substr($escape, 2, -1))) ?? '\\' . $escape;
                }

                return stripcslashes('\\' . $escape);
            },
            $value,
        );
    }
}
