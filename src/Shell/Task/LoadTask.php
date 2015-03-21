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
 * Task for loading plugins.
 *
 */
class LoadTask extends Shell
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
     * @return bool
     */
    public function main($plugin = null)
    {
        $this->bootstrap = ROOT . DS . 'config' . DS . 'bootstrap.php';

        if (empty($plugin)) {
            $this->err('<error>You must provide a plugin name in CamelCase format.</error>');
            $this->err('To load an "Example" plugin, run <info>`cake plugin load Example`</info>.');
            return false;
        }

        return $this->_modifyBootstrap(
            $plugin,
            $this->params['bootstrap'],
            $this->params['routes'],
            $this->params['autoload']
        );
    }

    /**
     * Update the applications bootstrap.php file.
     *
     * @param string $plugin Name of plugin.
     * @param bool $hasBootstrap Whether or not bootstrap should be loaded.
     * @param bool $hasRoutes Whether or not routes should be loaded.
     * @param bool $hasAutoloader Whether or not there is an autoloader configured for
     * the plugin.
     * @return bool If modify passed.
     */
    protected function _modifyBootstrap($plugin, $hasBootstrap, $hasRoutes, $hasAutoloader)
    {
        $bootstrap = new File($this->bootstrap, false);
        $contents = $bootstrap->read();
        if (!preg_match("@\n\s*Plugin::loadAll@", $contents)) {
            $autoloadString = $hasAutoloader ? "'autoload' => true" : '';
            $bootstrapString = $hasBootstrap ? "'bootstrap' => true" : '';
            $routesString = $hasRoutes ? "'routes' => true" : '';

            $append = "\nPlugin::load('%s', [%s]);\n";
            $options = implode(', ', array_filter([$autoloadString, $bootstrapString, $routesString]));

            $bootstrap->append(sprintf($append, $plugin, $options));
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

        $parser->addOption('bootstrap', [
                    'short' => 'b',
                    'help' => 'Will load bootstrap.php from plugin.',
                    'boolean' => true,
                    'default' => false,
                ])
                ->addOption('routes', [
                    'short' => 'r',
                    'help' => 'Will load routes.php from plugin.',
                    'boolean' => true,
                    'default' => false,
                ])
                ->addOption('autoload', [
                    'help' => 'Will autoload the plugin using CakePHP. ' .
                        'Set to true if you are not using composer to autoload your plugin.',
                    'boolean' => true,
                    'default' => false,
                ])
                ->addArgument('plugin', [
                    'help' => 'Name of the plugin to load.',
                ]);

        return $parser;
    }
}
