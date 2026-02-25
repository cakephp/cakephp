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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Utility\Fs\Iterator;

use RecursiveFilterIterator;

/**
 * Filters out hidden files and directories (those starting with a dot).
 *
 * @internal
 */
final class HiddenFileFilterIterator extends RecursiveFilterIterator
{
    /**
     * @inheritDoc
     */
    public function accept(): bool
    {
        /** @var \SplFileInfo $current */
        $current = $this->current();

        return !str_starts_with($current->getFilename(), '.');
    }

    /**
     * @inheritDoc
     */
    public function getChildren(): self
    {
        /** @var \RecursiveIterator<string, \SplFileInfo> $inner */
        $inner = $this->getInnerIterator();

        return new self($inner->getChildren());
    }
}
