# Configuración del Formulario de Contacto PHP

## 📋 Archivos creados

1. **`/public/contacto.php`** - Script PHP que procesa el formulario
2. **`/public/contacto-config.php.example`** - Archivo de configuración de ejemplo

## 🚀 Configuración en el servidor

### Paso 1: Crear archivo de configuración

En el servidor, copia el archivo de ejemplo y configúralo:

```bash
cp public/contacto-config.php.example public/contacto-config.php
```

### Paso 2: Editar `contacto-config.php`

Abre el archivo y configura tus datos:

```php
<?php
return [
  'BASE_PATH' => '',
  'SITE_URL' => 'https://aplicacionweb.cl',
  'TO_EMAIL' => 'tu-email@ejemplo.com',  // Email donde recibirás los mensajes
  'FROM_EMAIL' => 'contacto@aplicacionweb.cl',
  'FROM_NAME' => 'Aplicación Web',
  'BCC_EMAILS' => '',  // Opcional: emails en copia oculta
];
```

### Paso 3: Verificar permisos

Asegúrate de que el archivo PHP tenga permisos de ejecución:

```bash
chmod 644 public/contacto.php
chmod 644 public/contacto-config.php
```

## 🧪 Probar el formulario

### Debug mode

Para verificar la configuración sin enviar emails:

```
https://aplicacionweb.cl/contacto.php?debug=1
```

Esto mostrará la configuración actual en formato JSON.

## 📧 Configuración de email

El script usa la función `mail()` de PHP. Asegúrate de que tu servidor tenga configurado:

1. **Sendmail** o **Postfix** instalado
2. **SPF** y **DKIM** configurados para tu dominio
3. El email `FROM_EMAIL` debe ser del mismo dominio (contacto@aplicacionweb.cl)

### Alternativa: SMTP

Si tu hosting no soporta `mail()`, puedes usar PHPMailer con SMTP. Contacta a tu proveedor de hosting para obtener las credenciales SMTP.

## 🔒 Seguridad

El formulario incluye:

- ✅ **Honeypot** anti-spam (campo `bot-field`)
- ✅ **Validación de email** con `filter_var()`
- ✅ **Sanitización** de todos los inputs
- ✅ **Escape HTML** para prevenir XSS
- ✅ **Headers seguros** para prevenir inyección

## 📝 Campos del formulario

- **Nombre** (requerido)
- **Email** (requerido)
- **Teléfono** (requerido)
- **Rubro** (requerido)
- **Plan de interés** (opcional)
- **Mensaje** (opcional)

## 🎨 Respuestas del formulario

El script responde en formato JSON:

**Éxito:**
```json
{
  "success": true,
  "message": "¡Mensaje enviado exitosamente! Nos pondremos en contacto contigo en menos de 24 horas."
}
```

**Error:**
```json
{
  "success": false,
  "message": "No se pudo enviar el mensaje. Por favor intenta nuevamente o contáctanos por WhatsApp."
}
```

## 🔧 Troubleshooting

### El formulario no envía emails

1. Verifica que `mail()` funcione en tu servidor:
   ```php
   <?php
   $ok = mail('tu-email@ejemplo.com', 'Test', 'Mensaje de prueba');
   echo $ok ? 'OK' : 'ERROR';
   ```

2. Revisa los logs del servidor:
   ```bash
   tail -f /var/log/mail.log
   ```

3. Verifica la configuración SPF/DKIM de tu dominio

### Los emails llegan a spam

1. Configura **SPF** en tu DNS:
   ```
   v=spf1 a mx ~all
   ```

2. Configura **DKIM** (consulta con tu hosting)

3. Usa un email del mismo dominio como `FROM_EMAIL`

## 📞 Soporte

Si tienes problemas, contacta a tu proveedor de hosting para:
- Verificar que PHP mail() esté habilitado
- Obtener credenciales SMTP si es necesario
- Configurar SPF/DKIM

---

**Última actualización**: 24 de mayo, 2026
