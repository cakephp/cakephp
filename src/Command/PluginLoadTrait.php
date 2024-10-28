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
 * @since         5.1.2
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Command;

/**
 * Trait for plugin load/unload functionality.
 *
 * @internal
 */
trait PluginLoadTrait
{
    /**
     * @param string $configFile Config file.
     * @return bool
     */
    protected function isTabIndentation(string $configFile): bool
    {
        if (!file_exists($configFile)) {
            return false;
        }

        $content = (string)file_get_contents($configFile);

        return str_contains($content, "\t");
    }

    /**
     * @param string $array Content
     * @return string
     */
    protected function indentWithTab(string $array): string
    {
        return str_replace(['    ', '  '], "\t", $array);
    }
}
