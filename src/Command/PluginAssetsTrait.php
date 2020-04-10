<?php
declare(strict_types=1);

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
namespace Cake\Command;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Filesystem\Filesystem;
use Cake\Utility\Inflector;

/**
 * trait for symlinking / copying plugin assets to app's webroot.
 *
 * @internal
 */
trait PluginAssetsTrait
{
    /**
     * Arguments
     *
     * @var \Cake\Console\Arguments
     */
    protected $args;

    /**
     * Console IO
     *
     * @var \Cake\Console\ConsoleIo
     */
    protected $io;

    /**
     * Get list of plugins to process. Plugins without a webroot directory are skipped.
     *
     * @param string|null $name Name of plugin for which to symlink assets.
     *   If null all plugins will be processed.
     * @return array List of plugins with meta data.
     */
    protected function _list(?string $name = null): array
    {
        if ($name === null) {
            $pluginsList = Plugin::loaded();
        } else {
            $pluginsList = [$name];
        }

        $plugins = [];

        foreach ($pluginsList as $plugin) {
            $path = Plugin::path($plugin) . 'webroot';
            if (!is_dir($path)) {
                $this->io->verbose('', 1);
                $this->io->verbose(
                    sprintf('Skipping plugin %s. It does not have webroot folder.', $plugin),
                    2
                );
                continue;
            }

            $link = Inflector::underscore($plugin);
            $wwwRoot = Configure::read('App.wwwRoot');
            $dir = $wwwRoot;
            $namespaced = false;
            if (strpos($link, '/') !== false) {
                $namespaced = true;
                $parts = explode('/', $link);
                $link = array_pop($parts);
                $dir = $wwwRoot . implode(DIRECTORY_SEPARATOR, $parts) . DIRECTORY_SEPARATOR;
            }

            $plugins[$plugin] = [
                'srcPath' => Plugin::path($plugin) . 'webroot',
                'destDir' => $dir,
                'link' => $link,
                'namespaced' => $namespaced,
            ];
        }

        return $plugins;
    }

    /**
     * Process plugins
     *
     * @param array $plugins List of plugins to process
     * @param bool $copy Force copy mode. Default false.
     * @param bool $overwrite Overwrite existing files.
     * @return void
     */
    protected function _process(array $plugins, bool $copy = false, bool $overwrite = false): void
    {
        foreach ($plugins as $plugin => $config) {
            $this->io->out();
            $this->io->out('For plugin: ' . $plugin);
            $this->io->hr();

            if (
                $config['namespaced'] &&
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
                    $this->io->verbose(
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

        $this->io->out();
        $this->io->out('Done');
    }

    /**
     * Remove folder/symlink.
     *
     * @param array $config Plugin config.
     * @return bool
     */
    protected function _remove(array $config): bool
    {
        if ($config['namespaced'] && !is_dir($config['destDir'])) {
            $this->io->verbose(
                $config['destDir'] . $config['link'] . ' does not exist',
                1
            );

            return false;
        }

        $dest = $config['destDir'] . $config['link'];

        if (!file_exists($dest)) {
            $this->io->verbose(
                $dest . ' does not exist',
                1
            );

            return false;
        }

        if (is_link($dest)) {
            // phpcs:ignore
            if (@unlink($dest)) {
                $this->io->out('Unlinked ' . $dest);

                return true;
            } else {
                $this->io->err('Failed to unlink  ' . $dest);

                return false;
            }
        }

        $fs = new Filesystem();
        if ($fs->deleteDir($dest)) {
            $this->io->out('Deleted ' . $dest);

            return true;
        } else {
            $this->io->err('Failed to delete ' . $dest);

            return false;
        }
    }

    /**
     * Create directory
     *
     * @param string $dir Directory name
     * @return bool
     */
    protected function _createDirectory(string $dir): bool
    {
        $old = umask(0);
        // phpcs:disable
        $result = @mkdir($dir, 0755, true);
        // phpcs:enable
        umask($old);

        if ($result) {
            $this->io->out('Created directory ' . $dir);

            return true;
        }

        $this->io->err('Failed creating directory ' . $dir);

        return false;
    }

    /**
     * Create symlink
     *
     * @param string $target Target directory
     * @param string $link Link name
     * @return bool
     */
    protected function _createSymlink(string $target, string $link): bool
    {
        // phpcs:disable
        $result = @symlink($target, $link);
        // phpcs:enable

        if ($result) {
            $this->io->out('Created symlink ' . $link);

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
    protected function _copyDirectory(string $source, string $destination): bool
    {
        $fs = new Filesystem();
        if ($fs->copyDir($source, $destination)) {
            $this->io->out('Copied assets to directory ' . $destination);

            return true;
        }

        $this->io->err('Error copying assets to directory ' . $destination);

        return false;
    }
}
