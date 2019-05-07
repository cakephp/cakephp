<?php
declare(strict_types=1);

namespace TestApp\Model\Entity;

use Cake\ORM\Entity;

/**
 * Test entity for mass assignment.
 */
class OpenArticleEntity extends Entity
{
    protected $_accessible = [
        '*' => true,
    ];
}
