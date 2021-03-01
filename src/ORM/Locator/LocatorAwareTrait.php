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
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Locator;

use Cake\Datasource\FactoryLocator;

/**
 * Contains method for setting and accessing LocatorInterface instance
 */
trait LocatorAwareTrait
{
    /**
     * Table locator instance
     *
     * @var \Cake\ORM\Locator\LocatorInterface|null
     */
    protected $_tableLocator;

    /**
     * Sets the table locator.
     *
     * @param \Cake\ORM\Locator\LocatorInterface $tableLocator LocatorInterface instance.
     * @return $this
     */
    public function setTableLocator(LocatorInterface $tableLocator)
    {
        $this->_tableLocator = $tableLocator;

        return $this;
    }

    /**
     * Gets the table locator.
     *
     * @return \Cake\ORM\Locator\LocatorInterface
     */
    public function getTableLocator(): LocatorInterface
    {
        if ($this->_tableLocator === null) {
            /** @psalm-suppress InvalidPropertyAssignmentValue */
            $this->_tableLocator = FactoryLocator::get('Table');
        }

        /** @var \Cake\ORM\Locator\LocatorInterface */
        return $this->_tableLocator;
    }
}
