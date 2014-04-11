<?php
/**
 * --------------------------------------------------------------------------------
 * Payment Plugin - Paymill
 * --------------------------------------------------------------------------------
 * @package     Joomla!_2.5x_And_3.0X
 * @subpackage  Redshop
 * @author      Techjoomla <support@techjoomla.com>
 * @copyright   Copyright (c) 2010 - 2015 Techjoomla . All rights reserved.
 * @license     GNU/GPL license: http://www.techjoomla.com/copyleft/gpl.html
 * @link        http://techjoomla.com
 * --------------------------------------------------------------------------------
 * */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');
$lang = JFactory::getLanguage();
$lang->load('plg_redshop_payment_paymill', JPATH_ADMINISTRATOR);

/**
	* PlgRedshop_paymentrs_payment_paymill class.
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

class PlgRedshop_paymentrs_payment_paymill extends JPlugin
{
	var $_table_prefix = null;

/**
	* Constructs.
	*
	* @param   string  &$subject  The number of spaces each tab represents.
	*
	* @see process()
 * */

	private function __construct(&$subject)
	{
		// Load plugin parameters
		parent::__construct($subject);
		$this->_table_prefix = '#__redshop_';

		// Error code in api error
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
		JPluginHelper::getPlugin('redshop_payment', 'onPrePayment');
		$this->_plugin = JPluginHelper::getPlugin('redshop_payment', 'rs_payment_paymill');
		$this->_params = new JRegistry($this->_plugin->params);
	}

/**
	* Plugin method with the same name as the event will be called automatically.
	*
	* @param   string  $element  plugin name.
	* @param   array   $data     plugin data.
	*
	* @return  void
	*
	* @see process()
 * */

	public function onPrePayment($element, $data)
	{
		if ($element != 'rs_payment_paymill')
		:
		{
			return;
		}
		endif;

		if (empty($plugin))
		:
		{
			$plugin = $element;
		}
		endif;

		$app = JFactory::getApplication();
		$paymentpath = JPATH_SITE . '/plugins/redshop_payment/' . $plugin . DS . $plugin . '/form.php';
		include $paymentpath;
	}

/**
	* Plugin method with the same name as the event will be called automatically.
	*
	* @param   string  $element  plugin name.
	* @param   array   $request  request data.
	*
	* @return  void
	*
	* @see process()
 * */

	public function onNotifyPaymentrs_payment_paymill($element, $request)
	{
		if ($element != 'rs_payment_paymill')
		:
		{
			return;
		}
		endif;

		// API HOST KEY
		define('PAYMILL_API_HOST', 'https://api.paymill.com/v2/');

		// FROM PAYMILL PLUGIN BACKEND
		define('PAYMILL_API_KEY', $this->_params->get("private_key"));
		set_include_path(implode(PATH_SEPARATOR, array(realpath(realpath(dirname(__FILE__)) . '/lib'),get_include_path(),)));

		// CREATED TOKEN
		$token = $request["token"];
		$jinput   = JFactory::getApplication()->input;
		$component  = $jinput->getCmd('option'); 		
		$xml = JFactory::getXML(JPATH_SITE.'/administrator/components/com_redshop/redshop.xml');
		$comversion=(string)$xml->version;	
		$paymillxml = JFactory::getXML(JPATH_SITE.'/plugins/redshop_payment/rs_payment_paymill/rs_payment_paymill.xml');	
		$pluginversion=(string)$paymillxml->version;	
		$source = $pluginversion.'_'.$component.'_'.$comversion; 		
		$order_id = $request["orderid"];
		if ($token)
		{
			// Access lib folder
				require "rs_payment_paymill/lib/Services/Paymill/Transactions.php";

				// Pass api key and private key to Services_Paymill_Transactions function
				$transactionsObject = new Services_Paymill_Transactions(PAYMILL_API_KEY, PAYMILL_API_HOST);

				$params = array(
				'amount'      => ($request["card-amount"] * 100), // Amount *100
				'currency'    => $request["card-currency"],   // ISO 4217
				'token'       => $token,
				'description' => 'Order Id: '.$order_id,
				'source'       => $source
				);

				$transaction = $transactionsObject->create($params);

				$status = $transaction['status'];

				// If error find
				if ($transaction['error'])
				{
					$values->order_status_code = 'error';
					$values->order_payment_status_code = 'Unpaid';
					$values->log = JText::_('COM_REDSHOP_ORDER_NOT_PLACED');
					$values->msg = JText::_('COM_REDSHOP_ORDER_NOT_PLACED');
					$values->transaction_id = '';
					$values->order_id = $request["order_id"];
				}
				else
				{
					$status = $transaction['status'];

					if ($status == 'closed')
					{
						$values->order_status_code = $status;
						$values->order_payment_status_code = 'paid';
						$values->log = JText::_('COM_REDSHOP_ORDER_PLACED');
						$values->msg = JText::_('COM_REDSHOP_ORDER_PLACED');
						$values->transaction_id = $transaction['id'];
						$values->order_id = $request["order_id"];
					}
					elseif ($status == 'pending')
					{
						$values->order_status_code = $status;
						$values->order_payment_status_code = 'paid';
						$values->log = JText::_('COM_REDSHOP_ORDER_PLACED');
						$values->msg = JText::_('COM_REDSHOP_ORDER_PLACED');
						$values->transaction_id = $transaction['id'];
						$values->order_id = $request["order_id"];
					}
					elseif ($status == 'failed')
					{
						$values->order_status_code = $transaction['error'];
						$values->order_payment_status_code = 'paid';
						$values->log = JText::_('COM_REDSHOP_ORDER_PLACED');
						$values->msg = JText::_('COM_REDSHOP_ORDER_PLACED');
						$values->transaction_id = $transaction['id'];
						$values->order_id = $request["order_id"];
					}
					else
					{
						$values->order_status_code = $transaction['error'];
						$values->order_payment_status_code = 'Unpaid';
						$values->log = JText::_('COM_REDSHOP_ORDER_NOT_PLACED');
						$values->msg = JText::_('COM_REDSHOP_ORDER_NOT_PLACED');
					}
				}
		}
		else
		{
				$values->order_status_code = 'error';
				$values->order_payment_status_code = 'Unpaid';
				$values->log = JText::_('COM_REDSHOP_ORDER_NOT_PLACED');
				$values->msg = JText::_('COM_REDSHOP_ORDER_NOT_PLACED');
				$values->transaction_id = '';
				$values->order_id = $request["order_id"];
		}

		return $values;
	}
}
