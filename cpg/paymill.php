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

defined('_JEXEC')or die('Restricted access');

jimport('joomla.filesystem.file');

jimport('joomla.plugin.plugin');

if (JVERSION >= '1.6.0')
{
	require_once JPATH_SITE . '/plugins/payment/paymill/paymill/helper.php';
}
else
{
	require_once JPATH_SITE . '/plugins/payment/paymill/helper.php';
}
// Set the language in the class
$lang = JFactory::getLanguage();
$lang->load('plg_payment_paymill', JPATH_ADMINISTRATOR);
/**
	* CPG Main class.
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
class Plgpaymentpaymill extends JPlugin
{
	private $_payment_gateway = 'payment_paymill';

	private $_log = null;

/**
	* Constructs a PHP_CodeSniffer object.
	*
	* @param   string  $subject  The number of spaces each tab represents.
	* @param   string  $config   The charset of the sniffed files.
	*
	* @see process()
 * */

	private function __construct($subject, $config)
	{
		parent::__construct($subject, $config);

		$config = JFactory::getConfig();

		// Define Payment Status codes in Authorise  And Respective Alias in Framework
		// closed = Approved, Pending = Declined, failed = Error, open = Held for Review

		$this->responseStatus = array(
			'closed' => 'C',
			'Pending' => 'D',
			'failed' => 'E',
			'open' => 'UR'
		);

		// Error code in api error

		$this->code_arr = array (
		'internal_server_error'       => JText::_('INTERNAL_SERVER_ERROR'),
		'invalid_public_key'          => JText::_('INVALID_PUBLIC_KEY'),
		'unknown_error'               => JText::_('UNKNOWN_ERROR'),
		'3ds_cancelled'               => JText::_('3DS_CANCELLED'),
		'field_invalid_card_number'   => JText::_('FIELD_INVALID_CARD_NUMBER'),
		'field_invalid_card_exp_year' => JText::_('FIELD_INVALID_CARD_EXP_YEAR'),
		'field_invalid_card_exp_month' => JText::_('FIELD_INVALID_CARD_EXP_MONTH'),
		'field_invalid_card_exp'      => JText::_('FIELD_INVALID_CARD_EXP'),
		'field_invalid_card_cvc'      => JText::_('FIELD_INVALID_CARD_CVC'),
		'field_invalid_card_holder'   => JText::_('FIELD_INVALID_CARD_HOLDER'),
		'field_invalid_amount_int'    => JText::_('FIELD_INVALID_AMOUNT_INT'),
		'field_invalid_amount'        => JText::_('FIELD_INVALID_AMOUNT'),
		'field_invalid_currency'      => JText::_('FIELD_INVALID_CURRENCY'),
		'field_invalid_account_number' => JText::_('FIELD_INVALID_AMOUNT_NUMBER'),
		'field_invalid_account_holder' => JText::_('FIELD_INVALID_ACCOUNT_HOLDER'),
		'field_invalid_bank_code'     => JText::_('FIELD_INVALID_BANK_CODE')
		);
		$this->public_key = $this->params->get('public_key');
		$this->private_key = $this->params->get('private_key');
		$this->testmode = $this->params->get('payment_mode', '1');
	}

/**
	* Internal use functions  @TODO move to common helper
	* Builds the layout to be shown, along with hidden fields.
	*
	* @param   string  $layout  Layout name of view.
	*
	* @return  void
	*
	* @see process()
 * */

	private function buildLayoutPath($layout="default")
	{
		if (empty($layout))
		{
			$layout = "default";
		}

		$app = JFactory::getApplication();
		$core_file = dirname(__FILE__) . DS . $this->_name . DS . 'tmpl' . DS . $layout . '.php';
		$override = JPATH_BASE . DS . 'templates' . DS .
					$app->getTemplate() . DS . 'html' . DS . 'plugins' . DS . $this->_type . DS . $this->_name . DS . $layout . '.php';

		if (JFile::exists($override))
		{
			return $override;
		}
		else
		{
			return  $core_file;
		}
	}

