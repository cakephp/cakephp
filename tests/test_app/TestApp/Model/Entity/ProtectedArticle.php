<?php
declare(strict_types=1);

namespace TestApp\Model\Entity;

use Cake\ORM\Entity;

/**
 * Test entity for mass assignment.
 */
class ProtectedArticle extends Entity
{
    protected array $_accessible = [
        'title' => true,
        'body' => true,
    ];
}
