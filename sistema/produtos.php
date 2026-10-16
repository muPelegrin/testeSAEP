<?php
require_once 'conexao.php';

// Verifica autenticacao
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$mensagem = '';
$tipo_mensagem = ''; // 'sucesso' ou 'erro'

// Acao: EXCLUSAO de produto
if (isset($_GET['excluir_id'])) {
    $id_excluir = (int)$_GET['excluir_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = :id");
        $stmt->execute([':id' => $id_excluir]);
        header("Location: produtos.php?msg=" . urlencode("Produto excluído com sucesso!"));
        exit;
    } catch (PDOException $e) {
        $mensagem = "Erro ao excluir produto: " . $e->getMessage();
        $tipo_mensagem = 'erro';
    }
}

// Acao: SALVAR (Novo produto ou Atualizar existente)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
    $nome = trim($_POST['nome'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $tamanho = trim($_POST['tamanho'] ?? '');
    $peso = trim($_POST['peso'] ?? '');
    $quantidade_estoque = trim($_POST['quantidade_estoque'] ?? '');
    $estoque_minimo = trim($_POST['estoque_minimo'] ?? '');

    // Validacoes no servidor
    $erros = [];

    if (empty($nome)) {
        $erros[] = "O campo Nome é obrigatório.";
    }
    if (empty($categoria)) {
        $erros[] = "O campo Categoria é obrigatório.";
    }
    if ($quantidade_estoque === '' || !is_numeric($quantidade_estoque) || (int)$quantidade_estoque < 0) {
        $erros[] = "A Quantidade em Estoque deve ser um número inteiro maior ou igual a zero.";
    }
    if ($estoque_minimo === '' || !is_numeric($estoque_minimo) || (int)$estoque_minimo < 0) {
        $erros[] = "O Estoque Mínimo deve ser um número inteiro maior ou igual a zero.";
    }

    if (!empty($erros)) {
        $mensagem = implode(" ", $erros);
        $tipo_mensagem = 'erro';
    } else {
        $qtd_num = (int)$quantidade_estoque;
        $min_num = (int)$estoque_minimo;

        if ($id) {
            // Edicao
            try {
                $sql = "UPDATE produtos SET 
                            nome = :nome, 
                            categoria = :categoria, 
                            descricao = :descricao, 
                            tamanho = :tamanho, 
                            peso = :peso, 
                            quantidade_estoque = :quantidade_estoque, 
                            estoque_minimo = :estoque_minimo 
                        WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nome' => $nome,
                    ':categoria' => $categoria,
                    ':descricao' => $descricao,
                    ':tamanho' => $tamanho,
                    ':peso' => $peso,
                    ':quantidade_estoque' => $qtd_num,
                    ':estoque_minimo' => $min_num,
                    ':id' => $id
                ]);
                header("Location: produtos.php?msg=" . urlencode("Produto atualizado com sucesso!"));
                exit;
            } catch (PDOException $e) {
                $mensagem = "Erro ao atualizar produto: " . $e->getMessage();
                $tipo_mensagem = 'erro';
            }
        } else {
            // Insercao (gera codigo interno automaticamente para integridade do banco)
            $codigo_automatico = 'PRD-' . date('Ymd') . '-' . rand(100, 999);
            try {
                $sql = "INSERT INTO produtos (codigo, nome, categoria, descricao, tamanho, peso, quantidade_estoque, estoque_minimo) 
                        VALUES (:codigo, :nome, :categoria, :descricao, :tamanho, :peso, :quantidade_estoque, :estoque_minimo)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':codigo' => $codigo_automatico,
                    ':nome' => $nome,
                    ':categoria' => $categoria,
                    ':descricao' => $descricao,
                    ':tamanho' => $tamanho,
                    ':peso' => $peso,
                    ':quantidade_estoque' => $qtd_num,
                    ':estoque_minimo' => $min_num
                ]);
                header("Location: produtos.php?msg=" . urlencode("Produto cadastrado com sucesso!"));
                exit;
            } catch (PDOException $e) {
                $mensagem = "Erro ao cadastrar produto: " . $e->getMessage();
                $tipo_mensagem = 'erro';
            }
        }
    }
}

