<?php
declare(strict_types=1);

namespace Cake\Controller\Attribute;

use Cake\Http\ServerRequest;
use ReflectionParameter;

/**
 * Interface for controller action parameter attributes.
 *
 * Attributes implementing this interface can resolve parameter values
 * from the request, enabling custom parameter injection in controller actions.
 */
interface ParameterAttributeInterface
{
    /**
     * Resolve the parameter value from the request.
     *
     * @param \ReflectionParameter $parameter The parameter being resolved
     * @param \Cake\Http\ServerRequest $request The server request
     * @return mixed The resolved value
     */
    public function resolve(ReflectionParameter $parameter, ServerRequest $request): mixed;
}
