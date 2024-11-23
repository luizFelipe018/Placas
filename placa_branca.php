<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Caminho para o arquivo Excel
$arquivoExcel = 'C:\Tabloide\tabloide1611.xlsx';

// Carregar o arquivo Excel
$spreadsheet = IOFactory::load($arquivoExcel);
$sheet = $spreadsheet->getActiveSheet();

// Função para corrigir preço por unidade
function corrigirPrecoKgLTUn($descricao, $preco) {
    // Converte a descrição para minúsculas
    $descricao = strtolower($descricao);

    // Inicializa a unidade padrão
    $unidadeDetectada = 'PRODUTOS';

    // Verificar por padrões específicos na descrição
    if (preg_match('/\d+\s*(kg|g)/', $descricao)) {
        $unidadeDetectada = 'KG'; // G é tratado como KG
    } elseif (preg_match('/\d+\s*(l|ml)/', $descricao)) {
        $unidadeDetectada = 'LT'; // ML é tratado como LT
    } elseif (preg_match('/\d+\s*un/', $descricao)) {
        $unidadeDetectada = 'UN';
    }

    // Processa o preço para garantir o formato numérico correto
    $valorNumerico = (float)str_replace(',', '.', str_replace('.', '', $preco));

    // Retorna os dados corrigidos
    return [
        'valor' => $valorNumerico,
        'unidade' => $unidadeDetectada
    ];
}


// Função para obter os dados do Excel
function carregarDadosExcel($sheet, $inicioLinha, $fimLinha) {
    $dados = [];
    $totalLinhas = $sheet->getHighestRow(); // Obtém a última linha preenchida na planilha

    for ($linha = $inicioLinha; $linha <= $fimLinha && $linha <= $totalLinhas; $linha++) {
        // Obtém a célula da descrição na coluna B
        $celulaDescricao = $sheet->getCell('B' . $linha);
        // Verifica a cor de fundo da célula (amarela, por exemplo)
        $corDeFundo = $celulaDescricao->getStyle()->getFill()->getStartColor()->getRGB();
        
        // Define a cor amarela (geralmente é FFFFFF00)
        $corAmarela = 'FFFF00'; // Ou 'FFFF99', dependendo do tom de amarelo que você usa
        
        // Se a cor não for amarela, continua o processo
        if ($corDeFundo !== $corAmarela) {
            $descricao = $celulaDescricao->getValue();
            $preco = $sheet->getCell('C' . $linha)->getValue();
            $codigo = $sheet->getCell('F' . $linha)->getValue();

            // Aplica a função corrigirPrecoKgLTUn
            $dadosPreco = corrigirPrecoKgLTUn($descricao, $preco);

            $dados[] = [
                'descricao' => $descricao,
                'preco' => $dadosPreco['valor'],
                'unidade' => $dadosPreco['unidade'],
                'codigo' => $codigo
            ];
        }
    }
    return $dados;
}

// Para carregar todos os dados do Excel
$dadosPlacas = carregarDadosExcel($sheet, 4, $sheet->getHighestRow()); // O total de linhas é obtido diretamente da planilha

// Dividir em páginas de 10 placas
$placasPorPagina = 10;
$totalPlacas = count($dadosPlacas);
$totalPaginas = ceil($totalPlacas / $placasPorPagina);
?>


