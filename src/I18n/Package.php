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
 * @copyright     Copyright (c) 2017 Aura for PHP
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n;

/**
 * Message Catalog
 */
class Package
{
    /**
     * Constructor.
     *
     * @param string $formatter The name of the formatter to use when formatting translated messages.
     * @param string|null $fallback The name of a fallback package to use when a message key does not
     *   exist.
     * @param array<array|string> $messages The messages in this package.
     */
    public function __construct(
        protected string $formatter = 'default',
        protected ?string $fallback = null,
        protected array $messages = []
    ) {
    }

    /**
     * Sets the messages for this package.
     *
     * @param array<array|string> $messages The messages for this package.
     */
    public function setMessages(array $messages): void
    {
        $this->messages = $messages;
    }

    /**
     * Adds one message for this package.
     *
     * @param string $key the key of the message
     * @param array|string $message the actual message
     */
    public function addMessage(string $key, array|string $message): void
    {
        $this->messages[$key] = $message;
    }

    /**
     * Adds new messages for this package.
     *
     * @param array<array|string> $messages The messages to add in this package.
     */
    public function addMessages(array $messages): void
    {
        $this->messages = array_merge($this->messages, $messages);
    }

    /**
     * Gets the messages for this package.
     *
     * @return array<array|string>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Gets the message of the given key for this package.
     *
     * @param string $key the key of the message to return
     * @return array|string|false The message translation, or false if not found.
     */
    public function getMessage(string $key): array|string|false
    {
        return $this->messages[$key] ?? false;
    }

    /**
     * Sets the formatter name for this package.
     *
     * @param string $formatter The formatter name for this package.
     */
    public function setFormatter(string $formatter): void
    {
        $this->formatter = $formatter;
    }

    /**
     * Gets the formatter name for this package.
     */
    public function getFormatter(): string
    {
        return $this->formatter;
    }

    /**
     * Sets the fallback package name.
     *
     * @param string|null $fallback The fallback package name.
     */
    public function setFallback(?string $fallback): void
    {
        $this->fallback = $fallback;
    }

    /**
     * Gets the fallback package name.
     */
    public function getFallback(): ?string
    {
        return $this->fallback;
    }
}
