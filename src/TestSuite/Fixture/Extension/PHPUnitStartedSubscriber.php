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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Fixture\Extension;

use Cake\Log\Log;
use Cake\TestSuite\ConnectionHelper;
use PHPUnit\Event\TestSuite\Started;
use PHPUnit\Event\TestSuite\StartedSubscriber as PHPUnitStarted;
use function Cake\Core\env;

class PHPUnitStartedSubscriber implements PHPUnitStarted
{
    /**
     * Initializes before any tests are run.
     *
     * @param \PHPUnit\Event\TestSuite\Started $event The event
     * @return void
     */
    public function notify(Started $event): void
    {
        $helper = new ConnectionHelper();
        $helper->addTestAliases();

        $enableLogging = env('LOG_QUERIES', false);
        if ((int)$enableLogging !== 0) {
            $helper->enableQueryLogging();
            Log::drop('queries');
            Log::setConfig('queries', [
                'className' => 'Console',
                'stream' => 'php://stderr',
                'scopes' => ['cake.database.queries'],
            ]);
        }
    }
}
