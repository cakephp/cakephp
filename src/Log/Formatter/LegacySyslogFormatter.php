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

/**
 * @deprecated 4.3.0 Create a custom formatter and set it with `formatter` config instead.
 */
class LegacySyslogFormatter extends AbstractFormatter
{
    /**
     * Default config for this class
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [
        'format' => '%s: %s',
    ];

    /**
     * @param array<string, mixed> $config Formatter config
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * @inheritDoc
     */
    public function format($level, string $message, array $context = []): string
    {
        return sprintf($this->getConfig('format'), $level, $message);
    }
}
