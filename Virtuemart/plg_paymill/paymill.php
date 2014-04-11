<?php
/*
 * @package 	Paymill Payment Gateway for Virtuemart
 * @copyright 	Copyright (C) 2010-2011 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license 	GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link     	http://www.techjoomla.com
 */
 
defined('_JEXEC') or die('Restricted access');
if (!class_exists('vmPSPlugin')) {
	require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
}
 
class plgVmpaymentPaymill extends vmPSPlugin
{
	var $pluginConfig = array(
		'public_key' => array('PUBLIC_KEY', 'input'),
		'private_key' => array('PRIVATE_KEY', 'input'),
		'payment_mode' => array('ENABLE_PAYMENT_MODE', 'list',array(
			'true' => 'Test')
		)
	);

	const PAYMILL_PAYMENT_CURRENCY = "EUR";
	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param object $subject The object to observe
	 * @param array  $config  An array that holds the plugin configuration
	 * @since 1.5
	 */
	// instance of class
	function __construct (& $subject, $config)
	{

       parent::__construct($subject, $config);
		$config = JFactory::getConfig();

        $this->_loggable = true;
        $this->tableFields = array_keys($this->getTableSQLFields());

        $varsToPush = array(
            'payment_uid' => array('', 'char'),
            'payment_pid' => array('', 'char'),
            'payment_pas' => array('', 'char'),
            'payment_npas' => array('', 'char'),
            'payment_info' => array('', 'char'),
            'debug' => array(0, 'int'),
            'status_pending' => array('', 'char'),
            'status_success' => array('', 'char'),
            'status_canceled' => array('', 'char'),
        );
		$this->tableFields = array_keys ($this->getTableSQLFields ());
		$varsToPush = $this->getVarsToPush ();
		$this->setConfigParameterable ($this->_configTableFieldName, $varsToPush);
	}
	
	protected function getVmPluginCreateTableSQL ()
	{
		return $this->createTableSQL('Payment Paymill Table');
	}

	function getTableSQLFields ()
	{
      $SQLfields = array(
            'id' 										 => 'tinyint(1) unsigned NOT NULL AUTO_INCREMENT',
            'virtuemart_order_id' 						 => 'int(11) UNSIGNED DEFAULT NULL',
            'order_number' 								 => 'char(32) DEFAULT NULL',
            'virtuemart_paymentmethod_id' 				 => 'mediumint(1) UNSIGNED DEFAULT NULL',
            'payment_name' 								 => 'char(255) NOT NULL DEFAULT \'\' ',
            'payment_order_total' 						 => 'decimal(15,5) NOT NULL DEFAULT \'0.00000\' ',
            'payment_currency' 							 => 'char(3) ',
            'tax_id'                                     => 'smallint(1)',
			'paymill_payment_id'						 => 'varchar(64)',
			'paymill_transaction_id'				     => 'varchar(64)',
			'paymill_transaction_status'		         => 'varchar(32)',
			'paymill_client_email'					     => 'varchar(64)',
			'paymill_transaction_object'			     => 'text'
        );
		return $SQLfields;
	}

