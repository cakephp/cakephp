<?php
declare(strict_types=1);

namespace Cake\Controller\Attribute;

use Attribute;
use Cake\Controller\Attribute\Enum\RequestToDtoSource;
use Cake\Controller\Exception\InvalidParameterException;
use Cake\Http\ServerRequest;
use ReflectionNamedType;
use ReflectionParameter;

#[Attribute(Attribute::TARGET_PARAMETER)]
readonly class RequestToDto implements ParameterAttributeInterface
{
    /**
     * @param string|null $class DTO class name (optional for typed parameters)
     * @param \Cake\Controller\Attribute\Enum\RequestToDtoSource $source Data source: body, query, request, or auto
     */
    public function __construct(
        protected ?string $class = null,
        protected RequestToDtoSource $source = RequestToDtoSource::Auto,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(ReflectionParameter $parameter, ServerRequest $request): object
    {
        $dtoClass = $this->class;
        if ($dtoClass === null) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $dtoClass = $type->getName();
            }
        }

        if ($dtoClass === null || !class_exists($dtoClass)) {
            throw new InvalidParameterException([
                'template' => 'missing_dependency',
                'parameter' => $parameter->getName(),
                'type' => $dtoClass ?? 'Dto',
            ]);
        }

        if (!method_exists($dtoClass, 'createFromArray')) {
            throw new InvalidParameterException([
                'template' => 'missing_dependency',
                'parameter' => $parameter->getName(),
                'type' => $dtoClass,
            ]);
        }

        /** @var class-string $dtoClass */
        return $dtoClass::createFromArray($this->extractData($request));
    }

    /**
     * Extract data from request based on source.
     *
     * @param \Cake\Http\ServerRequest $request The server request
     * @return array<string, mixed>
     */
    protected function extractData(ServerRequest $request): array
    {
        return match ($this->source) {
            RequestToDtoSource::Body => (array)$request->getData(),
            RequestToDtoSource::Query => $request->getQueryParams(),
            RequestToDtoSource::Request => array_merge(
                $request->getQueryParams(),
                (array)$request->getData(),
            ),
            RequestToDtoSource::Auto => $this->extractAutoData($request),
        };
    }

    /**
     * Auto-detect data source based on request method.
     *
     * @param \Cake\Http\ServerRequest $request The server request
     * @return array<string, mixed>
     */
    protected function extractAutoData(ServerRequest $request): array
    {
        if ($request->is(['get', 'head'])) {
            return $request->getQueryParams();
        }

        $data = (array)$request->getData();
        if ($data !== []) {
            return $data;
        }

        return $request->getQueryParams();
    }
}
