<?php
/**
 * --------------------------------------------------------------------------------
 * Payment Plugin - Paymill
 * --------------------------------------------------------------------------------
 * @package     Joomla!_2.5x_And_3.0X
 * @subpackage  J2 Store
 * @author      Techjoomla <support@techjoomla.com>
 * @copyright   Copyright (c) 2010 - 2015 Techjoomla . All rights reserved.
 * @license     GNU/GPL license: http://www.techjoomla.com/copyleft/gpl.html
 * @link        http://techjoomla.com
 * --------------------------------------------------------------------------------
 * 
 * */

// No direct access

defined('_JEXEC') or die('Restricted access');

require_once JPATH_ADMINISTRATOR . '/components/com_j2store/library/plugins/payment.php';
require_once JPATH_SITE . '/components/com_j2store/helpers/utilities.php';
jimport('joomla.application.component.helper');

/**
	* plgJ2StorePayment_paymill class.
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

class PlgJ2StorePaymentpaymill extends J2StorePaymentPlugin
{
/**
 * @var $_element  string  Should always correspond with the plugin's filename, 
 * forcing it to be unique 
 * */

private $_element = 'payment_paymill';

private $login_id = '';

private $tran_key = '';

private $_isLog = false;

/**
	* Constructs a PHP_CodeSniffer object.
	*
	* @param   string  $subject  The number of spaces each tab represents.
	* @param   string  $config   The charset of the sniffed files.
	*
	* @see process()
 * */

private function __construct($subject, $config)
{
		parent::__construct($subject, $config);
		$this->loadLanguage('', JPATH_ADMINISTRATOR);
		$params = JComponentHelper::getParams('com_j2store');
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
		'field_invalid_bank_code'     => JText::_('FIELD_INVALID_BANK_CODE'),
		);

		private $this->public_key = $this->_getParam('public_key');
		private $this->private_key = $this->_getParam('private_key');
}

