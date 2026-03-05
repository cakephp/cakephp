<?php
declare(strict_types=1);

namespace TestApp\Model\Entity;

use Cake\ORM\Entity;

class UserWithProps extends Entity
{
    protected int $id;
    protected string $name;
    protected ?string $username;
    protected ?string $password;
    protected $created;
    protected $updated;
    protected $odd;

    protected $article;
    protected $articles;
    protected $comments;
}
