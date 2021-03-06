<?php
/**
 * --------------------------------------------------------------------------------
 * Payment Plugin - Paymill
 * --------------------------------------------------------------------------------
 * @package     Joomla!_2.5x_And_3.0X
 * @subpackage  Tienda
 * @author      Techjoomla <support@techjoomla.com>
 * @copyright   Copyright (c) 2010 - 2015 Techjoomla . All rights reserved.
 * @license     GNU/GPL license: http://www.techjoomla.com/copyleft/gpl.html
 * @link        http://techjoomla.com
 * --------------------------------------------------------------------------------
 * */

defined('_JEXEC') or die('Restricted access');
?>

<style type="text/css">
    #authorizedotnet_form { width: 100%; }
    #authorizedotnet_form td { padding: 5px; }
    #authorizedotnet_form .field_name { font-weight: bold; }
</style>

<form action="<?php echo JRoute::_("index.php?option=com_tienda&view=checkout"); ?>" method="post" name="adminForm" enctype="multipart/form-data">

    <div class="note">
        <?php echo JText::_('COM_TIENDA_TIENDA_AUTHORIZEDOTNET_PAYMENT_PREPARATION_MESSAGE'); ?>
        
        <table id="authorizedotnet_form">            
            <tr>
                <td class="field_name"><?php echo JText::_('COM_TIENDA_CREDIT_CARD_TYPE') ?></td>
                <td><?php echo $vars->cardtype; ?></td>
            </tr>
<?php
if ($vars->payment_mode == 'cc')
{
?>
            <tr>
                <td class="field_name"><?php echo JText::_('COM_TIENDA_CARD_NUMBER') ?></td>
                <td>************<?php echo $vars->cardnum_last4; ?></td>
            </tr>
            <tr>
                <td class="field_name"><?php echo JText::_('COM_TIENDA_EXPIRATION_DATE') ?></td>
                <td><?php echo $vars->cardexp; ?></td>
            </tr>
            <tr>
                <td class="field_name"><?php echo JText::_('COM_TIENDA_CARD_CVV_NUMBER') ?></td>
                <td>****</td>
            </tr>
<?php
}
else
{
?>
            <tr>
                <td class="field_name"><?php echo JText::_('ACCOUNT_NUMBER') ?></td>
                <td>************<?php echo $vars->accnum_last4; ?></td>
            </tr>
            <tr>
                <td class="field_name"><?php echo JText::_('BANK_CODE_NUMBER') ?></td>
                <td><?php echo $vars->banknum; ?></td>
            </tr>
            <tr>
                <td class="field_name"><?php echo JText::_('COUNTRY') ?></td>
                <td><?php echo $vars->country; ?></td>
            </tr>
<?php
}
?>
        </table>
    </div>

    <input type='hidden' name='cardtype' value='<?php echo @$vars->cardtype; ?>'>
    <input type='hidden' name='cardnum' value='<?php echo @$vars->cardnum; ?>'>
    <input type='hidden' name='cardexp' value='<?php echo @$vars->cardexp; ?>'>
    <input type='hidden' name='cardcvv' value='<?php echo @$vars->cardcvv; ?>'>
 <input type='hidden' name='accnum' value='<?php echo @$vars->accnum; ?>'>
    <input type='hidden' name='banknum' value='<?php echo @$vars->banknum; ?>'>
    <input type='hidden' name='country' value='<?php echo @$vars->country; ?>'>
    <input type='hidden' name='token' value='<?php echo @$vars->token12; ?>'>
    <input type="submit" class="button" value="<?php echo JText::_('COM_TIENDA_CLICK_HERE_TO_COMPLETE_ORDER'); ?>" id="submit_button" 
    onclick="document.getElementById('submit_button').disabled = 1; this.form.submit();" />

    <input type='hidden' name='order_id' value='<?php echo @$vars->order_id; ?>'>
    <input type='hidden' name='orderpayment_id' value='<?php echo @$vars->orderpayment_id; ?>'>
    <input type='hidden' name='orderpayment_type' value='<?php echo @$vars->orderpayment_type; ?>'>
    <input type='hidden' name='task' value='confirmPayment'>
    <input type='hidden' name='paction' value='process'>
    
    <?php echo JHTML::_('form.token'); ?>
</form>
