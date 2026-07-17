<?php

namespace Botnetdobbs\Luminous\Support;

use Botnetdobbs\Luminous\Extractors\EnumExtractor;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\In;

class TypeMapper
{
    private EnumExtractor $enumExtractor;

    public function __construct(EnumExtractor $enumExtractor)
    {
        $this->enumExtractor = $enumExtractor;
    }

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

    // Exact-match validation rules that set $schema['format']
    private const RULE_FORMAT_MAP = [
        'email' => 'email',
        'uuid' => 'uuid',
        'url' => 'uri',
        'ip' => 'ipv4',
        'ipv4' => 'ipv4',
        'ipv6' => 'ipv6',
        'date' => 'date',
        'ulid' => 'ulid',
        'date_format:Y-m-d' => 'date',
        'date_format:Y-m-d\TH:i:sP' => 'date-time',
    ];

    // Exact-match validation rules that set $schema['pattern']
    private const RULE_PATTERN_MAP = [
        'alpha' => '^[a-zA-Z]+$',
        'alpha_num' => '^[a-zA-Z0-9]+$',
        'alpha_dash' => '^[a-zA-Z0-9_-]+$',
        'ascii' => '^[\x00-\x7F]*$',
        'uppercase' => '^[^a-z]*$',
        'lowercase' => '^[^A-Z]*$',
        'hex_color' => '^#([0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$',
        'mac_address' => '^([0-9a-fA-F]{2}[:-]){5}[0-9a-fA-F]{2}$',
    ];

    public static function applyNullable(array $schema): array
    {
        $type = $schema['type'] ?? 'string';
        if (! is_array($type)) {
            $schema['type'] = [$type, 'null'];
        }

        return $schema;
    }

    public function phpTypeToOpenApi(string $phpType, ?string $format = null): array
    {
        if ($format && isset(self::FORMAT_MAP[$format])) {
            return self::FORMAT_MAP[$format];
        }

        $base = ltrim($phpType, '?');

        // Handle PHP 8 union types (e.g. string|null, int|float|null)
        if (str_contains($base, '|')) {
            $parts = explode('|', $base);
            $parts = collect($parts)->reject(fn ($p) => $p === 'null')->values()->all();
            $base = count($parts) === 1 ? $parts[0] : 'mixed';
        }

        if (class_exists($base) && is_subclass_of($base, \BackedEnum::class)) {
            return $this->enumToOpenApi($base);
        }

        return self::PHP_TO_OPENAPI[$base] ?? ['type' => 'string'];
    }

