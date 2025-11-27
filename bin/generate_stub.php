#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * PHPSTORM STUB GENERATOR (PHP 7.1 → 8.4)
 *
 * Usage:
 *   php generate-stubs.php /custom/output/dir
 *
 * If no argument is provided, it defaults to:
 *   <project-root>/.phpstorm-stubs
 *
 * Generates:
 *   Functions.php — autocomplete for all internal + user functions
 *
 * Includes:
 * - Internal functions (according to loaded extensions)
 * - User-defined functions (including namespaced)
 * - Functions loaded via Composer (autoload/files)
 * - Normalized types, defaults, invisible characters
 * - Lowercase "null"
 *
 * NOT FOR RUNTIME USE.
 */

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Polyfill for PHP < 8.0
 */
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}

/**
 * Resolve output directory (argument or default)
 */
$customOutputDir = $argv[1] ?? null;
$defaultOutputDir = realpath(__DIR__ . '/..') . '/.phpstorm-stubs';

$outputDir = $customOutputDir
    ? rtrim($customOutputDir, "/")
    : $defaultOutputDir;

$outputFile = $outputDir . '/Functions.php';

/**
 * Ensure output directory exists
 */
if (!is_dir($outputDir)) {
    @mkdir($outputDir, 0777, true);
    if (!is_dir($outputDir)) {
        fwrite(STDERR, "ERROR: Could not create output directory: $outputDir\n");
        exit(1);
    }
}

echo "Output directory: $outputDir\n";

/**
 * Loaded extensions → used to filter internal functions
 */
$loadedExtensions = get_loaded_extensions();
$loaded = array_fill_keys($loadedExtensions, true);

echo "Loaded extensions:" . PHP_EOL;
foreach ($loadedExtensions as $ext) {
    echo "- $ext" . PHP_EOL;
}

/**
 * Normalize type names
 */
function normalizeType(string $type): string {
    static $primitives = [
        'int','float','string','bool','array','null','callable','iterable',
        'object','mixed','true','false','void','never','resource'
    ];

    return implode('|', array_map(function ($t) use ($primitives) {
        $t = trim($t);

        if ($t === '' || in_array($t, $primitives, true)) {
            return $t;
        }

        if (str_starts_with($t, '\\')) {
            return $t;
        }

        return '\\' . $t;

    }, explode('|', $type)));
}

/**
 * Normalize default values (null, strings, invisible chars, arrays, etc.)
 */
function normalizeDefault($value): string {

    if (is_int($value) || is_float($value) || is_bool($value) || $value === null) {
        return strtolower(var_export($value, true));
    }

    if (is_string($value)) {
        if (preg_match('/[\x00-\x1F\x7F]/', $value)) {
            $escaped = '';
            $len = strlen($value);

            for ($i = 0; $i < $len; $i++) {
                $ch  = $value[$i];
                $ord = ord($ch);

                switch ($ord) {
                    case 9:  $escaped .= '\t'; break;
                    case 10: $escaped .= '\n'; break;
                    case 11: $escaped .= '\v'; break;
                    case 12: $escaped .= '\f'; break;
                    case 13: $escaped .= '\r'; break;
                    case 0:  $escaped .= '\0'; break;
                    default:
                        if ($ord >= 32 && $ord <= 126) {
                            $escaped .= $ch;
                        } else {
                            $escaped .= sprintf('\\x%02X', $ord);
                        }
                }
            }

            return '"' . $escaped . '"';
        }

        return var_export($value, true);
    }

    if (is_array($value)) {
        return $value === [] ? '[]' : 'null';
    }

    return 'null';
}

/**
 * Collect all functions
 */
$functions = array_unique(array_merge(
    get_defined_functions()['internal'],
    get_defined_functions()['user']
));

$lines = [];

foreach ($functions as $fn) {

    try {
        $ref = new ReflectionFunction($fn);
    } catch (ReflectionException $e) {
        continue;
    }

    $ext = $ref->getExtensionName();

    // Filter out internal functions from unloaded extensions
    if (is_string($ext) && !isset($loaded[$ext])) {
        continue;
    }

    /**
     * Resolve return type (PHP 7.1 → 8.4)
     */
    $returnType = 'mixed';

    if ($ref->hasReturnType()) {
        $rt = $ref->getReturnType();

        if ($rt instanceof ReflectionNamedType) {
            $name = $rt->getName();
            if ($rt->allowsNull() && $name !== 'mixed') {
                $name .= '|null';
            }
            $returnType = normalizeType($name);

        } elseif (class_exists('ReflectionUnionType') && $rt instanceof ReflectionUnionType) {
            $parts = [];
            foreach ($rt->getTypes() as $t) {
                $parts[] = $t->getName();
            }
            if ($rt->allowsNull()) {
                $parts[] = 'null';
            }
            $returnType = normalizeType(implode('|', $parts));

        } elseif (class_exists('ReflectionIntersectionType') && $rt instanceof ReflectionIntersectionType) {
            $parts = [];
            foreach ($rt->getTypes() as $t) {
                $parts[] = $t->getName();
            }
            $returnType = normalizeType(implode('&', $parts));
        }
    }

    /**
     * Resolve parameters (PHP 7.1 → 8.4)
     */
    $params = [];

    foreach ($ref->getParameters() as $param) {
        $part = '';
        $t = $param->getType();
        $typeString = '';

        if ($t instanceof ReflectionNamedType) {
            $typeString = $t->getName();
            if ($t->allowsNull() && $typeString !== 'mixed') {
                $typeString .= '|null';
            }

        } elseif (class_exists('ReflectionUnionType') && $t instanceof ReflectionUnionType) {
            $pieces = [];
            foreach ($t->getTypes() as $u) {
                $pieces[] = $u->getName();
            }
            if ($t->allowsNull()) {
                $pieces[] = 'null';
            }
            $typeString = implode('|', $pieces);

        } elseif (class_exists('ReflectionIntersectionType') && $t instanceof ReflectionIntersectionType) {
            $pieces = [];
            foreach ($t->getTypes() as $u) {
                $pieces[] = $u->getName();
            }
            $typeString = implode('&', $pieces);
        }

        if ($typeString !== '') {
            $part .= normalizeType($typeString) . ' ';
        }

        $part .= '$' . $param->getName();

        if ($param->isOptional()) {
            if ($param->isPassedByReference()) {
                $part .= " = null";
            } elseif ($param->isDefaultValueAvailable()) {
                $part .= " = " . normalizeDefault($param->getDefaultValue());
            } else {
                $part .= " = null";
            }
        }

        $params[] = $part;
    }

    $paramsStr = implode(', ', $params);
    $lines[] = " * @method {$returnType} {$fn}({$paramsStr})";
}

/**
 * Build final stub file
 */
$docblock = implode("\n", $lines);

$stub = <<<PHP
<?php
declare(strict_types=1);

namespace Filisko;

/**
 * AUTO-GENERATED STUB FOR PHPSTORM AUTOCOMPLETE
 * DO NOT USE IN RUNTIME
 *
$docblock
 */
class Functions {}
PHP;

file_put_contents($outputFile, $stub);

echo "Stub generated at: $outputFile\n";
