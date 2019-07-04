<?php
declare(strict_types=1);

namespace TestApp\Model\Entity;

use Cake\ORM\Entity;

/**
 * Entity for testing with hidden fields.
 */
class ProtectedUser extends Entity
{
    protected $_hidden = ['password'];
}
