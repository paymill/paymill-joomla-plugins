<?php
/**
 * --------------------------------------------------------------------------------
 * Payment Plugin - Paymill
 * --------------------------------------------------------------------------------
 * @package     Joomla!_2.5x_And_3.0X
 * @subpackage  Hikashop
 * @author      Techjoomla <support@techjoomla.com>
 * @copyright   Copyright (c) 2010 - 2015 Techjoomla . All rights reserved.
 * @license     GNU/GPL license: http://www.techjoomla.com/copyleft/gpl.html
 * @link        http://techjoomla.com
 * --------------------------------------------------------------------------------
 * */

defined('_JEXEC') or die('Restricted access');

$lang = JFactory::getLanguage();
$lang->load('plg_hikashoppayment_paymill', JPATH_ADMINISTRATOR);

/**
	* PlgHikashoppaymentpaymill class.
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

class PlgHikashoppaymentpaymill extends hikashopPaymentPlugin
{
	public $debugData = array();

	public $multiple = true;

	public $name = 'paymill';

	public $pluginConfig = array(
		'public_key' => array('PUBLIC_KEY', 'input'),
		'private_key' => array('PRIVATE_KEY', 'input'),
		'payment_mode' => array('ENABLE_PAYMENT_MODE', 'list',array(
			'true' => 'Test',
			'false' => 'Live')
		)
	);

/**
	* Constructs a PHP_CodeSniffer object.
	*
	* @param   string  $subject  The number of spaces each tab represents.
	* @param   string  $config   The charset of the sniffed files.
	*
	* @see process()
 * */

	public function __construct($subject, $config)
	{
		parent::__construct($subject, $config);

		// Set the language in the class
		$config = JFactory::getConfig();

		$this->code_arr = array (
		'internal_server_error'       => JText::_('INTERNAL_SERVER_ERROR'),
		'invalid_public_key'    	  => JText::_('FEEDBACK_CONFIG_ERROR_PUBLICKEY'),
		'unknown_error'               => JText::_('UNKNOWN_ERROR'),
		'3ds_cancelled'               => JText::_('3DS_CANCELLED'),
		'field_invalid_card_number'   => JText::_('FIELD_INVALID_CARD_NUMBER'),
		'field_invalid_card_exp_year' => JText::_('FIELD_INVALID_CARD_EXP_YEAR'),
		'field_invalid_card_exp_month' => JText::_('FIELD_INVALID_CARD_EXP_MONTH'),
		'field_invalid_card_exp'      => JText::_('FIELD_INVALID_CARD_EXP'),
		'field_invalid_card_cvc'      => JText::_('FEEDBACK_ERROR_CREDITCARD_CVC'),
		'field_invalid_card_holder'   => JText::_('FEEDBACK_ERROR_CREDITCARD_HOLDER'),
		'field_invalid_amount_int'    => JText::_('FIELD_INVALID_AMOUNT_INT'),
		'field_invalid_amount'        => JText::_('FIELD_INVALID_AMOUNT'),
		'field_invalid_currency'      => JText::_('FIELD_INVALID_CURRENCY'),
		'field_invalid_account_number' => JText::_('FIELD_INVALID_AMOUNT_NUMBER'),
		'field_invalid_account_holder' => JText::_('FIELD_INVALID_ACCOUNT_HOLDER'),
		'field_invalid_bank_code'     => JText::_('FEEDBACK_ERROR_DIRECTDEBIT_BANKCODE')
		);

		$this->code_arr = json_encode($this->code_arr);
	}

/**
	* set currency and amount.
	*
	* @param   array  $cart  cart info.
	*
	* @return  void
	*
	* @see process()
 * */

public function onAfterCartProductsLoad($cart)
{
		$this->amount1 = $cart->full_total->prices[0]->price_value_with_tax;
		$currency1 = $cart->full_total->prices[0]->price_currency_id;
		$db = JFactory::getDBO();
		$q_oi = "SELECT * FROM #__hikashop_currency where `currency_id`= '" . $currency1 . "'";
		$db->setQuery($q_oi);
		$result = $db->loadObject();
		$this->currency1 = $result->currency_code;
}

/**
	* validate crad details.
	*
	* @param   array  $method  pass to view.
	*
	* @return  void
	*
	* @see process()
 * */

