<?php defined('_JEXEC') or die(); 
$jsonarr= json_encode($this->code_arr);
$url = JURI::ROOT().'plugins/j2store/payment_paymill/payment_paymill/images/ajax_loader.gif';
if(JVERSION <= '3.0')
{
	echo '<link href="plugins/rj2store/payment_paymill/payment_paymill/css/paymill.css" rel="stylesheet">';
}
if($this->private_key == '0')
{
  $t = 'true';	
}
else
{
  $t = 'false';
}
require_once (JPATH_SITE.'/components/com_j2store/helpers/cart.php');
$access = new J2StoreHelperCart();
$amount = $access->getTotal();
$j2store_params = JComponentHelper::getParams('com_j2store');
$currency_code = $j2store_params->get('currency_code');
?>
<!-- hidden from for token save-->
<style>
.error
{			padding : 5px;
			margin : 5px;
			background-color: #F2DEDE;
			border-color: #EED3D7;
			color: #B94A48;
}

</style>
	     <script type="text/javascript" src="https://bridge.paymill.com/"></script>
      
        <script type="text/javascript">
		var PAYMILL_PUBLIC_KEY = '<?php echo $this->public_key; ?>';
		var PAYMILL_TEST_MODE  = <?php echo $t; ?>;
		jQuery("#button-payment-method").css("display", "none");
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
				var payment_type = jQuery('#payment_type').val();
				
				if(payment_type == 'cc')
				{
					try {
						paymill.createToken({
							number:     jQuery(' .card-number').val(),
							exp_month:  jQuery(' .card-expiry-month').val(),
							exp_year:   jQuery('.card-expiry-year').val(),
							cvc:        jQuery(' .card-cvc').val(),
							cardholder: jQuery(' .card-holdername').val(),
							amount: jQuery('#card-tds-form .card-amount').val(),
							currency: jQuery('#card-tds-form .card-currency').val(),

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
							number: jQuery('.debit-number').val(),
							bank:  jQuery('.debit-bank').val(),
							country:   jQuery('.debit-country').val(),
							accountholder: jQuery('.card-holdername').val()
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

        function PaymillResponseHandler(error, result) {
			//console.log(error);
			error ? logResponse(error.apierror) : logResponse(result.token);
			if (error) {
				var jason_error = '[<?php echo $jsonarr; ?>]';
				var slab = jQuery.parseJSON(jason_error);
				//console.log(slab);
				jQuery.each(slab[0], function(index, element) {
					if(index == error.apierror){
						var version = '<?php echo JVERSION;?>';
						//alert(version);
						if(version >= "3.0")
						{
							jQuery(".payment-errors").addClass('alert alert-error');
						}
						else
						{
							jQuery(".payment-errors").addClass('error');
						}
						//jQuery(".payment-errors").addClass('alert alert-error');
						jQuery('#paymill_button').removeAttr("disabled");    
						jQuery(".payment-errors").text(element);
					}
				});
				
			}
			else
			{
					jQuery('#token12').val(result.token);
					jQuery("#button-payment-method").click();
			}

        }
        function logResponse(res)
        {
            // create console.log to avoid errors in old IE browsers
            if (!window.console) console = {log:function(){}};
            //console.log(res);
            if(PAYMILL_TEST_MODE)
            jQuery('.debug').text(res).show().fadeOut(3000);
        }
        </script>
       
			<div class="payment-errors"></div>
			<!-- display from-->
			<div><?php echo JText::_('PAYMILL_HEAD_LINE') ;?></div><br>
			<div id="loadder" style="display:none;text-align:center;"><img src="<?php echo $url;?>"/></div>
            <div class="akeeba-bootstrap">
						<div id="field">
						<div class="control-group">
								<label class="control-label"><?php echo JText::_('NAME') ;?></label>
								<div class="controls"><input class="card-holdername" name="cardholder" type="text" size="20" 
								value="<?php echo !empty($vars->prepop['x_card_holder']) ? ($vars->prepop['x_card_holder']) : '' ?>" />
								</div>
                        </div>
                        <div class="control-group">
							<label class="control-label"><?php echo JText::_('PAYMENT_TYPE') ;?></label>
								<div class="controls">
									<select id="payment_type" name="payment_mode" onchange="ChangeDropdowns(this.value);">
										<option value="cc" selected="true"><?php echo JText::_('CREDIT_CARD') ;?></option>
										<option value="dc"><?php echo JText::_('DEBIT_CARD') ;?></option>
								</select>
						</div>
						</div>
                        <div id="cc">
							<div class="control-group">
									<label class="control-label"><?php echo JText::_('CREDIT_CARD_NUMBER') ;?></label>
									<div class="controls"><input class="card-number" name="cardnum" type="text" maxlength="16" value="" />
									</div>
							</div>


							<div class="control-group">
									<label class="control-label"><?php echo JText::_('EXPIRY') ;?></label>
								   <div class="controls"> <input class="card-expiry-month" name="month" type="text" size="2" maxlength="2" style="width:20px;"/>/
									<input class="card-expiry-year" name="year" type="text" size="4"  maxlength="4" style="margin-left: 0px;width:50px;"/>
									&nbsp;<?php echo JText::_('CVC') ;?><input class="card-cvc" name="cardexp" type="text" maxlength="4" size="4" value="" style="width:65px;"/>
									</div>
							</div>
                        </div>
                        <div id="bank" style="display:none;">

									 <div class="control-group">
											<label class="control-label"><?php echo JText::_('ACCOUNT_NUMBER') ;?></label>
											<div class="controls"> <input class="debit-number" name="accnum" maxlength="10" type="text" size="20" value="" /></div>
									</div>
									 <div class="control-group">
											<label class="control-label"><?php echo JText::_('BANK_CODE_NUMBER') ;?></label>
											<div class="controls">  <input class="debit-bank" name="banknum" maxlength="8" type="text" size="20" value="" /></div>
									</div>

									<div class="control-group">
												<label class="control-label"><?php echo JText::_('COUNTRY') ;?></label>
												<div class="controls"><input class="debit-country" name="country" type="text" size="20" value="" /></div>
									</div>
                        </div>
                        </div>
								<input type="hidden" name="token12"  id="token12"  value="" />
								<input class="card-amount" type="hidden" size="10" value="<?php echo $amount;?>" />
								<input class="card-currency" type="hidden" size="10" value="<?php echo $currency_code;?>" />
						</div>
                       <div class="form-actions"> <input id="paymill_button" class="button btn btn-primary"  onclick="submitme();" type="button" value="<?php echo  JText::_('SUBMIT') ;?>"/></div>
                    
                </div>

