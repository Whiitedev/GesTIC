<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>GesTIC</title>
    <link rel="stylesheet" href="css/style.css"/>
  </head>
  <body>
    <div>
      <h1 class="title">Bienvenido a GesTIC</h1>
      <div class="box">
        <form class="form">
          <img src="img/Logo-sin_fondo.png" class="logo" alt="Logo GesTIC" />
          <p class="text">
            Para utilizar las funciones del sistema, debera disponer de una
            cuenta
          </p>
          <div class="buttons-container">
              <a href = "inicio_sesion/login.php" class="log_in">Iniciar Sesion</button>
              <a href = "registro/registro.php" class="sign_in">Registrate</button>
          </div>
        </form>
      </div>
    </div>
  </body>
</html>