<script>
  function printDiv() {
        const divPlaca = document.getElementById("folha"); // Seleciona a div da placa
        const originalContent = document.body.innerHTML; // Armazena o conteúdo original da página

        // Armazena o valor atual do select
        const selectUnidade = document.querySelector("select[name='unidade']");
        const selectValue = selectUnidade ? selectUnidade.value : null;

        // Verifica a cor de fundo atual da folha
        const corFolha = divPlaca.style.backgroundColor || 'white'; // 'white' é o valor padrão

        // Verifica a cor de fundo atual das placas
        const placas = document.getElementsByClassName('placa');
        const corPlacas = placas.length > 0 ? placas[0].style.backgroundColor : 'white'; // 'white' é o valor padrão

        // Adiciona estilos específicos para impressão
        const style = document.createElement("style");
        style.innerHTML = `
            @media print {
                #folha {
                    width: 210mm;
                    height: 297mm;
                    display: flex;
                    flex-wrap: wrap;
                    gap: 1px;
                    padding: 0 10px;
                    justify-content: space-between;
                    background-color: yellow !important; /* Aplica a cor de fundo da folha com !important */
                }
                .placa {
                    width: 49%;
                    height: 19%;
                    background-color: ${corPlacas} !important; /* Aplica a cor de fundo das placas com !important */
                    border: 1px solid #000;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    text-align: center;
                }
                .placa h1 {
                    margin: 0;
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imprimir Placas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI';
        }
        #folha {
            width: 210mm;
            height: 297mm;
            display: flex;
            flex-wrap: wrap;
            gap: 1px;
            padding: 0 10px;
            justify-content: space-between;
            background: white;
        }
        .placa {
            width: 49%;
            height: 19%;
            background: #fff;
            border: 1px solid #000;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .placa h1 {
            margin: 0;
        }
        .navigation {
            margin: 10px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .navigation button {
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
        }
    </style>
    <script>
        let currentPage = 1;
        const totalPaginas = <?php echo $totalPaginas; ?>;

        function changePage(page) {
            const placas = document.querySelectorAll('.placa');
            placas.forEach((placa, index) => {
                placa.style.display = (Math.floor(index / 10) + 1 === page) ? 'block' : 'none';
            });
            currentPage = page;
            updateNavigation();
        }

        function updateNavigation() {
            document.getElementById('prevButton').disabled = currentPage === 1;
            document.getElementById('nextButton').disabled = currentPage === totalPaginas;
        }

        document.addEventListener('DOMContentLoaded', () => {
            changePage(1);
        });



        function alternarCorFolha() {
        // Obtém o elemento da folha
        const folha = document.getElementById('folha');
        // Obtém todos os elementos com a classe 'placa'
        const placas = document.getElementsByClassName('placa');
        
        // Verifica a cor atual e alterna entre amarelo e branco
        if (folha.style.backgroundColor === 'yellow') {
            folha.style.backgroundColor = 'white'; // Volta para branco
            
            // Itera sobre todas as placas e altera para branco
            for (let i = 0; i < placas.length; i++) {
                placas[i].style.backgroundColor = 'white';
            }
        } else {
            folha.style.backgroundColor = 'yellow'; // Muda para amarelo
            
            // Itera sobre todas as placas e altera para amarelo
            for (let i = 0; i < placas.length; i++) {
                placas[i].style.backgroundColor = 'yellow';
            }
        }
    }
    </script>
</head>
<body style="display: flex;">
    <div id="folha">
        <?php foreach ($dadosPlacas as $index => $placa): ?>
            <div class="placa" style="display: none;">
                <h1 style="font-size: 14px;margin-bottom:20px;"><?php echo $placa['descricao']; ?></h1>
                <h1 style="font-size: 92px;margin-right: 25px;" >
                    <span style="font-size: 18px;">R$</span>
                    <?php echo number_format($placa['preco'], 2, ',', '.'); ?></h1>
                <h1 style="font-size: 16px;">
                    <?php
                    if ($placa['unidade'] === 'UN') {
                        echo 'NESTA EMBALAGEM A UN SAI R$ ';
                    } elseif ($placa['unidade'] === 'LT') {
                        echo 'NESTA EMBALAGEM O LT SAI R$ ';
                    } elseif ($placa['unidade'] === 'KG') {
                        echo 'NESTA EMBALAGEM O KG SAI R$ ';
                    } else {
                        echo 'NESTA EMBALAGEM O PRODUTO SAI R$ ';
                    }
                    echo number_format($placa['preco'], 2, ',', '.');
                    ?>
                </h1>
                <h1 style="font-size: 16px;"><?php echo "cód. ", $placa['codigo']; ?></h1>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="navigation">
    <button style="width: 100px; height: 40px; background-color: #4CAF50; color: white; font-size: 16px; border: none; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease;">
        Anterior
    </button>
    <button style="width: 100px; height: 40px; background-color: #008CBA; color: white; font-size: 16px; border: none; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease;">
        Próxima
    </button>
    <button onclick="printDiv()" style="width: 100px; height: 40px; background-color: #b51515; color: white; font-size: 16px; border: none; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease;">
        Imprimir
    </button>
    <button class="btn" onclick="alternarCorFolha()" style="width: 100px; height: 40px; background-color: #008CBA; color: white; font-size: 16px; border: none; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease;">amarela</button>

</div>
   
</body>
</html>
