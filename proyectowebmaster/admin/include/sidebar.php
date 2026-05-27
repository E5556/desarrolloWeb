<div class="span3">
					<div class="sidebar">


<ul class="widget widget-menu unstyled">
							<li><a href="dashboard.php"><i class="menu-icon icon-home"></i><strong>Dashboard</strong></a></li>
						</ul>
						<ul class="widget widget-menu unstyled">
							<li>
								<a class="collapsed" data-toggle="collapse" href="#togglePages">
									<i class="menu-icon icon-cog"></i>
									<i class="icon-chevron-down pull-right"></i><i class="icon-chevron-up pull-right"></i>
									Gestión de pedidos
								</a>
								<ul id="togglePages" class="collapse unstyled">
									<?php if (in_array($_SESSION['arole']??'super',['super','editor','asesor'])): ?>
									<li>
										<a href="create-order.php" style="color:#27ae60;font-weight:600">
											<i class="icon-plus-sign" style="color:#27ae60"></i>
											Crear pedido
										</a>
									</li>
									<?php endif; ?>
									<li>
										<a href="todays-orders.php">
											<i class="icon-tasks"></i>
											Pedidos de hoy
  <?php
  $f1="00:00:00";
$from=date('Y-m-d')." ".$f1;
$t1="23:59:59";
$to=date('Y-m-d')." ".$t1;
 $result = mysqli_query($con,"SELECT * FROM orders where orderDate Between '$from' and '$to'");
$num_rows1 = mysqli_num_rows($result);
{
?>
											<b class="label orange pull-right"><?php echo htmlentities($num_rows1); ?></b>
											<?php } ?>
										</a>
									</li>
									<li>
										<a href="pending-orders.php">
											<i class="icon-tasks"></i>
											Ordenes pendientes
										<?php	
	$status='Delivered';									 
$ret = mysqli_query($con,"SELECT * FROM orders where orderStatus!='$status' || orderStatus is null ");
$num = mysqli_num_rows($ret);
{?><b class="label orange pull-right"><?php echo htmlentities($num); ?></b>
<?php } ?>
										</a>
									</li>
									<li>
										<a href="delivered-orders.php">
											<i class="icon-inbox"></i>
											Ordenes entregadas
								<?php	
	$status='Delivered';									 
$rt = mysqli_query($con,"SELECT * FROM orders where orderStatus='$status'");
$num1 = mysqli_num_rows($rt);
{?><b class="label green pull-right"><?php echo htmlentities($num1); ?></b>
<?php } ?>

										</a>
									</li>
								</ul>
							</li>
							
							<li>
								<a href="manage-users.php">
									<i class="menu-icon icon-group"></i>
									CRM — Clientes
								</a>
							</li>
						</ul>


						<ul class="widget widget-menu unstyled">
                                <li><a href="category.php"><i class="menu-icon icon-tasks"></i> Crear Categoría </a></li>
                                <li><a href="subcategory.php"><i class="menu-icon icon-tasks"></i>SubCategoria </a></li>
                                <li><a href="insert-product.php"><i class="menu-icon icon-paste"></i>Insertar Producto </a></li>
                                <li><a href="manage-products.php"><i class="menu-icon icon-table"></i>Administrar Productos </a></li>
                                <li><a href="product-customizer.php"><i class="menu-icon icon-magic"></i>Configurador de producto</a></li>
                        
                            </ul><!--/.widget-nav-->

						<ul class="widget widget-menu unstyled">
							<li><a href="reviews.php"><i class="menu-icon icon-star"></i>Reseñas de productos </a>
							<?php
							$_rv_q = mysqli_query($con,"SELECT COUNT(*) n FROM productreviews WHERE status='pending'");
