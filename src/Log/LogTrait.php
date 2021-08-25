<?php
declare(strict_types=1);

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
use Stringable;

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
     * @param \Stringable|string $message Log message.
     * @param string|int $level Error level.
     * @param array|string $context Additional log data relevant to this message.
     * @return bool Success of log write.
     */
    public function log(
        Stringable|string $message,
        string|int $level = LogLevel::ERROR,
        array|string $context = []
    ): bool {
        return Log::write($level, $message, $context);
    }
}
