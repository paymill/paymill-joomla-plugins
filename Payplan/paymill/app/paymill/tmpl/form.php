<?php
if(defined('_JEXEC')===false) die();
$url = JURI::base().'plugins/payplans/paymill/paymill/app/paymill/tmpl/ajax_loader.gif';
		if(JVERSION <= '3.0')
		{
			echo '<link href="plugins/redshop_payment/payplans/payplans/paymill.css" rel="stylesheet">';
		}
		$code_arr= json_encode($code_arr);
		if($this->getAppParam('type') == '0')
		{
			 $mode = 'true';
		}
		else
		{
			$mode = 'false';
		}
		?>
		<script type="text/javascript" src="https://bridge.paymill.com/"></script>
        <script type="text/javascript">
		//public key parameter
		var PAYMILL_PUBLIC_KEY = '<?php echo $this->getAppParam('public_key'); ?>';
		//test code 
		var PAYMILL_TEST_MODE  = <?php echo $mode; ?>;
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
			jQuery('#pp-payment-app-buy').attr("disabled", "disabled");
				var payment_type = jQuery('#payment_type').val();
				if(payment_type == 'cc')
				{
					try {
						paymill.createToken({
							number:     jQuery('#checkout_form .card-number').val(),
							exp_month:  jQuery('#checkout_form .card-expiry-month').val(),
							exp_year:   jQuery('#checkout_form .card-expiry-year').val(),
							cvc:        jQuery('#checkout_form .card-cvc').val(),
							cardholder: jQuery('#checkout_form .card-holdername').val(),
							amount: jQuery('#checkout_form .card-amount').val(),
							currency: jQuery('#checkout_form .card-currency').val(),

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
			error ? logResponse(error.apierror) : logResponse(result.token);
			if (error) {
				var jason_error = '<?php echo $code_arr; ?>';
				//console.log(jason_error);
				var slab = jQuery.parseJSON(jason_error);
				//console.log(slab);
				jQuery.each(slab, function(index, element) {
					//console.log(index);
					if(index == error.apierror){
						var version ='<?php echo JVERSION;?>'
						if(version > "3.0")
						{
							jQuery(".payment-errors").addClass("alert alert-error");
						}
						else
						{
							jQuery(".payment-errors").addClass("error");
						}
						jQuery('#pp-payment-app-buy').removeAttr("disabled");    
						jQuery(".payment-errors").text(element);
					}
				});
			}
			else
			{
					jQuery("#loadder").css("display", "block");
					jQuery('#pp-payment-app-buy').attr("disabled", "disabled");
					jQuery('#token').val(result.token);
					jQuery('#checkout_form').submit();
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
			.error
			{
					padding : 5px;
					margin : 5px;
					background-color: #F2DEDE;
					border-color: #EED3D7;
					color: #B94A48;
			}
        </style>
<div class="pp-grid_12 pp-payment-authorize-arb">
	<?php //print_r($invoice->currency); ?>
	<form method="post" action="<?php echo $post_url;?>" id="checkout_form">
		<fieldset class="pp-parameter pp-small">
		<div class="payment-errors"></div>
		<div id="loadder" style="display:none;text-align:center;"><img src="<?php echo $url;?>"/></div>
      	<legend><?php echo XiText::_('COM_PAYPLANS_PAYMENT_APP_AUTHORIZE_CREDIT_CARD_DETAILS');?></legend>
      	<div  class="pp-primary pp-color pp-background pp-bold">         
         <?php  $currency = $invoice->getCurrency();
         		echo $this->_render('partial_amount', compact('currency', 'amount'), 'default'). XiText::_('COM_PAYPLANS_PAYMENT_APP_AUTHORIZE_PAYMENT_AMOUNT');
         ?>
        </div>
        <div class="pp-row">
          <div class="pp-col pp-label"><?php echo XiText::_('NAME');?></div>
          <div class="pp-col pp-input"><input type="text" name="paymill-card-nm" class="card-holdername"  type="text" size="20" value="" /></div>
        </div>
         <div class="pp-row">
           <div class="pp-col pp-label"><?php echo XiText::_('PAYMENT_TYPE');?></div>
          <div class="pp-col pp-input">
			  <select id="payment_type" name="PAYMENT_TYPE" onchange="ChangeDropdowns(this.value);">
					<option value="cc"><?php echo XiText::_('CREDIT_CARD'); ?></option>
					<option value="dc"><?php echo XiText::_('DEBIT_CARD'); ?></option>
			  </select>
          </div>
        </div>
         <div id="cc">
         <div class="pp-row">
          <div class="pp-col pp-label"><?php echo XiText::_('CREDIT_CARD_NUMBER');?></div>
          <div class="pp-col pp-input"><input type="text" class="card-number" name="paymill-card-no"  type="text"  size="16" value="" /></div>
        </div>
        
        <div class="pp-row">
           <div class="pp-col pp-label"><?php echo XiText::_('EXPIRY')?></div>
          <div class="pp-col pp-input"><?php
			    /*** array of months ***/
		        $months = array(
		                1=>XiText::_('JANUARY'),
		                2=>XiText::_('FEBRUARY'),
		                3=>XiText::_('MARCH'),
		                4=>XiText::_('APRIL'),
		                5=>XiText::_('MAY'),
		                6=>XiText::_('JUNE'),
		                7=>XiText::_('JULY'),
		                8=>XiText::_('AUGUST'),
		                9=>XiText::_('SEPTEMBER'),
		                10=>XiText::_('OCTOBER'),
		                11=>XiText::_('NOVEMBER'),
		                12=>XiText::_('DECEMBER'));
		        /*** current month ***/
		        $select = '<select name="card-expiry-month" class="card-expiry-month"  style="width:93px;">'."\n";
		        foreach($months as $key=>$mon)
		        {
					if($key <= 9)
					{
						$i=0;
						$key = $i.$key;
					}
		            $select .= "<option value=".$key;
		            $select .= ">$mon</option>\n";
		        }
		        $select .= '</select>';
		        echo $select;
          
		        /*** the current year ***/
				$start_year = date('Y');
				$end_year = $start_year + 20;
				
		        /*** range of years ***/
		        $rangeOfYear = range($start_year, $end_year);
		
		        /*** create the select ***/
		        $select = '<select name="card-expiry-year" class="card-expiry-year"style="width:93px;">';
		        foreach( $rangeOfYear as $year )
		        {
		            $select .= "<option value=".$year;
		            $select .= ">$year</option>\n";
		        }
		        $select .= '</select>';
         		
		        echo $select;
         ?>
         </div>
        </div>
        <div class="pp-row">
           <div class="pp-col pp-label"><?php echo XiText::_('CVC')?></div>
          <div class="pp-col pp-input"><input type="text" class="card-cvc" name="paymill-card-ex-cvc" type="text" maxlength="4"  value="" /></div>
        </div>
       </div>
       <div id="bank" style="display:none;">
		 <div class="pp-row">
			  <div class="pp-col pp-label"><?php echo XiText::_('ACCOUNT_NUMBER')?></div>
			  <div class="pp-col pp-input"><input type="text" class="debit-number" maxlength="10" value="" /></div>
        </div> 
         <div class="pp-row">
			  <div class="pp-col pp-label"><?php echo XiText::_('BANK_CODE_NUMBER')?></div>
			  <div class="pp-col pp-input"><input type="text" class="debit-bank" maxlength="8" value="" /></div>
        </div> 
		 <div class="pp-row">
			  <div class="pp-col pp-label"><?php echo XiText::_('COUNTRY')?></div>
			  <div class="pp-col pp-input"><input type="text" class="debit-country" maxlength="20" value="" /></div>
        </div> 
       </div>

      </fieldset>
      <fieldset class="pp-parameter pp-small">
      	<legend><?php echo XiText::_('COM_PAYPLANS_PAYMENT_APP_AUTHORIZE_PERSONAL_DETAILS');?></legend>
        <div class="pp-row">
          <div class="pp-col pp-label"><?php echo XiText::_('COM_PAYPLANS_PAYMENT_APP_AUTHORIZE_FIRST_NAME')?></div>
          <div class="pp-col pp-input"><input type="text" class="required pp-secondary pp-color pp-border pp-background" name="x_first_name" value="" /></div>
        </div>
        <div class="pp-row">
           <div class="pp-col pp-label"><?php echo XiText::_('COM_PAYPLANS_PAYMENT_APP_AUTHORIZE_LAST_NAME')?></div>
          <div class="pp-col pp-input"><input type="text" class="required pp-secondary pp-color pp-border pp-background" name="x_last_name" value="" /></div>
        </div>
         <div class="pp-row">
          <div class="pp-col pp-label"><?php echo XiText::_('COM_PAYPLANS_PAYMENT_APP_AUTHORIZE_EMAIL')?></div>
          <div class="pp-col pp-input"><input type="text" class="email pp-secondary pp-color pp-border pp-background" name="x_email" value="" /></div>
        </div>
        
	    <?php if ($this->getAppParam('customer_id',0)) :?>
		 <div class="pp-row">
	       <div class="pp-col pp-label"><?php echo XiText::_('COM_PAYPLANS_PAYMENT_APP_AUTHORIZE_CUSTOMER_ID')?></div>
	       <div class="pp-col pp-input"><input type="text" class="required pp-secondary pp-color pp-border pp-background" name="x_cust_id" value="" /></div>
	     </div>
        <?php endif;
        if ($this->getAppParam('phone_number',0)):?>
         <div class="pp-row">
          <div class="pp-col pp-label"><?php echo XiText::_('COM_PAYPLANS_PAYMENT_APP_AUTHORIZE_PHONE_NUMBER')?></div>
          <div class="pp-col pp-input"><input type="text" class="required pp-secondary pp-color pp-border pp-background" name="x_phone" value="" /></div>
        </div>

		<?php endif;
		if ($this->getAppParam('fax_number',0)):?>
         <div class="pp-row">
          <div class="pp-col pp-label"><?php echo XiText::_('COM_PAYPLANS_PAYMENT_APP_AUTHORIZE_FAX_NUMBER')?></div>
          <div class="pp-col pp-input"><input type="text" class="required pp-secondary pp-color pp-border pp-background" name="x_fax" value="" /></div>
        </div>
		<?php endif;
		if($this->getAppParam('company_name',0)) :?>
		<div class="pp-row">
           <div class="pp-col pp-label"><?php echo XiText::_('COM_PAYPLANS_PAYMENT_APP_AUTHORIZE_COMPANY_NAME')?></div>
           <div class="pp-col pp-input"><input type="text" class="required pp-secondary pp-color pp-border pp-background" name="x_company" value="" /></div>
        </div>
        <?php endif;?>
        <div class="pp-row">
           <div class="pp-col pp-label"><?php echo XiText::_('COM_PAYPLANS_PAYMENT_APP_AUTHORIZE_ADDRESS')?></div>
          <div class="pp-col pp-input"><input type="text" class="required pp-secondary pp-color pp-border pp-background" name="x_address" value="" /></div>
        </div>
        <div class="pp-row">
           <div class="pp-col pp-label"><?php echo XiText::_('COM_PAYPLANS_PAYMENT_APP_AUTHORIZE_CITY')?></div>
          <div class="pp-col pp-input"><input type="text" class="required pp-secondary pp-color pp-border pp-background" name="x_city" value="" /></div>
        </div>

        <div class="pp-row">
           <div class="pp-col pp-label"><?php echo XiText::_('COM_PAYPLANS_PAYMENT_APP_AUTHORIZE_STATE')?></div>
          <div class="pp-col pp-input"><input type="text" class="required pp-secondary pp-color pp-border pp-background" name="x_state" value="" /></div>
        </div>
        <div class="pp-row">
           <div class="pp-col pp-label"><?php echo XiText::_('COM_PAYPLANS_PAYMENT_APP_AUTHORIZE_ZIP_CODE')?></div>
          <div class="pp-col pp-input"><input type="text" class="required pp-secondary pp-color pp-border pp-background" name="x_zip" value="" /></div>
        </div>
        <div class="pp-row">
           <div class="pp-col pp-label"><?php echo XiText::_('COM_PAYPLANS_PAYMENT_APP_AUTHORIZE_COUNRTY')?></div>
          <div class="pp-col pp-input"><input type="text" class="required pp-secondary pp-color pp-border pp-background" name="x_country" value="" /></div>
        </div>
      </fieldset>

	 <fieldset class="pp-parameter">
	      <div class="pp-row">
	      	<div class="pp-col pp-label"><button id="pp-payment-app-buy" type="button" onclick="submitme();" class ="pp-button ui-button ui-widget ui-button-primary ui-corner-all ui-button-text-only"><?php echo XiText::_('COM_PAYPLANS_PAYMENT_APP_AUTHORIZE_BUY')?></button></div>
	  	  	<div class="pp-col pp-input"><a class="pp-button ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" href="<?php echo XiRoute::_("index.php?option=com_payplans&view=payment&task=complete&action=cancel&payment_key=".$payment->getKey()); ?>"><?php echo XiText::_('COM_PAYPLANS_PAYMENT_APP_AUTHORIZE_CANCEL')?></a></div>
	      </div>
      </fieldset>
      
		<input name="token"  id="token" type="hidden" size="20" value="" />
		<input type="hidden" class="card-currency"  name="card-currency" size="10" value="<?php echo $currency_code;?>" />
		<input type="hidden" name="payment_key" value="<?php echo $payment->getKey();?>" />
		<input type="hidden" class="card-amount" name="card-amount" value="<?php echo $amount;?>" />
	</form>
</div>
