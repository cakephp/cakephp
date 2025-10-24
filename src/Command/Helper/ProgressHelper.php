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
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Command\Helper;

use Cake\Console\Helper;
use InvalidArgumentException;

/**
 * Create a progress bar using a supplied callback.
 *
 * ## Usage
 *
 * The ProgressHelper can be accessed from shells using the helper() method
 *
 * ```
 * $this->helper('Progress')->output(['callback' => function ($progress) {
 *     // Do work
 *     $progress->increment();
 * }]);
 * ```
 */
class ProgressHelper extends Helper
{
    /**
     * Default value for progress bar total value.
     * Percent completion is derived from progress/total
     */
    protected const DEFAULT_TOTAL = 100;

    /**
     * Default value for progress bar width
     */
    protected const DEFAULT_WIDTH = 80;

    /**
     * The current progress.
     *
     * @var float|int
     */
    protected float|int $_progress = 0;

    /**
     * The total number of 'items' to progress through.
     *
     * @var int
     */
    protected int $_total = self::DEFAULT_TOTAL;

    /**
     * The width of the bar.
     *
     * @var int
     */
    protected int $_width = self::DEFAULT_WIDTH;

    /**
     * Output a progress bar.
     *
     * Takes a number of options to customize the behavior:
     *
     * - `total` The total number of items in the progress bar. Defaults
     *   to 100.
     * - `width` The width of the progress bar. Defaults to 80.
     * - `callback` The callback that will be called in a loop to advance the progress bar.
     *
     * @param array $args The arguments/options to use when outputting the progress bar.
     * @return void
     */
    public function output(array $args): void
    {
        $args += ['callback' => null];
        if (isset($args[0])) {
            $args['callback'] = $args[0];
        }
        if (!$args['callback'] || !is_callable($args['callback'])) {
            throw new InvalidArgumentException('Callback option must be a callable.');
        }
        $this->init($args);

        $callback = $args['callback'];

        $this->_io->out('', 0);
        while ($this->_progress < $this->_total) {
            $callback($this);
            $this->draw();
        }
        $this->_io->out('');
    }

    /**
     * Initialize the progress bar for use.
     *
     * - `total` The total number of items in the progress bar. Defaults
     *   to 100.
     * - `width` The width of the progress bar. Defaults to 80.
     *
     * @param array<string, mixed> $args The initialization data.
     * @return $this
     */
    public function init(array $args = [])
    {
        $args += ['total' => self::DEFAULT_TOTAL, 'width' => self::DEFAULT_WIDTH];
        $this->_progress = 0;
        $this->_width = $args['width'];
        $this->_total = $args['total'];

        return $this;
    }

    /**
     * Increment the progress bar.
     *
     * @param float|int $num The amount of progress to advance by.
     * @return $this
     */
    public function increment(float|int $num = 1)
    {
        $this->_progress = min(max(0, $this->_progress + $num), $this->_total);

        return $this;
    }

    /**
     * Render the progress bar based on the current state.
     *
     * @return $this
     */
    public function draw()
    {
        $numberLen = strlen(' 100%');
        $complete = round($this->_progress / $this->_total, 2);
        $barLen = ($this->_width - $numberLen) * $this->_progress / $this->_total;
        $bar = '';
        if ($barLen > 1) {
            $bar = str_repeat('=', (int)$barLen - 1) . '>';
        }

        $pad = ceil($this->_width - $numberLen - $barLen);
        if ($pad > 0) {
            $bar .= str_repeat(' ', (int)$pad);
        }
        $percent = ($complete * 100) . '%';
        $bar .= str_pad($percent, $numberLen, ' ', STR_PAD_LEFT);

        $this->_io->overwrite($bar, 0);

        return $this;
    }
}
