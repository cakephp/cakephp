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

use InvalidArgumentException;
use function Cake\Core\h;

/**
 * A Debugger formatter for generating interactive styled HTML output.
 *
 * @internal
 */
class HtmlFormatter implements FormatterInterface
{
    /**
     * @var bool
     */
    protected static bool $outputHeader = false;

    /**
     * Random id so that HTML ids are not shared between dump outputs.
     *
     * @var string
     */
    protected string $id;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->id = uniqid('', true);
    }

    /**
     * Check if the current environment is not a CLI context
     *
     * @return bool
     */
    public static function environmentMatches(): bool
    {
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
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
            $lineInfo = sprintf(
                '<span><strong>%s</strong> (line <strong>%s</strong>)</span>',
                $location['file'],
                $location['line']
            );
        }
        $parts = [
            '<div class="cake-debug-output cake-debug" style="direction:ltr">',
            $lineInfo,
            $contents,
            '</div>',
        ];

        return implode("\n", $parts);
    }

    /**
     * Generate the CSS and Javascript for dumps
     *
     * Only output once per process as we don't need it more than once.
     *
     * @return string
     */
    protected function dumpHeader(): string
    {
        ob_start();
        include __DIR__ . DIRECTORY_SEPARATOR . 'dumpHeader.html';

        return (string)ob_get_clean();
    }

    /**
     * Convert a tree of NodeInterface objects into HTML
     *
     * @param \Cake\Error\Debug\NodeInterface $node The node tree to dump.
     * @return string
     */
    public function dump(NodeInterface $node): string
    {
        $html = $this->export($node, 0);
        $head = '';
        if (!static::$outputHeader) {
            static::$outputHeader = true;
            $head = $this->dumpHeader();
        }

        return $head . '<div class="cake-debug">' . $html . '</div>';
    }

    /**
     * Convert a tree of NodeInterface objects into HTML
     *
     * @param \Cake\Error\Debug\NodeInterface $var The node tree to dump.
     * @param int $indent The current indentation level.
     * @return string
     */
    protected function export(NodeInterface $var, int $indent): string
    {
        if ($var instanceof ScalarNode) {
            return match ($var->getType()) {
                'bool' => $this->style('const', $var->getValue() ? 'true' : 'false'),
                'null' => $this->style('const', 'null'),
                'string' => $this->style('string', "'" . $var->getValue() . "'"),
                'int', 'float' => $this->style('visibility', "({$var->getType()})") .
                        ' ' . $this->style('number', "{$var->getValue()}"),
                default => "({$var->getType()}) {$var->getValue()}",
            };
        }
        if ($var instanceof ArrayNode) {
            return $this->exportArray($var, $indent + 1);
        }
        if ($var instanceof ClassNode || $var instanceof ReferenceNode) {
            return $this->exportObject($var, $indent + 1);
        }
        if ($var instanceof SpecialNode) {
            return $this->style('special', $var->getValue());
        }
        throw new InvalidArgumentException('Unknown node received ' . $var::class);
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
        $open = '<span class="cake-debug-array">' .
            $this->style('punct', '[') .
            '<samp class="cake-debug-array-items">';
        $vars = [];
        $break = "\n" . str_repeat('  ', $indent);
        $endBreak = "\n" . str_repeat('  ', $indent - 1);

        $arrow = $this->style('punct', ' => ');
        foreach ($var->getChildren() as $item) {
            $val = $item->getValue();
            $vars[] = $break . '<span class="cake-debug-array-item">' .
                $this->export($item->getKey(), $indent) . $arrow . $this->export($val, $indent) .
                $this->style('punct', ',') .
                '</span>';
        }

        $close = '</samp>' .
            $endBreak .
            $this->style('punct', ']') .
            '</span>';

        return $open . implode('', $vars) . $close;
    }

    /**
     * Handles object to string conversion.
     *
     * @param \Cake\Error\Debug\ClassNode|\Cake\Error\Debug\ReferenceNode $var Object to convert.
     * @param int $indent The current indentation level.
     * @return string
     * @see \Cake\Error\Debugger::exportVar()
     */
    protected function exportObject(ClassNode|ReferenceNode $var, int $indent): string
    {
        $objectId = "cake-db-object-{$this->id}-{$var->getId()}";
        $out = sprintf(
            '<span class="cake-debug-object" id="%s">',
            $objectId
        );
        $break = "\n" . str_repeat('  ', $indent);
        $endBreak = "\n" . str_repeat('  ', $indent - 1);

        if ($var instanceof ReferenceNode) {
            $link = sprintf(
                '<a class="cake-debug-ref" href="#%s">id: %s</a>',
                $objectId,
                $var->getId()
            );

            return '<span class="cake-debug-ref">' .
                $this->style('punct', 'object(') .
                $this->style('class', $var->getValue()) .
                $this->style('punct', ') ') .
                $link .
                $this->style('punct', ' {}') .
                '</span>';
        }

        $out .= $this->style('punct', 'object(') .
            $this->style('class', $var->getValue()) .
            $this->style('punct', ') id:') .
            $this->style('number', (string)$var->getId()) .
            $this->style('punct', ' {') .
            '<samp class="cake-debug-object-props">';

        $props = [];
        foreach ($var->getChildren() as $property) {
            $arrow = $this->style('punct', ' => ');
            $visibility = $property->getVisibility();
            $name = $property->getName();
            if ($visibility && $visibility !== 'public') {
                $props[] = $break .
                    '<span class="cake-debug-prop">' .
                    $this->style('visibility', $visibility) .
                    ' ' .
                    $this->style('property', $name) .
                    $arrow .
                    $this->export($property->getValue(), $indent) .
                '</span>';
            } else {
                $props[] = $break .
                    '<span class="cake-debug-prop">' .
                    $this->style('property', $name) .
                    $arrow .
                    $this->export($property->getValue(), $indent) .
                    '</span>';
            }
        }

        $end = '</samp>' .
            $endBreak .
            $this->style('punct', '}') .
            '</span>';

        if ($props !== []) {
            return $out . implode('', $props) . $end;
        }

        return $out . $end;
    }

    /**
     * Style text with HTML class names
     *
     * @param string $style The style name to use.
     * @param string $text The text to style.
     * @return string The styled output.
     */
    protected function style(string $style, string $text): string
    {
        return sprintf(
            '<span class="cake-debug-%s">%s</span>',
            $style,
            h($text)
        );
    }
}