public function needCC($method)
{
		$jinput = JFactory::getApplication()->input;

		if ($jinput->get('POST'))
		{
			$nm = $jinput->get('paymill-card-nm');
			$no = $jinput->get('paymill-card-no');

			$xreplace = substr($no, 0, 12);
			$replace_no = str_replace($xreplace, "xxxxxxxxxxxx", $no);

			$ex_mm = $jinput->get('paymill-card-ex-mm');
			$ex_yy = $jinput->get('paymill-card-ex-yy');
			$ex_cvc = $jinput->get('paymill-card-ex-cvc');
			$token = $jinput->get('token');
			$ac_no = $jinput->get('paymill-card-acc-no');
			$bank_no = $jinput->get('paymill-card-bank-no');
			$acc_country = $jinput->get('paymill-card-acc-country');
			$PAYMENT_TYPE = $jinput->get('PAYMENT_TYPE');
		}

		if (JRequest::getVar('PAYMENT_TYPE') == 'dc')
		{
			$checkdc = 'selected';
			$style = 'block';
			$stylecc = 'none';
		}
		else
		{
			$checkcc = 'selected';
			$style = 'none';
		}

		if (JRequest::getInt('token'))
		{
			$maindiv = 'none';

			if (JRequest::getVar('PAYMENT_TYPE') == 'cc')
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

		// From html layout
		$url = JURI::base() . 'plugins/hikashoppayment/paymill/img/ajax_loader.gif';

		if (JVERSION <= '3.0')
		{
			$method->custom_html .='<link href="' . JURI::base() . 'plugins/hikashoppayment/paymill/css/paymill.css" rel="stylesheet">';
		}
		else
		{
			$method->custom_html .='';
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
					if(jQuery("#radio_' . $method->payment_type . '_' . $method->payment_id . '").attr("checked") == "checked")
					{
							jQuery("#hikashop_checkout_next_button").css("display", "none");
					}
				});
				//if onclick paymill radio button
				jQuery("#radio_' . $method->payment_type . '_' . $method->payment_id . '").click(function()
				{
					jQuery("#hikashop_checkout_next_button").css("display", "none"); 
				});
				//public key parameter
				var PAYMILL_PUBLIC_KEY = "' . $method->payment_params->public_key . '";
				//test code 
				var PAYMILL_TEST_MODE  = "' . $method->payment_params->payment_mode . '";
				// payment type mode 
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
			jQuery("#paymill_button").attr("disabled", "disabled");
			if(jQuery("#token").val())
			{
				document.forms["hikashop_checkout_form"].submit();
			}
			else
			{
				var payment_type = jQuery("#paymill-payment_type").val();
				if(payment_type == "cc")
				{
					try {
						paymill.createToken({
							number:     jQuery(".paymill-card-number").val(),
							exp_month:  jQuery(".paymill-card-expiry-month").val(),
							exp_year:   jQuery(".paymill-card-expiry-year").val(),
							cvc:        jQuery(".paymill-card-cvc").val(),
							cardholder: jQuery(".paymill-card-holdername").val(),
							amount: jQuery(".paymill-card-amount").val(),
							currency: jQuery(".paymill-card-currency").val(),

						}, PaymillResponseHandler);
					} catch(e) {
						 jQuery(".paymill-payment-errors").text(e);
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
						 jQuery(".paymill-payment-errors").text(e);
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
        }
        function PaymillResponseHandler(error, result) {
			console.log(result);
			console.log(error);
			error ? logResponse(error.apierror) : logResponse(result.token);
			if (error) {
				var jason_error = ' . $this->code_arr . ';
				jQuery.each(jason_error, function(index, element) {
					if(index == error.apierror){
						var version = "' . JVERSION . '";
						if(version > "2.5.0")
						{
							jQuery(".paymill-payment-errors").addClass("alert alert-error");
						}
						else
						{
							jQuery(".paymill-payment-errors").addClass("error");
						}
						jQuery("#paymill_button").removeAttr("disabled");
						jQuery(".paymill-payment-errors").text(element);
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
        <div id="paymill-paymill_plugin" style="display:' . $maindiv . ';">
			<!-- display from-->
			<div id="loadder" style="display:none;text-align:center;"><img src="' . $url . '"/></div>
            <div class="akeeba-bootstrap">
            <!-- error display div-->
            <div class="payment-errors"></div>
                    <form id="card-tds-form" name="second" action="#" method="POST" class="form-validate form-horizontal">
						<div class="control-group">
								<label class="control-label">' . JText::_('NAME') . '</label>
								<div class="controls"><input name="paymill-card-nm" class="paymill-card-holdername" 
								type="text" size="20" value="' . $nm . '" ' . $readonlynm . ' />
								</div>
                        </div>
                        <div class="control-group">
							<label class="control-label">' . JText::_('PAYMENT_TYPE') . '</label>
								<div class="controls">
									<select id="paymill-payment_type" name="PAYMENT_TYPE" ' . $readonlytype . ' onchange="ChangeDropdowns(this.value);">
										<option value="cc" ' . $checkcc . '>' . JText::_('FRONTEND_CREDITCARD') . '</option>
										<option value="dc" ' . $checkdc . '>' . JText::_('FRONTEND_DIRECTDEBIT') . '</option>
								</select>
						</div>
						</div>
                        <div id="cc" style="display:' . $stylecc . ';">
							<div class="control-group">
									<label class="control-label">' . JText::_('CREDIT_CARD_NUMBER') . '</label>
									<div class="controls"><input class="paymill-card-number" name="paymill-card-no" type="text" maxlength="16" size="16" 
									value="' . $no . '" ' . $readonlyno . '/>
									</div>
							</div>


							<div class="control-group">
									<label class="control-label">' . JText::_('EXPIRY') . '</label>
								   <div class="controls"> <input ' . $readonlymm . ' 
								    name="paymill-card-ex-mm" class="paymill-card-expiry-month" 
								    type="text" size="2" maxlength="2" value="' . $ex_mm . '" style="width:20px;"/>/
									<input name="paymill-card-ex-yy" ' . $readonlyyy . '  
									class="paymill-card-expiry-year" type="text" size="4"  value="' . $ex_yy . '"  maxlength="4" style="margin-left: 0px;width:50px;"/>
									&nbsp;' . JText::_('FRONTEND_CREDITCARD_LABEL_CVC') . '
									<input class="paymill-card-cvc" ' . $readonlycvc . '  name="paymill-card-ex-cvc" type="text" maxlength="4" 
									size="4"  value="' . $ex_cvc . '"  style="width:65px;"/>
									</div>
							</div>
                        </div>
                        <div id="bank" style="display:' . $style . ';">

									 <div class="control-group">
											<label class="control-label">' . JText::_('FRONTEND_DIRECTDEBIT_LABEL_NUMBER') . '</label>
											<div class="controls"> <input ' . $readonlyacno . ' 
											name="paymill-card-acc-no" class="paymill-debit-number" maxlength="10" type="text" size="20" value="' . $ac_no . '" /></div>
									</div>
									 <div class="control-group">
											<label class="control-label">' . JText::_('FRONTEND_DIRECTDEBIT_LABEL_BANKCODE') . '</label>
											<div class="controls">  <input ' . $readonlybkno . ' 
											 class="paymill-debit-bank"name="paymill-card-bank-no"  maxlength="8" type="text" size="20" value="' . $bank_no . '" /></div>
									</div>

									<div class="control-group">
												<label class="control-label">' . JText::_('COUNTRY') . '</label>
												<div class="controls"><input ' . $readonlycoun . ' 
												class="paymill-debit-country" name="paymill-card-acc-country" type="text" size="20" value="' . $acc_country . '" /></div>
									</div>
                        </div>
                        <div style="display:none;"class="control-group">
								<label class="control-label">' . JText::_('AMOUNT_LABEL') . '</label>
								<div class="controls"><input class="paymill-card-amount" type="text" size="4" value="' . $this->amount1 . '" /></div>
						</div>
                        <div style="display:none;" class="control-group">
							<label class="control-label">' . JText::_('CURRENCY_LABEL') . '</label>
							<div class="controls"><input class="paymill-card-currency" type="text" size="4" value="' . $this->currency1 . '" /></div>
                       </div></div>
				<input name="token"  id="token" type="hidden"  value="' . $token . '" />
				</div>
				<div id="seconddiv" style="display:' . $secondiv . ';"> 
				Name - ' . $nm . '<br> Card No - ' . $replace_no . '<br> Expiration date - ' . $ex_mm . '/' . $ex_yy . '<br> Card validation code - xxx
				</div>
				<div id="threediv" style="display:' . $threediv . ';"> 
				Name - ' . $nm . '<br> Account Number - ' . $ac_no . '<br> Bank Code Number - ' . $bank_no . '<br> Country - ' . $acc_country . '<br>
				</div>
                     <input  onclick="submitme();" type="button" value="' . JText::_('SUBMIT') . '"/>
                    </form>
                </div>';

			return true;
}

/**
	* onBeforeOrderCreate
	*
	* @param   array  $order  order details.
	* @param   array  $do     submiited value.
	*
	* @return  void
	*
	* @see process()
 * */

	public function onBeforeOrderCreate($order,$do)
	{
		if (parent::onBeforeOrderCreate($order, $do) === true)
		{
			return true;
		}
		/*else
		{
			return false;
		}*/

		$this->ccLoad();
		$jinput = JFactory::getApplication()->input;
		$token = $jinput->get('token'); 
		$component  = $jinput->getCmd('option'); 
		$xml = JFactory::getXML(JPATH_SITE.'/administrator/components/com_hikashop/hikashop.xml');
		$comversion=(string)$xml->version;	
		$paymillxml = JFactory::getXML(JPATH_SITE.'/plugins/hikashoppayment/paymill/paymill.xml');	
		$pluginversion=(string)$paymillxml->version;			
		$source = $pluginversion.'_'.$component.'_'.$comversion; 
		
		define('PAYMILL_API_HOST', 'https://api.paymill.com/v2/');

		// FROM PAYMILL PLUGIN BACKEND
		define('PAYMILL_API_KEY', $order->cart->payment->payment_params->private_key);

		set_include_path(implode(PATH_SEPARATOR, array(realpath(realpath(dirname(__FILE__)) . '/lib'),get_include_path(),)));

		// CREATED TOKEN
		if ($token)
		{
				// Access lib folder
				require "lib/Services/Paymill/Transactions.php";

				// Pass api key and private key to Services_Paymill_Transactions function
				$transactionsObject = new Services_Paymill_Transactions(PAYMILL_API_KEY, PAYMILL_API_HOST);

				$params = array(
				'amount'      => ($order->cart->full_total->prices[0]->price_value_with_tax * 100), // Amount *100
				'currency'    => $this->currency->currency_code ,   // ISO 4217
				'token'       => $token,
				'description' => 'Test Transaction',
				'source'       => $source
				);

				$transaction = $transactionsObject->create($params);

				$status = $transaction['status'];
				$history = new stdClass();
				$history->history_notified = 0;
				$history->history_amount = round($order->cart->full_total->prices[0]->price_value_with_tax, 2) . $this->currency->currency_code;
				$history->history_data = '';

				if ($status == 'closed')
				{
						$this->modifyOrder($order, $this->payment_params->verified_status, $history, false);
				}
				elseif ($status == 'pending')
				{
						$this->modifyOrder($order, $this->payment_params->pending_status, $history, false);
				}
				elseif ($status == 'failed')
				{
						$this->modifyOrder($order, $this->payment_params->invalid_status, $history, false);
				}
				elseif ($transaction['error'])
				{
					$this->app->enqueueMessage('Sorry!! Unable further process due some technical issue');
					$error = $transaction['error'];
					$this->ccClear();
					$do = false;

					if (!empty($error))
					{
						$this->app->enqueueMessage($error);
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
		else
		{
					$this->error_paymill = 'error';
					$this->app->enqueueMessage('Your transaction was declined. Please reenter your credit card or another credit card information.');
					$this->ccClear();
					$do = false;
		}
	}

/**
	* Get Payment default values
	*
	* @param   array  $order      order details.
	* @param   array  $methods    payment method.
	* @param   int    $method_id  get method id.
	*
	* @return  void
	*
	* @see process()
 * */

	public function onAfterOrderConfirm($order, $methods, $method_id)
	{
		$this->paymill_order_id = $order->order_id;
		$this->removeCart = true;
		$method = $methods[$method_id];
		$this->return_url = @$method->payment_params->return_url;

		return $this->showPage('thanks');
	}

/**
	* Get Payment default values
	*
	* @param   array  $element  Layout name of view.
	*
	* @return  void
	*
	* @see process()
 * */

	public function getPaymentDefaultValues($element)
	{
		$element->payment_name = 'Paymill';
		$element->payment_description = 'You can pay by Credit card/ Direct debit using this payment method';
		$element->payment_images = '';

		$element->payment_params->invalid_status = 'cancelled';
		$element->payment_params->pending_status = 'created';
		$element->payment_params->verified_status = 'confirmed';
	}
}
