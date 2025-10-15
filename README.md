# 🔥 SimpleMailer

**Versión estable: 1.0.0**

SimpleMailer es una clase PHP ligera y modular para enviar correos electrónicos mediante SMTP con autenticación `AUTH LOGIN`, soporte para `STARTTLS` y `SSL`, encabezados MIME robustos, codificación `quoted-printable`, múltiples destinatarios (`To`, `Cc`, `Bcc`) y archivos adjuntos. Diseñada para máxima compatibilidad con servidores exigentes como `smtp.uservers.net`.

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
require_once 'SimpleMailer.php';
```

## Ejemplo de Uso

```php
$mailer = new SimpleMailer([
    'host'       => 'smtp.uservers.net',
    'port'       => 587, // o 465 para SSL
    'username'   => 'factflow.soffia@megapractical.com',
    'password'   => 'tu-contraseña',
    'encryption' => 'tls', // 'ssl', 'tls' o 'none'
    'smtp_auth'  => true,
    'from'       => 'factflow.soffia@megapractical.com',
    'from_name'  => 'FactFlow Soffia'
]);
```

## Ejemplo de Uso

```php
$mailer->addRecipient('cliente@ejemplo.com', 'Cliente');
$mailer->addCc('soporte@megapractical.com', 'Soporte');
$mailer->addBcc('auditoria@megapractical.com', 'Auditoría');

$mailer->setSubject('Reestablecimiento de Contraseña');
$mailer->setBodyHtml('<h1>Hola</h1><p>Haz clic para reestablecer tu contraseña.</p>');

$mailer->send();
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