<?php
declare(strict_types=1);

namespace MagicCMS\Core;

/**
 * Outgoing mail. Drivers (Admin → Settings → Email):
 *   mail – PHP mail() (works on most shared hosts)
 *   smtp – built-in SMTP client (plain, STARTTLS or implicit TLS; AUTH LOGIN)
 *   log  – writes messages to cache/mail.log instead of sending (development / testing)
 * Header injection is prevented (no CR/LF in headers), the SMTP password is stored encrypted.
 */
final class Mailer
{
    public static string $error = '';

    public static function config(): array
    {
        return [
            'driver' => in_array(setting('mail.driver', 'mail'), ['mail', 'smtp', 'log'], true) ? (string) setting('mail.driver', 'mail') : 'mail',
            'from_name' => (string) setting('mail.from_name', setting('site.name', 'MagicCMS')),
            'from_email' => (string) setting('mail.from_email', ''),
            'host' => (string) setting('mail.smtp_host', ''), 'port' => (int) setting('mail.smtp_port', 587),
            'secure' => in_array(setting('mail.smtp_secure', 'tls'), ['none', 'tls', 'ssl'], true) ? (string) setting('mail.smtp_secure', 'tls') : 'tls',
            'user' => (string) setting('mail.smtp_user', ''), 'pass' => self::decrypt((string) setting('mail.smtp_pass', '')),
        ];
    }

