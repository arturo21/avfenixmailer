
<h1 align="center">🔥 AVFenixMailer</h1>

**Versión estable: 1.0.0**

AVFenixMailer es una clase PHP ligera y modular para enviar correos electrónicos mediante SMTP con autenticación `AUTH LOGIN`, soporte para `STARTTLS` y `SSL`, encabezados MIME robustos, codificación `quoted-printable`, múltiples destinatarios (`To`, `Cc`, `Bcc`) y archivos adjuntos. Diseñada para máxima compatibilidad con servidores exigentes como `smtp.uservers.net`.

---

## ✨ Características

- Autenticación exclusiva por `AUTH LOGIN`
- Soporte para `STARTTLS`, `SSL` y conexiones sin cifrado
- Encabezados MIME completos y bien formateados
- Codificación `quoted-printable` para máxima entregabilidad
- Soporte para múltiples destinatarios, CC y BCC
- Archivos adjuntos con validación de tamaño
- Cuerpo HTML con fallback en texto plano
- Logging detallado en UTF-8 para auditoría y depuración

---

## 🚀 Instalación

SimpleMailer no requiere dependencias externas. Solo incluye el archivo en tu proyecto:

```php
require_once 'AVFenixMailer.php';
```

## Ejemplo de Uso Básico

```php
$mailer = new AVFenixMailer([
    'host'       => 'smtp.xxxxx.xxx',
    'port'       => 587, // o 465 para SSL
    'username'   => 'xxxxx@xxxxxx.xxx',
    'password'   => 'tu-contraseña',
    'encryption' => 'tls', // 'ssl', 'tls' o 'none'
    'smtp_auth'  => true,
    'from'       => 'xxxxx@xxxxxx.xxx',
    'from_name'  => 'PRUEBA'
]);
```

## Añadir CC/BCC - Asunto - Mensaje
```php
$mailer->addRecipient('cliente@ejemplo.com', 'Cliente');
$mailer->addCc('soporte@xxxxxxxxxx.com', 'Soporte');
$mailer->addBcc('auditoria@xxxxxxxxxx.com', 'Auditoría');

$mailer->setSubject('Reestablecimiento de Contraseña');
$mailer->setBodyHtml('<h1>Hola</h1><p>Haz clic para reestablecer tu contraseña.</p>');

$mailer->send();
```

## Ejemplo con try/catch y excepciones

```php
require_once 'AVFenixMailer.php';

try {
    $mailer = new AVFenixMailer([
	    'host'       => 'smtp.xxxxx.xxx',
	    'port'       => 587, // o 465 para SSL
	    'username'   => 'xxxxx@xxxxxx.xxx',
	    'password'   => 'tu-contraseña',
	    'encryption' => 'tls', // 'ssl', 'tls' o 'none'
	    'smtp_auth'  => true,
	    'from'       => 'xxxxx@xxxxxx.xxx',
	    'from_name'  => 'PRUEBA'
    ]);

    $mailer->addRecipient('cliente@ejemplo.com', 'Cliente');
    $mailer->addCc('soporte@xxxxxxxxxx.com', 'Soporte');
    $mailer->addBcc('auditoria@xxxxxxxxxx.com', 'Auditoría');

    $mailer->setSubject('Reestablecimiento de Contraseña');
    $mailer->setBodyHtml(
        '<h1>Hola</h1><p>Haz clic para reestablecer tu contraseña.</p>'
    );

    $mailer->addAttachment('docs/manual.pdf', 'Manual.pdf', 'application/pdf');

    if (!$mailer->send()) {
        throw new Exception('El envío falló. Revisa el log para más detalles.');
    }

    echo "✅ Correo enviado correctamente.";

} catch (Exception $e) {
    echo "❌ Error al enviar el correo: " . $e->getMessage();
}
```

## 📎 Adjuntar archivos
```php
	$mailer->addAttachment('/ruta/manual.pdf', 'Manual.pdf', 'application/pdf');
	$mailer->addAttachment('/ruta/logo.png', 'Logo.png', 'image/png');
```

## 🔒 Validación de tamaño
```php
	$mailer->setMaxAttachmentSize(10_000_000); // 10 MB
```

## 📓 Registro de actividad
```php
 /*smtp_debug_log.txt*/
```