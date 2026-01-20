<?php
namespace ORS;

/**
 * Base Model Class
 */
abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    /**
     * Find by ID
     */
    public static function find(int $id): ?array
    {
        return Database::fetchOne(
            sprintf('SELECT * FROM `%s` WHERE `%s` = ?', static::$table, static::$primaryKey),
            [$id]
        );
    }

    /**
     * Find all records
     */
    public static function all(string $orderBy = 'id DESC'): array
    {
        return Database::fetchAll(
            sprintf('SELECT * FROM `%s` ORDER BY %s', static::$table, $orderBy)
        );
    }

    /**
     * Find by conditions
     */
    public static function where(array $conditions, string $orderBy = 'id DESC'): array
    {
        $whereParts = [];
        $params = [];

        foreach ($conditions as $column => $value) {
            if ($value === null) {
                $whereParts[] = "`{$column}` IS NULL";
            } else {
                $whereParts[] = "`{$column}` = ?";
                $params[] = $value;
            }
        }

        $sql = sprintf(
            'SELECT * FROM `%s` WHERE %s ORDER BY %s',
            static::$table,
            implode(' AND ', $whereParts),
            $orderBy
        );

        return Database::fetchAll($sql, $params);
    }

    /**
     * Find first by conditions
     */
    public static function findWhere(array $conditions): ?array
    {
        $results = static::where($conditions, 'id ASC');
        return $results[0] ?? null;
    }

    /**
     * Create new record
     */
    public static function create(array $data): int
    {
        return Database::insert(static::$table, $data);
    }

    /**
     * Update record by ID
     */
    public static function updateById(int $id, array $data): int
    {
        return Database::update(
            static::$table,
            $data,
            static::$primaryKey . ' = ?',
            [$id]
        );
    }

    /**
     * Delete record by ID
     */
    public static function deleteById(int $id): int
    {
        return Database::delete(
            static::$table,
            static::$primaryKey . ' = ?',
            [$id]
        );
    }

    /**
     * Count records
     */
    public static function count(array $conditions = []): int
    {
        if (empty($conditions)) {
            return (int) Database::fetchColumn(
                sprintf('SELECT COUNT(*) FROM `%s`', static::$table)
            );
        }

        $whereParts = [];
        $params = [];

        foreach ($conditions as $column => $value) {
            if ($value === null) {
                $whereParts[] = "`{$column}` IS NULL";
            } else {
                $whereParts[] = "`{$column}` = ?";
                $params[] = $value;
            }
        }

        return (int) Database::fetchColumn(
            sprintf('SELECT COUNT(*) FROM `%s` WHERE %s', static::$table, implode(' AND ', $whereParts)),
            $params
        );
    }

    /**
     * Check if record exists
     */
    public static function exists(int $id): bool
    {
        return static::find($id) !== null;
    }

    /**
     * Get table name
     */
    public static function getTable(): string
    {
        return static::$table;
    }
}

// Concrete Model Classes

class Project extends Model
{
    protected static string $table = 'ors_project';
}

class Task extends Model
{
    protected static string $table = 'ors_task';

    /**
     * Get tasks by project with today filter option
     */
    public static function getByProject(?int $projectId, bool $todayOnly = false): array
    {
        if ($projectId) {
            $sql = 'SELECT * FROM ors_task WHERE project_id = ?';
            $params = [$projectId];
        } else {
            $sql = 'SELECT * FROM ors_task WHERE 1=1';
            $params = [];
        }

        if ($todayOnly) {
            $sql .= ' AND DATE(created_at) = CURDATE()';
        }

        $sql .= ' ORDER BY created_at DESC';

        return Database::fetchAll($sql, $params);
    }

    /**
     * Get today's tasks for all projects
     */
    public static function getToday(): array
    {
        return Database::fetchAll(
            'SELECT t.*, p.project_name FROM ors_task t
             LEFT JOIN ors_project p ON t.project_id = p.id
             WHERE DATE(t.created_at) = CURDATE()
             ORDER BY t.created_at DESC'
        );
    }

    /**
     * Get template tasks, optionally filtered by project type
     */
    public static function getTemplates(?string $projectType = null): array
    {
        if ($projectType) {
            // Filter by project type: match NULL (all types) or containing the specific type
            return Database::fetchAll(
                'SELECT * FROM ors_task WHERE template_flag = 1
                 AND (project_types IS NULL OR project_types = "" OR FIND_IN_SET(?, project_types) > 0)
                 ORDER BY phase_code, title',
                [$projectType]
            );
        }
        return Database::fetchAll(
            'SELECT * FROM ors_task WHERE template_flag = 1 ORDER BY phase_code, title'
        );
    }
}

class Item extends Model
{
    protected static string $table = 'ors_item';

    /**
     * Get template items, optionally filtered by project type
     */
    public static function getTemplates(?string $projectType = null): array
    {
        if ($projectType) {
            return Database::fetchAll(
                'SELECT * FROM ors_item WHERE template_flag = 1
                 AND (project_types IS NULL OR project_types = "" OR FIND_IN_SET(?, project_types) > 0)
                 ORDER BY category, item_name',
                [$projectType]
            );
        }
        return Database::fetchAll(
            'SELECT * FROM ors_item WHERE template_flag = 1 ORDER BY category, item_name'
        );
    }

