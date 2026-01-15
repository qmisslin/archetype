<?php

namespace Archetype\Core;

/**
 * Schemes service.
 * Manages JSON data structures and entry migrations.
 */
class Schemes
{
    public static function List(): array
    {
        $db = Database::get();
        return $db->query("SELECT id, name, version FROM SCHEMES ORDER BY name ASC")->fetchAll();
    }

    public static function Get(int $id): ?array
    {
        $db = Database::get();
        $stmt = $db->prepare("SELECT * FROM SCHEMES WHERE id = ?");
        $stmt->execute([$id]);
        $scheme = $stmt->fetch();
        if ($scheme) {
            $scheme['fields'] = json_decode($scheme['fields'], true);
        }
        return $scheme ?: null;
    }

    public static function Create(string $name, int $userId): int
    {
        $db = Database::get();
        $now = time();
        $stmt = $db->prepare("INSERT INTO SCHEMES (name, version, fields, creation_timestamp, modification_timestamp, last_modification_userId) VALUES (?, 1, '[]', ?, ?, ?)");
        $stmt->execute([$name, $now, $now, $userId]);
        
        $id = (int)$db->lastInsertId();
        Logs::info('scheme', "Scheme created: {$name} (ID: {$id}) by User {$userId}");
        return $id;
    }

    public static function Remove(int $id): void
    {
        $db = Database::get();
        // Delete entries first per spec
        $db->prepare("DELETE FROM ENTRIES WHERE schemeId = ?")->execute([$id]);
        $db->prepare("DELETE FROM SCHEMES WHERE id = ?")->execute([$id]);
        Logs::info('scheme', "Scheme ID {$id} and all related entries deleted.");
    }

    public static function Rename(int $id, string $name, int $userId): void
    {
        $db = Database::get();
        $stmt = $db->prepare("UPDATE SCHEMES SET name = ?, modification_timestamp = ?, last_modification_userId = ? WHERE id = ?");
        $stmt->execute([$name, time(), $userId, $id]);
        Logs::info('scheme', "Scheme ID {$id} renamed to {$name}");
    }

    public static function AddField(int $id, array $field, int $userId): void
    {
        $scheme = self::Get($id);
        $fields = $scheme['fields'];
        
        // Check if key already exists
        foreach ($fields as $f) {
            if ($f['key'] === $field['key']) throw new \Exception("Field key already exists.");
        }

        $fields[] = $field;
        self::save($id, $fields, $userId, false); // No version increment for new field
    }

    public static function RemoveField(int $id, string $key, int $userId): void
    {
        $scheme = self::Get($id);
        $fields = array_filter($scheme['fields'], fn($f) => $f['key'] !== $key);
        
        // Migration: Remove the key from all entries
        $db = Database::get();
        $stmt = $db->prepare("SELECT id, data FROM ENTRIES WHERE schemeId = ?");
        $stmt->execute([$id]);
        while ($entry = $stmt->fetch()) {
            $data = json_decode($entry['data'], true);
            if (isset($data[$key])) {
                unset($data[$key]);
                $upd = $db->prepare("UPDATE ENTRIES SET data = ? WHERE id = ?");
                $upd->execute([json_encode($data), $entry['id']]);
            }
        }

        self::save($id, array_values($fields), $userId, true);
    }

    public static function RekeyField(int $id, string $oldKey, string $newKey, int $userId): void
    {
        $scheme = self::Get($id);
        $fields = $scheme['fields'];
        $found = false;

        foreach ($fields as &$f) {
            if ($f['key'] === $newKey) throw new \Exception("New key already exists.");
            if ($f['key'] === $oldKey) {
                $f['key'] = $newKey;
                $found = true;
            }
        }

        if (!$found) throw new \Exception("Field not found.");

        // Migration: Rename the key in all entries
        $db = Database::get();
        $stmt = $db->prepare("SELECT id, data FROM ENTRIES WHERE schemeId = ?");
        $stmt->execute([$id]);
        while ($entry = $stmt->fetch()) {
            $data = json_decode($entry['data'], true);
            if (isset($data[$oldKey])) {
                $data[$newKey] = $data[$oldKey];
                unset($data[$oldKey]);
                $upd = $db->prepare("UPDATE ENTRIES SET data = ? WHERE id = ?");
                $upd->execute([json_encode($data), $entry['id']]);
            }
        }

        self::save($id, $fields, $userId, false); // Rekey doesn't change validation rules
    }

    public static function UpdateField(int $id, string $key, array $updatedField, int $userId): void
    {
        $scheme = self::Get($id);
        $fields = $scheme['fields'];
        $increment = false;

        foreach ($fields as &$f) {
            if ($f['key'] === $key) {
                // Check for version increment per spec
                if ($f['required'] !== $updatedField['required'] || 
                    $f['type'] !== $updatedField['type'] || 
                    $f['is-array'] !== $updatedField['is-array'] ||
                    json_encode($f['rules']) !== json_encode($updatedField['rules'])) {
                    $increment = true;
                }
                $f = $updatedField;
                break;
            }
        }

        self::save($id, $fields, $userId, $increment);
    }

    public static function IndexField(int $id, string $key, int $newIndex, int $userId): void
    {
        $scheme = self::Get($id);
        $fields = $scheme['fields'];
        
        $field = null;
        $currentIndex = -1;
        foreach ($fields as $idx => $f) {
            if ($f['key'] === $key) {
                $field = $f;
                $currentIndex = $idx;
                break;
            }
        }

        if ($field) {
            array_splice($fields, $currentIndex, 1);
            array_splice($fields, $newIndex, 0, [$field]);
            self::save($id, $fields, $userId, false);
        }
    }

    public static function IsInSchemes(int $entryId, array $allowedSchemeIds): bool
    {
        if (empty($allowedSchemeIds)) return true;

        $db = Database::get();
        $stmt = $db->prepare("SELECT schemeId FROM ENTRIES WHERE id = ?");
        $stmt->execute([$entryId]);
        $entry = $stmt->fetch();

        return $entry && in_array((int)$entry['schemeId'], $allowedSchemeIds);
    }

    private static function save(int $id, array $fields, int $userId, bool $incrementVersion): void
    {
        $db = Database::get();
        $scheme = self::Get($id);
        $newVersion = $incrementVersion ? $scheme['version'] + 1 : $scheme['version'];
        
        $stmt = $db->prepare("UPDATE SCHEMES SET fields = ?, version = ?, modification_timestamp = ?, last_modification_userId = ? WHERE id = ?");
        $stmt->execute([json_encode($fields), $newVersion, time(), $userId, $id]);

        Logs::info('scheme', "Scheme ID {$id} updated to version {$newVersion} by User {$userId}", (string)$id);
    }
}