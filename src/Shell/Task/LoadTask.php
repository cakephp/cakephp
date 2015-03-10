<?php

namespace Cake\Shell\Task;

use Cake\Console\Shell;
use Cake\Core\App;
use Cake\Filesystem\File;

class LoadTask extends Shell
{

    public $path = null;
    public $bootstrap = null;

    /**
     * main() method.
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

        if($write) {
            return true;
        }

        return false;
    }

    /**
     * Update the app's bootstrap.php file.
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
                            "\nPlugin::load('%s', [%s'bootstrap' => " . ($hasBootstrap ? 'true' : 'false') . ", 'routes' => " . ($hasRoutes ? 'true' : 'false') . "]);\n", $plugin, $autoload
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