    private static function key(): string
    {
        return substr(hash('sha256', 'mcms-mail|' . (string) (Bootstrap::$config['app']['secret'] ?? ''), true), 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }

    public static function encrypt(string $plain): string
    {
        if ($plain === '') {
            return '';
        }
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return 'enc:' . base64_encode($nonce . sodium_crypto_secretbox($plain, $nonce, self::key()));
    }

    public static function decrypt(string $stored): string
    {
        if (!str_starts_with($stored, 'enc:')) {
            return '';
        }
        $raw = base64_decode(substr($stored, 4), true) ?: '';
        if (strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return '';
        }
        $plain = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), self::key());
        return $plain === false ? '' : $plain;
    }

    public static function fromEmail(): string
    {
        $c = (string) setting('mail.from_email', '');
        if (filter_var($c, FILTER_VALIDATE_EMAIL)) {
            return $c;
        }
        $host = parse_url((string) (Bootstrap::$config['app']['url'] ?? ''), PHP_URL_HOST) ?: 'localhost';
        return 'noreply@' . preg_replace('/^www\./', '', $host);
    }

    private static function clean(string $s): string
    {
        return trim((string) preg_replace('/[\r\n]+/', ' ', $s));
    }

    private static function enc(string $s): string
    {
        return preg_match('/[^\x20-\x7E]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
    }

    /** Branded HTML e-mail. $button = [label, url] */
    public static function template(string $title, string $bodyHtml, ?array $button = null, string $footer = ''): string
    {
        $site = e((string) setting('site.name', 'MagicCMS'));
        $btn = $button ? '<p style="margin:26px 0"><a href="' . e($button[1]) . '" style="display:inline-block;padding:13px 26px;border-radius:999px;background:linear-gradient(135deg,#ff5238,#e5091f);color:#ffffff;font-weight:700;text-decoration:none">' . e($button[0]) . '</a></p><p style="font-size:12px;color:#9a8a80;word-break:break-all">' . e(t('If the button does not work, copy this link into your browser:')) . '<br>' . e($button[1]) . '</p>' : '';
        return '<!doctype html><html><body style="margin:0;padding:0;background:#0a0706"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0a0706;padding:28px 12px"><tr><td align="center">'
            . '<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;background:#17110e;border:1px solid #2d211b;border-radius:20px;overflow:hidden;font-family:Segoe UI,Arial,sans-serif;color:#fff4ec">'
            . '<tr><td style="padding:18px 26px;background:linear-gradient(90deg,#8c0012,#e5091f,#ff5238);font-size:14px;font-weight:800;letter-spacing:2px;text-transform:uppercase;color:#fff">' . $site . '</td></tr>'
            . '<tr><td style="padding:28px 26px;font-size:15px;line-height:1.6"><h1 style="margin:0 0 14px;font-size:22px;color:#fff4ec">' . e($title) . '</h1>' . $bodyHtml . $btn . '</td></tr>'
            . '<tr><td style="padding:16px 26px;border-top:1px solid #2d211b;font-size:12px;color:#8f7d73">' . ($footer !== '' ? e($footer) : e(t('You received this e-mail because of activity on {site}.', ['site' => (string) setting('site.name')]))) . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    public static function send(string $to, string $subject, string $html, ?string $text = null): bool
    {
        self::$error = '';
        $to = self::clean($to);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            self::$error = 'invalid recipient';
            return false;
        }
        $c = self::config();
        $subject = self::clean($subject);
        $text ??= trim(html_entity_decode(strip_tags(preg_replace('#<(br|/p|/h1|/tr)>#i', "\n", $html) ?? ''), ENT_QUOTES, 'UTF-8'));
        $from = self::fromEmail();
        $fromName = self::clean($c['from_name']);
        $bound = 'b' . bin2hex(random_bytes(8));
        $headers = [
            'Date: ' . date('r'), 'Message-ID: <' . bin2hex(random_bytes(10)) . '@' . (parse_url(abs_url(''), PHP_URL_HOST) ?: 'localhost') . '>',
            'From: ' . self::enc($fromName) . ' <' . $from . '>', 'To: ' . $to, 'Subject: ' . self::enc($subject),
            'MIME-Version: 1.0', 'Content-Type: multipart/alternative; boundary="' . $bound . '"', 'X-Mailer: MagicCMS',
        ];
        $body = '--' . $bound . "\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($text))
            . '--' . $bound . "\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html))
            . '--' . $bound . "--\r\n";

        try {
            return match ($c['driver']) {
                'log' => self::toLog($to, $subject, $text),
                'smtp' => self::smtp($c, $from, $to, implode("\r\n", $headers) . "\r\n\r\n" . $body),
                default => self::phpMail($to, $subject, $headers, $body),
            };
        } catch (\Throwable $e) {
            self::$error = $e->getMessage();
            error_log('MagicCMS mail: ' . $e->getMessage());
            return false;
        }
    }

    private static function toLog(string $to, string $subject, string $text): bool
    {
        $line = json_encode(['at' => gmdate('c'), 'to' => $to, 'subject' => $subject, 'text' => $text], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        return file_put_contents(MCMS_ROOT . '/cache/mail.log', $line, FILE_APPEND | LOCK_EX) !== false;
    }

    private static function phpMail(string $to, string $subject, array $headers, string $body): bool
    {
        $h = array_values(array_filter($headers, fn($x) => !preg_match('/^(To|Subject):/i', $x)));
        $ok = @mail($to, self::enc($subject), $body, implode("\r\n", $h));
        if (!$ok) {
            self::$error = 'mail() failed – configure SMTP in Admin → Settings → Email';
        }
        return $ok;
    }

    private static function smtp(array $c, string $from, string $to, string $data): bool
    {
        if ($c['host'] === '') {
            throw new \RuntimeException('SMTP host is not configured');
        }
        $remote = ($c['secure'] === 'ssl' ? 'ssl://' : 'tcp://') . $c['host'] . ':' . $c['port'];
        $fp = @stream_socket_client($remote, $errno, $errstr, 12, STREAM_CLIENT_CONNECT);
        if (!$fp) {
            throw new \RuntimeException("SMTP connect failed: $errstr");
        }
        stream_set_timeout($fp, 12);
        $read = function () use ($fp): string {
            $out = '';
            while (($l = fgets($fp, 1024)) !== false) {
                $out .= $l;
                if (strlen($l) < 4 || $l[3] === ' ') {
                    break;
                }
            }
            return $out;
        };
        $cmd = function (string $line, array $ok) use ($fp, $read): string {
            fwrite($fp, $line . "\r\n");
            $r = $read();
            if (!in_array((int) substr($r, 0, 3), $ok, true)) {
                throw new \RuntimeException('SMTP error: ' . trim($r));
            }
            return $r;
        };
        try {
            if ((int) substr($read(), 0, 3) !== 220) {
                throw new \RuntimeException('SMTP greeting failed');
            }
            $host = parse_url(abs_url(''), PHP_URL_HOST) ?: 'localhost';
            $cmd('EHLO ' . $host, [250]);
            if ($c['secure'] === 'tls') {
                $cmd('STARTTLS', [220]);
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('STARTTLS failed');
                }
                $cmd('EHLO ' . $host, [250]);
            }
            if ($c['user'] !== '') {
                $cmd('AUTH LOGIN', [334]);
                $cmd(base64_encode($c['user']), [334]);
                $cmd(base64_encode($c['pass']), [235]);
            }
            $cmd('MAIL FROM:<' . $from . '>', [250]);
            $cmd('RCPT TO:<' . $to . '>', [250, 251]);
            $cmd('DATA', [354]);
            fwrite($fp, preg_replace('/^\./m', '..', $data) . "\r\n.\r\n");
            $r = $read();
            if ((int) substr($r, 0, 3) !== 250) {
                throw new \RuntimeException('SMTP rejected the message: ' . trim($r));
            }
            @fwrite($fp, "QUIT\r\n");
            return true;
        } finally {
            @fclose($fp);
        }
    }
}
