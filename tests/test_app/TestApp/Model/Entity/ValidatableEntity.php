<?php

namespace TestApp\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\EntityValidatorTrait;
use Cake\Validation\ValidatableInterface;

/**
 * Tests entity class used for asserting correct loading
 *
 */
class ValidatableEntity extends Entity implements ValidatableInterface
{

    use EntityValidatorTrait;
}
