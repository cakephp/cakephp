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
use Cake\Core\App;
use Cake\Filesystem\File;

/**
 * Task for loading plugins.
 *
 */
class LoadTask extends Shell
{

    public $path = null;
    public $bootstrap = null;

    /**
     * Execution method always used for tasks
     *
     * @return boolean if action passed
     *
     */
    public function main($plugin = null)
    {

        $this->path = current(App::path('Plugin'));
        $this->bootstrap = ROOT . DS . 'config' . DS . 'bootstrap.php';

        if (empty($plugin)) {
            $this->err('<error>You must provide a plugin name in CamelCase format.</error>');
            $this->err('To load an "Example" plugin, run <info>`cake plugin load Example`</info>.');
            return false;
        }


        $write = $this->_modifyBootstrap($plugin, $this->params['bootstrap'], $this->params['routes'], false);

        if ($write) {
            return true;
        }

        return false;
    }

    /**
     * Update the applications bootstrap.php file.
     *
     * @param string $plugin Name of plugin
     * @param bool $hasAutoloader Whether or not there is an autoloader configured for
     * the plugin
     * @return void
     */
    protected function _modifyBootstrap($plugin, $hasBootstrap, $hasRoutes, $hasAutoloader)
    {
        $bootstrap = new File($this->bootstrap, false);
        $contents = $bootstrap->read();
        if (!preg_match("@\n\s*Plugin::loadAll@", $contents)) {
            $autoload = $hasAutoloader ? null : "'autoload' => true, ";
            $bootstrap->append(sprintf(
                "\nPlugin::load('%s', [%s'bootstrap' => " . ($hasBootstrap ? 'true' : 'false') . ", 'routes' => " . ($hasRoutes ? 'true' : 'false') . "]);\n",
                $plugin,
                $autoload
            ));
            $this->out('');
            $this->out(sprintf('%s modified', $this->bootstrap));
            return true;
        }
        return false;
    }

    /**
     * GetOptionParser method.
     *
     * @return type
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();

        $parser->addOption('bootstrap', [
            'short'   => 'b',
            'help'    => 'Will load bootstrap.php from plugin.',
            'boolean' => true,
            'default' => false,
        ]);

        $parser->addOption('routes', [
            'short'   => 'r',
            'help'    => 'Will load routes.php from plugin.',
            'boolean' => true,
            'default' => false,
        ]);

        $parser->addArgument('plugin', [
            'help' => 'Name of the plugin to load.',
        ]);

        return $parser;
    }
}
