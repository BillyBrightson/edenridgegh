<?php
use Core\{Csrf, Settings, View};
?>
<div class="page-head">
  <div>
    <h2>Appearance</h2>
    <p>The logo, favicon and site name. Colours and typography are fixed by the brand design and live in <code>tokens.css</code>.</p>
  </div>
</div>

<form method="post" action="/appearance" data-guard>
  <?= Csrf::field() ?>
  <div class="card">
    <div class="card-head"><h3>Brand</h3></div>
    <div class="card-body">
      <div class="field">
        <label class="field-label" for="site_name">Site name</label>
        <input type="text" id="site_name" name="site_name" value="<?= e((string)Settings::get('site_name', '')) ?>">
      </div>
      <div class="field">
        <label class="field-label" for="tagline">Tagline</label>
        <p class="hint">Used in search results and social previews when no page-specific description is set.</p>
        <textarea id="tagline" name="tagline" rows="2"><?= e((string)Settings::get('tagline', '')) ?></textarea>
      </div>
      <?= View::render('admin/partials/field', [
          'field' => ['key' => 'logo', 'type' => 'image', 'label' => 'Logo', 'hint' => 'Replaces the monogram and wordmark in the header. A transparent SVG or PNG works best.'],
          'value' => Settings::get('logo_media_id', ''),
          'name'  => 'logo_media_id',
          'id'    => 'logo_media',
          'depth' => 0,
      ]) ?>
      <?= View::render('admin/partials/field', [
          'field' => ['key' => 'favicon', 'type' => 'image', 'label' => 'Favicon', 'hint' => 'The small icon in the browser tab. A square image, 180×180 or larger.'],
          'value' => Settings::get('favicon_media_id', ''),
          'name'  => 'favicon_media_id',
          'id'    => 'favicon_media',
          'depth' => 0,
      ]) ?>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h3>Palette</h3></div>
    <div class="card-body">
      <p style="font-size:13px;color:var(--a-ink-soft);">These are the brand colours used across the site. They are defined once in <code>public/assets/css/tokens.css</code> so a change there flows everywhere.</p>
      <div class="grid cols-4" style="margin-top:14px;">
        <?php foreach ([
          'Pine' => '#131f19', 'Pine deep' => '#0d1712', 'Paper' => '#f6f3ec',
          'Paper warm' => '#efe9dc', 'Ink' => '#1b1c19', 'Ink soft' => '#4a4a45',
          'Brass' => '#b6904f', 'Brass soft' => '#cdae74',
        ] as $label => $hex): ?>
          <div>
            <div style="height:52px;border-radius:var(--a-radius);border:1px solid var(--a-line);background:<?= e($hex) ?>;"></div>
            <div style="font-size:12px;margin-top:6px;"><?= e($label) ?></div>
            <div style="font-size:11px;color:var(--a-ink-faint);"><?= e($hex) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="savebar">
    <span class="state" data-dirty-state>All changes published</span>
    <div class="spacer"></div>
    <button type="submit" class="btn btn-accent">Save appearance</button>
  </div>
</form>
