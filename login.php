<?php
session_start();

// Verificar se o usuário já está logado
if (isset($_SESSION['usuario'])) {
    header('Location: dashboard.php');
    exit();
}

// Verificação do login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = $_POST['usuario'];
    $senha = $_POST['senha'];

    // Credenciais de login
    $usuarioCorreto = 'SuperAvanzi';
    $senhaCorreta = 'SuperAvanzi';

    // Verificação do usuário e senha
    if ($usuario == $usuarioCorreto && $senha == $senhaCorreta) {
        $_SESSION['usuario'] = $usuario;
        header('Location: dashboard.php'); // Redireciona para a página segura
        exit();
    } else {
        $erro = 'Usuário ou senha incorretos!';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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
        .login-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 300px;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 16px;
        }
        button:hover {
            background-color: #45a049;
        }
        .erro {
            color: red;
            font-size: 14px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2>Login</h2>
    <?php if (isset($erro)): ?>
        <div class="erro"><?php echo $erro; ?></div>
    <?php endif; ?>
    <form method="POST" action="login.php">
        <input type="text" name="usuario" placeholder="Usuário" required>
        <input type="password" name="senha" placeholder="Senha" required>
        <button type="submit">Entrar</button>
    </form>
</div>

</body>
</html>
