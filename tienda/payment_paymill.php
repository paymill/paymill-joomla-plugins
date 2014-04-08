<?php
/**
 * --------------------------------------------------------------------------------
 * Payment Plugin - Paymill
 * --------------------------------------------------------------------------------
 * @package     Joomla!_2.5x_And_3.0X
 * @subpackage  Tienda
 * @author      Techjoomla <support@techjoomla.com>
 * @copyright   Copyright (c) 2010 - 2015 Techjoomla . All rights reserved.
 * @license     GNU/GPL license: http://www.techjoomla.com/copyleft/gpl.html
 * @link        http://techjoomla.com
 * --------------------------------------------------------------------------------
 * */

defined('_JEXEC') or die('Restricted access');

Tienda::load('TiendaPaymentPlugin', 'library.plugins.payment');

$lang = JFactory::getLanguage();
$lang->load('plg_tienda_payment_paymill', JPATH_ADMINISTRATOR);

/**
	* plgTiendaPayment_paymill class.
	*
	* @category   PHP
	* @package    Paymill
	* @author     Techjoomla <support@techjoomla.com>
	* @author     Techjoomla <support@techjoomla.com>
	* @copyright  2006-2013 Techjoomla
	* @license    Techjoomla Licence
	* @link       techjoomla.com
	* @since      new
 * */

class PlgTiendaPayment_paymill extends TiendaPaymentPlugin
{
		private $_element = 'payment_paymill';

		private $public_key = '';

		private $private_key  = '';

		private $_isLog = false;

/**
	* Constructs.
	*
	* @param   string  &$subject  The number of spaces each tab represents.
	* @param   string  $config    The charset of the sniffed files.
	*
	* @see process()
 * */

	private function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage('', JPATH_ADMINISTRATOR);
		$this->code_arr = array (
		'internal_server_error'       => JText::_('INTERNAL_SERVER_ERROR'),
		'invalid_public_key'    	  => JText::_('INVALID_PUBLIC_KEY'),
		'unknown_error'               => JText::_('UNKNOWN_ERROR'),
		'3ds_cancelled'               => JText::_('3DS_CANCELLED'),
		'field_invalid_card_number'   => JText::_('FIELD_INVALID_CARD_NUMBER'),
		'field_invalid_card_exp_year' => JText::_('FIELD_INVALID_CARD_EXP_YEAR'),
		'field_invalid_card_exp_month' => JText::_('FIELD_INVALID_CARD_EXP_MONTH'),
		'field_invalid_card_exp'      => JText::_('FIELD_INVALID_CARD_EXP'),
		'field_invalid_card_cvc'      => JText::_('FIELD_INVALID_CARD_CVC'),
		'field_invalid_card_holder'   => JText::_('FIELD_INVALID_CARD_HOLDER'),
		'field_invalid_amount_int'    => JText::_('FIELD_INVALID_AMOUNT_INT'),
		'field_invalid_amount'        => JText::_('FIELD_INVALID_AMOUNT'),
		'field_invalid_currency'      => JText::_('FIELD_INVALID_CURRENCY'),
		'field_invalid_account_number' => JText::_('FIELD_INVALID_AMOUNT_NUMBER'),
		'field_invalid_account_holder' => JText::_('FIELD_INVALID_ACCOUNT_HOLDER'),
		'field_invalid_bank_code'     => JText::_('FIELD_INVALID_BANK_CODE')
		);
		$this->code_arr = json_encode($this->code_arr);
		$this->public_key = $this->_getParam('public_key');
		$this->private_key = $this->_getParam('private_key');
		$this->sandbox = $this->_getParam('sandbox');
		$document = JFactory::getDocument();

		if ($this->sandbox == '0')
		{
		$t = 'true';
		}
		else
		{
		$t = 'false';
		}

