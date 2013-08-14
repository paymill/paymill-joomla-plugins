<?php
/**
 * @package     RedSHOP
 * @subpackage  Plugin
 *
 * @copyright   Copyright (C) 2005 - 2013 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');
$lang = & JFactory::getLanguage();
$lang->load('plg_redshop_payment_paymill', JPATH_ADMINISTRATOR);

class plgRedshop_paymentrs_payment_paymill extends JPlugin
{
	public $_table_prefix = null;

	public function plgRedshop_paymentrs_payment_paymill(&$subject)
	{
		// Load plugin parameters
		parent::__construct($subject);
		$this->_table_prefix = '#__redshop_';
		//error code in api error
		$this->code_arr = array (
		'internal_server_error'       => JText::_('INTERNAL_SERVER_ERROR'),
		'invalid_public_key'    	  => JText::_('INVALID_PUBLIC_KEY'),
		'unknown_error'               => JText::_('UNKNOWN_ERROR'),	
		'3ds_cancelled'               => JText::_('3DS_CANCELLED'),
		'field_invalid_card_number'   => JText::_('FIELD_INVALID_CARD_NUMBER'),
		'field_invalid_card_exp_year' => JText::_('FIELD_INVALID_CARD_EXP_YEAR'),
		'field_invalid_card_exp_month'=> JText::_('FIELD_INVALID_CARD_EXP_MONTH'),
		'field_invalid_card_exp'      => JText::_('FIELD_INVALID_CARD_EXP'),
		'field_invalid_card_cvc'      => JText::_('FIELD_INVALID_CARD_CVC'),
		'field_invalid_card_holder'   => JText::_('FIELD_INVALID_CARD_HOLDER'),
		'field_invalid_amount_int'    => JText::_('FIELD_INVALID_AMOUNT_INT'),
		'field_invalid_amount'        => JText::_('FIELD_INVALID_AMOUNT'),
		'field_invalid_currency'      => JText::_('FIELD_INVALID_CURRENCY'),
		'field_invalid_account_number'=> JText::_('FIELD_INVALID_AMOUNT_NUMBER'),
		'field_invalid_account_holder'=> JText::_('FIELD_INVALID_ACCOUNT_HOLDER'),
		'field_invalid_bank_code'     => JText::_('FIELD_INVALID_BANK_CODE'),
	
		);
		JPluginHelper::getPlugin('redshop_payment', 'onPrePayment');
		$this->_plugin = JPluginHelper::getPlugin('redshop_payment', 'rs_payment_paymill');
		$this->_params = new JRegistry($this->_plugin->params);
	}

	/**
	 * Plugin method with the same name as the event will be called automatically.
	 */
	public function onPrePayment($element, $data)
	{
		if ($element != 'rs_payment_paymill')
		{
			return;
		}

		if (empty($plugin))
		{
			$plugin = $element;
		}

		$app = JFactory::getApplication();
		$paymentpath = JPATH_SITE . '/plugins/redshop_payment/' . $plugin . DS . $plugin . '/form.php';
		include $paymentpath;
	}

	public function onNotifyPaymentrs_payment_paymill($element, $request)
	{
		if ($element != 'rs_payment_paymill')
		{
			return;
		}
		//API HOST KEY
		define('PAYMILL_API_HOST', 'https://api.paymill.com/v2/');
		//FROM PAYMILL PLUGIN BACKEND 
		define('PAYMILL_API_KEY', $this->_params->get("private_key"));
		set_include_path(implode(PATH_SEPARATOR, array(realpath(realpath(dirname(__FILE__)) . '/lib'),get_include_path(),)));
		//CREATED TOKEN 
		$token = $request["token"];
		
		if ($token) 
		{		// access lib folder
				require "rs_payment_paymill/lib/Services/Paymill/Transactions.php";
				//pass api key and private key to Services_Paymill_Transactions function
				$transactionsObject = new Services_Paymill_Transactions(PAYMILL_API_KEY, PAYMILL_API_HOST);

				$params = array(
				'amount'      => ($request["card-amount"] *100), //amount *100
				'currency'    => $request["card-currency"],   // ISO 4217
				'token'       => $token,
				'description' => 'Test Transaction'
				);
				
				$transaction = $transactionsObject->create($params);
				//echo $transaction['error'];
				//print_r($transaction);die();
				$status = $transaction['status'];
				//if error find 
				if($transaction['error'])
				{
					$values->order_status_code = 'error';
					$values->order_payment_status_code = 'Unpaid';
					$values->log = JText::_('COM_REDSHOP_ORDER_NOT_PLACED');
					$values->msg = JText::_('COM_REDSHOP_ORDER_NOT_PLACED');
					$values->transaction_id = '';
					$values->order_id =  $request["order_id"];
					return $values;
				}
				else
				{
					$status = $transaction['status'];
					if($status == 'closed')
					{
						$values->order_status_code = $status;
						$values->order_payment_status_code = 'paid';
						$values->log = JText::_('COM_REDSHOP_ORDER_PLACED');
						$values->msg = JText::_('COM_REDSHOP_ORDER_PLACED');
						$values->transaction_id = $transaction['id'];
						$values->order_id =  $request["order_id"];
					}
					else if($status == 'pending')
					{
						$values->order_status_code = $status;
						$values->order_payment_status_code = 'paid';
						$values->log = JText::_('COM_REDSHOP_ORDER_PLACED');
						$values->msg = JText::_('COM_REDSHOP_ORDER_PLACED');
						$values->transaction_id = $transaction['id'];
						$values->order_id =  $request["order_id"];
					}
					else if($status == 'failed')
					{
						$values->order_status_code = $transaction['error'];
						$values->order_payment_status_code = 'paid';
						$values->log = JText::_('COM_REDSHOP_ORDER_PLACED');
						$values->msg = JText::_('COM_REDSHOP_ORDER_PLACED');
						$values->transaction_id = $transaction['id'];
						$values->order_id =  $request["order_id"];
					}
					else
					{
						$values->order_status_code = $transaction['error'];
						$values->order_payment_status_code = 'Unpaid';
						$values->log = JText::_('COM_REDSHOP_ORDER_NOT_PLACED');
						$values->msg = JText::_('COM_REDSHOP_ORDER_NOT_PLACED');
					}
					return $values;
				}
			}
			else
			{
					$values->order_status_code = 'error';
					$values->order_payment_status_code = 'Unpaid';
					$values->log = JText::_('COM_REDSHOP_ORDER_NOT_PLACED');
					$values->msg = JText::_('COM_REDSHOP_ORDER_NOT_PLACED');
					$values->transaction_id = '';
					$values->order_id =  $request["order_id"];
					return $values;
				
			}
	}

	/*public function getparametersgetparameters($payment)
	{
		$db = JFactory::getDBO();
		$sql = "SELECT * FROM #__extensions WHERE `element`='" . $payment . "'";
		$db->setQuery($sql);
		$params = $db->loadObjectList();

		return $params;
	}*/

	
}
