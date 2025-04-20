# 🔐 SSO Login - Plugin de Autenticação Externa para Moodle

Este plugin permite autenticação única (SSO) no Moodle a partir de um sistema externo, utilizando criptografia AES-256-CBC e assinatura HMAC-SHA256 para garantir segurança e integridade dos dados.

---

## 🚀 Instalação

1. Acesse o Moodle como **administrador**
2. Vá até **Administração do site > Notificações**
3. O Moodle detectará o plugin e pedirá para atualizar a base de dados

---

## ⚙️ Configuração

Após instalado, acesse:

**Administração do site > Plugins > Plugins locais > SSO Login**

Configure os seguintes parâmetros:

- 🔑 **Chave Secreta Compartilhada (HMAC)**
- ⏱️ **Tempo máximo permitido para timestamp** (ex: `300` segundos)
- ✅ **Habilitar logs de autenticação**

---

## 🔗 Integração com Sistema Externo

### ✅ Parâmetros esperados:

| Parâmetro | Descrição |
|----------|-----------|
| `data`   | JSON criptografado com AES-256-CBC + codificado em Base64 |
| `sig`    | Assinatura `hash_hmac` SHA256 do payload JSON (antes da criptografia) |

### 📋 O Plugin:

1. **Descriptografa** os dados recebidos via `data`
2. **Valida** a assinatura `sig` com HMAC
3. **Verifica** o tempo de envio (timestamp)
4. **Autentica** o usuário automaticamente no Moodle
5. **Gera logs** de sucesso ou falha (se habilitado)

---

## 💻 Exemplo de Código para Integração

### PHP
```php
function redirect_to_moodle_sso($username, $shared_secret, $moodle_login_url) {
    $timestamp = time();
    $payload = json_encode(['username' => $username, 'timestamp' => $timestamp]);

    $iv = openssl_random_pseudo_bytes(16);
    $ciphertext = openssl_encrypt($payload, 'aes-256-cbc', $shared_secret, 0, $iv);
    $encrypted = base64_encode($ciphertext . '::' . $iv);

    $sig = hash_hmac('sha256', $payload, $shared_secret);

    $url = $moodle_login_url . '?data=' . urlencode($encrypted) . '&sig=' . $sig;
    header("Location: $url");
    exit;
}

require_once 'config.php'; // define MOODLE_SSO
$username = $_SESSION['username'];
$moodle_url = 'https://localhost/moodle/local/ssologin/login.php';
$shared_secret = MOODLE_SSO;

redirect_to_moodle_sso($username, $shared_secret, $moodle_url);

### Python
```python
import time, json, base64, hmac, hashlib
from Crypto.Cipher import AES
from Crypto.Random import get_random_bytes
import urllib.parse, webbrowser

secret = b'CHAVE_SECRETA'.ljust(32, b'\0')
username = 'jose'
timestamp = int(time.time())

payload = json.dumps({'username': username, 'timestamp': timestamp}).encode()
iv = get_random_bytes(16)

cipher = AES.new(secret, AES.MODE_CBC, iv)
padding = 16 - len(payload) % 16
payload += bytes([padding]) * padding
encrypted = base64.b64encode(cipher.encrypt(payload) + b'::' + iv).decode()

sig = hmac.new(secret, payload[:-padding], hashlib.sha256).hexdigest()

url = 'https://seudominio.com/local/ssologin/login.php?data={}&sig={}'.format(
    urllib.parse.quote(encrypted), sig
)

webbrowser.open(url)

### JAVA
```java
String secret = "CHAVE_SECRETA";
String username = "jose";
long timestamp = System.currentTimeMillis() / 1000;

String json = "{\"username\":\"" + username + "\",\"timestamp\":" + timestamp + "}";

// 🔐 Encrypt JSON com AES/CBC/PKCS5Padding
// 🔐 Assinatura HMAC-SHA256
// 🔗 Base64 encode + redirect para Moodle

// ⚠️ A implementação depende da sua stack Java (ex: BouncyCastle, Apache Commons Crypto)

###🔒 Considerações de Segurança
Utilize sempre HTTPS

Armazene a chave secreta com segurança

Revise periodicamente os logs de autenticação

Limite o tempo de validade do token (recomendado ≤ 300s)

Atualize regularmente o plugin

###Licença
GNU GPLv3 - Arquivo LICENSE

Aviso: Recomenda-se auditoria de segurança antes de usar em produção


###👨‍💻 Autor
Desenvolvido por Richard Guedes - Instituto de Defesa Cibernética (IDCiber) – idciber.org
Contato: contato@idciber.org

<p xmlns:cc="http://creativecommons.org/ns#" xmlns:dct="http://purl.org/dc/terms/"><a property="dct:title" rel="cc:attributionURL" href="https://github.com/richardg7/sso_login">SSO Login</a> by <a rel="cc:attributionURL dct:creator" property="cc:attributionName" href="https://www.linkedin.com/in/richard-guedes/">Richard Guedes</a> is licensed under <a href="https://creativecommons.org/licenses/by-sa/4.0/?ref=chooser-v1" target="_blank" rel="license noopener noreferrer" style="display:inline-block;">Creative Commons Attribution-ShareAlike 4.0 International<img style="height:22px!important;margin-left:3px;vertical-align:text-bottom;" src="https://mirrors.creativecommons.org/presskit/icons/cc.svg?ref=chooser-v1" alt=""><img style="height:22px!important;margin-left:3px;vertical-align:text-bottom;" src="https://mirrors.creativecommons.org/presskit/icons/by.svg?ref=chooser-v1" alt=""><img style="height:22px!important;margin-left:3px;vertical-align:text-bottom;" src="https://mirrors.creativecommons.org/presskit/icons/sa.svg?ref=chooser-v1" alt=""></a></p>