/**
	* Internal use functions  @TODO move to common helper
	* Builds the layout to be shown, along with hidden fields.
	*
	* @param   array  $vars    Varible pass by component.
	* @param   array  $layout  Layout name of view.
	*
	* @return  void
	*
	* @see process()
 * */

	public function buildLayout($vars, $layout = 'default' )
	{
		// Load the layout & push variables
		ob_start();
		$layout = $this->buildLayoutPath($layout);
		include $layout;
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

/**
	* This function falls under STEP 1 of the Common Payment Gateway flow
	* It is Used to Build List of Payment Gateway in the respective Components
	*
	* @param   array  $config  List of payment plugin names from component settings/config.
	* 
	* @return  object $obj       With 'name' set as in the param plugin_name and 'id' set as the plugin's filename
	* 
	* @return  void
	* 
	* @see process()
 * */

	public function onTP_GetInfo($config)
	{
		if (!in_array($this->_name, $config))
		{
			return;
		}

		$obj 		= new stdClass;
		$obj->name 	= $this->params->get('plugin_name');
		$obj->id	= $this->_name;

		return $obj;
	}

/**
	* This function falls under STEP 2 of the Common Payment Gateway flow
	* It Constructs the Payment form in case of On Site Payment gateways like Auth.net
	* OR constructs the Submit button in case of offsite
	*
	* @param   object  $vars  List of all data required by payment plugin constructed by the component
	* 
	* @return  string  HTML  To display.
	* 
	* @return  void
	* 
	* @see process()
	* 
 * */

	public function onTP_GetHTML($vars)
	{
		$session = JFactory::getSession();
		$session->set('amount', $vars->amount);
		$session->set('currency_code', $vars->currency_code);

		if (!empty($vars->payment_type) && $vars->payment_type! = '')
		{
			$payment_type = $vars->payment_type;
		}
		else
		{
			$payment_type = '';
		}

		$html = $this->buildLayout($vars, $payment_type);

		return $html;
	}

/**
 * This function falls under STEP 3 of the Common Payment Gateway flow
 * If Process on the post data from the payment and pass a fixed format data to component for further process
 *
 * @param   array  $data  Post data from gateway to notify url.
 * 
 * @return   associative  array  Gateway specific fixed format data required by the component to process payment.
 * 
 * @return void
 * 
 * @see process()
 * 
 * */

	private function onTP_Processpayment($data)
	{
		// API HOST KEY
		define('PAYMILL_API_HOST', 'https://api.paymill.com/v2/');

		// FROM PAYMILL PLUGIN BACKEND
		define('PAYMILL_API_KEY', $this->private_key);

		set_include_path(implode(PATH_SEPARATOR, array(realpath(realpath(dirname(__FILE__)) . '/lib'),get_include_path(),)));

		// CREATED TOKEN
		$token = $data["token"];
		$session = JFactory::getSession();
		$jinput   = JFactory::getApplication()->input;
		$component  = $jinput->getCmd('option'); 
		if($component == 'com_quick2cart') {
		$xml = JFactory::getXML(JPATH_SITE.'/administrator/components/com_quick2cart/quick2cart.xml'); }
		if($component == 'com_jgive') {
		$xml = JFactory::getXML(JPATH_SITE.'/administrator/components/com_jgive/jgive.xml'); }
		if($component == 'com_jticketing') {
		$xml = JFactory::getXML(JPATH_SITE.'/administrator/components/com_jticketing/jticketing.xml'); }
		if($component == 'com_socialads') {
		$xml = JFactory::getXML(JPATH_SITE.'/administrator/components/com_socialads/socialads.xml'); }
		$comversion=(string)$xml->version;	
		$paymillxml = JFactory::getXML(JPATH_SITE.'/plugins/payment/paymill/paymill.xml');	
		$pluginversion=(string)$paymillxml->version;	
		$source = $pluginversion.'_'.$component.'_'.$comversion; 
		if ($token)
		{
				// Access lib folder
				require "paymill/lib/Services/Paymill/Transactions.php";

				// Pass api key and private key to Services_Paymill_Transactions function
				$transactionsObject = new Services_Paymill_Transactions(PAYMILL_API_KEY, PAYMILL_API_HOST);

				$params = array(
				'amount'      => ($session->get('amount') * 100), // Amount *100
				'currency'    => $session->set('currency_code') ,   // ISO 4217
				'token'       => $token,
				'description' => 'Test Transaction',
				'source'       => $source
				);

				$transaction = $transactionsObject->create($params);

				if ($transaction['error'])
				{
					$result = array(
								'transaction_id' => '',
								'order_id ' => $data["order_id"],
								'status' => 'E',
								'total_paid_amt' => '0',
								'raw_data' => '',
								'error' => $transaction['error'],
								'return' => $data['return']
								);
				}
				else
				{
					// If error not find.
					// $status varible
					$status = $this->translateResponse($transaction['status']);

					// Array pass to translate function
					$result = array(
									'transaction_id' => $transaction['id'],
									'order_id' => $data["order_id"],
									'status' => $status,
									'total_paid_amt' => $transaction['origin_amount'],
									'raw_data' => json_encode($transaction),
									'error' => $transaction['error'],
									'return' => $data['return']
									);
				}
		}
		else
		{
			$result = array(
								'transaction_id' => '',
								'order_id' => $data["order_id"],
								'status' => 'E',
								'total_paid_amt' => '0',
								'raw_data' => '',
								'error' => $transaction['error'],
								'return' => $data['return']
								);
		}

		// End if token
		return $result;
	}

/**
 * Internal use functions  @TODO move to common helper
 * translate the status response depending upon you payment gateway
 * 
 * @param   array  $payment_status  Payment_status array.
 * 
 * @return   array  $value specific fixed format data required by the component to process payment.
 * 
 * @return void
 * 
 * @see process()
 * 
 * */
	private function translateResponse($payment_status)
	{
			foreach ($this->responseStatus as $key => $value)
			{
				if ($key == $payment_status)
				{
					return $value;
				}
			}
	}

/**
 * This function falls under STEP 3 of the Common Payment Gateway flow 
 * It Logs the payment process data 
 * 
 * @param   array  $data  Result send to log function.
 * 
 * @return void
 * 
 * @see process()
 * 
 * */

	private function onTP_Storelog($data)
	{
			$log = plgPaymentpaymillHelper::Storelog($this->_name, $data);
	}
}
