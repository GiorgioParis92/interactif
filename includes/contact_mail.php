<?php
/**
 * Envoi des mails du formulaire contact.
 * 1) Si includes/contact_smtp_config.php existe → SMTP (IONOS, etc.)
 * 2) Sinon → PHP mail() avec en-têtes adaptés à l’hébergement mutualisé
 */

declare(strict_types=1);

/**
 * @param  string      $to            Destinataire (email du site)
 * @param  string      $replyTo       Email du visiteur
 * @param  string      $subjectMime   Sujet déjà encodé MIME (?=UTF-8?B?...?=)
 * @param  string      $subjectPlain  Sujet lisible (pour le corps SMTP)
 * @param  string      $body          Corps texte brut
 */
function mia_contact_send_mail(
    string $to,
    string $replyTo,
    string $subjectMime,
    string $subjectPlain,
    string $body
): bool {
    $to    = trim($to);
    $from  = $to;

    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    if (!filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $configFile = __DIR__ . '/contact_smtp_config.php';
    if (is_readable($configFile)) {
        /** @var array<string, mixed> $cfg */
        $cfg = include $configFile;
        if (is_array($cfg) && !empty($cfg['enabled']) && mia_contact_smtp_send($cfg, $to, $replyTo, $subjectPlain, $body)) {
            return true;
        }
        if (is_array($cfg) && !empty($cfg['enabled'])) {
            error_log('mia_contact: SMTP configuré mais envoi échoué');
            return false;
        }
    }

    return mia_contact_php_mail($to, $from, $replyTo, $subjectMime, $body);
}

function mia_contact_php_mail(
    string $to,
    string $from,
    string $replyTo,
    string $subjectMime,
    string $body
): bool {
    $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
    if (in_array('mail', $disabled, true)) {
        error_log('mia_contact: fonction mail() désactivée sur ce serveur');
        return false;
    }

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= 'From: MIA <' . $from . ">\r\n";
    $headers .= 'Reply-To: ' . $replyTo . "\r\n";
    $headers .= 'X-Mailer: PHP/' . phpversion() . "\r\n";

    $oldFrom = ini_get('sendmail_from');
    ini_set('sendmail_from', $from);

    $extra = '-f' . $from;
    $ok    = @mail($to, $subjectMime, $body, $headers, $extra);

    if ($oldFrom !== false && $oldFrom !== '') {
        ini_set('sendmail_from', $oldFrom);
    } else {
        ini_restore('sendmail_from');
    }

    return $ok;
}

/**
 * @param array<string, mixed> $cfg
 */
function mia_contact_smtp_send(array $cfg, string $to, string $replyTo, string $subject, string $body): bool
{
    $host   = (string) ($cfg['host'] ?? '');
    $port   = (int) ($cfg['port'] ?? 465);
    $user   = (string) ($cfg['user'] ?? '');
    $pass   = (string) ($cfg['pass'] ?? '');
    $from   = (string) ($cfg['from'] ?? $user);
    $enc    = strtolower((string) ($cfg['encryption'] ?? 'ssl'));

    if ($host === '' || $user === '' || $pass === '' || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $remote = ($enc === 'tls' || $port === 587)
        ? "tcp://{$host}:{$port}"
        : "ssl://{$host}:{$port}";

    $ctx = stream_context_create([
        'ssl' => [
            'verify_peer'       => true,
            'verify_peer_name'  => true,
            'allow_self_signed' => false,
        ],
    ]);

    $fp = @stream_socket_client($remote, $errno, $errstr, 25, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        error_log("mia_contact SMTP connect: $errstr ($errno)");
        return false;
    }
    stream_set_timeout($fp, 25);

    $read = static function () use ($fp): string {
        $out = '';
        while ($line = fgets($fp, 8192)) {
            $out .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }
        return $out;
    };

    $write = static function (string $cmd) use ($fp): void {
        fwrite($fp, $cmd . "\r\n");
    };

    $expect = static function (string $resp, string $code): bool {
        return str_starts_with($resp, $code);
    };

    $domain = 'localhost';
    if (preg_match('/@([^>]+)/', $from, $m)) {
        $domain = trim($m[1]);
    }

    if (!$expect($read(), '220')) {
        fclose($fp);
        return false;
    }

    $write('EHLO ' . $domain);
    $read();

    if ($enc === 'tls' || $port === 587) {
        $write('STARTTLS');
        if (!$expect($read(), '220')) {
            fclose($fp);
            return false;
        }
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($fp);
            return false;
        }
        $write('EHLO ' . $domain);
        $read();
    }

    $write('AUTH LOGIN');
    if (!$expect($read(), '334')) {
        fclose($fp);
        return false;
    }
    $write(base64_encode($user));
    if (!$expect($read(), '334')) {
        fclose($fp);
        return false;
    }
    $write(base64_encode($pass));
    if (!$expect($read(), '235')) {
        fclose($fp);
        return false;
    }

    $write('MAIL FROM:<' . $from . '>');
    if (!$expect($read(), '250')) {
        fclose($fp);
        return false;
    }

    $write('RCPT TO:<' . $to . '>');
    if (!$expect($read(), '250')) {
        fclose($fp);
        return false;
    }

    $write('DATA');
    if (!$expect($read(), '354')) {
        fclose($fp);
        return false;
    }

    $subjHdr = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $data    = "From: MIA <{$from}>\r\n";
    $data .= "To: <{$to}>\r\n";
    $data .= "Subject: {$subjHdr}\r\n";
    $data .= "Reply-To: {$replyTo}\r\n";
    $data .= "MIME-Version: 1.0\r\n";
    $data .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $data .= "\r\n";
    $norm = str_replace(["\r\n", "\r", "\n"], "\n", $body);
    $lines = explode("\n", $norm);
    foreach ($lines as $i => $line) {
        if ($line !== '' && $line[0] === '.') {
            $lines[$i] = '.' . $line;
        }
    }
    $data .= implode("\r\n", $lines);
    $data .= "\r\n.\r\n";

    fwrite($fp, $data);
    if (!$expect($read(), '250')) {
        fclose($fp);
        return false;
    }

    $write('QUIT');
    fclose($fp);
    return true;
}
