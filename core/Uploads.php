<?php

namespace Archetype\Core;

/**
 * Uploads service.
 * Manages virtual folders and physical files stored in a flat directory.
 */
class Uploads
{
    /**
     * Lists entries (files and folders) for a specific parent folder.
     * If folderId is null, lists the root.
     */
    public static function List(?int $folderId): array
    {
        $db = Database::get();
        if ($folderId === null) {
            $stmt = $db->query("SELECT * FROM UPLOADS WHERE parentId IS NULL ORDER BY modification_timestamp DESC");
        } else {
            $stmt = $db->prepare("SELECT * FROM UPLOADS WHERE parentId = ? ORDER BY modification_timestamp DESC");
            $stmt->execute([$folderId]);
        }
        
        $results = $stmt->fetchAll();
        foreach ($results as &$row) {
            $row['access'] = json_decode($row['access'], true);
        }
        return $results;
    }

    /**
     * Creates a virtual folder in the database.
     */
    public static function CreateFolder(string $name, ?int $parentId, array $access, int $userId): int
    {
        self::validateAccess($access);
        self::validateParent($parentId);

        $db = Database::get();
        $now = time();

        // filepath is NULL for folders
        $stmt = $db->prepare("INSERT INTO UPLOADS 
            (name, filepath, access, mime, extension, parentId, creation_timestamp, modification_timestamp, last_modification_userId) 
            VALUES (?, NULL, ?, NULL, NULL, ?, ?, ?, ?)");
        
        $stmt->execute([
            $name,
            json_encode($access),
            $parentId,
            $now,
            $now,
            $userId
        ]);

        $id = (int)$db->lastInsertId();
        Logs::info('uploads', "Folder created: {$name} (ID: {$id}) by User {$userId}");
        
        return $id;
    }

    /**
     * Handles physical file upload and database registration.
     */
    public static function Upload(array $file, string $name, ?int $parentId, array $access, int $userId): int
    {
        self::validateAccess($access);
        self::validateParent($parentId);

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception("File upload failed with error code " . $file['error']);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        // Use mime_content_type for security, fallback to uploaded type
        $mime = mime_content_type($file['tmp_name']) ?: $file['type'];
        
        // Generate unique physical filename to prevent collisions in the flat directory
        $physName = uniqid() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = Env::getUploadsPath() . $physName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new \Exception("Failed to move uploaded file to storage.");
        }

        $db = Database::get();
        $now = time();

        $stmt = $db->prepare("INSERT INTO UPLOADS 
            (name, filepath, access, mime, extension, parentId, creation_timestamp, modification_timestamp, last_modification_userId) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $name,
            $physName,
            json_encode($access),
            $mime,
            $ext,
            $parentId,
            $now,
            $now,
            $userId
        ]);

        $id = (int)$db->lastInsertId();
        Logs::info('uploads', "File uploaded: {$name} (ID: {$id}) by User {$userId}");

        return $id;
    }

    /**
     * Replaces the physical file of an existing entry.
     */
    public static function Replace(int $fileId, array $file, int $userId): void
    {
        $current = self::getById($fileId);
        if (!$current) throw new \Exception("File entry not found");
        if ($current['filepath'] === null) throw new \Exception("Cannot replace a folder with a file.");

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception("File upload failed with error code " . $file['error']);
        }

        // Remove old physical file
        $oldPath = Env::getUploadsPath() . $current['filepath'];
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }

        // Process new file
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mime = mime_content_type($file['tmp_name']) ?: $file['type'];
        $physName = uniqid() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = Env::getUploadsPath() . $physName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new \Exception("Failed to move uploaded file.");
        }

        $db = Database::get();
        $stmt = $db->prepare("UPDATE UPLOADS SET filepath = ?, mime = ?, extension = ?, modification_timestamp = ?, last_modification_userId = ? WHERE id = ?");
        $stmt->execute([$physName, $mime, $ext, time(), $userId, $fileId]);

        Logs::info('uploads', "File replaced: ID {$fileId} by User {$userId}");
    }

    /**
     * Removes a file or folder.
     * If it is a folder, children are moved to root (parentId = NULL) as per specs.
     */
    public static function Remove(int $fileId): void
    {
        $target = self::getById($fileId);
        if (!$target) return; // Already gone

        $db = Database::get();

        if ($target['filepath'] === null) {
            // Logic for Folder: Unlink children (orphans go to root)
            $stmt = $db->prepare("UPDATE UPLOADS SET parentId = NULL WHERE parentId = ?");
            $stmt->execute([$fileId]);
        } else {
            // Logic for File: Delete physical file
            $path = Env::getUploadsPath() . $target['filepath'];
            if (file_exists($path)) {
                unlink($path);
            }
        }

        // Delete the entry itself
        $stmt = $db->prepare("DELETE FROM UPLOADS WHERE id = ?");
        $stmt->execute([$fileId]);

        Logs::info('uploads', "Item deleted: ID {$fileId}");
    }

    /**
     * Edits metadata (name, access).
     */
    public static function Edit(int $fileId, string $name, array $access, int $userId): void
    {
        self::validateAccess($access);
        
        $current = self::getById($fileId);
        if (!$current) throw new \Exception("Item not found");

        $db = Database::get();
        $stmt = $db->prepare("UPDATE UPLOADS SET name = ?, access = ?, modification_timestamp = ?, last_modification_userId = ? WHERE id = ?");
        $stmt->execute([$name, json_encode($access), time(), $userId, $fileId]);

        Logs::info('uploads', "Item updated: ID {$fileId} by User {$userId}");
    }

    /**
     * Retrieves an entry by ID.
     */
    public static function Get(int $fileId): ?array
    {
        $entry = self::getById($fileId);
        if ($entry) {
            $entry['access'] = json_decode($entry['access'], true);
        }
        return $entry;
    }

    // --- Helpers ---

    private static function getById(int $id): ?array
    {
        $db = Database::get();
        $stmt = $db->prepare("SELECT * FROM UPLOADS WHERE id = ?");
        $stmt->execute([$id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    private static function validateAccess(array $access): void
    {
        $validRoles = ['PUBLIC', 'EDITOR', 'ADMIN'];
        foreach ($access as $role) {
            if (!in_array($role, $validRoles)) {
                throw new \Exception("Invalid access role: {$role}");
            }
        }
    }

    private static function validateParent(?int $parentId): void
    {
        if ($parentId) {
            $parent = self::getById($parentId);
            if (!$parent) throw new \Exception("Parent folder not found");
            if ($parent['filepath'] !== null) throw new \Exception("Parent ID is not a folder");
        }
    }

    public static function GetFileStats(int $id): ?array
    {
        $file = self::getById($id);
        if (!$file) return null;

        if ($file['filepath'] === null) {
            return ['mime' => 'application/x-directory', 'size' => 0];
        }

        $path = Env::getUploadsPath() . $file['filepath'];
        $size = file_exists($path) ? filesize($path) : 0;

        return [
            'mime' => $file['mime'],
            'size' => $size
        ];
    }
}