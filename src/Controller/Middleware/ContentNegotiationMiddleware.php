<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license incontentTypeion, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         4.4.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller\Middleware;

use Cake\Controller\Controller;
use Cake\Http\Exception\NotAcceptableException;
use Cake\Http\ServerRequest;
use Cake\Utility\Hash;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ContentNegotiationMiddleware implements MiddlewareInterface
{
    /**
     * @var \Cake\Controller\Controller
     */
    protected $controller;

    /**
     * @var array<string, string|null>
     */
    protected $validTypes = [];

    public function __construct(Controller $controller, array $validTypes = [])
    {
        $this->controller = $controller;

        $validTypes += ['json' => 'Json'];

        $this->validTypes = Hash::normalize($validTypes);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $contentType = $request instanceof ServerRequest ? $this->getContentType($request) : 'html';

        if (in_array($contentType, ['htm', 'html'], true)) {
            return $handler->handle($request);
        }

        $this->renderAs($contentType);

        return $handler->handle($request);
    }

    protected function getContentType(ServerRequest $request): string
    {
        $contentType = $request->getParam('_ext');
        if ($contentType) {
            return $contentType;
        }

        $accept = $request->parseAccept();
        if (empty($accept) || current($accept)[0] !== 'text/html') {
            return 'html';
        }

        /** @var array $accepts */
        $accepts = $this->controller->getResponse()->mapType($accept);
        $preferredTypes = current($accepts);
        if (array_intersect($preferredTypes, ['html', 'xhtml'])) {
            return 'html';
        }

        $validTypes = array_keys($this->validTypes);
        foreach ($accepts as $types) {
            $matchedTypes = array_intersect($validTypes, $types);
            if ($matchedTypes) {
                return current($matchedTypes);
            }
        }

        throw new NotAcceptableException();
    }

    protected function renderAs(string $contentType): void
    {
        $this->controller->setResponse(
            $this->controller->getResponse()->withType($contentType)
        );

        $builder = $this->controller->viewBuilder();
        if ($builder->getClassName() !== null) {
            return;
        }

        $viewClass = $this->validTypes[$contentType];
        if ($viewClass !== null) {
            $builder->setClassName($viewClass);
        }
    }
}
