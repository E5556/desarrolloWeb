<?php
// ── Cart totals ──────────────────────────────────────────────────────────────
$_header_total = 0;
$_header_qty   = 0;
if (!empty($_SESSION['cart'])) {
    $ids          = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types        = str_repeat('i', count($ids));
    $stmt_h = mysqli_prepare($con, "SELECT id, productPrice, shippingCharge FROM products WHERE id IN ($placeholders)");
    mysqli_stmt_bind_param($stmt_h, $types, ...$ids);
    mysqli_stmt_execute($stmt_h);
    $res_h = mysqli_stmt_get_result($stmt_h);
    while ($rh = mysqli_fetch_assoc($res_h)) {
        $q = $_SESSION['cart'][$rh['id']]['quantity'];
        $_header_total += ($rh['productPrice'] + $rh['shippingCharge']) * $q;
        $_header_qty   += $q;
    }
    mysqli_stmt_close($stmt_h);
    $_SESSION['tp']   = number_format($_header_total, 2, '.', ',');
    $_SESSION['qnty'] = $_header_qty;
}

// ── Logo & site name ─────────────────────────────────────────────────────────
$_logo_row = mysqli_query($con, "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('site_logo','site_name')");
$_site = [];
while ($_r = mysqli_fetch_assoc($_logo_row)) $_site[$_r['setting_key']] = $_r['setting_value'];
$_logo_src  = htmlspecialchars($_site['site_logo']  ?? 'assets/images/oig.jpg');
$_site_name = htmlspecialchars($_site['site_name']  ?? 'Personal Shopper');

// ── Categories + subcategories for nav ───────────────────────────────────────
$_cats_raw = mysqli_query($con, "SELECT id, categoryName FROM category LIMIT 6");
$_cats = [];
while ($_c = mysqli_fetch_assoc($_cats_raw)) {
    $_subs_r = mysqli_query($con, "SELECT id, subcategory FROM subcategory WHERE categoryid=".intval($_c['id']));
    $_c['subs'] = [];
    while ($_s = mysqli_fetch_assoc($_subs_r)) $_c['subs'][] = $_s;
    $_cats[] = $_c;
}
?>

<!-- ══════════════════════════════════════════════════════════════════════════
     UNIFIED STICKY HEADER — Personal Shopper
     ══════════════════════════════════════════════════════════════════════════ -->
<style>
/* ── Font ─────────────────────────────────────────────────────────────────── */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');

/* ── Shell ────────────────────────────────────────────────────────────────── */
.ps-header {
    position: sticky;
    top: 0;
    z-index: 1000;
    background: rgba(255, 255, 255, 0.88);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-bottom: 1px solid rgba(0, 0, 0, 0.07);
    transition: box-shadow .3s, background .3s;
    font-family: 'Poppins', sans-serif;
}
.ps-header.scrolled {
    background: rgba(255, 255, 255, 0.97);
    box-shadow: 0 4px 28px rgba(0, 0, 0, 0.09);
}
.ps-header .ps-container {
    max-width: 1240px;
    margin: 0 auto;
    padding: 0 24px;
    display: flex;
    align-items: center;
    height: 74px;
    gap: 0;
    transition: height .3s;
}
.ps-header.scrolled .ps-container { height: 60px; }

