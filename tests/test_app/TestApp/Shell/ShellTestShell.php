<?php
declare(strict_types=1);

/**
 * ShellTestShell file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.8
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace TestApp\Shell;

use Cake\Console\Shell;

/**
 * ShellTestShell class
 */
class ShellTestShell extends Shell
{
    /**
     * name property
     *
     * @var string
     */
    public $name = 'ShellTestShell';

    /**
     * modelClass
     *
     * @var string
     */
    protected $modelClass = 'TestPlugin.TestPluginComments';

    /**
     * stopped property
     *
     * @var int
     */
    public $stopped;

    /**
     * testMessage property
     *
     * @var string
     */
    public $testMessage = 'all your base are belong to us';

    /**
     * @inheritDoc
     */
    protected function _stop(int $status = Shell::CODE_SUCCESS): void
    {
        $this->stopped = $status;
    }

    protected function _secret(): void
    {
    }

    // phpcs:disable
    public function doSomething()
    {
    }

    protected function noAccess()
    {
    }

    public function logSomething()
    {
        $this->log($this->testMessage);
    }
    // phpcs:enable
}
