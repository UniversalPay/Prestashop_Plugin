<?php
/**
 * UniversalPay module
 *
 * @author    UniversalPay
 * @copyright Copyright (c) 2018 UniversalPay
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

include_once _PS_MODULE_DIR_ . '/universalpay/tools/sdk/payments.php';
include_once _PS_MODULE_DIR_ . '/universalpay/translations/statuses.php';

class UniversalPay extends PaymentModule
{
    //blank space is required for prestashop 1.6.
    //cannot be set to NULL or ''.
    const EVO_PAYMENT_SOLUTION = ' ';
    const IFRAME_PAYMENT_SOLUTION = '500';
    
    const MAP_STATUSES = [
        'success'    => 'EVO_STATUS_SUCCESS',
        'failure'    => 'EVO_STATUS_ERROR',
        'cancel'     => 'EVO_STATUS_CANCELED',
        'inprogress' => 'EVO_STATUS_INPROGRESS',
        'refunded'   => 'EVO_STATUS_REFUNDED'
    ];

    public $cart = null;
    public $id_cart = null;
    public $order = null;
    public $id_order = null;
    public $token = null;
    public $ssl = true;

    /**
     * begin - switching constants which will be used to hide or show the UI controls 
     * 
     ***/
    const ST_SHOW_IFRAME = "1";
    const ST_SHOW_REDIRECT = "1";
    const ST_SHOW_HOSTEDPAY = "1";
    
    const ST_SHOW_SANDBOX_FIELDS = "0";
    const ST_SHOW_LIVE_FIELDS = "0";
    
    const ST_EVO_CASHIER_URL_SANDBOX = "https://cashierui.test.universalpay.es/ui/cashier";
    const ST_EVO_JAVASCRIPT_URL_SANDBOX = "https://cashierui.test.universalpay.es/js/api.js";
    const ST_EVO_TOKEN_URL_SANDBOX = "https://api.test.universalpay.es/token";
    const ST_EVO_PAYMENT_URL_SANDBOX = "https://api.test.universalpay.es/payments";
   
    const ST_EVO_CASHIER_URL_LIVE = "https://cashierui.universalpay.es/ui/cashier";
    const ST_EVO_JAVASCRIPT_URL_LIVE = "https://cashierui.universalpay.es/js/api.js";
    const ST_EVO_TOKEN_URL_LIVE = "https://api.universalpay.es/token";
    const ST_EVO_PAYMENT_URL_LIVE = "https://api.universalpay.es/payments";
    //DEFAULT TO IFRAME
    // 1 - IFRAME
    // 0 - REDIRECT
    // 2 - HOSTED PAY
    const ST_EVO_PAYMENT_TYPE = "1";
    /** end**/

	private $payments = null;
    
    public function __construct()
    {
        $this->name = 'universalpay';
        $this->displayName = 'UniversalPay';
        $this->tab = 'payments_gateways';
        $this->version = '1.1.0';
        $this->author = 'UniversalPay';
        $this->need_instance = 1;
        $this->bootstrap = true;
        $this->ssl=true;
        $this->ps_versions_compliancy = ['min' => '1.6.0', 'max' => '1.7'];

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->is_eu_compatible = 1;

        parent::__construct();

        $this->displayName = $this->l('UniversalPay');
        $this->description = $this->l('Accepts payments by UniversalPay');

        $this->confirm_uninstall = $this->l('Are you sure you want to uninstall?');
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     */
    public function install()
    {
        $statusesTranslation = new StatusTranslation\Statuses();

		Configuration::updateValue('EVO_SANDBOX', 0);
		Configuration::updateValue('EVO_MERCHANT_ID', '');
		Configuration::updateValue('EVO_PASSWORD', '');
		Configuration::updateValue('EVO_BRANDID', '');
		Configuration::updateValue('EVO_PAYMENT_TYPE', self::ST_EVO_PAYMENT_TYPE);
		Configuration::updateValue('EVO_PAYMENT_SOLUTION', self::EVO_PAYMENT_SOLUTION);
		Configuration::updateValue('EVO_REFUND_POSSIBILITY', 1);
		// url for sandbox
		Configuration::updateValue('EVO_CASHIER_URL_SANDBOX', self::ST_EVO_CASHIER_URL_SANDBOX);
		Configuration::updateValue('EVO_JAVASCRIPT_URL_SANDBOX', self::ST_EVO_JAVASCRIPT_URL_SANDBOX);
		Configuration::updateValue('EVO_TOKEN_URL_SANDBOX', self::ST_EVO_TOKEN_URL_SANDBOX);
		Configuration::updateValue('EVO_PAYMENT_URL_SANDBOX', self::ST_EVO_PAYMENT_URL_SANDBOX);     
	
		// url for prod
		Configuration::updateValue('EVO_CASHIER_URL_LIVE', self::ST_EVO_CASHIER_URL_LIVE);
		Configuration::updateValue('EVO_JAVASCRIPT_URL_LIVE', self::ST_EVO_JAVASCRIPT_URL_LIVE);
		Configuration::updateValue('EVO_TOKEN_URL_LIVE', self::ST_EVO_TOKEN_URL_LIVE);
		Configuration::updateValue('EVO_PAYMENT_URL_LIVE', self::ST_EVO_PAYMENT_URL_LIVE); 

		Configuration::updateValue('EVO_STATUS_SUCCESS', $this->addNewOrderState('EVO_STATUS_SUCCESS',
			$statusesTranslation::EVO_STATUS_SUCCESS));
		Configuration::updateValue('EVO_STATUS_ERROR', $this->addNewOrderState('EVO_STATUS_ERROR',
			$statusesTranslation::EVO_STATUS_ERROR));
		Configuration::updateValue('EVO_STATUS_CANCELED', $this->addNewOrderState('EVO_STATUS_CANCELED',
			$statusesTranslation::EVO_STATUS_CANCELED));
		Configuration::updateValue('EVO_STATUS_INPROGRESS', $this->addNewOrderState('EVO_STATUS_INPROGRESS',
			$statusesTranslation::EVO_STATUS_INPROGRESS));
		Configuration::updateValue('EVO_STATUS_REFUNDED', $this->addNewOrderState('EVO_STATUS_REFUNDED',
			$statusesTranslation::EVO_STATUS_REFUNDED));

        return (
            in_array('curl', get_loaded_extensions()) && 	parent::install() &&
            $this->createDbTable() &&
            $this->createHooks()
        );
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        return !(!parent::uninstall() ||
            !Configuration::deleteByName('EVO_SANDBOX') ||
            !Configuration::deleteByName('EVO_MERCHANT_ID') ||
            !Configuration::deleteByName('EVO_PASSWORD') ||
            !Configuration::deleteByName('EVO_PAYMENT_SOLUTION') ||
            !Configuration::deleteByName('EVO_BRANDID') ||
            !Configuration::deleteByName('EVO_PAYMENT_TYPE') ||
            !Configuration::deleteByName('EVO_REFUND_POSSIBILITY') ||
            
			/*
            !Configuration::deleteByName('EVO_STATUS_SUCCESS') ||
            !Configuration::deleteByName('EVO_STATUS_ERROR') ||
            !Configuration::deleteByName('EVO_STATUS_CANCELED') ||
            !Configuration::deleteByName('EVO_STATUS_INPROGRESS') ||
            !Configuration::deleteByName('EVO_STATUS_REFUNDED') ||
			*/
            
            !Configuration::deleteByName('EVO_CASHIER_URL_SANDBOX') ||
            !Configuration::deleteByName('EVO_JAVASCRIPT_URL_SANDBOX') ||
            !Configuration::deleteByName('EVO_TOKEN_URL_SANDBOX') ||
            !Configuration::deleteByName('EVO_PAYMENT_URL_SANDBOX') ||
            
            !Configuration::deleteByName('EVO_CASHIER_URL_LIVE') ||
            !Configuration::deleteByName('EVO_JAVASCRIPT_URL_LIVE') ||
            !Configuration::deleteByName('EVO_TOKEN_URL_LIVE') ||
            !Configuration::deleteByName('EVO_PAYMENT_URL_LIVE') );
            
    }

    /**
     * @return bool
     */
    private function createHooks()
    {
        $hooks =
            $this->registerHook('adminOrder') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('header');

        if (version_compare(_PS_VERSION_, '1.7', 'lt')) {
            $hooks &= $this->registerHook('displayPaymentEU') && $this->registerHook('payment');
        } else {
            $hooks &= $this->registerHook('paymentOptions');
        }

        return $hooks;
    }

    public function initEVOConfig()
    {
        if (Configuration::get('EVO_SANDBOX') == 1) {
            $this->payments = (new  Payments\Payments())->environmentUrls([
                "merchantId" => Configuration::get('EVO_MERCHANT_ID'),
                "password" => Configuration::get('EVO_PASSWORD'),
                "tokenURL" => Configuration::get('EVO_TOKEN_URL_SANDBOX'),
                "paymentsURL" => Configuration::get('EVO_PAYMENT_URL_SANDBOX'),
                "baseUrl" => Configuration::get('EVO_CASHIER_URL_SANDBOX'),
                "jsApiUrl" =>  Configuration::get('EVO_JAVASCRIPT_URL_SANDBOX'),
            ]);

        } else {
            $this->payments = (new Payments\Payments())->environmentUrls([
                "merchantId" => Configuration::get('EVO_MERCHANT_ID'),
                "password" => Configuration::get('EVO_PASSWORD'),
                "tokenURL" => Configuration::get('EVO_TOKEN_URL_LIVE'),
                "paymentsURL" => Configuration::get('EVO_PAYMENT_URL_LIVE'),
                "baseUrl" => Configuration::get('EVO_CASHIER_URL_LIVE'),
                "jsApiUrl" =>  Configuration::get('EVO_JAVASCRIPT_URL_LIVE'),
            ]);
        }
    }

    /**
     * @return string
     */
    public function getContent()
    {
        $return = '';
        $errors = [];

        if (Tools::isSubmit('submit' . $this->name)) {

            $EVO_SANDBOX = Tools::getValue('EVO_SANDBOX');
            $EVO_MERCHANT_ID = Tools::getValue('EVO_MERCHANT_ID');
            $EVO_PASSWORD = Tools::getValue('EVO_PASSWORD');
            $EVO_BRANDID = Tools::getValue('EVO_BRANDID');
			$EVO_PAYMENT_TYPE = Tools::getValue('EVO_PAYMENT_TYPE',self::ST_EVO_PAYMENT_TYPE);
            $EVO_REFUND_POSSIBILITY = Tools::getValue('EVO_REFUND_POSSIBILITY');
                
            // fields for sandbox env
            $EVO_CASHIER_URL_SANDBOX = Tools::getValue('EVO_CASHIER_URL_SANDBOX');
            $EVO_JAVASCRIPT_URL_SANDBOX = Tools::getValue('EVO_JAVASCRIPT_URL_SANDBOX');
            $EVO_TOKEN_URL_SANDBOX = Tools::getValue('EVO_TOKEN_URL_SANDBOX');
            $EVO_PAYMENT_URL_SANDBOX = Tools::getValue('EVO_PAYMENT_URL_SANDBOX');
            if( empty($EVO_CASHIER_URL_SANDBOX) ) {
                $EVO_CASHIER_URL_SANDBOX = self::ST_EVO_CASHIER_URL_SANDBOX;
            }
            if( empty($EVO_TOKEN_URL_SANDBOX) ) {
                $EVO_TOKEN_URL_SANDBOX = self::ST_EVO_TOKEN_URL_SANDBOX;
            }
            if( empty($EVO_PAYMENT_URL_SANDBOX) ) {
                $EVO_PAYMENT_URL_SANDBOX = self::ST_EVO_PAYMENT_URL_SANDBOX;
            }
            if( empty($EVO_JAVASCRIPT_URL_SANDBOX) ) {
                $EVO_JAVASCRIPT_URL_SANDBOX =  self::ST_EVO_JAVASCRIPT_URL_SANDBOX;
            }
            
            // fields for live env
            $EVO_CASHIER_URL_LIVE = Tools::getValue('EVO_CASHIER_URL_LIVE');
            $EVO_JAVASCRIPT_URL_LIVE = Tools::getValue('EVO_JAVASCRIPT_URL_LIVE');
            $EVO_TOKEN_URL_LIVE = Tools::getValue('EVO_TOKEN_URL_LIVE');
            $EVO_PAYMENT_URL_LIVE = Tools::getValue('EVO_PAYMENT_URL_LIVE');
            if( empty($EVO_CASHIER_URL_LIVE)) {
                $EVO_CASHIER_URL_LIVE = self::ST_EVO_CASHIER_URL_LIVE;
            }
            if( empty($EVO_TOKEN_URL_LIVE)) {
                $EVO_TOKEN_URL_LIVE = self::ST_EVO_TOKEN_URL_LIVE;
            }
            if( empty($EVO_PAYMENT_URL_LIVE)) {
                $EVO_PAYMENT_URL_LIVE = self::ST_EVO_PAYMENT_URL_LIVE;
            }
            if( empty($EVO_JAVASCRIPT_URL_LIVE) ) {
                $EVO_JAVASCRIPT_URL_LIVE = self::ST_EVO_JAVASCRIPT_URL_LIVE;
            }
            
            //brand check
            if($EVO_BRANDID!=''){
                if(!is_numeric($EVO_BRANDID)){
                    $errors[] = $this->l('The field value of "ID Brand" must be numeric.');
                }
            }

            //password check
            $password = strval($EVO_PASSWORD);
            if($password) {
                if(!Configuration::updateValue('EVO_PASSWORD', $EVO_PASSWORD) ) {
                    $errors[] = $this->l('An error occurred. Can not save password.');
                }
            }
            
            
            if (
                !Configuration::updateValue('EVO_SANDBOX', $EVO_SANDBOX) ||
                !Configuration::updateValue('EVO_MERCHANT_ID', $EVO_MERCHANT_ID) ||
                !Configuration::updateValue('EVO_BRANDID', $EVO_BRANDID) ||
                !Configuration::updateValue('EVO_PAYMENT_TYPE', $EVO_PAYMENT_TYPE) ||
                !Configuration::updateValue('EVO_REFUND_POSSIBILITY', $EVO_REFUND_POSSIBILITY) ||
                
                !Configuration::updateValue('EVO_CASHIER_URL_SANDBOX', $EVO_CASHIER_URL_SANDBOX) ||
                !Configuration::updateValue('EVO_JAVASCRIPT_URL_SANDBOX', $EVO_JAVASCRIPT_URL_SANDBOX) ||
                !Configuration::updateValue('EVO_TOKEN_URL_SANDBOX', $EVO_TOKEN_URL_SANDBOX) ||
                !Configuration::updateValue('EVO_PAYMENT_URL_SANDBOX', $EVO_PAYMENT_URL_SANDBOX) ||
                
                !Configuration::updateValue('EVO_CASHIER_URL_LIVE', $EVO_CASHIER_URL_LIVE) ||
                !Configuration::updateValue('EVO_JAVASCRIPT_URL_LIVE', $EVO_JAVASCRIPT_URL_LIVE) ||
                !Configuration::updateValue('EVO_TOKEN_URL_LIVE', $EVO_TOKEN_URL_LIVE) ||
                !Configuration::updateValue('EVO_PAYMENT_URL_LIVE', $EVO_PAYMENT_URL_LIVE)
            ) {
                $errors[] = $this->l('An error occurred. Can not save configuration.');
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $return .= $this->displayError($error);
                }
            } else {
                $return .= $this->displayConfirmation($this->l('Settings updated!'));
            }
        }

        $return .= $this->fetchTemplate('/views/templates/admin/info.tpl');

        return $return . $this->displayForm();
    }

    /**
     * @return mixed
     */
    public function displayForm()
    {
        $form['universalpay'] = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Integration options'),
                    'icon' => 'icon-th'
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Sandbox mode'),
                        'desc' => $this->l('Remember: You have to complete the correct connection data (login and password) to the Sandbox account in the form below if option is enabled.'),
                        'name' => 'EVO_SANDBOX',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ]
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Refund possibility'),
                        'desc' => $this->l('Runs the returns option in the order edition.'),
                        'name' => 'EVO_REFUND_POSSIBILITY',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ]
                        ],
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ]
            ]
        ];

            $form['merchant'] = [
                'form' => [
                    'legend' => [
                        'title' => $this->l('UniversalPay Connection settings'),
                        'icon' => 'icon-cog'
                    ],
                    'input' => [
                        [
                            'type' => 'text',
                            'required' => true,
                            'label' => $this->l('Merchant ID'),
                            'name' => 'EVO_MERCHANT_ID'
                        ],
                        [
                            'type' => 'password',
                            'required' => true,
                            'label' => $this->l('Password'),
                            'name' => 'EVO_PASSWORD'
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->l('ID Brand'),
                            'name' => 'EVO_BRANDID'
                        ],
                        // sandbox 

                        
                        
                        // live
                       
                    ],
                    'submit' => [
                        'title' => $this->l('Save'),
                    ]
                ]
            ];

        $this->addIntegrationMode($form['universalpay']['form']['input']);
        $this->addSandboxFields($form['merchant']['form']['input']);
        $this->addLiveFields($form['merchant']['form']['input']);

        $helper = new HelperForm();
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->show_toolbar = false;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submit' . $this->name;

        $language = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $language->id;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules',
                false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;

        $helper->tpl_vars = [
            'fields_value' => $this->getFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        ];

        return $helper->generateForm($form);
    }
    /**
     * Add integration mode based on the correspoding switching constants
     * @param Array $arr
     */
    private function addIntegrationMode(&$arr){
        
        $tmpArr = [];
        
        if(self::ST_SHOW_IFRAME) {
            array_push($tmpArr, [
                'id' => 'iframe',
                'value' => 1,
                'label' => $this->l('Iframe')
            ]);
        }
        
        if(self::ST_SHOW_REDIRECT) {
            array_push($tmpArr, [
                'id' => 'redirect',
                'value' => 0,
                'label' => $this->l('Redirect')
            ]);
        } 
        
        if(self::ST_SHOW_HOSTEDPAY) {
            array_push($tmpArr,  [
                'id' => 'hostedpay',
                'value' => 2,
                'label' => $this->l('Hosted Payment Page')
            ]);
        }        
        
        if( count($tmpArr) == 0 )
            return;
        
        array_unshift($arr, [
            'type' => 'radio',
            'label' => $this->l('Iframe/Redirect'),
            'name' => 'EVO_PAYMENT_TYPE',
            'class' => 't',
            'values' => $tmpArr,
            'is_bool' => true,
            'required' => true
        ]);
    }
    
    /**
     * Add URL fields for sandbox env based on the correspoding switching constants
     * @param Array $arr
     */
    private function addSandboxFields(&$arr){
        if(! self::ST_SHOW_SANDBOX_FIELDS)
            return;
        
        array_push($arr, 
                [
                'type' => 'text',
                'label' => $this->l('Cashier URL - Sandbox'),
                'name' => 'EVO_CASHIER_URL_SANDBOX'
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('JavaScript URL - Sandbox'),
                    'name' => 'EVO_JAVASCRIPT_URL_SANDBOX'
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Token URL - Sandbox'),
                    'name' => 'EVO_TOKEN_URL_SANDBOX'
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Payment Action URL - Sandbox'),
                    'name' => 'EVO_PAYMENT_URL_SANDBOX'
                ]
            );
        
    }
    
    /**
     * Add URL fields for live env based on the correspoding switching constants
     * @param Array $arr
     */
    private function addLiveFields(&$arr){
        if(! self::ST_SHOW_LIVE_FIELDS)
            return;
        
        array_push($arr, 
                    [
                'type' => 'text',
                'label' => $this->l('Cashier URL - Live'),
                'name' => 'EVO_CASHIER_URL_LIVE'
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('JavaScript URL - Live'),
                        'name' => 'EVO_JAVASCRIPT_URL_LIVE'
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Token URL - Live'),
                        'name' => 'EVO_TOKEN_URL_LIVE'
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Payment Action URL - Live'),
                        'name' => 'EVO_PAYMENT_URL_LIVE'
                    ]
            );
        
            
    }
    /**
     * @return array
     */
    private function getFieldsValues()
    {
        $data = [
            'EVO_SANDBOX' => Configuration::get('EVO_SANDBOX'),
            'EVO_MERCHANT_ID' => Configuration::get('EVO_MERCHANT_ID'),
            'EVO_PASSWORD' => Configuration::get('EVO_PASSWORD'),
            'EVO_BRANDID' => Configuration::get('EVO_BRANDID'),
            'EVO_PAYMENT_TYPE' => Configuration::get('EVO_PAYMENT_TYPE'),
            'EVO_REFUND_POSSIBILITY' => Configuration::get('EVO_REFUND_POSSIBILITY'),
            // sandbox
            'EVO_JAVASCRIPT_URL_SANDBOX' => Configuration::get('EVO_JAVASCRIPT_URL_SANDBOX'),
            'EVO_CASHIER_URL_SANDBOX' => Configuration::get('EVO_CASHIER_URL_SANDBOX'),
            'EVO_TOKEN_URL_SANDBOX' => Configuration::get('EVO_TOKEN_URL_SANDBOX'),
            'EVO_PAYMENT_URL_SANDBOX' => Configuration::get('EVO_PAYMENT_URL_SANDBOX'),
            
            // live
            'EVO_JAVASCRIPT_URL_LIVE' => Configuration::get('EVO_JAVASCRIPT_URL_LIVE'),
            'EVO_CASHIER_URL_LIVE' => Configuration::get('EVO_CASHIER_URL_LIVE'),
            'EVO_TOKEN_URL_LIVE' => Configuration::get('EVO_TOKEN_URL_LIVE'),
            'EVO_PAYMENT_URL_LIVE' => Configuration::get('EVO_PAYMENT_URL_LIVE'),
        ];

        return $data;
    }

    /**
     * @param string $name
     * @return string
     */
    public function buildTemplatePath($name)
    {
        if (version_compare(_PS_VERSION_, '1.7', 'lt')) {
            return $name . '.tpl';
        }

        return 'module:universalpay/views/templates/front/' . $name . '17.tpl';
    }

    /**
     * @param $name
     * @return mixed
     */
    public function fetchTemplate($name)
    {
        return $this->display(__FILE__, $name);
    }

    /**
     * @param $array
     * @return true
     */
    public function addJsVar($array)
    {
        Media::addJsDef(['evomodule' => $array]);
        return true;
    }

    /**
     * @param $cart
     * @return float
     */
    private function checkCurrency($cart)
    {
        $currency = new Currency((int)$cart->id_currency);
        $moduleCurrencies = $this->getCurrency((int)$cart->id_currency);

        if (is_array($moduleCurrencies)) {
            foreach ($moduleCurrencies as $currencyModule) {
                if ($currency->id == $currencyModule['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param $id_currency
     * @return string
     */
    private function getCurrencyIsobyId($id_currency)
    {
        return (new Currency((int)$id_currency))->iso_code;
    }


    /**
     * @param $file
     *
     * @return Media
     */
    public function getEVOLogo($file = 'evo_logo.png')
    {
        return Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/img/' . $file);
    }

    /**
     * For >=1.7
     * @param $params
     * @return array
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $paymentOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $paymentOption->setCallToActionText($this->l('Pay with UniversalPay'))
            ->setLogo($this->getEVOLogo('evo_u_icon.png'))
            ->setAction($this->context->link->getModuleLink($this->name, 'payment'))
            ->setModuleName($this->name);

        return [$paymentOption];
    }

    /**
     * @return null|string
     * @throws PrestaShopDatabaseException
     */
    public function hookAdminOrder($params)
    {
        $order = new Order($params['id_order']);
        $refund = Configuration::get('EVO_REFUND_POSSIBILITY');
        $return = '';

        $langCode = $this->context->language->iso_code;
//         $langCode = (new Language($order->id_lang)) -> iso_code;

        if (Configuration::get('EVO_REFUND_POSSIBILITY') && $order->module == 'universalpay') {
            $lastStatus = 0;
            $this->id_order = $params['id_order'];
            $orders = $this->getOrdersByIdOrder($params['id_order']);
            if ($orders) {
                foreach ($orders as &$orderr) {
                    //1. translate status  into target language
                    $statusTranslated = $this->getStatusDescByEvoStatus($orderr['status'], $langCode);
                    $orderr['statusTranslated'] = $statusTranslated;
                    
                    //2. translate info into target language
                    $rawInfo = $orderr['info'] == null ? "" : trim($orderr['info']);
                    if($rawInfo !== ""){
                        $start = mb_strrpos($rawInfo, ' ');
                        $rawInfo = mb_substr($rawInfo, $start);
                        $orderr['info'] = $this->l('Refunded: ', 'universalpay').$rawInfo;
                    }
                    
                    if ($orderr['status'] == 'success') {
                        $lastStatus = 1;
                        $tokenEvo = $orderr['token'];
                        break;
                    }
                }
            }
           
            
            $sum = $this->getSumCurrentOrderRefund($params['id_order']);
            $possibleToRefund = sprintf('%0.2f', $order->total_paid + $sum); 

            if (Tools::isSubmit('submit'.$this->name) && $lastStatus===1 && (int)$refund===1) {
                $return = $this->refundProcess($order, $tokenEvo);

                if(isset($return['redirect']) && $return['redirect']=='1'){
                    $admin = explode(DIRECTORY_SEPARATOR,_PS_ADMIN_DIR_);
                    $slice = array_slice($admin, -1);
                    $admin_folder = array_pop($slice);
                    $adminurl = __PS_BASE_URI__.$admin_folder;
                    Tools::redirectAdmin($adminurl.'/index.php?controller=AdminOrders&id_order='.$order->id.'&vieworder&conf=4&token='.Tools::getValue('token').'&success=1#evoOrders');
                }
            }

            $this->context->smarty->assign([
                'EVO_ORDERS' => $orders,
                'order' => $order,
                'name' => $this->name,
                'return' => $return,
                'sum' => $sum,
                'possibleToRefund' => $possibleToRefund,
                'lastStatus' => $lastStatus,
                'refund' => $refund
            ]);

            return $this->fetchTemplate('/views/templates/admin/status.tpl');
        }
        return false;
    }

    /**
     * @param Order
     * @return mixed
     */
    private function refundProcess($order, $tokenEvo)
    {
        $refundAmountRaw = Tools::getValue('refundValue');
        $sum = $this->getSumCurrentOrderPayments($order);
        $possibleToRefundRaw = $order->total_paid + $sum;
        $currency = new Currency($order->id_currency);

        //var_dump($possibleToRefund);
        $refundAmount = (double)$refundAmountRaw ;
        $refundAmount = Tools::convertPrice($refundAmount , $currency, false);
        $refundAmount = Tools::ps_round((float)$refundAmount ,2);

         
        $possibleToRefund = Tools::convertPrice($possibleToRefundRaw , $currency, false);
        $possibleToRefund = Tools::ps_round((float)$possibleToRefund ,2);

        
        if($possibleToRefund < 0 || round($refundAmount,2) > round($possibleToRefund,2)){
            return ['state' => 'danger', 'text' => $this->l('You can not make a refund on this amount for this order.', 'universalpay')];
        }
        
        $refund = $this->postRefund($tokenEvo, $order, $refundAmount);
        if(!$refund->errors){
            //             $token = $refund->originalMerchantTxId;
            //refund transaction id in payment gateway
            $refundTxId = $refund->merchantTxId;
            $currency = new Currency($order->id_currency);
            $id_evo_payment = $this->addOrderPaymentToDB($order->id_cart, $refundTxId, $status='refunded', '-'.$refundAmountRaw, $order->id, $refundAmountRaw.$currency->iso_code);
            //$order->addOrderPayment('-'.$refundAmount, 'UniversalPay', $id_evo_payment, $currency, date('Y-m-d H:i:s'));
            $this->updateOrderStatus((int)Configuration::get(self::MAP_STATUSES['refunded']), $order);
            return ['redirect' => '1'];
        } else {
            if($refund->errors){
                $arr_errors = $refund->errors;
                if(is_array($arr_errors) && count($arr_errors)>0){
                    $arr_errors_final = '';
                    foreach ($arr_errors as $err) {
                        $arr_errors_final .= $err['messageCode'].'. ';
                    }
                } else {
                    $arr_errors_final = $arr_errors;
                }
                return ['state' => 'danger', 'text' => $arr_errors_final];
            }
            return ['state' => 'danger', 'text' => $this->l('No connect with payment gateway.', 'universalpay')];
        }
        return false;
    }

    /**
     * @param Order
     * @param $refundAmount
     * @return float
     */
    private function postRefund($tokenEvo, $order, $refundAmount)
    {
        try {
            $this->initEVOConfig();
            $payments = $this->payments;

            $refund = $payments->refund();
            $refund->allowOriginUrl(Tools::getHttpHost(true) . __PS_BASE_URI__)->
            channel(Payments\Payments::CHANNEL_ECOM)->
            userDevice(Payments\Payments::USER_DEVICE_DESKTOP)->
            paymentSolutionId(trim(Configuration::get('EVO_PAYMENT_SOLUTION')))->
            originalMerchantTxId($tokenEvo)->
            amount(number_format($refundAmount, 2, '.', ''))->
            country(strtoupper($this->context->language->iso_code))->
            currency($this->getCurrencyIsobyId($order->id_currency));

            return $refund->execute();
        } catch (Exception $e) {
            return ['error' =>$e->getMessage(), 'errorcode' =>$e->getCode()];
        }
    }

    /**
     * @param $status
     * @param Order
     * @return float
     */
    public function updateOrderStatus($status, $order)
    {
        $history = new OrderHistory();
        $history->id_order = $order->id;
        $history->changeIdOrderState($status, $order->id);
        $history->addWithemail(true);

        return true;
    }

    /**
     * @deprecated
     * @param Order
     * @return float
     */
    private function getSumCurrentOrderPayments($order)
    {
        $payments = $order->getOrderPayments();
        $sum = 0;
        foreach ($payments as $payment) {
            if($payment->payment_method=='UniversalPay'){
                $dataPayment[$payment->id] = ['amount' => $payment->amount];
                if($payment->amount<0){
                    $sum = $payment->amount+$sum;
                }
            }
        }

        return $sum;
    }
    /**
     * sum up all the refunds of the order
     * 
     * @param number $id_order
     * @return number
     */
    private function getSumCurrentOrderRefund($id_order)
    {
        $sql = 'SELECT sum(amount) FROM ' . _DB_PREFIX_ . 'evo_payments WHERE id_order="' . addslashes($id_order) . '" and status="refunded"';
        $result = Db::getInstance()->executeS($sql, true, false);
        
        foreach ( $result[0] as $key => $value){
            $refundedAmount = $value;
        }
        
        return Tools::ps_round((float)$refundedAmount,2);
    }
    
    /**
     * @param $params
     * @return mixed
     */
    public function hookPayment($params)
    {
        $link = $this->context->link->getModuleLink('universalpay', 'payment');

        $this->context->smarty->assign([
                'image' => $this->getEVOLogo(),
                'actionUrl' => $link
            ]
        );

        return $this->fetchTemplate('/views/templates/hook/payment16.tpl');
    }

    public function hookDisplayPaymentEU()
    {
        $paymentOptions = [
            'cta_text' => $this->l('Pay with UniversalPay'),
            'action' => $this->context->link->getModuleLink('universalpay', 'payment'),
            'logo' => $this->getEVOLogo()
        ];

        return $paymentOptions;
    }

    public function hookHeader()
    {
        $this->initEVOConfig();
        $urlEvo = Payments\Config::$JavaScriptUrl;
        $this->context->controller->addCSS($this->_path . 'css/evopayments.css', 'all');
		
        $keyFunExists =  method_exists ( $this->context->controller , 'registerJavascript');
        if($keyFunExists){
            $this->context->controller->registerJavascript('api.js',$urlEvo,
            ['position' => 'head', 'priority' => 50, 'server' => 'remote']);
        }else {
            $this->context->controller->addJs($urlEvo, 'all');
        }
        $this->context->controller->addJS($this->_path . 'js/evopayments.js', 'all');
		
        $this->addJsVar(['baseUrl' => Context::getContext()->shop->getBaseURL(true)]);
    }

    /**
     * @param $id_order
     * @return bool | array
     * @throws PrestaShopDatabaseException
     */
    public function getOrdersByIdOrder($id_order)
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'evo_payments WHERE id_order="' . addslashes($id_order) . '" ORDER BY create_at DESC';
        $result = Db::getInstance()->executeS($sql, true, false);

        return $result ? $result : false;
    }

    /**
     * @param $token
     * @return bool | array
     * @throws PrestaShopDatabaseException
     */
    public function getIdOrderByToken($token)
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'evo_payments WHERE token="' . addslashes($token) . '"';
        $result = Db::getInstance()->executeS($sql, true, false);

        return $result ? $result : false;
    }

    /**
     * @param $id_order
     * @param $token
     * @return bool | array
     * @throws PrestaShopDatabaseException
     */
    public function checkAccess($id_order, $token)
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'evo_payments WHERE token="' . addslashes($token) . '" AND id_order="' . (int)$id_order . '"';
        $result = Db::getInstance()->executeS($sql, true, false);
        if ($result == false) {
            Tools::redirect('/index.php');
        } else {
            return true;
        }
        return false;
    }
    
    
    public function queryEvoPaymentsByMerchantTxId($merchantTxId){
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'evo_payments WHERE token="' . addslashes($merchantTxId) . '"';

        return Db::getInstance()->getRow($sql , false);
    }

    public function updateEvoPaymentsStatus($merchantTxId, $status){
        
        $sql = 'UPDATE ' . _DB_PREFIX_ . 'evo_payments
			SET status = "' . pSQL($status) . '", update_at = NOW()
			WHERE token="' . pSQL($merchantTxId) .'"';
        
        return Db::getInstance()->execute($sql);

    }
    
    /**
     * @param $token
     * @param $cart
     * @param $statusPayment
     * @return bool
     */
    private function changePaymentStatus($token, $cart, $statusPayment)
    {
        $sql = 'UPDATE ' . _DB_PREFIX_ . 'evo_payments
			SET status = "' . pSQL($statusPayment) . '", update_at = NOW()
			WHERE token="' . pSQL($token) . '" AND id_cart = "' . (int)$cart . '" ';

        return Db::getInstance()->execute($sql);
    }

    /**
     * @param $id_evo_payment
     * @param $id_order
     * @return bool
     */
    public function updateIdOrderInEvoPayment($id_evo_payment, $id_order)
    {
        $sql = 'UPDATE ' . _DB_PREFIX_ . 'evo_payments
			SET id_order= "' . (int)$id_order . '", update_at = NOW()
			WHERE id_evo_payment = "' . (int)$id_evo_payment . '" ';

        return Db::getInstance()->execute($sql);
    }

    public function updateRawIdOrderInEvoPayment($id_evo_payment, $id_order)
    {
        $sql = 'UPDATE ' . _DB_PREFIX_ . 'evo_payments
			SET id_order_raw= "' . (int)$id_order . '", update_at = NOW()
			WHERE id_evo_payment = "' . (int)$id_evo_payment . '" ';
        
        return Db::getInstance()->execute($sql);
    }
    
    public function updateOrder()
    {
        $result = null;
        $this->order = new Order($this->id_order);
    }

    /**
     * @return array
     */
    public function getPaymentToken($merchantTxId, $retry='', $mode, $amount='')
    {
        if($amount===''){
            $amount = $this->context->cart->getOrderTotal(true);
        }

        $address = new Address($this->context->cart->id_address_invoice);
        $countryIso = Country::getIsoById( $address->id_country );
        
        $customerAddressCountry = $countryIso;
        $customerAddressCity =  $address->city;
        $customerAddressStreet = $address->address1;
        $customerAddressPostalCode = $address->postcode;
        $customerFirstName = $address->firstname;
        $customerLastName = $address->lastname;
        $customerEmail = $this->context->customer->email;
        $customerPhone = null;
        if(trim($address->phone_mobile))
          $customerPhone = trim($address->phone_mobile);
        else 
          $customerPhone = trim($address->phone);
        $customerAddressState=(new State($address->id_state))->iso_code;
        if($customerAddressState == null ) $customerAddressState  = '';

        $defaultLang = Configuration::get('PS_LANG_DEFAULT');
        $merchantCountryId = Configuration::get('PS_COUNTRY_DEFAULT');
        $merchantCurrencyId = Configuration::get('PS_CURRENCY_DEFAULT');
        $merchantCountryIsoCode = (new Country($merchantCountryId ))->iso_code;
        $merchantCurrencyCode = $this->getCurrencyIsobyId($merchantCurrencyId );

        // Get currency
        if($this->context->cart->id_currency ) {
            $currency = new Currency($this->context->cart->id_currency);
        }else {
            $currency = $this->context->currency;
        }

        $amount = number_format($amount, 2, '.', '');
        $amount= Tools::convertPrice($amount, $currency, false);
        $amount = Tools::ps_round((float)$amount,2);

        try {
            $payments = $this->payments;
            $purchase = $payments->purchase();

			$customerIPAddress = isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            $merchantChallengeInd = '01';
            $merchantDecReqInd = 'N';
            if( strlen($customerAddressStreet) > 50 )
                $customerAddressStreet = substr($customerAddressStreet, 0,50);
            if($customerIPAddress == '::1')
                $customerIPAddress = '127.0.0.1';
            $integrateMode = (int) $mode;
            $notificationUrl = Tools::getHttpHost(true).__PS_BASE_URI__ . 'index.php?fc=module&module=universalpay&controller=response&ipg=0&retry=' . $retry;
            $landingUrl = Tools::getHttpHost(true).__PS_BASE_URI__ . 'index.php?fc=module&module=universalpay&controller=response&ipg=0&retry=' 
                . $retry .'&merchantTxId='.$merchantTxId 
                .'&lang='.$this->context->language->iso_code;
            
            $purchase -> paymentSolutionId(trim(Configuration::get('EVO_PAYMENT_SOLUTION')))
                      -> merchantNotificationUrl($notificationUrl);
            
            /**
             * Mode value pair
             * 
             * 0 - redirect
             * 1 - iframe
             * 2 - hosted payment
             */ 
             if($integrateMode === 0 || $integrateMode === 2) {//redirect or hosted payment
                $purchase -> allowOriginUrl(Tools::getHttpHost(true) . __PS_BASE_URI__)
                          -> merchantLandingPageUrl($landingUrl);
            } else { // iframe
                $purchase->allowOriginUrl(Tools::getHttpHost(true));
            }

            $brandId = Configuration::get('EVO_BRANDID');
            if($brandId != ''){
                $purchase -> brandId($brandId);
            }
			$langCode = $this->context->language->iso_code;
			if($langCode == 'mx'){
			  $langCode = 'es-MX';
			}
            $purchase->channel(Payments\Payments::CHANNEL_ECOM)->
            userDevice(Payments\Payments::USER_DEVICE_DESKTOP)->
            merchantTxId($merchantTxId)->
            language($langCode)->
            amount($amount)->
            country($merchantCountryIsoCode)->
            currency($merchantCurrencyCode)->
            customerAddressCountry($customerAddressCountry) ->
            customerAddressCity($customerAddressCity) ->
            customerAddressStreet($customerAddressStreet) ->
            customerAddressPostalCode($customerAddressPostalCode) ->
            customerFirstName($customerFirstName) ->
            customerLastName($customerLastName)->
            merchantLandingPageRedirectMethod('GET')->
            merchantChallengeInd($merchantChallengeInd)->
            merchantDecReqInd($merchantDecReqInd)->
            userAgent($userAgent)->
            customerIPAddress($customerIPAddress)->
            customerAddressHouseName($customerAddressStreet)->
            customerEmail($customerEmail)->
            customerPhone($customerPhone);
			
            $token = $purchase->token();

            return $token->token();
        } catch (Exception $e) {
            return ['error' =>$e->getMessage(), 'errorcode' =>$e->getCode()];
        }
    }

    /**
     * @return array
     */
    public function getPaymentStatus($merchantTxId)
    {
        try {
            $payments = null;
            if (Configuration::get('EVO_SANDBOX') == 1) {
                $payments = (new  Payments\Payments())->environmentUrls([
                    "merchantId" => Configuration::get('EVO_MERCHANT_ID'),
                    "password" => Configuration::get('EVO_PASSWORD'),
                    "tokenURL" => Configuration::get('EVO_TOKEN_URL_SANDBOX'),
                    "paymentsURL" => Configuration::get('EVO_PAYMENT_URL_SANDBOX'),
                    "baseUrl" => Configuration::get('EVO_CASHIER_URL_SANDBOX'),
                    "jsApiUrl" =>  Configuration::get('EVO_JAVASCRIPT_URL_SANDBOX'),
                ]);
                
            } else {
                $payments = (new Payments\Payments())->environmentUrls([
                    "merchantId" => Configuration::get('EVO_MERCHANT_ID'),
                    "password" => Configuration::get('EVO_PASSWORD'),
                    "tokenURL" => Configuration::get('EVO_TOKEN_URL_LIVE'),
                    "paymentsURL" => Configuration::get('EVO_PAYMENT_URL_LIVE'),
                    "baseUrl" => Configuration::get('EVO_CASHIER_URL_LIVE'),
                    "jsApiUrl" =>  Configuration::get('EVO_JAVASCRIPT_URL_LIVE'),
                ]);
            }
            $status_check = $payments->status_check();
            $status_check->merchantTxId($merchantTxId)->
            allowOriginUrl(Tools::getHttpHost(true).__PS_BASE_URI__);

            return $status_check->execute();
        } catch (Exception $e) {
            return ['error' =>$e->getMessage(), 'errorcode' =>$e->getCode()];
        }
    }

    /**
     * @param $status
     * @return Order
     * @throws PrestaShopDatabaseException
     */
    public function createOrder($status)
    {
        $this->validateOrder(
            $this->context->cart->id, (int)Configuration::get(self::MAP_STATUSES[$status]),
            $this->context->cart->getOrderTotal(true, Cart::BOTH), $this->displayName,
            null, [], (int)$this->context->cart->id_currency, false, $this->context->cart->secure_key,
            Context::getContext()->shop->id ? new Shop((int)Context::getContext()->shop->id) : null
        );

        return new Order($this->currentOrder);
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     */
    private function dropDbTable()
    {
        return Db::getInstance()->Execute('DROP TABLE IF EXISTS  `' . _DB_PREFIX_ . 'evo_payments`');
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     */
    private function createDbTable()
    {
        if (Db::getInstance()->ExecuteS('SHOW TABLES LIKE "' . _DB_PREFIX_ . 'evo_payments"')) {
            if (Db::getInstance()->ExecuteS('SHOW COLUMNS FROM ' . _DB_PREFIX_ . 'evo_payments LIKE "ext_order_id"') == false) {
                return true;
            }

            return true;
        }

        return Db::getInstance()->Execute('CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'evo_payments` (
                `id_evo_payment` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                `id_order` INT(10) UNSIGNED NOT NULL,
                `id_cart` INT(10) UNSIGNED NOT NULL,
				`id_order_raw` INT(10) UNSIGNED NOT NULL,
                `token` varchar(255) NOT NULL,
                `status` varchar(64) NOT NULL,
                `amount` DECIMAL(20,2) NULL DEFAULT NULL, 
                `info` varchar(255) NOT NULL,
                `create_at` datetime,
                `update_at` datetime
            )');
    }

    /**
     * @param $id_lang
     * @param $name
     * @return bool | string
     * @throws PrestaShopDatabaseException
     */
    public function checkIfStateExist($id_lang, $name){
        $sql = 'SELECT id_order_state FROM ' . _DB_PREFIX_ . 'order_state_lang WHERE id_lang="' . (int)$id_lang . '" AND name="' . addslashes($name) . '"';
        $result = Db::getInstance()->executeS($sql, true, false);

        return isset($result['0']['id_order_state']) ? $result['0']['id_order_state'] : false;
    }

    /**
     * @param string $state
     * @param array  $names
     * @return bool
     */
    public function addNewOrderState($state, $names)
    {
		$languages = Language::getLanguages(true, $this->context->shop->id);
		$order_state = new OrderState( Configuration::get($state) );

		 foreach ($languages as $step => $lang) {
			$lang = $languages[$step]['iso_code'];
			$id_lang = $languages[$step]['id_lang'];
			if('mx' === $lang )
				$lang = 'es-mx';	
			if( ! isset( $names[$lang]) )
			  continue;			
			$order_state->name[$id_lang] = $names[$lang];
		}
		$order_state->send_email = false;
		$order_state->invoice = false;
		$order_state->unremovable = true;
		$order_state->color = '#4286f4';
		$order_state->module_name = 'universalpay';
		if($order_state->id != null ){
			$order_state->update();
		} else {
			$order_state->add();
			Configuration::updateValue($state, $order_state->id);
		}
		copy(_PS_MODULE_DIR_ . $this->name . '/logo.gif', _PS_IMG_DIR_ . 'os/' . $order_state->id . '.gif');
		
        return $order_state->id;
    }

    /**
     * @param int    $cart
     * @param string $token
     * @param string $statusPayment
     * @return mixed
     */
    public function paymentProcess($token, $cart, $statusPayment)
    {
        $this->changePaymentStatus($token, $cart, $statusPayment);

        return true;
    }

    /**
     * @param int    $idCart
     * @param string $token
     * @param string $status
     * @param float $amount
     * @return mixed
     */
    public function addOrderPaymentToDB($idCart, $token, $status = 'inprogress', $amount = null, $idOrder = 0, $additionalInfo = '')
    {
        $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'evo_payments (id_cart, token, status, amount, create_at, update_at, id_order, info)
				VALUES (' . (int)$idCart . ', "' . pSQL($token) . '", "' . pSQL($status)  .'","' .$amount .'", NOW(), NOW(), ' . (int)$idOrder . ', "' . pSQL($additionalInfo) . '")';
        
        if (Db::getInstance()->execute($sql)) {
            return (int)Db::getInstance()->Insert_ID();
        }
        
        return false;
    }
    
    /**
     * get payment status description by UniversalPay status
     */
    public function getStatusDescByEvoStatus($evoStatus, $lang){
        
        $statusesTranslation = new StatusTranslation\Statuses();
        $candidate = null;
        switch ($evoStatus) {
            case "failure":
                $candidate = $statusesTranslation::EVO_STATUS_ERROR;
                break;
            case "refunded":
                $candidate = $statusesTranslation::EVO_STATUS_REFUNDED;
                break;
            case "cancel":
                $candidate = $statusesTranslation::EVO_STATUS_CANCELED;
                break;
            case "inprogress":
                $candidate = $statusesTranslation::EVO_STATUS_INPROGRESS;
                break;
            default:
                $candidate = $statusesTranslation::EVO_STATUS_SUCCESS;
        }
        
        
        if (! isset($candidate[$lang]) ){
            return $candidate['default'];
        }
        
        return $candidate[$lang];
    }
}
