<?php
/** @var array $stats */
/** @var array $recent */
/** @var array $edits */
/** @var array $drafts */
use Core\{Activity, AdminUi, Config, Enquiry, Schema};
?>
<div class="page-head">
  <div>
    <h2>Welcome back</h2>
    <p>Everything on the public site is editable from here — the site is at <a href="<?= e((string)Config::get('site_url', '/')) ?>" target="_blank" rel="noopener" style="border-bottom:1px solid var(--a-line-strong);"><?= e((string)Config::get('site_url', '')) ?></a>.</p>
  </div>
</div>

<div class="grid cols-4">
  <div class="card stat"><div class="n"><?= (int)$stats['new'] ?></div><div class="l">New enquiries</div></div>
  <div class="card stat"><div class="n"><?= (int)$stats['today'] ?></div><div class="l">Today</div></div>
  <div class="card stat"><div class="n"><?= (int)$stats['week'] ?></div><div class="l">Last 7 days</div></div>
  <div class="card stat"><div class="n"><?= (int)$stats['video'] ?></div><div class="l">Video requests</div></div>
</div>

<?php if ($drafts): ?>
  <div class="card" style="margin-top:18px;">
    <div class="card-head"><h3>Unpublished drafts</h3></div>
    <div class="card-body tight">
      <p style="font-size:13px;color:var(--a-ink-soft);margin:8px 0 12px;">These sections have saved edits that are not on the public site yet.</p>
      <div class="actions-row">
        <?php foreach ($drafts as $draft): ?>
          <a class="btn btn-ghost btn-sm" href="/pages/home?section=<?= e((string)$draft['key']) ?>">
            <?= e(Schema::title((string)$draft['key'])) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="grid cols-2" style="margin-top:18px;">
  <div class="card">
    <div class="card-head">
      <h3>Latest enquiries</h3>
      <div class="spacer"></div>
      <a class="btn btn-ghost btn-sm" href="/enquiries">Open inbox</a>
    </div>
    <?php if ($recent): ?>
      <div class="table-wrap">
        <table class="data">
          <tbody>
          <?php foreach ($recent as $row): ?>
            <tr>
              <td>
                <a class="row-link" href="/enquiries/<?= (int)$row['id'] ?>"><?= e((string)$row['name']) ?></a>
                <div style="font-size:12px;color:var(--a-ink-faint);"><?= e((string)$row['interest']) ?></div>
              </td>
              <td style="text-align:right;">
                <span class="pill pill-<?= e((string)$row['status']) ?>"><?= e(Enquiry::STATUSES[$row['status']] ?? $row['status']) ?></span>
                <div style="font-size:12px;color:var(--a-ink-faint);margin-top:4px;"><?= e(human_date((string)$row['created_at'], 'j M, H:i')) ?></div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty"><h4>No enquiries yet</h4><p>They will appear here the moment someone submits the form.</p></div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-head"><h3>Recent activity</h3></div>
    <?php if ($edits): ?>
      <div class="table-wrap">
        <table class="data">
          <tbody>
          <?php foreach ($edits as $entry): ?>
            <tr>
              <td>
                <?= e(Activity::label((string)$entry['action'])) ?>
                <div style="font-size:12px;color:var(--a-ink-faint);"><?= e((string)($entry['user_name'] ?? 'System')) ?></div>
              </td>
              <td style="text-align:right;font-size:12px;color:var(--a-ink-faint);"><?= e(human_date((string)$entry['created_at'], 'j M, H:i')) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty"><p>Nothing logged yet.</p></div>
    <?php endif; ?>
  </div>
</div>

<div class="card" style="margin-top:18px;">
  <div class="card-head"><h3>Quick links</h3></div>
  <div class="card-body">
    <div class="actions-row">
      <a class="btn btn-ghost btn-sm" href="/pages/home"><?= AdminUi::glyph('eye') ?> Edit the home page</a>
      <a class="btn btn-ghost btn-sm" href="/media">Swap an image</a>
      <a class="btn btn-ghost btn-sm" href="/gallery">Curate the gallery</a>
      <a class="btn btn-ghost btn-sm" href="/settings">Contact details</a>
      <a class="btn btn-ghost btn-sm" href="<?= e((string)Config::get('site_url', '/')) ?>" target="_blank" rel="noopener"><?= AdminUi::glyph('external') ?> View the site</a>
    </div>
  </div>
</div>
