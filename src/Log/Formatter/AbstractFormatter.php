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
 * @since         4.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Log\Formatter;

use Cake\Core\InstanceConfigTrait;

abstract class AbstractFormatter
{
    use InstanceConfigTrait;

    /**
     * Default config for this class
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
    ];

    /**
     * @param array<string, mixed> $config Config options
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * Formats message.
     *
     * @param mixed $level Logging level
     * @param string $message Message string
     * @param array $context Mesage context
     * @return string Formatted message
     */
    abstract public function format(mixed $level, string $message, array $context = []): string;
}
