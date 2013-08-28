<?php
	
JModel::addIncludePath( JPATH_ADMINISTRATOR.'/components/com_tienda/models' );		
JTable::addIncludePath( JPATH_ADMINISTRATOR.'/components/com_tienda/tables' );
Tienda::load( 'TiendaHelperCarts', 'helpers.carts' );
Tienda::load( 'TiendaHelperCurrency', 'helpers.currency' );
Tienda::load( 'TiendaHelperBase', 'helpers._base' );
$items = TiendaHelperCarts::getProductsInfo();
$orderTable = JTable::getInstance('Orders', 'TiendaTable');
//print_r($orderTable);
foreach($items as $item)
{
    $orderTable->addItem($item);
}
$items = $orderTable->getItems();
$orderTable->calculateTotals();
$amount =$orderTable->order_total;
$currency = TiendaHelperCurrency::getCurrentCurrency();
$currency = TiendaHelperCurrency::load($currency);
$currency_code = $currency->currency_code;
$jsonarr= json_encode($this->code_arr);
$urlme = JURI::ROOT().'plugins/tienda/payment_paymill/payment_paymill/images/ajax_loader.gif';
if(JVERSION <= '2.5.9')
{
		//echo '<link href="'.JURI::ROOT().'plugins/tienda/payment_paymill/payment_paymill/css/paymill.css" rel="stylesheet">';
}
?>		
<style>
#opc-payment-button
{
		display:none;
}
.error
{			padding : 5px;
			margin : 5px;
			background-color: #F2DEDE;
			border-color: #EED3D7;
			color: #B94A48;
}
</style>
<?php
if($this->params->get( 'sandbox', '0' ) == '0')
{
		$t = 'true';
}else
{
		$t = 'false';
}
 ?>


	<div class="payment-errors"></div>
	<!-- display from layout-->
	<div id="loadder" style="display:none;text-align:center;"><img src="<?php echo $urlme; ?>"/></div>
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
			   <div class="form-actions"> <input id="paymill_button" class="btn btn-primary" onclick="submitme();" type="button" value="<?php echo  JText::_('CONTINUE') ;?>"/></div>

   </div>