	/**
	 * This shows the plugin for choosing in the payment list of the checkout process.
	*/
   public function plgVmDisplayListFEPayment($cart, $selected = 0, &$htmlIn) {
		$public_key = $this->params->def('public_key');
		$private_key = $this->params->def('private_key');
		if ($this->getPluginMethods ($cart->vendorId) === 0) {
			if (empty($this->_name)) {
				$app = JFactory::getApplication ();
				$app->enqueueMessage (JText::_ ('COM_VIRTUEMART_CART_NO_' . strtoupper ($this->_psType)));
				return FALSE;
			} else {
				return FALSE;
			}
		}
		$price = round($cart->pricesUnformatted['salesPrice'], 2);
		JFactory::getLanguage ()->load ('com_virtuemart');
		foreach ($this->methods as $method) { 
			if ($this->checkConditions ($cart, $method, $cart->pricesUnformatted)) {
				$methodSalesPrice = $this->calculateSalesPrice ($cart, $method, $cart->pricesUnformatted);
				$html = $this->getPluginHtml ($method, $selected, $methodSalesPrice);
				$html .= '
				<script type="text/javascript" src="https://bridge.paymill.de/"></script>
				<script type="text/javascript" src="https://static.paymill.com/assets/js/jquery/jquery-1.7.2.min.js"></script>
				<script type="text/javascript" src="'.JURI::root().'plugins/vmpayment/paymill/assets/js/paymill.js"></script>
				<script type="text/javascript">
						PAYMILL_PUBLIC_KEY =  "'.$public_key.'";
						PAYMILL_PRIVATE_KEY = "'.$private_key.'";
				</script>
				<style>
				.checkout-button-top { text-align:left; }
				</style>
			<link rel="stylesheet" href="'.JURI::root().'plugins/vmpayment/paymill/assets/css/screen.css">
			<div id ="paymillfrm">
            <input class="sum" id="test-transaction-form-sum" type="hidden" value="'.$price.'" readonly style="background-color:transparent;color:black !important;">
            <div id="uname">
            <div id="uname1" style="float:left;width:11%;"><label  style="">Name</label></div>
            <div id="unameinput" style="float:center;"><input class="name" id="test-transaction-form-name" type="text" value="">
            </div>
            
            <div class="btn-group">
            	
                <a class="btn btn-payment btn-primary" id="btn-payment-cc">Credit Card</a>
                <a class="btn btn-payment" id="btn-payment-debit">Direct Debit</a>
                <a class="btn btn-payment" id="btn-payment-debit-v2" >IBAN/BIC</a>
            </div>
            <br>

            <div class="payment-input" id="cc" style="display:block;">
                <div id="uname1" style="float:left;width:11%;"><label >Credit card #</label></div>
                <input class="number" id="CC_NUMBER" type="text" maxlength="16" value=""><br><br>
                
                <div id="uname1" style="float:left;width:11%;"><label >Expiry date</label>
                </div><input class="month" id="test-transaction-form-month" type="text" maxlength="2" size="4" value=""> /
                <input class="year" id="test-transaction-form-year" type="text" maxlength="4" value="" size="6"><br><br>
                
                <div id="cvcno">
                <div id="cvclbl" style="float:left;width:11%;"><label class="cvcLabel" >'.JText::_ ('CVC').'</label></div>
                <input class="checksum" id="test-transaction-form-cvc" type="text" value=""><br>
                </div>
            </div>

            <div class="payment-input" style="display:none;" id="dc">
                <div id="accno" style="float:left;width:11%;"><label >Account #</label></div>
                <input class="number" id="test-transaction-form-account" type="text" maxlength="16" value=""><br><br>
                <div id="bno" style="float:left;width:11%;"><label >Bank code</label></div>
                <input class="number" id="test-transaction-form-code" type="text" maxlength="16" value=""><br>
            </div>

            <div class="payment-input" style="display:none;" id="iban">
                <div id="ibnlbl" style="float:left;width:11%;"><label >IBAN</label></div>
                <input class="number" id="test-transaction-form-iban" type="text" maxlength="27" value=""><br><br>
                <div id="bnlbl" style="float:left;width:11%;"><label >BIC</label></div>
                <input class="number" id="test-transaction-form-bic" type="text" maxlength="16" value=""><br>
            </div>
            </div>
            <div class="payment-errors"></div>
					<div align="right"><input type="button" id="test-transaction-button" class="btn btn-primary pull-right" value="Submit" style="text-align:right;"/></div>
		        	<div id="loader" style="display: none">'.JText::_ ('VMPAYMENT_PAYMILL_PAYMENT_IS_PROCEEDED').'...</div>
		        	<div id="paymentErrors"></div>
		        	<div id="result" style="display: none"><span style="color: #009900">Daten erfolgreich eingegeben!</span></div>
		        	<img id="loadergif" src="plugins/vmpayment/paymill/assets/image/loader.gif" style="display: none; margin: 0px 10px" />';
				$htmla[] = $html;
			}
		}
		$htmlIn[] = $htmla;
		return true;
    }

