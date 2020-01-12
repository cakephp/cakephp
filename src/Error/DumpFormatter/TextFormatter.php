<?php
declare(strict_types=1);

namespace Cake\Error\DumpFormatter;

use Cake\Error\DumpNode\ArrayNode;
use Cake\Error\DumpNode\ClassNode;
use Cake\Error\DumpNode\NodeInterface;
use Cake\Error\DumpNode\ReferenceNode;
use Cake\Error\DumpNode\ScalarNode;
use Cake\Error\DumpNode\SpecialNode;
use RuntimeException;

class TextFormatter
{
    public function dump(NodeInterface $node): string
    {
        $indent = 0;
        return $this->_export($node, $indent);
    }

    protected function _export(NodeInterface $var, int $indent): string
    {
        if ($var instanceof ScalarNode) {
            switch ($var->getType()) {
                case 'bool':
                    return $var->getValue() ? 'true' : 'false';
                case 'null':
                    return 'null';
                case 'string':
                    return "'" . $var->getValue() . "'";
                default:
                    return "({$var->getType()}) {$var->getValue()}";
            }
        }
        if ($var instanceof ArrayNode) {
            return $this->_array($var, $indent + 1);
        }
        if ($var instanceof ClassNode || $var instanceof ReferenceNode) {
            return $this->_object($var, $indent + 1);
        }
        if ($var instanceof SpecialNode) {
            return (string)$var->getValue();
        }
        throw new RuntimeException('Unknown node received ' . get_class($var));
    }

    /**
     * Export an array type object
     *
     * @param \Cake\Error\DumpNode\ArrayNode $var The array to export.
     * @param int $context The current dump context.
     * @return string Exported array.
     */
    protected function _array(ArrayNode $var, int $indent): string
    {
        $out = '[';
        $break = "\n" . str_repeat("  ", $indent);
        $end = "\n" . str_repeat("  ", $indent - 1);
        $vars = [];

        foreach ($var->getChildren() as $item) {
            $val = $item->getValue();
            $vars[] = $break . $this->_export($item->getKey(), $indent) . ' => ' . $this->_export($val, $indent);
        }
        if (count($vars)) {
            return $out . implode(',', $vars) . $end . ']';
        }

        return $out . ']';
    }

    /**
     * Handles object to string conversion.
     *
     * @param ClassNode|ReferenceNode $var Object to convert.
     * @param int $indent Current indent level
     * @return string
     * @see \Cake\Error\Debugger::exportVar()
     */
    protected function _object($var, int $indent): string
    {
        $out = '';
        $props = [];

        if ($var instanceof ReferenceNode) {
            return "object({$var->getValue()}) id:{$var->getId()} {}";
        }

        /* @var \Cake\Error\DumpNode\ClassNode $var */
        $out .= "object({$var->getValue()}) id:{$var->getId()} {";
        $break = "\n" . str_repeat("  ", $indent);
        $end = "\n" . str_repeat("  ", $indent - 1) . '}';

        foreach ($var->getChildren() as $property) {
            $visibility = $property->getVisibility();
            $name = $property->getName();
            if ($visibility && $visibility !== 'public') {
                $props[] = "[{$visibility}] {$name} => " . $this->_export($property->getValue(), $indent);
            } else {
                $props[] = "{$name} => " . $this->_export($property->getValue(), $indent);
            }
        }
        if (count($props)) {
            return $out . $break . implode($break, $props) . $end;
        }

        return $out . '}';
    }
}
