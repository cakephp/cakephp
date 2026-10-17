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

use PhpToken;

/**
 * Extracts literal messages passed to CakePHP's translation functions.
 *
 * @internal
 */
class FunctionExtractor implements ExtractorInterface
{
    /**
     * Read translation function markers and report unresolved literal arguments.
     *
     * @param string $file Source filename for diagnostics.
     * @param string $code PHP source code.
     * @return iterable<\Cake\I18n\Extractor\ExtractedMessage|\Cake\I18n\Extractor\ExtractionDiagnostic>
     */
    public function extract(string $file, string $code): iterable
    {
        $functions = [
            '__' => ['singular'],
            '__n' => ['singular', 'plural'],
            '__d' => ['domain', 'singular'],
            '__dn' => ['domain', 'singular', 'plural'],
            '__x' => ['context', 'singular'],
            '__xn' => ['context', 'singular', 'plural'],
            '__dx' => ['domain', 'context', 'singular'],
            '__dxn' => ['domain', 'context', 'singular', 'plural'],
        ];
        if (!preg_match('/(' . implode('|', array_keys($functions)) . ')\s*\(/', $code)) {
            return;
        }
        $tokens = array_values(array_filter(
            PhpToken::tokenize($code),
            static fn(PhpToken $lexeme): bool => !$lexeme->isIgnorable() && $lexeme->id !== T_INLINE_HTML,
        ));
        foreach ($tokens as $index => $lexeme) {
            $name = ltrim($lexeme->text, '\\');
            if (
                !in_array($lexeme->id, [T_STRING, T_NAME_FULLY_QUALIFIED], true)
                || !isset($functions[$name])
                || ($tokens[$index + 1]->text ?? '') !== '('
                || in_array(
                    $tokens[$index - 1]->id ?? null,
                    [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW],
                    true,
                )
            ) {
                continue;
            }
            $position = $index + 2;
            $values = [];
            foreach ($functions[$name] as $argument) {
                $value = $this->readString($tokens, $position);
                if ($value === null) {
                    yield new ExtractionDiagnostic(sprintf(
                        "Invalid marker content in %s:%d\n* %s",
                        $file,
                        $lexeme->line,
                        $this->readMarker($tokens, $index),
                    ), invalidMarker: true);
                    continue 2;
                }
                $values[$argument] = $value;
            }
            yield new ExtractedMessage(
                $values['singular'],
                $values['domain'] ?? 'default',
                $values['context'] ?? '',
                $values['plural'] ?? null,
                $lexeme->line,
            );
        }
    }

    /**
     * Read one quoted or numeric argument, allowing concatenated string literals.
     *
     * @param list<\PhpToken> $tokens Source tokens without ignorable tokens.
     * @param int $position Argument position, advanced beyond its delimiter.
     * @return string|null Source text, or null for nonliteral content.
     */
    protected function readString(array $tokens, int &$position): ?string
    {
        $value = '';
        $expectString = true;
        while (isset($tokens[$position])) {
            $lexeme = $tokens[$position++];
            if ($expectString) {
                if ($lexeme->id === T_CONSTANT_ENCAPSED_STRING) {
                    $value .= PhpStringLiteral::decode($lexeme->text);
                } elseif ($lexeme->id === T_LNUMBER) {
                    $value .= $lexeme->text;
                } else {
                    return null;
                }
            } else {
                if ($lexeme->text === ',' || $lexeme->text === ')') {
                    return $value;
                }
                if ($lexeme->text !== '.') {
                    return null;
                }
            }
            $expectString = !$expectString;
        }

        return null;
    }

    /**
     * Reconstruct an invalid marker for the command's optional error display.
     *
     * @param list<\PhpToken> $tokens Source tokens.
     * @param int $position Position of the function name.
     * @return string Marker through its closing parenthesis or the end of the file.
     */
    protected function readMarker(array $tokens, int $position): string
    {
        $marker = $tokens[$position++]->text;
        $depth = 0;
        do {
            $lexeme = $tokens[$position++]->text;
            $marker .= $lexeme;
            if ($lexeme === '(') {
                $depth++;
            } elseif ($lexeme === ')') {
                $depth--;
            }
        } while ($depth > 0 && isset($tokens[$position]));

        return $marker;
    }
}
