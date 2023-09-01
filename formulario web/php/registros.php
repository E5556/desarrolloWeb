<?php


include_once 'conexion.php';//llamada de un archivo externo

$Nombres = $_REQUEST['Nombre'];//llamada a variables
$Apellidos = $_REQUEST['Apellido'];
$Usuarios = $_REQUEST['Usuario'];
$Correos = $_REQUEST['Correo'];
$Fnacimientos = $_REQUEST['Fnacimiento'];
$Codpostals = $_REQUEST['Codpostal'];
$Direcciones = $_REQUEST['Direccion'];
$Telefonos = $_REQUEST['Telefono'];
$Passwords1 = $_REQUEST['Password1'];
$Passwords2 = $_REQUEST['Password2'];
$ImagenPerfiles = $_REQUEST['ImagenPerfil'];



//validacion de tamaño de archivo


$imagen_perfil = $_FILES['ImagenPerfil'];

// Verificar si se ha cargado un archivo
if ($imagen_perfil['error'] == 0) {
    // Verificar el tamaño del archivo (5 megabytes)
    $max_size = 5 * 1024 * 1024; // 5MB en bytes
    if ($imagen_perfil['size'] <= $max_size) {
        // El archivo tiene un tamaño válido, puedes procesarlo aquí
    } else {
        // El archivo es demasiado grande, muestra un mensaje de error
        echo "La imagen de perfil debe ser menor o igual a 5MB.";
		mysqli_close($conectar);
    }
} else {
    // Ocurrió un error al cargar el archivo, muestra un mensaje de error
    echo "Ocurrió un error al cargar la imagen de perfil.";
	mysqli_close($conectar);
}







$insertar = "INSERT INTO `usuarios`(`NOMBRES`, `APELLIDOS`, `USUARIO`, `CORREO`, `FECHA`, `codigopostal`, `direccion`, `telefono`, `password1`, `password2`, `imagenperfil`) 
VALUES ('$Nombres','$Apellidos','$Usuarios','$Correos','$Fnacimientos','$Codpostals', '$Direcciones', '$Telefonos', '$Passwords1', '$Passwords2', '$ImagenPerfiles')";


$resultado = mysqli_query($conectar, $insertar);

if (!$resultado) {
	echo '<script>alert("Error al Registrar") </script>';
		header("Location:../index3.php");
}else{
	echo '<script>alert("Registro Exitoso") </script>';
	header("Location:../index3.php");

}

mysqli_close($conectar);

?>


