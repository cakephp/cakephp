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

use Cake\Utility\Fs\Enum\DepthOperator;
use FilterIterator;
use Iterator;
use RecursiveIteratorIterator;

/**
 * Filters files by directory depth.
 *
 * Uses DepthOperator enum for comparison:
 * - EQUAL: Depth must equal value
 * - NOT_EQUAL: Depth must not equal value
 * - LESS_THAN: Depth must be less than value
 * - LESS_THAN_OR_EQUAL: Depth must be <= value
 * - GREATER_THAN: Depth must be greater than value
 * - GREATER_THAN_OR_EQUAL: Depth must be >= value
 *
 * @extends \FilterIterator<string, \SplFileInfo, \Iterator<string, \SplFileInfo>>
 */
final class DepthFilterIterator extends FilterIterator
{
    /**
     * @param \Iterator $iterator The iterator to filter (typically RecursiveIteratorIterator)
     * @param \Cake\Utility\Fs\Enum\DepthOperator $operator Comparison operator
     * @param int $value Depth value to compare against
     */
    public function __construct(
        Iterator $iterator,
        protected readonly DepthOperator $operator,
        protected readonly int $value,
    ) {
        parent::__construct($iterator);
    }

    /**
     * @inheritDoc
     */
    public function accept(): bool
    {
        $inner = $this->getInnerIterator();

        // If the inner iterator is a RecursiveIteratorIterator, use its getDepth()
        if ($inner instanceof RecursiveIteratorIterator) {
            $depth = $inner->getDepth();
        } else {
            // For other iterators wrapped in callbacks/filters, we need to unwrap
            // until we find the RecursiveIteratorIterator
            $current = $inner;
            while ($current instanceof FilterIterator) {
                $current = $current->getInnerIterator();
            }

            if ($current instanceof RecursiveIteratorIterator) {
                $depth = $current->getDepth();
            } else {
                // Fallback: can't determine depth, accept everything
                return true;
            }
        }

        return match ($this->operator) {
            DepthOperator::EQUAL => $depth === $this->value,
            DepthOperator::NOT_EQUAL => $depth !== $this->value,
            DepthOperator::LESS_THAN => $depth < $this->value,
            DepthOperator::LESS_THAN_OR_EQUAL => $depth <= $this->value,
            DepthOperator::GREATER_THAN => $depth > $this->value,
            DepthOperator::GREATER_THAN_OR_EQUAL => $depth >= $this->value,
        };
    }
}
