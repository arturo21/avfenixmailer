<?php
class AVFenixMailer
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private bool $smtpAuth = true;
    private string $encryption = 'tls'; // 'ssl', 'tls', 'none'
    private string $from;
    private string $fromName = '';
    private array $to = [];
    private array $cc = [];
    private array $bcc = [];
    private string $subject = '';
    private string $bodyHtml = '';
    private string $bodyText = '';
    private array $attachments = []; // [ ['path' => ..., 'name' => ..., 'type' => ...], ... ]
    private int $maxAttachmentSize = 5_000_000; // 5 MB por defecto
    private $socket = null;
    private string $logFile = 'smtp_debug_log.txt';

    public function __construct(array $config)
    {
        $this->host       = $config['host'];
        $this->port       = $config['port'];
        $this->username   = $config['username'];
        $this->password   = $config['password'];
        $this->smtpAuth   = $config['smtp_auth'] ?? true;
        $this->encryption = $config['encryption'] ?? 'tls';
        $this->from       = $config['from'];
        $this->fromName   = $config['from_name'] ?? '';
        $this->log("=== Nuevo intento de envío ===");
    }

    public function addRecipient(string $email, string $name = '')
    {
        $this->to[] = [$email, $name];
        $this->log("➕ Para: $name <$email>");
    }

    public function addCc(string $email, string $name = '')
    {
        $this->cc[] = [$email, $name];
        $this->log("📎 CC: $name <$email>");
    }

    public function addBcc(string $email, string $name = '')
    {
        $this->bcc[] = [$email, $name];
        $this->log("🙈 BCC: $name <$email>");
    }

    public function setSubject(string $subject)
    {
        $this->subject = $subject;
        $this->log("📝 Asunto: $subject");
    }

    public function setBodyHtml(string $html, string $textFallback = '')
    {
        $this->bodyHtml = $html;
        $this->bodyText = $textFallback ?: strip_tags($html);
        $this->log("📄 Cuerpo HTML definido. Longitud: " . strlen($html));
    }

    public function addAttachment(string $filePath, string $fileName = '', string $mimeType = 'application/octet-stream')
    {
        if (!file_exists($filePath)) {
            $this->log("⚠️ Archivo no encontrado: $filePath");
            return;
        }

        $fileSize = filesize($filePath);
        if ($fileSize > $this->maxAttachmentSize) {
            $this->log("🚫 Archivo demasiado grande: $filePath ({$fileSize} bytes)");
            return;
        }

        $this->attachments[] = [
            'path' => $filePath,
            'name' => $fileName ?: basename($filePath),
            'type' => $mimeType
        ];
        $this->log("📎 Adjuntado: {$fileName} ({$mimeType}, {$fileSize} bytes)");
    }

    public function setMaxAttachmentSize(int $bytes)
    {
        $this->maxAttachmentSize = $bytes;
        $this->log("⚙️ Tamaño máximo de adjunto establecido en {$bytes} bytes");
    }

    public function send(): bool
    {
        if (!$this->connectSMTP()) {
            $this->log("❌ Error de conexión SMTP.");
            return false;
        }

        if (!$this->smtpCommand("EHLO localhost")) return false;

        if ($this->encryption === 'tls') {
            if (!$this->smtpCommand("STARTTLS")) return false;
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                $this->log("❌ Error al activar STARTTLS.");
                return false;
            }
            $this->log("🔐 STARTTLS activado.");
            if (!$this->smtpCommand("EHLO localhost")) return false;
        }

        if ($this->smtpAuth) {
            if (!$this->authLogin()) {
                $this->log("❌ Falló la autenticación con AUTH LOGIN.");
                return false;
            }
        }

        if (!$this->smtpCommand("MAIL FROM:<{$this->from}>")) return false;

        foreach (array_merge($this->to, $this->cc, $this->bcc) as [$email]) {
            if (!$this->smtpCommand("RCPT TO:<$email>")) return false;
        }

        if (!$this->smtpCommand("DATA")) return false;
        fwrite($this->socket, $this->buildMessage() . "\r\n.\r\n");
        $this->log("📤 Mensaje enviado al servidor.");
        $this->smtpCommand("QUIT");

        fclose($this->socket);
        $this->log("✅ Conexión cerrada correctamente.");
        return true;
    }

    private function connectSMTP(): bool
    {
        $transport = $this->encryption === 'ssl' ? 'ssl://' : '';
        $this->log("🌐 Conectando a {$transport}{$this->host}:{$this->port}...");
        $this->socket = stream_socket_client(
            "{$transport}{$this->host}:{$this->port}",
            $errno,
            $errstr,
            10,
            STREAM_CLIENT_CONNECT
        );

        if ($this->socket === false) {
            $this->log("❌ stream_socket_client falló: $errstr ($errno)");
        } else {
            $this->log("✅ Conexión establecida.");
        }

        return $this->socket !== false;
    }

    private function smtpCommand(string $cmd): bool
    {
        fwrite($this->socket, $cmd . "\r\n");
        $response = fgets($this->socket, 512);
        $this->log("→ $cmd");
        $this->log("← $response");
        return $response && preg_match('/^[23]/', $response);
    }

    private function authLogin(): bool
    {
        $this->log("🔍 Iniciando autenticación AUTH LOGIN...");
        if (!$this->smtpCommand("AUTH LOGIN")) return false;

        $response1 = fgets($this->socket, 512);
        $this->log("← $response1");
        if (!preg_match('/^334/', $response1)) return false;

        if (!$this->smtpCommand(base64_encode($this->username))) return false;

        $response2 = fgets($this->socket, 512);
        $this->log("← $response2");
        if (!preg_match('/^334/', $response2)) return false;

        if (!$this->smtpCommand(base64_encode($this->password))) return false;

        $response3 = fgets($this->socket, 512);
        $this->log("← $response3");
        if (!preg_match('/^235/', $response3)) return false;

        $this->log("✅ Autenticación con AUTH LOGIN exitosa.");
        return true;
    }

    private function buildMessage(): string
    {
        $boundary = "=_boundary_" . md5(uniqid(time(), true));

        $toHeader  = implode(', ', array_map(fn($r) => "{$r[1]} <{$r[0]}>", $this->to));
        $ccHeader  = implode(', ', array_map(fn($r) => "{$r[1]} <{$r[0]}>", $this->cc));
        $bccHeader = implode(', ', array_map(fn($r) => "{$r[1]} <{$r[0]}>", $this->bcc));

        $headers = [
            "Date: " . date('r'),
            "Message-ID: <" . uniqid() . "@megapractical.com>",
            "Return-Path: <{$this->from}>",
            "From: {$this->fromName} <{$this->from}>",
            "To: $toHeader",
            $ccHeader ? "Cc: $ccHeader" : null,
            $bccHeader ? "Bcc: $bccHeader" : null,
            "Reply-To: {$this->from}",
            "Subject: {$this->subject}",
            "MIME-Version: 1.0",
            "X-Mailer: AVFenixMailer PHP",
            "Content-Type: multipart/mixed; boundary=\"$boundary\""
        ];

        $headers = array_filter($headers);

        $htmlBody = quoted_printable_encode($this->wrapHtml($this->bodyHtml));
        $textBody = quoted_printable_encode($this->bodyText);

        $body = "--$boundary\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $body .= "$textBody\r\n\r\n";

        $body .= "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $body .= "$htmlBody\r\n\r\n";

        $body .= "--$boundary--";
        //Agrega los archivos adjuntos
        foreach ($this->attachments as $attachment) {
            $fileContent = chunk_split(base64_encode(file_get_contents($attachment['path'])));
            $body .= "\r\n--$boundary\r\n";
            $body .= "Content-Type: {$attachment['type']}; name=\"{$attachment['name']}\"\r\n";
            $body .= "Content-Disposition: attachment; filename=\"{$attachment['name']}\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= "$fileContent\r\n";
        }
        $body .= "--$boundary--";

        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }

    private function wrapHtml(string $html): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$this->subject}</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #ffffff;
      color: #333333;
      margin: 0;
      padding: 20px;
    }
    h1, h2, h3 {
      color: #005f99;
      margin-bottom: 10px;
    }
    p {
      line-height: 1.6;
      margin-bottom: 15px;
    }
    a {
      color: #0077cc;
      text-decoration: none;
    }
    a:hover {
      text-decoration: underline;
    }
    .footer {
      font-size: 12px;
      color: #777777;
      margin-top: 30px;
      border-top: 1px solid #eeeeee;
      padding-top: 10px;
    }
  </style>
</head>
<body>
  {$html}
  <div class="footer">
    Este correo fue enviado automáticamente por el sistema FactFlow Soffia. Por favor no responda a este mensaje.
  </div>
</body>
</html>
HTML;
    }

    private function log(string $message): void
    {
        file_put_contents($this->logFile, "[" . date('Y-m-d H:i:s') . "] $message\n", FILE_APPEND | LOCK_EX);
    }
}
