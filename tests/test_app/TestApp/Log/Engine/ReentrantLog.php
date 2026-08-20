<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Log\Engine;

use Cake\Log\Engine\BaseLog;
use Cake\Log\Log;
use Stringable;

/**
 * Writes a log message from inside its own log(), the way a table-backed logger does when the
 * insert it performs is picked up by the query logger.
 */
class ReentrantLog extends BaseLog
{
    /**
     * Messages this logger was handed.
     *
     * @var array<string>
     */
    public array $messages = [];

    /**
     * Guards the stub against running away if the reentrancy check ever stops working.
     *
     * @var int
     */
    public int $limit = 20;

    /**
     * @param mixed $level
     * @param \Stringable|string $message
     * @param array $context
     * @return void
     */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->messages[] = (string)$message;

        if (count($this->messages) >= $this->limit) {
            return;
        }

        Log::write('debug', 'INSERT INTO logs ...', [
            'scope' => ['queriesLog', 'cake.database.queries'],
        ]);
    }
}
