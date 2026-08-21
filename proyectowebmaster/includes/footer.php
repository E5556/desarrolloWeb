<?php
// Cargar settings del footer desde BD
$_fs = [];
$_fs_res = mysqli_query($con, "SELECT setting_key, setting_value FROM settings WHERE setting_key IN (
    'site_name','footer_tagline',
    'footer_hours_weekday','footer_hours_saturday','footer_hours_sunday',
    'footer_city','footer_phone','footer_email',
    'social_facebook','social_twitter','social_linkedin','social_rss','social_pinterest','social_tiktok',
    'cookie_consent_enabled'
)");
while($_fs_row = mysqli_fetch_assoc($_fs_res)) $_fs[$_fs_row['setting_key']] = $_fs_row['setting_value'];
function _fs($k,$d=''){ global $_fs; return htmlspecialchars($_fs[$k] ?? $d, ENT_QUOTES,'UTF-8'); }
function _fs_raw($k,$d=''){ global $_fs; return $_fs[$k] ?? $d; }
?>
<footer id="footer" class="footer color-bg">
	  <div class="links-social inner-top-sm">
        <div class="container">
            <div class="row">
            	<div class="col-xs-12 col-sm-6 col-md-4">
<div class="contact-info">
    <div class="footer-logo">
        <div class="logo">
            <a href="index2.php"><h3><?php echo _fs('site_name','Jade Zapatillas'); ?></h3></a>
        </div>
    </div>
    <div class="module-body m-t-20">
        <p class="about-us"><?php echo _fs('footer_tagline','Ofrecemos las mejores zapatillas del mercado'); ?></p>
        <div class="social-icons">
            <a href="<?php echo _fs('social_facebook','#'); ?>" target="_blank" rel="noopener noreferrer" class="active"><i class="icon fa fa-facebook"></i></a>
            <a href="<?php echo _fs('social_twitter','#'); ?>" target="_blank" rel="noopener noreferrer"><i class="icon fa fa-twitter"></i></a>
            <a href="<?php echo _fs('social_linkedin','#'); ?>" target="_blank" rel="noopener noreferrer"><i class="icon fa fa-linkedin"></i></a>
            <a href="<?php echo _fs('social_rss','#'); ?>" target="_blank" rel="noopener noreferrer"><i class="icon fa fa-rss"></i></a>
            <a href="<?php echo _fs('social_pinterest','#'); ?>" target="_blank" rel="noopener noreferrer"><i class="icon fa fa-pinterest"></i></a>
            <a href="<?php echo _fs('social_tiktok','#'); ?>" target="_blank" rel="noopener noreferrer" style="font-weight:900;font-size:14px;line-height:1;display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.18 8.18 0 004.78 1.52V6.76a4.85 4.85 0 01-1.01-.07z"/></svg>
            </a>
        </div>
    </div>
</div>
            	</div><!-- /.col -->

            	<div class="col-xs-12 col-sm-6 col-md-4">
<div class="contact-timing">
	<div class="module-heading"><h4 class="module-title">Horarios</h4></div>
	<div class="module-body outer-top-xs">
		<div class="table-responsive">
			<table class="table">
				<tbody>
					<tr><td>Lunes-viernes:</td><td class="pull-right"><?php echo _fs('footer_hours_weekday','08.00 a 18.00'); ?></td></tr>
					<tr><td>Sábados:</td><td class="pull-right"><?php echo _fs('footer_hours_saturday','09.00 a 20.00'); ?></td></tr>
					<tr><td>Domingo:</td><td class="pull-right"><?php echo _fs('footer_hours_sunday','10.00 a 20.00'); ?></td></tr>
				</tbody>
			</table>
		</div>
	</div>
</div>
            	</div><!-- /.col -->

            	<div class="col-xs-12 col-sm-6 col-md-4">
