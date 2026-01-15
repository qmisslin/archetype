<?php

namespace Archetype\Core;

/**
 * Entries service.
 * Manages JSON data entries, validation, and advanced search filtering based on schemas.
 */
class Entries
{
    /**
     * Adds an entry that conforms to the current schema.
     */
    public static function Create(int $schemeId, array $data, int $userId): int
    {
        $scheme = Schemes::Get($schemeId);
        if (!$scheme) {
            APIHelper::error("Scheme not found", 404);
        }

        try {
            Validator::validateEntry($data, $scheme['fields']);
        } catch (\Exception $e) {
            APIHelper::error($e->getMessage(), 400);
        }

        $db = Database::get();
        $now = time();
        $stmt = $db->prepare("INSERT INTO ENTRIES (schemeId, schemeVersion, data, creation_timestamp, modification_timestamp, last_modification_userId) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $schemeId,
            $scheme['version'],
            json_encode($data),
            $now,
            $now,
            $userId
        ]);

        $id = (int)$db->lastInsertId();
        Logs::info('entry', "Entry created in scheme {$schemeId} (ID: {$id}) by User {$userId}");
        return $id;
    }

    /**
     * Updates an entry if it conforms to the current schema.
     */
    public static function Edit(int $entryId, array $data, int $userId): void
    {
        $db = Database::get();
        $stmt = $db->prepare("SELECT schemeId FROM ENTRIES WHERE id = ?");
        $stmt->execute([$entryId]);
        $entry = $stmt->fetch();

        if (!$entry) {
            APIHelper::error("Entry not found", 404);
        }

        // Fetch the current scheme version to validate against
        $scheme = Schemes::Get((int)$entry['schemeId']);
        
        try {
            Validator::validateEntry($data, $scheme['fields']);
        } catch (\Exception $e) {
            APIHelper::error($e->getMessage(), 400);
        }

        $stmt = $db->prepare("UPDATE ENTRIES SET data = ?, modification_timestamp = ?, last_modification_userId = ? WHERE id = ?");
        $stmt->execute([json_encode($data), time(), $userId, $entryId]);
        Logs::info('entry', "Entry ID {$entryId} updated by User {$userId}");
    }

    /**
     * Deletes an entry.
     */
    public static function Remove(int $entryId): void
    {
        $db = Database::get();
        $stmt = $db->prepare("DELETE FROM ENTRIES WHERE id = ?");
        $stmt->execute([$entryId]);
        Logs::info('entry', "Entry ID {$entryId} deleted.");
    }

    /**
     * Duplicates an entry data (supports duplicating outdated data).
     */
    public static function Duplicate(int $entryId, int $userId): int
    {
        $db = Database::get();
        $stmt = $db->prepare("SELECT * FROM ENTRIES WHERE id = ?");
        $stmt->execute([$entryId]);
        $original = $stmt->fetch();

        if (!$original) {
            APIHelper::error("Source entry not found", 404);
        }

        $now = time();
        $stmt = $db->prepare("INSERT INTO ENTRIES (schemeId, schemeVersion, data, creation_timestamp, modification_timestamp, last_modification_userId) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $original['schemeId'],
            $original['schemeVersion'],
            $original['data'],
            $now,
            $now,
            $userId
        ]);

        return (int)$db->lastInsertId();
    }

    /**
     * Lists entries for a given schema, filtered by user role access.
     */
    public static function List(int $schemeId, bool $outdated = false): array
    {
        $scheme = Schemes::Get($schemeId);
        if (!$scheme) return [];

        $db = Database::get();
        $sql = "SELECT * FROM ENTRIES WHERE schemeId = ?";
        if (!$outdated) {
            $sql .= " AND schemeVersion = " . (int)$scheme['version'];
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$schemeId]);
        $entries = $stmt->fetchAll();

        $user = Auth::user();
        $role = $user['role'] ?? 'PUBLIC'; // [cite: 42, 53]

        foreach ($entries as &$entry) {
            $entry['data'] = json_decode($entry['data'], true);
            $entry['data'] = self::filterByAccess($entry['data'], $scheme['fields'], $role);
        }

        return $entries;
    }

    /**
     * Retrieves values of an entry, filtered by access rules.
     */
    public static function GetById(int $entryId): ?array
    {
        $db = Database::get();
        $stmt = $db->prepare("SELECT * FROM ENTRIES WHERE id = ?");
        $stmt->execute([$entryId]);
        $entry = $stmt->fetch();

        if (!$entry) return null;

        $scheme = Schemes::Get((int)$entry['schemeId']);
        $entry['data'] = json_decode($entry['data'], true);
        
        $role = Auth::user()['role'] ?? 'PUBLIC';
        $entry['data'] = self::filterByAccess($entry['data'], $scheme['fields'], $role);

        return $entry;
    }

    /**
     * Retrieves the list of entries matching the JSON filter AST.
     */
    public static function Search(int $schemeId, bool $outdated, array $search): array
    {
        $entries = self::List($schemeId, $outdated);
        
        return array_values(array_filter($entries, function($entry) use ($search) {
            return self::evaluate($search, $entry['data']);
        }));
    }

    /**
     * Filters data to remove values the user cannot access based on the schema.
     */
    private static function filterByAccess(array $data, array $fields, string $role): array
    {
        $filtered = [];
        foreach ($fields as $field) {
            $allowedRoles = $field['access'] ?? [];
            if (in_array($role, $allowedRoles)) {
                $filtered[$field['key']] = $data[$field['key']] ?? $field['default'];
            }
        }
        return $filtered;
    }

    /**
     * Recursive AST Evaluator for the Search engine.
     */
    private static function evaluate(array $ast, array $data): bool
    {
        $operator = strtoupper($ast[0]);
        $args = array_slice($ast, 1);

        switch ($operator) {
            // Logical Operators
            case 'AND':
                foreach ($args as $sub) if (!self::evaluate($sub, $data)) return false;
                return true;
            case 'OR':
                foreach ($args as $sub) if (self::evaluate($sub, $data)) return true;
                return false;
            case 'NOT':
                return !self::evaluate($args[0], $data);

            // Selectors
            case 'KEY': return $data[$args[0]] ?? null;
            case 'PATH': return self::resolvePath($args[0], $data);
            case 'VALUE': return $args[0];

            // Comparisons
            case 'EQUAL': return self::resolve($args[0], $data) == self::resolve($args[1], $data);
            case 'NOT-EQUAL': return self::resolve($args[0], $data) != self::resolve($args[1], $data);
            case 'GREATER-THAN': return self::resolve($args[0], $data) > self::resolve($args[1], $data);
            case 'GREATER-OR-EQUAL': return self::resolve($args[0], $data) >= self::resolve($args[1], $data);
            case 'LESS-THAN': return self::resolve($args[0], $data) < self::resolve($args[1], $data);
            case 'LESS-OR-EQUAL': return self::resolve($args[0], $data) <= self::resolve($args[1], $data);
            
            // Set / Range
            case 'IN': return in_array(self::resolve($args[0], $data), (array)self::resolve($args[1], $data));
            case 'NOT-IN': return !in_array(self::resolve($args[0], $data), (array)self::resolve($args[1], $data));
            case 'BETWEEN':
                $val = self::resolve($args[0], $data);
                return $val >= $args[1] && $val <= $args[2];

            // Strings
            case 'CONTAINS': return str_contains((string)self::resolve($args[0], $data), (string)self::resolve($args[1], $data));
            case 'STARTS-WITH': return str_starts_with((string)self::resolve($args[0], $data), (string)self::resolve($args[1], $data));
            case 'ENDS-WITH': return str_ends_with((string)self::resolve($args[0], $data), (string)self::resolve($args[1], $data));

            // Existence
            case 'EXISTS': return array_key_exists($args[0][1], $data);
            case 'IS-NULL': return ($data[$args[0][1]] ?? null) === null;
            case 'IS-NOT-NULL': return ($data[$args[0][1]] ?? null) !== null;

            // Arrays
            case 'ANY':
                $list = (array)self::resolve($args[0], $data);
                foreach ($list as $item) if (self::evaluate($args[1], ['item' => $item])) return true;
                return false;
            case 'ALL':
                $list = (array)self::resolve($args[0], $data);
                foreach ($list as $item) if (!self::evaluate($args[1], ['item' => $item])) return false;
                return true;

            default: return false;
        }
    }

    /**
     * Resolves a value from a KEY, PATH or VALUE expression.
     */
    private static function resolve(mixed $expr, array $data): mixed
    {
        if (!is_array($expr)) return $expr;
        $type = strtoupper($expr[0]);
        if ($type === 'KEY') return $data[$expr[1]] ?? null;
        if ($type === 'PATH') return self::resolvePath($expr[1], $data);
        if ($type === 'VALUE') return $expr[1];
        return null;
    }

    /**
     * Resolves a nested path (e.g., "user.profile.name").
     */
    private static function resolvePath(string $path, array $data): mixed
    {
        $keys = explode('.', $path);
        foreach ($keys as $key) {
            if (!isset($data[$key])) return null;
            $data = $data[$key];
        }
        return $data;
    }
}