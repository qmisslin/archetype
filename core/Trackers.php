<?php

namespace Archetype\Core;

/**
 * Trackers service.
 * Manages tracking links logic.
 */
class Trackers
{
    /**
     * Lists all trackers.
     */
    public static function List(): array
    {
        $db = Database::get();
        return $db->query("SELECT * FROM TRACKERS ORDER BY creation_timestamp DESC")->fetchAll();
    }

    /**
     * Creates a new tracker.
     */
    public static function Create(string $name, ?string $description, int $userId): int
    {
        $db = Database::get();
        $now = time();

        $stmt = $db->prepare("INSERT INTO TRACKERS 
            (name, description, creation_timestamp, modification_timestamp, last_modification_userId) 
            VALUES (?, ?, ?, ?, ?)");
        
        $stmt->execute([$name, $description, $now, $now, $userId]);
        
        $id = (int)$db->lastInsertId();
        Logs::info('trackers', "Tracker created: {$name} (ID: {$id}) by User {$userId}");
        
        return $id;
    }

    /**
     * Updates an existing tracker.
     */
    public static function Edit(int $id, string $name, ?string $description, int $userId): void
    {
        $db = Database::get();
        
        // Ensure existance
        $check = $db->prepare("SELECT id FROM TRACKERS WHERE id = ?");
        $check->execute([$id]);
        if (!$check->fetch()) {
            throw new \Exception("Tracker not found");
        }

        $stmt = $db->prepare("UPDATE TRACKERS 
            SET name = ?, description = ?, modification_timestamp = ?, last_modification_userId = ? 
            WHERE id = ?");
        
        $stmt->execute([$name, $description, time(), $userId, $id]);
        Logs::info('trackers', "Tracker updated: ID {$id} by User {$userId}");
    }

    /**
     * Deletes a tracker.
     */
    public static function Remove(int $id): void
    {
        $db = Database::get();
        $stmt = $db->prepare("DELETE FROM TRACKERS WHERE id = ?");
        $stmt->execute([$id]);
        
        Logs::info('trackers', "Tracker deleted: ID {$id}");
    }
}