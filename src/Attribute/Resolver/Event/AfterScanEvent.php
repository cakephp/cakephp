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
namespace Cake\Attribute\Resolver\Event;

use Cake\Attribute\Resolver;
use Cake\Attribute\Resolver\AttributeCollection;
use Cake\Event\Event;

/**
 * Event fired after file scanning completes.
 *
 * This event is dispatched after all source files have been scanned and attributes
 * extracted. Contains the count of files scanned and attributes found.
 *
 * Stopping this event has no effect as the scanning is already complete.
 *
 * @extends \Cake\Event\Event<\Cake\Attribute\Resolver>
 */
class AfterScanEvent extends Event
{
    /**
     * Event name constant
     */
    public const NAME = 'Attribute.Resolver.afterScan';

    /**
     * Constructor
     *
     * @param \Cake\Attribute\Resolver $subject The Resolver instance
     * @param \Cake\Attribute\Resolver\AttributeCollection $collection The collection of found attributes
     * @param array<string> $scannedFiles List of scanned file paths
     */
    public function __construct(Resolver $subject, AttributeCollection $collection, array $scannedFiles)
    {
        parent::__construct(self::NAME, $subject, [
            'collection' => $collection,
            'scannedFiles' => $scannedFiles,
        ]);
    }

    /**
     * Returns the Resolver subject
     *
     * @return \Cake\Attribute\Resolver
     */
    public function getSubject(): Resolver
    {
        return parent::getSubject();
    }

    /**
     * Returns the collection of found attributes
     *
     * @return \Cake\Attribute\Resolver\AttributeCollection
     */
    public function getCollection(): AttributeCollection
    {
        return $this->getData('collection');
    }

    /**
     * Returns the list of scanned file paths
     *
     * @return array<string>
     */
    public function getScannedFiles(): array
    {
        return $this->getData('scannedFiles');
    }

    /**
     * Returns the number of files scanned
     *
     * @return int
     */
    public function getFileCount(): int
    {
        return count($this->getData('scannedFiles'));
    }

    /**
     * Returns the number of attributes found
     *
     * @return int
     */
    public function getAttributeCount(): int
    {
        return $this->getData('collection')->count();
    }
}
