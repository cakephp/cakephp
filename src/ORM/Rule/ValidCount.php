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
 * @since         3.2.9
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Rule;

use Cake\Datasource\EntityInterface;
use Cake\Validation\Validation;
use Countable;

/**
 * Validates the count of associated records.
 */
class ValidCount
{
    /**
     * The field to check
     *
     * @var string
     */
    protected $_field;

    /**
     * Constructor.
     *
     * @param string $field The field to check the count on.
     */
    public function __construct(string $field)
    {
        $this->_field = $field;
    }

    /**
     * Performs the count check
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity from where to extract the fields.
     * @param array<string, mixed> $options Options passed to the check.
     * @return bool True if successful, else false.
     */
    public function __invoke(EntityInterface $entity, array $options): bool
    {
        $value = $entity->{$this->_field};
        if (!is_array($value) && !$value instanceof Countable) {
            return false;
        }

        return Validation::comparison(count($value), $options['operator'], $options['count']);
    }
}
