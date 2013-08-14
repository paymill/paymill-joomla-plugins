<?php defined('_JEXEC') or die(); 
$jsonarr= json_encode($this->code_arr);

require_once JPATH_COMPONENT . '/helpers/helper.php';
require_once JPATH_SITE . '/administrator/components/com_redshop/helpers/redshop.cfg.php';
$Itemid = $_REQUEST['Itemid'];
if ($this->_params->get("currency") != "")
{
	$currency_main = $this->_params->get("currency");
}
else
{
	$currency_main = "EUR";
}
$order->order_subtotal = $order_details->order_total;
$returnUrl = JURI::base() . "index.php?tmpl=component&option=com_redshop&view=order_detail&controller=order_detail&task=notify_payment&payment_plugin=rs_payment_paymill&Itemid=$Itemid&orderid=" .$data['order_id'];		
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
		var PAYMILL_PUBLIC_KEY = '<?php echo $this->_params->get("public_key"); ?>';
		var PAYMILL_TEST_MODE  = <?php echo $this->_params->get("payment_mode"); ?>;
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
							number:     jQuery('#card-tds-form .card-number').val(),
							exp_month:  jQuery('#card-tds-form .card-expiry-month').val(),
							exp_year:   jQuery('#card-tds-form .card-expiry-year').val(),
							cvc:        jQuery('#card-tds-form .card-cvc').val(),
							cardholder: jQuery('#card-tds-form .card-holdername').val(),
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
						if(version > "2.5.9")
						{
							jQuery(".payment-errors").addClass('alert alert-error');
						}
						else
						{
							jQuery(".payment-errors").addClass('error');
						}
						jQuery(".payment-errors").text(element);
					}
				});
				
			}
			else
			{
					jQuery('#token').val(result.token);
					jQuery('#card-tds-form').submit();
				
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
      
        <style>
        .payment-errors {color:red;}
        </style>
       
			<div class="payment-errors"></div>
			<!-- display from-->
            <div class="akeeba-bootstrap">
                    <form id="card-tds-form" name="second" action="<?php echo $returnUrl;?>" method="POST" class="form-validate form-horizontal">
						<div class="control-group">
								<label class="control-label"><?php echo JText::_('NAME') ;?></label>
								<div class="controls"><input class="card-holdername"  type="text" size="20" value="<?php echo $vars->user_firstname;?>" />
								</div>
                        </div>
                        <div class="control-group">
							<label class="control-label"><?php echo JText::_('PAYMENT_TYPE') ;?></label>
								<div class="controls">
									<select id="payment_type" onchange="ChangeDropdowns(this.value);">
										<option value="cc" selected="true"><?php echo JText::_('CREDIT_CARD') ;?></option>
										<option value="dc"><?php echo JText::_('DEBIT_CARD') ;?></option>
								</select>
						</div>
						</div>
                        <div id="cc">
							<div class="control-group">
									<label class="control-label"><?php echo JText::_('CREDIT_CARD_NUMBER') ;?></label>
									<div class="controls"><input class="card-number" type="text" maxlength="16" value="" />
									</div>
							</div>


							<div class="control-group">
									<label class="control-label"><?php echo JText::_('EXPIRY') ;?></label>
								   <div class="controls"> <input class="card-expiry-month" type="text" size="2" maxlength="2" style="width:20px;"/>/
									<input class="card-expiry-year" type="text" size="4"  maxlength="4" style="margin-left: 0px;width:50px;"/>
									&nbsp;<?php echo JText::_('CVC') ;?><input class="card-cvc" type="text" maxlength="4" size="4" value="" style="width:65px;"/>
									</div>
							</div>
                        </div>
                        <div id="bank" style="display:none;">

									 <div class="control-group">
											<label class="control-label"><?php echo JText::_('ACCOUNT_NUMBER') ;?></label>
											<div class="controls"> <input class="debit-number" maxlength="10" type="text" size="20" value="" /></div>
									</div>
									 <div class="control-group">
											<label class="control-label"><?php echo JText::_('BANK_CODE_NUMBER') ;?></label>
											<div class="controls">  <input class="debit-bank" maxlength="8" type="text" size="20" value="" /></div>
									</div>

									<div class="control-group">
												<label class="control-label"><?php echo JText::_('COUNTRY') ;?></label>
												<div class="controls"><input class="debit-country" type="text" size="20" value="" /></div>
									</div>
                        </div>
                        <div style="display:none;"class="control-group">
								<label class="control-label"><?php echo JText::_('AMOUNT') ;?></label>
								<div class="controls"><input class="card-amount" type="text" size="4" value="<?php echo $data['order_subtotal'];?>" /></div>
						</div>
                        <div style="display:none;" class="control-group">
							<label class="control-label"><?php echo JText::_('CURRENCY') ;?></label>
							<div class="controls"><input class="card-currency" type="text" size="4" value="<?php echo "USD";?>" /></div>
                       </div></div>
						<input type="hidden" name="token"  id="token"  value="" />
						<input type="hidden" name="card-currency" size="10" value="<?php echo $currency_main;?>" />
						<input type="hidden" name="card-amount" size="10" value="<?php echo $data['order_subtotal'];?>" />
						<input type="hidden" name="order_id" size="10" value="<?php echo $data['order_id'];?>" />
						<input type="hidden" name="plugin_payment_method" value="onsite" />
				
                       <div class="form-actions"> <input  onclick="submitme();" type="button" value="<?php echo  JText::_('SUBMIT') ;?>"/></div>
                    </form>
                </div>

