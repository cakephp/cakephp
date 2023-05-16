<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.5.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\TestSuite;

use Cake\Console\ConsoleIo;
use Cake\Console\Shell;
use Cake\Console\ShellDispatcher;
use function Cake\Core\pluginSplit;

/**
 * Allows injecting mock IO into shells
 */
class LegacyShellDispatcher extends ShellDispatcher
{
    /**
     * @var \Cake\Console\ConsoleIo
     */
    protected $_io;

    /**
     * Constructor
     *
     * @param array $args Argument array
     * @param bool $bootstrap Initialize environment
     * @param \Cake\Console\ConsoleIo|null $io ConsoleIo
     */
    public function __construct(array $args = [], bool $bootstrap = true, ?ConsoleIo $io = null)
    {
        /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
        $this->_io = $io;
        parent::__construct($args, $bootstrap);
    }

    /**
     * Injects mock and stub io components into the shell
     *
     * @param string $className Class name
     * @param string $shortName Short name
     * @return \Cake\Console\Shell
     */
    protected function _createShell(string $className, string $shortName): Shell
    {
        [$plugin] = pluginSplit($shortName);
        /** @var \Cake\Console\Shell $instance */
        $instance = new $className($this->_io);
        if ($plugin) {
            $instance->plugin = trim($plugin, '.');
        }

        return $instance;
    }
}

// phpcs:disable
class_alias(
    'Cake\Console\TestSuite\LegacyShellDispatcher',
    'Cake\TestSuite\LegacyShellDispatcher'
);
// phpcs:enable
