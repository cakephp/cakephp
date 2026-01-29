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

use Cake\Utility\Fs\Path;
use Closure;
use FilterIterator;
use Iterator;

/**
 * Filters files using a custom callback function.
 */
final class CallbackFilterIterator extends FilterIterator
{
    /**
     * @param \Iterator $iterator The iterator to filter
     * @param \Closure(\SplFileInfo, string): bool $callback Filter callback
     * @param string $basePath Base path for calculating relative paths
     */
    public function __construct(
        Iterator $iterator,
        protected Closure $callback,
        protected readonly string $basePath,
    ) {
        parent::__construct($iterator);
    }

    /**
     * @inheritDoc
     */
    public function accept(): bool
    {
        /** @var \SplFileInfo $file */
        $file = $this->current();

        // Calculate relative path
        $relativePath = Path::makeRelative(
            Path::normalize($file->getPathname()),
            Path::normalize($this->basePath),
        );

        return ($this->callback)($file, $relativePath);
    }
}
