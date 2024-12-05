<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class GpsrTabModule extends Module
{
    public function __construct()
    {
        $this->name = 'gpsrtabmodule';
        $this->tab = 'front_office_features';
        $this->version = '1.0.2';
        $this->author = 'JasspeR Development';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('GPSR Tab Module');
        $this->description = $this->l('Dodaje zakładkę GPSR na stronie produktu.');
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('displayProductTab') &&
            $this->registerHook('displayProductTabContent') &&
            $this->registerHook('displayHeader') && // Dla załadowania CSS
            $this->createDatabaseTable();
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->deleteDatabaseTable();
    }

    /**
     * Tworzy tabelę w bazie danych dla GPSR
     */
    private function createDatabaseTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "gpsr_tab` (
            `id_gpsr` INT(11) NOT NULL AUTO_INCREMENT,
            `id_manufacturer` INT(11) NOT NULL,
            `content` TEXT NOT NULL,
            `enabled` TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id_gpsr`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";
        return Db::getInstance()->execute($sql);
    }

    /**
     * Usuwa tabelę GPSR z bazy danych
     */
    private function deleteDatabaseTable()
    {
        $sql = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "gpsr_tab`;";
        return Db::getInstance()->execute($sql);
    }

    /**
     * Wyświetla zawartość modułu w panelu administracyjnym
     */
    public function getContent()
    {
        $output = '';
        $errors = [];

        // Obsługa aktualizacji ustawień modułu
        if (Tools::isSubmit('submitGpsrTabModule')) {
            $enabled = (int)Tools::getValue('GPSR_TAB_ENABLED');
            Configuration::updateValue('GPSR_TAB_ENABLED', $enabled);
            $output .= $this->displayConfirmation($this->l('Ustawienia zostały zaktualizowane.'));
        }

        // Obsługa dodawania nowego GPSR
        if (Tools::isSubmit('submitGpsrTabModuleAdd')) {
            $id_manufacturer = (int)Tools::getValue('manufacturer');
            $content = Tools::getValue('gpsrContent', true);
            $enabled = (int)Tools::getValue('gpsrEnabled');

            if (!$id_manufacturer || empty($content)) {
                $errors[] = $this->l('Proszę wypełnić wszystkie pola formularza.');
            } else {
                Db::getInstance()->insert('gpsr_tab', [
                    'id_manufacturer' => $id_manufacturer,
                    'content' => pSQL($content, true),
                    'enabled' => $enabled,
                ]);
                $output .= $this->displayConfirmation($this->l('Nowa pozycja GPSR została dodana.'));
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->name . '&conf=4');
            }
        }

        // Obsługa edycji istniejącego GPSR
        if (Tools::isSubmit('submitGpsrTabModuleEdit')) {
            $id_gpsr = (int)Tools::getValue('id_gpsr');
            $id_manufacturer = (int)Tools::getValue('manufacturer');
            $content = Tools::getValue('gpsrContent', true);
            $enabled = (int)Tools::getValue('gpsrEnabled');

            if (!$id_manufacturer || empty($content)) {
                $errors[] = $this->l('Proszę wypełnić wszystkie pola formularza.');
            } else {
                Db::getInstance()->update('gpsr_tab', [
                    'id_manufacturer' => $id_manufacturer,
                    'content' => pSQL($content, true),
                    'enabled' => $enabled,
                ], 'id_gpsr = ' . (int)$id_gpsr);
                $output .= $this->displayConfirmation($this->l('Pozycja GPSR została zaktualizowana.'));
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->name . '&conf=4');
            }
        }

        // Obsługa usuwania GPSR
        if (Tools::isSubmit('deletegpsr_tab')) {
            $id_gpsr = (int)Tools::getValue('id_gpsr');
            Db::getInstance()->delete('gpsr_tab', 'id_gpsr = ' . $id_gpsr);
            $output .= $this->displayConfirmation($this->l('Pozycja GPSR została usunięta.'));
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->name . '&conf=1');
        }

        // Obsługa zmiany statusu GPSR
        if (Tools::isSubmit('statusgpsr_tab')) {
            $id_gpsr = (int)Tools::getValue('id_gpsr');
            $gpsr = Db::getInstance()->getRow('SELECT `enabled` FROM `' . _DB_PREFIX_ . 'gpsr_tab` WHERE `id_gpsr` = ' . (int)$id_gpsr);
            $enabled = !$gpsr['enabled'];
            Db::getInstance()->update('gpsr_tab', ['enabled' => (int)$enabled], 'id_gpsr = ' . $id_gpsr);
            $output .= $this->displayConfirmation($this->l('Status został zaktualizowany.'));
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->name . '&conf=4');
        }
        

        // Obsługa wyświetlania formularza edycji GPSR
        if (Tools::isSubmit('updategpsr_tab')) {
            $output .= $this->renderEditGpsrForm((int)Tools::getValue('id_gpsr'));
            return $output;
        }

        // Wyświetlanie błędów
        if (count($errors)) {
            foreach ($errors as $error) {
                $output .= $this->displayError($error);
            }
        }

        // Wyświetlanie formularzy i listy GPSR
        return $output . $this->renderForm() . $this->renderAddGpsrForm() . $this->renderGpsrList();
    }

    /**
     * Renderuje formularz ustawień modułu
     */
    public function renderForm()
    {
        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Ustawienia GPSR Tab Module'),
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Włącz GPSR Tab'),
                        'name' => 'GPSR_TAB_ENABLED',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'enabled',
                                'value' => 1,
                                'label' => $this->l('Tak')
                            ],
                            [
                                'id' => 'disabled',
                                'value' => 0,
                                'label' => $this->l('Nie')
                            ]
                        ]
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Zapisz'),
                    'name' => 'submitGpsrTabModule',
                ],
            ]
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitGpsrTabModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->fields_value['GPSR_TAB_ENABLED'] = Configuration::get('GPSR_TAB_ENABLED');

        return $helper->generateForm([$fieldsForm]);
    }

    /**
     * Renderuje formularz dodawania nowego GPSR
     */
    public function renderAddGpsrForm()
    {
        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Dodaj nowy GPSR'),
                    'icon' => 'icon-plus'
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->l('Wybierz producenta'),
                        'name' => 'manufacturer',
                        'options' => [
                            'query' => Manufacturer::getManufacturers(false, $this->context->language->id),
                            'id' => 'id_manufacturer',
                            'name' => 'name'
                        ],
                        'required' => true,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Treść HTML'),
                        'name' => 'gpsrContent',
                        'autoload_rte' => true,
                        'cols' => 50,
                        'rows' => 10,
                        'required' => true,
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Włącz zakładkę GPSR'),
                        'name' => 'gpsrEnabled',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'enabled',
                                'value' => 1,
                                'label' => $this->l('Tak')
                            ],
                            [
                                'id' => 'disabled',
                                'value' => 0,
                                'label' => $this->l('Nie')
                            ]
                        ]
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Dodaj'),
                    'name' => 'submitGpsrTabModuleAdd',
                ],
            ]
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitGpsrTabModuleAdd';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        return $helper->generateForm([$fieldsForm]);
    }

    /**
     * Renderuje formularz edycji istniejącego GPSR
     */
    public function renderEditGpsrForm($id_gpsr)
    {
        $gpsr = Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'gpsr_tab` WHERE id_gpsr = ' . (int)$id_gpsr);

        if (!$gpsr) {
            return $this->displayError($this->l('Nie znaleziono GPSR.'));
        }

        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Edytuj GPSR'),
                    'icon' => 'icon-edit'
                ],
                'input' => [
                    [
                        'type' => 'hidden',
                        'name' => 'id_gpsr',
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Wybierz producenta'),
                        'name' => 'manufacturer',
                        'options' => [
                            'query' => Manufacturer::getManufacturers(false, $this->context->language->id),
                            'id' => 'id_manufacturer',
                            'name' => 'name'
                        ],
                        'required' => true,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Treść HTML'),
                        'name' => 'gpsrContent',
                        'autoload_rte' => true,
                        'cols' => 50,
                        'rows' => 10,
                        'required' => true,
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Włącz zakładkę GPSR'),
                        'name' => 'gpsrEnabled',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'enabled',
                                'value' => 1,
                                'label' => $this->l('Tak')
                            ],
                            [
                                'id' => 'disabled',
                                'value' => 0,
                                'label' => $this->l('Nie')
                            ]
                        ]
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Zapisz'),
                    'name' => 'submitGpsrTabModuleEdit',
                ],
            ]
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitGpsrTabModuleEdit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        // Ustawienie wartości pól formularza
        $helper->fields_value = [
            'id_gpsr' => $gpsr['id_gpsr'],
            'manufacturer' => $gpsr['id_manufacturer'],
            'gpsrContent' => $gpsr['content'],
            'gpsrEnabled' => $gpsr['enabled'],
        ];

        return $helper->generateForm([$fieldsForm]);
    }

    /**
     * Renderuje listę GPSR w panelu administracyjnym
     */
    public function renderGpsrList()
    {
        $gpsrData = Db::getInstance()->executeS('
            SELECT g.*, m.name as manufacturer_name 
            FROM `' . _DB_PREFIX_ . 'gpsr_tab` g 
            LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON g.id_manufacturer = m.id_manufacturer
        ');

        // Przygotowanie danych do listy
        foreach ($gpsrData as &$gpsr) {
            $gpsr['enabled'] = $gpsr['enabled'] ? $this->l('Tak') : $this->l('Nie');
        }

        $fields_list = [
            'id_gpsr' => [
                'title' => $this->l('ID'),
                'type' => 'text',
            ],
            'manufacturer_name' => [
                'title' => $this->l('Producent'),
                'type' => 'text',
            ],
            'enabled' => [
                'title' => $this->l('Włączony'),
                'type' => 'bool',
                'active' => 'status',
                'ajax' => true,
                'orderby' => false,
            ],
        ];

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->actions = ['edit', 'delete'];
        $helper->identifier = 'id_gpsr';
        $helper->show_toolbar = false;
        $helper->title = $this->l('Lista GPSR');
        $helper->table = 'gpsr_tab';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name;

        return $helper->generateList($gpsrData, $fields_list);
    }

    public function ajaxProcessChangeStatus()
{
    // Pobierz dane z formularza (id_gpsr i enabled)
    $id_gpsr = (int)Tools::getValue('id_gpsr');
    $enabled = (int)Tools::getValue('enabled');

    // Zaktualizuj status w bazie danych
    $update = Db::getInstance()->update('gpsr_tab', [
        'enabled' => $enabled,
    ], 'id_gpsr = ' . (int)$id_gpsr);

    // Sprawdź, czy aktualizacja się powiodła
    if ($update) {
        // Jeśli tak, zwróć sukces
        die(json_encode(['success' => true, 'new_status' => $enabled]));
    } else {
        // Jeśli nie, zwróć błąd
        die(json_encode(['success' => false]));
    }
}



    /**
     * Hook do wyświetlania zakładki GPSR
     */
    public function hookDisplayProductTab($params)
{
    if (!Configuration::get('GPSR_TAB_ENABLED')) {
        return '';
    }

    /** @var Product $product */
    $product = $params['product'];
    $manufacturerId = (int)$product->id_manufacturer;

    $gpsrContent = Db::getInstance()->getRow('
        SELECT * FROM `' . _DB_PREFIX_ . 'gpsr_tab` 
        WHERE id_manufacturer = ' . $manufacturerId . ' 
        AND enabled = 1
    ');

    if (!$gpsrContent) {
        return '';
    }

    $this->context->smarty->assign([
        'gpsrTitle' => $this->l('GPSR'),
    ]);

    // Dodanie testowego komentarza
    $testText = '<!-- GPSR Tab Hook Called -->';

    return $this->display(__FILE__, 'views/templates/front/gpsr_tab.tpl') . $testText;
}

public function hookDisplayProductTabContent($params)
{
    if (!Configuration::get('GPSR_TAB_ENABLED')) {
        return '';
    }

    /** @var Product $product */
    $product = $params['product'];
    $manufacturerId = (int)$product->id_manufacturer;

    $gpsrContent = Db::getInstance()->getRow('
        SELECT * FROM `' . _DB_PREFIX_ . 'gpsr_tab` 
        WHERE id_manufacturer = ' . $manufacturerId . ' 
        AND enabled = 1
    ');

    if (!$gpsrContent) {
        return '';
    }

    $this->context->smarty->assign([
        'gpsrContent' => $gpsrContent['content'],
    ]);

    // Dodanie testowego komentarza
    $testText = '<!-- GPSR Content Hook Called -->';

    return $this->display(__FILE__, 'views/templates/front/gpsr_content.tpl') . $testText;
}



    /**
     * Hook do wyświetlania nagłówka (ładowanie CSS)
     */
    public function hookDisplayHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/gpsrtabmodule.css', 'all');
    }
}
