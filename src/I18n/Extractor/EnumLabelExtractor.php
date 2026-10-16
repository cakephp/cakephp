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

use Cake\Database\Type\Attribute\Label;
use ReflectionClass;
use Throwable;

/**
 * Extracts enum case labels using the existing attribute reflection behavior.
 *
 * @internal
 */
class EnumLabelExtractor implements ExtractorInterface
{
    /**
     * Extract Label attribute strings from a PHP file using reflection.
     *
     * @param string $file Absolute path to the file being processed.
     * @param string $code File contents.
     * @return iterable<\Cake\I18n\Extractor\ExtractedMessage|\Cake\I18n\Extractor\ExtractionDiagnostic>
     */
    public function extract(string $file, string $code): iterable
    {
        $fqn = $this->parseClassName($code);
        if ($fqn === null) {
            return;
        }

        try {
            // @phpstan-ignore argument.type
            $reflection = new ReflectionClass($fqn);
        } catch (Throwable $e) {
            yield new ExtractionDiagnostic(
                sprintf('Could not reflect class/enum %s in file %s: %s', $fqn, $file, $e->getMessage()),
            );

            return;
        }

        if (!$reflection->isEnum()) {
            return;
        }

        foreach ($reflection->getReflectionConstants() as $constant) {
            if (!$constant->isEnumCase()) {
                continue;
            }

            $labelAttributes = $constant->getAttributes(Label::class);
            if (!$labelAttributes) {
                continue;
            }

            /** @var \Cake\Database\Type\Attribute\Label $label */
            $label = $labelAttributes[0]->newInstance();
            yield new ExtractedMessage($label->label, $label->domain, $label->context);
        }
    }

    /**
     * Parse the fully qualified class/enum name from PHP source code.
     *
     * Uses token_get_all() to read the namespace declaration and the first
     * class or enum name without executing the file.
     *
     * @param string $code PHP source code.
     * @return string|null Fully qualified name, or null if none found.
     */
    protected function parseClassName(string $code): ?string
    {
        $tokens = token_get_all($code);
        $namespace = '';
        $waitingForNamespace = false;
        $waitingForName = false;
        $previous = null;

        foreach ($tokens as $token) {
            if (!is_array($token)) {
                continue;
            }

            [$type, $value] = $token;

            if ($type === T_WHITESPACE) {
                continue;
            }

            if ($type === T_NAMESPACE) {
                $waitingForNamespace = true;
                continue;
            }

            if ($waitingForNamespace) {
                if ($type === T_STRING || $type === T_NAME_QUALIFIED) {
                    $namespace = $value;
                }
                $waitingForNamespace = false;
                continue;
            }

            if (($type === T_ENUM || $type === T_CLASS) && $previous !== T_DOUBLE_COLON) {
                $waitingForName = true;
                continue;
            }

            if ($waitingForName && $type === T_STRING) {
                return $namespace === '' ? $value : $namespace . '\\' . $value;
            }

            if ($waitingForName) {
                $waitingForName = false;
            }

            $previous = $type;
        }

        return null;
    }
}
