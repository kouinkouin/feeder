<?php
/*
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Feeder extends Module
{
    private $_postErrors = [];

    public function __construct()
    {
        $this->name = 'feeder';
        $this->tab = 'front_office_features';
        $this->version = '0.7.3';
        $this->author = 'PrestaShop';
        $this->need_instance = 0;
        $this->bootstrap = true;

        $this->_directory = dirname(__FILE__) . '/../../';
        parent::__construct();

        $this->displayName = $this->l('RSS products feed');
        $this->description = $this->l('Generate a RSS feed for your latest products.');
    }

    function install()
    {
        return (parent::install() && $this->registerHook('header') && $this->initDefaultConfigurationValues());
    }

    function hookHeader($params)
    {
        if (!($id_category = (int)Tools::getValue('id_category'))) {
            if (isset($_SERVER['HTTP_REFERER']) && strstr($_SERVER['HTTP_REFERER'], Tools::getHttpHost()) && preg_match('!^(.*)\/([0-9]+)\-(.*[^\.])|(.*)id_category=([0-9]+)(.*)$!', $_SERVER['HTTP_REFERER'], $regs)) {
                if (isset($regs[2]) && is_numeric($regs[2])) {
                    $id_category = (int)($regs[2]);
                } elseif (isset($regs[5]) && is_numeric($regs[5])) {
                    $id_category = (int)$regs[5];
                }
            } elseif ($id_product = (int)Tools::getValue('id_product')) {
                $product = new Product($id_product);
                $id_category = $product->id_category_default;
            }
        }

        $orderBy = Tools::getProductsOrder('by', Tools::getValue('orderby'));
        $orderWay = Tools::getProductsOrder('way', Tools::getValue('orderway'));
        $this->smarty->assign([
            'feedUrl' => Tools::getShopDomainSsl(true, true) . _MODULE_DIR_ . $this->name . '/rss.php?id_category=' . $id_category . '&amp;orderby=' . $orderBy . '&amp;orderway=' . $orderWay,
        ]);
        return $this->display(__FILE__, 'feederHeader.tpl');
    }

    private function initDefaultConfigurationValues()
    {
        if (!Configuration::hasKey('FEEDER_INCLUDE_IMAGES')) {
            Configuration::updateValue('FEEDER_INCLUDE_IMAGES', 1);
        }

        return true;
    }

    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submit' . $this->name)) {
            $value = strval(Tools::getValue('FEEDER_INCLUDE_IMAGES'));
            Configuration::updateValue('FEEDER_INCLUDE_IMAGES', $value);
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }
        return $output . $this->displayForm();
    }

    public function displayForm()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fields_form = [
            0 => [
                'form' => [
                    'legend' => [
                        'title' => $this->l('Settings'),
                        'icon' => 'icon-cogs',
                    ],
                    'input' => [
                        [
                            'type' => 'switch',
                            'label' => $this->l('Include images'),
                            'name' => 'FEEDER_INCLUDE_IMAGES',
                            'desc' => $this->l('Include images in RSS feeds'),
                            'values' => [
                                [
                                    'id' => 'active_on',
                                    'value' => 1,
                                    'label' => $this->l('Enabled'),
                                ],
                                [
                                    'id' => 'active_off',
                                    'value' => 0,
                                    'label' => $this->l('Disabled'),
                                ],
                            ],
                        ],
                    ],
                    'submit' => [
                        'title' => $this->l('Save'),
                        'class' => 'btn btn-default pull-right',
                    ],
                ],
            ],
        ];

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = false;        // false -> remove toolbar
        $helper->submit_action = 'submit' . $this->name;

        // Load current value
        $helper->fields_value['FEEDER_INCLUDE_IMAGES'] = Configuration::get('FEEDER_INCLUDE_IMAGES');

        return $helper->generateForm($fields_form);
    }
}
