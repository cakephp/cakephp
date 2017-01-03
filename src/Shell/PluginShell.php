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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Shell;

use Cake\Console\Shell;
use Cake\Core\Plugin;

/**
 * Shell for tasks related to plugins.
 */
class PluginShell extends Shell
{

    /**
     * Tasks to load
     *
     * @var array
     */
    public $tasks = [
        'Assets',
        'Load',
        'Unload',
    ];

    /**
     * Displays all currently loaded plugins.
     *
     * @return void
     */
    public function loaded()
    {
        $loaded = Plugin::loaded();
        $this->out($loaded);
    }

    /**
     * Gets the option parser instance and configures it.
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();

        $parser->setDescription('Plugin Shell perform various tasks related to plugin.')
            ->addSubcommand('assets', [
                'help' => 'Symlink / copy plugin assets to app\'s webroot',
                'parser' => $this->Assets->getOptionParser()
            ])
            ->addSubcommand('loaded', [
                'help' => 'Lists all loaded plugins',
                'parser' => $parser,
            ])
            ->addSubcommand('load', [
                'help' => 'Loads a plugin',
                'parser' => $this->Load->getOptionParser(),
            ])
            ->addSubcommand('unload', [
                'help' => 'Unloads a plugin',
                'parser' => $this->Unload->getOptionParser(),
            ]);

        return $parser;
    }
}
