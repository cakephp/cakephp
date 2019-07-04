<?php
declare(strict_types=1);

namespace TestApp\Controller\Component;

use Cake\Controller\Component\RequestHandlerComponent;

class RequestHandlerExtComponent extends RequestHandlerComponent
{
    public $ext;

    public function getExt()
    {
        return $this->ext;
    }

    public function setExt($ext)
    {
        $this->ext = $ext;
    }
}
