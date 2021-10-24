<?php
declare(strict_types=1);

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
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Validation;

/**
 * Provides methods for managing multiple validators.
 */
interface ValidatorAwareInterface
{
    /**
     * Returns the validation rules tagged with $name.
     *
     * If a $name argument has not been provided, the default validator will be returned.
     * You can configure your default validator name in a `DEFAULT_VALIDATOR`
     * class constant.
     *
     * @param string|null $name The name of the validation set to return.
     * @return \Cake\Validation\Validator
     */
    public function getValidator(?string $name = null): Validator;

    /**
     * This method stores a custom validator under the given name.
     *
     * @param string $name The name of a validator to be set.
     * @param \Cake\Validation\Validator $validator Validator object to be set.
     * @return $this
     */
    public function setValidator(string $name, Validator $validator);

    /**
     * Checks whether a validator has been set.
     *
     * @param string $name The name of a validator.
     * @return bool
     */
    public function hasValidator(string $name): bool;
}
