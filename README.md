# 🔐 SSO Login — External Authentication Plugin for Moodle

A local Moodle plugin that enables **Single Sign-On (SSO)** authentication from an external system, using **AES-256-CBC** encryption and **HMAC-SHA256** signatures to ensure data security and integrity.

> **Version:** `v1.6` · **Moodle version code:** `2026051802` · **Maturity:** `MATURITY_STABLE`
> **Requires:** Moodle 5+ · **License:** GNU GPL v3
> **Developer:** [Richard Guedes](https://www.linkedin.com/in/richard-guedes/) — President of the Cyber Defense Institute ([IDCiber](https://idciber.org))

---

## 🚀 Installation

1. Copy the `ssologin` folder to `{moodle_root}/local/`
2. Access Moodle as **Administrator**
3. Go to **Site Administration › Notifications**
4. Moodle will detect the plugin and run the database upgrade

---

## ⚙️ Configuration

After installing, go to:

**Site Administration › Plugins › Local Plugins › SSO Login**

| Setting | Default | Description |
|---|---|---|
| **Shared Secret Key** | Auto-generated | HMAC key shared between Moodle and external system |
| **Token Expiry (seconds)** | `300` | Maximum age of a valid token |
| **Enable Legacy Mode** | Off | Allow requests without HMAC signature (transition only) |
| **Enable JIT Provisioning** | Off | Auto-create Moodle users on first SSO login |
| **Enable Profile Sync** | Off | Update user profile fields on every SSO login |

### Reading settings in code
```php
$secret      = get_config('local_ssologin', 'secretkey');
$tokenexpire = get_config('local_ssologin', 'tokenexpire');
$legacymode  = get_config('local_ssologin', 'legacymode');
$jitprov     = get_config('local_ssologin', 'jitprovisioning');
$profilesync = get_config('local_ssologin', 'profilesync');
```

---

## 🔗 How It Works

```
External System                          Moodle (local_ssologin)
──────────────                           ───────────────────────
1. Build JSON payload
   { username, email, timestamp, nonce }
2. Derive AES key: SHA-256(secret)
3. Encrypt payload (AES-256-CBC)
4. Sign encdata with HMAC-SHA256
5. Redirect user to Moodle ──────────►  6. Verify HMAC signature (before decrypt)
                                         7. Decrypt payload
                                         8. Validate timestamp (clock-skew)
                                         9. Check nonce (replay protection)
                                        10. Account Linking (Fallback to email)
                                        11. JIT provision user (if enabled)
                                        12. Sync profile (if enabled)
                                        13. complete_user_login()
                                        14. Secure redirect ◄── user lands in Moodle
```

> [!IMPORTANT]
> **Protocol rules — all three must be followed exactly:**
> 1. The AES key is **always** `SHA-256(secret)` — never use the raw secret as the key
> 2. The encrypted payload format is `base64( base64(ciphertext) + '::' + iv )`
> 3. The HMAC signs the **already-encrypted Base64 string** — not the raw JSON

---

## 💻 Integration Examples

### 🔹 PHP

```php
<?php
/**
 * Generates the SSO URL to redirect the user to Moodle.
 */
function generate_sso_url(array $userdata, string $sharedSecret, string $moodleLoginUrl, string $redirectAfter = ''): string {

    // 1. Build the JSON payload
    $payload = json_encode(array_merge([
        'timestamp' => time(),
        'nonce'     => bin2hex(random_bytes(16)),
    ], $userdata));

    // 2. Derive AES-256 key via SHA-256 (required — matches locallib.php)
    $key = hash('sha256', $sharedSecret, true);

    // 3. Generate random IV and encrypt (AES-256-CBC)
    $iv         = random_bytes(16);
    $ciphertext = openssl_encrypt($payload, 'aes-256-cbc', $key, 0, $iv);

    // 4. Build encdata: base64(ciphertext . '::' . iv)
    $encdata = base64_encode($ciphertext . '::' . $iv);

    // 5. Sign the already-encrypted encdata with HMAC-SHA256
    $signature = hash_hmac('sha256', $encdata, $sharedSecret);

    // 6. Build final URL
    $params = ['data' => $encdata, 'sig' => $signature];
    if (!empty($redirectAfter)) {
        $params['redirect'] = $redirectAfter;
    }

    return $moodleLoginUrl . '?' . http_build_query($params);
}

// Usage
$url = generate_sso_url(
    [
        'username'  => 'joao.silva',
        'email'     => 'joao@empresa.com',
        'firstname' => 'João',
        'lastname'  => 'Silva',
    ],
    sharedSecret:   'YOUR_SECRET_KEY',
    moodleLoginUrl: 'https://moodle.yoursite.com/local/ssologin/login.php',
    redirectAfter:  '/course/view.php?id=5'
);

header('Location: ' . $url);
exit;
```

---

### 🔹 Python

> **Requirement:** `pip install pycryptodome`

```python
import time, json, base64, hmac, hashlib, os, urllib.parse
from Crypto.Cipher import AES
from Crypto.Util.Padding import pad

def generate_sso_url(userdata: dict, shared_secret: str, moodle_login_url: str, redirect_after: str = '') -> str:
    secret_bytes = shared_secret.encode('utf-8')

    # 1. Build the JSON payload
    payload = json.dumps({
        **userdata,
        'timestamp': int(time.time()),
        'nonce':     os.urandom(16).hex(),
    }, ensure_ascii=False).encode('utf-8')

    # 2. Derive AES-256 key via SHA-256 (required — matches locallib.php)
    key = hashlib.sha256(secret_bytes).digest()  # 32 bytes

    # 3. Generate random IV and encrypt (AES-256-CBC + PKCS7 padding)
    iv         = os.urandom(16)
    cipher     = AES.new(key, AES.MODE_CBC, iv)
    ciphertext = cipher.encrypt(pad(payload, AES.block_size))

    # 4. Build encdata: base64( base64(ciphertext) + '::' + iv )
    #    openssl_encrypt in PHP returns ciphertext as Base64 (flag 0) — mirror that here
    php_ciphertext = base64.b64encode(ciphertext).decode('ascii')
    encdata        = base64.b64encode((php_ciphertext + '::').encode('ascii') + iv).decode('ascii')

    # 5. Sign the already-encrypted encdata with HMAC-SHA256
    signature = hmac.new(secret_bytes, encdata.encode('utf-8'), hashlib.sha256).hexdigest()

    # 6. Build final URL
    params = {'data': encdata, 'sig': signature}
    if redirect_after:
        params['redirect'] = redirect_after

    return moodle_login_url + '?' + urllib.parse.urlencode(params)


# Usage
url = generate_sso_url(
    userdata={
        'username':  'joao.silva',
        'email':     'joao@empresa.com',
        'firstname': 'João',
        'lastname':  'Silva',
    },
    shared_secret='YOUR_SECRET_KEY',
    moodle_login_url='https://moodle.yoursite.com/local/ssologin/login.php',
    redirect_after='/course/view.php?id=5',
)

print(url)
# In a web app: redirect the user to this URL
```

---

### 🔹 Java

> No external dependencies — uses `javax.crypto` from JDK 8+.
> For Java 16 or older, replace `HexFormat.of().formatHex()` with Apache Commons Codec `Hex.encodeHexString()`.

```java
import javax.crypto.Cipher;
import javax.crypto.Mac;
import javax.crypto.spec.IvParameterSpec;
import javax.crypto.spec.SecretKeySpec;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.security.SecureRandom;
import java.util.Base64;
import java.util.HexFormat;

public class SsoLoginHelper {

    /**
     * Generates the SSO URL compatible with the Moodle local_ssologin plugin.
     */
    public static String generateSsoUrl(
            String username, String email, String firstname, String lastname,
            String sharedSecret, String moodleLoginUrl, String redirectAfter
    ) throws Exception {

        long   timestamp = System.currentTimeMillis() / 1000L;
        String nonce     = generateNonce();

        // 1. Build the JSON payload
        String payload = String.format(
            "{\"username\":\"%s\",\"email\":\"%s\",\"firstname\":\"%s\",\"lastname\":\"%s\",\"timestamp\":%d,\"nonce\":\"%s\"}",
            username, email, firstname, lastname, timestamp, nonce
        );

        // 2. Derive AES-256 key via SHA-256 (required — matches locallib.php)
        byte[] key = MessageDigest.getInstance("SHA-256")
                                  .digest(sharedSecret.getBytes(StandardCharsets.UTF_8));

        // 3. Generate random IV and encrypt (AES-256-CBC)
        byte[] iv = new byte[16];
        new SecureRandom().nextBytes(iv);
        Cipher cipher = Cipher.getInstance("AES/CBC/PKCS5Padding");
        cipher.init(Cipher.ENCRYPT_MODE, new SecretKeySpec(key, "AES"), new IvParameterSpec(iv));
        byte[] ciphertext = cipher.doFinal(payload.getBytes(StandardCharsets.UTF_8));

        // 4. Build encdata: base64( base64(ciphertext) + '::' + iv )
        //    openssl_encrypt in PHP returns Base64 ciphertext (flag 0) — mirror that here
        String phpCiphertext = Base64.getEncoder().encodeToString(ciphertext);
        byte[] rawPart       = (phpCiphertext + "::").getBytes(StandardCharsets.US_ASCII);
        byte[] combined      = new byte[rawPart.length + iv.length];
        System.arraycopy(rawPart, 0, combined, 0,             rawPart.length);
        System.arraycopy(iv,      0, combined, rawPart.length, iv.length);
        String encdata = Base64.getEncoder().encodeToString(combined);

        // 5. Sign the already-encrypted encdata with HMAC-SHA256
        Mac mac = Mac.getInstance("HmacSHA256");
        mac.init(new SecretKeySpec(sharedSecret.getBytes(StandardCharsets.UTF_8), "HmacSHA256"));
        String signature = HexFormat.of().formatHex(mac.doFinal(encdata.getBytes(StandardCharsets.UTF_8)));

        // 6. Build final URL
        String url = moodleLoginUrl
            + "?data=" + URLEncoder.encode(encdata, StandardCharsets.UTF_8)
            + "&sig="  + signature;

        if (redirectAfter != null && !redirectAfter.isEmpty()) {
            url += "&redirect=" + URLEncoder.encode(redirectAfter, StandardCharsets.UTF_8);
        }

        return url;
    }

    private static String generateNonce() {
        byte[] bytes = new byte[16];
        new SecureRandom().nextBytes(bytes);
        return HexFormat.of().formatHex(bytes);
    }

    public static void main(String[] args) throws Exception {
        String url = generateSsoUrl(
            "joao.silva", "joao@empresa.com", "João", "Silva",
            "YOUR_SECRET_KEY",
            "https://moodle.yoursite.com/local/ssologin/login.php",
            "/course/view.php?id=5"
        );
        System.out.println(url);
        // In a web app: response.sendRedirect(url);
    }
}
```

---

## 🗂️ Core Functions (`locallib.php`)

| Function | Signature | Description |
|---|---|---|
| `local_ssologin_is_legacy_mode` | `(): bool` | Returns whether Legacy Mode is enabled |
| `local_ssologin_verify_token` | `($data, $sig, $secret): bool` | Validates HMAC-SHA256 using `hash_equals()` (timing-attack safe) |
| `local_ssologin_decrypt` | `($encrypted, $secret): string\|false` | Decrypts AES-256-CBC payload; key derived via `hash('sha256', $secret, true)` |
| `local_ssologin_is_nonce_used` | `($nonce): bool` | Checks `local_ssologin_nonces` table for consumed nonces |
| `local_ssologin_save_nonce` | `($nonce): void` | Persists a used nonce with timestamp via `$DB->insert_record()` |
| `local_ssologin_provision_user` | `(array $payload): stdClass\|false` | JIT: creates a Moodle user from payload. Required fields: `username`, `email`, `firstname`, `lastname` |
| `local_ssologin_sync_profile` | `(stdClass $user, array $payload): void` | Updates only changed profile fields using `user_update_user()` |
| `local_ssologin_log_attempt` | `($status, $userid, $username): void` | Fires `sso_login_attempted` event with status `success` or `fail` |

---

## 🗄️ Database Schema (`db/install.xml`)

### Table: `local_ssologin_nonces`
Stores consumed nonces to prevent replay attacks.

| Column | Type | Description |
|---|---|---|
| `id` | `int(10)` | Primary key, auto-increment |
| `nonce` | `char(64)` | Unique request identifier |
| `timecreated` | `int(10)` | Unix timestamp of when the nonce was consumed |

- **Unique index** on `nonce` for integrity and performance.
- Expired nonces should be purged periodically via Task API (planned improvement).

---

## 📡 Moodle Event (`classes/event/sso_login_attempted.php`)

Namespace: `local_ssologin\event\sso_login_attempted`

| Property | Value |
|---|---|
| CRUD | `r` (Read — login attempt) |
| Level | `LEVEL_OTHER` |
| Context | `context_system` |

```php
\local_ssologin\event\sso_login_attempted::create([
    'context' => \context_system::instance(),
    'other'   => ['username' => $username, 'status' => 'success'],
    'userid'  => $user->id,
])->trigger();
```

Events are visible under **Reports → Logs** in Moodle.

---

## 🌐 Language Strings (`lang/en/local_ssologin.php`)

| Key | Description |
|---|---|
| `pluginname` | Plugin display name: "SSO Login" |
| `secretkey` / `secretkey_desc` | Shared HMAC key |
| `tokenexpire` / `tokenexpire_desc` | Token validity window |
| `legacymode` / `legacymode_desc` | Legacy compatibility toggle |
| `jitprovisioning` / `jitprovisioning_desc` | Automatic user provisioning |
| `profilesync` / `profilesync_desc` | Profile synchronisation |
| `invalidtoken` | Invalid or expired token message |
| `loginsuccess` | Successful login log message |
| `loginfailure` | Failed login log message |
| `eventssologinattempted` | Event name in Moodle logs |
| `privacy:metadata*` | GDPR metadata strings |

---

## 🔒 Security Features

| Threat | Mitigation |
|---|---|
| Padding Oracle Attack | HMAC verified **before** decryption |
| Timing Attack | `hash_equals()` for signature comparison |
| Replay Attack | Single-use nonces stored in `local_ssologin_nonces` |
| Expired Token | Bidirectional timestamp validation (`tokenexpire`) |
| Open Redirect | Only same-host Moodle URLs are accepted |
| SQL Injection | Moodle `$DB` API with automatic placeholders |
| XSS | `required_param` / `optional_param` with `PARAM_*` types |

---

## 🛡️ GDPR Compliance

This plugin implements Moodle's **Privacy API** (`classes/privacy/provider.php`).

The only data stored is the `local_ssologin_nonces` table (nonce string + timestamp), which is not linked to any specific user. No personal data is retained by this plugin.

---

## 📦 Plugin Structure

```
local/ssologin/
├── version.php                        # Version, maturity and requirements
├── login.php                          # SSO entry point
├── locallib.php                       # Core functions (encrypt, verify, provision, sync)
├── settings.php                       # Admin configuration panel
├── lang/en/local_ssologin.php         # Language strings
├── db/install.xml                     # Database schema (nonces table)
└── classes/
    ├── event/sso_login_attempted.php  # Moodle Events API
    └── privacy/provider.php           # GDPR Privacy API
```

---

## ✅ Plugin Directory Checklist

Requirements to publish on [moodle.org/plugins](https://moodle.org/plugins):

- [x] `version.php` with `component`, `version`, `requires`, `maturity`, `release`
- [x] Privacy API implemented (GDPR)
- [x] Language strings in `lang/en/`
- [x] Database schema in `db/install.xml`
- [x] Event registered in `classes/event/`
- [x] GPLv3 header in all files
- [x] PHPDoc on all public functions
- [x] `MATURITY_STABLE` set
- [ ] Pass **Moodle Code Checker** (`local_codechecker`)
- [ ] Periodic cleanup of expired nonces (Task API)

---

## 🖥️ Useful CLI Commands

```bash
# Check coding style (requires local_codechecker)
php admin/tool/codechecker/cli/check.php --path=local/ssologin

# Purge all Moodle caches
php admin/cli/purge_caches.php

# Run database upgrade (after changing version.php)
php admin/cli/upgrade.php

# Run cron manually
php admin/cli/cron.php
```

---

## 📋 Version History

| Version | Date | Highlights |
|---|---|---|
| `v1.0.0` | 2025-04-20 | Initial release: SSO with HMAC + AES-256-CBC |
| `v1.1.0-dev` | 2026-05-09 | Authenticate-then-Decrypt, Nonces, JIT, Profile Sync, Legacy Mode, GDPR |
| `v1.2` | 2026-05-17 | Promoted to `MATURITY_STABLE`, version `2026050900` |
| `v1.3` | 2026-05-17 | Fixed XMLDB schema error (`<PRIMARY>` to `<KEY>`) |
| `v1.4` | 2026-05-18 | Added Account Linking (Email Fallback) to prevent duplicate accounts |
| `v1.5` | 2026-05-18 | Added Moodle HQ Plugin Directory description |
| `v1.6` | 2026-05-18 | Synchronized VERSION attribute in db/install.xml with version.php |

> 📄 See full history in [changelog.txt](./changelog.txt)

---

## 📜 License

GNU General Public License v3 — see [LICENSE](https://www.gnu.org/licenses/gpl-3.0.html)

> ⚠️ A security audit is recommended before deploying in production.

---

## 👨‍💻 Author

Developed by **[Richard Guedes](https://www.linkedin.com/in/richard-guedes/)** — President of the Cyber Defense Institute (IDCiber)

- 🌐 [idciber.org](https://idciber.org)
- 📧 contato@idciber.org
- 💼 [linkedin.com/in/richard-guedes](https://www.linkedin.com/in/richard-guedes/)