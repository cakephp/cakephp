<?php
declare(strict_types=1);

namespace TestApp\Model\Entity;

use Cake\ORM\Entity;

/**
 * Marks id as protected
 */
class Session extends Entity
{
    protected $_accessible = [
        'id' => false,
        '*' => true,
    ];
}
