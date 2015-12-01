<?php
App::uses("ShellHelper", "Console/Helper");

class TableShellHelper extends ShellHelper
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
        $this->_consoleOutput->write($out);
    }

    /**
     * Output a row.
     *
     * @param array $row The row to output.
     * @param array $widths The widths of each column to output.
     * @param array $options Options to be passed.
     * @return void
     */
    protected function _render($row, $widths, $options = [])
    {
        $out = '';
        foreach ($row as $i => $column) {
            $pad = $widths[$i] - mb_strlen($column);
            if (!empty($options['style'])) {
                $column = $this->_addStyle($column, $options['style']);
            }
            $out .= '| ' . $column . str_repeat(' ', $pad) . ' ';
        }
        $out .= '|';
        $this->_consoleOutput->write($out);
    }

    /**
     * Output a table.
     *
     * @param array $rows The data to render out.
     * @return void
     */
    public function output($rows)
    {
        $config = $this->config();
        $widths = $this->_calculateWidths($rows);
        $this->_rowSeparator($widths);
        if ($config['headers'] === true) {
            $this->_render(array_shift($rows), $widths, ['style' => $config['headerStyle']]);
            $this->_rowSeparator($widths);
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