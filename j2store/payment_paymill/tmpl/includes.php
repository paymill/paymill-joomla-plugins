<?
/**************************************************************************************************
* Form PHP Kit Includes File
**************************************************************************************************

**************************************************************************************************
* Change history
* ==============
*
* 10/02/2009 - Simon Wolfe - Updated for protocol 2.23
* 18/10/2007 - Nick Selby - New kit version
**************************************************************************************************
* Description
* ===========
*
* Page with no visible content, but defines the constants and functions used in other pages in the
* kit.  It can also be used to open database connections to the database and defines record sets for later use.
* It is included at the top of every other page in the kit and is paried with the closedown scipt.
**************************************************************************************************/

//ob_start();

/**************************************************************************************************
* Values for you to update
**************************************************************************************************/

$strVirtualDir="pspfk2"; //Change if you have created a Virtual Directory in IIS with a different name

/** IMPORTANT.  Set the strYourSiteFQDN value to the Fully Qualified Domain Name of your server. **
** This should start http:// or https:// and should be the name by which our servers can call back to yours **
** i.e. it MUST be resolvable externally, and have access granted to the Sage Pay servers **
** examples would be https://www.mysite.com or http://212.111.32.22/ **
** NOTE: You should leave the final / in place. **/

$strEncryptionPassword=$this->_getParam( 'enc_password' );  /** Set this value to the XOR Encryption password assigned to you by Sage Pay **/
//$strEncryptionPassword=""; //simulator
$strCurrency=$this->_currency; /** Set this to indicate the currency in which you wish to trade. You will need a merchant number in this currency **/
$strTransactionType="PAYMENT"; /** This can be DEFERRED or AUTHENTICATE if your Sage Pay account supports those payment types **/
$strPartnerID=""; /** Optional setting. If you are a Sage Pay Partner and wish to flag the transactions with your unique partner id set it here. **/

/* Optional setting. 
** 0 = Do not send either customer or vendor e-mails, 
** 1 = Send customer and vendor e-mails if address(es) are provided(DEFAULT). 
** 2 = Send Vendor Email but not Customer Email. If you do not supply this field, 1 is assumed and e-mails are sent if addresses are provided. **/
$bSendEMail=2; 
//$strVendorEMail=""; /** Optional setting. Set this to the mail address which will receive order confirmations and failures **/

/**************************************************************************************************
* Global Definitions for this site
**************************************************************************************************/

$strProtocol="2.23";

/**************************************************************************************************
* Useful functions for all pages in this kit
***************************************************************************************************/

//Function to redirect browser to a specific page
function redirect($url) {
   if (!headers_sent())
       header('Location: '.$url);
   else {
       echo '<script type="text/javascript">';
       echo 'window.location.href="'.$url.'";';
       echo '</script>';
       echo '<noscript>';
       echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
       echo '</noscript>';
   }
}

/* The getToken function.                                                                                         **
** NOTE: A function of convenience that extracts the value from the "name=value&name2=value2..." reply string **
** Works even if one of the values is a URL containing the & or = signs.                                      	  */

function getToken($thisString) {

  // List the possible tokens
  $Tokens = array(
    "Status",
    "StatusDetail",
    "VendorTxCode",
    "VPSTxId",
    "TxAuthNo",
    "Amount",
    "AVSCV2", 
    "AddressResult", 
    "PostCodeResult", 
    "CV2Result", 
    "GiftAid", 
    "3DSecureStatus", 
    "CAVV",
	"AddressStatus",
	"CardType",
	"Last4Digits",
	"PayerStatus","CardType");



  // Initialise arrays
  $output = array();
  $resultArray = array();
  
  // Get the next token in the sequence
  for ($i = count($Tokens)-1; $i >= 0 ; $i--){
    // Find the position in the string
    $start = strpos($thisString, $Tokens[$i]);
	// If it's present
    if ($start !== false){
      // Record position and token name
      $resultArray[$i]->start = $start;
      $resultArray[$i]->token = $Tokens[$i];
    }
  }
  
  // Sort in order of position
  sort($resultArray);
	// Go through the result array, getting the token values
  for ($i = 0; $i<count($resultArray); $i++){
    // Get the start point of the value
    $valueStart = $resultArray[$i]->start + strlen($resultArray[$i]->token) + 1;
	// Get the length of the value
    if ($i==(count($resultArray)-1)) {
      $output[$resultArray[$i]->token] = substr($thisString, $valueStart);
    } else {
      $valueLength = $resultArray[$i+1]->start - $resultArray[$i]->start - strlen($resultArray[$i]->token) - 2;
	  $output[$resultArray[$i]->token] = substr($thisString, $valueStart, $valueLength);
    }      

  }

  // Return the ouput array
  return $output;
}

