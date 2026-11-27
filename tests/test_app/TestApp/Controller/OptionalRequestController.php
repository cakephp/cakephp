<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Http\ServerRequest;

class OptionalRequestController extends Controller
{
    public function __construct(
        ServerRequest $request,
        public readonly ?ServerRequest $originalRequest = null,
    ) {
        parent::__construct($request);
    }
}
