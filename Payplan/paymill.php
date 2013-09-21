<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
/**
 * Payplans Paymill Plugin
 */
 $lang =  JFactory::getLanguage();
$lang->load('plg_payplans_paymill', JPATH_ADMINISTRATOR);

class plgPayplansPaymill extends XiPlugin
{
	
	public function onPayplansSystemStart()
	{
		$appPath = dirname(__FILE__).DS.'paymill'.DS.'app';
		PayplansHelperApp::addAppsPath($appPath);

		return true;
	}
}
