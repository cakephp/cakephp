<?php
declare(strict_types=1);

namespace TestApp\Controller\Component;

use Cake\Controller\Component\RequestHandlerComponent;

class RequestHandlerExtComponent extends RequestHandlerComponent
{
    public $ext;

    public function getExt(): ?string
    {
        return $this->ext;
    }

    public function setExt(?string $ext)
    {
        $this->ext = $ext;
    }
}
