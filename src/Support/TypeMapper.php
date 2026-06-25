<?php

namespace Botnetdobbs\Luminous\Support;

use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\In;

class TypeMapper
{
    private const PHP_TO_OPENAPI = [
        'int' => ['type' => 'integer'],
        'float' => ['type' => 'number', 'format' => 'float'],
        'bool' => ['type' => 'boolean'],
        'string' => ['type' => 'string'],
        'array' => ['type' => 'array'],
        'mixed' => [],
        'void' => [],
    ];

    private const FORMAT_MAP = [
        'uuid' => ['type' => 'string',  'format' => 'uuid'],
        'email' => ['type' => 'string',  'format' => 'email'],
        'uri' => ['type' => 'string',  'format' => 'uri'],
        'url' => ['type' => 'string',  'format' => 'uri'],
        'date-time' => ['type' => 'string',  'format' => 'date-time'],
        'date' => ['type' => 'string',  'format' => 'date'],
        'time' => ['type' => 'string',  'format' => 'time'],
        'password' => ['type' => 'string',  'format' => 'password'],
        'binary' => ['type' => 'string',  'format' => 'binary'],
        'byte' => ['type' => 'string',  'format' => 'byte'],
        'int32' => ['type' => 'integer', 'format' => 'int32'],
        'int64' => ['type' => 'integer', 'format' => 'int64'],
        'float' => ['type' => 'number',  'format' => 'float'],
        'double' => ['type' => 'number',  'format' => 'double'],
    ];

    public function phpTypeToOpenApi(string $phpType, ?string $format = null): array
    {
        if ($format && isset(self::FORMAT_MAP[$format])) {
            return self::FORMAT_MAP[$format];
        }

        $base = ltrim($phpType, '?');
        $nullable = $phpType !== $base;

        // Handle PHP 8 union types (e.g. string|null, int|float|null)
        if (str_contains($base, '|')) {
            $parts = explode('|', $base);
            $nullable = $nullable || in_array('null', $parts, true);
            $parts = array_values(array_filter($parts, fn ($p) => $p !== 'null'));
            $base = count($parts) === 1 ? $parts[0] : 'mixed';
        }

        if (class_exists($base) && is_subclass_of($base, \BackedEnum::class)) {
            return $this->enumToOpenApi($base);
        }

        return self::PHP_TO_OPENAPI[$base] ?? ['type' => 'string'];
    }

    public function enumToOpenApi(string $enumClass): array
    {
        $reflection = new \ReflectionEnum($enumClass);
        $backingType = $reflection->getBackingType()?->getName() ?? 'string';
        $cases = collect($enumClass::cases())->map(fn ($c) => $c->value)->all();

        return [
            'type' => $backingType === 'int' ? 'integer' : 'string',
            'enum' => $cases,
        ];
    }

