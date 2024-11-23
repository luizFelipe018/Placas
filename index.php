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

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;




// Função para extrair e separar partes do texto da célula
function extrairPartesTexto($linha, $coluna) {
    $caminhoArquivo = 'C:\Tabloide\tabloide1611.xlsx';
    $planilha = IOFactory::load($caminhoArquivo);
    $abaAtiva = $planilha->getActiveSheet();

    $celula = $abaAtiva->getCell($coluna . $linha);
    $textoAntes = '';
    $textoNegrito = '';
    $textoDepois = '';
    $negritoEncontrado = false;

    // Verifica se a célula contém texto com formatação (RichText)
    if ($celula->getValue() instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
        // Itera sobre os elementos do texto rico
        foreach ($celula->getValue()->getRichTextElements() as $elemento) {
            $texto = $elemento->getText(); // Texto do elemento
            $isBold = $elemento->getFont() && $elemento->getFont()->getBold(); // Verifica se o texto é negrito

            if ($isBold) {
                // Acumula o texto em negrito
                $textoNegrito .= $texto;
                $negritoEncontrado = true;
            } else {
                if (!$negritoEncontrado) {
                    // Texto antes do negrito
                    $textoAntes .= $texto;
                } else {
                    // Texto depois do negrito
                    $textoDepois .= $texto;
                }
            }
        }
    } else {
        // Caso a célula contenha apenas texto simples (sem formatação)
        $textoAntes = $celula->getValue();
    }

    return [
        'textoAntes' => $textoAntes,
        'textoNegrito' => $textoNegrito,
        'textoDepois' => $textoDepois
    ];
}
// Função para carregar dados da linha atual (inclusive negrito separado)
function carregarLinhaExcel($linha) {
    $caminhoArquivo = 'C:\Tabloide\tabloide1611.xlsx';
    $planilha = IOFactory::load($caminhoArquivo);
    $abaAtiva = $planilha->getActiveSheet();

    // Extrair a descrição da célula B (com formatação rica, caso exista)
    $descricaoPartes = extrairPartesTexto($linha, 'B');

    // Se a descrição contiver partes, unifica-as em uma string
    $descricaoProduto = is_array($descricaoPartes) ? implode(' ', $descricaoPartes) : $descricaoPartes;

    // Leitura de preços
    $preco = (string)$abaAtiva->getCell('C' . $linha)->getCalculatedValue(); // Preço na coluna C
    $preçokgLTUn = (string)$abaAtiva->getCell('E' . $linha)->getCalculatedValue(); // Preço por KG/LT/UN na coluna E

    // Corrigir valores (substituindo vírgulas e pontos para formatação correta)
    $preco = (float)str_replace(',', '.', str_replace('.', '', $preco));

    // Corrigir preço por KG/LT/UN com base na descrição do produto (exemplo de função de ajuste)
    $dadosKgLTUn = corrigirPrecoKgLTUn($preçokgLTUn, $descricaoProduto);

    return [
        'descricaoPartes' => $descricaoPartes, // Descrição dividida
        'preco' => $preco, // Preço
        'preçokgLTUn' => $dadosKgLTUn // Preço por KG/LT/UN
    ];
}




