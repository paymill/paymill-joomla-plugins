<?php
defined ('_JEXEC') or die('Restricted access');

/**
 *
 * @package    VirtueMart
 * @subpackage Plugins  - Elements
 * @author Valérie Isaksen
 * @link http://www.virtuemart.net
 * @copyright Copyright (c) 2004 - 2011 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id$
 */

class JElementPaymillCurl extends JElement {
	var $_name = 'paymillcurl';
	function fetchElement ($name, $value, &$node, $control_name) {

		if (!function_exists ('curl_init') or !function_exists ('curl_exec')) {
			return JText::_ ('VMPAYMENT_PAYMILL_CURL_LIBRARY_NOT_INSTALLED');
		}
		else {
			return JText::_ ('VMPAYMENT_PAYMILL_CURL_LIBRARY_INSTALLED');
		}
	}
}
