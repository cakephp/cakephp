<?php
declare(strict_types=1);

namespace TestApp\Model\Entity;

use Cake\ORM\Entity;

/**
 * Test entity for mass assignment.
 */
class OpenTag extends Entity
{
    protected $_accessible = [
        'tag' => true,
    ];
}
