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

use Cake\Utility\Fs\Enum\FinderMode;
use FilterIterator;
use Iterator;

/**
 * Filters files by type (files only, directories only, or all).
 *
 * @internal
 */
class FileTypeFilterIterator extends FilterIterator
{
    /**
     * @param \Iterator $iterator The iterator to filter
     * @param \Cake\Utility\Fs\Enum\FinderMode $mode The mode (FILES, DIRECTORIES, or ALL)
     */
    public function __construct(
        Iterator $iterator,
        protected readonly FinderMode $mode,
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

        return match ($this->mode) {
            FinderMode::FILES => $current->isFile(),
            FinderMode::DIRECTORIES => $current->isDir(),
            FinderMode::ALL => true,
        };
    }
}