/**
	* set currency and amount.
	*
	* @param   array  $data  form post data.
	*
	* @return  string   HTML to display
	* @return  void
	*
	* @see process()
 * */

	public function _prePayment( $data )
	{
		$jinput = JFactory::getApplication()->input;

		// Prepare the payment form
		$vars = new JObject;

		// Now we have everthing in the data. We need to generate some more paymill specific things.

		// Lets get vendorname

		$vars->url = JRoute::_("index.php?option=com_j2store&view=checkout");
		$vars->order_id = $data['order_id'];
		$vars->orderpayment_id = $data['orderpayment_id'];
		$vars->orderpayment_type = $this->_element;

		$vars->cardholder = $jinput->get("cardholder", '', 'filter');
		$vars->payment_mode = $jinput->get("payment_mode", '', 'filter');

		// Cerdit card
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
	* @return  string   HTML to display
	* @return  void
	*
	* @see process()
 * */

	public function _postPayment( $data )
	{
		// Process the payment
		$jinput = JFactory::getApplication()->input;
		$vars = new JObject();
		$app = JFactory::getApplication;
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
				$html . = $this->_displayArticle();
				break;
			default:
				$vars->message = JText::_('J2STORE_PAYMILL_MESSAGE_INVALID_ACTION');
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
	* @return  string   unknown_type.
	* 
	* @return  void
	*
	* @see process()
 * */

	public function _renderForm($data)
	{
		$vars = new JObject();
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
	* @param   array  $submitted_values  form post data.
	*
	* @return  string   unknown_type.
	* 
	* @return  void
	*
	* @see process()
 * */

	public function _verifyForm($submitted_values)
	{
		$object = new JObject();
		$object->error = false;
		$object->message = '';

		$user = JFactory::getUser();

		foreach ($submitted_values as $key => $value)
		{
			switch ($key)
			{
				case "cardholder":
					if (!isset($submitted_values[$key]) || !JString::strlen($submitted_values[$key]))
					:
					$object->error = true;
					$object->message . = "<li>" . JText::_("J2STORE_SAGEPAY_MESSAGE_CARD_HOLDER_NAME_REQUIRED") . "</li>";
					endif;
					break;
				case "cardtype":
					if (!isset($submitted_values[$key]) || !JString::strlen($submitted_values[$key]))
					:
					{
						$object->error = true;
						$object->message . = "<li>" . JText::_("J2STORE_SAGEPAY_MESSAGE_CARD_TYPE_INVALID") . "</li>";
					}
					endif;
					break;
				case "cardnum":
					if (!isset($submitted_values[$key]) || !JString::strlen($submitted_values[$key]))
					:
					{
						$object->error = true;
						$object->message . = "<li>" . JText::_("J2STORE_SAGEPAY_MESSAGE_CARD_NUMBER_INVALID") . "</li>";
					}
					endif;
					break;
				case "month":
					if (!isset($submitted_values[$key]) || !JString::strlen($submitted_values[$key]))
					:
					{
						$object->error = true;
						$object->message . = "<li>" . JText::_("J2STORE_SAGEPAY_MESSAGE_CARD_EXPIRATION_DATE_INVALID") . "</li>";
					}
					endif;
					break;
				case "year":
					if (!isset($submitted_values[$key]) || !JString::strlen($submitted_values[$key]))
					:
					{
						$object->error = true;
						$object->message . = "<li>" . JText::_("J2STORE_SAGEPAY_MESSAGE_CARD_EXPIRATION_DATE_INVALID") . "</li>";
					}
					endif;
					break;
				case "cardcvv":
					if (!isset($submitted_values[$key]) || !JString::strlen($submitted_values[$key]))
					:
					{
						$object->error = true;
						$object->message . = "<li>" . JText::_("J2STORE_SAGEPAY_MESSAGE_CARD_CVV_INVALID") . " </li>";
					}
					endif;
					break;
					default:
					break;
			}
		}
	}

/**
	* Processes the payment
	* This method process only real time (simple) payments
	*
	* @return  string   unknown_type.
	* 
	* @return  string
	*
	* @access protected
 * */

	public function _process()
	{
		if (! JRequest::checkToken())
		:
		{
			return $this->_renderHtml(JText::_('J2STORE_PAYMILL_INVALID_TOKEN'));
		}
		endif;
		$jinput = JFactory::getApplication()->input;
		$data = $jinput->get('post');

		// Get order information
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_j2store/tables');
		$order = JTable::getInstance('Orders', 'Table');
		$order->load($data['orderpayment_id']);

		// Check for exisiting things
		if (empty($order->order_id))
		:
		{
			return JText::_('J2STORE_PAYMILL_INVALID_ORDER');
		}
		endif;

		// Prepare the form for submission to sage pay
		$process_vars = $this->_getProcessVars($data);

		return $this->_processSimplePayment($process_vars);
	}

/**
 * Prepares parameters for the payment processing
 * 
 * @param   object  $data  Post variables
 * 
 * @return   array
 * 
 * @access protected
 * */

	private function _getProcessVars($data)
	{
		require_once JPATH_SITE . '/components/com_j2store/helpers/cart.php';
		$access = new J2StoreHelperCart();
		$amount = $access->getTotal();
		require_once JPATH_SITE . '/components/com_j2store/helpers/utilities.php';

		$J2StoreUtilities = new J2StoreUtilities();
		$total_amount = $J2StoreUtilities->number($amount);
		$jinput   = JFactory::getApplication()->input;
		$component  = $jinput->getCmd('option'); 		
		$xml = JFactory::getXML(JPATH_SITE.'/administrator/components/com_j2store/manifest.xml');
		$comversion=(string)$xml->version;	
		$paymillxml = JFactory::getXML(JPATH_SITE.'/plugins/j2store/payment_paymill/payment_paymill.xml');	
		$pluginversion=(string)$paymillxml->version;	
		$source = $pluginversion.'_'.$component.'_'.$comversion; 

		$user = JFactory::getUser();
		$j2store_params = JComponentHelper::getParams('com_j2store');
		$currency_code = $j2store_params->get('currency_code');
		$params = array(
				'amount'      => ($total_amount * 100), // Amount *100
				'currency'    => $currency_code ,   // ISO 4217
				'token'       => $data['token'],
				'description' => $data,
				'source'      => $source
				);

		return $params;
	}

/**
	* Simple logger 
	* 
	* @param   string  $text  text
	* @param   string  $type  message
	* 
	* @return void
	* 
	* @access protected
 * */

	public function _log($text, $type = 'message')
	{
		if ($this->_isLog)
		:
		{
			$file = JPATH_ROOT . "/cache/{$this->_element}.log";
			$date = JFactory::getDate();

			$f = fopen($file, 'a');
			fwrite($f, "\n\n" . $date->toFormat('%Y-%m-%d %H:%M:%S'));
			fwrite($f, "\n" . $type . ': ' . $text);
			fclose($f);
		}
		endif;
	}

/**
 * Processes a simple (non-recurring payment)
 * by sending data to auth.net and interpreting the response
 * and managing the order as required
 *
 * @param   array  $params  Parameters
 * 
 * @return string
 * 
 * @access protected
 * */

	private function _processSimplePayment($params)
	{
		require "plugins/j2store/payment_paymill/payment_paymill/lib/Services/Paymill/Transactions.php";
		define('PAYMILL_API_HOST', 'https://api.paymill.com/v2/');

		// FROM PAYMILL PLUGIN BACKEND
		define('PAYMILL_API_KEY', $this->private_key);
		set_include_path(implode(PATH_SEPARATOR, array(realpath(realpath(dirname(__FILE__)) . '/lib'),get_include_path(),)));
		$transactionsObject = new Services_Paymill_Transactions(PAYMILL_API_KEY, PAYMILL_API_HOST);
		$transactionResponse = $transactionsObject->create($params);
		$evaluateResponse = $this->_evaluateSimplePaymentResponse($transactionResponse, $params);
		$this->_log($transactionResponse);

		return $evaluateResponse;
	}

/**
 * Proceeds the simple payment
 * 
 * @param   string  $resp              resp
 * @param   array   $submitted_values  submitted value list
 * 
 * @return   object  Message  object
 * 
 * @access protected
 * */

	private function _evaluateSimplePaymentResponse($resp, $submitted_values)
	{
		$object = new JObject();
		$object->message = '';
		$html = '';
		$errors = array();

		$payment_status = JText::_('J2STORE_INCOMPLETE');
		$user = JFactory::getUser();
		$token = $submitted_values['token'];

		if ($resp['status'] == 'closed')
		:
				{
					$payment_status = JText::_('J2STORE_COMPLETED');
				}
		endif;

		if ($token)
		{
				if ($resp['error'])
				{
					$payment_status = JText::_('J2STORE_DECLINED');
					$errors[] = $resp['error'];
				}
				elseif ($resp['status'] == 'closed')
				{
					$payment_status = JText::_('J2STORE_COMPLETED');
				}
				elseif ($resp['status'] == 'Pending')
				{
					$payment_status = JText::_('J2STORE_PENDING');
					$errors[] = JText::_("J2STORE_PAYMILL_ERROR_PROCESSING_PAYMENT");
				}
				elseif ($resp['status'] == 'failed')
				{
					$payment_status = JText::_('J2STORE_FAILED');
					$errors[] = JText::_("J2STORE_PAYMILL_ERROR_PROCESSING_PAYMENT");
				}
				else
				{
					$payment_status = JText::_('J2STORE_ERROR');
					$order_status = JText::_('J2STORE_INCOMPLETE');
					$errors[] = JText::_("J2STORE_PAYMILL_ERROR_PROCESSING_PAYMENT");
				}
		}
		else
		{
			$payment_status = JText::_('J2STORE_ERROR');
			$order_status = JText::_('J2STORE_INCOMPLETE');
			$errors[] = JText::_("J2STORE_PAYMILL_ERROR_PROCESSING_PAYMENT");
		}

/** End if token
		// Evaluate a typical response from sage pay
		// =======================
		// verify & create payment
		// =======================
 * */
			// Check that payment amount is correct for order_id
			JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_j2store/tables');
			$orderpayment = JTable::getInstance('Orders', 'Table');
			$orderpayment->load($submitted_values['description']['orderpayment_id']);

			if (empty($orderpayment->order_id))
			:
			{
				// TODO fail
			}
			endif;
			$orderpayment->transaction_details  = $this->_getFormattedTransactionDetails($resp);
			$orderpayment->transaction_id       = $resp['id'];
			$orderpayment->transaction_status   = $payment_status;

			// Set a default status to it
			$orderpayment->order_state = JText::_('J2STORE_PENDING');

			// PENDING
			$orderpayment->order_state_id = 4;

			// Set the order's new status and update quantities if necessary
			if (count($errors))
			{
				// If an error occurred
				$orderpayment->order_state  = trim(JText::_('J2STORE_FAILED'));

				// FAILED
				$orderpayment->order_state_id = 3;
			}
			else
			{
				$orderpayment->order_state  = trim(JText::_('J2STORE_CONFIRMED'));

				// Payment received and CONFIRMED
				$orderpayment->order_state_id = 1;

				// CONFIRMED
				JLoader::register('J2StoreHelperCart', JPATH_SITE . '/components/com_j2store/helpers/cart.php');

				// Remove items from cart
				J2StoreHelperCart::removeOrderItems($orderpayment->id);
			}

			// Save the order

			if (!$orderpayment->save())
			:
			{
				$errors[] = $orderpayment->getError();
			}
			endif;

			if (empty($errors))
			{
				// Let us inform the user that the payment is successful
				require_once JPATH_SITE . '/components/com_j2store/helpers/orders.php';

				$return = JText::_("J2STORE_PAYMILL_MESSAGE_PAYMENT_SUCCESS");

				return $return;
			}
			else
			{
				$error = count($errors) ? implode("\n", $errors) : '';
			}

			return count($errors) ? implode("\n", $errors) : '';
	}

/**
 * Proceeds the simple payment
 * 
 * @param   array  $data  data
 * 
 * @return   object  Message  object
 * 
 * @access protected
 * */

	private function _getFormattedTransactionDetails($data)
	{
		$separator = "\n";
		$formatted = array();

		foreach ($data as $key => $value)
		{
			if ($key != 'view' && $key != 'layout')
			{
				$formatted[] = $key . ' = ' . $value;
			}
		}

		return count($formatted) ? implode("\n", $formatted) : '';
	}
}
