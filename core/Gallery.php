<?php
declare(strict_types=1);

namespace Core;

final class Gallery
{
    public static function categories(): array
    {
        return DB::all('SELECT * FROM gallery_categories ORDER BY sort_order ASC, id ASC');
    }

    public static function items(bool $publishedOnly = true): array
    {
        $sql = 'SELECT gi.*, gc.slug AS category_slug, gc.label AS category_label,
                       m.alt AS media_alt, m.filename AS media_filename
                FROM gallery_items gi
                LEFT JOIN gallery_categories gc ON gc.id = gi.category_id
                JOIN media m ON m.id = gi.media_id';
        if ($publishedOnly) {
            $sql .= ' WHERE gi.is_published = 1';
        }
        $sql .= ' ORDER BY gi.sort_order ASC, gi.id ASC';
        return DB::all($sql);
    }

    public static function hasMedia(int $mediaId): bool
    {
        return (bool)DB::value('SELECT COUNT(*) FROM gallery_items WHERE media_id = ?', [$mediaId]);
    }

    public static function addItem(int $mediaId, ?int $categoryId = null, string $caption = ''): int
    {
        $max = (int)DB::value('SELECT COALESCE(MAX(sort_order), 0) FROM gallery_items');
        return DB::insert('gallery_items', [
            'media_id'     => $mediaId,
            'category_id'  => $categoryId,
            'title'        => '',
            'caption'      => $caption,
            'sort_order'   => $max + 1,
            'is_published' => 1,
        ]);
    }

    public static function saveItem(int $id, array $data): void
    {
        DB::update('gallery_items', [
            'category_id'  => $data['category_id'] !== '' ? (int)$data['category_id'] : null,
            'caption'      => Sanitizer::text((string)($data['caption'] ?? '')),
            'sort_order'   => (int)($data['sort_order'] ?? 0),
            'is_published' => !empty($data['is_published']) ? 1 : 0,
        ], 'id = :id', ['id' => $id]);
    }

    public static function deleteItem(int $id): void
    {
        DB::delete('gallery_items', 'id = ?', [$id]);
    }

    public static function saveCategory(?int $id, string $label, int $sortOrder): int
    {
        $slug = str_slug($label);
        if ($id) {
            DB::update('gallery_categories', ['label' => $label, 'slug' => $slug, 'sort_order' => $sortOrder], 'id = :id', ['id' => $id]);
            return $id;
        }
        $existing = DB::first('SELECT id FROM gallery_categories WHERE slug = ?', [$slug]);
        if ($existing) {
            return (int)$existing['id'];
        }
        return DB::insert('gallery_categories', ['slug' => $slug, 'label' => $label, 'sort_order' => $sortOrder]);
    }

    public static function deleteCategory(int $id): void
    {
        DB::delete('gallery_categories', 'id = ?', [$id]);
    }
}