    public function enumToOpenApi(string $enumClass): array
    {
        return $this->enumExtractor->extract($enumClass);
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
                $rule === 'numeric', $rule === 'decimal', str_starts_with($rule, 'decimal:') => 'number',
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
        $minKey = $this->minKey($isNumeric, $isArray);
        $maxKey = $this->maxKey($isNumeric, $isArray);

        // Pass 2: apply constraints based on the resolved type
        foreach ($ruleStrings as $rule) {
            if (isset(self::RULE_FORMAT_MAP[$rule])) {
                $schema['format'] = self::RULE_FORMAT_MAP[$rule];

                continue;
            }
            if (isset(self::RULE_PATTERN_MAP[$rule])) {
                $schema['pattern'] = self::RULE_PATTERN_MAP[$rule];

                continue;
            }

            if ($rule === 'nullable') {
                $schema['nullable'] = true;

                continue;
            }
            if ($rule === 'confirmed') {
                $schema['writeOnly'] = true;
                if ($resolvedType === 'string') {
                    $schema['format'] = 'password';
                }

                continue;
            }
            if ($rule === 'distinct' && $isArray) {
                $schema['uniqueItems'] = true;

                continue;
            }

            // --- Parameterized rules (require parsing the suffix) ---
            if (str_starts_with($rule, 'min:')) {
                $schema[$minKey] = $this->boundParam(substr($rule, strlen('min:')), $isNumeric);

                continue;
            }
            if (str_starts_with($rule, 'max:')) {
                $schema[$maxKey] = $this->boundParam(substr($rule, strlen('max:')), $isNumeric);

                continue;
            }
            if (str_starts_with($rule, 'size:')) {
                $val = $this->boundParam(substr($rule, strlen('size:')), $isNumeric);
                $schema[$minKey] = $val;
                $schema[$maxKey] = $val;

                continue;
            }
            if (str_starts_with($rule, 'between:')) {
                $range = $this->parsePair(substr($rule, strlen('between:')));
                if ($range === null) {
                    $this->warn("Luminous: malformed between: rule '{$rule}', expected between:min,max. Skipping.");

                    continue;
                }
                [$min, $max] = $range;
                $schema[$minKey] = $this->boundParam($min, $isNumeric);
                $schema[$maxKey] = $this->boundParam($max, $isNumeric);

                continue;
            }
            if (str_starts_with($rule, 'digits:')) {
                // digits: means "exactly N digits". Only meaningful for string types in OpenAPI
                if (! $isNumeric) {
                    $d = (int) substr($rule, strlen('digits:'));
                    $schema['minLength'] = $d;
                    $schema['maxLength'] = $d;
                    $schema['pattern'] = "^\\d{{$d}}$";
                }

                continue;
            }
            if (str_starts_with($rule, 'digits_between:')) {
                if (! $isNumeric) {
                    $range = $this->parsePair(substr($rule, strlen('digits_between:')));
                    if ($range === null) {
                        $this->warn("Luminous: malformed digits_between: rule '{$rule}', expected digits_between:min,max. Skipping.");

                        continue;
                    }
                    [$min, $max] = $range;
                    $schema['minLength'] = (int) $min;
                    $schema['maxLength'] = (int) $max;
                    $schema['pattern'] = "^\\d{{$min},{$max}}$";
                }

                continue;
            }
            if (str_starts_with($rule, 'in:')) {
                $schema['enum'] = explode(',', substr($rule, strlen('in:')));

                continue;
            }
            if (str_starts_with($rule, 'regex:')) {
                $raw = substr($rule, strlen('regex:'));
                // Strip common regex delimiters (e.g. /pattern/, ~pattern~, #pattern#)
                if (strlen($raw) > 1 && $raw[0] === $raw[-1] && ! ctype_alnum($raw[0])) {
                    $raw = substr($raw, 1, -1);
                }
                // OpenAPI pattern must be ECMAScript. Warn on PCRE-only constructs
                // (atomic groups, named groups, possessive quantifiers, \K, \p{}) that
                // are invalid ECMAScript and can break Swagger UI or cause ReDoS.
                if (preg_match('/\(\?>|\(\?P[<\']|[+*?]\+|\\\\K\b|\\\\[pP]\{/', $raw)) {
                    $this->warn("Luminous: regex: rule contains PCRE-only constructs that ECMAScript does not support. The pattern may not work correctly in Swagger UI: {$raw}");
                }
                $schema['pattern'] = $raw;

                continue;
            }
            if (str_starts_with($rule, 'min_digits:')) {
                if (! $isNumeric) {
                    $schema['minLength'] = (int) substr($rule, strlen('min_digits:'));
                }

                continue;
            }
            if (str_starts_with($rule, 'max_digits:')) {
                if (! $isNumeric) {
                    $schema['maxLength'] = (int) substr($rule, strlen('max_digits:'));
                }

                continue;
            }
            if (str_starts_with($rule, 'multiple_of:')) {
                $schema['multipleOf'] = (float) substr($rule, strlen('multiple_of:'));

                continue;
            }
            if (str_starts_with($rule, 'gt:')) {
                $n = substr($rule, strlen('gt:'));
                if (is_numeric($n)) {
                    $schema[$isNumeric ? 'exclusiveMinimum' : 'minLength'] = $isNumeric ? (float) $n : (int) $n + 1;
                }

                continue;
            }
            if (str_starts_with($rule, 'gte:')) {
                $n = substr($rule, strlen('gte:'));
                if (is_numeric($n)) {
                    $schema[$isNumeric ? 'minimum' : 'minLength'] = $isNumeric ? (float) $n : (int) $n;
                }

                continue;
            }
            if (str_starts_with($rule, 'lt:')) {
                $n = substr($rule, strlen('lt:'));
                if (is_numeric($n)) {
                    $schema[$isNumeric ? 'exclusiveMaximum' : 'maxLength'] = $isNumeric ? (float) $n : (int) $n - 1;
                }

                continue;
            }
            if (str_starts_with($rule, 'lte:')) {
                $n = substr($rule, strlen('lte:'));
                if (is_numeric($n)) {
                    $schema[$isNumeric ? 'maximum' : 'maxLength'] = $isNumeric ? (float) $n : (int) $n;
                }

                continue;
            }
            if (str_starts_with($rule, 'starts_with:')) {
                $parts = array_map(fn ($v) => preg_quote($v, '/'), explode(',', substr($rule, strlen('starts_with:'))));
                $schema['pattern'] = '^('.implode('|', $parts).')';

                continue;
            }
            if (str_starts_with($rule, 'ends_with:')) {
                $parts = array_map(fn ($v) => preg_quote($v, '/'), explode(',', substr($rule, strlen('ends_with:'))));
                $schema['pattern'] = '('.implode('|', $parts).')$';

                continue;
            }
        }

        // Rule objects (Rule::enum(), Rule::in(), etc.)
        foreach ($ruleObjects as $rule) {
            if ($rule instanceof Enum) {
                $enumClass = $this->enumExtractor->classFromRule($rule);
                if ($enumClass !== null) {
                    $schema['enum'] = collect($enumClass::cases())->map(fn ($c) => $c->value)->all();
                }
            } elseif ($rule instanceof In) {
                try {
                    $prop = (new \ReflectionObject($rule))->getProperty('values');
                    $values = $prop->getValue($rule);
                    $schema['enum'] = collect($values)->map(fn ($v) => (string) $v)->all();
                } catch (\Throwable $e) {
                    // Comma-splitting __toString would silently corrupt values containing commas.
                    // Omit the enum list and warn rather than produce a wrong schema.
                    $this->warn("Luminous: could not read Rule::In values via reflection, so the enum list was left out of the schema. Error: {$e->getMessage()}");
                }
            } elseif (method_exists($rule, '__toString')) {
                $str = (string) $rule;
                if (str_starts_with($str, 'in:')) {
                    $schema['enum'] = collect(explode(',', substr($str, 3)))->map(fn ($v) => trim($v, '"'))->all();
                }
            }
        }

        // OpenAPI 3.2: convert nullable sentinel to type array
        if (isset($schema['nullable'])) {
            unset($schema['nullable']);
            $schema = self::applyNullable($schema);
        }

        return collect($schema)->filter(fn ($v) => $v !== [] && $v !== '')->all();
    }

    /**
     * Parse a min/max/size/between rule parameter the way Laravel compares it:
     * numeric fields keep float parameters, length/items constraints are integers.
     */
    private function boundParam(string $raw, bool $isNumeric): int|float
    {
        if ($isNumeric && is_numeric($raw) && str_contains($raw, '.')) {
            return (float) $raw;
        }

        return (int) $raw;
    }

    private function minKey(bool $isNumeric, bool $isArray): string
    {
        return $isNumeric ? 'minimum' : ($isArray ? 'minItems' : 'minLength');
    }

    private function maxKey(bool $isNumeric, bool $isArray): string
    {
        return $isNumeric ? 'maximum' : ($isArray ? 'maxItems' : 'maxLength');
    }

    private function parsePair(string $value): ?array
    {
        $parts = explode(',', $value, 2);

        return count($parts) === 2 ? $parts : null;
    }

    private function warn(string $message): void
    {
        try {
            logger()->warning($message);
        } catch (\Throwable) {
            // No-op when the Laravel container is not available (e.g. unit tests).
        }
    }
}
