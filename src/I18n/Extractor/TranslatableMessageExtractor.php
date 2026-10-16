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

use Cake\I18n\TranslatableMessage;
use PhpToken;

/**
 * Finds literal TranslatableMessage metadata without executing PHP source code.
 *
 * Resolves class imports and namespaces, then reads constructor arguments while
 * ignoring runtime formatting values. Used by the i18n extraction command for
 * messages in attributes, templates, and ordinary application code.
 *
 * @internal
 */
class TranslatableMessageExtractor implements ExtractorInterface
{
    /**
     * Extract constructor metadata, preserving null for values that are not literal strings.
     *
     * @param string $file Source filename for diagnostics.
     * @param string $code PHP source, which is never included or evaluated.
     * @return iterable<\Cake\I18n\Extractor\ExtractedMessage|\Cake\I18n\Extractor\ExtractionDiagnostic>
     */
    public function extract(string $file, string $code): iterable
    {
        $tokens = array_values(array_filter(
            PhpToken::tokenize($code),
            static fn(PhpToken $token): bool => !$token->isIgnorable() && $token->id !== T_INLINE_HTML,
        ));
        $namespace = '';
        $imports = [];
        $depth = 0;
        $namespaceDepth = 0;
        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            if ($token->id === T_NAMESPACE) {
                $namespace = '';
                while (isset($tokens[++$i]) && !in_array($tokens[$i]->text, [';', '{'], true)) {
                    $namespace .= $tokens[$i]->text;
                }
                $imports = [];
                $namespaceDepth = ($tokens[$i]->text ?? '') === '{' ? ++$depth : $depth;
                continue;
            }
            if ($token->text === '{') {
                $depth++;
            } elseif ($token->text === '}') {
                $depth--;
                if ($depth < $namespaceDepth) {
                    $namespace = '';
                    $imports = [];
                    $namespaceDepth = $depth;
                }
            }
            if ($token->id === T_USE && $depth === $namespaceDepth && ($tokens[$i + 1]->text ?? '') !== '(') {
                $imports = $this->readImports($tokens, $i) + $imports;
                continue;
            }
            if ($token->id !== T_NEW || !isset($tokens[$i + 1], $tokens[$i + 2])) {
                continue;
            }
            $class = $this->resolveClass($tokens[$i + 1]->text, $namespace, $imports);
            if (strcasecmp($class, TranslatableMessage::class) !== 0 || $tokens[$i + 2]->text !== '(') {
                continue;
            }
            $arguments = $this->readArguments($tokens, $i + 2);
            $metadata = $this->readMetadata($arguments);
            if ($metadata['message'] === null || $metadata['domain'] === null || $metadata['context'] === null) {
                yield new ExtractionDiagnostic(sprintf(
                    'Could not extract TranslatableMessage in %s on line %d: ' .
                    'use literal message, domain, and context strings.',
                    $file,
                    $token->line,
                ));
                continue;
            }
            if ($metadata['message'] !== '') {
                yield new ExtractedMessage(
                    $metadata['message'],
                    $metadata['domain'],
                    $metadata['context'],
                    line: $token->line,
                );
            }
        }
    }

    /**
     * Read ordinary, aliased, comma-separated, and grouped class imports.
     *
     * @param list<\PhpToken> $tokens Source tokens without whitespace or comments.
     * @param int $position Position of use, advanced to its terminating semicolon.
     * @return array<string, string> Lowercase aliases mapped to fully qualified class names.
     */
    protected function readImports(array $tokens, int &$position): array
    {
        $statement = '';
        while (isset($tokens[++$position]) && $tokens[$position]->text !== ';') {
            $statement .= $tokens[$position]->text . ' ';
        }
        $statement = trim($statement);
        if (preg_match('/^(function|const)\s/i', $statement)) {
            return [];
        }
        $prefix = '';
        $group = strpos($statement, '{');
        if ($group !== false) {
            $prefix = (string)preg_replace('/\s+/', '', substr($statement, 0, $group));
            $statement = substr($statement, $group + 1, (int)strrpos($statement, '}') - $group - 1);
        }
        $imports = [];
        foreach (explode(',', $statement) as $import) {
            $parts = preg_split('/\s+as\s+/i', trim($import));
            if (!$parts || preg_match('/^(function|const)\s/i', $parts[0])) {
                continue;
            }
            $name = $prefix . preg_replace('/\s+/', '', $parts[0]);
            $name = trim($name, '\\');
            if ($name === '') {
                continue;
            }
            $alias = $parts[1] ?? substr($name, (int)strrpos('\\' . $name, '\\'));
            $imports[strtolower(trim($alias))] = $name;
        }

        return $imports;
    }

    /**
     * Resolve a constructor name using the current namespace's class aliases.
     *
     * @param string $name Constructor class name as written in the source.
     * @param string $namespace Current namespace, or an empty string for global code.
     * @param array<string, string> $imports Imported class aliases.
     * @return string Resolved fully qualified name.
     */
    protected function resolveClass(string $name, string $namespace, array $imports): string
    {
        if (str_starts_with($name, '\\')) {
            return substr($name, 1);
        }
        if (str_starts_with(strtolower($name), 'namespace\\')) {
            return ltrim($namespace . '\\' . substr($name, 10), '\\');
        }
        $parts = explode('\\', $name, 2);
        $alias = strtolower($parts[0]);
        if (isset($imports[$alias])) {
            return $imports[$alias] . (isset($parts[1]) ? '\\' . $parts[1] : '');
        }

        return ltrim($namespace . '\\' . $name, '\\');
    }

    /**
     * Split constructor arguments without splitting nested arrays, calls, or closures.
     *
     * @param list<\PhpToken> $tokens Source tokens.
     * @param int $position Opening parenthesis of a constructor call.
     * @return list<list<\PhpToken>> Positional or named argument token lists.
     */
    protected function readArguments(array $tokens, int $position): array
    {
        $arguments = [];
        $argument = [];
        $depth = 0;
        while (isset($tokens[++$position])) {
            $token = $tokens[$position];
            if ($depth === 0 && ($token->text === ',' || $token->text === ')')) {
                if ($argument) {
                    $arguments[] = $argument;
                }
                $argument = [];
                if ($token->text === ')') {
                    break;
                }
                continue;
            }
            if (in_array($token->text, ['(', '[', '{', '#['], true)) {
                $depth++;
            } elseif (in_array($token->text, [')', ']', '}'], true)) {
                $depth--;
            }
            $argument[] = $token;
        }

        return $arguments;
    }

    /**
     * Map named and positional arguments to message, domain, and context literals.
     *
     * @param list<list<\PhpToken>> $arguments Constructor arguments.
     * @return array{message: string|null, domain: string|null, context: string|null}
     */
    protected function readMetadata(array $arguments): array
    {
        $metadata = ['message' => null, 'domain' => 'default', 'context' => ''];
        $names = ['message', 'arguments', 'domain', 'context'];
        foreach ($arguments as $position => $argument) {
            if ($argument[0]->id === T_ELLIPSIS) {
                return ['message' => null, 'domain' => null, 'context' => null];
            }
            $name = $names[$position] ?? null;
            if (($argument[1]->text ?? '') === ':') {
                $name = $argument[0]->text;
                $argument = array_slice($argument, 2);
            }
            if ($name !== null && array_key_exists($name, $metadata)) {
                $metadata[$name] = $this->readLiteral($argument);
            }
        }

        return $metadata;
    }

    /**
     * Read quoted string literals and literal concatenations without evaluating expressions.
     *
     * @param list<\PhpToken> $tokens Tokens for one constructor argument.
     * @return string|null Decoded string, or null for a dynamic expression.
     */
    protected function readLiteral(array $tokens): ?string
    {
        $value = '';
        $expectString = true;
        foreach ($tokens as $token) {
            if ($expectString) {
                if ($token->id !== T_CONSTANT_ENCAPSED_STRING) {
                    return null;
                }
                $value .= PhpStringLiteral::decode($token->text);
            } elseif ($token->text !== '.') {
                return null;
            }
            $expectString = !$expectString;
        }

        return $expectString ? null : $value;
    }
}