$pend_rv = $_rv_q ? intval(mysqli_fetch_assoc($_rv_q)['n']) : 0;
							if ($pend_rv > 0): ?>
							<b class="label orange pull-right"><?php echo $pend_rv; ?></b>
							<?php endif; ?>
						</li>
						<li><a href="contact-messages.php"><i class="menu-icon icon-envelope"></i>Mensajes de contacto
							<?php
							$_cm_q = @mysqli_query($con,"SELECT COUNT(*) n FROM contact_messages WHERE is_read=0");
							$_cm_n = $_cm_q ? intval(mysqli_fetch_assoc($_cm_q)['n']) : 0;
							if ($_cm_n > 0): ?>
							<b class="label orange pull-right"><?php echo $_cm_n; ?></b>
							<?php endif; ?>
						</a></li>
						<li><a href="newsletter-subscribers.php"><i class="menu-icon icon-envelope-alt"></i>Suscriptores newsletter</a></li>
						<li><a href="newsletter-send.php"><i class="menu-icon icon-send"></i>Enviar newsletter</a></li>
						<li><a href="returns.php"><i class="menu-icon icon-undo"></i>Devoluciones
							<?php
							$_ret_pend = @mysqli_query($con,"SELECT COUNT(*) n FROM returns WHERE status='pending'" );
							$_ret_n = $_ret_pend ? intval(mysqli_fetch_assoc($_ret_pend)['n']) : 0;
							if ($_ret_n > 0): ?>
							<b class="label orange pull-right"><?php echo $_ret_n; ?></b>
							<?php endif; ?>
						</a></li>
						<li><a href="statistics.php"><i class="menu-icon icon-bar-chart"></i>Estadísticas </a></li>
						<li><a href="conversion-funnel.php"><i class="menu-icon icon-filter"></i>Funnel de conversion</a></li>
							<li><a href="coupons.php"><i class="menu-icon icon-tag"></i>Cupones y descuentos </a></li>
							<li><a href="flash-sales.php"><i class="menu-icon icon-bolt"></i>Flash Sales </a></li>
							<li><a href="abandoned-carts.php"><i class="menu-icon icon-shopping-cart"></i>Carritos abandonados
							<?php
							$_ab_q = mysqli_query($con, "SELECT COUNT(DISTINCT userId) n FROM orders WHERE paymentMethod IS NULL AND orderDate < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
							$_ab_n = $_ab_q ? intval(mysqli_fetch_assoc($_ab_q)['n']) : 0;
							if ($_ab_n > 0): ?>
							<b class="label orange pull-right"><?php echo $_ab_n; ?></b>
							<?php endif; ?>
							</a></li>
							<li><a href="user-logs.php"><i class="menu-icon icon-tasks"></i>Registro de usuario </a></li>
							<li><a href="admin-users.php"><i class="menu-icon icon-lock"></i>Administradores </a></li>
							<li><a href="faq.php"><i class="menu-icon icon-question-sign"></i>FAQ del sitio</a></li>
						<li><a href="settings.php"><i class="menu-icon icon-cog"></i>Configuración del sitio </a></li>
							<li><a href="admin-log.php"><i class="menu-icon icon-list"></i>Log de actividad</a></li>
							<li><a href="discount-rules.php"><i class="menu-icon icon-percent"></i>Descuentos categoría</a></li>
							<li><a href="stock-history.php"><i class="menu-icon icon-list-alt"></i>Historial de stock</a></li>
							<li><a href="product-questions.php"><i class="menu-icon icon-question-sign"></i>Preguntas de productos
							<?php
							$_pq_q = @mysqli_query($con,"SELECT COUNT(*) n FROM product_questions WHERE answer IS NULL");
							$_pq_n = $_pq_q ? intval(mysqli_fetch_assoc($_pq_q)['n']) : 0;
							if ($_pq_n > 0): ?>
							<b class="label orange pull-right"><?php echo $_pq_n; ?></b>
							<?php endif; ?>
							</a></li>
							<li><a href="live-chat.php"><i class="menu-icon icon-comment"></i>Chat en vivo
							<?php
							$_lc_q = @mysqli_query($con,"SELECT COUNT(*) n FROM live_chat_messages WHERE sender='user' AND is_read=0 AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
							$_lc_n = $_lc_q ? intval(mysqli_fetch_assoc($_lc_q)['n']) : 0;
							if ($_lc_n > 0): ?>
							<b class="label orange pull-right"><?php echo $_lc_n; ?></b>
							<?php endif; ?>
							</a></li>
							<li><a href="suppliers.php"><i class="menu-icon icon-truck"></i>Proveedores</a></li>
							<li><a href="challenges.php"><i class="menu-icon icon-trophy"></i>Retos y recompensas</a></li>
							<li><a href="subscriptions.php"><i class="menu-icon icon-repeat"></i>Suscripciones</a></li>
							<li><a href="shipping-zones.php"><i class="menu-icon icon-map-marker"></i>Zonas de envío</a></li>
							<li><a href="segmentation.php"><i class="menu-icon icon-group"></i>Segmentación clientes</a></li>
							<li><a href="sliders.php"><i class="menu-icon icon-picture"></i>Carrusel de imágenes</a></li>
							<li><a href="home-carousels.php"><i class="menu-icon icon-film"></i>Carruseles de productos</a></li>
							<li><a href="bundles.php"><i class="menu-icon icon-archive"></i>Bundles / Paquetes</a></li>
						<li><a href="brands.php"><i class="menu-icon icon-star"></i>Carrusel de marcas</a></li>
							<li>
								<a href="logout.php">
									<i class="menu-icon icon-signout"></i>
									Salir
								</a>
							</li>
						</ul>

					</div><!--/.sidebar-->
				</div><!--/.span3-->
<script>
(function(){
    document.body.classList.add('admin-has-sidebar');
    var sidebar = document.querySelector('.span3');
    var cur = window.location.pathname.split('/').pop().split('?')[0];

    // Marcar ítem activo y hacer scroll hacia él
    var activeLink = null;
    var links = document.querySelectorAll('.sidebar .widget-menu li a');
    links.forEach(function(a){
        var page = (a.getAttribute('href')||'').split('/').pop().split('?')[0];
        if(page && page === cur){ a.classList.add('sidebar-active'); activeLink = a; }
    });

    // Scroll: si hay ítem activo, ir a él; si no, restaurar posición guardada
    if(activeLink && sidebar){
        var offset = activeLink.getBoundingClientRect().top - sidebar.getBoundingClientRect().top + sidebar.scrollTop - 80;
        sidebar.scrollTop = Math.max(0, offset);
        localStorage.setItem('admin_sidebar_scroll', sidebar.scrollTop);
    } else {
        var savedScroll = localStorage.getItem('admin_sidebar_scroll');
        if(savedScroll && sidebar) sidebar.scrollTop = parseInt(savedScroll);
    }

    // Guardar posición al hacer clic en cualquier enlace del sidebar
    if(sidebar){
        sidebar.addEventListener('scroll', function(){
            localStorage.setItem('admin_sidebar_scroll', sidebar.scrollTop);
        });
    }
})();
</script>
