<?php
/**
 * --------------------------------------------------------------------------------
 * Payment Plugin - Paymill
 * --------------------------------------------------------------------------------
 * @package     Joomla!_2.5x_And_3.0X
 * @subpackage  Payplan
 * @author      Techjoomla <support@techjoomla.com>
 * @copyright   Copyright (c) 2010 - 2015 Techjoomla . All rights reserved.
 * @license     GNU/GPL license: http://www.techjoomla.com/copyleft/gpl.html
 * @link        http://techjoomla.com
 * --------------------------------------------------------------------------------
 * */

defined('_JEXEC') or die('Restricted access');

/**
	* PayplansAppPaymill class.
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

class PayplansAppPaymill extends PayplansAppPayment
{
protected $_location = __FILE__;

/**
	* Over-ride to make it applicable on onPayplansControllerCreation.
	*
	* @param   array  $refObject  null.
	* @param   array  $eventName  event name blank.
	*
	* @return  void
	*
	* @see process()
 * */

public function isApplicable($refObject = null, $eventName='')
{
	// Return true for event onPayplansControllerCreation
	if ($eventName == 'onPayplansControllerCreation')
	:
	{
		return true;
	}
	endif;

	return parent::isApplicable($refObject, $eventName);
}

/**
	* if app support payment cancel.
	*
	* @param   array  $invoice  invoice details.
	* 
	* @return  void
	*
	* @see process()
 * */

	public function isSupportPaymentCancellation($invoice)
	{
		if ($invoice->isRecurring())
		:
		{
			return true;
		}
		endif;

		return false;
	}

/**
	* if app support payment cancel.
	* 
	* @param   string  &$view        view name.
	* @param   array   &$controller  invoice details.
	* @param   array   &$task        invoice details.
	* @param   array   &$format      invoice details.
	* 
	* @return  void
	*
	* @see process()
 * */

	public function onPayplansControllerCreation(&$view, &$controller, &$task, &$format)
	{
		if ($view != 'payment' || ($task != 'notify'))
		:
		{
			return true;
		}
		endif;

		$jinput = JFactory::getApplication()->input;
		$paymentKey = $jinput->get('invoice', null);

		if (!empty($paymentKey))
		:
		{
			$jinput->set('payment_key', $paymentKey, 'POST');

			return true;
		}
		endif;

		return true;
	}

/**
	* if app support payment cancel.
	*
	* @param   array  $payment  payment details.
	* @param   array  $data     null.
	* 
	* @return  void
	*
	* @see process()
 * */

	public function onPayplansPaymentForm(PayplansPayment $payment, $data = null)
	{
		$code_arr = array (
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
		$invoice = $payment->getInvoice(PAYPLANS_INSTANCE_REQUIRE);
		$amount  = $invoice->getTotal();
		$currency = $invoice->getCurrency('isocode');
		$this->assign('post_url', XiRoute::_("index.php?option=com_payplans&view=payment&task=complete&payment_key=" . $payment->getKey()));
		$this->assign('payment', $payment);
		$this->assign('invoice', $invoice);
		$this->assign('amount', $amount);
		$this->assign('currency_code', $currency);
		$this->assign('code_arr', $code_arr);

		return $this->_render('form');
	}

/**
	* onPayplansPaymentAfter.
	*
	* @param   array   $payment     payment details.
	* @param   string  &$action     action
	* @param   string  &$data       data.
	* @param   array   $controller  controller.
	* 
	* @return  void
	*
	* @see process()
 * */

	public function onPayplansPaymentAfter(PayplansPayment $payment, &$action, &$data, $controller)
	{
		if ($action == 'cancel')
		:
		{
			return true;
		}
		endif;

		// Add your API LOGIN ID
		(!defined("PAYMILL_API_KEY")) 	? define("PAYMILL_API_KEY", 	$this->getAppParam('private_key', '')) : '';

		// Add your API transaction key
		(!defined("PAYMILL_API_HOST")) 	? define("PAYMILL_API_HOST", 	'https://api.paymill.com/v2/'): '';

		$errors = array();
		$invoice = $payment->getInvoice(PAYPLANS_INSTANCE_REQUIRE);

		if ($invoice->isRecurring())
		{
			$this->_processRecurringRequest($payment, $data);
		}
		else
		{
			$this->_processNonRecurringRequest($payment, $data);
		}

		$payment->save();

		return parent::onPayplansPaymentAfter($payment, $action, $data, $controller);
	}

/**
	* onPayplansPaymentAfter.
	*
	* @param   array  $payment  payment details.
	* @param   array  $data     data.
	* 
	* @return  void
	*
	* @see process()
 * */

	protected function _processNonRecurringRequest(PayplansPayment $payment, $data)
	{
		require dirname(__FILE__) . '/lib/Services/Paymill/Transactions.php';
		$transactionsObject = new Services_Paymill_Transactions(PAYMILL_API_KEY, PAYMILL_API_HOST);
		$invoice = $payment->getInvoice(PAYPLANS_INSTANCE_REQUIRE);
		$amount = $invoice->getTotal();
		$currency = $invoice->getCurrency();
		$txn = PayplansTransaction::getInstance();
		$jinput   = JFactory::getApplication()->input;
		$component  = $jinput->getCmd('option'); 		
		$xml = JFactory::getXML(JPATH_SITE.'/administrator/components/com_payplans/payplans.xml');
		$comversion=(string)$xml->version;	
		$paymillxml = JFactory::getXML(JPATH_SITE.'/plugins/payplans/paymill/paymill.xml');	
		$pluginversion=(string)$paymillxml->version;	
		$source = $pluginversion.'_'.$component.'_'.$comversion; 
		$transactionData = array(
			'amount' => number_format($invoice->getTotal(), 2) * 100,
			'currency'    => $invoice->getCurrency('isocode'), // ISO 4217
			'token'       => $data['token'],
			'description' => $invoice->getTitle().'/'.$source;
			);
		$response = $transactionsObject->create($transactionData);
		$txn->set('user_id', $payment->getBuyer())
			->set('amount', $amount)
			->set('invoice_id', $invoice->getId())
			->set('payment_id', $payment->getId())
			->set('gateway_txn_id', isset($response['id']) ? $response['id'] : 0)
			->set('params', PayplansHelperParam::arrayToIni($response));

		if ($response['status'] == 'closed')
		{
			$txn->set('amount', $amount)
				->set('message', 'COM_PAYPLANS_APP_AUTHORIZE_TRANSACTION_COMPLETED');
		}
		else
		{
			if ($response['status'] == 'pending' )
			{
				$errors['response_reason_code'] = $response['status'];
				$errors['response_code']		= $response['status'];
				$errors['response_reason_text'] = $response['status'];
			}
			else
			{
				$errors['response_reason_code'] = $response['http_status_code'];
				$errors['response_code']		= $response['response_code'];
				$errors['response_reason_text'] = $response['error'];
			}

			$message = XiText::_('COM_PAYPLANS_LOGGER_ERROR_IN_AUTHORIZE_PAYMENT_PROCESS');
			PayplansHelperLogger::log(XiLogger::LEVEL_ERROR, $message, $payment, $errors);
			$getApp = XiFactory::getApplication();
			$getApp->enqueueMessage(XiText::_($errors['response_reason_text'] . ' (' . $errors['response_code'] . ')'));
			$getApp->redirect(XiRoute::_('index.php?option=com_payplans&view=payment&task=pay&payment_key=' . $payment->getKey()));
		}

		$txn->save();
	}
}
