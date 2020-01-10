<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Log;

use Psr\Log\LogLevel;

/**
 * A trait providing an object short-cut method
 * to logging.
 */
trait LogTrait
{
    /**
     * Convenience method to write a message to Log. See Log::write()
     * for more information on writing to logs.
     *
     * @param mixed $message Log message.
     * @param int|string $level Error level.
     * @param string|array $context Additional log data relevant to this message.
     * @return bool Success of log write.
     */
    public function log($message, $level = LogLevel::ERROR, $context = [])
    {
        return Log::write($level, $message, $context);
    }
}
