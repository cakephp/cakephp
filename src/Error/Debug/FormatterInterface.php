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
 * @since         4.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Error\Debug;

/**
 * Interface for formatters used by Debugger::exportVar()
 *
 * @unstable This interface is not stable and may change in the future.
 */
interface FormatterInterface
{
    /**
     * Convert a tree of NodeInterface objects into a plain text string.
     *
     * @param \Cake\Error\Debug\NodeInterface $node The node tree to dump.
     * @return string
     */
    public function dump(NodeInterface $node): string;

    /**
     * Output a dump wrapper with location context.
     *
     * @param string $contents The contents to wrap and return
     * @param array $location The file and line the contents came from.
     * @return string
     */
    public function formatWrapper(string $contents, array $location);
}