		$document->addScriptDeclaration('
		var PAYMILL_PUBLIC_KEY = "' . $this->public_key . '";
		var PAYMILL_TEST_MODE  = ' . $t . ';
		jQuery("a#opc-payment-button").css("display", "none");
		function ChangeDropdowns(value)
		{
		   if(value=="cc")
		   {
			   jQuery("#cc").css("display", "block");
			   jQuery("#bank").css("display", "none");
		   }else if(value=="dc")
		   {
			   jQuery("#cc").css("display", "none");
			   jQuery("#bank").css("display", "block");
		   }
		}
		function submitme()
		{
				var payment_type = jQuery("#payment_type").val();
				if(payment_type == "cc")
				{
					try {
						paymill.createToken({
							number:     jQuery(" .paymill-card-number").val(),
							exp_month:  jQuery(" .paymill-card-expiry-month").val(),
							exp_year:   jQuery(".paymill-card-expiry-year").val(),
							cvc:        jQuery(" .paymill-card-cvc").val(),
							cardholder: jQuery(" .paymill-card-holdername").val(),
							amount: jQuery("#card-tds-form .paymill-card-amount").val(),
							currency: jQuery("#card-tds-form .paymill-card-currency").val(),

						}, PaymillResponseHandler);
					} catch(e) {
						 jQuery(".payment-errors").text(e);
						logResponse(e.message);
					}
				}
				else
				{
					try {
						paymill.createToken({
							number: jQuery(".paymill-debit-number").val(),
							bank:  jQuery(".paymill-debit-bank").val(),
							country:   jQuery(".paymill-debit-country").val(),
							accountholder: jQuery(".paymill-card-holdername").val()
						}, PaymillResponseHandler);
					} catch(e) {
						 jQuery(".payment-errors").text(e);
						logResponse(e.message);
					}
					 jQuery("#debit-form .paymill-debit-bank").bind("paste cut keydown",function(e) {
						var that = this;
						setTimeout(function() {
								paymill.getBankName(jQuery(that).val(), function(error, result) {
								error ? logResponse(error.apierror) : jQuery(".debit-bankname").val(result);
									});
								}, 200);
						});
				}
        }
        function PaymillResponseHandler(error, result) {
			error ? logResponse(error.apierror) : logResponse(result.token);
			if (error) {
				var jason_error = [' . $this->code_arr . '];
				//var slab = jQuery.parseJSON(jason_error);
				//console.log(jason_error);
				jQuery.each(jason_error[0], function(index, element) {
					if(index == error.apierror){
						var version = "' . JVERSION . '";
						//alert(version);
						if(version >= "3.0")
						{
							jQuery(".paymill-payment-errors").addClass("alert alert-error");
						}
						else
						{
							jQuery(".paymill-payment-errors").addClass("error");
						}
						//jQuery(".payment-errors").addClass("alert alert-error");
						jQuery("#paymill_button").removeAttr("disabled");
						jQuery(".payment-errors").text(element);
					}
				});

			}
			else
			{
					jQuery("#token12").val(result.token);
					jQuery("#opc-payment-button").click();

			}
        }
        function logResponse(res)
        {
            // create console.log to avoid errors in old IE browsers
            if (!window.console) console = {log:function(){}};
            //console.log(res);
            if(PAYMILL_TEST_MODE)
            jQuery(".debug").text(res).show().fadeOut(3000);
        }');

		$document->addscript("https://bridge.paymill.com/");
	}

/************************************
 * Note to 3pd:
 *
 * The methods between here
 * and the next comment block are
 * yours to modify
 *
 ************************************/

/**
 * Prepares the payment form
 * and returns HTML Form to be displayed to the user
 * generally will have a message saying, 'confirm entries, then click complete order'
 *
 * Submit button target for onsite payments & return URL for offsite payments should be:
 * index.php?option=com_tienda&view=checkout&task=confirmPayment&orderpayment_type=xxxxxx
 * where xxxxxxx = $_element = the plugin's filename
 *
 * @param   array  $data  form post data.
 *
 * @return  void
 *
 * @see process()
 * */
public function _prePayment( $data )
{
	$jinput = JFactory::getApplication()->input;

	// Prepare the payment form
	$vars->order_id = $data['order_id'];
	$vars->orderpayment_id = $data['orderpayment_id'];
	$vars->orderpayment_amount = $data['orderpayment_amount'];
	$vars->orderpayment_type = $this->_element;

	$vars->cardholder = $jinput->get("cardholder");
	$vars->payment_mode = $jinput->get("payment_mode");

	// Credit card
	$vars->cardnum = $jinput->get("cardnum");
	$month = $jinput->get("month");
	$year = $jinput->get("year");
	$card_exp = $month . ' / ' . $year;
	$vars->cardexp = $card_exp;

	$vars->cardcvv = $jinput->get("cardcvv");
	$vars->cardnum_last4 = substr($jinput->get("cardnum"), -4);

	// Debit card
	$vars->accnum = $jinput->get("accnum");
	$vars->accnum_last4 = substr($jinput->get("accnum"), -4);
	$vars->banknum = $jinput->get("banknum");
	$vars->country = $jinput->get("country");

	// Token
	$vars->token12 = $jinput->get("token12");

	// Lets check the values submitted
	$html = $this->_getLayout('prepayment', $vars);

	return $html;
}

/**
 * Processes the payment form
 * and returns HTML to be displayed to the user
 * generally with a success/failed message
 *
 * @param   array  $data  form post data.
 *
 * @return string
 *
 * @see process()
 * */

public function _postPayment( $data )
{
	// Process the payment
	$vars = new JObject;
	$jinput = JFactory::getApplication()->input;
	$app = JFactory::getApplication();
	$paction = $jinput->get('paction');

	switch ($paction)
	{
		case 'process_recurring':
			// TODO Complete this
			$app->close();
		break;
		case 'process':
			$vars->message = $this->_process();
			$html = $this->_getLayout('message', $vars);
		break;
		default:
			$vars->message = JText::_('COM_TIENDA_INVALID_ACTION');
			$html = $this->_getLayout('message', $vars);
		break;
	}

	return $html;
}

/**
 * Prepares variables and
 * Renders the form for collecting payment info
 *
 * @param   array  $data  form post data.
 *
 * @return unknown_type
 *
 * @see process()
 * */

