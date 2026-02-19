<?php
declare(strict_types=1);

/**
 * CakePHP :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Command;

trait PluginConfigFileTrait
{
    protected function invalidatePhpFileCache(string $path): void
    {
        clearstatcache(true, $path);
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }
    }

    protected function writePhpFile(string $path, string $contents): void
    {
        file_put_contents($path, $contents);
        $this->invalidatePhpFileCache($path);
    }

    protected function deletePhpFile(string $path): void
    {
        $this->invalidatePhpFileCache($path);
        unlink($path);
    }

    /**
     * @return array<string, mixed>
     */
    protected function includePhpConfig(string $path): array
    {
        $this->invalidatePhpFileCache($path);
        $config = include $path;
        assert(is_array($config));

        return $config;
    }
}
