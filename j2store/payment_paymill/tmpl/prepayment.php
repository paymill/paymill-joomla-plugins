<?php
/**
 * --------------------------------------------------------------------------------
 * Payment Plugin - Paymill
 * --------------------------------------------------------------------------------
 * @package     Joomla!_2.5x_And_3.0X
 * @subpackage  J2 Store
 * @author      Techjoomla <support@techjoomla.com>
 * @copyright   Copyright (c) 2010 - 2015 Techjoomla . All rights reserved.
 * @license     GNU/GPL license: http://www.techjoomla.com/copyleft/gpl.html
 * @link        http://techjoomla.com
 * --------------------------------------------------------------------------------
 * */

defined('_JEXEC') or die('Restricted access');
?>
<form action="<?php echo JRoute::_("index.php?option=com_j2store&view=checkout"); ?>" method="post" name="adminForm" enctype="multipart/form-data">

    <div class="note">
        <?php echo JText::_("J2STORE_PAYMILL_PAYMENT_STANDARD_PREPARATION_MESSAGE"); ?>
        
        <table id="sagepay_form">            
            <tr>
                <td class="field_name"><?php echo JText::_('Credit Card Holder') ?></td>
                <td><?php echo $vars->cardholder; ?></td>
            </tr>
            <?php if ($vars->payment_mode == 'cc')
{
?>
            <tr>
                <td class="field_name"><?php echo JText::_('Card Number') ?></td>
                <td>************<?php echo $vars->cardnum_last4; ?></td>
            </tr>
            <tr>
                <td class="field_name"><?php echo JText::_('J2STORE_SAGEPAY_EXPIRATION_DATE') ?></td>
                <td><?php echo $vars->cardexp; ?></td>
            </tr>
            <tr>
                <td class="field_name"><?php echo JText::_('J2STORE_SAGEPAY_CARD_CVV') ?></td>
                <td>****</td>
            </tr> <?php
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
            </tr><?php
}
?>
        </table>
    </div>
<?php echo $vars->token;?>
    <input type='hidden' name='cardholder' value='<?php echo @$vars->cardholder; ?>'>
    <input type='hidden' name='payment_mode' value='<?php echo @$vars->payment_mode; ?>'>
    <input type='hidden' name='cardnum' value='<?php echo @$vars->cardnum; ?>'>
    <input type='hidden' name='cardexp' value='<?php echo @$vars->cardexp; ?>'>
    <input type='hidden' name='cardcvv' value='<?php echo @$vars->cardcvv; ?>'>
    <input type='hidden' name='accnum' value='<?php echo @$vars->accnum; ?>'>
    <input type='hidden' name='banknum' value='<?php echo @$vars->banknum; ?>'>
    <input type='hidden' name='country' value='<?php echo @$vars->country; ?>'>
    <input type='hidden' name='token' value='<?php echo @$vars->token12; ?>'>
    
            
    <input type="submit" class="btn btn-primary button" value="<?php echo JText::_('J2STORE_SAGEPAY_CLICK_TO_COMPLETE_ORDER'); ?>" />

    <input type='hidden' name='order_id' value='<?php echo @$vars->order_id; ?>'>
    <input type='hidden' name='orderpayment_id' value='<?php echo @$vars->orderpayment_id; ?>'>
    <input type='hidden' name='orderpayment_type' value='<?php echo @$vars->orderpayment_type; ?>'>
    <input type='hidden' name='task' value='confirmPayment'>
    <input type='hidden' name='paction' value='process'>
    <?php echo JHTML::_('form.token'); ?>
</form>
