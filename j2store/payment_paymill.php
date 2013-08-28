<?php
/*
 * --------------------------------------------------------------------------------
   Weblogicx India  - J2 Store v 3.0 - Payment Plugin - SagePay
 * --------------------------------------------------------------------------------
 * @package		Joomla! 2.5x
 * @subpackage	J2 Store
 * @author    	Weblogicx India http://www.weblogicxindia.com
 * @copyright	Copyright (c) 2010 - 2015 Weblogicx India Ltd. All rights reserved.
 * @license		GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link		http://weblogicxindia.com
 * --------------------------------------------------------------------------------
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once (JPATH_ADMINISTRATOR.'/components/com_j2store/library/plugins/payment.php');
require_once (JPATH_SITE.'/components/com_j2store/helpers/utilities.php');
jimport('joomla.application.component.helper');
class plgJ2StorePayment_paymill extends J2StorePaymentPlugin

{/**
	 * @var $_element  string  Should always correspond with the plugin's filename, 
	 *                         forcing it to be unique 
	 */
	 
		
		//print_r($params);die();
    var $_element    = 'payment_paymill';
    var $login_id    = '';
    var $tran_key    = '';
    var $_isLog      = false;
    
    function plgJ2StorePayment_paymill(& $subject, $config) 
	{
		parent::__construct($subject, $config);
		$this->loadLanguage( '', JPATH_ADMINISTRATOR );
		$params = JComponentHelper::getParams('com_j2store');
		
		/*$app = &JFactory::getApplication();
		$params = JComponentHelper::getParams('com_j2store');
		
		$dashboardId = $params->get('dashboardId');
		var_dump($dashboardId);*/
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
        $this->public_key = $this->_getParam( 'public_key' ); 
        $this->private_key = $this->_getParam( 'private_key' );
	}

    
    /**
     * @param $data     array       form post data
     * @return string   HTML to display
     */
    function _prePayment( $data )
    {
		
        // prepare the payment form
        $vars = new JObject();
        
        //now we have everthing in the data. We need to generate some more sagepay specific things.
        
        //lets get vendorname
        
        $vars->url = JRoute::_( "index.php?option=com_j2store&view=checkout" );
        $vars->order_id = $data['order_id'];
        $vars->orderpayment_id = $data['orderpayment_id'];
        $vars->orderpayment_type = $this->_element;
        
        $vars->cardholder = JRequest::getVar("cardholder");
        $vars->payment_mode = JRequest::getVar("payment_mode");
         //crdit card
        $vars->cardnum = JRequest::getVar("cardnum");
        $month=JRequest::getVar("month");
        $year=JRequest::getVar("year");
        $card_exp = $month.' / '.$year;
        $vars->cardexp = $card_exp;
        
        $vars->cardcvv = JRequest::getVar("cardcvv");
        $vars->cardnum_last4 = substr( JRequest::getVar("cardnum"), -4 );
        //debit card
        $vars->accnum =JRequest::getVar("accnum");
        $vars->accnum_last4 = substr( JRequest::getVar("accnum"), -4 );
        $vars->banknum =JRequest::getVar("banknum");
        $vars->country =JRequest::getVar("country");
        
        //token 
        $vars->token12 =JRequest::getVar("token12");
        //lets check the values submitted
      //  print_r($vars);die();
        $html = $this->_getLayout('prepayment', $vars);
        return $html;
    }
    
    /**
     * Processes the payment form
     * and returns HTML to be displayed to the user
     * generally with a success/failed message
     *  
     * @param $data     array       form post data
     * @return string   HTML to display
     */
    function _postPayment( $data )
    {
		//print_r($data);die();
        // Process the payment        
        $vars = new JObject();
        
        $app =JFactory::getApplication();
        $paction = JRequest::getVar( 'paction' );
        
        switch ($paction)
        {
            case 'process_recurring':
                // TODO Complete this
                // $this->_processRecurringPayment();
                $app->close();                  
              break;
            case 'process':
                $vars->message = $this->_process();
                $html = $this->_getLayout('message', $vars);
                 $html .= $this->_displayArticle();
              break;
            default:
                $vars->message = JText::_( 'J2STORE_SAGEPAY_MESSAGE_INVALID_ACTION' );
                $html = $this->_getLayout('message', $vars);
              break;
        }
        
        return $html;
    }
    
    /**
     * Prepares variables and 
     * Renders the form for collecting payment info
     * 
     * @return unknown_type
     */
    function _renderForm( $data )
    {
		//echo "hi";die();
        $vars = new JObject();
        $vars->prepop = array();
        //$vars->cctype_input   = $this->_cardTypesField();
        //print_r($vars);
        $html = $this->_getLayout('form', $vars);
       // echo $html;die('ccc');
        return $html;
    }
    
    /**
     * Verifies that all the required form fields are completed
     * if any fail verification, set 
     * $object->error = true  
     * $object->message .= '<li>x item failed verification</li>'
     * 
     * @param $submitted_values     array   post data
     * @return unknown_type
     */
    function _verifyForm( $submitted_values )
    {
        $object = new JObject();
        $object->error = false;
        $object->message = '';
        $user = JFactory::getUser();
        foreach ($submitted_values as $key=>$value) 
        {
            switch ($key) 
            {
                case "cardholder":
                    if (!isset($submitted_values[$key]) || !JString::strlen($submitted_values[$key])) 
                    {
                        $object->error = true;
                        $object->message .= "<li>".JText::_( "J2STORE_SAGEPAY_MESSAGE_CARD_HOLDER_NAME_REQUIRED" )."</li>";
                    }
                  break;
               case "cardtype":
                    if (!isset($submitted_values[$key]) || !JString::strlen($submitted_values[$key])) 
                    {
                        $object->error = true;
                        $object->message .= "<li>".JText::_( "J2STORE_SAGEPAY_MESSAGE_CARD_TYPE_INVALID" )."</li>";
                    }
                  break;
                case "cardnum":
                    if (!isset($submitted_values[$key]) || !JString::strlen($submitted_values[$key])) 
                    {
                        $object->error = true;
                        $object->message .= "<li>".JText::_( "J2STORE_SAGEPAY_MESSAGE_CARD_NUMBER_INVALID" )."</li>";
                    } 
                  break;
                 case "month":
                    if (!isset($submitted_values[$key]) || !JString::strlen($submitted_values[$key])) 
                    {
                        $object->error = true;
                        $object->message .= "<li>".JText::_( "J2STORE_SAGEPAY_MESSAGE_CARD_EXPIRATION_DATE_INVALID" )."</li>";
                    } 
                  break;
                case "year":
                    if (!isset($submitted_values[$key]) || !JString::strlen($submitted_values[$key])) 
                    {
                        $object->error = true;
                        $object->message .= "<li>".JText::_( "J2STORE_SAGEPAY_MESSAGE_CARD_EXPIRATION_DATE_INVALID" )."</li>";
                    } 
                  break;
                case "cardcvv":
                    if (!isset($submitted_values[$key]) || !JString::strlen($submitted_values[$key])) 
                    {
                        $object->error = true;
                        $object->message .= "<li>".JText::_( "J2STORE_SAGEPAY_MESSAGE_CARD_CVV_INVALID" )."</li>";
                    } 
                  break;
                default:
                  break;
            }
        }  
            
       //return $object;
    }

    /**
     * Formats the value of the card expiration date
     * 
     * @param string $format
     * @param $value
     * @return string|boolean date string or false
     * @access protected
     */
    function _getFormattedCardExprDate($format, $value)
    {
        // we assume we received a $value in the format MMYY
        $month = substr($value, 0, 2);
        $year = substr($value, 2);
        
        if (strlen($value) != 4 || empty($month) || empty($year) || strlen($year) != 2) {
            return false;
        }
        
        $date = date($format, mktime(0, 0, 0, $month, 1, $year));
        return $date;
    }

    /**
     * Gets the gateway URL
     * 
     * @param string $type Simple or subscription
     * @return string
     * @access protected
     */
    function _getActionUrl($type = 'simple')
    {
        if ($type == 'simple') 
        {
            $url  = $this->params->get('sandbox') ? 'https://test.sagepay.com/simulator/VSPDirectGateway.asp' : 'https://live.sagepay.com/gateway/service/vspdirect-register.vsp';
        }
            else 
        {
            // recurring billing url
            $url  = $this->params->get('sandbox') ? 'https://test.sagepay.com/simulator/VSPDirectGateway.asp' : 'https://live.sagepay.com/gateway/service/vspdirect-register.vsp';
        }
        
        return $url;
    }
    
    /**
     * Gets a value of the plugin parameter
     * 
     * @param string $name
     * @param string $default
     * @return string
     * @access protected
     */
    function _getParam($name, $default = '') 
    {
        $sandbox_param = "sandbox_$name";
        $sb_value = $this->params->get($sandbox_param);
        
        if ($this->params->get('sandbox') && !empty($sb_value)) {
            $param = $this->params->get($sandbox_param, $default);
        }
        else {
            $param = $this->params->get($name, $default);
        }
        
        return $param;
    }
    
    
    /**
     * Processes the payment
     * 
     * This method process only real time (simple) payments
     * 
     * @return string
     * @access protected
     */
    function _process()
    {

		//echo "dfsdf";die();
        /*
         * perform initial checks 
         */
        if ( ! JRequest::checkToken() ) {
            return $this->_renderHtml( JText::_( 'J2STORE_SAGEPAY_INVALID_TOKEN' ) );
        }
        
        
        $data = JRequest::get('post');
        // get order information
        JTable::addIncludePath( JPATH_ADMINISTRATOR.'/components/com_j2store/tables' );
        $order = JTable::getInstance('Orders', 'Table');
        $order->load( $data['orderpayment_id'] );
        
        //check for exisiting things
     if ( empty($order->order_id) ) {
            return JText::_( 'J2STORE_SAGEPAY_INVALID_ORDER' );
        }
     
        // prepare the form for submission to sage pay
        $process_vars = $this->_getProcessVars($data);
        
        return $this->_processSimplePayment($process_vars);       
  
    }
    
    /**
     * Prepares parameters for the payment processing
     * 
     * @param object $data Post variables
     * @param string $auth_net_login_id
     * @param string $auth_net_tran_key
     * @return array
     * @access protected
     */
    function _getProcessVars($data)
    {
		require_once (JPATH_SITE.'/components/com_j2store/helpers/cart.php');
		$access = new J2StoreHelperCart();
		$amount = $access->getTotal();
		//echo $cu = $access->dispayPriceWithTax();
		require_once (JPATH_SITE.'/components/com_j2store/helpers/utilities.php');
		$J2StoreUtilities = new J2StoreUtilities();
		$total_amount = $J2StoreUtilities->number($amount);
		// joomla info
        $user =JFactory::getUser();
        $j2store_params = JComponentHelper::getParams('com_j2store');
		$currency_code = $j2store_params->get('currency_code');
        $params = array(
				'amount'      => ($total_amount *100), //amount *100
				'currency'    => $currency_code ,   // ISO 4217
				'token'       => $data['token'],
				'description' => $data
				);
				//print_r($params);die();
        return $params;
    }
    

    
    /**
     * Simple logger 
     * 
     * @param string $text
     * @param string $type
     * @return void
     */
    function _log($text, $type = 'message')
    {
        if ($this->_isLog) {
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
     * @param array $authnet_values  
     * @return string
     * @access protected
     */
    function _processSimplePayment($params) 
    {
		require "plugins/j2store/payment_paymill/payment_paymill/lib/Services/Paymill/Transactions.php";
        define('PAYMILL_API_HOST', 'https://api.paymill.com/v2/');
		//FROM PAYMILL PLUGIN BACKEND 
		define('PAYMILL_API_KEY', $this->private_key);
		set_include_path(implode(PATH_SEPARATOR, array(realpath(realpath(dirname(__FILE__)) . '/lib'),get_include_path(),)));
		$transactionsObject = new Services_Paymill_Transactions(PAYMILL_API_KEY, PAYMILL_API_HOST);
		$transactionResponse = $transactionsObject->create($params);
		//print_r($transactionResponse);die();
		$evaluateResponse = $this->_evaluateSimplePaymentResponse( $transactionResponse, $params );
        $this->_log($transactionResponse);
       
        return $evaluateResponse;
    }
   //voveran  
    /**
     * Proceeds the simple payment
     * 
     * @param string $resp
     * @param array $submitted_values
     * @return object Message object
     * @access protected
     */
    function _evaluateSimplePaymentResponse( $resp, $submitted_values )
    {
		//print_r($resp);die();
        $object = new JObject();
        $object->message = '';
        $html = '';
        $errors = array();
        $payment_status = JText::_('J2STORE_INCOMPLETE');
		$user =JFactory::getUser();
		$token = $submitted_values['token'];
		if($resp['status'] == 'closed')
				{
					$payment_status = JText::_('J2STORE_COMPLETED');
				}
		if($token) 
		{
				if($resp['error'])
				{
					 $payment_status = JText::_('J2STORE_DECLINED');
                     $errors[] = $resp['error'];
				}
				else if($resp['status'] == 'closed')
				{
					$payment_status = JText::_('J2STORE_COMPLETED');
				}
				else if($resp['status'] == 'Pending')
				{
					$payment_status = JText::_('J2STORE_PENDING');
					$errors[] = JText::_( "J2STORE_SAGEPAY_ERROR_PROCESSING_PAYMENT" );
				}
				else if($resp['status'] == 'failed')
				{
					$payment_status = JText::_('J2STORE_FAILED');
					$errors[] = JText::_( "J2STORE_SAGEPAY_ERROR_PROCESSING_PAYMENT" );
				}
				else
				{
					 $payment_status = JText::_('J2STORE_ERROR');
                     $order_status = JText::_('J2STORE_INCOMPLETE');
                     $errors[] = JText::_( "J2STORE_SAGEPAY_ERROR_PROCESSING_PAYMENT" );
                    
				}
			
		
		}
		else
		{
			 $payment_status = JText::_('J2STORE_ERROR');
             $order_status = JText::_('J2STORE_INCOMPLETE');
             $errors[] = JText::_( "J2STORE_SAGEPAY_ERROR_PROCESSING_PAYMENT" );    
			
		}
		//end if token
        // Evaluate a typical response from sage pay
        // =======================
        // verify & create payment
        // =======================
			//$data['orderpayment_id'] 
			
            // check that payment amount is correct for order_id
            JTable::addIncludePath( JPATH_ADMINISTRATOR.'/components/com_j2store/tables' );
            $orderpayment = JTable::getInstance('Orders', 'Table');
            $orderpayment->load($submitted_values['description']['orderpayment_id']);
           // print_r($orderpayment);die;
            if (empty($orderpayment->order_id))
            {
                // TODO fail
            }
            $orderpayment->transaction_details  = $this->_getFormattedTransactionDetails($resp);
            $orderpayment->transaction_id       = $resp['id'];
            $orderpayment->transaction_status   = $payment_status;

            
            //set a default status to it
			$orderpayment->order_state = JText::_('J2STORE_PENDING'); // PENDING
			$orderpayment->order_state_id = 4; // PENDING
        
            // set the order's new status and update quantities if necessary
            if (count($errors)) 
            {
                // if an error occurred 
                $orderpayment->order_state  = trim(JText::_('J2STORE_FAILED')); // FAILED
                 $orderpayment->order_state_id = 3; // FAILED                
            }
             else 
            {
				$orderpayment->order_state  = trim(JText::_('J2STORE_CONFIRMED')); // Payment received and CONFIRMED
				 $orderpayment->order_state_id = 1; // CONFIRMED
				 JLoader::register( 'J2StoreHelperCart', JPATH_SITE.'/components/com_j2store/helpers/cart.php');
				// remove items from cart
        	    J2StoreHelperCart::removeOrderItems( $orderpayment->id );	
				//$this->setOrderPaymentReceived( $orderpayment->order_id );
            }
    
            // save the order
           // print_r($orderpayment);die;
            if (!$orderpayment->save())
            {
                $errors[] = $orderpayment->getError();
            }
            
            if (empty($errors))
            {
            	 // let us inform the user that the payment is successful
        		require_once (JPATH_SITE.'/components/com_j2store/helpers/orders.php');
            	
                $return = JText::_( "J2STORE_SAGEPAY_MESSAGE_PAYMENT_SUCCESS" );
                return $return;                
            } 
            else {
            	$error = count($errors) ? implode("\n", $errors) : '';
            	//$this->_sendErrorEmails($error, $orderpayment->transaction_details);
            }
            
            return count($errors) ? implode("\n", $errors) : '';

        // ===================
        // end custom code
        // ===================
    }
    
    
     function _getFormattedTransactionDetails( $data )
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
    
   
    
    /**
     * Gets admins data
     *
     * @return array|boolean
     * @access protected
     */
    function _getAdmins()
    {
    	$db =JFactory::getDBO();
    	$query = $db->getQuery(true);
    	$query->select('u.name,u.email');
    	$query->from('#__users AS u');
    	$query->join('LEFT', '#__user_usergroup_map AS ug ON u.id=ug.user_id');
    	$query->where('u.sendEmail = 1');
    	$query->where('ug.group_id = 8');
    
    	$db->setQuery($query);
    	$admins = $db->loadObjectList();
    	if ($error = $db->getErrorMsg()) {
    		JError::raiseError(500, $error);
    		return false;
    	}
    
    	return $admins;
    }
    
    function _getOrderPaymentId($order_id) {
		
		$db = JFactory::getDBO();
		$query = 'SELECT id FROM #__j2store_orders WHERE order_id='.$order_id;
		$db->setQuery($query);
		$result = $db->loadResult();
		//print_r($result);die();
		//return $db->loadResult();
		
		
	}
	
	function _getOrderInfo($orderpayment_id) {
	
		$db = JFactory::getDBO();
		$query = 'SELECT * FROM #__j2store_orderinfo WHERE orderpayment_id='.$db->Quote($orderpayment_id);
		$db->setQuery($query);
		return $db->loadObject();
	}
}
