<?php
/** @var string $content */
/** @var string $slug */
/** @var string $path */
use Core\{Settings, Seo, Media, Content, Config};

$favicon   = (int)Settings::get('favicon_media_id', 0);
$faviconUrl = $favicon ? Media::url($favicon, 180) : '';
$hero      = Content::get('hero');
$ga4       = (string)Settings::get('ga4_id', '');
$pixel     = (string)Settings::get('meta_pixel_id', '');
$consent   = Settings::bool('cookie_consent_enabled', false);
$preload   = ($slug ?? 'home') === 'home' ? Media::preload($hero['background_image'] ?? null, '100vw') : '';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#131f19">
<?= Seo::headTags($slug ?? 'home', $path ?? '/') ?>
<?php if ($faviconUrl !== ''): ?>
<link rel="icon" href="<?= e($faviconUrl) ?>">
<?php else: ?>
<link rel="icon" href="data:,">
<?php endif; ?>
<link rel="stylesheet" href="<?= e(asset('/assets/css/tokens.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('/assets/css/site.css')) ?>">
<?= $preload ?>
<?= $jsonld ?? '' ?>
<?php if ($ga4 !== ''): ?>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
(function(){
  var needsConsent = <?= $consent ? 'true' : 'false' ?>;
  if (needsConsent && localStorage.getItem('eden_consent') !== 'granted') return;
  var s = document.createElement('script');
  s.async = true;
  s.src = 'https://www.googletagmanager.com/gtag/js?id=<?= e($ga4) ?>';
  document.head.appendChild(s);
  gtag('js', new Date());
  gtag('config', '<?= e($ga4) ?>');
})();
</script>
<?php endif; ?>
<?php if ($pixel !== ''): ?>
<script>
(function(){
  var needsConsent = <?= $consent ? 'true' : 'false' ?>;
  if (needsConsent && localStorage.getItem('eden_consent') !== 'granted') return;
  !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
  n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
  n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
  t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
  document,'script','https://connect.facebook.net/en_US/fbevents.js');
  fbq('init', '<?= e($pixel) ?>');
  fbq('track', 'PageView');
})();
</script>
<?php endif; ?>
</head>
<body data-consent="<?= $consent ? '1' : '0' ?>">
<a class="skip-link" href="#main">Skip to content</a>
<?= $content ?>
<script src="<?= e(asset('/assets/js/site.js')) ?>" defer></script>
</body>
</html>
