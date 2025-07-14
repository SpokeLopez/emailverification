# Módulo de Verificación de Email para PrestaShop 8.1

Este módulo añade un sistema de verificación de correo electrónico al proceso de registro de usuarios en PrestaShop 8.1. Los usuarios no podrán iniciar sesión hasta que verifiquen su email, y el correo de bienvenida estándar solo se enviará tras la verificación.

## Características

- Bloquea el acceso de usuarios no verificados.
- Envía un email de verificación personalizado tras el registro.
- El correo de bienvenida estándar se envía solo después de la verificación.
- Integración con el flujo de registro de vendedores.
- Compatible con PrestaShop 8.1.
- Fácil instalación y configuración.

## Instalación

1. **Clona o descarga este repositorio en la carpeta `modules/` de tu tienda PrestaShop:**

   ```bash
   cd modules
   git clone https://github.com/SpokeLopez/emailverification.git
   ```

2. **Instala el módulo desde el backoffice de PrestaShop:**
   - Ve a "Módulos > Administrador de módulos".
   - Busca "emailverification" y haz clic en "Instalar".

   **O instala desde consola:**

   ```bash
   php bin/console prestashop:module install emailverification
   ```

3. **Configura el módulo si es necesario desde el backoffice.**

## Uso

- Cuando un usuario se registra, recibirá un email con un enlace de verificación.
- Hasta que el usuario verifique su email, no podrá iniciar sesión.
- El correo de bienvenida estándar se enviará solo después de la verificación.
- El flujo de registro de vendedores también está integrado.

## Personalización

- Puedes personalizar la plantilla de email de verificación en la carpeta `mails/` del módulo.
- Si necesitas cambiar la expiración del código de verificación, ajusta la lógica en el archivo principal del módulo.

## Hooks utilizados

- `actionSubmitAccountAfter`
- `actionAuthentication`
- `actionEmailSendBefore`
- Otros hooks relevantes para el flujo de registro y autenticación.

## Desinstalación

- Puedes desinstalar el módulo desde el backoffice o usando:

  ```bash
  php bin/console prestashop:module uninstall emailverification
  ```

## Requisitos

- PrestaShop 8.1+
- Acceso a la base de datos para crear la tabla de verificación (el módulo lo hace automáticamente al instalarse).

---

## Soporte

Para reportar bugs o sugerencias, abre un issue en este repositorio.