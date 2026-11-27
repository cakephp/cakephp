<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Http\ServerRequest;

/**
 * Controller fixture with an additional optional request parameter
 * that must keep its default value.
 */
class OptionalRequestController extends Controller
{
    /**
     * @param \Cake\Http\ServerRequest $request The active request.
     * @param \Cake\Http\ServerRequest|null $originalRequest Must stay null.
     */
    public function __construct(
        ServerRequest $request,
        public readonly ?ServerRequest $originalRequest = null,
    ) {
        parent::__construct($request);
    }
}
