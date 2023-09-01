<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login y Register - MagtimusPro</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">


    <link rel="stylesheet" href="css/style.css">
	<style>
        /* Estilos para el formulario */
        .formulario__register {
            margin-top: 550px; /* Ajustar el margen superior según sea necesario */
        }
    </style>
</head>
<body>

        <main>

            <div class="contenedor__todo">
                <div class="caja__trasera">
                    <div class="caja__trasera-login">
                        <h3>¿Ya tienes una cuenta?</h3>
                        <p>Inicia sesión para entrar en la página</p>
                        <button id="btn__iniciar-sesion">Iniciar Sesión</button>
                    </div>
                    <div class="caja__trasera-register">
                        <h3>¿Aún no tienes una cuenta?</h3>
                        <p>Regístrate para que puedas iniciar sesión</p>
                        <button id="btn__registrarse">Regístrarse</button>
                    </div>
                </div>

                <!--Formulario de Login y registro-->
                <div class="contenedor__login-register">
                    <!--Login-->
                    <form action="" class="formulario__login">
                        <h2>Iniciar Sesión</h2>
                        <input type="text" placeholder="Correo Electronico">
                        <input type="password" placeholder="Contraseña">
                        <button>Entrar</button>
                    </form>

                    <!--Register-->
					<form class="styled-form formulario__register form-expanded" action="php/registros.php" method="post">
						

                    <h2>Registrarse</h2>

                    <input class="controls" type="text" name="Nombre" placeholder="Ingrese Nombres" required><br><br>
					<input class="controls" type="text" name="Apellido" placeholder="Ingrese Apellidos" required><br><br>
					<input class="controls"type="text" name="Usuario" placeholder="Ingrese Usuario" required><br><br>
					<input class="controls"type="text" name="Correo" placeholder="Ingrese Correo" required> <br><br>

					<div class="input-container">
    				<input class="controls" type="date" name="Fnacimiento" placeholder="Ingrese Fecha Nacimiento" title="Seleccione su fecha de nacimiento"required><br><br>
					</div>


					<input class="controls"type="text" name="Codpostal" placeholder="Ingrese Codigo Postal" required><br><br>
					<input class="controls"type="text" name="Direccion" placeholder="Ingrese Direccion" required><br><br>
					<input class="controls"type="text" name="Telefono" placeholder="Ingrese Teléfono" required><br><br>
					

					<fieldset class="password-fieldset">
    				<legend>Contraseña</legend>
					<input class="controls"type="text" name="Password1" placeholder="Digite Password" required><br><br>
					<input class="controls"type="text" name="Password2" placeholder="Confirme Password" required><br><br>
					</fieldset>

					<input class="controls" type="file" name="ImagenPerfil" accept="image/*" required>
       	 			<span class="tooltip-text">Seleccione una imagen de perfil</span>
  
					
					<!--<button>Regístrarse</button>-->
					<input class="botons" type="submit" value="Registrar">	
					
				</div>

                    </form>
                </div>
            </div>

        </main>

        <script src="js/script.js"></script>
</body>
</html>


























<!--<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<link rel="stylesheet" href="css/style.css">

	<title>Formulario de Registro</title>
</head>
<body class="pagina2">
	<header>   
		<section class="form-register">
			<center> 
				<h4>Formulario de Registro</h4>
				<form class="styled-form" action="php/registros.php" method="post">
				
					<input class="controls" type="text" name="Nombre" placeholder="Ingrese Nombres" required><br><br>
					<input class="controls" type="text" name="Apellido" placeholder="Ingrese Apellidos" required><br><br>
					<input class="controls"type="text" name="Usuario" placeholder="Ingrese Usuario" required><br><br>
					<input class="controls"type="text" name="Correo" placeholder="Ingrese Correo" required> <br><br>

					<div class="input-container">
					<input class="controls"type="date" name="Fnacimiento" placeholder="Ingrese Fecha Nacimiento" required><br><br>
					<span class="tooltip-text">Fecha De Nacimiento</span>
					</div>

					<input class="controls"type="text" name="Codpostal" placeholder="Ingrese Codigo Postal" required><br><br>
					<input class="controls"type="text" name="Direccion" placeholder="Ingrese Direccion" required><br><br>
					<input class="controls"type="text" name="Telefono" placeholder="Ingrese Teléfono" required><br><br>
					</div>
					<p>Estoy de acuerdo con <a href="#">Terminos y Condiciones</a></p>
					<input class="botons" type="submit" value="Registrar"> 
					<p><a href="#">¿Ya tengo Cuenta?</a></p>
				</form>
			</center>
		</section>
	
</body>
</html>-->


