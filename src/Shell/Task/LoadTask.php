<?php
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
namespace Cake\Shell\Task;

use Cake\Console\Shell;
use Cake\Filesystem\File;

/**
 * Task for loading plugins.
 */
class LoadTask extends Shell
{

    /**
     * Path to the bootstrap file.
     *
     * @var string
     */
    public $bootstrap;

    /**
     * Execution method always used for tasks.
     *
     * @param string|null $plugin The plugin name.
     * @return bool
     */
    public function main($plugin = null)
    {
        $filename = 'bootstrap';
        if ($this->params['cli']) {
            $filename .= '_cli';
        }

        $this->bootstrap = ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . $filename . '.php';

        if (!$plugin) {
            $this->err('You must provide a plugin name in CamelCase format.');
            $this->err('To load an "Example" plugin, run `cake plugin load Example`.');

            return false;
        }

        $options = $this->makeOptions();

        $app = APP . 'Application.php';
        if (file_exists($app) && !$this->param('no_app')) {
            $this->modifyApplication($app, $plugin, $options);

            return true;
        }

        return $this->_modifyBootstrap($plugin, $options);
    }

    /**
     * Create options string for the load call.
     *
     * @return string
     */
    protected function makeOptions()
    {
        $autoloadString = $this->param('autoload') ? "'autoload' => true" : '';
        $bootstrapString = $this->param('bootstrap') ? "'bootstrap' => true" : '';
        $routesString = $this->param('routes') ? "'routes' => true" : '';

        return implode(', ', array_filter([$autoloadString, $bootstrapString, $routesString]));
    }

    /**
     * Modify the application class
     *
     * @param string $app The Application file to modify.
     * @param string $plugin The plugin name to add.
     * @param string $options The plugin options to add
     * @return void
     */
    protected function modifyApplication($app, $plugin, $options)
    {
        $file = new File($app, false);
        $contents = $file->read();

        $append = "\n        \$this->addPlugin('%s', [%s]);\n";
        $insert = str_replace(', []', '', sprintf($append, $plugin, $options));

        if (!preg_match('/function bootstrap\(\)/m', $contents)) {
            $this->abort('Your Application class does not have a bootstrap() method. Please add one.');
        } else {
            $contents = preg_replace('/(function bootstrap\(\)(?:\s+)\{)/m', '$1' . $insert, $contents);
        }
        $file->write($contents);

        $this->out('');
        $this->out(sprintf('%s modified', $app));
    }

    /**
     * Update the applications bootstrap.php file.
     *
     * @param string $plugin Name of plugin.
     * @param string $options The options string
     * @return bool If modify passed.
     */
    protected function _modifyBootstrap($plugin, $options)
    {
        $bootstrap = new File($this->bootstrap, false);
        $contents = $bootstrap->read();
        if (!preg_match("@\n\s*Plugin::loadAll@", $contents)) {
            $append = "\nPlugin::load('%s', [%s]);\n";

            $bootstrap->append(str_replace(', []', '', sprintf($append, $plugin, $options)));
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
                    'help' => 'Will autoload the plugin using CakePHP.' .
                        'Set to true if you are not using composer to autoload your plugin.',
                    'boolean' => true,
                    'default' => false,
                ])
                ->addOption('cli', [
                    'help' => 'Use the bootstrap_cli file.',
                    'boolean' => true,
                    'default' => false,
                ])
                ->addOption('no_app', [
                    'help' => 'Do not update the Application if it exist. Forces config/bootstrap.php to be updated.',
                    'boolean' => true,
                    'default' => false,
                ])
                ->addArgument('plugin', [
                    'help' => 'Name of the plugin to load.',
                ]);

        return $parser;
    }
}
