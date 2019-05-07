<?php
declare(strict_types=1);

namespace TestApp\Model\Entity;

use Cake\ORM\Behavior\Translate\TranslateTrait;
use Cake\ORM\Entity;

class TranslateTestEntity extends Entity
{
    use TranslateTrait;
}
