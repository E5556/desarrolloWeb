<?php
/* Analytics automático (Y1 + Y2)
   Incluir en el <head> de cada página pública.
   Lee ga4_measurement_id y meta_pixel_id de settings.
   Emite eventos e-commerce: view_item, add_to_cart, purchase.
*/
$_an_q = mysqli_query($con, "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('ga4_measurement_id','meta_pixel_id')");
$_an = [];
while ($_an_q && $r = mysqli_fetch_assoc($_an_q)) $_an[$r['setting_key']] = $r['setting_value'];
$_ga4_id    = trim($_an['ga4_measurement_id'] ?? '');
$_pixel_id  = trim($_an['meta_pixel_id'] ?? '');
?>
<?php if ($_ga4_id !== ''): ?>
<!-- Google Analytics 4 (Y1) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($_ga4_id); ?>"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '<?php echo htmlspecialchars($_ga4_id); ?>', {
    page_title: document.title,
    page_location: window.location.href
});
// Helper global para eventos e-commerce
window.psGA4Event = function(name, params) {
    if (typeof gtag === 'function') gtag('event', name, params);
};
</script>
<?php endif; ?>

<?php if ($_pixel_id !== ''): ?>
<!-- Meta Pixel (Y2) -->
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '<?php echo htmlspecialchars($_pixel_id); ?>');
fbq('track', 'PageView');
window.psFBEvent = function(name, params) {
    if (typeof fbq === 'function') fbq(name, params || {});
};
</script>
<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?php echo htmlspecialchars($_pixel_id); ?>&ev=PageView&noscript=1"/></noscript>
<?php endif; ?>
