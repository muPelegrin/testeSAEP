<?php
require_once 'conexao.php';

// Verifica autenticacao
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$mensagem = '';
$tipo_mensagem = ''; // 'sucesso', 'erro' ou 'alerta'
$alerta_js = '';

/**
 * Requisito 7.1.1: Listar produtos em ordem alfabetica utilizando um algoritmo de ordenacao.
 * Algoritmo: Bubble Sort.
 */
function ordenarProdutosPorNome(&$lista) {
    $tamanho = count($lista);
    for ($i = 0; $i < $tamanho - 1; $i++) {
        for ($j = 0; $j < $tamanho - $i - 1; $j++) {
            if (strcasecmp($lista[$j]['nome'], $lista[$j + 1]['nome']) > 0) {
                $temp = $lista[$j];
                $lista[$j] = $lista[$j + 1];
                $lista[$j + 1] = $temp;
            }
        }
    }
}

// Processamento da Movimentacao de Estoque
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produto_id = isset($_POST['produto_id']) ? (int)$_POST['produto_id'] : 0;
    $tipo = trim($_POST['tipo'] ?? '');
    $quantidade = isset($_POST['quantidade']) ? (int)$_POST['quantidade'] : 0;
    $data_movimentacao = trim($_POST['data_movimentacao'] ?? '');
    $usuario_id = (int)$_SESSION['usuario_id'];

    if ($produto_id <= 0) {
        $mensagem = "Selecione um produto válido.";
        $tipo_mensagem = 'erro';
    } elseif ($tipo !== 'entrada' && $tipo !== 'saida') {
        $mensagem = "Selecione o tipo de movimentação: Entrada ou Saída.";
        $tipo_mensagem = 'erro';
    } elseif ($quantidade <= 0) {
        $mensagem = "A quantidade deve ser um número inteiro positivo maior que zero.";
        $tipo_mensagem = 'erro';
    } elseif (empty($data_movimentacao)) {
        $mensagem = "Informe a data da movimentação.";
        $tipo_mensagem = 'erro';
    } else {
        $stmtProd = $pdo->prepare("SELECT * FROM produtos WHERE id = :id LIMIT 1");
        $stmtProd->execute([':id' => $produto_id]);
        $produto = $stmtProd->fetch();

        if (!$produto) {
            $mensagem = "Produto não encontrado.";
            $tipo_mensagem = 'erro';
        } else {
            $estoque_atual = (int)$produto['quantidade_estoque'];
            $estoque_minimo = (int)$produto['estoque_minimo'];
            $novo_estoque = $estoque_atual;
            $operacao_permitida = true;

            if ($tipo === 'saida') {
                if ($quantidade > $estoque_atual) {
                    $mensagem = "Operação cancelada: Quantidade de saída informada é maior que o estoque atual disponível.";
                    $tipo_mensagem = 'erro';
                    $operacao_permitida = false;
                } else {
                    $novo_estoque = $estoque_atual - $quantidade;
                }
            } else {
                $novo_estoque = $estoque_atual + $quantidade;
            }

            if ($operacao_permitida) {
                try {
                    $pdo->beginTransaction();

                    $stmtUpdate = $pdo->prepare("UPDATE produtos SET quantidade_estoque = :novo_estoque WHERE id = :id");
                    $stmtUpdate->execute([
                        ':novo_estoque' => $novo_estoque,
                        ':id' => $produto_id
                    ]);

                    $stmtMov = $pdo->prepare("INSERT INTO movimentacoes (produto_id, usuario_id, tipo, quantidade, data_movimentacao) 
                                              VALUES (:produto_id, :usuario_id, :tipo, :quantidade, :data_movimentacao)");
                    $stmtMov->execute([
                        ':produto_id' => $produto_id,
                        ':usuario_id' => $usuario_id,
                        ':tipo' => $tipo,
                        ':quantidade' => $quantidade,
                        ':data_movimentacao' => $data_movimentacao
                    ]);

                    $pdo->commit();

                    if ($tipo === 'saida' && $novo_estoque < $estoque_minimo) {
                        $alerta_js = "ALERTA AUTOMÁTICO: O estoque do produto " . addslashes($produto['nome']) . " ficou abaixo do mínimo configurado! Estoque atual: " . $novo_estoque . " - Estoque mínimo: " . $estoque_minimo . ".";
                        $mensagem = "Movimentação realizada com sucesso. ATENÇÃO: O produto " . htmlspecialchars($produto['nome']) . " está com estoque abaixo do mínimo! Atual: $novo_estoque - Mínimo: $estoque_minimo";
                        $tipo_mensagem = 'alerta';
                    } else {
                        $mensagem = "Movimentação de " . ($tipo === 'entrada' ? 'Entrada' : 'Saída') . " registrada com sucesso! Novo estoque: $novo_estoque.";
                        $tipo_mensagem = 'sucesso';
                    }

                } catch (Exception $e) {
                    $pdo->rollBack();
                    $mensagem = "Erro ao registrar movimentação: " . $e->getMessage();
                    $tipo_mensagem = 'erro';
                }
            }
        }
    }
}

