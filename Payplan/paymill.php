<?php
/**
 * --------------------------------------------------------------------------------
 * Payment Plugin - Paymill
 * --------------------------------------------------------------------------------
 * @package     Joomla!_2.5x_And_3.0X
 * @subpackage  Payplan
 * @author      Techjoomla <support@techjoomla.com>
 * @copyright   Copyright (c) 2010 - 2015 Techjoomla . All rights reserved.
 * @license     GNU/GPL license: http://www.techjoomla.com/copyleft/gpl.html
 * @link        http://techjoomla.com
 * --------------------------------------------------------------------------------
 * */

defined('_JEXEC') or die('Restricted access');

/**
 * Payplans Paymill Plugin
 * */

$lang = JFactory::getLanguage();
$lang->load('plg_payplans_paymill', JPATH_ADMINISTRATOR);

/**
	* PlgPayplansPaymill class.
	*
	* @category   PHP
	* @package    Paymill
	* @author     Techjoomla <support@techjoomla.com>
	* @author     Techjoomla <support@techjoomla.com>
	* @copyright  2006-2013 Techjoomla 
	* @license    Techjoomla Licence
	* @link       techjoomla.com
	* @since      new
 * */

class PlgPayplansPaymill extends XiPlugin
{
/**
	* onPayplansSystemStart
	*
	* @return  void
	*
	* @see process()
 * */

	public function onPayplansSystemStart()
	{
		$appPath = dirname(__FILE__) . DS . 'paymill' . DS . 'app';
		PayplansHelperApp::addAppsPath($appPath);

		return true;
	}
}
