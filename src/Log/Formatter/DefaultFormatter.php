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

use DateTime;

class DefaultFormatter extends AbstractFormatter
{
    /**
     * Default config for this class
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'dateFormat' => 'Y-m-d H:i:s',
        'includeTags' => false,
        'includeDate' => true,
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
        if ($this->_config['includeDate']) {
            $message = sprintf('%s %s: %s', (new DateTime())->format($this->_config['dateFormat']), $level, $message);
        } else {
            $message = sprintf('%s: %s', $level, $message);
        }
        if ($this->_config['includeTags']) {
            return sprintf('<%s>%s</%s>', $level, $message, $level);
        }

        return $message;
    }
}
