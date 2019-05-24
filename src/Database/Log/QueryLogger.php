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
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Log;

use Cake\Log\Engine\BaseLog;
use Cake\Log\Log;

/**
 * This class is a bridge used to write LoggedQuery objects into a real log.
 * by default this class use the built-in CakePHP Log class to accomplish this
 *
 * @internal
 */
class QueryLogger extends BaseLog
{
    /**
     * Constructor.
     *
     * @param array $config Configuration array
     */
    public function __construct(array $config = [])
    {
        $this->_defaultConfig['scopes'] = ['queriesLog'];

        parent::__construct($config);
    }

    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = [])
    {
        Log::write(
            'debug',
            (string)$context['query'],
            ['scope' => $this->scopes() ?: ['queriesLog']]
        );
    }
}
