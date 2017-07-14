<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Log;

use Cake\Core\InstanceConfigTrait;
use Cake\Log\Log;

/**
 * Slow Query logger
 */
class SlowQueryLogger extends QueryLogger
{

    use InstanceConfigTrait;

    /**
     * Default Config
     *
     * @var array
     */
    protected $_defaultConfig = [
        'filter' => null,
        'threshold' => 100
    ];

    /**
     * Constructor
     *
     * The $config array takes the following keys:
     *
     * - threshold: Threshold in milliseconds to log only query running slower than the given threshold. Default is 0.
     * - filter: A callable to filter based on the LoggedQuery object.
     *
     * @param array $config Config options.
     * @return void
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * Log queries
     *
     * @param \Cake\Database\Log\LoggedQuery $query The query being logged.
     * @return void
     */
    public function log(LoggedQuery $query)
    {
        if ($query->took < $this->getConfig('threshold')) {
            return;
        }

        $filter = $this->getConfig('filter');
        if (is_callable($filter)) {
            $result = $filter($query);
            if (!$result) {
                return;
            }
        }

        $this->_logSlow($query);
    }

    /**
     * Wrapper function for the logger object, useful for unit testing
     * or for overriding in subclasses.
     *
     * @param \Cake\Database\Log\LoggedQuery $query to be written in log
     * @return void
     */
    protected function _logSlow($query)
    {
        Log::write('debug', $query, ['slowQueriesLog']);
    }
}
