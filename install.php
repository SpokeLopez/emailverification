<?php
/**
 * Script de instalación para el módulo Emailverification
 */

// Verificar que estamos en PrestaShop
if (!defined('_PS_VERSION_')) {
    exit;
}

// Instalar el módulo
$module = new Emailverification();

if ($module->install()) {
    echo "✅ Módulo Emailverification instalado correctamente\n";
    echo "📧 Sistema de verificación de email activado\n";
    echo "🔗 Los nuevos usuarios recibirán un email de verificación\n";
    echo "⏰ Los enlaces expiran en 1 hora\n";
} else {
    echo "❌ Error al instalar el módulo Emailverification\n";
}