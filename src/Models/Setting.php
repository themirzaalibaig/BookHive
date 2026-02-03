<?php

namespace BookHive\Models;

use BookHive\Config\Database;

class Setting
{
    private Database $db;
    private static ?array $cache = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get a setting value
     */
    public function get(string $key, $default = null)
    {
        if (self::$cache === null) {
            $this->loadCache();
        }

        if (!isset(self::$cache[$key])) {
            return $default;
        }

        $setting = self::$cache[$key];
        
        // Convert based on type
        switch ($setting['type']) {
            case 'boolean':
                return (bool) $setting['value'];
            case 'integer':
                return (int) $setting['value'];
            case 'decimal':
                return (float) $setting['value'];
            default:
                return $setting['value'];
        }
    }

    /**
     * Set a setting value
     */
    public function set(string $key, $value): bool
    {
        $existing = $this->db->fetchOne("SELECT * FROM settings WHERE `key` = ?", [$key]);

        if ($existing) {
            $result = $this->db->update('settings', [
                'value' => (string) $value,
                'updated_at' => date('Y-m-d H:i:s')
            ], '`key` = ?', [$key]) > 0;
        } else {
            $result = $this->db->insert('settings', [
                'key' => $key,
                'value' => (string) $value,
                'type' => $this->detectType($value),
                'updated_at' => date('Y-m-d H:i:s')
            ]) > 0;
        }

        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    /**
     * Get all settings
     */
    public function getAll(): array
    {
        return $this->db->fetchAll("SELECT * FROM settings ORDER BY `key` ASC");
    }

    /**
     * Get settings by category (based on key prefix)
     */
    public function getByCategory(string $prefix): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM settings WHERE `key` LIKE ? ORDER BY `key` ASC",
            [$prefix . '%']
        );
    }

    /**
     * Update multiple settings at once
     */
    public function updateMultiple(array $settings): bool
    {
        $this->db->beginTransaction();

        try {
            foreach ($settings as $key => $value) {
                $this->set($key, $value);
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Load all settings into cache
     */
    private function loadCache(): void
    {
        $settings = $this->db->fetchAll("SELECT * FROM settings");
        self::$cache = [];
        
        foreach ($settings as $setting) {
            self::$cache[$setting['key']] = $setting;
        }
    }

    /**
     * Clear settings cache
     */
    private function clearCache(): void
    {
        self::$cache = null;
    }

    /**
     * Detect value type
     */
    private function detectType($value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        } elseif (is_int($value)) {
            return 'integer';
        } elseif (is_float($value)) {
            return 'decimal';
        } else {
            return 'string';
        }
    }
}