function corrigirPrecoKgLTUn($valorTexto, $descricaoProduto) {
    // Garantir que a descrição esteja em minúsculas para comparação
    $descricaoProduto = strtolower($descricaoProduto);

    // Definir unidades esperadas, incluindo 'g' e 'ml' para tratar como 'kg' e 'l'
    $unidades = ['kg', 'l', 'un', 'g', 'ml'];

    // Caso a descrição contenha 'un' (UN), definimos como unidade 'UN', mas verificamos que tenha um número antes
    if (strpos($descricaoProduto, 'un') !== false) {
        // Se encontrar 'un', retornamos como 'UN' sem lançar exceção, mas apenas se houver um número
        if (preg_match('/(\d+)/', $descricaoProduto)) {
            $valorNumerico = (float)str_replace(',', '.', str_replace('.', '', $valorTexto));
            return ['valor' => $valorNumerico, 'unidade' => 'UN'];
        }
    }

    // Tratar 'ml' como 'l' e 'g' como 'kg', garantindo que haja um número antes da unidade
    if (preg_match('/(\d+)\s*ml/', $descricaoProduto)) {
        $valorNumerico = (float)str_replace(',', '.', str_replace('.', '', $valorTexto));
        return ['valor' => $valorNumerico, 'unidade' => 'LT']; // Mudamos 'L' para 'LT'
    }

    if (preg_match('/(\d+)\s*g/', $descricaoProduto)) {
        $valorNumerico = (float)str_replace(',', '.', str_replace('.', '', $valorTexto));
        return ['valor' => $valorNumerico, 'unidade' => 'KG'];
    }

    // Verificação para 'kg' sem número - se a descrição tiver apenas 'kg', não devemos tratar como 'un'
    if (strpos($descricaoProduto, 'kg') !== false) {
        // Se encontrar 'kg', definimos a unidade como 'KG'
        $valorNumerico = (float)str_replace(',', '.', str_replace('.', '', $valorTexto));
        return ['valor' => $valorNumerico, 'unidade' => 'KG'];
    }

    // Caso contrário, verificar se é 'kg', 'l' ou 'un' e aplicar a conversão conforme necessário
    foreach ($unidades as $unidade) {
        if (strpos($descricaoProduto, $unidade) !== false) {
            // Verificar se há um número antes da unidade
            if (preg_match('/(\d+)\s*' . $unidade . '/', $descricaoProduto)) {
                $valorNumerico = (float)str_replace(',', '.', str_replace('.', '', $valorTexto));
                // Se for 'l', mudar para 'LT'
                if ($unidade == 'l') {
                    return ['valor' => $valorNumerico, 'unidade' => 'LT'];
                }
                return ['valor' => $valorNumerico, 'unidade' => strtoupper($unidade)];
            }
        }
    }
    // Caso não encontre nenhuma unidade definida, retorna "INDEFINIDO"
    return ['valor' => 0, 'unidade' => 'INDEFINIDO'];
}

       

// Inicializa a linha se não estiver setada
if (!isset($_SESSION['linhaAtual'])) {
    $_SESSION['linhaAtual'] = 4; // Começa na segunda linha (A2, B2)
}

// Controla a navegação entre as linhas
if (isset($_POST['proximaLinha'])) {
    $_SESSION['linhaAtual']++; // Vai para a próxima linha
} elseif (isset($_POST['voltarLinha']) && $_SESSION['linhaAtual'] > 4) {
    $_SESSION['linhaAtual']--; // Vai para a linha anterior, mas não pode voltar para antes da linha 2
}

$linhaAtual = $_SESSION['linhaAtual'];
$dados = carregarLinhaExcel($linhaAtual);

?>



<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Impressão de Placas</title>
    <style>
        /* Estilo da fonte e da placa */
        *{
            font-family: 'Arial Black';
            text-align: center;
            margin: 0;
        }
        
        #placa p {
            margin:  0;

        }


    #folhaAmarela {
    width: 525.22px; 
    height: 483.74px; 
    background: #b51515;
    margin: 0; 
    display: flex;
    margin-left: 100px;

}
        /* Estilo do contorno da placa */
        #placa {
            width: 30.5rem;
            height: 483.74px;
            background: yellow;
            margin: 0 auto;
            overflow: hidden; /* Garante que o texto não ultrapasse a div */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

    .header {
    justify-content: space-between;
    align-items: center;
    width: 488px;
    height: 191.48px;
    overflow: hidden; /* Esconde texto extra */

  }

  .header h1 {
    white-space: nowrap; /* Impede que o texto quebre linha */
    margin: 0;
    overflow: hidden;

  }



.price{
    width: 100%;
    height: 184px;
}

.footer{
    width: 100%;
    height: 108.28px;
    display: flex;
    align-items: flex-end;
    justify-content: center;
}

.container{
    display: flex;
}


body {
    background-color: #f4f6f9;
    margin: 0;
    padding: 0;
}

.painel {
    width: 500px;
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    margin: 30px auto;
}

.button-container {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    display: inline-block;
    text-align: center;
}

.btn-back {
    background-color: #e74c3c;
    color: #fff;
}

.btn-next {
    background-color: #2ecc71;
    color: #fff;
}

