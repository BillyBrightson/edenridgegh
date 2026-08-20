<?php
/** @var string $content */
use Core\Settings;
$siteName = (string)Settings::get('site_name', 'Eden Ridge');
?><!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($siteName) ?></title></head>
<body style="margin:0;padding:0;background:#f6f3ec;font-family:Helvetica,Arial,sans-serif;color:#1b1c19;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f3ec;padding:28px 12px;">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border:1px solid rgba(27,28,25,0.12);">
        <tr>
          <td style="background:#131f19;padding:22px 28px;">
            <span style="font-family:Georgia,serif;font-size:19px;letter-spacing:.06em;color:#f6f3ec;"><?= e(strtoupper($siteName)) ?></span>
            <div style="font-size:9px;letter-spacing:.2em;text-transform:uppercase;color:#cdae74;margin-top:4px;">Residential Collection</div>
          </td>
        </tr>
        <tr><td style="padding:30px 28px;font-size:15px;line-height:1.7;"><?= $content ?></td></tr>
        <tr>
          <td style="background:#0d1712;padding:18px 28px;font-size:11px;line-height:1.6;color:#a9a493;">
            <?= e((string)Settings::get('contact_address', '')) ?><br>
            <?= e((string)Settings::get('contact_email', '')) ?> · <?= e((string)Settings::get('contact_phone', '')) ?>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