// Mensagem via GET (apos redirecionamento)
if (isset($_GET['msg'])) {
    $mensagem = $_GET['msg'];
    $tipo_mensagem = 'sucesso';
}

// Carregar produto para edicao se solicitado
$produto_edicao = null;
if (isset($_GET['editar_id'])) {
    $id_editar = (int)$_GET['editar_id'];
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id_editar]);
    $produto_edicao = $stmt->fetch();
}

// Termo de busca (por nome ou categoria)
$termo_busca = trim($_GET['busca'] ?? '');

// Consulta de listagem de produtos (carregados automaticamente)
if (!empty($termo_busca)) {
    $sql_listagem = "SELECT * FROM produtos WHERE nome LIKE :termo OR categoria LIKE :termo ORDER BY id DESC";
    $stmt_lista = $pdo->prepare($sql_listagem);
    $stmt_lista->execute([':termo' => "%$termo_busca%"]);
} else {
    $sql_listagem = "SELECT * FROM produtos ORDER BY id DESC";
    $stmt_lista = $pdo->query($sql_listagem);
}
$produtos = $stmt_lista->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>SAEP - Cadastro de Produtos</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h1>Sistema de Gestão de Estoque - SAEP</h1>
    <h2>Cadastro e Gerenciamento de Produtos</h2>

    <div class="nav-links">
        <a href="index.php">Menu Principal</a>
        <a href="estoque.php">Gestão de Estoque</a>
        <a href="logout.php">Sair</a>
    </div>

    <?php if (!empty($mensagem)): ?>
        <div class="mensagem <?php echo $tipo_mensagem === 'erro' ? 'mensagem-erro' : 'mensagem-sucesso'; ?>">
            <strong><?php echo $tipo_mensagem === 'erro' ? 'ERRO: ' : 'AVISO: '; ?></strong><?php echo htmlspecialchars($mensagem); ?>
        </div>
        <script>
            alert("<?php echo addslashes($mensagem); ?>");
        </script>
    <?php endif; ?>

    <!-- Formulario de Cadastro e Edicao -->
    <h3><?php echo $produto_edicao ? 'Editar Produto' : 'Cadastrar Novo Produto'; ?></h3>

    <form method="POST" action="produtos.php" onsubmit="return validarFormularioProduto(this);">
        <?php if ($produto_edicao): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($produto_edicao['id']); ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="nome">Nome do Produto:</label>
            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($produto_edicao['nome'] ?? $_POST['nome'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="categoria">Categoria:</label>
            <input type="text" id="categoria" name="categoria" value="<?php echo htmlspecialchars($produto_edicao['categoria'] ?? $_POST['categoria'] ?? ''); ?>" placeholder="Exemplo: Martelo, Chave de Fenda">
        </div>

        <div class="form-group">
            <label for="descricao">Descrição:</label>
            <textarea id="descricao" name="descricao" rows="3"><?php echo htmlspecialchars($produto_edicao['descricao'] ?? $_POST['descricao'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label for="tamanho">Tamanho ou Medidas:</label>
            <input type="text" id="tamanho" name="tamanho" value="<?php echo htmlspecialchars($produto_edicao['tamanho'] ?? $_POST['tamanho'] ?? ''); ?>" placeholder="Exemplo: 25mm ou 30cm">
        </div>

        <div class="form-group">
            <label for="peso">Peso:</label>
            <input type="text" id="peso" name="peso" value="<?php echo htmlspecialchars($produto_edicao['peso'] ?? $_POST['peso'] ?? ''); ?>" placeholder="Exemplo: 500g">
        </div>

        <div class="form-group">
            <label for="quantidade_estoque">Quantidade em Estoque:</label>
            <input type="number" id="quantidade_estoque" name="quantidade_estoque" min="0" value="<?php echo htmlspecialchars($produto_edicao['quantidade_estoque'] ?? $_POST['quantidade_estoque'] ?? '0'); ?>">
        </div>

        <div class="form-group">
            <label for="estoque_minimo">Estoque Mínimo:</label>
            <input type="number" id="estoque_minimo" name="estoque_minimo" min="0" value="<?php echo htmlspecialchars($produto_edicao['estoque_minimo'] ?? $_POST['estoque_minimo'] ?? '5'); ?>">
        </div>

        <p>
            <button type="submit"><?php echo $produto_edicao ? 'Salvar Alterações' : 'Cadastrar Produto'; ?></button>
            <?php if ($produto_edicao): ?>
                <a href="produtos.php" class="btn-secondary">Cancelar Edição</a>
            <?php endif; ?>
        </p>
    </form>

    <hr>

    <!-- Campo de Busca -->
    <h3>Pesquisar Produtos</h3>
    <form method="GET" action="produtos.php">
        <div class="form-group">
            <label for="busca">Termo de busca por Nome ou Categoria:</label>
            <input type="text" id="busca" name="busca" value="<?php echo htmlspecialchars($termo_busca); ?>" style="display:inline-block; vertical-align:middle;">
            <button type="submit" style="vertical-align:middle;">Buscar</button>
            <?php if (!empty($termo_busca)): ?>
                <a href="produtos.php" class="btn-secondary" style="vertical-align:middle;">Limpar busca</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Listagem de Produtos em Tabela -->
    <h3>Lista de Produtos Cadastrados</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Categoria</th>
                <th>Descrição</th>
                <th>Tamanho</th>
                <th>Peso</th>
                <th>Estoque Atual</th>
                <th>Estoque Mínimo</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($produtos)): ?>
                <tr>
                    <td colspan="9">Nenhum produto encontrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($produtos as $p): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p['id']); ?></td>
                        <td><?php echo htmlspecialchars($p['nome']); ?></td>
                        <td><?php echo htmlspecialchars($p['categoria']); ?></td>
                        <td><?php echo htmlspecialchars($p['descricao']); ?></td>
                        <td><?php echo htmlspecialchars($p['tamanho']); ?></td>
                        <td><?php echo htmlspecialchars($p['peso']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($p['quantidade_estoque']); ?>
                            <?php if ($p['quantidade_estoque'] < $p['estoque_minimo']): ?>
                                <br><span class="alerta-minimo">Abaixo do mínimo!</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($p['estoque_minimo']); ?></td>
                        <td>
                            <a href="produtos.php?editar_id=<?php echo $p['id']; ?>">Editar</a> | 
                            <a href="produtos.php?excluir_id=<?php echo $p['id']; ?>" onclick="return confirmarExclusao('<?php echo addslashes($p['nome']); ?>');">Excluir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Validacao com JavaScript -->
<script>
    function validarFormularioProduto(form) {
        var nome = form.nome.value.trim();
        var categoria = form.categoria.value.trim();
        var quantidade = form.quantidade_estoque.value.trim();
        var estoqueMinimo = form.estoque_minimo.value.trim();

        if (!nome) {
            alert("O campo Nome do Produto é obrigatório.");
            form.nome.focus();
            return false;
        }
        if (!categoria) {
            alert("O campo Categoria é obrigatório.");
            form.categoria.focus();
            return false;
        }
        if (quantidade === "" || isNaN(quantidade) || parseInt(quantidade, 10) < 0) {
            alert("A Quantidade em Estoque deve ser preenchida com um número válido maior ou igual a zero.");
            form.quantidade_estoque.focus();
            return false;
        }
        if (estoqueMinimo === "" || isNaN(estoqueMinimo) || parseInt(estoqueMinimo, 10) < 0) {
            alert("O Estoque Mínimo deve ser preenchido com um número válido maior ou igual a zero.");
            form.estoque_minimo.focus();
            return false;
        }

        return true;
    }

    function confirmarExclusao(nomeProduto) {
        return confirm("Deseja realmente excluir o produto " + nomeProduto + "?");
    }
</script>

</body>
</html>
