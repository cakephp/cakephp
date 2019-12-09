<?php
declare(strict_types=1);

/**
 * CakePHP(tm) :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakefoundation.org CakePHP(tm) Project
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Log\Engine;

/**
 * Array logger.
 *
 * Collects log messages in memory. Intended primarily for usage
 * in testing where using mocks would be complicated. But can also
 * be used in scenarios where you need to capture logs in application code.
 */
class ArrayLog extends BaseLog
{
    /**
     * Captured messages
     *
     * @var array
     */
    protected $content = [];

    /**
     * Implements writing to the internal storage.
     *
     * @param mixed $level The severity level of log you are making.
     * @param string $message The message you want to log.
     * @param array $context Additional information about the logged message
     * @return void success of write.
     * @see Cake\Log\Log::$_levels
     */
    public function log($level, $message, array $context = [])
    {
        $this->content[] = $level . ' ' . $this->_format($message, $context);
    }

    /**
     * Read the internal storage
     *
     * @return string[]
     */
    public function read(): array
    {
        return $this->content;
    }

    /**
     * Reset internal storage.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->content = [];
    }
}
