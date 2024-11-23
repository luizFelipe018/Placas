<?php
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    // Se não estiver logado, redireciona para a página de login
    header('Location: login.php');
    exit(); // Encerra a execução do script para evitar que o restante da página seja carregado
}

// Saída de boas-vindas
$usuario = $_SESSION['usuario'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Controle</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .dashboard-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 300px;
            text-align: center;
        }
        h1 {
            color: #333;
        }
        button {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }
        .placa-amarela-grande {
            background-color: #f39c12;
        }
        .placa-amarela-pequena {
            background-color: #f1c40f;
        }
        .placa-branca {
            background-color: #ecf0f1;
            color: #333;
        }
        button:hover {
            opacity: 0.9;
        }
        a {
            display: inline-block;
            margin-top: 20px;
            color: #3498db;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <h1>Bem-vindo, <?php echo $_SESSION['usuario']; ?>!</h1>
    <p>Escolha uma opção:</p>

    <!-- Botões para placas -->
    <form action="processa_plaque.php" method="POST">
        <button type="button" class="placa-amarela-grande" onclick="window.location.href='index.php';">Placa Amarela Grande</button>
        <button type="submit" name="placa" value="placa_amarela_pequena" class="placa-amarela-pequena">Placa Amarela Pequena</button>
    </form>

    <!-- Botão de redirecionamento para Placa Branca -->
    <button type="button" class="placa-branca" onclick="window.location.href='placa_branca.php';">Placa Branca</button>

    <a href="logout.php">Sair</a>
</div>

</body>
</html>
