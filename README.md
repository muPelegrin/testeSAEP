Sistema de Estoque - SAEP

Este sistema ajuda a controlar ferramentas e equipamentos do almoxarifado. Ele foi criado para a avaliação prática SAEP.

- O que o sistema faz

Permite entrar com e-mail e senha, avisa quando os dados estão incorretos e oferece a opção de sair. Mostra o nome de quem está usando o sistema e destaca produtos com pouco estoque. Permite cadastrar, editar, buscar e excluir produtos pelo nome ou categoria. Confere os campos obrigatórios, organiza os produtos em ordem alfabética e registra entradas e saídas com a data. Também impede retiradas maiores que o saldo, avisa quando o estoque fica abaixo do mínimo e guarda o histórico das movimentações com a data e o nome de quem fez a operação.

- Tecnologias usadas

PHP 8 executa as funções do sistema e acessa o banco de dados. MySQL armazena os dados no banco `saep_db`. JavaScript ajuda na validação dos formulários e nos avisos de estoque. CSS define a aparência das páginas pelo arquivo `style.css`. Apache é o servidor web incluído no XAMPP.

- Como executar

1. Abra o painel do XAMPP e inicie o Apache e o MySQL.
2. Importe o banco de dados usando o arquivo `saep_db.sql`.
3. Abra este endereço no navegador:
"http://localhost/testeSAEP/"
4. Use estes dados para entrar:
E-mail: `admin@saep.com`
Senha: `123456`