// 1. Busca produtos
$stmtTodosProdutos = $pdo->query("SELECT * FROM produtos");
$produtos = $stmtTodosProdutos->fetchAll();

// 2. Ordena alfabeticamente
ordenarProdutosPorNome($produtos);

// 3. Consulta historico
$sqlHistorico = "SELECT m.id, m.tipo, m.quantidade, m.data_movimentacao, m.data_registro, 
                        p.nome AS produto_nome, 
                        u.nome AS usuario_nome 
                 FROM movimentacoes m 
                 JOIN produtos p ON m.produto_id = p.id 
                 JOIN usuarios u ON m.usuario_id = u.id 
                 ORDER BY m.id DESC";
$stmtHist = $pdo->query($sqlHistorico);
$historico = $stmtHist->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>SAEP - Gestão de Estoque</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h1>Sistema de Gestão de Estoque - SAEP</h1>
    <h2>Gestão e Movimentação de Estoque</h2>

    <div class="nav-links">
        <a href="index.php">Menu Principal</a>
        <a href="produtos.php">Cadastro de Produtos</a>
        <a href="logout.php">Sair</a>
    </div>

    <?php if (!empty($mensagem)): ?>
        <div class="mensagem <?php echo $tipo_mensagem === 'erro' ? 'mensagem-erro' : ($tipo_mensagem === 'alerta' ? 'mensagem-alerta' : 'mensagem-sucesso'); ?>">
            <strong><?php echo $tipo_mensagem === 'erro' ? 'ERRO: ' : ($tipo_mensagem === 'alerta' ? 'ALERTA DE ESTOQUE: ' : 'SUCESSO: '); ?></strong><?php echo htmlspecialchars($mensagem); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($alerta_js)): ?>
        <script>
            alert("<?php echo $alerta_js; ?>");
        </script>
    <?php endif; ?>

    <!-- Formulario de Movimentacao de Estoque -->
    <h3>Registrar Movimentação de Estoque</h3>

    <form method="POST" action="estoque.php" onsubmit="return validarMovimentacao(this);">
        <div class="form-group">
            <label for="produto_id">Selecione o Produto:</label>
            <select id="produto_id" name="produto_id" onchange="atualizarInfoProduto(this);">
                <option value="">Selecione um produto</option>
                <?php foreach ($produtos as $p): ?>
                    <option value="<?php echo $p['id']; ?>" 
                            data-estoque="<?php echo $p['quantidade_estoque']; ?>" 
                            data-minimo="<?php echo $p['estoque_minimo']; ?>" 
                            data-nome="<?php echo htmlspecialchars($p['nome']); ?>">
                        <?php echo htmlspecialchars($p['nome']); ?> - Estoque Atual: <?php echo htmlspecialchars($p['quantidade_estoque']); ?> - Mínimo: <?php echo htmlspecialchars($p['estoque_minimo']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="info_produto_selecionado" class="box-info" style="display:none;"></div>

        <div class="form-group radio-group">
            <label>Tipo de Movimentação:</label><br>
            <label><input type="radio" name="tipo" value="entrada" checked> Entrada</label>
            <label><input type="radio" name="tipo" value="saida"> Saída</label>
        </div>

        <div class="form-group">
            <label for="quantidade">Quantidade:</label>
            <input type="number" id="quantidade" name="quantidade" min="1" value="1">
        </div>

        <div class="form-group">
            <label for="data_movimentacao">Data da Movimentação:</label>
            <input type="date" id="data_movimentacao" name="data_movimentacao" value="<?php echo date('Y-m-d'); ?>">
        </div>

        <p>
            <button type="submit">Confirmar Movimentação</button>
        </p>
    </form>

    <hr>

    <!-- Listagem dos Produtos Ordenados Alfabeticamente com Algoritmo -->
    <h3>Produtos em Estoque - Ordenados Alfabeticamente</h3>
    <table>
        <thead>
            <tr>
                <th>Nome do Produto</th>
                <th>Categoria</th>
                <th>Estoque Atual</th>
                <th>Estoque Mínimo</th>
                <th>Situação do Estoque</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($produtos)): ?>
                <tr>
                    <td colspan="5">Nenhum produto cadastrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($produtos as $p): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p['nome']); ?></td>
                        <td><?php echo htmlspecialchars($p['categoria']); ?></td>
                        <td><?php echo htmlspecialchars($p['quantidade_estoque']); ?></td>
                        <td><?php echo htmlspecialchars($p['estoque_minimo']); ?></td>
                        <td>
                            <?php if ($p['quantidade_estoque'] < $p['estoque_minimo']): ?>
                                <span class="alerta-minimo">Abaixo do mínimo!</span>
                            <?php else: ?>
                                Regular
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <hr>

    <!-- Historico de Movimentacoes -->
    <h3>Histórico de Movimentações</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Data da Movimentação</th>
                <th>Produto</th>
                <th>Tipo</th>
                <th>Quantidade</th>
                <th>Responsável</th>
                <th>Data de Registro</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($historico)): ?>
                <tr>
                    <td colspan="7">Nenhuma movimentação registrada.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($historico as $h): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($h['id']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($h['data_movimentacao'])); ?></td>
                        <td><?php echo htmlspecialchars($h['produto_nome']); ?></td>
                        <td><strong><?php echo strtoupper($h['tipo']); ?></strong></td>
                        <td><?php echo htmlspecialchars($h['quantidade']); ?></td>
                        <td><?php echo htmlspecialchars($h['usuario_nome']); ?></td>
                        <td><?php echo date('d/m/Y H:i:s', strtotime($h['data_registro'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- JavaScript para Interacao e Validacao/Avisos -->
<script>
    function atualizarInfoProduto(selectElement) {
        var infoP = document.getElementById('info_produto_selecionado');
        var selectedOption = selectElement.options[selectElement.selectedIndex];
        if (selectedOption.value) {
            var estoque = selectedOption.getAttribute('data-estoque');
            var minimo = selectedOption.getAttribute('data-minimo');
            infoP.style.display = "block";
            infoP.innerHTML = "Estoque atual: <strong>" + estoque + "</strong> unidades | Estoque mínimo configurado: <strong>" + minimo + "</strong> unidades";
        } else {
            infoP.style.display = "none";
            infoP.innerHTML = "";
        }
    }

    function validarMovimentacao(form) {
        var selectProduto = form.produto_id;
        var produtoId = selectProduto.value;
        var quantidade = parseInt(form.quantidade.value, 10);
        var dataMov = form.data_movimentacao.value.trim();
        var tipo = form.tipo.value;

        if (!produtoId) {
            alert("Por favor, selecione um produto para a movimentação.");
            selectProduto.focus();
            return false;
        }

        if (isNaN(quantidade) || quantidade <= 0) {
            alert("Informe uma quantidade válida maior que zero.");
            form.quantidade.focus();
            return false;
        }

        if (!dataMov) {
            alert("Informe a data da movimentação.");
            form.data_movimentacao.focus();
            return false;
        }

        var selectedOption = selectProduto.options[selectProduto.selectedIndex];
        var estoqueAtual = parseInt(selectedOption.getAttribute('data-estoque'), 10);
        var estoqueMinimo = parseInt(selectedOption.getAttribute('data-minimo'), 10);
        var nomeProduto = selectedOption.getAttribute('data-nome');

        if (tipo === 'saida') {
            if (quantidade > estoqueAtual) {
                alert("Atenção: A quantidade de saída informada de " + quantidade + " é maior que o estoque atual disponível de " + estoqueAtual + "!");
                return false;
            }

            var saldoFuturo = estoqueAtual - quantidade;
            if (saldoFuturo < estoqueMinimo) {
                var confirma = confirm("AVISO DE ESTOQUE MÍNIMO:\nEsta saída deixará o produto " + nomeProduto + " com " + saldoFuturo + " unidades, ficando abaixo do estoque mínimo configurado de " + estoqueMinimo + " unidades.\nDeseja confirmar a operação?");
                if (!confirma) {
                    return false;
                }
            }
        }

        return true;
    }
</script>

</body>
</html>
