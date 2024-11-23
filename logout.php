<?php
session_start();

// Destruir a sessão e redirecionar para a página de login
session_unset();
session_destroy();
header('Location: login.php');
exit();
