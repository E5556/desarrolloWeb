<?php
/**
 * seed.php — Poblar la BD con datos de prueba realistas
 * Ejecutar UNA VEZ en local: http://localhost/proyectowebmaster/seed.php
 * Luego borrar este archivo.
 */
set_time_limit(300);
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/config.php');

echo "<style>body{font-family:monospace;padding:20px;background:#111;color:#0f0}
.ok{color:#0f0}.err{color:#f55}.sec{color:#ff0;font-size:16px;margin-top:16px}</style>";

function log_ok($msg)  { echo "<div class='ok'>✓ $msg</div>"; }
function log_err($msg) { echo "<div class='err'>✗ $msg</div>"; }
function sec($msg)     { echo "<div class='sec'>── $msg ──</div>"; flush(); ob_flush(); }

// ─── Descargar imagen de picsum y guardarla ────────────────────────────────
function download_image($folder, $filename, $seed) {
    if (!is_dir($folder)) mkdir($folder, 0755, true);
    $path = $folder . '/' . $filename;
    if (file_exists($path)) return true;
    $url = "https://picsum.photos/seed/{$seed}/480/600";
    $ctx = stream_context_create(['http'=>['timeout'=>15,'follow_location'=>true]]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data) { file_put_contents($path, $data); return true; }
    return false;
}

// ══════════════════════════════════════════════════════════════════════════════
// 1. LIMPIAR datos anteriores (mantener admin y settings)
// ══════════════════════════════════════════════════════════════════════════════
sec("1. Limpiando datos anteriores");
$tables = ['productreviews','wishlist','orders','product_views','cart_events',
           'products','subcategory','category','users'];
mysqli_query($con, "SET FOREIGN_KEY_CHECKS=0");
foreach ($tables as $t) {
    mysqli_query($con, "TRUNCATE TABLE `$t`");
    log_ok("Limpiada tabla: $t");
}
mysqli_query($con, "SET FOREIGN_KEY_CHECKS=1");

// ══════════════════════════════════════════════════════════════════════════════
// 2. CATEGORÍAS
// ══════════════════════════════════════════════════════════════════════════════
sec("2. Insertando categorías");
$categories = [
    [1, 'Mujer',      'Moda femenina — vestidos, blusas, pantalones y más'],
    [2, 'Hombre',     'Moda masculina — camisas, pantalones, chaquetas y más'],
    [3, 'Accesorios', 'Bolsos, joyería, relojes y gafas de sol'],
    [4, 'Calzado',    'Tacones, deportivos, botines y sandalias'],
];
foreach ($categories as [$id, $name, $desc]) {
    $imgSeed = "cat{$id}banner";
    $imgFile = "cat_{$id}_banner.jpg";
    $imgFolder = __DIR__ . '/admin/categoryimages';
    download_image($imgFolder, $imgFile, $imgSeed);
    mysqli_query($con, "INSERT INTO category(id,categoryName,categoryDescription,categoryImage,creationDate)
        VALUES($id,'".mysqli_real_escape_string($con,$name)."','".mysqli_real_escape_string($con,$desc)."','$imgFile',NOW())");
    log_ok("Categoría: $name");
}

// ══════════════════════════════════════════════════════════════════════════════
// 3. SUBCATEGORÍAS
// ══════════════════════════════════════════════════════════════════════════════
sec("3. Insertando subcategorías");
$subcats = [
    // [id, categoryid, nombre]
    [1,  1, 'Vestidos'],
    [2,  1, 'Blusas'],
    [3,  1, 'Pantalones'],
    [4,  1, 'Ropa Deportiva'],
    [5,  2, 'Camisas'],
    [6,  2, 'Pantalones Hombre'],
    [7,  2, 'Chaquetas'],
    [8,  2, 'Ropa Deportiva Hombre'],
    [9,  3, 'Bolsos'],
    [10, 3, 'Joyería'],
    [11, 3, 'Relojes'],
    [12, 3, 'Gafas de Sol'],
    [13, 4, 'Tacones'],
    [14, 4, 'Deportivos'],
    [15, 4, 'Botines'],
    [16, 4, 'Sandalias'],
];
foreach ($subcats as [$id, $catid, $name]) {
    $imgSeed = "sub{$id}img";
    $imgFile = "sub_{$id}.jpg";
    $imgFolder = __DIR__ . '/admin/subcategoryimages';
    download_image($imgFolder, $imgFile, $imgSeed);
    mysqli_query($con, "INSERT INTO subcategory(id,categoryid,subcategory,subcategoryImage,creationDate)
        VALUES($id,$catid,'".mysqli_real_escape_string($con,$name)."','$imgFile',NOW())");
    log_ok("Subcategoría: $name");
}

// ══════════════════════════════════════════════════════════════════════════════
// 4. PRODUCTOS
// ══════════════════════════════════════════════════════════════════════════════
sec("4. Insertando productos e imágenes");

$products = [
    // [cat, subcat, nombre, empresa, precio, precioAntes, descripcion, disponibilidad, envio]
    // ── MUJER - Vestidos
    [1,1,'Vestido Floral Primavera','Zara',189000,250000,'Vestido midi de manga corta con estampado floral multicolor. Tela ligera de viscosa, perfecta para ocasiones casuales y formales. Incluye cinturón a juego.','In Stock',12000],
    [1,1,'Vestido Midi Elegante Negro','H&M',145000,180000,'Vestido midi de cuello en V, ideal para cenas y eventos. Tejido con elastano para mayor comodidad. Disponible en talla XS a XL.','In Stock',12000],
    [1,1,'Vestido Boho Largo','Mango',210000,280000,'Vestido largo estilo bohemio con bordados artesanales en el cuello. Perfecto para la playa o eventos al aire libre. Tela 100% algodón.','In Stock',15000],
    [1,1,'Vestido Mini Casual','Pull&Bear',98000,130000,'Vestido mini de punto acanalado. Diseño sencillo y versátil. Se puede combinar con sneakers o sandalias.','In Stock',9000],
    // ── MUJER - Blusas
    [1,2,'Blusa Seda Marfil','Massimo Dutti',135000,175000,'Blusa de seda con escote en V y mangas tipo globo. Elegante y sofisticada, ideal para el trabajo o una salida especial.','In Stock',10000],
    [1,2,'Blusa Estampada Tropical','Bershka',75000,99000,'Blusa de gasa con estampado tropical. Escote cuadrado y mangas cortas con volante. Muy fresca para el verano.','In Stock',8000],
    [1,2,'Blusa Lino Natural','Mango',110000,145000,'Blusa de lino 100% natural en color neutro. Corte relajado y cómodo, perfecta para el uso diario.','In Stock',10000],
    // ── MUJER - Pantalones
    [1,3,'Pantalón Wide Leg Blanco','Zara',165000,210000,'Pantalón de pierna ancha en tejido fluido color blanco roto. Cintura alta con cierre lateral. Ideal para outfits casuales y formales.','In Stock',12000],
    [1,3,'Jean Skinny Azul Oscuro','Levi\'s',220000,280000,'Jean skinny de denim premium con lavado oscuro. Cinco bolsillos, cierre con botón y cremallera. Alto porcentaje de elastano para mayor comodidad.','In Stock',15000],
    [1,3,'Pantalón Culotte Cuadros','H&M',120000,155000,'Pantalón culotte con estampado de cuadros en tonos neutros. Cierre con elástico en la cintura. Muy cómodo y de tendencia.','In Stock',10000],
    // ── MUJER - Ropa Deportiva
    [1,4,'Set Deportivo Rosa Neon','Adidas',285000,350000,'Conjunto deportivo de top y leggins en rosa neón. Tela técnica de secado rápido y control de humedad. Ideal para gym y yoga.','In Stock',15000],
    [1,4,'Leggins Compresión Negro','Nike',195000,240000,'Leggins de compresión con bolsillo lateral y cintura alta. Tela Dri-FIT de alta tecnología. Soporte máximo para actividades de alta intensidad.','In Stock',12000],
    // ── HOMBRE - Camisas
    [2,5,'Camisa Oxford Azul Marino','Tommy Hilfiger',245000,310000,'Camisa Oxford de algodón en azul marino. Corte slim fit con botones de nácar. Versátil para ocasiones formales y casuales.','In Stock',12000],
    [2,5,'Camisa Lino Beige','Massimo Dutti',215000,270000,'Camisa de lino 100% en tono beige arena. Corte regular y cuello italiano. Perfecta para climas cálidos.','In Stock',12000],
    [2,5,'Camisa Franela Cuadros','Levis',155000,195000,'Camisa de franela con estampado de cuadros en rojo y negro. Ideal para un look casual masculino. Bolsillo frontal doble.','In Stock',10000],
    [2,5,'Camisa Polo Blanca','Ralph Lauren',185000,230000,'Polo clásico de piqué de algodón. Logo bordado en el pecho. Ideal para un look smart casual en cualquier ocasión.','In Stock',12000],
    // ── HOMBRE - Pantalones
    [2,6,'Chino Khaki Slim','Zara Man',155000,195000,'Pantalón chino en color khaki con corte slim fit. Cierre con botón y cremallera. Combinable con camisas formales o camisetas casuales.','In Stock',12000],
    [2,6,'Jean Straight Dark Blue','Levi\'s 511',235000,295000,'Jean de corte recto en azul oscuro lavado. Cinco bolsillos clásicos. Tejido denim rígido de alta calidad. El pantalón básico de todo hombre.','In Stock',15000],
    [2,6,'Pantalón Jogger Gris','Nike',175000,215000,'Pantalón jogger en felpa francesa color gris jaspeado. Cintura ajustable y bolsillos laterales con cremallera. Cómodo para el día a día.','In Stock',12000],
    // ── HOMBRE - Chaquetas
    [2,7,'Chaqueta Cuero Sintético Negra','Zara Man',385000,480000,'Chaqueta de cuero sintético en negro mate. Cierre con cremallera frontal y bolsillos laterales. Forro interior acolchado.','In Stock',18000],
    [2,7,'Bomber Verde Militar','Pull&Bear',265000,330000,'Chaqueta bomber en nylon verde militar. Puños y cintura elásticos. Bolsillo en el pecho con cremallera. Look urbano y moderno.','In Stock',15000],
    // ── HOMBRE - Ropa Deportiva
    [2,8,'Conjunto Deportivo Gris','Adidas',320000,395000,'Sudadera y pantalón jogger a juego en gris melange. Logo Adidas en contraste blanco. Tela de algodón orgánico.','In Stock',15000],
    [2,8,'Camiseta Técnica Azul','Under Armour',125000,155000,'Camiseta de entrenamiento con tecnología HeatGear. Tejido de secado ultra rápido y protección UV. Ideal para running y deportes al aire libre.','In Stock',10000],
    // ── ACCESORIOS - Bolsos
    [3,9,'Bolso Shopper Cuero Camel','Michael Kors',520000,680000,'Bolso shopper grande en cuero genuino color camel. Asas largas de cuero trenzado. Interior con bolsillo con cremallera y bolsillos laterales.','In Stock',0],
    [3,9,'Clutch Noche Dorado','Zara',185000,235000,'Clutch de noche en tejido metálico dorado. Cierre magnético y cadena desmontable. Capacidad para teléfono, tarjetas y llaves.','In Stock',0],
    [3,9,'Mochila Mini Negra','Calvin Klein',295000,370000,'Mochila mini en nylon negro con logo grabado. Perfecta para el día a día. Compartimento principal con bolsillos internos.','In Stock',0],
    // ── ACCESORIOS - Joyería
    [3,10,'Collar Perlas Naturales','Tous',345000,430000,'Collar de perlas cultivadas con cierre de plata 925. Longitud 45cm ajustable. Viene en caja de regalo.','In Stock',0],
    [3,10,'Aretes Aro Dorados XL','Pandora',125000,155000,'Aretes de aro extra grandes en baño de oro 18k. Diámetro 5cm. Ligeros y cómodos para uso diario.','In Stock',0],
    [3,10,'Pulsera Charm Plata','Pandora',280000,350000,'Pulsera de plata 925 con 3 charms incluidos. Se pueden agregar más charms. Cierre con serpiente característica de la marca.','In Stock',0],
    // ── ACCESORIOS - Relojes
    [3,11,'Reloj Minimalista Blanco','Daniel Wellington',680000,850000,'Reloj de pulsera con esfera blanca y correa de cuero marrón intercambiable. Movimiento de cuarzo. Resistente al agua 3ATM.','In Stock',0],
    [3,11,'Reloj Smartwatch Negro','Samsung Galaxy',1250000,1550000,'Smartwatch con pantalla AMOLED 1.4". Monitor de ritmo cardíaco, GPS integrado, resistencia al agua IP68. Compatible con Android e iOS.','In Stock',0],
    // ── ACCESORIOS - Gafas de Sol
    [3,12,'Gafas Aviador Doradas','Ray-Ban',520000,650000,'Gafas de sol estilo aviador con montura dorada y lentes verdes clásico. Protección UV400. Incluye estuche y paño de limpieza.','In Stock',0],
    [3,12,'Gafas Cat Eye Carey','Hawkers',185000,230000,'Gafas de sol estilo cat-eye en carey con lentes degradados rosas. Montura de acetato premium. Protección UV400 homologada.','In Stock',0],
    // ── CALZADO - Tacones
    [4,13,'Stiletto Negro Clásico','Steve Madden',380000,475000,'Stiletto de 10cm en cuero negro con puntera fina. Plantilla acolchada para mayor comodidad. Ideal para eventos formales y cenas.','In Stock',12000],
    [4,13,'Mule Tacón Bajo Camel','Zara',245000,305000,'Mule destalonado con tacón cuadrado bajo en color camel. Tira en el empeine con hebilla dorada. Muy cómoda para uso prolongado.','In Stock',12000],
    // ── CALZADO - Deportivos
    [4,14,'Sneakers Blancas Classic','Adidas Stan Smith',385000,480000,'Zapatillas clásicas Stan Smith en cuero blanco con detalle verde. La zapatilla icónica de todos los tiempos. Suela de goma vulcanizada.','In Stock',12000],
    [4,14,'Running Boost Neon','Nike Air Max',465000,580000,'Zapatillas de running con cámara de aire Max visible. Suela React para máxima amortiguación. Upper de mesh transpirable en colorway neon.','In Stock',15000],
    [4,14,'Sneakers Plataforma Beige','New Balance',420000,525000,'Zapatillas chunky con plataforma de 4cm en color beige. Upper de cuero y mesh. Tendencia dad shoes que no pasa de moda.','In Stock',12000],
    // ── CALZADO - Botines
    [4,15,'Botín Chelsea Camel','Zara',365000,455000,'Botín Chelsea en cuero genuino color camel. Elásticos laterales y lengüeta trasera. Suela de goma antideslizante.','In Stock',12000],
    [4,15,'Botín Cowboy Bordado','Pull&Bear',285000,355000,'Botín estilo cowboy con bordados florales en tono turquesa. Punta cuadrada y tacón western. Muy trendy esta temporada.','In Stock',12000],
    // ── CALZADO - Sandalias
    [4,16,'Sandalia Plana Trenzada','Mango',155000,195000,'Sandalia plana en cuero trenzado color cuero natural. Cierre con hebilla dorada en el tobillo. Perfecta para el verano.','In Stock',9000],
    [4,16,'Sandalia Gladiadora Negra','Zara',185000,230000,'Sandalia gladiadora con múltiples tiras que llegan hasta la rodilla. Suela plana de cuero. Un must-have de la temporada.','In Stock',10000],
];

$pid = 1;
foreach ($products as $p) {
    [$cat,$sub,$name,$company,$price,$priceOld,$desc,$avail,$ship] = $p;
    $name    = mysqli_real_escape_string($con, $name);
    $company = mysqli_real_escape_string($con, $company);
    $desc    = mysqli_real_escape_string($con, $desc);
    $avail   = mysqli_real_escape_string($con, $avail);

    // 3 imágenes por producto (seeds distintos)
    $img1 = "prod{$pid}_1.jpg";
    $img2 = "prod{$pid}_2.jpg";
    $img3 = "prod{$pid}_3.jpg";
    $imgDir = __DIR__ . "/admin/productimages/{$pid}";

    // Seeds temáticos por categoría para imágenes más coherentes
    $seeds = [
        1 => ['fashion','dress','women'],
        2 => ['man','shirt','mensfashion'],
        3 => ['accessories','bag','jewelry'],
        4 => ['shoes','sneakers','boots'],
    ];
    $s = $seeds[$cat] ?? ['product','item','shop'];
    download_image($imgDir, $img1, $s[0].$pid);
    download_image($imgDir, $img2, $s[1].$pid);
    download_image($imgDir, $img3, $s[2].$pid);

    $r = mysqli_query($con, "INSERT INTO products
        (id,category,subCategory,productName,productCompany,productPrice,
         productPriceBeforeDiscount,productDescription,productImage1,productImage2,
         productImage3,shippingCharge,productAvailability,postingDate)
        VALUES($pid,$cat,$sub,'$name','$company',$price,$priceOld,'$desc',
               '$img1','$img2','$img3',$ship,'$avail',NOW())");
    if ($r) log_ok("Producto #$pid: {$p[2]}");
    else    log_err("Error producto #$pid: ".mysqli_error($con));
    $pid++;
}

// ══════════════════════════════════════════════════════════════════════════════
// 5. USUARIOS
// ══════════════════════════════════════════════════════════════════════════════
sec("5. Insertando usuarios");
$users = [
    ['Laura Martínez','laura@gmail.com','3001234567','Calle 45 #12-30','Cundinamarca','Bogotá','110111'],
    ['Carlos Rodríguez','carlos@hotmail.com','3109876543','Carrera 70 #5-15','Antioquia','Medellín','050001'],
    ['María Gómez','maria@yahoo.com','3156789012','Avenida El Dorado #68-95','Cundinamarca','Bogotá','110221'],
    ['Andrés Torres','andres@gmail.com','3204567890','Calle 93 #14-20','Cundinamarca','Bogotá','110221'],
    ['Valentina López','vale@gmail.com','3001122334','Carrera 15 #82-65','Cundinamarca','Bogotá','110221'],
    ['Diego Hernández','diego@outlook.com','3215678901','Calle 10 #43-20','Valle del Cauca','Cali','760001'],
    ['Camila Sánchez','camila@gmail.com','3123456789','Carrera 43A #5-113','Antioquia','Medellín','050021'],
    ['Sebastián Vargas','sebas@gmail.com','3187654321','Calle 72 #10-07','Atlántico','Barranquilla','080001'],
    ['Isabella Moreno','isa@gmail.com','3009988776','Carrera 9 #74-08','Cundinamarca','Bogotá','110221'],
    ['Felipe Castro','felipe@gmail.com','3145566778','Calle 134 #55-30','Cundinamarca','Bogotá','110111'],
];
$uid = 1;
foreach ($users as [$name,$email,$tel,$addr,$state,$city,$pin]) {
    $name  = mysqli_real_escape_string($con,$name);
    $email = mysqli_real_escape_string($con,$email);
    $addr  = mysqli_real_escape_string($con,$addr);
    $pass  = password_hash('password123', PASSWORD_DEFAULT);
    mysqli_query($con,"INSERT INTO users(id,name,email,contactno,password,
        shippingAddress,shippingState,shippingCity,shippingPincode,
        billingAddress,billingState,billingCity,billingPincode,regDate)
        VALUES($uid,'$name','$email','$tel','$pass',
               '$addr','$state','$city','$pin',
               '$addr','$state','$city','$pin',NOW())");
    log_ok("Usuario: $name ($email)");
    $uid++;
}

// ══════════════════════════════════════════════════════════════════════════════
// 6. ÓRDENES
// ══════════════════════════════════════════════════════════════════════════════
sec("6. Insertando órdenes");
$orders = [
    [1,5,1,'COD','Delivered','-15 days'],
    [1,12,2,'Debit / Credit card','Delivered','-30 days'],
    [2,3,1,'COD','Delivered','-20 days'],
    [2,18,1,'COD','in Process','-2 days'],
    [3,7,2,'Debit / Credit card','Delivered','-45 days'],
    [3,25,1,'COD','Delivered','-10 days'],
    [4,1,3,'Debit / Credit card','Delivered','-60 days'],
    [4,30,1,'COD','in Process','-1 days'],
    [5,10,1,'COD','Delivered','-25 days'],
    [5,15,2,'Debit / Credit card','Delivered','-35 days'],
    [6,22,1,'COD','Delivered','-50 days'],
    [6,8,1,'COD','in Process','-3 days'],
    [7,4,2,'Debit / Credit card','Delivered','-18 days'],
    [7,33,1,'COD','Delivered','-40 days'],
    [8,19,1,'COD','Delivered','-55 days'],
    [8,2,3,'Debit / Credit card','Delivered','-12 days'],
    [9,11,1,'COD','in Process','-4 days'],
    [9,27,2,'COD','Delivered','-22 days'],
    [10,6,1,'Debit / Credit card','Delivered','-38 days'],
    [10,14,1,'COD','Delivered','-48 days'],
    [1,20,2,'COD','Delivered','-65 days'],
    [2,9,1,'Debit / Credit card','Delivered','-70 days'],
    [3,31,1,'COD','Delivered','-28 days'],
    [4,16,2,'COD','Delivered','-33 days'],
    [5,38,1,'Debit / Credit card','Delivered','-42 days'],
];
$oid = 1;
foreach ($orders as [$userId,$productId,$qty,$payment,$status,$daysAgo]) {
    $date = date('Y-m-d H:i:s', strtotime("now $daysAgo"));
    $r = mysqli_query($con,"INSERT INTO orders(id,userId,productId,quantity,orderDate,paymentMethod,orderStatus)
        VALUES($oid,$userId,$productId,$qty,'$date','$payment','$status')");
    if ($r) log_ok("Orden #$oid: usuario $userId → producto $productId");
    else    log_err("Error orden #$oid: ".mysqli_error($con));
    $oid++;
}

// ══════════════════════════════════════════════════════════════════════════════
// 7. RESEÑAS
// ══════════════════════════════════════════════════════════════════════════════
sec("7. Insertando reseñas");
$reviews = [
    [5,'Laura Martínez',5,5,5,'¡Me encantó!','Excelente calidad, llegó rápido y el empaque era perfecto. Totalmente recomendado.','approved',1],
    [12,'Carlos Rodríguez',4,5,4,'Muy buena compra','La prenda es exactamente como en las fotos. La tela es de buena calidad. Volvería a comprar.','approved',1],
    [3,'María Gómez',5,4,5,'Hermoso vestido','Me quedó perfecto, el estampado es precioso en persona. La entrega fue muy rápida.','approved',1],
    [7,'Andrés Torres',4,4,4,'Buena chaqueta','La chaqueta es bonita y de buena calidad. El talle es un poco grande, pedir una talla menos.','approved',1],
    [25,'Valentina López',5,5,5,'Bolso increíble','El cuero es genuino y muy resistente. Los acabados son perfectos. Vale cada peso.','approved',1],
    [30,'Diego Hernández',5,5,5,'Reloj espectacular','Se ve muy lujoso, la correa de cuero es suave y la esfera muy elegante. Súper recomendado.','approved',1],
    [1,'Camila Sánchez',4,4,4,'Vestido muy bonito','Me encantó el vestido, la tela es suave y fresca. Lo usé en una boda y recibí muchos elogios.','approved',1],
    [10,'Sebastián Vargas',5,4,5,'Excelentes zapatillas','Muy cómodas desde el primer uso, sin periodo de adaptación. El diseño es muy actual.','pending',0],
    [22,'Isabella Moreno',4,5,4,'Camisa de calidad','La camisa de lino es perfecta para el calor. Se ve elegante y es muy fresca.','approved',1],
    [15,'Felipe Castro',5,5,5,'Sandalias perfectas','Son exactamente como en la foto, muy cómodas y de buena calidad. Las uso todos los días.','approved',1],
    [38,'Laura Martínez',4,4,4,'Buenos deportivos','Muy cómodos para correr, el material es transpirable. El único detalle es que el talle corre grande.','pending',0],
    [9,'Carlos Rodríguez',5,5,5,'Jeans perfectos','Los mejores jeans que he comprado. Se amoldan perfecto al cuerpo y no pierden la forma al lavar.','approved',1],
    [33,'María Gómez',4,4,5,'Gafas lindísimas','Las gafas son preciosas y de buena calidad. La protección UV es real. Llegaron con estuche y paño.','approved',1],
    [19,'Andrés Torres',5,4,5,'Botín muy trendy','Se ven increíbles, el bordado es detallado y bonito. Cómodos para caminar todo el día.','approved',1],
    [27,'Valentina López',4,5,4,'Buen bolso','La mochila es práctica y de buena calidad. El cierre es resistente. Ideal para el trabajo.','pending',0],
];
$rid = 1;
foreach ($reviews as [$productId,$name,$quality,$price,$value,$summary,$review,$status,$verified]) {
    $name    = mysqli_real_escape_string($con,$name);
    $summary = mysqli_real_escape_string($con,$summary);
    $review  = mysqli_real_escape_string($con,$review);
    $userId  = ($rid <= 10) ? $rid : ($rid % 10) + 1;
    mysqli_query($con,"INSERT INTO productreviews
        (id,productId,userId,quality,price,value,name,summary,review,status,verified,reviewDate)
        VALUES($rid,$productId,$userId,$quality,$price,$value,'$name','$summary','$review','$status',$verified,NOW())");
    log_ok("Reseña #$rid: $name → producto $productId");
    $rid++;
}

// ══════════════════════════════════════════════════════════════════════════════
// 8. SLIDERS (banners del home)
// ══════════════════════════════════════════════════════════════════════════════
sec("8. Insertando sliders");
$sliders = [
    ['banner_1.jpg','Nueva Colección Mujer','mujerfashion',1,'mujer'],
    ['banner_2.jpg','Moda Hombre Premium','hombrefashion',2,'hombre'],
    ['banner_3.jpg','Accesorios Exclusivos','accessorios',3,'accesorios'],
];
mysqli_query($con, "DELETE FROM sliders");
foreach ($sliders as [$file,$title,$seed,$sort,$kw]) {
    $imgDir = __DIR__ . '/assets/images/sliders';
    if (!is_dir($imgDir)) mkdir($imgDir, 0755, true);
    $url = "https://picsum.photos/seed/{$seed}/1200/500";
    $ctx = stream_context_create(['http'=>['timeout'=>15,'follow_location'=>true]]);
    $data = @file_get_contents($url, false, $ctx);
    $imgPath = 'assets/images/sliders/'.$file;
    if ($data) {
        file_put_contents(__DIR__.'/'.$imgPath, $data);
        $kw_esc = mysqli_real_escape_string($con,$kw);
        mysqli_query($con,"INSERT INTO sliders(image_path,keyword,sort_order,active)
            VALUES('$imgPath','$kw_esc',$sort,1)");
        log_ok("Slider: $title");
    } else {
        log_err("No se pudo descargar banner: $file");
    }
}

// ══════════════════════════════════════════════════════════════════════════════
echo "<br><div style='color:#ff0;font-size:18px'>
✅ ¡Seed completado!<br>
<a href='index2.php' style='color:#0af'>→ Ver tienda</a> &nbsp;
<a href='admin/dashboard.php' style='color:#0af'>→ Ver admin</a>
</div>";
echo "<br><div style='color:#f55'>⚠️ Elimina este archivo (seed.php) después de ejecutarlo.</div>";
