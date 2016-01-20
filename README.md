Paymill plugins for various Joomla! Extensions
====================

PAYMILL extension for Joomla extensions

## Compatibility
* Virtuemart gateway compatible with Virtuemart 2.x and Joomla 2.5
* Common Payment Gateway plugin for Joomla 2.5 & 3 (Used by Social Ads, Quick2Cart, JGive and several other extensions)
* Hikashop (payment plugin for Joomla 2.5)
* J2store (Payment plugin for Joomla 2.5)
* Payplan (Payment App for Joomla 2.5 & 3)
* Redshop (Payment plugin for Joomla 2.5)
* Tienda (Payment plugin for Joomla 2.5)

## Your Advantages
* PCI DSS compatibility
* Payment means: Credit Card (Visa, Visa Electron, Mastercard, Maestro, Diners, Discover, JCB, AMEX), Direct Debit (ELV)
* Works with both checkout modes - regular and one page.

## Installation from this git repository

Download the appropriate files from the releases page

[Joomla Releases](https://github.com/paymill/paymill-joomla-plugins/releases)

* Except Virtuemart, for all other you just need to install the appropriate plugin
* For Virtuemart, install the component as well as the plugin
* The common payment gateway plugin (cpg.zip) is used by Quick2Cart, Social Ads, jGive and Hikashop


## Configuration

* In the Joomla Admin go to Extensions > Plugin Manager and configure the PAYMILL payment methods you intend to use by inserting your PAYMILL test or live keys in the PAYMILL Basic Settings. Make sure the paymill plugin is set to published/enabled.
* For Virtuemart, go to the Payment gateways section and create a new payment gateway for Paymill and publish it


## Payment Process

Currently only the direct capture method is supported
