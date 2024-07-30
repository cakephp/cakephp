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

use SplObjectStorage;

/**
 * Context tracking for Debugger::exportVar()
 *
 * This class is used by Debugger to track element depth, and
 * prevent cyclic references from being traversed multiple times.
 *
 * @internal
 */
class DebugContext
{
    private int $depth = 0;

    /**
     * @var \SplObjectStorage<object, int>
     */
    private SplObjectStorage $refs;

    /**
     * Constructor
     *
     * @param int $maxDepth The desired depth of dump output.
     */
    public function __construct(private int $maxDepth)
    {
        $this->refs = new SplObjectStorage();
    }

    /**
     * Return a clone with increased depth.
     */
    public function withAddedDepth(): static
    {
        $new = clone $this;
        $new->depth += 1;

        return $new;
    }

    /**
     * Get the remaining depth levels
     */
    public function remainingDepth(): int
    {
        return $this->maxDepth - $this->depth;
    }

    /**
     * Get the reference ID for an object.
     *
     * If this object does not exist in the reference storage,
     * it will be added and the id will be returned.
     *
     * @param object $object The object to get a reference for.
     */
    public function getReferenceId(object $object): int
    {
        if ($this->refs->contains($object)) {
            return $this->refs[$object];
        }

        $refId = $this->refs->count();
        $this->refs->attach($object, $refId);

        return $refId;
    }

    /**
     * Check whether an object has been seen before.
     *
     * @param object $object The object to get a reference for.
     */
    public function hasReference(object $object): bool
    {
        return $this->refs->contains($object);
    }
}
