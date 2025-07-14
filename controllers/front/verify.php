<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class EmailverificationVerifyModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();
        $this->display_column_right = false;
        $this->display_column_left = false;
    }

    public function initContent()
    {
        parent::initContent();

        $verification_code = Tools::getValue('code');
        $success = false;
        $message = '';
        $error = '';

        if (!$verification_code) {
            $error = $this->module->l('Código de verificación no válido', 'verify');
        } else {
            // Verificar el código
            if ($this->module->verifyCode($verification_code)) {
                $success = true;
                $message = $this->module->l('¡Tu cuenta ha sido verificada exitosamente! Ya puedes iniciar sesión.', 'verify');
            } else {
                $error = $this->module->l('El código de verificación no es válido o ha expirado. Por favor, solicita un nuevo código.', 'verify');
            }
        }

        $this->context->smarty->assign([
            'verification_success' => $success,
            'verification_message' => $message,
            'verification_error' => $error,
            'login_url' => $this->context->link->getPageLink('authentication'),
            'home_url' => $this->context->link->getPageLink('index')
        ]);

        $this->setTemplate('module:emailverification/views/templates/front/verify.tpl');
    }
}