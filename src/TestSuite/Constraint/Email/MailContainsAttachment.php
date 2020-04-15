<?php

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.9.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Constraint\Email;

/**
 * MailContainsAttachment
 *
 * @internal
 */
class MailContainsAttachment extends MailContains
{
    /**
     * Checks constraint
     *
     * @param mixed $other Constraint check
     * @return bool
     */
    public function matches($other)
    {
        list($expectedFilename, $expectedFileInfo) = $other;

        $messages = $this->getEmails();
        foreach ($messages as $message) {
            foreach ($message->getAttachments() as $filename => $fileInfo) {
                if ($filename === $expectedFilename && empty($expectedFileInfo)) {
                    return true;
                }
                if (!empty($expectedFileInfo) && array_intersect($expectedFileInfo, $fileInfo) === $expectedFileInfo) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Assertion message string
     *
     * @return string
     */
    public function toString()
    {
        if ($this->at) {
            return sprintf('is an attachment of email #%d', $this->at);
        }

        return 'is an attachment of an email';
    }

    /**
     * Overwrites the descriptions so we can remove the automatic "expected" message
     *
     * @param mixed $other Value
     * @return string
     */
    protected function failureDescription($other)
    {
        list($expectedFilename) = $other;

        return '\'' . $expectedFilename . '\' ' . $this->toString();
    }
}
