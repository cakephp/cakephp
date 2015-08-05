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
namespace Cake\Shell\Task;

use Cake\Console\Shell;
use Cake\Filesystem\File;

/**
 * Task for unloading plugins.
 *
 */
class UnloadTask extends Shell
{
    /**
     * Path to the bootstrap file.
     *
     * @var string
     */
    public $bootstrap = null;

    /**
     * Execution method always used for tasks.
     *
     * @param string $plugin The plugin name.
     * @return bool if action passed.
     */
    public function main($plugin = null)
    {
        $this->bootstrap = ROOT . DS . 'config' . DS . 'bootstrap.php';

        if (empty($plugin)) {
            $this->err('<error>You must provide a plugin name in CamelCase format.</error>');
            $this->err('To unload an "Example" plugin, run <info>`cake plugin unload Example`</info>.');
            return false;
        }

        return (bool)$this->_modifyBootstrap($plugin);
    }

    /**
     * Update the applications bootstrap.php file.
     *
     * @param string $plugin Name of plugin.
     * @return bool If modify passed.
     */
    protected function _modifyBootstrap($plugin)
    {
        $finder = "/\nPlugin::load\((.|.\n|\n\s\s|\n\t|)+'$plugin'(.|.\n|)+\);\n/";

        $bootstrap = new File($this->bootstrap, false);
        $contents = $bootstrap->read();

        if (!preg_match("@\n\s*Plugin::loadAll@", $contents)) {
            $contents = preg_replace($finder, "", $contents);

            $bootstrap->write($contents);

            $this->out('');
            $this->out(sprintf('%s modified', $this->bootstrap));

            return true;
        }
        return false;
    }

    /**
     * GetOptionParser method.
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();

        $parser->addArgument('plugin', [
            'help' => 'Name of the plugin to load.',
        ]);

        return $parser;
    }
}
