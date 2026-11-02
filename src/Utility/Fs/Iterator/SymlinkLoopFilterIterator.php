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
 * @since         5.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Utility\Fs\Iterator;

use RecursiveDirectoryIterator;
use RecursiveFilterIterator;

/**
 * Prevents descending into symbolic links that would cause an infinite loop.
 *
 * When symbolic links are followed, a link pointing to one of the directories
 * currently being traversed (for example, a link to its own parent) would be
 * descended into forever. This iterator keeps track of the directories from the
 * root down to the current one and refuses to descend into a link whose target
 * is already part of that chain.
 *
 * The link itself is still returned as an entry.
 *
 * @internal
 */
final class SymlinkLoopFilterIterator extends RecursiveFilterIterator
{
    /**
     * Identities (device and inode) of the directories from the root down to the current one.
     *
     * @var array<string>
     */
    protected array $visited;

    /**
     * @param \RecursiveDirectoryIterator $iterator The iterator to wrap
     * @param array<string> $ancestors Identities of the directories above the one being iterated
     */
    public function __construct(RecursiveDirectoryIterator $iterator, array $ancestors = [])
    {
        parent::__construct($iterator);

        $this->visited = $ancestors;

        $stat = stat($iterator->getPath());
        if ($stat !== false) {
            $this->visited[] = $stat['dev'] . ':' . $stat['ino'];
        }
    }

    /**
     * @inheritDoc
     */
    public function accept(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function hasChildren(): bool
    {
        if (!parent::hasChildren()) {
            return false;
        }

        /** @var \SplFileInfo $current */
        $current = $this->current();
        if (!$current->isLink()) {
            return true;
        }

        $stat = stat($current->getPathname());

        return $stat === false || !in_array($stat['dev'] . ':' . $stat['ino'], $this->visited, true);
    }

    /**
     * @inheritDoc
     */
    public function getChildren(): self
    {
        /** @var \RecursiveDirectoryIterator $inner */
        $inner = $this->getInnerIterator();

        return new self($inner->getChildren(), $this->visited);
    }
}
