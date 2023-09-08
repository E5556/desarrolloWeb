<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Landing Page</title>
  <link rel="stylesheet" href="css/style.css">
</head> 
<body>

<div> 
  <center><h1>"Listado de usuarios registrados"</h1> </center>    
</div>

<div id="conteintable"> 
  <table id="conteinertable" style="margin: auto; width: 1000px; border-collapse: separate; border-spacing: 30px 20px;">


 


<thead>
  
<tr>
<th class="unique-header"colspan="3">NOMBRES</th>
<th class="unique-header"colspan="2">APELLIDOS</th>
<th class="unique-header" colspan="2">USUARIO</th>
<th class="unique-header" colspan="2">CORREO</th>
<th class="unique-header" colspan="2">FECHA</th>
<th class="unique-header" colspan="2">codigopostal</th>
<th class="unique-header" colspan="2">direccion</th>
<th class="unique-header" colspan="2">telefono</th>
<th class="unique-header" colspan="2" style="display: none;">password1</th>
</tr>
</thead>
<tbody>
<?php
include_once 'php/conexion.php';

$consultarusuarios = "SELECT * FROM `usuarios`";
$resultadosusuarios = $conectar->query($consultarusuarios) or die(mysqli_error($conectar));

$contador = 1;

while ($fila = $resultadosusuarios->fetch_assoc()) {
  echo "<tr>";
  echo "<td>" . $contador . "</td>"; // Muestra el contador como una columna en la tabla
  echo "<td>"; echo $fila['NOMBRES'];echo "<td>";
  echo "<td>"; echo $fila['APELLIDOS'];echo "<td>";
echo "<td>"; echo $fila['USUARIO'];echo "<td>";
echo "<td>"; echo $fila['CORREO'];echo "<td>";
echo "<td>"; echo $fila['FECHA'];echo "<td>";
echo "<td>"; echo $fila['codigopostal'];echo "<td>";
echo "<td>"; echo $fila['direccion'];echo "<td>";
echo "<td>"; echo $fila['telefono'];echo "<td>";
echo "<td style='display: none'>"; echo $fila['password1'];echo "<td>";
  echo "</tr>";

  $contador++;
}
?>
 </tbody>
</table>  

</div>


<div id="filter-container">
  <label for="filter-input">Filtrar en la lista:</label>
  <input type="text" id="filter-input">
  <button style="display: none;" id="filter-button">Filtrar</button>


  <button id="clear-filter-button">Borrar Filtro</button>


</div>





  
<script type="text/javascript">
  document.addEventListener("DOMContentLoaded", function() {
    // Obtén una referencia a los elementos relevantes
    const filterInput = document.getElementById("filter-input");
    const filterButton = document.getElementById("filter-button");
    const clearFilterButton = document.getElementById("clear-filter-button");
    const tableRows = document.querySelectorAll("#conteinertable tbody tr");

    // Función para aplicar el filtro
    function applyFilter() {
      const filterText = filterInput.value.toLowerCase();

      // Itera a través de las filas de la tabla y muestra/oculta según coincidan
      tableRows.forEach(function(row) {
        const rowData = row.textContent.toLowerCase();
        if (rowData.includes(filterText)) {
          row.style.display = "table-row"; // Muestra la fila
        } else {
          row.style.display = "none"; // Oculta la fila
        }
      });
    }

    // Agrega un evento de clic al botón de filtrar
    filterButton.addEventListener("click", applyFilter);

    // Agrega un evento de clic al botón de borrar filtro
    clearFilterButton.addEventListener("click", function() {
      // Limpia el contenido del campo de entrada
      filterInput.value = "";

      // Muestra todas las filas nuevamente
      tableRows.forEach(function(row) {
        row.style.display = "table-row";
      });
    });

    // Agrega un evento de entrada al campo de entrada para aplicar el filtro en tiempo real
    filterInput.addEventListener("input", applyFilter);
  });
</script>



</body>
</html>
