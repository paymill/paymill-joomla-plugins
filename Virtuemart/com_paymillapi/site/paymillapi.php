<?php
/*
 * @package 	Paymill Payment Gateway for Virtuemart
 * @copyright 	Copyright (C) 2010-2011 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license 	GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link     	http://www.techjoomla.com
 */

defined('_JEXEC') or die('Restricted access');

require_once(JPATH_COMPONENT.DS.'controller.php');

// Require specific controller if requested
if($controller = JRequest::getWord('controller')) {
    $path = JPATH_COMPONENT.DS.'controllers'.DS.$controller.'.php';
    if (file_exists($path)) {
        require_once $path;
    } else {
        $controller = '';
    }
}

//Create controller
$classname = 'PaymillapiController'.$controller;
$controller = new $classname();

//Perform task in URL
$controller->execute(JRequest::getWord('task'));

//Redirect if set by controller
$controller->redirect();
