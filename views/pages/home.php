<?php
/** @var array $sections rows from the sections table */
use Core\{Content, View, Schema};
$rendered = [];
foreach ($sections as $section) {
    $key = (string)$section['key'];
    if (!View::exists('sections/' . $key)) {
        continue;
    }
    $rendered[$key] = View::render('sections/' . $key, [
        'c'       => array_replace(Schema::blank(Schema::fields($key)), Content::decode((string)$section['content'])),
        'section' => $section,
    ]);
}
// The header sits outside <main>; everything else is page content.
echo $rendered['header'] ?? '';
unset($rendered['header']);
$footer = $rendered['footer'] ?? '';
unset($rendered['footer']);
?>
<main id="main">
<?= implode("\n", $rendered) ?>
</main>
<?= $footer ?>
