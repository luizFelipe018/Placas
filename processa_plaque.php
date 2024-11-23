<?php
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

// Verificar se um valor de placa foi enviado
if (isset($_POST['placa'])) {
    $placa = $_POST['placa'];

    // Ações específicas para cada placa
    switch ($placa) {
        case 'placa_amarela_grande':
            // Ação para Placa Amarela Grande
            echo "Você escolheu a Placa Amarela Grande!";
            break;

        case 'placa_amarela_pequena':
            // Ação para Placa Amarela Pequena
            echo "Você escolheu a Placa Amarela Pequena!";
            break;

        case 'placa_branca':
            // Ação para Placa Branca
            echo "Você escolheu a Placa Branca!";
            break;

        default:
            echo "Opção inválida!";
            break;
    }
} else {
    echo "Nenhuma opção foi selecionada!";
}
?>

<a href="dashboard.php">Voltar ao painel</a>
