<?php

$LOCALHOST = "localhost";
$USERNAME = "root";
$BDPASSWORD = "";
$BDNAME = "registros";


$conectar = mysqli_connect($LOCALHOST, $USERNAME, $BDPASSWORD, $BDNAME);

if (!$conectar) {

	/*die("Error al conectar la BD: " . mysqli_connect_error());*/
	echo "Error al conectar la BD";
}
else
{
	echo "Conectado Exitosamente";	
}


?>