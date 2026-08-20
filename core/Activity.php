<?php
declare(strict_types=1);

namespace Core;

final class Activity
{
    public static function log(?int $userId, string $action, ?string $entity = null, ?int $entityId = null, array $meta = []): void
    {
        if (!DB::tableExists('activity_log')) {
            return;
        }
        DB::insert('activity_log', [
            // 0 would break the foreign key; an unattributed action is NULL.
            'user_id'    => $userId ?: null,
            'action'     => $action,
            'entity'     => $entity,
            'entity_id'  => $entityId,
            'meta'       => $meta ? json_encode($meta, JSON_UNESCAPED_SLASHES) : null,
            'ip_hash'    => ip_hash(),
            'created_at' => now(),
        ]);
    }

    public static function recent(int $limit = 50, int $offset = 0): array
    {
        return DB::all(
            'SELECT a.*, u.name AS user_name FROM activity_log a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC, a.id DESC LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
    }

    public static function count(): int
    {
        return (int)DB::value('SELECT COUNT(*) FROM activity_log');
    }

    /** Human label for an action key. */
    public static function label(string $action): string
    {
        return match ($action) {
            'auth.login'        => 'Signed in',
            'auth.logout'       => 'Signed out',
            'auth.throttled'    => 'Blocked sign-in attempt',
            'section.save'      => 'Saved a draft',
            'section.publish'   => 'Published a section',
            'section.revert'    => 'Reverted a section',
            'section.toggle'    => 'Changed section visibility',
            'section.reorder'   => 'Reordered sections',
            'media.upload'      => 'Uploaded media',
            'media.update'      => 'Updated media details',
            'media.delete'      => 'Deleted media',
            'gallery.save'      => 'Updated the gallery',
            'enquiry.status'    => 'Changed an enquiry status',
            'enquiry.note'      => 'Added an enquiry note',
            'enquiry.delete'    => 'Moved an enquiry to trash',
            'enquiry.restore'   => 'Restored an enquiry',
            'enquiry.export'    => 'Exported enquiries',
            'settings.save'     => 'Updated settings',
            'seo.save'          => 'Updated SEO',
            'page.save'         => 'Saved a page',
            'user.create'       => 'Created a user',
            'user.update'       => 'Updated a user',
            'user.delete'       => 'Deleted a user',
            'tools.backup'      => 'Downloaded a backup',
            'tools.restore'     => 'Restored from a backup',
            'tools.cache_clear' => 'Cleared the cache',
            default             => ucfirst(str_replace(['.', '_'], ' ', $action)),
        };
    }
}