// Filters unwanted characters out of an input string.  Useful for tidying up FORM field inputs.
function cleanInput($strRawText,$strType) {

	if ($strType=="Number") {
		$strClean="0123456789.";
		$bolHighOrder=false;
	}
	else if ($strType=="VendorTxCode") {
		$strClean="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.";
		$bolHighOrder=false;
	}
	else {
  		$strClean=" ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789.,'/{}@():?-_&�$=%~<>*+\"";
		$bolHighOrder=true;
	}
	
	$strCleanedText="";
	$iCharPos = 0;
		
	do
		{
    		// Only include valid characters
			$chrThisChar=substr($strRawText,$iCharPos,1);
			
			if (strspn($chrThisChar,$strClean,0,strlen($strClean))>0) { 
				$strCleanedText=$strCleanedText . $chrThisChar;
			}
			else if ($bolHighOrder==true) {
				// Fix to allow accented characters and most high order bit chars which are harmless 
				if (bin2hex($chrThisChar)>=191) {
					$strCleanedText=$strCleanedText . $chrThisChar;
				}
			}
			
		$iCharPos=$iCharPos+1;
		}
	while ($iCharPos<strlen($strRawText));
		
  	$cleanInput = ltrim($strCleanedText);
	return $cleanInput;
	
}

/* Base 64 Encoding function **
** PHP does it natively but just for consistency and ease of maintenance, let's declare our own function **/

function base64Encode($plain) {
  // Initialise output variable
  $output = "";
  
  // Do encoding
  $output = base64_encode($plain);
  
  // Return the result
  return $output;
}

/* Base 64 decoding function **
** PHP does it natively but just for consistency and ease of maintenance, let's declare our own function **/

function base64Decode($scrambled) {
  // Initialise output variable
  $output = "";
  
  // Fix plus to space conversion issue
  $scrambled = str_replace(" ","+",$scrambled);
  
  // Do encoding
  $output = base64_decode($scrambled);
  
  // Return the result
  return $output;
}


/*  The SimpleXor encryption algorithm                                                                                **
**  NOTE: This is a placeholder really.  Future releases of Form will use AES or TwoFish.  Proper encryption      **
**  This simple function and the Base64 will deter script kiddies and prevent the "View Source" type tampering        **
**  It won't stop a half decent hacker though, but the most they could do is change the amount field to something     **
**  else, so provided the vendor checks the reports and compares amounts, there is no harm done.  It's still          **
**  more secure than the other PSPs who don't both encrypting their forms at all                                      */

function simpleXor($InString, $Key) {
  // Initialise key array
  $KeyList = array();
  // Initialise out variable
  $output = "";
  
  // Convert $Key into array of ASCII values
  for($i = 0; $i < strlen($Key); $i++){
    $KeyList[$i] = ord(substr($Key, $i, 1));
  }

  // Step through string a character at a time
  for($i = 0; $i < strlen($InString); $i++) {
    // Get ASCII code from string, get ASCII code from key (loop through with MOD), XOR the two, get the character from the result
    // % is MOD (modulus), ^ is XOR
    $output.= chr(ord(substr($InString, $i, 1)) ^ ($KeyList[$i % strlen($Key)]));
  }

  // Return the result
  return $output;
}

// Function to check validity of email address entered in form fields
function is_valid_email($email) {
  $result = TRUE;
  if(!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$", $email)) {
    $result = FALSE;
  }
  return $result;
}

?>