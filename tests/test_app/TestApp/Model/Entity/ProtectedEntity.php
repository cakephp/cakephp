<?php
declare(strict_types=1);

namespace TestApp\Model\Entity;

use Cake\ORM\Entity;

class ProtectedEntity extends Entity
{
    protected array $_accessible = [
        'id' => true,
        'title' => false,
    ];
}
