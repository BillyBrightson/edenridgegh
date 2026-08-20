<?php
/** @var array $page */
/** @var array|null $section */
/** @var string $slug */
use Core\{Config, Content, Csrf};
$body = $section ? (string)(Content::decode((string)$section['content'])['body'] ?? '') : '';
?>
<div class="page-head">
  <div>
    <h2><?= e((string)$page['title']) ?></h2>
    <p>Shown at <code><?= e(rtrim((string)Config::get('site_url', ''), '/') . '/' . $slug) ?></code></p>
  </div>
  <div class="spacer"></div>
  <a class="btn btn-ghost btn-sm" href="<?= e(rtrim((string)Config::get('site_url', ''), '/') . '/' . $slug) ?>" target="_blank" rel="noopener">Preview</a>
</div>

<form method="post" action="/pages/<?= e($slug) ?>/save" data-guard>
  <?= Csrf::field() ?>
  <div class="card">
    <div class="card-body">
      <div class="field">
        <label class="field-label" for="title">Page title</label>
        <input type="text" id="title" name="title" value="<?= e((string)$page['title']) ?>">
      </div>
      <div class="field" data-richtext>
        <span class="field-label">Page content</span>
        <p class="hint">Bold, italics, links and bullet lists are allowed. Anything else is stripped when you save.</p>
        <div class="rt-toolbar">
          <button type="button" data-cmd="bold"><strong>B</strong></button>
          <button type="button" data-cmd="italic"><em>I</em></button>
          <button type="button" data-cmd="insertUnorderedList">&bull; List</button>
          <button type="button" data-cmd="createLink">Link</button>
          <button type="button" data-cmd="removeFormat">Clear</button>
        </div>
        <div class="rt-area" contenteditable="true" role="textbox" aria-multiline="true" aria-label="Page content" style="min-height:340px;"></div>
        <textarea name="body" hidden><?= e($body) ?></textarea>
      </div>
      <div class="field">
        <input type="hidden" name="is_published" value="0">
        <label class="switch">
          <input type="checkbox" name="is_published" value="1" <?= (int)$page['is_published'] === 1 ? 'checked' : '' ?>>
          <span class="track"></span>
          <span>Published</span>
        </label>
      </div>
    </div>
  </div>
  <div class="savebar">
    <span class="state" data-dirty-state>All changes published</span>
    <div class="spacer"></div>
    <button type="submit" class="btn btn-accent">Save &amp; publish</button>
  </div>
</form>
