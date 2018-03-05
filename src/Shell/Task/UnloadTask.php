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
 * Task for unloading plugins.
 */
class UnloadTask extends Shell
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
     * @return bool if action passed.
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
            $this->err('To unload an "Example" plugin, run `cake plugin unload Example`.');

            return false;
        }

        $app = APP . 'Application.php';
        if (file_exists($app) && !$this->param('no_app')) {
            $this->modifyApplication($app, $plugin);

            return true;
        }

        return (bool)$this->_modifyBootstrap($plugin);
    }

    /**
     * Update the applications bootstrap.php file.
     *
     * @param string $app Path to the application to update.
     * @param string $plugin Name of plugin.
     * @return bool If modify passed.
     */
    protected function modifyApplication($app, $plugin)
    {
        $finder = "@\\\$this\-\>addPlugin\(\s*'$plugin'(.|.\n|)+\);+@";

        $content = file_get_contents($app);
        $newContent = preg_replace($finder, '', $content);

        if ($newContent === $content) {
            return false;
        }

        file_put_contents($app, $newContent);

        $this->out('');
        $this->out(sprintf('%s modified', $app));

        return true;
    }

    /**
     * Update the applications bootstrap.php file.
     *
     * @param string $plugin Name of plugin.
     * @return bool If modify passed.
     */
    protected function _modifyBootstrap($plugin)
    {
        $finder = "@\nPlugin::load\((.|.\n|\n\s\s|\n\t|)+'$plugin'(.|.\n|)+\);\n@";

        $bootstrap = new File($this->bootstrap, false);
        $content = $bootstrap->read();

        if (!preg_match("@\n\s*Plugin::loadAll@", $content)) {
            $newContent = preg_replace($finder, '', $content);

            if ($newContent === $content) {
                return false;
            }

            $bootstrap->write($newContent);

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

        $parser->addOption('cli', [
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
