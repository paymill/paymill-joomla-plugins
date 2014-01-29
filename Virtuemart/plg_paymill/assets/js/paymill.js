// Paymill.js
 jQuery(document).ready(function(){ 
			var or = document.getElementsByName("virtuemart_paymentmethod_id");
			for (var i = 0; i < or.length; i++) {
					or[i].checked = false;
			}
			jQuery('input:radio[name=virtuemart_paymentmethod_id]').click(function() {
				var paymethod  = jQuery('label:[for='+this.id+']').text();
				if(paymethod == 'Paymill')	
				{ jQuery(".buttonBar-right").css("display", "none"); }	
				else 
				{ jQuery(".buttonBar-right").css("display", "block"); }		
			});  
			var flag;         
		jQuery('#btn-payment-cc').click(function() { 
				jQuery("#cc").css("display", "block");
				jQuery("#dc").css("display", "none");
				jQuery("#iban").css("display", "none");
				method = 'cc';
					   
			});  
          jQuery('#btn-payment-debit').click(function() { 
				jQuery("#cc").css("display", "none");
				jQuery("#dc").css("display", "block");
				jQuery("#iban").css("display", "none");			   
				method = 'elv';
			    });
           jQuery('#btn-payment-debit-v2').click(function() { 
				jQuery("#cc").css("display", "none");
				jQuery("#dc").css("display", "none");
				jQuery("#iban").css("display", "block");	
				method = 'iban/bic';	
		});
		


       
        jQuery('#test-transaction-button').click(function(){
  			var paytype = jQuery('.btn-primary').attr('id');
			var params;
			if(paytype == 'btn-payment-cc')
			{
                   params = {
                        number: jQuery('#CC_NUMBER').val(),
                        exp_month: jQuery('#test-transaction-form-month').val(),
                        exp_year: jQuery('#test-transaction-form-year').val(),
                        cvc: jQuery('#test-transaction-form-cvc').val(),
                        amount: jQuery('#test-transaction-form-sum').attr('value').replace(/,/, "."),
                        currency: 'EUR',
                        cardholder: jQuery('#test-transaction-form-name').val()
                    };		
			}				
			if(paytype == 'btn-payment-debit')
			{ 
				var bcode = jQuery('#test-transaction-form-code').val();
                   params = {
                        number: jQuery('#test-transaction-form-account').val(),
                        bank: bcode,
                        accountholder: jQuery('#test-transaction-form-name').val()
                    };			
			}
			if(paytype == 'btn-payment-debit-v2')
			{
                    params = {
                        iban: jQuery('#test-transaction-form-iban').val(),
                        bic: jQuery('#test-transaction-form-bic').val(),
                        accountholder: jQuery('#test-transaction-form-name').val()
                    };			
			}
            paymill.createToken(params, tokenResponseHandler);
            return false;
        });

        function tokenResponseHandler(error,result) { 	
		
            if (error) { 
				 	jQuery.ajax({
					type: "POST",
					url: 'index.php?option=com_paymillapi&task=transError&error='+error.apierror+'&format=raw',
					data: {flag:true},
					cache: false,
					success: function(html)
					{
						alert(html);
					}
					});   
				
            } else { 
                var token = result.token;
                var amount = jQuery('#test-transaction-form-sum').attr('value');
                amount = amount.replace(/,/, ".");
                amount = parseFloat(amount);
                amount *= 100;
                amount = Math.round(amount);
                if( isNaN(amount) || amount <= 0) {
                    alert( jQuery('#sum_error').text() );
                    return false;
                }
                var account = jQuery('#test-transaction-form-account').attr('value');
                var vmpid = jQuery('input:radio[name=virtuemart_paymentmethod_id]:checked').val();
 				jQuery.ajax({
					type: "POST",
					url: 'index.php?option=com_paymillapi&task=chkPayment&token='+token+'&format=raw',
					data: {currency:'EUR', amount:amount, token:token, vmpid:vmpid },
					cache: false,
					success: function(html)
					{
						jQuery('#paymentForm').submit();
						return true;
					}
					});               
            }
        }

        function paymillPreconditionErrorHandler(response) {
            var data = jQuery.parseJSON(response.responseText);
            var msg = '';
            if (data && data.error) {
                jQuery('#test-transaction-form').hide();

                for (var key in data.error.messages) {
                    msg += data.error.messages[key] + " ";
                }
                jQuery('#test-transaction-apierror').text(data.error.field + ' ' + msg ).show();
            }
        }
        function paymillErrorHandler(response) {
            var data = jQuery.parseJSON(response.responseText);
            if (data && data.error) {
                jQuery('#test-transaction-form').hide();
                jQuery('#test-transaction-apierror').text(data.error).show();
            }
        }

        jQuery('.btn-payment').click(function() {
            if (jQuery(this).hasClass('btn-primary')) return;

            jQuery('.btn-payment').removeClass('btn-primary');
            jQuery(this).addClass('btn-primary');
            var index = jQuery('.btn-payment').index(this);

            jQuery('.payment-input').hide();
            jQuery('.payment-input').eq(index).show();
        });

        jQuery('.back-to-form-link').live('click', function(event) {
            jQuery('#test-transaction-form').show();
            jQuery('.test-transaction-messages').hide();
            event.preventDefault();
            return false;
        });

	 });
	 