.btn-print {
    background-color: #3498db;
    color: #fff;
}

.btn:hover {
    opacity: 0.9;
}

.font-size-container {
    margin-top: 20px;
}

.font-size-control {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.font-size-control label {
    flex: 1;
    font-size: 18px;
    color: #333;
    margin-right: 10px;
}

.btn-adjust {
    background-color: #f39c12;
    color: #fff;
    width: 40px;
    font-size: 18px;
    margin-left: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-adjust:hover {
    background-color: #e67e22;
}

h3 {
    color: #333;
    font-size: 22px;
    margin-bottom: 15px;
}


select {
        -webkit-appearance: none;  /* Chrome, Safari, Opera */
        -moz-appearance: none;     /* Firefox */
        appearance: none;          /* Padrão moderno */
        padding-right: 20px;       /* Espaço para compensar a seta removida */
        background: transparent;   /* Remover fundo padrão */
        outline: none;
    }

    </style>




    <script>
function printDiv() {
    const divPlaca = document.getElementById("placa"); // Seleciona a div da placa
    const originalContent = document.body.innerHTML; // Armazena o conteúdo original da página

    // Armazena o valor atual do select
    const selectUnidade = document.querySelector("select[name='unidade']");
    const selectValue = selectUnidade ? selectUnidade.value : null;

    // Adiciona estilos específicos para impressão
    const style = document.createElement("style");
    style.innerHTML = `
        @media print {
            #placa {
                margin-top: 540px; /* Aplica a margem superior apenas na impressão */
            }
        }
    `;
    document.head.appendChild(style); // Adiciona o estilo ao cabeçalho da página

    // Substitui o conteúdo da página com apenas a div da placa
    document.body.innerHTML = divPlaca.outerHTML;

    // Restaura o valor do select, se houver
    if (selectValue) {
        const restoredSelect = document.querySelector("select[name='unidade']");
        if (restoredSelect) {
            restoredSelect.value = selectValue;
        }
    }

    // Inicia o processo de impressão
    window.print();

    // Restaura o conteúdo original da página
    document.body.innerHTML = originalContent;

    // Remove os estilos de impressão temporários
    document.head.removeChild(style);

    // Recarrega os scripts e eventos (opcional, se necessário)
    window.location.reload();
}


    </script>
</head>
<body>

<div class="title" style="display: flex;justify-content: center;margin-bottom: 50px;margin-top: 10px;color: #202020;">
<h2>Placa Gerada (Linha <?php echo $linhaAtual; ?>):</h2>

<a href="logout.php" style="margin-left: 100px;margin-top: 10px;color: #202020;text-decoration: none;">Sair</a>
</div>



<div class="container">
    
<div id="folhaAmarela">
    <div id='placa'>
        <div class="header" style="padding: 1rem;" >
        <h1 id="textoAntes" style="font-size: 36px;line-height: 1.2em;"><?php echo $dados['descricaoPartes']['textoAntes']; ?></h1>
        <h1 id="textoNegrito" style="font-size: 75px; line-height: 3.5rem;"><?php echo $dados['descricaoPartes']['textoNegrito']; ?></h1>
        <h1 id="textoDepois" style="font-size:28px;line-height: 2rem"><?php echo $dados['descricaoPartes']['textoDepois']; ?></h1>
        
        </div>
        
        <div class="price" style="display: flex;justify-content: space-between;width: 478px;padding-left: 5px; padding-right: 5px;align-items: end;" >
        <p style="display: flex;
        align-items:end;font-size:20px ;">R$</p>
        <h1 id="Preco" style="font-size: clamp(8rem, 5vw, 10rem);line-height: 0.9;"><?php echo number_format($dados['preco'], 2, ',', '.'); ?></h1>
        <div style="display: flex; align-items:end;; font-size:20px;background-color: yellow;padding: 0;" >
        <form method="post">
        <select name="unidade" style="font-size: 20px;border:none;background-color: yellow;padding: 0 " >
            <option value="UN" <?php echo (isset($dados['preçokgLTUn']['unidade']) && $dados['preçokgLTUn']['unidade'] == 'UN') ? 'selected' : ''; ?>>UN</option>
            <option value="KG" <?php echo (isset($dados['preçokgLTUn']['unidade']) && $dados['preçokgLTUn']['unidade'] == 'KG') ? 'selected' : ''; ?>>KG</option>
        </select>
        </form>
        </div>
                
                </div>
                <div class="footer">
                <p style="font-size: 14px;">
            <?php
    // Verificar a unidade e ajustar o texto
    if ($dados['preçokgLTUn']['unidade'] == 'UN') {
        // Se a unidade for UN, usa "A UN"
        echo 'NESTA EMBALAGEM A UN SAI R$ ';
    } elseif ($dados['preçokgLTUn']['unidade'] == 'LT') {
        // Se a unidade for LT, usa "O LT"
        echo 'NESTA EMBALAGEM O LT SAI R$ ';
    } elseif ($dados['preçokgLTUn']['unidade'] == 'KG') {
        // Se a unidade for KG, usa "O KG"
        echo 'NESTA EMBALAGEM O KG SAI R$ ';
    } else {
        // Caso a unidade seja INDEFINIDO ou outro valor, usa "O produto"
        echo 'NESTA EMBALAGEM O PRODUTO SAI R$ ';
    }

    // Exibir o valor formatado
    echo number_format($dados['preçokgLTUn']['valor'], 2, ',', '.');
    ?>
</p>
        </div>

    </div>
</div>

<script>
/**
 * Ajusta o tamanho da fonte e o line-height do elemento especificado.
 * @param {string} elementId - O ID do elemento HTML.
 * @param {number} adjustment - O valor para ajustar o tamanho da fonte (positivo ou negativo).
 */
function ajustarFonteEAltura(elementId, adjustment) {
    const elemento = document.getElementById(elementId);
    const computedStyle = window.getComputedStyle(elemento);

    // Obtém o tamanho atual da fonte
    let fontSize = parseFloat(computedStyle.fontSize);

    // Ajusta o tamanho da fonte
    fontSize += adjustment;

    if (fontSize > 10) { // Limite mínimo para fonte
        elemento.style.fontSize = fontSize + "px";

        // Ajusta o line-height proporcional ao tamanho da fonte
        const lineHeight = fontSize * 1.03; // Exemplo: 1.2x do tamanho da fonte
        elemento.style.lineHeight = lineHeight + "px";
    }
}
</script>

<div class="painel">
    <form method="post">
        <div class="button-container">
            <button class="btn btn-back" type="submit" name="voltarLinha" <?php echo ($_SESSION['linhaAtual'] <= 2) ? 'disabled' : ''; ?>>Voltar</button>
            <button class="btn btn-next" type="submit" name="proximaLinha">Próxima</button>
            <button class="btn btn-print" type="button" onclick="printDiv()">Imprimir</button>
        </div>
    </form>

    <div class="font-size-container">
        <h3>Ajustar tamanho da fonte:</h3>

        <div class="font-size-control">
            <label>Texto 1:</label>
            <button class="btn btn-adjust" type="button" onclick="ajustarFonteEAltura('textoAntes', 2)">+</button>
            <button class="btn btn-adjust" type="button" onclick="ajustarFonteEAltura('textoAntes', -2)">-</button>
        </div>

        <div class="font-size-control">
            <label>Marca:</label>
            <button class="btn btn-adjust" type="button" onclick="ajustarFonteEAltura('textoNegrito', 2)">+</button>
            <button class="btn btn-adjust" type="button" onclick="ajustarFonteEAltura('textoNegrito', -2)">-</button>
        </div>

        <div class="font-size-control">
            <label>Texto 3:</label>
            <button class="btn btn-adjust" type="button" onclick="ajustarFonteEAltura('textoDepois', 2)">+</button>
            <button class="btn btn-adjust" type="button" onclick="ajustarFonteEAltura('textoDepois', -2)">-</button>
        </div>

        <form id="diretorioForm" method="post" action="seu_script_php.php">
        <label for="caminho">Caminho do arquivo:</label>
        <input type="text" id="caminho" name="caminho" placeholder="Digite ou selecione o diretório" />
        <button type="button" onclick="selecionarDiretorio()">Selecionar Diretório</button>
        <br><br>
        <input type="submit" value="Carregar Planilha" />
        </form>
    </div>
</div>
</div>




</body>
</html>