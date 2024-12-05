<?php
// File: override/controllers/front/ProductController.php

class ProductControllerOverride extends ProductController
{
    public function initContent()
{
    parent::initContent();

    $id_manufacturer = (int)$this->product->id_manufacturer;

    $gpsrContent = Db::getInstance()->getValue('
        SELECT content FROM `' . _DB_PREFIX_ . 'gpsr_tab` 
        WHERE id_manufacturer = ' . $id_manufacturer . ' 
        AND enabled = 1
    ');

    // Przypisz zawartość GPSR do zmiennej Smarty, jeśli istnieje
    $this->context->smarty->assign('gpsrContent', $gpsrContent ?: '');

    // Tymczasowy komentarz do debugowania
    echo '<!-- GPSR Content: ' . htmlspecialchars($gpsrContent) . ' -->';
}

}
