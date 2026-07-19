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
use RecursiveIterator;

/**
 * Filters out directories by name during recursive traversal.
 *
 * This filter prevents traversal into excluded directories entirely,
 * making it more efficient than filtering after traversal.
 *
 * @internal
 */
final class ExcludeDirectoryFilterIterator extends RecursiveFilterIterator
{
    /**
     * Constructor.
     *
     * @param \RecursiveIterator<string, \SplFileInfo> $iterator The iterator to filter
     * @param array<string> $excludeDirs Array of directory names to exclude
     */
    public function __construct(
        RecursiveIterator $iterator,
        protected readonly array $excludeDirs,
    ) {
        parent::__construct($iterator);
    }

    /**
     * @inheritDoc
     */
    public function accept(): bool
    {
        /** @var \SplFileInfo $current */
        $current = $this->current();

        // Always accept files
        if (!$current->isDir()) {
            return true;
        }

        // Check if directory name matches any excluded names
        $filename = $current->getFilename();
        foreach ($this->excludeDirs as $excluded) {
            if ($filename === $excluded) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getChildren(): self
    {
        /** @var \RecursiveIterator<string, \SplFileInfo> $inner */
        $inner = $this->getInnerIterator();

        return new self($inner->getChildren(), $this->excludeDirs);
    }
}
