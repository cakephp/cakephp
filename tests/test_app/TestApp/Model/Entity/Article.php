<?php
declare(strict_types=1);

namespace TestApp\Model\Entity;

use Cake\ORM\Entity;

/**
 * Tests entity class used for asserting correct loading
 */
class Article extends Entity
{
    /**
     * Testing stub method.
     *
     * @return bool
     */
    public function isRequired()
    {
        return true;
    }
}