    /**
     * Search items by name
     */
    public static function search(string $keyword): array
    {
        return Database::fetchAll(
            'SELECT * FROM ors_item WHERE item_name LIKE ? ORDER BY item_name',
            ['%' . $keyword . '%']
        );
    }
}

class Purchase extends Model
{
    protected static string $table = 'ors_purchase';

    /**
     * Get purchases by project
     */
    public static function getByProject(?int $projectId, bool $todayOnly = false): array
    {
        $sql = 'SELECT p.*, i.item_name as linked_item_name, v.vendor_name
                FROM ors_purchase p
                LEFT JOIN ors_item i ON p.item_id = i.id
                LEFT JOIN ors_vendor v ON p.vendor_id = v.id';

        if ($projectId) {
            $sql .= ' WHERE p.project_id = ?';
            $params = [$projectId];
        } else {
            $sql .= ' WHERE 1=1';
            $params = [];
        }

        if ($todayOnly) {
            $sql .= ' AND DATE(p.created_at) = CURDATE()';
        }

        $sql .= ' ORDER BY p.created_at DESC';

        return Database::fetchAll($sql, $params);
    }

    /**
     * Get today's purchases
     */
    public static function getToday(): array
    {
        return Database::fetchAll(
            'SELECT p.*, i.item_name as linked_item_name, pr.project_name
             FROM ors_purchase p
             LEFT JOIN ors_item i ON p.item_id = i.id
             LEFT JOIN ors_project pr ON p.project_id = pr.id
             WHERE DATE(p.created_at) = CURDATE()
             ORDER BY p.created_at DESC'
        );
    }

    /**
     * Calculate total EUR for project
     */
    public static function getProjectTotalEur(?int $projectId): float
    {
        $result = Database::fetchColumn(
            'SELECT SUM(total_price_eur) FROM ors_purchase WHERE project_id = ?',
            [$projectId]
        );
        return (float) ($result ?? 0);
    }
}

class Vendor extends Model
{
    protected static string $table = 'ors_vendor';
}

class Lesson extends Model
{
    protected static string $table = 'ors_lesson';

    /**
     * Get template lessons, optionally filtered by project type
     */
    public static function getTemplates(?string $projectType = null): array
    {
        if ($projectType) {
            return Database::fetchAll(
                'SELECT * FROM ors_lesson WHERE template_flag = 1
                 AND (project_types IS NULL OR project_types = "" OR FIND_IN_SET(?, project_types) > 0)
                 ORDER BY category, title',
                [$projectType]
            );
        }
        return Database::fetchAll(
            'SELECT * FROM ors_lesson WHERE template_flag = 1 ORDER BY category, title'
        );
    }
}

class CheckItem extends Model
{
    protected static string $table = 'ors_check_item';

    /**
     * Get check items by project
     */
    public static function getByProject(int $projectId): array
    {
        return Database::fetchAll(
            'SELECT c.*, l.title as lesson_title
             FROM ors_check_item c
             LEFT JOIN ors_lesson l ON c.lesson_id = l.id
             WHERE c.project_id = ?
             ORDER BY c.check_date, c.id',
            [$projectId]
        );
    }
}

class Phase extends Model
{
    protected static string $table = 'ors_phase';

    /**
     * Get all phases ordered
     */
    public static function getAllOrdered(): array
    {
        return Database::fetchAll('SELECT * FROM ors_phase ORDER BY sort_order');
    }
}

class TemplateTag extends Model
{
    protected static string $table = 'ors_template_tag';

    /**
     * Get tags for entity
     */
    public static function getForEntity(string $entityType, int $entityId): array
    {
        return Database::fetchAll(
            'SELECT tag_name FROM ors_template_tag WHERE entity_type = ? AND entity_id = ?',
            [$entityType, $entityId]
        );
    }

    /**
     * Set tags for entity (replaces existing)
     */
    public static function setForEntity(string $entityType, int $entityId, array $tags): void
    {
        // Delete existing
        Database::delete('ors_template_tag', 'entity_type = ? AND entity_id = ?', [$entityType, $entityId]);

        // Insert new
        foreach ($tags as $tag) {
            $tag = trim($tag);
            if ($tag !== '') {
                Database::insert('ors_template_tag', [
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'tag_name' => $tag
                ]);
            }
        }
    }
}

class FxRate extends Model
{
    protected static string $table = 'ors_fx_rate';

    /**
     * Get latest rate
     */
    public static function getLatest(string $fromCurrency, string $toCurrency = 'EUR'): ?float
    {
        $result = Database::fetchColumn(
            'SELECT rate FROM ors_fx_rate WHERE from_currency = ? AND to_currency = ? ORDER BY rate_date DESC LIMIT 1',
            [$fromCurrency, $toCurrency]
        );
        return $result ? (float) $result : null;
    }
}
