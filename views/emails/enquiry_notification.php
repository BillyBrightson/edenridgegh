<?php
/** @var array $enquiry */
/** @var string $admin_link */
$fields = [
    'Name'     => $enquiry['name'],
    'Email'    => $enquiry['email'],
    'Phone'    => $enquiry['phone'] ?: '—',
    'Interest' => $enquiry['interest'] ?: '—',
    'Source'   => $enquiry['source_page'] ?: '—',
    'Campaign' => trim(($enquiry['utm_source'] ?? '') . ' ' . ($enquiry['utm_campaign'] ?? '')) ?: '—',
    'Received' => human_date((string)$enquiry['created_at']) . ' UTC',
];
?>
<h2 style="font-family:Georgia,serif;font-size:20px;margin:0 0 6px;">New enquiry from <?= e((string)$enquiry['name']) ?></h2>
<p style="margin:0 0 20px;color:#4a4a45;font-size:14px;">Reply directly to this email to reach the enquirer.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
  <?php foreach ($fields as $label => $value): ?>
    <tr>
      <td style="padding:9px 0;border-bottom:1px solid rgba(27,28,25,0.10);color:#8d8b82;width:34%;"><?= e($label) ?></td>
      <td style="padding:9px 0;border-bottom:1px solid rgba(27,28,25,0.10);"><?= e((string)$value) ?></td>
    </tr>
  <?php endforeach; ?>
</table>
<?php if (!empty($enquiry['message'])): ?>
  <p style="margin:22px 0 6px;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#8d8b82;">Message</p>
  <div style="padding:14px 16px;background:#f6f3ec;border-left:2px solid #b6904f;font-size:14px;line-height:1.7;"><?= nl2br(e((string)$enquiry['message'])) ?></div>
<?php endif; ?>
<?php if (!empty($enquiry['video_request'])): ?>
  <p style="margin:18px 0 0;font-size:13px;color:#b6904f;">This lead requested the video walkthrough.</p>
<?php endif; ?>
<p style="margin:26px 0 0;">
  <a href="<?= e($admin_link) ?>" style="display:inline-block;background:#b6904f;color:#161b13;padding:13px 24px;font-size:12px;letter-spacing:.09em;text-transform:uppercase;text-decoration:none;">Open in the dashboard</a>
</p>
