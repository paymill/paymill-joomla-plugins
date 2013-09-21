<?php
/**
 * @package	HikaShop for Joomla!
 * @version	2.2.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2013 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
include_once(rtrim(JPATH_ADMINISTRATOR,DS).DS.'components'.DS.'com_hikashop'.DS.'helpers'.DS.'helper.php');
$order_id =$this->paymill_order_id;
$orderClass = hikashop_get('class.order');

$order = $orderClass->loadFullOrder($order_id);
//print_r($order);
$db = JFactory::getDBO();
$q_oi = "SELECT * FROM #__hikashop_currency where `currency_id`= '".$order->order_currency_id."'";

$db->setQuery($q_oi);
$result = $db->loadObject();
$currency = $result->currency_code;
?><div class="hikashop_eway_thankyou" id="hikashop_eway_thankyou">
	<span id="hikashop_eway_thankyou_message" class="hikashop_eway_thankyou_message">
		<?php echo JText::_('THANK_YOU_FOR_PURCHASE').'<br>';
		echo "order Id : ".$order->order_id.'<br>';
		echo "Status : ".$order->order_status.'<br>';
		echo "order_number : ".$order->order_number.'<br>';
		echo "Price : ".$order->order_full_price.'<br>';
		echo "Currency : ".$currency .'<br>';
		if(!empty($this->return_url)){
			echo '<br/><a href="'.$this->return_url.'">'.JText::_('GO_BACK_TO_SHOP').'</a>';
		}?>
	</span>
</div>
<?php
if(!empty($this->return_url)){
	$doc = JFactory::getDocument();
	$doc->addScriptDeclaration("window.addEvent('domready', function() {window.location='".$this->return_url."'});");
}