/* ── Secondary nav bar ────────────────────────────────────────────────────── */
.ps-navbar {
    background: #fff;
    border-bottom: 1px solid rgba(0,0,0,0.07);
    font-family: 'Poppins', sans-serif;
    position: sticky;
    top: 74px;
    z-index: 999;
    transition: top .3s;
}
.ps-header.scrolled ~ .ps-navbar,
.ps-header.scrolled + * .ps-navbar { top: 60px; }
.ps-navbar-inner {
    max-width: 1240px;
    margin: 0 auto;
    padding: 0 24px;
    display: flex;
    align-items: center;
    gap: 2px;
}
.ps-nav {
    display: flex;
    align-items: center;
    gap: 2px;
    flex: 1;
}
.ps-nav-item {
    position: relative;
}
.ps-nav-item > a {
    position: relative;
    padding: 10px 14px;
    font-size: 13px;
    font-weight: 500;
    color: #333;
    text-decoration: none;
    letter-spacing: .25px;
    white-space: nowrap;
    transition: color .2s;
    display: block;
}
.ps-nav-item > a::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 14px;
    right: 14px;
    height: 2px;
    background: #c0396b;
    border-radius: 2px 2px 0 0;
    transform: scaleX(0);
    transform-origin: left center;
    transition: transform .25s cubic-bezier(.4, 0, .2, 1);
}
.ps-nav-item > a:hover,
.ps-nav-item > a.ps-active { color: #c0396b; }
.ps-nav-item > a:hover::after,
.ps-nav-item > a.ps-active::after { transform: scaleX(1); }

/* ── Dropdown ── */
.ps-dropdown {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    min-width: 180px;
    background: #fff;
    border: 1px solid #eee;
    border-radius: 0 0 6px 6px;
    box-shadow: 0 6px 20px rgba(0,0,0,.1);
    z-index: 9999;
    padding: 6px 0;
}
.ps-nav-item:hover .ps-dropdown { display: block; }
.ps-dropdown a {
    display: block;
    padding: 8px 16px;
    font-size: 12.5px;
    color: #444;
    text-decoration: none;
    white-space: nowrap;
    transition: background .15s, color .15s;
}
.ps-dropdown a:hover {
    background: #fdf0f5;
    color: #c0396b;
}

/* ── Center: logo ─────────────────────────────────────────────────────────── */
.ps-logo {
    flex: 0 0 auto;
    padding: 0 28px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-decoration: none;
    gap: 2px;
}
.ps-logo img {
    height: 56px;
    width: auto;
    object-fit: contain;
    display: block;
    transition: height .3s;
}
.ps-header.scrolled .ps-logo img { height: 42px; }
.ps-logo-name {
    font-size: 9.5px;
    font-weight: 600;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    color: #aaa;
    line-height: 1;
}

/* ── Right: utilities ─────────────────────────────────────────────────────── */
.ps-utils {
    display: flex;
    align-items: center;
    gap: 4px;
    flex: 1;
    justify-content: flex-end;
}
.ps-welcome-chip {
    font-size: 11px;
    font-weight: 500;
    color: #999;
    max-width: 130px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    margin-right: 4px;
    transition: opacity .25s;
}

/* icon buttons */
.ps-icon-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    color: #555;
    text-decoration: none !important;
    background: transparent;
    border: none;
    cursor: pointer;
    position: relative;
    transition: background .2s, color .2s;
}
.ps-icon-btn:hover { background: rgba(192, 57, 107, 0.09); color: #c0396b; }
.ps-icon-btn svg { width: 21px; height: 21px; stroke-width: 1.6; fill: none; stroke: currentColor; }

/* cart badge */
.ps-badge {
    position: absolute;
    top: 3px; right: 3px;
    background: #c0396b;
    color: #fff;
    border-radius: 10px;
    font-size: 9px;
    font-weight: 700;
    min-width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 3px;
    line-height: 1;
}

/* cart total */
.ps-cart-lbl {
    font-size: 12px;
    font-weight: 600;
    color: #444;
    white-space: nowrap;
    margin-left: 2px;
}

/* login pill */
.ps-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 20px;
    border-radius: 50px;
    background: #c0396b;
    color: #fff !important;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none !important;
    letter-spacing: .3px;
    transition: background .2s, transform .15s;
    margin-left: 6px;
    white-space: nowrap;
}
.ps-pill:hover { background: #a02d59; transform: translateY(-1px); }
.ps-pill svg { width: 15px; height: 15px; stroke-width: 2; fill: none; stroke: currentColor; }

/* logout pill (outline) */
.ps-pill-outline {
    background: transparent;
    border: 1.5px solid #c0396b;
    color: #c0396b !important;
}
.ps-pill-outline:hover { background: rgba(192,57,107,.07); transform: translateY(-1px); }

/* ── Search expand ────────────────────────────────────────────────────────── */
.ps-search-wrap { position: relative; display: flex; align-items: center; }
.ps-search-wrap form { display: flex; align-items: center; }
.ps-search-input {
    width: 0;
    opacity: 0;
    border: none;
    outline: none;
    background: rgba(0,0,0,0.06);
    border-radius: 50px;
    font-family: 'Poppins', sans-serif;
    font-size: 13px;
    padding: 0;
    position: absolute;
    right: 44px;
    transition: width .35s cubic-bezier(.4,0,.2,1), opacity .3s, padding .3s;
    color: #333;
}
.ps-search-wrap.open .ps-search-input {
    width: 210px;
    opacity: 1;
    padding: 8px 18px;
}
.ps-search-btn { background: transparent; border: none; padding: 0; cursor: pointer; }

/* ── Divider ──────────────────────────────────────────────────────────────── */
.ps-divider {
    width: 1px; height: 22px; background: rgba(0,0,0,0.12);
    margin: 0 6px; flex-shrink: 0;
}

/* ── Responsive ───────────────────────────────────────────────────────────── */
@media (max-width: 768px) {
    .ps-navbar {
        position: static;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        white-space: nowrap;
        scrollbar-width: none;
    }
    .ps-navbar::-webkit-scrollbar { display: none; }
    .ps-navbar-inner { padding: 0 8px; gap: 0; display: block; white-space: nowrap; }
    .ps-nav { display: inline-flex; gap: 0; }
    .ps-nav-item { display: inline-block; }
    .ps-nav-item > a { padding: 10px 14px; font-size: 13px; display: inline-block; }
    .ps-dropdown { display: none !important; }
    .ps-welcome-chip { display: none; }
    .ps-search-wrap.open .ps-search-input { width: 130px; }
    .ps-cart-lbl { display: none; }
}
@media (max-width: 575px) {
    .ps-header .ps-container { padding: 0 8px; gap: 0; }
    .ps-logo { padding: 0 4px; }
    .ps-utils { gap: 0; }
    .ps-icon-btn { width: 34px; height: 34px; }
    .ps-icon-btn svg { width: 18px; height: 18px; }
    /* Ocultar iconos menos prioritarios en móvil pequeño */
    .ps-icon-btn[title="Mis pedidos"],
    .ps-icon-btn[title="Mis compras"],
    .ps-divider { display: none; }
    .ps-pill span { display: none; }
    .ps-pill { padding: 8px 10px; }
}
</style>

<div class="ps-header" id="ps-header">
    <div class="ps-container">

        <!-- ── CENTER: Logo ── -->
        <a href="index2.php" class="ps-logo">
            <img src="./<?php echo $_logo_src; ?>" alt="<?php echo $_site_name; ?>">
            <span class="ps-logo-name"><?php echo $_site_name; ?></span>
        </a>

        <!-- ── RIGHT: Utilities ── -->
        <div class="ps-utils">

            <?php if (!empty($_SESSION['login'])): ?>
            <span class="ps-welcome-chip">
                Hola, <?php echo htmlspecialchars($_SESSION['username']); ?>
            </span>
            <?php endif; ?>

            <!-- Search -->
            <div class="ps-search-wrap" id="ps-search-wrap" style="position:relative">
                <form action="search-result.php" method="post" autocomplete="off">
                    <input class="ps-search-input" id="ps-search-input"
                           type="text" name="product" placeholder="Buscar productos…" autocomplete="off">
                    <button class="ps-icon-btn ps-search-btn" type="button" id="ps-search-toggle"
                            title="Buscar" aria-label="Buscar">
                        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </button>
                </form>
                <div id="ps-ac-dropdown" style="display:none;position:absolute;top:100%;right:0;width:320px;background:#fff;border:1px solid #eee;border-radius:6px;box-shadow:0 4px 20px rgba(0,0,0,.12);z-index:9999;max-height:360px;overflow-y:auto"></div>
            </div>

            <!-- Notifications (logged in only) -->
            <?php if (!empty($_SESSION['login'])): ?>
            <div id="ps-notif-wrap" style="position:relative;display:inline-block">
                <button id="ps-notif-bell" class="ps-icon-btn" title="Notificaciones" style="position:relative">
                    <svg viewBox="0 0 24 24" style="width:20px;height:20px"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <span id="ps-notif-badge" style="position:absolute;top:-2px;right:-2px;background:#e8233a;color:#fff;font-size:9px;font-weight:700;width:16px;height:16px;border-radius:50%;display:none;align-items:center;justify-content:center"></span>
                </button>
            </div>
            <?php endif; ?>

            <!-- Selector de moneda (R2) -->
            <?php $__cur = $_SESSION['currency'] ?? 'COP'; ?>
            <div style="position:relative;display:inline-block" id="ps-currency-wrap">
              <button onclick="document.getElementById('ps-cur-drop').style.display=document.getElementById('ps-cur-drop').style.display==='block'?'none':'block'" class="ps-icon-btn" style="font-size:12px;font-weight:700;width:auto;padding:0 6px;letter-spacing:.5px" title="Moneda"><?php echo htmlspecialchars($__cur); ?></button>
              <div id="ps-cur-drop" style="display:none;position:absolute;right:0;top:110%;background:#fff;border:1px solid #eee;border-radius:6px;box-shadow:0 4px 16px rgba(0,0,0,.12);z-index:9999;min-width:90px">
                <?php foreach(['COP'=>'🇨🇴 COP','USD'=>'🇺🇸 USD','EUR'=>'🇪🇺 EUR','BRL'=>'🇧🇷 BRL'] as $cval=>$clbl): ?>
                <a href="#" onclick="setCurrency('<?php echo $cval; ?>');return false" style="display:block;padding:8px 14px;font-size:12px;color:#333;text-decoration:none<?php echo $__cur===$cval?';font-weight:700':''; ?>"><?php echo $clbl; ?></a>
                <?php endforeach; ?>
              </div>
            </div>
            <script>
            function setCurrency(cur){
              fetch('ajax-currency.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'currency='+cur})
              .then(function(){location.reload();});
            }
            document.addEventListener('click',function(e){
              var w=document.getElementById('ps-currency-wrap');
              if(w && !w.contains(e.target)) document.getElementById('ps-cur-drop').style.display='none';
            });
            </script>

            <!-- Account -->
            <a href="my-account.php" class="ps-icon-btn" title="Mi cuenta">
                <!-- Lucide: user -->
                <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </a>

            <!-- Wishlist -->
            <a href="my-wishlist.php" class="ps-icon-btn" title="Lista de deseos">
                <!-- Lucide: heart -->
                <svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            </a>

            <!-- Cart -->
            <a href="my-cart.php" class="ps-icon-btn lnk-cart" title="Carrito">
                <!-- Lucide: shopping-cart -->
                <svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                <?php if ($_header_qty > 0): ?>
                <span class="ps-badge" id="header-cart-count"><?php echo $_header_qty; ?></span>
                <?php else: ?>
                <span class="ps-badge" id="header-cart-count" style="display:none">0</span>
                <?php endif; ?>
            </a>

            <?php if ($_header_total > 0): ?>
            <span class="ps-cart-lbl" id="header-cart-total">
                $<?php echo number_format($_header_total, 0, '.', ','); ?>
            </span>
            <?php else: ?>
            <span class="ps-cart-lbl" id="header-cart-total" style="display:none"></span>
            <?php endif; ?>

            <div class="ps-divider"></div>

            <!-- Track orders -->
            <a href="track-orders.php" class="ps-icon-btn" title="Mis pedidos">
                <!-- Lucide: package -->
                <svg viewBox="0 0 24 24"><path d="M16.5 9.4l-9-5.19"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
            </a>

            <?php if (!empty($_SESSION['login'])): ?>
            <!-- Mis Compras (solo logueado) -->
            <a href="mis-compras.php" class="ps-icon-btn" title="Mis compras">
                <!-- Lucide: shopping-bag -->
                <svg viewBox="0 0 24 24"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            </a>
            <?php endif; ?>

            <!-- Login / Logout pill -->
            <?php if (empty($_SESSION['login'])): ?>
            <a href="login.php" class="ps-pill">
                <svg viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                <span>Login</span>
            </a>
            <?php else: ?>
            <a href="logout.php" class="ps-pill ps-pill-outline">
                <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span>Salir</span>
            </a>
            <?php endif; ?>

        </div><!-- /.ps-utils -->
    </div><!-- /.ps-container -->
</div><!-- /.ps-header -->

<!-- ── SECONDARY NAV BAR ── -->
<div class="ps-navbar" id="ps-navbar">
    <div class="ps-navbar-inner">
        <nav class="ps-nav">
            <div class="ps-nav-item">
                <a href="index2.php" class="ps-active">Inicio</a>
            </div>
            <?php foreach ($_cats as $_cat): ?>
            <div class="ps-nav-item">
                <a href="category.php?cid=<?php echo (int)$_cat['id']; ?>">
                    <?php echo htmlspecialchars($_cat['categoryName']); ?>
                    <?php if(!empty($_cat['subs'])): ?><i style="font-size:.65em;margin-left:3px">▾</i><?php endif; ?>
                </a>
                <?php if(!empty($_cat['subs'])): ?>
                <div class="ps-dropdown">
                    <?php foreach($_cat['subs'] as $_sub): ?>
                    <a href="sub-category.php?scid=<?php echo (int)$_sub['id']; ?>">
                        <?php echo htmlspecialchars($_sub['subcategory']); ?>
                    </a>
                    <?php endforeach; ?>
                    <hr style="margin:4px 0;border-color:#f0f0f0">
                    <a href="category.php?cid=<?php echo (int)$_cat['id']; ?>" style="color:#c0396b;font-weight:600">
                        Ver todo →
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </nav>
    </div>
</div><!-- /.ps-navbar -->

<script>
(function(){
    // ── Sticky shrink on scroll ──────────────────────────────────────────────
    var hdr = document.getElementById('ps-header');
    var navbar = document.getElementById('ps-navbar');
    window.addEventListener('scroll', function(){
        if (window.scrollY > 20) {
            hdr.classList.add('scrolled');
            if (navbar) navbar.style.top = '60px';
        } else {
            hdr.classList.remove('scrolled');
            if (navbar) navbar.style.top = '74px';
        }
    }, { passive: true });

    // ── Expandable search ────────────────────────────────────────────────────
    var toggle = document.getElementById('ps-search-toggle');
    var wrap   = document.getElementById('ps-search-wrap');
    var input  = document.getElementById('ps-search-input');
    var welcome = document.querySelector('.ps-welcome-chip');
    function openSearch() {
        wrap.classList.add('open');
        if (welcome) welcome.style.opacity = '0';
        input.focus();
    }
    function closeSearch() {
        wrap.classList.remove('open');
        input.value = '';
        if (welcome) welcome.style.opacity = '1';
    }
    toggle.addEventListener('click', function(e){
        e.preventDefault();
        if (wrap.classList.contains('open')) {
            if (input.value.trim() !== '') {
                wrap.querySelector('form').submit();
            } else {
                closeSearch();
            }
        } else {
            openSearch();
        }
    });
    document.addEventListener('click', function(e){
        if (!wrap.contains(e.target)) closeSearch();
    });
    // submit on Enter
    input.addEventListener('keydown', function(e){
        if (e.key === 'Enter' && input.value.trim() !== '') {
            psAcClose();
            wrap.querySelector('form').submit();
        }
    });

    // ── Búsquedas recientes (localStorage) ──────────────────────────────────
    var _acDrop = document.getElementById('ps-ac-dropdown');
    var _acTimer = null;
    var _RS_KEY  = 'ps_recent_searches';

    function rsGet() { try { return JSON.parse(localStorage.getItem(_RS_KEY)||'[]'); } catch(e){ return []; } }
    function rsSave(q) {
        var list = rsGet().filter(function(s){ return s !== q; });
        list.unshift(q); if (list.length > 5) list = list.slice(0,5);
        localStorage.setItem(_RS_KEY, JSON.stringify(list));
    }
    function rsRemove(q) {
        localStorage.setItem(_RS_KEY, JSON.stringify(rsGet().filter(function(s){ return s !== q; })));
        showRecentSearches();
    }
    function showRecentSearches() {
        var list = rsGet();
        if (!list.length) { psAcClose(); return; }
        var html = '<div style="padding:6px 12px 2px;font-size:10px;color:#aaa;text-transform:uppercase;letter-spacing:.5px">Búsquedas recientes</div>';
        list.forEach(function(s) {
            html += '<div style="display:flex;align-items:center;padding:7px 12px;border-bottom:1px solid #f5f5f5;gap:8px">' +
                '<svg viewBox="0 0 24 24" style="width:12px;height:12px;flex-shrink:0;stroke:#aaa;fill:none;stroke-width:2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>' +
                '<a href="#" style="flex:1;text-decoration:none;font-size:13px;color:#333" onclick="event.preventDefault();input.value=\''+s.replace(/'/g,'')+'\';input.dispatchEvent(new Event(\'input\'));rsSave(\''+s.replace(/'/g,'')+'\')">'+s+'</a>' +
                '<a href="#" title="Eliminar" onclick="event.preventDefault();rsRemove(\''+s.replace(/'/g,'')+'\');" style="color:#ccc;font-size:14px;text-decoration:none">&times;</a>' +
                '</div>';
        });
        _acDrop.innerHTML = html;
        _acDrop.style.display = 'block';
    }

    function psAcClose() { if (_acDrop) _acDrop.style.display = 'none'; }

    // Mostrar recientes al abrir el buscador
    wrap.addEventListener('click', function(){ if (!input.value.trim()) showRecentSearches(); });

    // Guardar búsqueda al hacer submit
    wrap.querySelector('form').addEventListener('submit', function(){
        var q = input.value.trim(); if (q.length > 1) rsSave(q);
    });
    input.addEventListener('keydown', function(e){
        if (e.key === 'Enter' && input.value.trim().length > 1) rsSave(input.value.trim());
    });

    input.addEventListener('input', function(){
        clearTimeout(_acTimer);
        var q = input.value.trim();
        if (q.length < 2) { if (q.length === 0) showRecentSearches(); else psAcClose(); return; }
        _acTimer = setTimeout(function(){
            fetch('ajax-search.php?q=' + encodeURIComponent(q))
                .then(function(r){ return r.json(); })
                .then(function(data){
                    // Soporte formato nuevo {results,did_you_mean} y formato legacy array
                    var items = Array.isArray(data) ? data : (data.results || []);
                    var dym   = Array.isArray(data) ? null : (data.did_you_mean || null);

                    if (!items.length) {
                        if (dym) {
                            _acDrop.innerHTML = '<div style="padding:12px 14px;font-size:13px;color:#888">¿Quisiste decir: <a href="search-result.php?product=' + encodeURIComponent(dym) + '" style="color:#e8233a;font-weight:600">' + dym + '</a>?</div>';
                            _acDrop.style.display = 'block';
                        } else { psAcClose(); }
                        return;
                    }
                    var html = '';
                    items.forEach(function(p){
                        var avail = p.avail === 'In Stock' ? '<span style="color:#27ae60;font-size:10px">En stock</span>' :
                                    p.avail === 'On Order' ? '<span style="color:#f39c12;font-size:10px">Bajo pedido</span>' :
                                    '<span style="color:#e8233a;font-size:10px">Agotado</span>';
                        html += '<a href="product-details.php?product=' + p.id + '" style="display:flex;align-items:center;gap:10px;padding:8px 12px;text-decoration:none;color:#333;border-bottom:1px solid #f5f5f5;transition:background .15s" onmouseover="this.style.background=\'#fdf0f5\'" onmouseout="this.style.background=\'#fff\'">' +
                            '<img src="' + p.img + '" onerror="this.src=\'assets/images/blank.gif\'" style="width:36px;height:44px;object-fit:contain;flex-shrink:0">' +
                            '<div style="flex:1;min-width:0">' +
                            '<div style="font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + p.name + '</div>' +
                            '<div style="font-size:12px;color:#e8233a;font-weight:700">$' + p.price.toLocaleString('es-CO') + '</div>' +
                            avail + '</div></a>';
                    });
                    html += '<a href="search-result.php" onclick="document.querySelector(\'[name=product]\').value=\'' + q.replace(/'/g,'') + '\';event.preventDefault();wrap.querySelector(\'form\').submit();" style="display:block;text-align:center;padding:8px;font-size:12px;color:#e8233a;font-weight:600;text-decoration:none">Ver todos los resultados →</a>';
                    if (q.length > 1) rsSave(q);
                    _acDrop.innerHTML = html;
                    _acDrop.style.display = 'block';
                }).catch(psAcClose);
        }, 280);
    });

    document.addEventListener('click', function(e){
        if (!wrap.contains(e.target)) { closeSearch(); psAcClose(); }
    });

})();
</script>
