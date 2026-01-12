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

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Utility\Filesystem;
use Cake\Utility\Inflector;
use InvalidArgumentException;

/**
 * Trait for symlinking / copying plugin assets to app's webroot.
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
    protected Arguments $args;

    /**
     * Console IO
     *
     * @var \Cake\Console\ConsoleIo
     */
    protected ConsoleIo $io;

    /**
     * Get list of plugins to process. Plugins without a webroot directory are skipped.
     *
     * @param string|null $name Name of plugin for which to symlink assets.
     *   If null all plugins will be processed.
     * @return array<string, mixed> List of plugins with meta data.
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
                    2,
                );
                continue;
            }

            $link = Inflector::underscore($plugin);
            $wwwRoot = Configure::read('App.wwwRoot');
            $dir = $wwwRoot;
            $namespaced = false;
            if (str_contains($link, '/')) {
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
     * @param array<string, mixed> $plugins List of plugins to process
     * @param bool $copy Force copy mode. Default false.
     * @param bool $overwrite Overwrite existing files.
     * @param bool $relative Relative. Default false.
     * @return void
     */
    protected function _process(
        array $plugins,
        bool $copy = false,
        bool $overwrite = false,
        bool $relative = false,
    ): void {
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
            if ($copy) {
                if ((is_link($dest) || $overwrite) && !$this->_remove($config)) {
                    continue;
                }

                if (file_exists($dest)) {
                    $this->io->verbose($dest . ' already exists', 1);
                } else {
                    $this->_copyDirectory($config['srcPath'], $dest);
                }
                continue;
            }

            $result = $this->_createSymlink(
                $config['srcPath'],
                $dest,
                $relative,
            );
            if ($result) {
                continue;
            }

            if ($this->_isSymlinkValid($config['srcPath'], $dest)) {
                $this->io->verbose($dest . ' already exists', 1);
                continue;
            }

            if (!$this->_remove($config)) {
                continue;
            }

            if (!$this->_createSymlink($config['srcPath'], $dest)) {
                continue;
            }
        }

        $this->io->out();
        $this->io->out('Done');
    }

    /**
     * Remove folder/symlink.
     *
     * @param array<string, mixed> $config Plugin config.
     * @return bool
     */
    protected function _remove(array $config): bool
    {
        if ($config['namespaced'] && !is_dir($config['destDir'])) {
            return true;
        }

        $dest = $config['destDir'] . $config['link'];

        if (is_link($dest)) {
            // phpcs:ignore
            $success = DIRECTORY_SEPARATOR === '\\' ? @rmdir($dest) : @unlink($dest);
            if ($success) {
                $this->io->out('Unlinked ' . $dest);

                return true;
            }
            $this->io->error('Failed to unlink  ' . $dest);

            return false;
        }

        if (!file_exists($dest)) {
            return true;
        }

        $fs = new Filesystem();
        if (!$fs->deleteDir($dest)) {
            $this->io->error('Failed to delete ' . $dest);

            return false;
        }

        $this->io->out('Deleted ' . $dest);

        return true;
    }

    /**
     * Create directory
     *
     * @param string $dir Directory name
     * @return bool
     */
    protected function _createDirectory(string $dir): bool
    {
        // phpcs:disable
        $result = @mkdir($dir, 0777 ^ umask(), true);
        // phpcs:enable

        if ($result) {
            $this->io->out('Created directory ' . $dir);

            return true;
        }

        $this->io->error('Failed creating directory ' . $dir);

        return false;
    }

    /**
     * Create symlink
     *
     * @param string $target Target directory
     * @param string $link Link name
     * @param bool $relative Relative (true) or Absolute (false)
     * @return bool
     */
    protected function _createSymlink(string $target, string $link, bool $relative = false): bool
    {
        if ($relative) {
            $target = $this->_makeRelativePath($link, $target);
        }

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
     * Generate a relative path from one directory to another.
     *
     * @param string $from The symlink path
     * @param string $to The target path
     * @return string Relative path
     */
    protected function _makeRelativePath(string $from, string $to): string
    {
        $from = is_dir($from) ? rtrim($from, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : dirname($from);
        $from = realpath($from);
        $to = realpath($to);

        if ($from === false || $to === false) {
            throw new InvalidArgumentException('Invalid path provided to _makeRelativePath.');
        }

        $fromParts = explode(DIRECTORY_SEPARATOR, $from);
        $toParts = explode(DIRECTORY_SEPARATOR, $to);

        $fromCount = count($fromParts);
        $toCount = count($toParts);

        // Remove common parts
        while ($fromCount && $toCount && $fromParts[0] === $toParts[0]) {
            array_shift($fromParts);
            array_shift($toParts);
            $fromCount--;
            $toCount--;
        }

        return str_repeat('..' . DIRECTORY_SEPARATOR, $fromCount) . implode(DIRECTORY_SEPARATOR, $toParts);
    }

    /**
     * Checks if symlink exist and points to the correct target.
     *
     * @param string $target
     * @param string $link
     * @return bool
     */
    protected function _isSymlinkValid(string $target, string $link): bool
    {
        if (!is_link($link)) {
            return false;
        }

        $linkedPath = readlink($link);
        if ($linkedPath === false) {
            return false;
        }

        return realpath($target) === realpath($linkedPath);
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

        $this->io->error('Error copying assets to directory ' . $destination);

        return false;
    }
}
