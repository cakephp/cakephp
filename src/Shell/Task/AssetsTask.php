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
use Cake\Core\Plugin;
use Cake\Filesystem\Folder;
use Cake\Utility\Inflector;

/**
 * Task for symlinking / copying plugin assets to app's webroot.
 */
class AssetsTask extends Shell
{

    /**
     * Attempt to symlink plugin assets to app's webroot. If symlinking fails it
     * fallbacks to copying the assets. For vendor namespaced plugin, parent folder
     * for vendor name are created if required.
     *
     * @param string|null $name Name of plugin for which to symlink assets.
     *   If null all plugins will be processed.
     * @return void
     */
    public function symlink($name = null)
    {
        $this->_process($this->_list($name));
    }

    /**
     * Copying plugin assets to app's webroot. For vendor namespaced plugin,
     * parent folder for vendor name are created if required.
     *
     * @param string|null $name Name of plugin for which to symlink assets.
     *   If null all plugins will be processed.
     * @return void
     */
    public function copy($name = null)
    {
        $this->_process($this->_list($name), true);
    }

    /**
     * Remove plugin assets from app's webroot.
     *
     * @param string|null $name Name of plugin for which to remove assets.
     *   If null all plugins will be processed.
     * @return void
     * @since 3.5.12
     */
    public function remove($name = null)
    {
        $plugins = $this->_list($name);

        foreach ($plugins as $plugin => $config) {
            $this->out();
            $this->out('For plugin: ' . $plugin);
            $this->hr();

            $this->_remove($config);
        }

        $this->out();
        $this->out('Done');
    }

    /**
     * Get list of plugins to process. Plugins without a webroot directory are skipped.
     *
     * @param string|null $name Name of plugin for which to symlink assets.
     *   If null all plugins will be processed.
     * @return array List of plugins with meta data.
     */
    protected function _list($name = null)
    {
        if ($name === null) {
            $pluginsList = Plugin::loaded();
        } else {
            if (!Plugin::loaded($name)) {
                $this->err(sprintf('Plugin %s is not loaded.', $name));

                return [];
            }
            $pluginsList = [$name];
        }

        $plugins = [];

        foreach ($pluginsList as $plugin) {
            $path = Plugin::path($plugin) . 'webroot';
            if (!is_dir($path)) {
                $this->verbose('', 1);
                $this->verbose(
                    sprintf('Skipping plugin %s. It does not have webroot folder.', $plugin),
                    2
                );
                continue;
            }

            $link = Inflector::underscore($plugin);
            $dir = WWW_ROOT;
            $namespaced = false;
            if (strpos($link, '/') !== false) {
                $namespaced = true;
                $parts = explode('/', $link);
                $link = array_pop($parts);
                $dir = WWW_ROOT . implode(DIRECTORY_SEPARATOR, $parts) . DIRECTORY_SEPARATOR;
            }

            $plugins[$plugin] = [
                'srcPath' => Plugin::path($plugin) . 'webroot',
                'destDir' => $dir,
                'link' => $link,
                'namespaced' => $namespaced
            ];
        }

        return $plugins;
    }

    /**
     * Process plugins
     *
     * @param array $plugins List of plugins to process
     * @param bool $copy Force copy mode. Default false.
     * @return void
     */
    protected function _process($plugins, $copy = false)
    {
        $overwrite = (bool)$this->param('overwrite');

        foreach ($plugins as $plugin => $config) {
            $this->out();
            $this->out('For plugin: ' . $plugin);
            $this->hr();

            if ($config['namespaced'] &&
                !is_dir($config['destDir']) &&
                !$this->_createDirectory($config['destDir'])
            ) {
                continue;
            }

            $dest = $config['destDir'] . $config['link'];

            if (file_exists($dest)) {
                if ($overwrite && !$this->_remove($config)) {
                    continue;
                } elseif (!$overwrite) {
                    $this->verbose(
                        $dest . ' already exists',
                        1
                    );

                    continue;
                }
            }

            if (!$copy) {
                $result = $this->_createSymlink(
                    $config['srcPath'],
                    $dest
                );
                if ($result) {
                    continue;
                }
            }

            $this->_copyDirectory(
                $config['srcPath'],
                $dest
            );
        }

        $this->out();
        $this->out('Done');
    }

    /**
     * Remove folder/symlink.
     *
     * @param array $config Plugin config.
     * @return bool
     */
    protected function _remove($config)
    {
        if ($config['namespaced'] && !is_dir($config['destDir'])) {
            $this->verbose(
                $config['destDir'] . $config['link'] . ' does not exist',
                1
            );

            return false;
        }

        $dest = $config['destDir'] . $config['link'];

        if (!file_exists($dest)) {
            $this->verbose(
                $dest . ' does not exist',
                1
            );

            return false;
        }

        if (is_link($dest)) {
            // @codingStandardsIgnoreLine
            if (@unlink($dest)) {
                $this->out('Unlinked ' . $dest);

                return true;
            } else {
                $this->err('Failed to unlink  ' . $dest);

                return false;
            }
        }

        $folder = new Folder($dest);
        if ($folder->delete()) {
            $this->out('Deleted ' . $dest);

            return true;
        } else {
            $this->err('Failed to delete ' . $dest);

            return false;
        }
    }

    /**
     * Create directory
     *
     * @param string $dir Directory name
     * @return bool
     */
    protected function _createDirectory($dir)
    {
        $old = umask(0);
        // @codingStandardsIgnoreStart
        $result = @mkdir($dir, 0755, true);
        // @codingStandardsIgnoreEnd
        umask($old);

        if ($result) {
            $this->out('Created directory ' . $dir);

            return true;
        }

        $this->err('Failed creating directory ' . $dir);

        return false;
    }

    /**
     * Create symlink
     *
     * @param string $target Target directory
     * @param string $link Link name
     * @return bool
     */
    protected function _createSymlink($target, $link)
    {
        // @codingStandardsIgnoreStart
        $result = @symlink($target, $link);
        // @codingStandardsIgnoreEnd

        if ($result) {
            $this->out('Created symlink ' . $link);

            return true;
        }

        return false;
    }

    /**
     * Copy directory
     *
     * @param string $source Source directory
     * @param string $destination Destination directory
     * @return bool
     */
    protected function _copyDirectory($source, $destination)
    {
        $folder = new Folder($source);
        if ($folder->copy(['to' => $destination])) {
            $this->out('Copied assets to directory ' . $destination);

            return true;
        }

        $this->err('Error copying assets to directory ' . $destination);

        return false;
    }

    /**
     * Gets the option parser instance and configures it.
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();

        $parser->addSubcommand('symlink', [
            'help' => 'Symlink (copy as fallback) plugin assets to app\'s webroot.'
        ])->addSubcommand('copy', [
            'help' => 'Copy plugin assets to app\'s webroot.'
        ])->addSubcommand('remove', [
            'help' => 'Remove plugin assets from app\'s webroot.'
        ])->addArgument('name', [
            'help' => 'A specific plugin you want to symlink assets for.',
            'optional' => true,
        ])
        ->addOption('overwrite', [
            'boolean' => true,
            'default' => false,
            'help' => 'Overwrite existing symlink / folder.'
        ]);

        return $parser;
    }
}
