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
 * @since         4.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Error\Debug;

use RuntimeException;

/**
 * A Debugger formatter for generating output with ANSI escape codes
 *
 * @internal
 */
class ConsoleFormatter implements FormatterInterface
{
    /**
     * text colors used in colored output.
     *
     * @var array
     */
    protected $styles = [
        // bold yellow
        'const' => '1;33',
        // green
        'string' => '0;32',
        // bold blue
        'number' => '1;34',
        // cyan
        'class' => '0;36',
        // grey
        'punct' => '0;90',
        // default foreground
        'property' => '0;39',
        // magenta
        'visibility' => '0;35',
        // red
        'special' => '0;31',
    ];

    /**
     * Check if the current environment supports ANSI output.
     *
     * @return bool
     */
    public static function environmentMatches(): bool
    {
        if (PHP_SAPI !== 'cli') {
            return false;
        }
        // NO_COLOR in environment means no color.
        if (env('NO_COLOR')) {
            return false;
        }
        // Windows environment checks
        if (
            DIRECTORY_SEPARATOR === '\\' &&
            strpos(strtolower(php_uname('v')), 'windows 10') === false &&
            strpos(strtolower((string)env('SHELL')), 'bash.exe') === false &&
            !(bool)env('ANSICON') &&
            env('ConEmuANSI') !== 'ON'
        ) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function formatWrapper(string $contents, array $location): string
    {
        $lineInfo = '';
        if (isset($location['file'], $location['file'])) {
            $lineInfo = sprintf('%s (line %s)', $location['file'], $location['line']);
        }
        $parts = [
            $this->style('const', $lineInfo),
            $this->style('special', '########## DEBUG ##########'),
            $contents,
            $this->style('special', '###########################'),
            '',
        ];

        return implode("\n", $parts);
    }

    /**
     * Convert a tree of NodeInterface objects into a plain text string.
     *
     * @param \Cake\Error\Debug\NodeInterface $node The node tree to dump.
     * @return string
     */
    public function dump(NodeInterface $node): string
    {
        $indent = 0;

        return $this->export($node, $indent);
    }

    /**
     * Convert a tree of NodeInterface objects into a plain text string.
     *
     * @param \Cake\Error\Debug\NodeInterface $var The node tree to dump.
     * @param int $indent The current indentation level.
     * @return string
     */
    protected function export(NodeInterface $var, int $indent): string
    {
        if ($var instanceof ScalarNode) {
            switch ($var->getType()) {
                case 'bool':
                    return $this->style('const', $var->getValue() ? 'true' : 'false');
                case 'null':
                    return $this->style('const', 'null');
                case 'string':
                    return $this->style('string', "'" . (string)$var->getValue() . "'");
                case 'int':
                case 'float':
                    return $this->style('visibility', "({$var->getType()})") .
                        ' ' . $this->style('number', "{$var->getValue()}");
                default:
                    return "({$var->getType()}) {$var->getValue()}";
            }
        }
        if ($var instanceof ArrayNode) {
            return $this->exportArray($var, $indent + 1);
        }
        if ($var instanceof ClassNode || $var instanceof ReferenceNode) {
            return $this->exportObject($var, $indent + 1);
        }
        if ($var instanceof SpecialNode) {
            return $this->style('special', (string)$var->getValue());
        }
        throw new RuntimeException('Unknown node received ' . get_class($var));
    }

    /**
     * Export an array type object
     *
     * @param \Cake\Error\Debug\ArrayNode $var The array to export.
     * @param int $indent The current indentation level.
     * @return string Exported array.
     */
    protected function exportArray(ArrayNode $var, int $indent): string
    {
        $out = $this->style('punct', '[');
        $break = "\n" . str_repeat('  ', $indent);
        $end = "\n" . str_repeat('  ', $indent - 1);
        $vars = [];

        $arrow = $this->style('punct', ' => ');
        foreach ($var->getChildren() as $item) {
            $val = $item->getValue();
            $vars[] = $break . $this->export($item->getKey(), $indent) . $arrow . $this->export($val, $indent);
        }

        $close = $this->style('punct', ']');
        if (count($vars)) {
            return $out . implode($this->style('punct', ','), $vars) . $end . $close;
        }

        return $out . $close;
    }

    /**
     * Handles object to string conversion.
     *
     * @param \Cake\Error\Debug\ClassNode|\Cake\Error\Debug\ReferenceNode $var Object to convert.
     * @param int $indent Current indentation level.
     * @return string
     * @see \Cake\Error\Debugger::exportVar()
     */
    protected function exportObject($var, int $indent): string
    {
        $props = [];

        if ($var instanceof ReferenceNode) {
            return $this->style('punct', 'object(') .
                $this->style('class', $var->getValue()) .
                $this->style('punct', ') id:') .
                $this->style('number', (string)$var->getId()) .
                $this->style('punct', ' {}');
        }

        $out = $this->style('punct', 'object(') .
            $this->style('class', $var->getValue()) .
            $this->style('punct', ') id:') .
            $this->style('number', (string)$var->getId()) .
            $this->style('punct', ' {');

        $break = "\n" . str_repeat('  ', $indent);
        $end = "\n" . str_repeat('  ', $indent - 1) . $this->style('punct', '}');

        $arrow = $this->style('punct', ' => ');
        foreach ($var->getChildren() as $property) {
            $visibility = $property->getVisibility();
            $name = $property->getName();
            if ($visibility && $visibility !== 'public') {
                $props[] = $this->style('visibility', $visibility) .
                    ' ' .
                    $this->style('property', $name) .
                    $arrow .
                    $this->export($property->getValue(), $indent);
            } else {
                $props[] = $this->style('property', $name) .
                    $arrow .
                    $this->export($property->getValue(), $indent);
            }
        }
        if (count($props)) {
            return $out . $break . implode($break, $props) . $end;
        }

        return $out . $this->style('punct', '}');
    }

    /**
     * Style text with ANSI escape codes.
     *
     * @param string $style The style name to use.
     * @param string $text The text to style.
     * @return string The styled output.
     */
    protected function style(string $style, string $text): string
    {
        $code = $this->styles[$style];

        return "\033[{$code}m{$text}\033[0m";
    }
}
