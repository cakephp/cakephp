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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Association;

/**
 * Represents a type of association that that needs to be recovered by performing
 * an extra query.
 */
trait ExternalAssociationTrait
{

    use SelectableAssociationTrait {
        _defaultOptions as private _selectableOptions;
    }

    /**
     * Returns the default options to use for the eagerLoader
     *
     * @return array
     */
    protected function _defaultOptions()
    {
        return $this->_selectableOptions() + [
            'sort' => $this->sort()
        ];
    }
}