	public function _renderForm($data)
	{
		$vars = new JObject;
		$vars->prepop = array();
		$html = $this->_getLayout('form', $vars);

		return $html;
	}

/**
 * Verifies that all the required form fields are completed
 * if any fail verification, set
 * $object->error = true
 * $object->message .= '<li>x item failed verification</li>'
 *
 * @param   array  $submitted_values  post data
 *
 * @return unknown_type
 *
 * @see process()
 */

public function _verifyForm($submitted_values)
{
	$object = new JObject;
	$object->error = false;
	$object->message = '';
	$user = JFactory::getUser();

	foreach ($submitted_values as $key => $value)
	{
		switch ($key)
		{
			case "cardtype":
				if (!isset($submitted_values[$key]) || !JString::strlen($submitted_values[$key]))
				:
				{
					$object->error = true;
					$object->message . = "<li>" . JText::_('COM_TIENDA_PAYMILL_CARD_TYPE_INVALID') . "</li>";
				}
				endif;
			break;
			case "cardnum":
				if (!isset($submitted_values[$key]) || !JString::strlen($submitted_values[$key]))
				:
				{
					$object->error = true;
					$object->message . = "<li>" . JText::_('COM_TIENDA_PAYMILL_CARD_NUMBER_INVALID') . "</li>";
				}
				endif;
			break;
			case "cardexp":
				if (!isset($submitted_values[$key]) || JString::strlen($submitted_values[$key]) != 4)
				:
				{
					$object->error = true;
					$object->message . = "<li>" . JText::_('COM_TIENDA_PAYMILL_CARD_EXPIRATION_DATE_INVALID') . "</li>";
				}
				endif;
			break;
			case "cardcvv":
				if (!isset($submitted_values[$key]) || !JString::strlen($submitted_values[$key]))
				:
				{
					$object->error = true;
					$object->message . = "<li>" . JText::_('COM_TIENDA_PAYMILL_CARD_CVV_INVALID') . "</li>";
				}
				endif;
			break;
			default:
			break;
		}
	}

	return $object;
}

/**
 * Gets an existing user or creates a new one
 *
 * @param   array  $submitted_values  Data for a new user
 * @param   int    $user_id           Existing user id (optional)
 *
 * @return JUser object
 *
 * @access protected
 *
 * */

public function _getUser( $submitted_values, $user_id = 0 )
{
	$config = Tienda::getInstance();

	if ($user_id)
	{
		$user = JFactory::getUser($user_id);
	}
	else
	{
		$user = JFactory::getUser();
	}

	if ($user->id)
	:
	{
		return $user;
	}
	endif;

	Tienda::load('TiendaHelperUser', 'helpers.user');

	$newuser_email = $submitted_values['email'];

	// Create user from email
	jimport('joomla.user.helper');
	$details['name']        = $newuser_email;
	$details['username']    = $newuser_email;
	$details['email']       = $newuser_email;
	$details['password']    = JUserHelper::genRandomPassword();
	$details['password2']   = $details['password'];
	$details['block']       = $config->get('block_automatically_registered') ? '1' : '0';

	if ($user = TiendaHelperUser::createNewUser($details))
	{
		if ( ! $config->get('block_automatically_registered'))
		{
			// Login the new user
			$login = TiendaHelperUser::login($details, '1');
		}

		// Indicate that user was registed by AS automatically
		$user->set('automatically_registered', true);
	}

	return $user;
}

/**
 * Processes the payment
 *
 * This method process only real time (simple and subscription create) payments
 * The scheduled recurring payments are processed by the corresponding method
 *
 * @return string
 *
 * @access protected
 *
 * */
public function _process()
{
	/*
	 * perform initial checks
	 */
	if (!JRequest::checkToken())
	{
		return $this->_renderHtml(JText::_('COM_TIENDA_INVALID_TOKEN'));
	}

	$data = JRequest::get('post');

	// Get order information
	JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tienda/tables');
	$order = JTable::getInstance('Orders', 'TiendaTable');
	$order->load($data['order_id']);

	if (empty($order->order_id))
	{
		return JText::_('COM_TIENDA_AUTHORIZEDOTNET_MESSAGE_INVALID_ORDER');
	}

	// Prepare the form for submission to auth.net
	$process_vars = $this->_getProcessVars($data);

	return $this->_processSimplePayment($process_vars);
}

/**
 * Prepares parameters for the payment processing
 *
 * @param   object  $data  Post variables
 *
 * @return array
 *
 * @access protected
 * */
private function _getProcessVars($data)
{
	JModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tienda/models');
	JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tienda/tables');
	Tienda::load('TiendaHelperCarts', 'helpers.carts');
	Tienda::load('TiendaHelperCurrency', 'helpers.currency');
	Tienda::load('TiendaHelperBase', 'helpers._base');
	$items = TiendaHelperCarts::getProductsInfo();
	$orderTable = JTable::getInstance('Orders', 'TiendaTable');

foreach ($items as $item)
{
	$orderTable->addItem($item);
}

	$items = $orderTable->getItems();
	$orderTable->calculateTotals();
	$amount = $orderTable->order_total;
	$currency = TiendaHelperCurrency::getCurrentCurrency();
	$currency = TiendaHelperCurrency::load($currency);
	$currency_code = $currency->currency_code;
	$jinput   = JFactory::getApplication()->input;
	$component  = $jinput->getCmd('option'); 	
	$xml = JFactory::getXML(JPATH_SITE.'/administrator/components/com_tienda/manifest.xml');
	$comversion=(string)$xml->version;	
	$paymillxml = JFactory::getXML(JPATH_SITE.'/plugins/tienda/payment_paymill/payment_paymill.xml');	
	$pluginversion=(string)$paymillxml->version;	
	$source = $pluginversion.'_'.$component.'_'.$comversion; 
	$params = array(
			'amount'      => ($amount * 100), // Amount *100
			'currency'    => $currency_code ,   // ISO 4217
			'token'       => $data['token'],
			'description' => $data.'/'.$source
			);

	return $params;
}

/**
 * Simple logger
 *
 * @param   string  $text  data
 * @param   string  $type  message
 *
 * @return void
 * 
 * @access protected
 * */

public function _log($text, $type = 'message')
{
	if ($this->_isLog)
	{
		$file = JPATH_ROOT . "/cache/{$this->_element}.log";
		$date = JFactory::getDate();

		$f = fopen($file, 'a');
		fwrite($f, "\n\n" . $date->toFormat('%Y-%m-%d %H:%M:%S'));
		fwrite($f, "\n" . $type . ': ' . $text);
		fclose($f);
	}
}

/**
 * Processes a simple (non-recurring payment)
 * by sending data to auth.net and interpreting the response
 * and managing the order as required
 *
 * @param   array  $authnet_values  authnet_values
 *
 * @return string
 *
 * @access protected
 *
 * */

private function _processSimplePayment($authnet_values)
{
	require "plugins/tienda/payment_paymill/payment_paymill/lib/Services/Paymill/Transactions.php";
	define('PAYMILL_API_HOST', 'https://api.paymill.com/v2/');

	// FROM PAYMILL PLUGIN BACKEND
	define('PAYMILL_API_KEY', $this->private_key);
	set_include_path(implode(PATH_SEPARATOR, array(realpath(realpath(dirname(__FILE__)) . '/lib'),get_include_path(),)));
	$transactionsObject = new Services_Paymill_Transactions(PAYMILL_API_KEY, PAYMILL_API_HOST);
	$transactionResponse = $transactionsObject->create($authnet_values);
	$evaluateResponse = $this->_evaluateSimplePaymentResponse($transactionResponse, $authnet_values);
	$this->_log($transactionResponse);

	return $evaluateResponse;
}

/**
 * Proceeds the simple payment
 *
 * @param   string  $resp              resp
 * @param   array   $submitted_values  submitted_values
 * 
 * @return   object  Message object
 * 
 * @access protected
 * */

private function _evaluateSimplePaymentResponse($resp, $submitted_values)
{
	$send_email = false;
	$object = new JObject;
	$object->message = '';
	$html = '';
	$errors = array();
	$payment_status = '0';
	$order_status = '0';
	$payment_status = JText::_('J2STORE_INCOMPLETE');
	$user = JFactory::getUser();
	$token = $submitted_values['token'];

	if ($token)
	{
		if (empty($resp['status']))
		{
				if ($resp['error'])
				{
					$payment_status = JText::_('J2STORE_DECLINED');
					$errors[] = $resp['error'];
				}
		}
		else
		{
			if ($resp['status'] == 'closed')
			{
						$payment_status = '1';
						$subs_status = '1';
			}
			elseif ($resp['status'] == 'Pending')
			{
						$payment_status = '0';
						$order_status = '0';
						$errors[] = $resp['status'];
			}
			elseif ($resp['status'] == 'failed')
			{
						$payment_status = '0';
						$order_status = '0';
						$errors[] = $resp['status'];
			}
			else
			{
						$payment_status = '0';
						$order_status = '0';
						$errors[] = JText::_('COM_TIENDA_CARD_WAS_DECLINED');
			}
		}
	}
	else
	{
		$payment_status = '0';
		$order_status = '0';
		$errors[] = JText::_('COM_TIENDA_CARD_WAS_DECLINED');
	}

	// Orderpayment_id is always in this part of the response
	$orderpayment_id = $submitted_values['description']['orderpayment_id'];

	JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tienda/tables');
	$orderpayment = JTable::getInstance('OrderPayments', 'TiendaTable');
	$orderpayment->load($orderpayment_id);

	if (empty($orderpayment->order_id))
	{
		// TODO fail
	}

	$orderpayment->transaction_details  = $resp;

	if (empty($resp['id']))
	{
		$payment_status = '0';
		$order_status = '0';
		$errors[] = JText::_('COM_TIENDA_CARD_WAS_DECLINED');
	}
	else
	{
		$orderpayment->transaction_id = $resp['id'];
	}

	if (empty($resp['status']))
	{
		$payment_status = '0';
		$order_status = '0';
		$errors[] = JText::_('COM_TIENDA_CARD_WAS_DECLINED');
	}
	else
	{
		$orderpayment->transaction_status = $resp['status'];
	}

	// Set the order's new status and update quantities if necessary
	Tienda::load('TiendaHelperOrder', 'helpers.order');
	Tienda::load('TiendaHelperCarts', 'helpers.carts');
	$order = JTable::getInstance('Orders', 'TiendaTable');
	$order->load($orderpayment->order_id);

	if (count($errors))
	{
		// If an error occurred
		$order->order_state_id = $this->params->get('failed_order_state', '10');
	}
	else
	{
		// PAYMENT RECEIVED
		$order->order_state_id = $this->params->get('payment_received_order_state', '17');

		// Do post payment actions
		$setOrderPaymentReceived = true;

		// Send email
		$send_email = true;
	}

	// Save the order
	if (!$order->save())
	{
		$errors[] = $order->getError();
	}

	// Save the orderpayment
	if (!$orderpayment->save())
	{
		$errors[] = $orderpayment->getError();
	}

	if (!empty($setOrderPaymentReceived))
	{
		$this->setOrderPaymentReceived($orderpayment->order_id);
	}

	if ($send_email)
	{
		// Send notice of new order
		Tienda::load("TiendaHelperBase", 'helpers._base');
		$helper = TiendaHelperBase::getInstance('Email');
		$model = Tienda::getClass("TiendaModelOrders", "models.orders");
		$model->setId($orderpayment->order_id);
		$order = $model->getItem();
		$helper->sendEmailNotices($order, 'new_order');
	}

	if (empty($errors))
	{
		$return = JText::_('PAYMENT_SUCCESS');

		return $return;
	}

	if (!empty($errors))
	{
		$string = implode("\n", $errors);
		$return = "<div class='note_pink'>" . $string . "</div>";

		return $return;
	}
}
}
