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
 * @author        Bob Mulder <bobmulder@outlook.com>
 */

namespace Cake\Shell;

use Cake\Console\Shell;

//use Cake\Core\Plugin;
//use Cake\Filesystem\Folder;
//use Cake\Utility\Inflector;

/**
 * Shell for symlinking / copying plugin assets to app's webroot.
 *
 */
class PluginShell extends Shell
{

    /**
     * Tasks to load
     *
     * @var type
     */
    public $tasks = [
        'Load',
        'Unload',
    ];

    /**
     * Gets the option parser instance and configures it.
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();
        $parser->addSubcommand('load', [
            'help'   => 'Loads a plugin',
            'parser' => $this->Load->getOptionParser(),
        ]);
        $parser->addSubcommand('unload', [
            'help'   => 'Unloads a plugin',
            'parser' => $this->Unload->getOptionParser(),
        ]);

        return $parser;
    }

}
