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
 * @link          https://cakephp.org CakePHP Project
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Command\Helper;

use BackedEnum;
use Cake\Console\Helper;
use Cake\Database\Type\EnumLabelInterface;
use Closure;
use UnitEnum;

/**
 * Tree command helper.
 *
 * Formats nested arrays into a tree with dashes and pipes.
 */
class TreeHelper extends Helper
{
    /**
     * @inheritDoc
     */
    protected array $_defaultConfig = [
        'baseIndent' => 0,
        'elementIndent' => 0,
    ];

    /**
     * Outputs an array in tree form.
     *
     * @param array $args Tree array
     * @return void
     */
    public function output(array $args): void
    {
        $prefix = str_repeat(' ', $this->_config['baseIndent']);
        $this->outputArray($args, $prefix, topLevel: true);
    }

    /**
     * Output an array in a tree.
     *
     * @param array $array
     * @param string $prefix
     * @param bool $topLevel
     * @return void
     */
    protected function outputArray(array $array, string $prefix, bool $topLevel): void
    {
        $i = 1;
        $numValues = count($array);
        $elementPrefix = $topLevel ? '' : str_repeat(' ', $this->_config['elementIndent']);
        foreach ($array as $key => $value) {
            $isLast = $i++ === $numValues;
            $marker = $isLast ? '└── ' : '├── ';
            $indent = $isLast ? '    ' : '│   ';
            $this->outputElement($key, $value, $prefix . $elementPrefix, $marker, $indent);
        }
    }

    /**
     * Output an array element.
     *
     * @param string|int $key
     * @param mixed $value
     * @param string $prefix
     * @param string $marker
     * @param string $indent
     * @return void
     */
    protected function outputElement(
        string|int $key,
        mixed $value,
        string $prefix,
        string $marker,
        string $indent,
    ): void {
        if (is_array($value)) {
            $this->_io->out($prefix . $marker . $key);
            $this->outputArray($value, $prefix . $indent, topLevel: false);
        } elseif (is_string($key)) {
            $this->_io->out($prefix . $marker . $key);
            $this->outputValue($value, $prefix . $indent . '└── ');
        } else {
            $this->outputValue($value, $prefix . $marker);
        }
    }

    /**
     * Output a value in a tree.
     *
     * @param mixed $value
     * @param string $prefix
     * @return void
     */
    protected function outputValue(mixed $value, string $prefix): void
    {
        if ($value instanceof Closure) {
            $this->_io->out($prefix . $value());
        } elseif ($value instanceof EnumLabelInterface) {
            $this->_io->out($prefix . $value->label());
        } elseif ($value instanceof BackedEnum) {
            $this->_io->out($prefix . $value->value);
        } elseif ($value instanceof UnitEnum) {
            $this->_io->out($prefix . $value->name);
        } elseif (is_bool($value)) {
            $this->_io->out($prefix . ($value ? 'true' : 'false'));
        } else {
            $this->_io->out($prefix . $value);
        }
    }
}
