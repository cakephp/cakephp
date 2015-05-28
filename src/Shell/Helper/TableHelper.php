<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         3.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
     * Calculate the column widths
     *
     * @param array $rows The rows on which the columns width will be calculated on.
     * @return array
     */
    protected function _calculateWidths($rows)
    {
        $widths = [];
        foreach ($rows as $line) {
            for ($i = 0, $len = count($line); $i < $len; $i++) {
                $columnLength = mb_strlen($line[$i]);
                if ($columnLength > (isset($widths[$i]) ? $widths[$i] : 0)) {
                    $widths[$i] = $columnLength;
                }
            }
        }
        return $widths;
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
     * @param array $row The row to ouptut.
     * @param array $widths The widths of each column to output.
     * @return void
     */
    protected function _render($row, $widths)
    {
        $out = '';
        foreach ($row as $i => $column) {
            $pad = $widths[$i] - mb_strlen($column);
            $out .= '| ' . $column . str_repeat(' ', $pad) . ' ';
        }
        $out .= '|';
        $this->_io->out($out);
    }

    /**
     * Output a table.
     *
     * @param array $rows The data to render out.
     * @return void
     */
    public function output($rows)
    {
        $widths = $this->_calculateWidths($rows);

        $this->_rowSeparator($widths);
        $this->_render(array_shift($rows), $widths);
        $this->_rowSeparator($widths);

        foreach ($rows as $line) {
            $this->_render($line, $widths);
        }
        $this->_rowSeparator($widths);
    }
}
