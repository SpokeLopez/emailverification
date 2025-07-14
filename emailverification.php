<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Emailverification extends Module
{
    public function __construct()
    {
        $this->name = 'emailverification';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Eduardo López Barrientos';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Email Verification');
        $this->description = $this->l('Sistema de verificación de email para nuevos registros');
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('actionCustomerAccountAdd') &&
            $this->registerHook('actionCustomerAccountUpdate') &&
            $this->registerHook('displayCustomerAccountForm') &&
            $this->registerHook('actionEmailSendBefore') &&
            $this->registerHook('actionAuthentication') &&
            $this->createVerificationTable();
    }

    public function uninstall()
    {
        return parent::uninstall() &&
            $this->dropVerificationTable();
    }

    private function createVerificationTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "email_verification` (
            `id_verification` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `id_customer` INT UNSIGNED NOT NULL,
            `verification_code` VARCHAR(64) NOT NULL,
            `email` VARCHAR(255) NOT NULL,
            `is_verified` TINYINT(1) DEFAULT 0,
            `date_add` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `date_expires` DATETIME NOT NULL,
            `date_verified` DATETIME NULL,
            UNIQUE KEY `verification_code` (`verification_code`),
            FOREIGN KEY (`id_customer`) REFERENCES `" . _DB_PREFIX_ . "customer`(`id_customer`) ON DELETE CASCADE
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8mb4;";

        return Db::getInstance()->execute($sql);
    }

    private function dropVerificationTable()
    {
        $sql = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "email_verification`";
        return Db::getInstance()->execute($sql);
    }

    /**
     * Hook cuando se crea una nueva cuenta de cliente
     */
    public function hookActionCustomerAccountAdd($params)
    {
        if (!isset($params['newCustomer'])) {
            return;
        }
        $customer = $params['newCustomer'];
        if (!Validate::isLoadedObject($customer)) {
            return;
        }

        // Crear código de verificación
        $verification_code = $this->generateVerificationCode();
        // Usar función de MySQL para manejar correctamente las zonas horarias
        $date_expires = Db::getInstance()->getValue('SELECT DATE_ADD(NOW(), INTERVAL 1 HOUR)');

        // Insertar registro de verificación
        $result = Db::getInstance()->insert('email_verification', [
            'id_customer' => (int)$customer->id,
            'verification_code' => pSQL($verification_code),
            'email' => pSQL($customer->email),
            'date_expires' => pSQL($date_expires)
        ]);

        if ($result) {
            // Desactivar la cuenta temporalmente
            $customer->active = 0;
            $customer->update();
            // Enviar email de verificación
            $this->sendVerificationEmail($customer, $verification_code);
            // Limpiar códigos expirados
            $this->cleanExpiredCodes();
            // Mostrar mensaje al usuario
            if (isset($this->context->controller)) {
                $this->context->controller->errors[] = $this->l('¡Tu cuenta ha sido creada! Por favor revisa tu correo electrónico para activar tu cuenta.');
            }
        }
    }

    /**
     * Hook cuando se actualiza una cuenta de cliente
     */
    public function hookActionCustomerAccountUpdate($params)
    {
        $customer = $params['customer'];

        if (!Validate::isLoadedObject($customer)) {
            return;
        }

        // Si el email cambió, crear nueva verificación
        $existing_verification = Db::getInstance()->getRow('
            SELECT * FROM ' . _DB_PREFIX_ . 'email_verification
            WHERE id_customer = ' . (int)$customer->id . '
            AND is_verified = 0
        ');

        if ($existing_verification && $existing_verification['email'] !== $customer->email) {
            // Actualizar email en verificación existente
            Db::getInstance()->update('email_verification', [
                'email' => pSQL($customer->email),
                'date_expires' => pSQL(Db::getInstance()->getValue('SELECT DATE_ADD(NOW(), INTERVAL 1 HOUR)'))
            ], 'id_verification = ' . (int)$existing_verification['id_verification']);

            // Enviar nuevo email de verificación
            $this->sendVerificationEmail($customer, $existing_verification['verification_code']);
        }
    }

    /**
     * Hook para interceptar el login y verificar si la cuenta está activa
     */
    public function hookActionAuthentication($params)
    {
        $customer = $params['customer'];

        if (!Validate::isLoadedObject($customer)) {
            return;
        }

        // Verificar si la cuenta está activa
        if (!$customer->active) {
            // Verificar si tiene una verificación pendiente
            $verification = Db::getInstance()->getRow('
                SELECT * FROM ' . _DB_PREFIX_ . 'email_verification
                WHERE id_customer = ' . (int)$customer->id . '
                AND is_verified = 0
                AND date_expires > NOW()
            ');

            if ($verification) {
                // Enviar nuevo email de verificación
                $this->sendVerificationEmail($customer, $verification['verification_code']);
                // Mostrar mensaje de error
                $this->context->controller->errors[] = $this->l('Tu cuenta no está verificada. Hemos enviado un nuevo email de verificación a tu dirección de correo.');
            } else {
                $this->context->controller->errors[] = $this->l('Tu cuenta no está activa. Por favor, contacta con el administrador.');
            }

            // Prevenir el login
            $this->context->controller->errors[] = $this->l('No puedes iniciar sesión hasta verificar tu cuenta.');
        }
    }

    /**
     * Hook para interceptar el registro antes de la autenticación
     */
    public function hookActionBeforeSubmitAccount($params)
    {
        if (isset($params['newCustomer']) && Validate::isLoadedObject($params['newCustomer'])) {
            $customer = $params['newCustomer'];
            // Evita duplicados
            $exists = Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'email_verification WHERE id_customer = ' . (int)$customer->id);
            if (!$exists) {
                $verification_code = $this->generateVerificationCode();
                // Usar función de MySQL para manejar correctamente las zonas horarias
                $date_expires = Db::getInstance()->getValue('SELECT DATE_ADD(NOW(), INTERVAL 1 HOUR)');

                Db::getInstance()->insert('email_verification', [
                    'id_customer' => (int)$customer->id,
                    'verification_code' => pSQL($verification_code),
                    'email' => pSQL($customer->email),
                    'date_expires' => pSQL($date_expires)
                ]);

                $customer->active = 0;
                $customer->update();

                $this->sendVerificationEmail($customer, $verification_code);
                $this->cleanExpiredCodes();
            }
        }
    }

    /**
     * Hook para bloquear el correo de bienvenida estándar hasta que el usuario valide su email
     */
    public function hookActionEmailSendBefore($params)
    {
        // Bloquea el correo de bienvenida estándar si el usuario no está verificado
        if (isset($params['template']) && $params['template'] === 'account') {
            if (isset($params['to']) && is_array($params['to']) && count($params['to']) > 0) {
                $email = $params['to'][0];
                $customer = new Customer();
                $customer = $customer->getByEmail($email);
                if ($customer && Validate::isLoadedObject($customer)) {
                    $is_verified = Emailverification::isCustomerVerified($customer->id);
                    if (!$is_verified) {
                        // Cancelar el envío del correo de bienvenida
                        $params['prevent_send'] = true;
                    }
                }
            }
        }
    }

    /**
     * Generar código de verificación único
     */
    private function generateVerificationCode()
    {
        do {
            $code = bin2hex(random_bytes(32));
            $exists = Db::getInstance()->getValue('
                SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'email_verification
                WHERE verification_code = "' . pSQL($code) . '"
            ');
        } while ($exists > 0);

        return $code;
    }

    /**
     * Enviar email de verificación
     */
    private function sendVerificationEmail($customer, $verification_code)
    {
        $verification_url = $this->context->link->getModuleLink(
            'emailverification',
            'verify',
            ['code' => $verification_code]
        );

        $templateVars = [
            '{CUSTOMER_FIRSTNAME}' => $customer->firstname,
            '{CUSTOMER_LASTNAME}' => $customer->lastname,
            '{VERIFICATION_LINK}' => $verification_url,
            '{SHOP_NAME}' => Configuration::get('PS_SHOP_NAME'),
            '{EXPIRES_IN}' => '1 hora'
        ];

        return Mail::Send(
            (int)Configuration::get('PS_LANG_DEFAULT'),
            'email_verification',
            'Verifica tu cuenta de email',
            $templateVars,
            $customer->email,
            $customer->firstname . ' ' . $customer->lastname,
            null,
            null,
            null,
            null,
            _PS_MODULE_DIR_ . $this->name . '/mails/'
        );
    }

    /**
     * Limpiar códigos expirados
     */
    private function cleanExpiredCodes()
    {
        // Agregar un margen de 5 minutos para evitar eliminar registros recién insertados
        return Db::getInstance()->execute('
            DELETE FROM ' . _DB_PREFIX_ . 'email_verification
            WHERE date_expires < DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND is_verified = 0
        ');
    }

    /**
     * Verificar código de verificación
     */
    public function verifyCode($verification_code)
    {
        $verification = Db::getInstance()->getRow('
            SELECT * FROM ' . _DB_PREFIX_ . 'email_verification
            WHERE verification_code = "' . pSQL($verification_code) . '"
            AND is_verified = 0
            AND date_expires > NOW()
        ');

        if (!$verification) {
            return false;
        }

        // Marcar como verificado
        Db::getInstance()->update('email_verification', [
            'is_verified' => 1,
            'date_verified' => date('Y-m-d H:i:s')
        ], 'id_verification = ' . (int)$verification['id_verification']);

        // Activar la cuenta del cliente
        $customer = new Customer((int)$verification['id_customer']);
        if (Validate::isLoadedObject($customer)) {
            $customer->active = 1;
            $customer->update();

            // Enviar email de bienvenida
            $this->sendWelcomeEmail($customer);

            return true;
        }

        return false;
    }

    /**
     * Enviar email de bienvenida tras verificación
     */
    private function sendWelcomeEmail($customer)
    {
        $templateVars = [
            '{CUSTOMER_FIRSTNAME}' => $customer->firstname,
            '{CUSTOMER_LASTNAME}' => $customer->lastname,
            '{SHOP_NAME}' => Configuration::get('PS_SHOP_NAME'),
            '{ACCOUNT_LINK}' => $this->context->link->getPageLink('my-account')
        ];

        return Mail::Send(
            (int)Configuration::get('PS_LANG_DEFAULT'),
            'account_verified',
            'Tu cuenta ha sido verificada exitosamente',
            $templateVars,
            $customer->email,
            $customer->firstname . ' ' . $customer->lastname,
            null,
            null,
            null,
            null,
            _PS_MODULE_DIR_ . $this->name . '/mails/'
        );
    }

    /**
     * Verificar si un cliente está verificado
     */
    public static function isCustomerVerified($id_customer)
    {
        return (bool)Db::getInstance()->getValue('
            SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'email_verification
            WHERE id_customer = ' . (int)$id_customer . '
            AND is_verified = 1
        ');
    }

    /**
     * Obtener estado de verificación de un cliente
     */
    public static function getCustomerVerificationStatus($id_customer)
    {
        return Db::getInstance()->getRow('
            SELECT * FROM ' . _DB_PREFIX_ . 'email_verification
            WHERE id_customer = ' . (int)$id_customer . '
            ORDER BY id_verification DESC
        ');
    }

    // Prepara el método para el hook actionSubmitAccountBefore
    public function hookActionSubmitAccountBefore($params)
    {
        // Aquí puedes replicar la lógica de verificación si este hook es el que se ejecuta
    }

    public function hookActionSubmitAccountAfter($params)
    {
        if (!isset($params['customer']) || !Validate::isLoadedObject($params['customer'])) {
            return;
        }
        $customer = $params['customer'];

        // Crear código de verificación
        $verification_code = $this->generateVerificationCode();
        // Usar función de MySQL para manejar correctamente las zonas horarias
        $date_expires = Db::getInstance()->getValue('SELECT DATE_ADD(NOW(), INTERVAL 1 HOUR)');

        // Insertar registro de verificación
        $result = Db::getInstance()->insert('email_verification', [
            'id_customer' => (int)$customer->id,
            'verification_code' => pSQL($verification_code),
            'email' => pSQL($customer->email),
            'date_expires' => pSQL($date_expires)
        ]);

        if ($result) {
            // Desactivar la cuenta temporalmente
            $customer->active = 0;
            $customer->update();
            // Enviar email de verificación
            $this->sendVerificationEmail($customer, $verification_code);
            // Limpiar códigos expirados
            $this->cleanExpiredCodes();

            // Mostrar mensaje al usuario
            if (isset($this->context->controller)) {
                $this->context->controller->errors[] = $this->l('¡Tu cuenta ha sido creada! Por favor revisa tu correo electrónico para activar tu cuenta.');
            }
        }
    }
}