<div class="contact-information">
	<div class="module-heading"><h4 class="module-title">Información</h4></div>
	<div class="module-body outer-top-xs">
        <ul class="toggle-footer">
            <li class="media">
                <div class="pull-left"><span class="icon fa-stack fa-lg"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-map-marker fa-stack-1x fa-inverse"></i></span></div>
                <div class="media-body"><p><?php echo _fs('footer_city','Bogotá'); ?></p></div>
            </li>
            <li class="media">
                <div class="pull-left"><span class="icon fa-stack fa-lg"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-mobile fa-stack-1x fa-inverse"></i></span></div>
                <div class="media-body"><p><?php echo _fs_raw('footer_phone','3222476963<br>3101234567'); ?></p></div>
            </li>
            <li class="media">
                <div class="pull-left"><span class="icon fa-stack fa-lg"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-envelope fa-stack-1x fa-inverse"></i></span></div>
                <div class="media-body"><span><a href="mailto:<?php echo _fs('footer_email','info@modventures.com'); ?>"><?php echo _fs('footer_email','info@modventures.com'); ?></a></span></div>
            </li>
            <?php if((_fs_raw('cookie_consent_enabled','0')) === '1'): ?>
            <li class="media">
                <div class="pull-left"><span class="icon fa-stack fa-lg"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-lock fa-stack-1x fa-inverse"></i></span></div>
                <div class="media-body"><p><a href="#" id="cookie-reopen-link" style="color:inherit;">Configuración de cookies</a></p></div>
            </li>
            <?php endif; ?>
            <li class="media">
                <div class="pull-left"><span class="icon fa-stack fa-lg"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-envelope-o fa-stack-1x fa-inverse"></i></span></div>
                <div class="media-body"><p><a href="contact.php" style="color:inherit;">Contáctanos</a></p></div>
            </li>
        </ul>
        <div style="margin-top:14px;padding-top:10px;border-top:1px solid rgba(255,255,255,.15);">
            <span style="font-size:11px;color:#aaa;margin-right:6px">Legal:</span>
            <a href="terminos.php" style="font-size:11px;color:#ccc;margin-right:10px;text-decoration:none">Términos y condiciones</a>
            <a href="privacidad.php" style="font-size:11px;color:#ccc;margin-right:10px;text-decoration:none">Privacidad</a>
            <a href="devoluciones.php" style="font-size:11px;color:#ccc;text-decoration:none">Devoluciones</a>
        </div>
    </div>
</div>
            	</div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container -->
    </div><!-- /.links-social -->

<!-- ═══════════════════════════ NEWSLETTER ═══ -->
<div style="background:#1a1a1a;padding:28px 0;border-top:1px solid rgba(255,255,255,.08)">
    <div class="container">
        <div class="row" style="display:flex;align-items:center;flex-wrap:wrap;gap:16px">
            <div class="col-md-5" style="color:#fff">
                <h4 style="margin:0 0 4px;font-size:16px;font-weight:700"><i class="fa fa-envelope-o" style="color:#e8233a;margin-right:8px"></i>Suscríbete a nuestro newsletter</h4>
                <p style="margin:0;font-size:12px;color:#aaa">Recibe ofertas exclusivas y novedades directamente en tu correo.</p>
            </div>
            <div class="col-md-7">
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <input id="nl-name" type="text" placeholder="Tu nombre (opcional)" style="flex:1;min-width:120px;padding:9px 12px;border:1px solid #333;background:#2a2a2a;color:#fff;border-radius:5px;font-size:13px">
                    <input id="nl-email" type="email" placeholder="tu@correo.com" style="flex:2;min-width:180px;padding:9px 12px;border:1px solid #333;background:#2a2a2a;color:#fff;border-radius:5px;font-size:13px">
                    <button id="nl-btn" class="btn btn-primary" style="white-space:nowrap;padding:9px 18px;font-size:13px">Suscribirme</button>
                </div>
                <div id="nl-msg" style="font-size:12px;margin-top:6px;color:#aaa"></div>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function(){
    $('#nl-btn').on('click', function(){
        var email = $('#nl-email').val().trim();
        var name  = $('#nl-name').val().trim();
        if (!email) { $('#nl-msg').css('color','#e8233a').text('Ingresa tu correo.'); return; }
        $(this).prop('disabled', true).text('...');
        $.post('ajax-newsletter.php', {action:'subscribe', email:email, name:name}, function(r){
            $('#nl-btn').prop('disabled', false).text('Suscribirme');
            if (r.ok) {
                $('#nl-msg').css('color','#4caf50').text(r.msg);
                $('#nl-email').val(''); $('#nl-name').val('');
            } else {
                $('#nl-msg').css('color','#e8233a').text(r.msg);
            }
        }, 'json');
    });
    $('#nl-email').on('keypress', function(e){ if(e.which===13) $('#nl-btn').trigger('click'); });
});
</script>
<!-- ═══════════════════════════ NEWSLETTER : END ═══ -->
<script src="assets/js/notifications.js"></script>
<!-- PWA Service Worker (W1) -->
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function() {
    navigator.serviceWorker.register('/proyectowebmaster/sw.js', { scope: '/proyectowebmaster/' })
      .catch(function(){});
  });
}
// Prompt de instalación PWA
var _pwaPrompt = null;
window.addEventListener('beforeinstallprompt', function(e) {
  e.preventDefault();
  _pwaPrompt = e;
  var btn = document.getElementById('pwa-install-btn');
  if (btn) btn.style.display = 'inline-flex';
});
function pwaInstall() {
  if (!_pwaPrompt) return;
  _pwaPrompt.prompt();
  _pwaPrompt.userChoice.then(function(){ _pwaPrompt = null; });
}
</script>
<button id="pwa-install-btn" onclick="pwaInstall()" style="display:none;position:fixed;bottom:70px;right:16px;z-index:1050;background:#e8233a;color:#fff;border:none;border-radius:24px;padding:8px 16px;font-size:13px;font-weight:600;cursor:pointer;box-shadow:0 2px 12px rgba(0,0,0,.2);align-items:center;gap:6px">
  <i class="fa fa-download"></i> Instalar App
