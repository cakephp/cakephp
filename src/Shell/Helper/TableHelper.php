<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Shell\Helper;

use Cake\Console\Helper;

/**
 * Create a visually pleasing ASCII art table
 * from 2 dimensional array data.
 */
class TableHelper extends Helper
{

    /**
     * Default config for this helper.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'headers' => true,
        'rowSeparator' => false,
        'headerStyle' => 'info',
    ];

    /**
     * Calculate the column widths
     *
     * @param array $rows The rows on which the columns width will be calculated on.
     * @return array
     */
    protected function _calculateWidths($rows)
    {
        $widths = [];
        foreach ($rows as $line) {
            foreach (array_values($line) as $k => $v) {
                $columnLength = $this->_cellWidth($v);
                if ($columnLength >= (isset($widths[$k]) ? $widths[$k] : 0)) {
                    $widths[$k] = $columnLength;
                }
            }
        }

        return $widths;
    }

    /**
     * Get the width of a cell exclusive of style tags.
     *
     * @param string $text The text to calculate a width for.
     * @return int The width of the textual content in visible characters.
     */
    protected function _cellWidth($text)
    {
        if (strpos($text, '<') === false && strpos($text, '>') === false) {
            return mb_strwidth($text);
        }

        /** @var array $styles */
        $styles = $this->_io->styles();
        $tags = implode('|', array_keys($styles));
        $text = preg_replace('#</?(?:' . $tags . ')>#', '', $text);

        return mb_strwidth($text);
    }

    /**
     * Output a row separator.
     *
     * @param array $widths The widths of each column to output.
     * @return void
     */
    protected function _rowSeparator($widths)
    {
        $out = '';
        foreach ($widths as $column) {
            $out .= '+' . str_repeat('-', $column + 2);
        }
        $out .= '+';
        $this->_io->out($out);
    }

    /**
     * Output a row.
     *
     * @param array $row The row to output.
     * @param array $widths The widths of each column to output.
     * @param array $options Options to be passed.
     * @return void
     */
    protected function _render(array $row, $widths, $options = [])
    {
        if (count($row) === 0) {
            return;
        }

        $out = '';
        foreach (array_values($row) as $i => $column) {
            $pad = $widths[$i] - $this->_cellWidth($column);
            if (!empty($options['style'])) {
                $column = $this->_addStyle($column, $options['style']);
            }
            $out .= '| ' . $column . str_repeat(' ', $pad) . ' ';
        }
        $out .= '|';
        $this->_io->out($out);
    }

    /**
     * Output a table.
     *
     * Data will be output based on the order of the values
     * in the array. The keys will not be used to align data.
     *
     * @param array $rows The data to render out.
     * @return void
     */
    public function output($rows)
    {
        if (!is_array($rows) || count($rows) === 0) {
            return;
        }

        $config = $this->getConfig();
        $widths = $this->_calculateWidths($rows);

        $this->_rowSeparator($widths);
        if ($config['headers'] === true) {
            $this->_render(array_shift($rows), $widths, ['style' => $config['headerStyle']]);
            $this->_rowSeparator($widths);
        }

        if (!$rows) {
            return;
        }

        foreach ($rows as $line) {
            $this->_render($line, $widths);
            if ($config['rowSeparator'] === true) {
                $this->_rowSeparator($widths);
            }
        }
        if ($config['rowSeparator'] !== true) {
            $this->_rowSeparator($widths);
        }
    }

    /**
     * Add style tags
     *
     * @param string $text The text to be surrounded
     * @param string $style The style to be applied
     * @return string
     */
    protected function _addStyle($text, $style)
    {
        return '<' . $style . '>' . $text . '</' . $style . '>';
    }
}
