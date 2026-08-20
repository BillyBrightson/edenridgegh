<?php
declare(strict_types=1);

namespace Core;

/**
 * Section content: published documents, drafts and revision history.
 */
final class Content
{
    public const MAX_REVISIONS = 30;

    private static array $cache = [];

    public static function page(string $slug = 'home'): ?array
    {
        return DB::first('SELECT * FROM pages WHERE slug = ?', [$slug]);
    }

    /** All sections for a page, in display order. */
    public static function sections(string $slug = 'home', bool $publishedOnly = true): array
    {
        $sql = 'SELECT s.* FROM sections s
                JOIN pages p ON p.id = s.page_id
                WHERE p.slug = ?';
        if ($publishedOnly) {
            $sql .= ' AND s.is_published = 1';
        }
        $sql .= ' ORDER BY s.sort_order ASC, s.id ASC';
        return DB::all($sql, [$slug]);
    }

    public static function find(string $key, string $slug = 'home'): ?array
    {
        return DB::first(
            'SELECT s.* FROM sections s JOIN pages p ON p.id = s.page_id WHERE p.slug = ? AND s.key = ?',
            [$slug, $key]
        );
    }

    /** Published content document for a section, merged over the schema blank. */
    public static function get(string $key, string $slug = 'home'): array
    {
        $cacheKey = $slug . ':' . $key;
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }
        $section = self::find($key, $slug);
        $content = $section ? self::decode($section['content']) : [];
        return self::$cache[$cacheKey] = array_replace(Schema::blank(Schema::fields($key)), $content);
    }

    /** Draft content if one exists, otherwise the published document. */
    public static function draft(array $section): array
    {
        $raw = $section['draft_content'] !== null && $section['draft_content'] !== ''
            ? $section['draft_content']
            : $section['content'];
        $content = self::decode((string)$raw);
        return array_replace(Schema::blank(Schema::fields((string)$section['key'])), $content);
    }

    public static function hasDraft(array $section): bool
    {
        return $section['draft_content'] !== null
            && $section['draft_content'] !== ''
            && $section['draft_content'] !== $section['content'];
    }

    public static function decode(string $json): array
    {
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    public static function encode(array $content): string
    {
        return (string)json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** Save a draft without touching the live site. */
    public static function saveDraft(int $sectionId, array $content, int $userId): void
    {
        DB::update('sections', [
            'draft_content' => self::encode($content),
            'updated_by'    => $userId,
            'updated_at'    => now(),
        ], 'id = :id', ['id' => $sectionId]);
        Activity::log($userId, 'section.save', 'section', $sectionId);
    }

    /** Promote a draft (or an explicit document) to live, keeping a revision. */
    public static function publish(int $sectionId, array $content, int $userId, ?string $note = null): void
    {
        DB::transaction(function () use ($sectionId, $content, $userId, $note) {
            $current = DB::first('SELECT content FROM sections WHERE id = ?', [$sectionId]);
            if ($current && $current['content'] !== '') {
                DB::insert('section_revisions', [
                    'section_id' => $sectionId,
                    'content'    => $current['content'],
                    'user_id'    => $userId,
                    'note'       => $note,
                    'created_at' => now(),
                ]);
            }
            DB::update('sections', [
                'content'       => self::encode($content),
                'draft_content' => null,
                'updated_by'    => $userId,
                'updated_at'    => now(),
            ], 'id = :id', ['id' => $sectionId]);
            self::trimRevisions($sectionId);
        });
        Cache::flush();
        self::$cache = [];
        Activity::log($userId, 'section.publish', 'section', $sectionId);
    }

    public static function discardDraft(int $sectionId): void
    {
        DB::update('sections', ['draft_content' => null, 'updated_at' => now()], 'id = :id', ['id' => $sectionId]);
    }

    public static function revisions(int $sectionId, int $limit = self::MAX_REVISIONS): array
    {
        return DB::all(
            'SELECT r.*, u.name AS user_name FROM section_revisions r
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.section_id = ? ORDER BY r.created_at DESC, r.id DESC LIMIT ?',
            [$sectionId, $limit]
        );
    }

    public static function revert(int $sectionId, int $revisionId, int $userId): bool
    {
        $rev = DB::first('SELECT * FROM section_revisions WHERE id = ? AND section_id = ?', [$revisionId, $sectionId]);
        if (!$rev) {
            return false;
        }
        self::publish($sectionId, self::decode((string)$rev['content']), $userId, 'Reverted to an earlier revision');
        Activity::log($userId, 'section.revert', 'section', $sectionId, ['revision_id' => $revisionId]);
        return true;
    }

    private static function trimRevisions(int $sectionId): void
    {
        DB::run(
            'DELETE FROM section_revisions WHERE section_id = :s AND id NOT IN (
                SELECT id FROM section_revisions WHERE section_id = :s ORDER BY created_at DESC, id DESC LIMIT :n
             )',
            ['s' => $sectionId, 'n' => self::MAX_REVISIONS]
        );
    }

    public static function setPublished(int $sectionId, bool $published, int $userId): void
    {
        DB::update('sections', ['is_published' => $published ? 1 : 0, 'updated_at' => now()], 'id = :id', ['id' => $sectionId]);
        Cache::flush();
        Activity::log($userId, 'section.toggle', 'section', $sectionId, ['is_published' => $published]);
    }

    /** @param int[] $orderedIds */
    public static function reorder(array $orderedIds, int $userId): void
    {
        DB::transaction(function () use ($orderedIds) {
            foreach (array_values($orderedIds) as $i => $id) {
                DB::update('sections', ['sort_order' => $i + 1], 'id = :id', ['id' => (int)$id]);
            }
        });
        Cache::flush();
        Activity::log($userId, 'section.reorder', 'section', null, ['order' => $orderedIds]);
    }

    /** Simple editable pages (privacy, terms). */
    public static function simplePage(string $slug): ?array
    {
        $page = self::page($slug);
        if (!$page) {
            return null;
        }
        $section = self::find('body', $slug);
        $page['body'] = $section ? (string)(self::decode((string)$section['content'])['body'] ?? '') : '';
        return $page;
    }

    public static function flushCache(): void
    {
        self::$cache = [];
    }
}
