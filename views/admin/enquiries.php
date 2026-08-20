<?php
/** @var array $rows */
/** @var int $total */
/** @var int $page */
/** @var int $limit */
/** @var array $filters */
/** @var array $interests */
use Core\{AdminUi, Enquiry};
$queryString = static function (array $overrides = []) use ($filters, $page): string {
    return http_build_query(array_filter(array_merge($filters, ['page' => $page], $overrides), fn($v) => $v !== '' && $v !== null));
};
$pages = (int)max(1, ceil($total / $limit));
?>
<div class="page-head">
  <div>
    <h2><?= (string)($filters['trash'] ?? '') === '1' ? 'Trash' : 'Enquiries' ?></h2>
    <p><?= (int)$total ?> record<?= $total === 1 ? '' : 's' ?> matching the current filters.</p>
  </div>
  <div class="spacer"></div>
  <a class="btn btn-ghost btn-sm" href="/enquiries/export?<?= e($queryString(['page' => null])) ?>"><?= AdminUi::glyph('download') ?> Export CSV</a>
  <?php if ((string)($filters['trash'] ?? '') === '1'): ?>
    <a class="btn btn-ghost btn-sm" href="/enquiries">Back to inbox</a>
  <?php else: ?>
    <a class="btn btn-ghost btn-sm" href="/enquiries?trash=1">View trash</a>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-body tight">
    <form method="get" action="/enquiries" class="filters">
      <?php if ((string)($filters['trash'] ?? '') === '1'): ?><input type="hidden" name="trash" value="1"><?php endif; ?>
      <div class="field grow">
        <label class="field-label" for="q">Search</label>
        <input type="search" id="q" name="q" value="<?= e((string)$filters['q']) ?>" placeholder="Name, email, phone or message">
      </div>
      <div class="field">
        <label class="field-label" for="status">Status</label>
        <select id="status" name="status">
          <option value="">All</option>
          <?php foreach (Enquiry::STATUSES as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label class="field-label" for="interest">Interest</label>
        <select id="interest" name="interest">
          <option value="">All</option>
          <?php foreach ($interests as $interest): ?>
            <option value="<?= e((string)$interest) ?>" <?= $filters['interest'] === $interest ? 'selected' : '' ?>><?= e((string)$interest) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label class="field-label" for="from">From</label><input type="date" id="from" name="from" value="<?= e((string)$filters['from']) ?>"></div>
      <div class="field"><label class="field-label" for="to">To</label><input type="date" id="to" name="to" value="<?= e((string)$filters['to']) ?>"></div>
      <button type="submit" class="btn btn-primary btn-sm">Filter</button>
      <a class="btn btn-ghost btn-sm" href="/enquiries">Clear</a>
    </form>
  </div>

  <?php if ($rows): ?>
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr><th>Date</th><th>Name</th><th>Interest</th><th>Phone</th><th>Email</th><th>Status</th><th>Source</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td style="white-space:nowrap;color:var(--a-ink-faint);"><?= e(human_date((string)$row['created_at'], 'j M Y')) ?></td>
            <td>
              <a class="row-link" href="/enquiries/<?= (int)$row['id'] ?>"><?= e((string)$row['name']) ?></a>
              <?php if ((int)$row['video_request'] === 1): ?>
                <span class="pill pill-qualified" style="margin-left:6px;">video</span>
              <?php endif; ?>
            </td>
            <td><?= e((string)($row['interest'] ?? '—')) ?></td>
            <td><?= $row['phone'] ? '<a href="tel:' . e(phone_digits((string)$row['phone'])) . '">' . e((string)$row['phone']) . '</a>' : '—' ?></td>
            <td><a href="mailto:<?= e((string)$row['email']) ?>"><?= e((string)$row['email']) ?></a></td>
            <td><span class="pill pill-<?= e((string)$row['status']) ?>"><?= e(Enquiry::STATUSES[$row['status']] ?? $row['status']) ?></span></td>
            <td style="color:var(--a-ink-faint);"><?= e((string)($row['source_page'] ?? '—')) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($pages > 1): ?>
      <div class="pagination">
        <span>Page <?= $page ?> of <?= $pages ?></span>
        <div class="spacer"></div>
        <?php if ($page > 1): ?><a href="/enquiries?<?= e($queryString(['page' => $page - 1])) ?>">Previous</a><?php endif; ?>
        <?php if ($page < $pages): ?><a href="/enquiries?<?= e($queryString(['page' => $page + 1])) ?>">Next</a><?php endif; ?>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <div class="empty">
      <h4>Nothing here</h4>
      <p>No enquiries match these filters yet.</p>
    </div>
  <?php endif; ?>
</div>
