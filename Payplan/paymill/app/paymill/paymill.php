<?php
if(defined('_JEXEC')===false) die();
class PayplansAppPaymill extends PayplansAppPayment
{
	protected $_location	= __FILE__;
	// over-ride to make it applicable on onPayplansControllerCreation
	function isApplicable($refObject = null, $eventName='')
	{
		// return true for event onPayplansControllerCreation
		if($eventName == 'onPayplansControllerCreation'){
			return true;
		}
		return parent::isApplicable($refObject, $eventName);
	}
	/**
	 * if app support payment cancel
	 * @since 2.0
	 */
	public function isSupportPaymentCancellation($invoice)
	{	
		if($invoice->isRecurring()){
			return true;
		}
		return false;
	}	
	
	public function onPayplansControllerCreation(&$view, &$controller, &$task, &$format)
		{
			$jinput = JFactory::getApplication()->input;
			if($view != 'payment' || ($task != 'notify') )
			{
				return true;
			}
			$paymentKey =  $jinput->get('invoice', null);
			if(!empty($paymentKey)){
				 $jinput->set('payment_key', $paymentKey, 'POST');
				return true;
			}
			return true;
		}
		
    public function onPayplansPaymentForm(PayplansPayment $payment, $data = null)
	{		
		$code_arr = array (
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
		$invoice = $payment->getInvoice(PAYPLANS_INSTANCE_REQUIRE);
		$amount  = $invoice->getTotal();
		$currency = $invoice->getCurrency('isocode');
		$this->assign('post_url', XiRoute::_("index.php?option=com_payplans&view=payment&task=complete&payment_key=".$payment->getKey()));
		$this->assign('payment', $payment);
		$this->assign('invoice', $invoice);
		$this->assign('amount', $amount);
		$this->assign('currency_code', $currency);
		$this->assign('code_arr', $code_arr);
		return $this->_render('form');
	}
	
	public function onPayplansPaymentAfter(PayplansPayment $payment, &$action, &$data, $controller)
	{
		if($action == 'cancel'){
			return true;
		}
		//require_once 'AuthorizeNet.php'; 
		(!defined("PAYMILL_API_KEY")) 	? define("PAYMILL_API_KEY", 	$this->getAppParam('private_key', '')) : '';    // Add your API LOGIN ID
		(!defined("PAYMILL_API_HOST")) 	? define("PAYMILL_API_HOST", 	'https://api.paymill.com/v2/'): ''; // Add your API transaction key
		
		$errors   = array();
		$invoice = $payment->getInvoice(PAYPLANS_INSTANCE_REQUIRE);
		if($invoice->isRecurring())
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

	protected function _processNonRecurringRequest(PayplansPayment $payment, $data)
	{
		
		require dirname(__FILE__).'/lib/Services/Paymill/Transactions.php';
		$transactionsObject = new Services_Paymill_Transactions(PAYMILL_API_KEY, PAYMILL_API_HOST);
		$invoice = $payment->getInvoice(PAYPLANS_INSTANCE_REQUIRE);
		$amount = $invoice->getTotal();
		$currency = $invoice->getCurrency();
		$txn = PayplansTransaction::getInstance();
		//print_r($txn);		
		$transactionData = array(
	        'amount' => number_format($invoice->getTotal(), 2)*100, 
	        'currency'    => $invoice->getCurrency('isocode'),   // ISO 4217
			'token'       => $data['token'],
			'description' => $invoice->getTitle()
	        );
		$response = $transactionsObject->create($transactionData);
		$txn->set('user_id', $payment->getBuyer())
			->set('amount', $amount)
			->set('invoice_id', $invoice->getId())
			->set('payment_id', $payment->getId())
			->set('gateway_txn_id', isset($response['id']) ? $response['id'] : 0)
			->set('params', PayplansHelperParam::arrayToIni($response));
	    if($response['status'] == 'closed'){
	    	$txn->set('amount', $amount)
	    		->set('message', 'COM_PAYPLANS_APP_AUTHORIZE_TRANSACTION_COMPLETED');
		}		
		else{
			//hrer for pending 
			if($response['status'] == 'pending' || $response['status'] == 'pending' )
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
			$getApp->enqueueMessage(XiText::_($errors['response_reason_text'].' ('.$errors['response_code'].')'));
		  	$getApp->redirect(XiRoute::_('index.php?option=com_payplans&view=payment&task=pay&payment_key='.$payment->getKey()));
		}
		
		$txn->save();
	}
	
}



