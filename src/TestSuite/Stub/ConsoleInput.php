<?php
declare(strict_types=1);

/**
 * CakePHP :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Stub;

use Cake\Console\ConsoleInput as ConsoleInputBase;
use NumberFormatter;

/**
 * Stub class used by the console integration harness.
 *
 * This class enables input to be stubbed and have exceptions
 * raised when no answer is available.
 */
class ConsoleInput extends ConsoleInputBase
{
    /**
     * Reply values for ask() and askChoice()
     *
     * @var array<string>
     */
    protected $replies = [];

    /**
     * Current message index
     *
     * @var int
     */
    protected $currentIndex = -1;

    /**
     * Constructor
     *
     * @param array<string> $replies A list of replies for read()
     */
    public function __construct(array $replies)
    {
        parent::__construct();

        $this->replies = $replies;
    }

    /**
     * Read a reply
     *
     * @return string The value of the reply
     */
    public function read(): string
    {
        $this->currentIndex += 1;

        if (!isset($this->replies[$this->currentIndex])) {
            $total = count($this->replies);
            $formatter = new NumberFormatter('en', NumberFormatter::ORDINAL);
            $nth = $formatter->format($this->currentIndex + 1);

            $replies = implode(', ', $this->replies);
            $message = "There are no more input replies available. This is the {$nth} read operation, " .
                "only {$total} replies were set.\nThe provided replies are: {$replies}";
            throw new MissingConsoleInputException($message);
        }

        return $this->replies[$this->currentIndex];
    }

    /**
     * Check if data is available on stdin
     *
     * @param int $timeout An optional time to wait for data
     * @return bool True for data available, false otherwise
     */
    public function dataAvailable($timeout = 0): bool
    {
        return true;
    }
}
