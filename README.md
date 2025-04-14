# Local SSO Login Plugin

Este plugin permite a autenticação única (SSO) no Moodle através de um sistema externo, utilizando um token HMAC para validar a requisição de login.

## Pré-requisitos no Moodle

- **Plugin "Local SSO Login" instalado.**
- **Secret configurado:**  
  A chave secreta é gerada diretamente no Moodle e visível apenas para administradores.  
  Para configurar, acesse: **Administração do site > Plugins locais > Local SSO Login**.

- **Usuário existente no Moodle:**  
  O plugin realiza a verificação utilizando o `username` e o `email` do usuário.

- **Acesso via HTTPS obrigatório:**  
  Conforme validado no script, para garantir uma conexão segura.

## Configuração do Sistema Externo

1. **Obtenha a chave secreta:**  
   Copie a chave secreta da interface administrativa do Moodle (configuração do plugin).

2. **Ajuste o código do cliente:**  
   Cole a chave copiada no valor da variável `$secret` no código abaixo:

   ```php
   // === CONFIGURAÇÕES ===
   $moodle_url = 'http://SEU-MOODLE/local/sso_login/index.php'; // Altere para a URL correta
   $secret = '9a862f6cb13059ac9490f04555943c08'; // Copie da configuração do Moodle

   // === DADOS DO USUÁRIO ===
   $username = 'usuario12345';
   $email = 'joao5@email.com';
   $time = time();

   // === GERAÇÃO DO TOKEN ===
   $token = hash_hmac('sha256', $username . $email . $time . $secret, $secret);

   // === PARÂMETROS PARA LOGIN ===
   $params = http_build_query([
       'username' => $username,
       'email'    => $email,
       'time'     => $time,
       'token'    => $token,
       // 'courseid' => 2, // opcional
   ]);

   // === REDIRECIONAMENTO PARA O MOODLE ===
   header("Location: {$moodle_url}?{$params}");
   exit;