	/**
	 * Check if the payment conditions are fulfilled for this payment method
	 * @param $cart_prices: cart prices
	 * @param $payment
	 * @return true: if the conditions are fulfilled, false otherwise
	 *
	 */
	protected function checkConditions ($cart, $method, $cart_prices)
	{
		$this->convert_condition_amount($method);
		$amount = $this->getCartAmount($cart_prices);
		$address = (($cart->ST == 0) ? $cart->BT : $cart->ST);

		$amount_cond = ($amount >= $method->min_amount AND $amount <= $method->max_amount
			OR
			($method->min_amount <= $amount AND ($method->max_amount == 0)));
		if (!$amount_cond) {
			return FALSE;
		}
		$countries = array();
		if (!empty($method->countries)) {
			if (!is_array($method->countries)) {
				$countries[0] = $method->countries;
			} else {
				$countries = $method->countries;
			}
		}

		// probably did not gave his BT:ST address
		if (!is_array($address)) {
			$address = array();
			$address['virtuemart_country_id'] = 0;
		}

		if (!isset($address['virtuemart_country_id'])) {
			$address['virtuemart_country_id'] = 0;
		}
		if (count($countries) == 0 || in_array($address['virtuemart_country_id'], $countries) || count($countries) == 0) {
			return TRUE;
		}
		return FALSE;
	}

   function plgVmConfirmedOrder($cart, $order) { 
		$session = JFactory::getSession();
		$return_context = $session->getId(); 
		$transaction_key = $this->get_passkey(); 
        $db = &JFactory::getDBO();
		$jinput   = JFactory::getApplication()->input;
		$component  = $jinput->getCmd('option'); 
		$xml = JFactory::getXML(JPATH_SITE.'/administrator/components/com_virtuemart/virtuemart.xml');
		$comversion=(string)$xml->version;	
		$paymillxml = JFactory::getXML(JPATH_SITE.'/plugins/vmpayment/paymill/paymill.xml');	
		$pluginversion=(string)$paymillxml->version;	
		$source = $pluginversion.'_'.$component.'_'.$comversion; 
		$order_id = $order['details']['BT']->virtuemart_order_id;
        if (!($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
            return null; // Another method was selected, do nothing
        }
        if (!$this->selectedThisElement($method->payment_element)) {
            return false;
        }
        $session = JFactory::getSession();
        $return_context = $session->getId();
        $this->_debug = $method->debug;
        $this->logInfo('plgVmConfirmedOrder order number: ' . $order['details']['BT']->order_number, 'message');

        if (!class_exists('VirtueMartModelOrders'))
            require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );
        if (!class_exists('VirtueMartModelCurrency'))
            require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'currency.php');
        $html = '';
        $usrBT = $order['details']['BT'];
        $address = ((isset($order['details']['ST'])) ? $order['details']['ST'] : $order['details']['BT']);

        $vendorModel = new VirtueMartModelVendor();
        $vendorModel->setId(1);
        $vendor = $vendorModel->getVendor();
        $this->getPaymentCurrency($method);
        $q = 'SELECT `currency_code_3` FROM `#__virtuemart_currencies` WHERE `virtuemart_currency_id`="' . $method->payment_currency . '" ';
        $db->setQuery($q);
        $currency_code_3 = $db->loadResult();
 		$public_key = $this->params->def('public_key');
		$private_key = $this->params->def('private_key');
        define('API_HOST', 'https://api.paymill.com/v2/');
		define('API_KEY', $private_key);
		$db = & JFactory::getDBO();
        $sql = 'SELECT token FROM #__paymill ORDER BY id DESC LIMIT 1'; 
        $db->setQuery($sql);
        $token = $db->loadResult(); 
		$totalInPaymentCurrency = round($order['details']['BT']->order_total, 2); 
		if ($token) {
				require "components/com_paymillapi/lib/Services/Paymill/Transactions.php";
				$transactionsObject = new Services_Paymill_Transactions(API_KEY, API_HOST);
				$params = array(
					'amount' => $totalInPaymentCurrency * 100,
					'currency' => 'EUR',
					'token' => $token,
					'description' => 'Order Id: '.$order_id,
					'source'    => $source
				);
				$transaction = $transactionsObject->create($params);
				$pm_status = $transaction['status'];
				$q = "UPDATE #__paymill SET status = '".$pm_status."', email = '".$address->email."', source = '".$source."' WHERE token = '" .$token. "'"; 
		        $db->setQuery( $q );
		        $db->query(); }
		        else {
				echo "Your credit card payment was unfortunately wrong. Please check your entry.<br /><br /><a href='".JURI::root()."/component/virtuemart/cart/editpayment?Itemid=0'>Back to Payment</a>";
		}
        $totalInPaymentCurrency = round($order['details']['BT']->order_total, 2);
        $cd = CurrencyDisplay::getInstance($cart->pricesCurrency);

