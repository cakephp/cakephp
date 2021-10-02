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

use Cake\Core\Exception\CakeException;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\Table;

/**
 * Contains method for setting and accessing LocatorInterface instance
 */
trait LocatorAwareTrait
{
    /**
     * This object's default table alias.
     *
     * @var string|null
     */
    protected $defaultTable = null;

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

    /**
     * Convenience method to get a table instance.
     *
     * @param string|null $alias The alias name you want to get. Should be in CamelCase format.
     *  If `null` then the value of $defaultTable property is used.
     * @param array<string, mixed> $options The options you want to build the table with.
     *   If a table has already been loaded the registry options will be ignored.
     * @return \Cake\ORM\Table
     * @throws \Cake\Core\Exception\CakeException If `$alias` argument and `$defaultTable` property both are `null`.
     * @see \Cake\ORM\TableLocator::get()
     * @since 4.3.0
     */
    public function fetchTable(?string $alias = null, array $options = []): Table
    {
        $alias = $alias ?? $this->defaultTable;
        if ($alias === null) {
            throw new CakeException('You must provide an `$alias` or set the `$defaultTable` property.');
        }

        return $this->getTableLocator()->get($alias, $options);
    }
}
