<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Controller\Controller;

class UntypedRequestController extends Controller
{
    public function __construct($request)
    {
        parent::__construct($request); // phpcs:ignore
    }
}
