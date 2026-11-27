<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Http\ServerRequest;

/**
 * Controller fixture with two required request parameters. The factory can
 * only bind the active request once, so it must fail loudly instead of
 * auto-wiring an empty request into the second parameter.
 */
class TwoRequiredRequestsController extends Controller
{
    /**
     * @param \Cake\Http\ServerRequest $request The active request.
     * @param \Cake\Http\ServerRequest $other Cannot be resolved.
     */
    public function __construct(ServerRequest $request, public readonly ServerRequest $other)
    {
        parent::__construct($request);
    }
}
