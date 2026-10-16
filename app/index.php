<?php
require_once 'conexao.php';

// Verifica se o usuario esta autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$nome_usuario = $_SESSION['usuario_nome'] ?? 'Usuário';

// Consulta produtos abaixo do estoque minimo para aviso informativo
$stmtAlerta = $pdo->query("SELECT nome, quantidade_estoque, estoque_minimo FROM produtos WHERE quantidade_estoque < estoque_minimo");
$produtosAbaixoMinimo = $stmtAlerta->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>SAEP - Menu Principal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h1>Sistema de Gestão de Estoque - SAEP</h1>
    <h2>Menu Principal</h2>

    <p>Usuário autenticado: <strong><?php echo htmlspecialchars($nome_usuario); ?></strong></p>

    <div class="nav-links">
        <a href="produtos.php">Cadastro de Produtos</a>
        <a href="estoque.php">Gestão de Estoque</a>
        <a href="logout.php">Sair</a>
    </div>

    <hr>

    <?php if (!empty($produtosAbaixoMinimo)): ?>
        <h3>Avisos de Estoque Mínimo</h3>
        <div class="mensagem mensagem-alerta">
            <strong>Atenção:</strong> Os seguintes itens estão com estoque abaixo do mínimo configurado:
        </div>
        <table>
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Estoque Atual</th>
                    <th>Estoque Mínimo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produtosAbaixoMinimo as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['nome']); ?></td>
                        <td><span class="alerta-minimo"><?php echo htmlspecialchars($item['quantidade_estoque']); ?></span></td>
                        <td><?php echo htmlspecialchars($item['estoque_minimo']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>
