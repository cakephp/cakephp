<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Locator;

use Cake\ORM\Locator\LocatorInterface;
use Cake\ORM\TableRegistry;

/**
 * Contains method for setting and accessing LocatorInterface instance
 */
trait LocatorAwareTrait
{

    /**
     * Table locator instance
     *
     * @var \Cake\ORM\Locator\LocatorInterface
     */
    protected $_locator;

    /**
     * Sets the table locator.
     * If no parameters are passed, it will return the currently used locator.
     *
     * @param LocatorInterface|null $locator LocatorInterface instance.
     * @return LocatorInterface
     */
    public function locator(LocatorInterface $locator = null)
    {
        if ($locator !== null) {
            $this->_locator = $locator;
        }
        if (!$this->_locator) {
            $this->_locator = TableRegistry::locator();
        }
        return $this->_locator;
    }
}
