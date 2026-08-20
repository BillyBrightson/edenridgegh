<?php
/** @var array $entries */
/** @var int $total */
/** @var int $page */
/** @var int $limit */
use Core\Activity;
$pages = (int)max(1, ceil($total / $limit));
?>
<div class="page-head">
  <div>
    <h2>Activity log</h2>
    <p>Every content change, sign-in and administrative action, most recent first.</p>
  </div>
</div>

<div class="card">
  <?php if ($entries): ?>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Detail</th></tr></thead>
        <tbody>
        <?php foreach ($entries as $entry): ?>
          <tr>
            <td style="white-space:nowrap;color:var(--a-ink-faint);"><?= e(human_date((string)$entry['created_at'])) ?></td>
            <td><?= e((string)($entry['user_name'] ?? 'System')) ?></td>
            <td><?= e(Activity::label((string)$entry['action'])) ?></td>
            <td style="color:var(--a-ink-faint);font-size:12.5px;">
              <?= e(trim(((string)($entry['entity'] ?? '')) . ' ' . ((string)($entry['entity_id'] ?? '')))) ?>
              <?php if (!empty($entry['meta'])): ?>
                <code style="font-size:11.5px;"><?= e(mb_substr((string)$entry['meta'], 0, 120)) ?></code>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($pages > 1): ?>
      <div class="pagination">
        <span>Page <?= $page ?> of <?= $pages ?></span>
        <div class="spacer"></div>
        <?php if ($page > 1): ?><a href="/activity?page=<?= $page - 1 ?>">Previous</a><?php endif; ?>
        <?php if ($page < $pages): ?><a href="/activity?page=<?= $page + 1 ?>">Next</a><?php endif; ?>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <div class="empty"><h4>Nothing logged yet</h4></div>
  <?php endif; ?>
</div>
