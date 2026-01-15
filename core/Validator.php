<?php

namespace Archetype\Core;

/**
 * Validator utility class.
 * Enforces strict data integrity based on Archetype specifications.
 */
class Validator
{
    /**
     * Validates a full data entry against a set of scheme fields.
     */
    public static function validateEntry(array $data, array $fields): void
    {
        // Ghost Fields Check: Ensure no extra fields are present
        $allowedKeys = array_column($fields, 'key');
        $inputKeys = array_keys($data);
        $extraKeys = array_diff($inputKeys, $allowedKeys);

        if (!empty($extraKeys)) {
            $firstExtra = reset($extraKeys);
            throw new \Exception("Field '{$firstExtra}' is not defined in the scheme.");
        }

        // Fiels Validation
        foreach ($fields as $field) {
            $key = $field['key'];
            $exists = array_key_exists($key, $data);
            $value = $exists ? $data[$key] : null;

            // Check Required constraint
            if ($field['required'] && (!$exists || $value === null || $value === '')) {
                throw new \Exception("Field '{$key}' is required.");
            }

            if (!$exists) continue;

            // Handle Array vs Scalar
            if ($field['is-array']) {
                if (!is_array($value)) throw new \Exception("Field '{$key}' must be an array.");
                
                $rules = $field['rules'] ?? [];
                if (isset($rules['min-length']) && count($value) < $rules['min-length']) throw new \Exception("Field '{$key}' requires at least {$rules['min-length']} items.");
                if (isset($rules['max-length']) && count($value) > $rules['max-length']) throw new \Exception("Field '{$key}' allows maximum {$rules['max-length']} items.");

                foreach ($value as $item) {
                    self::validateField($item, $field);
                }
            } else {
                self::validateField($value, $field);
            }
        }
    }

    /**
     * Validates a single value against field rules and type.
     */
    private static function validateField(mixed $value, array $field): void
    {
        $type = $field['type'];
        $rules = $field['rules'] ?? [];
        $key = $field['key'];

        switch ($type) {
            case 'STRING':
                if (!is_string($value)) throw new \Exception("'{$key}' must be a string.");
                if (isset($rules['min-char']) && strlen($value) < $rules['min-char']) throw new \Exception("'{$key}' is too short.");
                if (isset($rules['max-char']) && strlen($value) > $rules['max-char']) throw new \Exception("'{$key}' is too long.");
                if (isset($rules['pattern']) && !preg_match("/{$rules['pattern']}/", $value)) throw new \Exception("'{$key}' does not match the required pattern.");
                if (isset($rules['format'])) self::checkStringFormat($value, $rules, $key);
                break;

            case 'NUMBER':
                if (!is_int($value) && !is_float($value)) throw new \Exception("'{$key}' must be a number (int or float).");
                if (isset($rules['format'])) {
                    if ($rules['format'] === 'int' && !is_int($value)) throw new \Exception("'{$key}' must be an integer.");
                    if ($rules['format'] === 'datetime' && !is_int($value)) throw new \Exception("'{$key}' must be a timestamp in milliseconds (integer).");
                }
                if (isset($rules['min-value']) && $value < $rules['min-value']) throw new \Exception("'{$key}' must be at least {$rules['min-value']}.");
                if (isset($rules['max-value']) && $value > $rules['max-value']) throw new \Exception("'{$key}' must be at most {$rules['max-value']}.");
                if (isset($rules['step']) && $rules['step'] > 0) {
                    $remainder = fmod((float)$value, (float)$rules['step']);
                    if ($remainder > 0.00001 && abs($remainder - $rules['step']) > 0.00001) {
                        throw new \Exception("'{$key}' must follow a rounding step of {$rules['step']}.");
                    }
                }
                break;

            case 'BOOLEAN':
                if (!is_bool($value)) throw new \Exception("'{$key}' must be a boolean.");
                break;

            case 'ENTRIES':
                if (!is_int($value)) throw new \Exception("'{$key}' must be an entry ID (integer).");
                if (isset($rules['schemes']) && !empty($rules['schemes'])) {
                    if (!Schemes::IsInSchemes($value, $rules['schemes'])) {
                        throw new \Exception("'{$key}' references an entry (ID {$value}) from an unauthorized scheme.");
                    }
                }
                break;

            case 'UPLOADS':
                if (!is_int($value)) throw new \Exception("'{$key}' must be an upload ID (integer).");
                // TODO : Further MIME validation can be added here
                break;
        }

        // Global Enum Rule
        if (isset($rules['enum']) && is_array($rules['enum']) && !in_array($value, $rules['enum'])) {
            throw new \Exception("'{$key}' value is not in the allowed list.");
        }
    }

    private static function checkStringFormat(string $value, array $rules, string $key): void
    {
        $format = $rules['format'];
        switch ($format) {
            case 'json':
                json_decode($value);
                if (json_last_error() !== JSON_ERROR_NONE) throw new \Exception("'{$key}' is not valid JSON.");
                break;
            case 'html':
            case 'xml':
                $dom = new \DOMDocument();
                libxml_use_internal_errors(true);
                if (!$dom->loadXML("<root>$value</root>")) throw new \Exception("'{$key}' is not valid XML/HTML.");
                break;
            case 'hex-color':
                if (!preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value)) throw new \Exception("'{$key}' is not a valid hex color.");
                break;
            case 'address':
                // ISO 19160-1 basic check: non-empty string with minimum structural length
                if (strlen($value) < 10) throw new \Exception("'{$key}' is too short for a valid address.");
                break;
        }
    }
}