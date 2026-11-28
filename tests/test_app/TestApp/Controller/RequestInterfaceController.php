<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Http\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller fixture whose constructor accepts the PSR request interface.
 */
class RequestInterfaceController extends Controller
{
    /**
     * @param ServerRequestInterface $request The active request.
     */
    public function __construct(ServerRequestInterface $request)
    {
        assert($request instanceof ServerRequest);
        parent::__construct($request);
    }
}