        $user_title = $address->title;
        $user_email = $address->email;
        $user_name = $address->first_name . ' ' . $address->last_name;
        $user_city = $address->city;
        $user_address = $address->address_1;
        $user_zip = $address->zip;
        $user_country = ShopFunctions::getCountryByID($address->virtuemart_country_id, 'country_3_code');

        $msg_1 = $user_name . " Kd-nr " . $usrBT->virtuemart_user_id;
        $msg_2 = "Bestellnr " . $order['details']['BT']->order_number;

		$cont=$method->payment_uid."|".$method->payment_pid."|||||".$totalInPaymentCurrency."|".$currency_code_3."|".$msg_1."|".$msg_2."|".$order['details']['BT']->order_number."|".$order['details']['BT']->virtuemart_paymentmethod_id."|VM v2.1||||".$method->payment_pas;
		$hash = md5($cont);

		$html .= '<div style="text-align: left; margin-top: 25px; margin-bottom: 25px;">';
		$html .= 'Your order has been received and will be processed';
		$html .= '</div>';

        // Prepare data that should be stored in the database
        $dbValues = array();
        $dbValues['order_number'] = $order['details']['BT']->order_number;
        $dbValues['payment_name'] = $this->renderPluginName($method, $order);
        $dbValues['virtuemart_paymentmethod_id'] = $cart->virtuemart_paymentmethod_id;
        $dbValues['payment_currency'] = $method->payment_currency;
        $dbValues['payment_order_total'] = $totalInPaymentCurrency;
        $this->storePSPluginInternalData($dbValues);
		$new_status = 'C';
        return $this->processConfirmedOrderPaymentResponse(1, $cart, $order, $html, $dbValues['payment_name'], $new_status);
    }
    
	public function plgVmonSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name) {
        return $this->onSelectedCalculatePrice($cart, $cart_prices, $cart_prices_name);
    }

    /**
     * plgVmOnCheckAutomaticSelectedPayment
     * Checks how many plugins are available. If only one, the user will not have the choice. Enter edit_xxx page
     * The plugin must check first if it is the correct type
     * @param VirtueMartCart cart: the cart object
     * @return null if no plugin was found, 0 if more then one plugin was found,  virtuemart_xxx_id if only one plugin is found
     *
     */
    function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array(), &$paymentCounter) 
    {
		$return = $this->onCheckAutomaticSelected($cart, $cart_prices);
		if (isset($return)) {
			return 0;
		} else {
			return NULL;
		}
	}



    function plgVmOnPaymentNotification() {
		$public_key = $this->params->def('public_key');
		$private_key = $this->params->def('private_key');
        $virtuemart_paymentmethod_id = JRequest::getInt('pm', 0);
		$order_number = JRequest::getInt('on', 0);
        if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
            return null; // Another method was selected, do nothing
        }
        if (!$this->selectedThisElement($method->payment_element)) {
            return false;
        }

        if (!class_exists('VirtueMartModelOrders'))
            require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );

        $virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number);
        $this->logInfo('plgVmOnPaymentNotification: virtuemart_order_id  found ' . $virtuemart_order_id, 'message');

        if (!$virtuemart_order_id) {
            $this->_debug = true; // force debug here
            $this->logInfo('plgVmOnPaymentNotification: virtuemart_order_id not found ', 'ERROR');
            exit;
        }
        $vendorId = 0;
        $payment = $this->getDataByOrderId($virtuemart_order_id);
        
        $method = $this->getVmPluginMethod($payment->virtuemart_paymentmethod_id);
        if (!$this->selectedThisElement($method->payment_element)) {
            return false;
        }
        $this->_debug = $method->debug;
        if (!$payment) {
            $this->logInfo('getDataByOrderId payment not found: exit ', 'ERROR');
            return null;
        }
        $new_status = 'C';
		$new_comment = 'Paymill - Money is received.';
        $this->logInfo('plgVmOnPaymentNotification return new_status:' . $new_status, 'message');
        if ($virtuemart_order_id) {
            // send the email only if payment has been accepted
            if (!class_exists('VirtueMartModelOrders'))
                require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );
            $modelOrder = new VirtueMartModelOrders();
            $order['order_status'] = $new_status;
            $order['comments'] = $new_comment;           
            $order['virtuemart_order_id'] = $virtuemart_order_id;
            $order['customer_notified'] = 0;
			// END NEW PM_VARS
            //$modelOrder->updateStatusForOneOrder($virtuemart_order_id, $order, true);
        }
        return true;
    }

}

