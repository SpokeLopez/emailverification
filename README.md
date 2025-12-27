# 📧 Email Verification Module for PrestaShop

**Módulo de verificación de correo electrónico para PrestaShop 8.x con sistema de validación obligatoria**

[![PrestaShop](https://img.shields.io/badge/PrestaShop-8.x-FF6900.svg)](https://www.prestashop.com)
[![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-Proprietary-red.svg)]()

## 📝 Descripción

Este módulo de PrestaShop proporciona un sistema robusto de verificación de correos electrónicos obligatoria para nuevos registros de usuarios. Los usuarios deben verificar su dirección de email antes de poder acceder a su cuenta, mejorando la seguridad y la calidad de la base de datos de clientes.

## ✨ Características

### 🔐 Verificación Obligatoria
- **Bloqueo de acceso**: Los usuarios no verificados no pueden iniciar sesión
- **Email de verificación**: Envío automático de enlaces de verificación
- **Control de bienvenida**: El email de bienvenida se envía solo tras la verificación
- **Gestión de expiración**: Los enlaces de verificación tienen tiempo límite

### 📧 Gestión de Correos
- **Templates personalizados**: Emails de verificación completamente personalizables
- **Soporte multiidioma**: Compatible con español e inglés
- **HTML y texto plano**: Formatos múltiples para máxima compatibilidad
- **Integración nativa**: Usa el sistema de emails de PrestaShop

### 🛡️ Seguridad
- **Tokens únicos**: Cada verificación usa un token seguro y único
- **Protección temporal**: Los enlaces expiran para evitar abuso
- **Validación robusta**: Verificación completa del proceso
- **Prevención de spam**: Control de cuentas no verificadas

## 🏗️ Estructura del Proyecto

```
emailverification/
├── config_mx.xml                # Configuración específica para México
├── emailverification.php        # Clase principal del módulo
├── install.php                  # Script de instalación
├── LICENSE                      # Licencia del proyecto
├── README.md                    # Este archivo
├── controllers/
│   └── front/
│       └── verify.php           # Controlador de verificación
├── mails/
│   ├── en/                      # Templates en inglés
│   │   ├── account_verified.html
│   │   ├── account_verified.txt
│   │   ├── email_verification.html
│   │   └── email_verification.txt
│   └── es/                      # Templates en español
│       ├── account_verified.html
│       ├── account_verified.txt
│       ├── email_verification.html
│       └── email_verification.txt
└── views/
    └── templates/
        └── front/
            └── verify.tpl       # Template de verificación
```

## ⚙️ Instalación

### Requisitos
- PrestaShop 8.x
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Acceso de escritura a la base de datos

### Pasos de instalación

1. **Descarga el módulo**
   ```bash
   git clone https://github.com/SpokeLopez/emailverification.git
   cd emailverification
   ```

2. **Sube a PrestaShop**
   - Comprime la carpeta del módulo en ZIP
   - Ve a Back Office → Módulos → Subir módulo
   - Selecciona el archivo ZIP y sube

3. **Instala el módulo**
   - Busca "Email Verification" en la lista de módulos
   - Haz clic en "Instalar"
   - El módulo creará automáticamente las tablas necesarias

4. **Instalación por consola** (alternativa)
   ```bash
   php bin/console prestashop:module install emailverification
   ```

## 🚀 Uso

### Flujo de Verificación

El módulo intercepta el proceso de registro y maneja la verificación:

```php
// Flujo típico de verificación
1. Usuario se registra → Cuenta creada pero inactiva
2. Email de verificación enviado → Con token único
3. Usuario hace clic en enlace → Verificación procesada
4. Cuenta activada → Email de bienvenida enviado
```

### URLs del Módulo

- **Verificación**: `index.php?fc=module&module=emailverification&controller=verify&token={TOKEN}`

### Proceso de Verificación

1. **Registro de usuario**
   - Cuenta creada con estado "no verificado"
   - Email de verificación enviado automáticamente

2. **Email de verificación**
   - Contiene enlace único con token seguro
   - Válido por tiempo limitado (configurable)

3. **Verificación exitosa**
   - Usuario puede iniciar sesión normalmente
   - Email de bienvenida enviado

## 🎯 Características Técnicas

### Hooks Registrados
- `actionCustomerAccountAdd`: Se ejecuta al crear nueva cuenta
- `actionAuthentication`: Verifica estado antes del login
- `actionEmailSendBefore`: Controla envío de emails de bienvenida
- `displayHeader`: Inyecta recursos necesarios

### Base de Datos
El módulo crea y gestiona:
- Tabla de verificaciones con tokens únicos
- Campo de estado de verificación en perfil de usuario
- Logs de intentos de verificación

### Gestión de Tokens
```php
// Ejemplo de generación de token
$token = md5(uniqid($customer->email, true));
$verification_link = $this->context->link->getModuleLink(
    'emailverification',
    'verify',
    ['token' => $token]
);
```

## 🎨 Personalización

### Templates de Email
Los templates están ubicados en `mails/`:
- **HTML**: Para clientes de email modernos
- **Texto plano**: Para máxima compatibilidad
- **Variables Smarty**: Para personalización dinámica

### Configuración
El módulo permite configurar:
- Tiempo de expiración de tokens
- Textos de los emails
- Comportamiento del módulo
- Mensajes de error y éxito

## 🌐 Internacionalización

El módulo soporta múltiples idiomas:
- **Español**: Idioma principal
- **Inglés**: Incluido por defecto
- Sistema completo de traducciones de PrestaShop

```php
// Ejemplo de uso de traducciones
$this->l('Su cuenta ha sido verificada exitosamente')
```

## 🔒 Seguridad

### Medidas Implementadas
- **Tokens únicos**: SHA256 para cada verificación
- **Expiración**: Los enlaces caducan automáticamente
- **Validación estricta**: Verificación completa de tokens
- **Prevención de ataques**: Protección contra fuerza bruta

### Protección de Archivos
- Headers de seguridad en todos los archivos
- Validación de entrada en controladores
- Escape de output en templates

## 📊 Funcionalidades Avanzadas

### Gestión de Estados
- **Pendiente**: Usuario registrado, sin verificar
- **Verificado**: Usuario puede acceder normalmente
- **Expirado**: Token caducado, requiere reenvío

### Reenvío de Verificaciones
- Opción de reenviar email de verificación
- Límites de frecuencia para prevenir spam
- Nuevos tokens para cada reenvío

## 🧪 Testing

### Casos de Uso
- ✅ Registro normal con verificación exitosa
- ✅ Intento de login sin verificar
- ✅ Token expirado o inválido
- ✅ Reenvío de email de verificación
- ✅ Verificación en diferentes idiomas

### URLs de Prueba
1. Registra un usuario nuevo
2. Verifica que no puede hacer login
3. Revisa el email de verificación
4. Usa el enlace de verificación
5. Confirma acceso normal tras verificación

## 🚀 Roadmap

### Funcionalidades Planeadas
- [ ] Panel de administración para gestionar verificaciones
- [ ] Estadísticas de conversión de verificaciones
- [ ] Integración con SMS para doble verificación
- [ ] API REST para verificaciones externas
- [ ] Verificación por código numérico

### Mejoras Técnicas
- [ ] Cache de verificaciones frecuentes
- [ ] Logs detallados de actividad
- [ ] Optimización de consultas SQL
- [ ] Integración con servicios de email externos

## 👥 Contribuir

### Proceso de Contribución
1. Fork del proyecto
2. Crea una rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit de cambios (`git commit -m 'Agrega nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abre un Pull Request

### Estándares de Código
- PSR-4 para namespaces
- Documentación en español
- Tests para nuevas funcionalidades
- Compatibilidad con PrestaShop 8.x

## 📚 Documentación Adicional

### Referencias Útiles
- [Documentación PrestaShop](https://devdocs.prestashop.com/)
- [Hooks de PrestaShop](https://devdocs.prestashop.com/8/modules/concepts/hooks/)
- [Sistema de Emails PrestaShop](https://devdocs.prestashop.com/8/development/components/mail/)

### Ejemplos de Implementación
El módulo incluye ejemplos completos de:
- Controladores personalizados
- Templates Smarty
- Gestión de emails multiidioma

## 🐛 Reportar Problemas

Si encuentras algún bug o tienes sugerencias:

1. Verifica que no esté ya reportado en GitHub
2. Incluye información del entorno (PrestaShop, PHP, etc.)
3. Pasos detallados para reproducir el problema
4. Comportamiento esperado vs comportamiento actual

## 📄 Licencia

Este proyecto es de uso propietario. Todos los derechos reservados.

## 🏆 Créditos

Desarrollado por Eduardo López Barrientos para HAMO.MX
- Diseñado específicamente para marketplace de artesanos mexicanos
- Optimizado para PrestaShop 8.x
- Integrado con ETS Marketplace module

---

**⭐ Si este proyecto te resulta útil, considera darle una estrella en GitHub y compartirlo en LinkedIn**

---

*Última actualización: Diciembre 2024*
*Versión: 1.0.0*
*Compatible con: PrestaShop 8.x*