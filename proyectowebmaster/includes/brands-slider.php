<?php
$_br_res = mysqli_query($con, "SELECT * FROM brands WHERE active=1 ORDER BY sort_order ASC");
$_brands = [];
while($_br = mysqli_fetch_assoc($_br_res)) $_brands[] = $_br;
?>
<div id="brands-carousel" class="logo-slider">
    <h3 class="section-title">Nuestras Marcas</h3>
    <div class="logo-slider-inner">
        <div id="brand-slider" class="owl-carousel brand-slider custom-carousel owl-theme">
        <?php foreach($_brands as $_b): ?>
            <div class="item">
                <a href="search-result.php?product=<?php echo urlencode($_b['keyword']); ?>"
                   class="image brand-item"
                   title="<?php echo htmlspecialchars($_b['brand_name']); ?>">
                    <img src="<?php echo htmlspecialchars($_b['image_path']); ?>"
                         alt="<?php echo htmlspecialchars($_b['brand_name']); ?>"
                         style="width:130px; max-height:70px; object-fit:contain;">
                    <span class="brand-name"><?php echo htmlspecialchars($_b['brand_name']); ?></span>
                </a>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.brand-item { display:flex; flex-direction:column; align-items:center; gap:6px; padding:10px; text-decoration:none !important; }
.brand-item img { transition: transform 0.2s, filter 0.2s; filter: grayscale(30%); }
.brand-item:hover img { transform: scale(1.08); filter: grayscale(0%); }
.brand-name { display:none; }
</style>

