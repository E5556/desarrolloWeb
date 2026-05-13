<?php
session_start();
error_reporting(0);
include('includes/config.php');
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Página no encontrada | <?php echo $_SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/green.css">
    <link rel="stylesheet" href="assets/css/cosmetics.css">
    <link rel="stylesheet" href="assets/css/font-awesome.min.css">
    <link rel="shortcut icon" href="<?php echo $_SITE_FAVICON; ?>">
    <style>
    .page-404-wrap {
        text-align: center;
        padding: 70px 20px 50px;
        min-height: 400px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .page-404-num {
        font-size: 110px;
        font-weight: 900;
        color: #eee;
        line-height: 1;
        position: relative;
        letter-spacing: -4px;
    }
    .page-404-num span {
        color: #e8233a;
    }
    .page-404-icon {
        font-size: 54px;
        color: #e8233a;
        margin-bottom: 12px;
        margin-top: -20px;
    }
    .page-404-wrap h2 {
        font-size: 1.8em;
        font-weight: 700;
        color: #333;
        margin-bottom: 10px;
    }
    .page-404-wrap p {
        color: #888;
        font-size: 1.05em;
        margin-bottom: 28px;
        max-width: 400px;
    }
    .page-404-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        justify-content: center;
    }
    .page-404-search {
        margin-top: 28px;
        display: flex;
        gap: 0;
        max-width: 420px;
        width: 100%;
    }
    .page-404-search input {
        flex: 1;
        border: 1.5px solid #ddd;
        border-right: none;
        border-radius: 5px 0 0 5px;
        padding: 9px 14px;
        font-size: 14px;
        outline: none;
    }
    .page-404-search button {
        border-radius: 0 5px 5px 0;
        padding: 9px 18px;
    }
    </style>
</head>
<body class="cnt-home">
<header class="header-style-1">
<?php include('includes/top-header.php');?>
<?php include('includes/main-header.php');?>
<?php include('includes/menu-bar.php');?>
</header>

<div class="body-content outer-top-xs">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="page-404-wrap">
                    <div class="page-404-num"><span>4</span>0<span>4</span></div>
                    <div class="page-404-icon"><i class="fa fa-search"></i></div>
                    <h2>Página no encontrada</h2>
                    <p>Lo sentimos, la página que buscas no existe o fue movida. Verifica la dirección o vuelve al inicio.</p>
                    <div class="page-404-actions">
                        <a href="index2.php" class="btn btn-primary btn-upper">
                            <i class="fa fa-home" style="margin-right:6px"></i> Ir al inicio
                        </a>
                        <a href="javascript:history.back()" class="btn btn-default btn-upper">
                            <i class="fa fa-arrow-left" style="margin-right:6px"></i> Volver
                        </a>
                    </div>
                    <form class="page-404-search" action="search-result.php" method="get">
                        <input type="text" name="product" placeholder="Buscar productos...">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php');?>
<script src="assets/js/jquery-1.11.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/owl.carousel.min.js"></script>
<script src="assets/js/scripts.js"></script>
<script src="assets/js/cart-drawer.js"></script>
<script src="assets/js/cookies.js"></script>
</body>
</html>
