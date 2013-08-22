<?php
/**
 * @package	HikaShop for Joomla!
 * @version	2.2.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2013 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
$lang = & JFactory::getLanguage();
$lang->load('plg_hikashoppayment_paymill', JPATH_ADMINISTRATOR);

class plgHikashoppaymentpaymill extends hikashopPaymentPlugin
{
	var $debugData = array();
	var $multiple = true;
	var $name = 'paymill';

	var $pluginConfig = array(
		'public_key' => array('PUBLIC_KEY', 'input'),
		'private_key' => array('PRIVATE_KEY', 'input'),
		'payment_mode' => array('ENABLE_PAYMENT_MODE', 'list',array(
			'true' => 'Test',
			'false' => 'Live')
		)
	);
	function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		//Set the language in the class
		$config = JFactory::getConfig();

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
		$this->code_arr= json_encode($this->code_arr);
		
	}
	//set currency and amount 
	function onAfterCartProductsLoad(&$cart)
	{ 
		$this->amount1 = $cart->full_total->prices[0]->price_value_with_tax;
		$currency1 = $cart->full_total->prices[0]->price_currency_id;
		$db = JFactory::getDBO();
		$q_oi = "SELECT * FROM #__hikashop_currency where `currency_id`= '".$currency1."'";
		$db->setQuery($q_oi);
		$result = $db->loadObject();
		$this->currency1= $result->currency_code;
	}
	
	function needCC(&$method) 
	{
		//print_r(JRequest::get('POST'));
		if(JRequest::get('POST'))
		{
			$nm =  JRequest::getInt('paymill-card-nm');
			$no = JRequest::getInt('paymill-card-no');
			
			$xreplace = substr($no,0,12);
			$replace_no= str_replace($xreplace,"xxxxxxxxxxxx",$no);
			
			$ex_mm = JRequest::getInt('paymill-card-ex-mm');
			$ex_yy = JRequest::getInt('paymill-card-ex-yy');
			$ex_cvc = JRequest::getInt('paymill-card-ex-cvc');
			$token = JRequest::getInt('token');
			$ac_no = JRequest::getVar('paymill-card-acc-no');
			$bank_no = JRequest::getVar('paymill-card-bank-no');
			$acc_country =JRequest::getVar('paymill-card-acc-country');
			$PAYMENT_TYPE= JRequest::getVar('PAYMENT_TYPE');
		}
		/*if(!isset(JRequest::getVar('PAYMENT_TYPE')))
		{
			$checkcc = 'selected';
			$style= 'none';
		}
		else*/ if(JRequest::getVar('PAYMENT_TYPE') == 'cc')
		{
			$checkcc = 'selected';
			$style= 'none';
		}
		else if(JRequest::getVar('PAYMENT_TYPE') == 'dc')
		{
			$checkdc = 'selected';
			$style= 'block';
			$stylecc= 'none';
		}
		else
		{
			$checkcc = 'selected';
			$style= 'none';
			
		}
		if(JRequest::getInt('token'))
		{
			$maindiv = 'none';
			if(JRequest::getVar('PAYMENT_TYPE') == 'cc')
			{
				$secondiv = 'block';
				$threediv = 'none';
			}
			else
			{
				$secondiv = 'none';
				$threediv = 'block';
			}
			
		}
		else
		{
			$maindiv = 'block';
			$secondiv = 'none';
			$threediv = 'none';
		}
		//from html layout	
		
		$url = JURI::base().'plugins/hikashoppayment/paymill/img/ajax_loader.gif';
		if(JVERSION <= '2.5.9')
		{
				$method->custom_html .='<link href="'.JURI::base().'plugins/hikashoppayment/paymill/css/paymill.css" rel="stylesheet">';
		}
		$method->custom_html .='
			<style>
			#hikashop_payment_methods table div {
			height: auto !important;
			}
			.error
			{
					padding : 5px;
					margin : 5px;
					background-color: #F2DEDE;
					border-color: #EED3D7;
					color: #B94A48;
			}
			</style>
			<script type="text/javascript" src="https://bridge.paymill.com/"></script>
			<script type="text/javascript" src="https://static.paymill.com/assets/js/jquery/jquery-1.7.2.min.js"></script>
			<script type="text/javascript">
				//if paymill radio button selected next button will be display none 
				jQuery(document).ready(function() 
				{
					if(jQuery("#radio_'.$method->payment_type.'_'.$method->payment_id.'").attr("checked") == "checked")
					{
							jQuery("#hikashop_checkout_next_button").css("display", "none");
					}
				});
				//if onclick paymill radio button
				jQuery("#radio_'.$method->payment_type.'_'.$method->payment_id.'").click(function()
				{
					jQuery("#hikashop_checkout_next_button").css("display", "none"); 
				});
				//public key parameter
				var PAYMILL_PUBLIC_KEY = "'.$method->payment_params->public_key.'";
				//test code 
				var PAYMILL_TEST_MODE  = "'.$method->payment_params->payment_mode.'";
				// payment type mode 
				function ChangeDropdowns(value)
				{
					alert("dssdf");
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
			jQuery("#paymill_button").attr("disabled", "disabled");
			if(jQuery("#token").val())
			{
				document.forms["hikashop_checkout_form"].submit();
			}
			else
			{
				var payment_type = jQuery("#payment_type").val();
				if(payment_type == "cc")
				{
					try {
						paymill.createToken({
							number:     jQuery(".card-number").val(),
							exp_month:  jQuery(".card-expiry-month").val(),
							exp_year:   jQuery(".card-expiry-year").val(),
							cvc:        jQuery(".card-cvc").val(),
							cardholder: jQuery(".card-holdername").val(),
							amount: jQuery(".card-amount").val(),
							currency: jQuery(".card-currency").val(),

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
							number: jQuery(".debit-number").val(),
							bank:  jQuery(".debit-bank").val(),
							country:   jQuery(".debit-country").val(),
							accountholder: jQuery(".card-holdername").val()
						}, PaymillResponseHandler);
					} catch(e) {
						 jQuery(".payment-errors").text(e);
						logResponse(e.message);
					}
					 jQuery("#debit-form .debit-bank").bind("paste cut keydown",function(e) {
						var that = this;
						setTimeout(function() {
								paymill.getBankName(jQuery(that).val(), function(error, result) {
								error ? logResponse(error.apierror) : jQuery(".debit-bankname").val(result);
									});
								}, 200);
						});
				}
			}
        }

        function PaymillResponseHandler(error, result) {
			
			console.log(error);
			error ? logResponse(error.apierror) : logResponse(result.token);
			if (error) {
				var jason_error = '.$this->code_arr.';
				jQuery.each(jason_error, function(index, element) {
					if(index == error.apierror){
						var version = "'.JVERSION.'";
						if(version > "2.5.9")
						{
							jQuery(".payment-errors").addClass("alert alert-error");
						}
						else
						{
							jQuery(".payment-errors").addClass("error");
						}
						jQuery("#paymill_button").removeAttr("disabled");
						jQuery(".payment-errors").text(element);
					}
				});
			}
			else
			{
					jQuery("#loadder").css("display", "block");
					jQuery("#paymill_button").attr("disabled", "disabled");
					jQuery("#token").val(result.token);
					document.forms["hikashop_checkout_form"].submit();
			}

        }
        function logResponse(res)
        {
            // create console.log to avoid errors in old IE browsers
            if (!window.console) console = {log:function(){}};
            //console.log(res);
            if(PAYMILL_TEST_MODE)
            jQuery(".debug").text(res).show().fadeOut(3000);
        }
        </script>
        <div id="paymill_plugin" style="display:'.$maindiv.';">
			<!-- display from-->
			<div id="loadder" style="display:none;text-align:center;"><img src="'.$url.'"/></div>
            <div class="akeeba-bootstrap">
            <!-- error display div-->
            <div class="payment-errors"></div>
                    <form id="card-tds-form" name="second" action="#" method="POST" class="form-validate form-horizontal">
						<div class="control-group">
								<label class="control-label">'.JText::_('NAME').'</label>
								<div class="controls"><input name="paymill-card-nm" class="card-holdername"  type="text" size="20" value="'.$nm.'" '.$readonlynm.' />
								</div>
                        </div>
                        <div class="control-group">
							<label class="control-label">'.JText::_('PAYMENT_TYPE').'</label>
								<div class="controls">
									<select id="payment_type" name="PAYMENT_TYPE" '.$readonlytype.' onchange="ChangeDropdowns(this.value);">
										<option value="cc" '.$checkcc.'>'.JText::_('CREDIT_CARD') .'</option>
										<option value="dc" '.$checkdc.'>'.JText::_('DEBIT_CARD') .'</option>
								</select>
						</div>
						</div>
                        <div id="cc" style="display:'.$stylecc.';">
							<div class="control-group">
									<label class="control-label">'.JText::_('CREDIT_CARD_NUMBER').'</label>
									<div class="controls"><input   class="card-number" name="paymill-card-no"  type="text" maxlength="16" size="16" value="'.$no.'" '.$readonlyno.'/>
									</div>
							</div>


							<div class="control-group">
									<label class="control-label">'.JText::_('EXPIRY') .'</label>
								   <div class="controls"> <input '.$readonlymm.'  name="paymill-card-ex-mm" class="card-expiry-month" type="text" size="2" maxlength="2" value="'.$ex_mm.'" style="width:20px;"/>/
									<input name="paymill-card-ex-yy" '.$readonlyyy.'  class="card-expiry-year" type="text" size="4"  value="'.$ex_yy.'"  maxlength="4" style="margin-left: 0px;width:50px;"/>
									&nbsp;'.JText::_('CVC').'<input class="card-cvc" '.$readonlycvc.'  name="paymill-card-ex-cvc" type="text" maxlength="4" size="4"  value="'.$ex_cvc.'"  style="width:65px;"/>
									</div>
							</div>
                        </div>
                        <div id="bank" style="display:'.$style.';">

									 <div class="control-group">
											<label class="control-label">'.JText::_('ACCOUNT_NUMBER').'</label>
											<div class="controls"> <input '.$readonlyacno.'  name="paymill-card-acc-no" class="debit-number" maxlength="10" type="text" size="20" value="'.$ac_no.'" /></div>
									</div>
									 <div class="control-group">
											<label class="control-label">'.JText::_('BANK_CODE_NUMBER') .'</label>
											<div class="controls">  <input '.$readonlybkno.'  class="debit-bank"name="paymill-card-bank-no"  maxlength="8" type="text" size="20" value="'.$bank_no.'" /></div>
									</div>

									<div class="control-group">
												<label class="control-label">'.JText::_('COUNTRY') .'</label>
												<div class="controls"><input '.$readonlycoun.'  class="debit-country" name="paymill-card-acc-country" type="text" size="20" value="'.$acc_country.'" /></div>
									</div>
                        </div>
                        <div style="display:none;"class="control-group">
								<label class="control-label">'.JText::_('AMOUNT') .'</label>
								<div class="controls"><input class="card-amount" type="text" size="4" value="'.$this->amount1.'" /></div>
						</div>
                        <div style="display:none;" class="control-group">
							<label class="control-label">'.JText::_('CURRENCY') .'</label>
							<div class="controls"><input class="card-currency" type="text" size="4" value="'.$this->currency1.'" /></div>
                       </div></div>
				<input name="token"  id="token" type="hidden"  value="'.$token.'" />
				</div>
				<div id="seconddiv" style="display:'.$secondiv.';"> 
				Name - '.$nm.'<br> Card No - '.$replace_no.'<br> Expiration date - '.$ex_mm.'/'.$ex_yy.'<br> Card validation code - xxx
				</div>
				<div id="threediv" style="display:'.$threediv.';"> 
				Name - '.$nm.'<br> Account Number - '.$ac_no.'<br> Bank Code Number - '.$bank_no.'<br> Country - '.$acc_country.'<br>
				</div>
                     <input  onclick="submitme();" type="button" value="'.JText::_('SUBMIT').'"/>
                    </form>
                </div>
			
			';
			
			return true;
	}

	function onBeforeOrderCreate(&$order,&$do)
	{
		if(parent::onBeforeOrderCreate($order, $do) === true)
		return true;
		$this->ccLoad();
		$token =JRequest::getVar('token');
		
		define('PAYMILL_API_HOST', 'https://api.paymill.com/v2/');
		//FROM PAYMILL PLUGIN BACKEND 
		define('PAYMILL_API_KEY', $order->cart->payment->payment_params->private_key);
		set_include_path(implode(PATH_SEPARATOR, array(realpath(realpath(dirname(__FILE__)) . '/lib'),get_include_path(),)));
		//CREATED TOKEN 
		if ($token) 
		{
				// access lib folder
				require "lib/Services/Paymill/Transactions.php";
				//pass api key and private key to Services_Paymill_Transactions function
				$transactionsObject = new Services_Paymill_Transactions(PAYMILL_API_KEY, PAYMILL_API_HOST);

				$params = array(
				'amount'      => ($order->cart->full_total->prices[0]->price_value_with_tax * 100), //amount *100
				'currency'    => $this->currency->currency_code ,   // ISO 4217
				'token'       => $token,
				'description' => 'Test Transaction'
				);
				$transaction = $transactionsObject->create($params);
				
				
				$status = $transaction['status'];
				$history = new stdClass();
				$history->history_notified=0;
				$history->history_amount= round($order->cart->full_total->prices[0]->price_value_with_tax,2) . $this->currency->currency_code;
				$history->history_data = '';

				if($status == 'closed')
				{
						$this->modifyOrder($order,$this->payment_params->verified_status,$history,false);			
				}
				else if($status == 'pending')
				{
						$this->modifyOrder($order,$this->payment_params->pending_status,$history,false);			
				}
				else if($status == 'failed')
				{	
						$this->modifyOrder($order,$this->payment_params->invalid_status,$history,false);			
				
				}
				else if($transaction['error'])
				{
					$this->app->enqueueMessage('Sorry!! Unable further process due some technical issue');
					$error = $transaction['error'];
					if(!empty($error)){
						$this->app->enqueueMessage($error);
					}
					$this->ccClear();
					$do = false;
				}
				else 
				{
					$this->error_paymill = 'error';
					$this->app->enqueueMessage('Your transaction was declined. Please reenter your credit card or another credit card information.');
					$this->ccClear();
					$do = false;
				}
		}
		else
		{
					$this->error_paymill = 'error';
					$this->app->enqueueMessage('Your transaction was declined. Please reenter your credit card or another credit card information.');
					$this->ccClear();
					$do = false;
		}	
	
	}

	function onAfterOrderConfirm(&$order,&$methods,$method_id){
		
		$this->paymill_order_id = $order->order_id;
		$this->removeCart = true;
		$method =& $methods[$method_id];
		$this->return_url = @$method->payment_params->return_url;
		return $this->showPage('thanks');
	}

	function getPaymentDefaultValues(&$element) {
		$element->payment_name='Paymill';
		$element->payment_description='You can pay by Credit card/ Direct debit using this payment method';
		$element->payment_images='';

		$element->payment_params->invalid_status='cancelled';
		$element->payment_params->pending_status='created';
		$element->payment_params->verified_status='confirmed';
	}
}