</button>

<!-- ═══ RECIENTEMENTE VISTOS ═══ -->
<style>
#rv-bar{position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:2px solid #e8233a;box-shadow:0 -2px 12px rgba(0,0,0,.15);z-index:1040;padding:8px 16px;display:none;align-items:center;gap:12px;overflow-x:auto;}
#rv-bar .rv-label{font-size:11px;font-weight:700;text-transform:uppercase;color:#e8233a;white-space:nowrap;letter-spacing:.5px;}
.rv-item{display:flex;align-items:center;gap:7px;text-decoration:none;color:#333;background:#f9f9f9;border:1px solid #eee;border-radius:6px;padding:4px 10px 4px 4px;min-width:130px;max-width:180px;transition:border-color .2s;}
.rv-item:hover{border-color:#e8233a;color:#e8233a;}
.rv-item img{width:36px;height:36px;object-fit:cover;border-radius:4px;}
.rv-item span{font-size:11px;line-height:1.3;max-width:110px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;}
#rv-close{margin-left:auto;background:none;border:none;font-size:18px;cursor:pointer;color:#aaa;padding:0 4px;flex-shrink:0;}
@media(max-width:600px){.rv-item span{display:none;} .rv-item{min-width:48px;max-width:56px;}}
</style>
<div id="rv-bar">
    <span class="rv-label"><i class="fa fa-eye"></i> Vistos</span>
    <div id="rv-items" style="display:flex;gap:8px;flex:1;overflow-x:auto;"></div>
    <button id="rv-close" title="Cerrar">×</button>
</div>
<script>
(function(){
    var KEY='ps_recently_viewed';
    function rvGet(){ try{ return JSON.parse(localStorage.getItem(KEY)||'[]'); }catch(e){ return []; } }
    var items=rvGet().filter(function(p){ return p && p.id && p.name && p.img && p.img!=='null'; });
    if(items.length===0) return;
    var bar=document.getElementById('rv-bar');
    var cont=document.getElementById('rv-items');
    items.forEach(function(p){
        var a=document.createElement('a');
        a.href='product-details.php?pid='+p.id;
        a.className='rv-item';
        a.innerHTML='<img src="admin/productimages/'+p.id+'/'+encodeURIComponent(p.img)+'" onerror="this.style.display=\'none\'"><span>'+p.name+'</span>';
        cont.appendChild(a);
    });
    bar.style.display='flex';
    document.getElementById('rv-close').addEventListener('click',function(){ bar.style.display='none'; });
})();
</script>
<!-- ═══ RECIENTEMENTE VISTOS : END ═══ -->
</footer>

<?php if((_fs_raw('cookie_consent_enabled','0')) === '1'): ?>
<!-- ═══════════════════════════════════════════ COOKIE CONSENT BANNER ═══ -->
<link rel="stylesheet" href="assets/css/cookies.css">
<div id="cookie-banner" style="display:none" role="dialog" aria-label="Preferencias de cookies">

    <!-- Panel principal -->
    <div id="cookie-main-panel">
        <p class="cookie-text">
            Usamos cookies para mejorar tu experiencia de compra.
            Puedes aceptar todas, rechazar las no esenciales o elegir cuáles activar.
            <a href="#" id="cookie-reopen-link-info">Más información</a>.
        </p>
        <div class="cookie-actions">
            <button id="cookie-accept-all" class="cookie-btn cookie-btn-primary">Aceptar todas</button>
            <button id="cookie-reject"     class="cookie-btn cookie-btn-secondary">Rechazar</button>
            <button id="cookie-customize"  class="cookie-btn cookie-btn-link">Personalizar</button>
        </div>
    </div>

    <!-- Panel personalización -->
    <div id="cookie-custom-panel" style="display:none">
        <h4 class="cookie-panel-title"><i class="fa fa-sliders"></i> Personalizar preferencias de cookies</h4>

        <div class="cookie-category">
            <div class="cookie-cat-header">
                <span class="cookie-cat-name">Esenciales</span>
                <span class="cookie-toggle-locked">Siempre activas</span>
            </div>
            <p class="cookie-cat-desc">Necesarias para el carrito de compras, inicio de sesión y seguridad. No se pueden desactivar.</p>
        </div>

        <div class="cookie-category">
            <div class="cookie-cat-header">
                <span class="cookie-cat-name">Analíticas</span>
                <label class="cookie-toggle" for="toggle-analytics">
                    <input type="checkbox" id="toggle-analytics">
                    <span class="cookie-toggle-slider"></span>
                </label>
            </div>
            <p class="cookie-cat-desc">Nos permiten medir el tráfico y el comportamiento de los visitantes para mejorar el sitio (p.ej. Google Analytics).</p>
        </div>

        <div class="cookie-category">
            <div class="cookie-cat-header">
                <span class="cookie-cat-name">Preferencias</span>
                <label class="cookie-toggle" for="toggle-preferences">
                    <input type="checkbox" id="toggle-preferences">
                    <span class="cookie-toggle-slider"></span>
                </label>
            </div>
            <p class="cookie-cat-desc">Recuerdan tu tema de color, idioma y otras opciones de personalización entre visitas.</p>
        </div>

        <div class="cookie-actions" style="margin-top:12px;">
            <button id="cookie-save-custom" class="cookie-btn cookie-btn-primary">Guardar preferencias</button>
            <button id="cookie-back"        class="cookie-btn cookie-btn-link">&#8592; Volver</button>
        </div>
    </div>

</div>
<!-- ═══════════════════════════════════════════ COOKIE CONSENT BANNER : END ═══ -->
<?php endif; ?>

<!-- ═══════════════════════════════════════════ CART DRAWER ═══════════════════════════════════════════ -->
<link rel="stylesheet" href="assets/css/cart-drawer.css">
<div id="cart-overlay"></div>
<div id="cart-drawer">
  <div class="drawer-header">
    <h3><i class="fa fa-shopping-cart" style="margin-right:10px"></i>Carrito de Compras</h3>
    <button class="drawer-close" title="Cerrar">&times;</button>
  </div>

  <div class="free-shipping-bar">
    <span id="fs-message">Cargando...</span>
    <div class="bar-track"><div class="bar-fill" id="fs-bar" style="width:0%"></div></div>
  </div>

  <div class="drawer-items" id="drawer-items-list">
    <div class="drawer-empty">
      <i class="fa fa-shopping-cart"></i>
      <p>Tu carrito está vacío</p>
    </div>
  </div>

  <div class="drawer-footer" id="drawer-footer" style="display:none">
    <div class="subtotal-row">
      <span class="subtotal-label">Subtotal</span>
      <span class="subtotal-amount" id="drawer-subtotal">$0</span>
    </div>
    <div class="drawer-actions">
      <a href="my-cart.php" class="btn-checkout">Finalizar compra</a>
      <a href="#" class="btn-continue" id="btn-keep-shopping">Seguir comprando</a>
    </div>
  </div>
</div>

<!-- ═══ FAB STACK + BACK TO TOP ═══ -->
<style>
/* Contenedor que agrupa todos los botones flotantes */
#ps-fab-stack{
    position:fixed;
    bottom:20px;
    right:20px;
    z-index:9999;
    display:flex;
    flex-direction:column-reverse;
    align-items:center;
    gap:10px;
}
/* Forzar que cada FAB individual use posición relativa dentro del stack */
#ps-fab-stack #ps-back-top,
#ps-fab-stack #ps-dark-toggle,
#ps-fab-stack #ps-chat-fab,
#ps-fab-stack #lc-btn {
    position:relative !important;
    bottom:auto !important;
    right:auto !important;
    top:auto !important;
}
/* Back to top */
#ps-back-top{width:42px;height:42px;border-radius:50%;background:#e8233a;color:#fff;border:none;box-shadow:0 4px 14px rgba(0,0,0,.22);cursor:pointer;display:none;align-items:center;justify-content:center;font-size:18px;transition:opacity .3s,transform .3s;}
#ps-back-top:hover{background:#c0396b;transform:translateY(-3px);}
</style>
<div id="ps-fab-stack">
    <button id="ps-back-top" title="Volver arriba"><i class="fa fa-angle-up"></i></button>
</div>
<!-- FAB scripts DESPUÉS del #ps-fab-stack para que getElementById los encuentre -->
<script src="assets/js/darkmode.js"></script>
<script src="assets/js/chatbot.js"></script>
<script src="assets/js/livechat.js"></script>
<script>
$(document).ready(function(){
    $(window).on('scroll', function(){
        if($(this).scrollTop() > 300){
            $('#ps-back-top').css('display','flex');
        } else {
            $('#ps-back-top').css('display','none');
        }
    });
    $('#ps-back-top').on('click', function(){
        $('html,body').animate({scrollTop:0}, 400);
    });
});
</script>

<!-- ═══════════════════════════════════════════ CART DRAWER : END ═══ -->

