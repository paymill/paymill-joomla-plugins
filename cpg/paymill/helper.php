<?php
/**
	* PHP_CodeSniffer tokenises PHP code and detects violations of a
	* defined set of coding standards.
	*
	* PHP version 5
	*
	* @category   PHP
	* @package    Paymill
	* @author     Techjoomla <support@techjoomla.com>
	* @author     Techjoomla <support@techjoomla.com>
	* @copyright  2006-2013 Techjoomla
	* @license    Techjoomla Licence
 * */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.html.html');

jimport('joomla.plugin.helper');
/**
	* PlgPaymentpaymillHelper class helper file 
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
class PlgPaymentpaymillHelper
{
/**
	* Gets the paymill URL
	*
	* @param   string  $secure  Get url.
	*
	* @return  void
	*
	* @see process()
 * */

	private function buildAuthorizenetUrl($secure = true)
	{
		$plugin = JPluginHelper::getPlugin('payment', 'paymill');
		$params = json_decode($plugin->params);
		$secure_post = $params->secure_post;
		$url = $params->sandbox ? 'test.paymill.net' : 'secure.paymill.net';

		if ($secure_post)
		{
			$url = 'https://' . $url . '/gateway/transact.dll';
		}
		else
		{
			$url = 'http://' . $url . '/gateway/transact.dll';
		}

		return $url;
	}

/**
	* Storelog function done store all data in log file.
	*
	* @param   string  $name     Payment gateway name.
	* @param   string  $logdata  Result data.
	*
	* @return  void
	*
	* @see process()
 * */

	private function Storelog($name,$logdata)
	{
		jimport('joomla.error.log');
		$options = "{DATE}\t{TIME}\t{USER}\t{DESC}";

		if (JVERSION >= '1.6.0')
		{
			$path = JPATH_SITE . '/plugins/payment/' . $name . '/' . $name . '/';
		}
		else
		{
			$path = JPATH_SITE . '/plugins/payment/' . $name . '/';
		}

		$my = JFactory::getUser();

		JLog::addLogger(
			array(
				'text_file' => $logdata['JT_CLIENT'] . '_' . $name . '.log',
				'text_entry_format' => $options,
				'text_file_path' => $path
			),
			JLog::INFO,
			$logdata['JT_CLIENT']
		);

		$logEntry = new JLogEntry('Transaction added', JLog::INFO, $logdata['JT_CLIENT']);
		$logEntry->user = $my->name . '(' . $my->id . ')';
		$logEntry->desc = json_encode($logdata['raw_data']);

		JLog::add($logEntry);
	}
}
