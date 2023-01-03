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
namespace Cake\Console\TestSuite;

use RuntimeException;

/**
 * Exception class used to indicate missing console input.
 */
class MissingConsoleInputException extends RuntimeException
{
    /**
     * Update the exception message with the question text
     *
     * @param string $question The question text.
     * @return void
     */
    public function setQuestion(string $question): void
    {
        $this->message .= "\nThe question asked was: " . $question;
    }
}

// phpcs:disable
class_alias(
    'Cake\Console\TestSuite\MissingConsoleInputException',
    'Cake\TestSuite\Stub\MissingConsoleInputException'
);
// phpcs:enable
