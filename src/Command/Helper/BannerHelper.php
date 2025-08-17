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
 * @since         5.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Command\Helper;

use Cake\Console\Helper;
use InvalidArgumentException;

/**
 * Banner command helper.
 *
 * Formats one or more lines of text into a large banner with
 * padding and a blank line below.
 */
class BannerHelper extends Helper
{
    /**
     * @var int The horizontal padding that is added to the longest line.
     */
    private int $padding = 2;

    /**
     * @var string The console output style to use on the banner.
     */
    private string $style = 'success.bg';

    /**
     * Modify the padding of the helper
     *
     * @param int $padding The padding value to use.
     * @return $this
     */
    public function withPadding(int $padding)
    {
        if ($padding < 0) {
            throw new InvalidArgumentException('padding must be greater than 0');
        }
        $this->padding = $padding;

        return $this;
    }

    /**
     * Modify the padding of the helper
     *
     * @param string $style The style value to use.
     * @return $this
     */
    public function withStyle(string $style)
    {
        $this->style = $style;

        return $this;
    }

    /**
     * Output a banner
     *
     * @param array $args The messages to output
     * @return void
     */
    public function output(array $args): void
    {
        if ($args === []) {
            throw new InvalidArgumentException('At least one argument is required');
        }

        $lengths = array_map(fn($i) => mb_strlen($i), $args);
        $maxLength = max($lengths);
        $bannerLength = $maxLength + $this->padding * 2;
        $start = "<{$this->style}>";
        $end = "</{$this->style}>";

        $lines = [
            '',
            $start . str_repeat(' ', $bannerLength) . $end,
        ];
        foreach ($args as $line) {
            $lineLength = mb_strlen($line);
            $linePadding = (int)max($this->padding, $bannerLength - $lineLength - $this->padding);

            $lines[] = $start .
                str_repeat(' ', $this->padding) .
                $line .
                str_repeat(' ', $linePadding) .
                $end;
        }

        $lines[] = $start . str_repeat(' ', $bannerLength) . $end;
        $lines[] = '';

        $this->_io->out($lines);
    }
}
