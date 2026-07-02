<?php

namespace Botnetdobbs\Luminous\Extractors;

use Botnetdobbs\Luminous\Attributes\ApiComposedOf;
use Botnetdobbs\Luminous\Attributes\ApiResponse;
use Botnetdobbs\Luminous\Attributes\ApiResponseHeader;
use Botnetdobbs\Luminous\Attributes\ApiStream;

class ResponseBuilder
{
    private const DEFAULT_MEDIA_TYPE = 'application/json';

    public function __construct(
        private readonly ResourceExtractor $resourceExtractor,
        private readonly array $config,
    ) {}

    public function build(\ReflectionMethod $methodRef, array $exampleInstances): array
    {
        $responses = $this->buildResponses($methodRef, $exampleInstances);
        $responses = $this->applyComposedOf($methodRef, $responses);

        if (! isset($responses['500'])) {
            $responses['500'] = ['description' => 'Internal server error'];
        }

        return $responses;
    }

    private function buildResponses(\ReflectionMethod $methodRef, array $exampleInstances): array
    {
        $responses = [];

        foreach ($methodRef->getAttributes(ApiResponse::class) as $attr) {
            $response = $attr->newInstance();
            $status = (string) $response->status;

            if ($response->ref !== null) {
                $responses[$status] = [
                    'description' => $response->description,
                    'content' => [
                        self::DEFAULT_MEDIA_TYPE => [
                            'schema' => ['$ref' => $response->ref],
                        ],
                    ],
                ];
            } elseif ($response->resource !== null) {
                $resourceSchema = $this->resourceExtractor->extract($response->resource);
                $schema = $response->isCollection()
                    ? ['type' => 'array', 'items' => $resourceSchema]
                    : $resourceSchema;

                if ($this->config['wrap_responses'] ?? false) {
                    $key = $this->config['response_wrapper_key'] ?? 'data';
                    if (empty($key) || ! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', (string) $key)) {
                        logger()->warning('luminous: invalid response_wrapper_key; falling back to "data"');
                        $key = 'data';
                    }
                    $wrapped = [
                        'type' => 'object',
                        'properties' => [$key => $schema],
                        'required' => [$key],
                    ];
                    if ($response->paginated && ($this->config['include_pagination_schema'] ?? true)) {
                        $wrapped['properties']['pagination'] = ['$ref' => '#/components/schemas/PaginationMeta'];
                        $wrapped['required'][] = 'pagination';
                    }
                    $schema = $wrapped;
                }

                $responses[$status] = [
                    'description' => $response->description,
                    'content' => [
                        self::DEFAULT_MEDIA_TYPE => ['schema' => $schema],
                    ],
                ];
            } else {
                $responses[$status] = ['description' => $response->description];
            }
        }

        $streamAttr = $methodRef->getAttributes(ApiStream::class)[0] ?? null;

        if ($streamAttr !== null) {
            $stream = $streamAttr->newInstance();
            $status = (string) $stream->status;

            if (isset($responses[$status])) {
                logger()->warning(
                    "Luminous: #[ApiStream] on {$methodRef->class}::{$methodRef->name} conflicts with ".
                    "#[ApiResponse({$stream->status})]. ApiStream was ignored; use distinct status codes."
                );
            } else {
                $itemSchema = $this->resourceExtractor->extract($stream->schema);
                $responses[$status] = [
                    'description' => $stream->description,
                    'content' => [
                        $stream->mediaType => ['itemSchema' => $itemSchema],
                    ],
                ];
            }
        }

        foreach ($exampleInstances as $example) {
            if ($example->type === 'response') {
                $status = (string) $example->status;
                if (! isset($responses[$status])) {
                    logger()->warning(
                        "Luminous: ApiExample '{$example->name}' targets response status {$status} "
                            ."which is not declared on {$methodRef->class}::{$methodRef->name}. Example was not applied."
                    );

                    continue;
                }
                if (! isset($responses[$status]['content'][$example->mediaType]['schema']) &&
                    ! isset($responses[$status]['content'][$example->mediaType]['itemSchema'])) {
                    logger()->warning(
                        "Luminous: ApiExample '{$example->name}' targets response {$status} which has no schema. Example was not applied."
                    );

                    continue;
                }
                $responses[$status]['content'][$example->mediaType]['examples'][$example->name] = $example->toExampleObject();
            }
        }

        foreach ($methodRef->getAttributes(ApiResponseHeader::class) as $attr) {
            $header = $attr->newInstance();
            $status = (string) $header->status;

            if (! isset($responses[$status])) {
                logger()->warning(
                    "Luminous: ApiResponseHeader '{$header->name}' targets status {$status} ".
                    "which is not declared on {$methodRef->class}::{$methodRef->name}. Header was skipped."
                );

                continue;
            }

            $headerObj = ['schema' => ['type' => $header->type]];
            if ($header->format !== '') {
                $headerObj['schema']['format'] = $header->format;
            }
            if ($header->description !== '') {
                $headerObj['description'] = $header->description;
            }
            if ($header->required) {
                $headerObj['required'] = true;
            }

            $responses[$status]['headers'][$header->name] = $headerObj;
        }

        return $responses;
    }

    private function applyComposedOf(\ReflectionMethod $methodRef, array $responses): array
    {
        foreach ($methodRef->getAttributes(ApiComposedOf::class) as $attr) {
            $composed = $attr->newInstance();
            $schemas = collect($composed->refs)
                ->map(fn ($classOrRef) => class_exists($classOrRef)
                    ? $this->resourceExtractor->extract($classOrRef)
                    : ['$ref' => $classOrRef]
                )
                ->all();

            $composedSchema = [$composed->composition => $schemas];

            if ($composed->forStatus !== null) {
                $key = (string) $composed->forStatus;
                if (isset($responses[$key])) {
                    if (! isset($responses[$key]['content'])) {
                        $responses[$key]['content'][self::DEFAULT_MEDIA_TYPE]['schema'] = $composedSchema;
                    } else {
                        logger()->warning(
                            "Luminous: ApiComposedOf on {$methodRef->class}::{$methodRef->name}: ".
                            "response {$key} already has a content schema. ApiComposedOf was not applied."
                        );
                    }
                }
            } else {
                $applied = false;
                foreach ($responses as &$responseObj) {
                    if (! isset($responseObj['content'])) {
                        $responseObj['content'][self::DEFAULT_MEDIA_TYPE]['schema'] = $composedSchema;
                        $applied = true;
                        break;
                    }
                }
                unset($responseObj);

                if (! $applied) {
                    logger()->warning(
                        "ApiComposedOf on {$methodRef->class}::{$methodRef->name}: no description-only response found; composition schema was not applied."
                    );
                }
            }
        }

        return $responses;
    }
}
