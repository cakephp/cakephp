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
namespace Cake\Auth;

/**
 * A password hasher that can use multiple different hashes where only
 * one is the preferred one. This is useful when trying to migrate an
 * existing database of users from one password type to another.
 */
class FallbackPasswordHasher extends AbstractPasswordHasher
{

    /**
     * Default config for this object.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'hashers' => []
    ];

    /**
     * Holds the list of password hasher objects that will be used
     *
     * @var array
     */
    protected $_hashers = [];

    /**
     * Constructor
     *
     * @param array $config configuration options for this object. Requires the
     * `hashers` key to be present in the array with a list of other hashers to be
     * used
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        foreach ($this->_config['hashers'] as $key => $hasher) {
            if (!is_string($hasher)) {
                $hasher += [
                    'className' => $key,
                ];
            }
            $this->_hashers[] = PasswordHasherFactory::build($hasher);
        }
    }

    /**
     * Generates password hash.
     *
     * Uses the first password hasher in the list to generate the hash
     *
     * @param string $password Plain text password to hash.
     * @return string Password hash
     */
    public function hash($password)
    {
        return $this->_hashers[0]->hash($password);
    }

    /**
     * Verifies that the provided password corresponds to its hashed version
     *
     * This will iterate over all configured hashers until one of them returns
     * true.
     *
     * @param string $password Plain text password to hash.
     * @param string $hashedPassword Existing hashed password.
     * @return bool True if hashes match else false.
     */
    public function check($password, $hashedPassword)
    {
        foreach ($this->_hashers as $hasher) {
            if ($hasher->check($password, $hashedPassword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if the password need to be rehashed, with the first hasher present
     * in the list of hashers
     *
     * @param string $password The password to verify
     * @return bool
     */
    public function needsRehash($password)
    {
        return $this->_hashers[0]->needsRehash($password);
    }
}
