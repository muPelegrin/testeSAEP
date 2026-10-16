<?php
require_once 'conexao.php';

// Se ja estiver logado, redireciona para a interface principal
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$mensagem_erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if (empty($email) || empty($senha)) {
        $mensagem_erro = 'Por favor, preencha todos os campos de login.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();

        // Verifica a senha (compativel com texto puro ou hash)
        if ($usuario && ($usuario['senha'] === $senha || password_verify($senha, $usuario['senha']))) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            header('Location: index.php');
            exit;
        } else {
            $mensagem_erro = 'Falha na autenticação: E-mail ou senha incorretos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>SAEP - Autenticação de Usuário</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h1>Sistema de Gestão de Estoque - SAEP</h1>
    <h2>Autenticação de Usuários</h2>

    <?php if (!empty($mensagem_erro)): ?>
        <div class="mensagem mensagem-erro">
            <strong>ERRO:</strong> <?php echo htmlspecialchars($mensagem_erro); ?>
        </div>
        <script>
            alert("<?php echo addslashes($mensagem_erro); ?>");
        </script>
    <?php endif; ?>

    <form method="POST" action="login.php" onsubmit="return validarLogin(this);">
        <div class="form-group">
            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="senha">Senha:</label>
            <input type="password" id="senha" name="senha">
        </div>

        <p>
            <button type="submit">Entrar</button>
        </p>
    </form>

    <hr>
    <div class="box-info">
        <p><strong>Contas de teste cadastradas no banco:</strong></p>
        <ul>
            <li>admin@saep.com - Senha: 123456</li>
            <li>carlos@saep.com - Senha: 123456</li>
            <li>mariana@saep.com - Senha: 123456</li>
        </ul>
    </div>
</div>

<script>
    function validarLogin(form) {
        if (!form.email.value.trim() || !form.senha.value.trim()) {
            alert("Por favor, preencha o e-mail e a senha antes de continuar.");
            return false;
        }
        return true;
    }
</script>

</body>
</html>