    /**
     * Two-pass approach:
     *   Pass 1: resolve the field type from rule strings
     *   Pass 2: apply constraints using the resolved type
     *
     * Single-pass fails because min:/max: may appear before the type keyword
     * in the rules array, producing numeric constraints on string fields.
     */
    public function validationRulesToSchema(array $rules): array
    {
        $ruleStrings = collect($rules)->filter(fn ($r) => is_string($r));
        $ruleObjects = collect($rules)->filter(fn ($r) => is_object($r));

        // Pass 1: resolve the field type
        $resolvedType = 'string';
        foreach ($ruleStrings as $rule) {
            $resolvedType = match (true) {
                $rule === 'integer', $rule === 'int' => 'integer',
                $rule === 'numeric' => 'number',
                $rule === 'boolean', $rule === 'bool' => 'boolean',
                $rule === 'array' => 'array',
                $rule === 'file', $rule === 'image' => 'file',
                $rule === 'string' => 'string',
                default => $resolvedType,
            };
        }

        $schema = ['type' => $resolvedType === 'file' ? 'string' : $resolvedType];
        if ($resolvedType === 'file') {
            $schema['format'] = 'binary';
        }

        $isNumeric = in_array($resolvedType, ['integer', 'number'], true);
        $isArray = $resolvedType === 'array';

        // Pass 2: apply constraints based on the resolved type
        foreach ($ruleStrings as $rule) {
            if ($rule === 'email') {
                $schema['format'] = 'email';

                continue;
            }
            if ($rule === 'uuid') {
                $schema['format'] = 'uuid';

                continue;
            }
            if ($rule === 'url') {
                $schema['format'] = 'uri';

                continue;
            }
            if ($rule === 'nullable') {
                $schema['nullable'] = true;

                continue;
            }
            if ($rule === 'date') {
                $schema['format'] = 'date';

                continue;
            }
            if ($rule === 'date_format:Y-m-d') {
                $schema['format'] = 'date';

                continue;
            }
            if ($rule === 'date_format:Y-m-d\TH:i:sP') {
                $schema['format'] = 'date-time';

                continue;
            }

            if (str_starts_with($rule, 'min:')) {
                $val = (int) substr($rule, 4);
                $schema[$isNumeric ? 'minimum' : ($isArray ? 'minItems' : 'minLength')] = $val;

                continue;
            }
            if (str_starts_with($rule, 'max:')) {
                $val = (int) substr($rule, 4);
                $schema[$isNumeric ? 'maximum' : ($isArray ? 'maxItems' : 'maxLength')] = $val;

                continue;
            }
            if (str_starts_with($rule, 'size:')) {
                $val = (int) substr($rule, 5);
                $schema[$isNumeric ? 'minimum' : ($isArray ? 'minItems' : 'minLength')] = $val;
                $schema[$isNumeric ? 'maximum' : ($isArray ? 'maxItems' : 'maxLength')] = $val;

                continue;
            }
            if (str_starts_with($rule, 'between:')) {
                [$min, $max] = explode(',', substr($rule, 8));
                $schema[$isNumeric ? 'minimum' : ($isArray ? 'minItems' : 'minLength')] = (int) $min;
                $schema[$isNumeric ? 'maximum' : ($isArray ? 'maxItems' : 'maxLength')] = (int) $max;

                continue;
            }
            if (str_starts_with($rule, 'digits:')) {
                // digits: means "exactly N digits". Only meaningful for string types in OpenAPI
                if (! $isNumeric) {
                    $d = (int) substr($rule, 7);
                    $schema['minLength'] = $d;
                    $schema['maxLength'] = $d;
                }

                continue;
            }
            if (str_starts_with($rule, 'digits_between:')) {
                if (! $isNumeric) {
                    [$min, $max] = explode(',', substr($rule, 15));
                    $schema['minLength'] = (int) $min;
                    $schema['maxLength'] = (int) $max;
                }

                continue;
            }
            if (str_starts_with($rule, 'in:')) {
                $schema['enum'] = explode(',', substr($rule, 3));

                continue;
            }
            if (str_starts_with($rule, 'regex:')) {
                $schema['pattern'] = substr($rule, 6);

                continue;
            }
            if (str_starts_with($rule, 'min_digits:')) {
                if (! $isNumeric) {
                    $schema['minLength'] = (int) substr($rule, 11);
                }

                continue;
            }
            if (str_starts_with($rule, 'max_digits:')) {
                if (! $isNumeric) {
                    $schema['maxLength'] = (int) substr($rule, 11);
                }

                continue;
            }
        }

        // Rule objects (Rule::enum(), Rule::in(), etc.)
        foreach ($ruleObjects as $rule) {
            if ($rule instanceof Enum) {
                try {
                    $prop = (new \ReflectionObject($rule))->getProperty('type');
                    $enumClass = $prop->getValue($rule);
                    if (is_string($enumClass) && class_exists($enumClass) && is_subclass_of($enumClass, \BackedEnum::class)) {
                        $schema['enum'] = collect($enumClass::cases())->map(fn ($c) => $c->value)->all();
                    }
                } catch (\Throwable) {
                    // Silently skip. Rule::enum() internals may change
                }
            } elseif ($rule instanceof In) {
                try {
                    $prop = (new \ReflectionObject($rule))->getProperty('values');
                    $values = $prop->getValue($rule);
                    $schema['enum'] = array_map('strval', $values);
                } catch (\Throwable) {
                    // Fallback: parse __toString, stripping any surrounding quotes
                    $str = (string) $rule;
                    if (str_starts_with($str, 'in:')) {
                        $schema['enum'] = array_map(fn ($v) => trim($v, '"'), explode(',', substr($str, 3)));
                    }
                }
            } elseif (method_exists($rule, '__toString')) {
                $str = (string) $rule;
                if (str_starts_with($str, 'in:')) {
                    $schema['enum'] = array_map(fn ($v) => trim($v, '"'), explode(',', substr($str, 3)));
                }
            }
        }

        return collect($schema)->filter(fn ($v) => $v !== [] && $v !== '')->all();
    }
}
