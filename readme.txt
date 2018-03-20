=== Plugin Name ===
Contributors: websnail, swicks
Tags: kashflow, accounts, accounting, woocommerce, woothemes, snailsolutions
Requires at least:
Tested up to:
Stable tag:

KashFlow for WooCommerce

== Description ==

*****************************************
PLUGIN REVIVED 01/03/18 by websnail
*****************************************

This plugin links your orders to an online accounts package call KashFlow.

Current functionality:-
    Automatically add/update customers in Kashflow when orders are placed.
    Generate a Kashflow sales invoice & assign payment to that invoice
    Associate WooCommerce payment gateways with Kashflow payment methods (via settings)

Tested with WooCommerce version 3.3.3

This version requires WooCommerce version: Unknown


== Installation ==
Installation :

1. Download.

2. Upload to your /wp-contents/plugins/ directory.

3. Activate the plugin through the 'Plugins' menu in WordPress.

4. Goto Woocommerce -> Settings and an Kashflow Tab will appear at the top of the screen.

Configure API:

1. Add your 'username' (this is usually the same as your login name).

2. Add your API password (Recommend you do not use your normal login password) - <a rel="nofollow" title "devicesoftware kashflow setup" href="http://devicesoftware.com/setup-kashflow-api/" >setup KashFlow API</a>

3. Press the Test API to confirm that the API is correctly configured.


== Frequently Asked Questions ==

== Screenshots ==

1. Accounts settings tab in WooCommerce.
2. Customer confirmation of order being placed.
3. KashFlow's copy of the sales order which was automatically generated.


== Changelog ==

= Version 0.0.91
* Now stores the Kashflow generated Invoice_id in post_meta -> 'kashflow_invoice_id'
* Fixed: Removes the assumption/requirement that Kashflow invoice_id's match WC Order_id's
* Fixed: Correctly assigns payment to the Kashflow Invoice generated from the WC Order
* Tested: Generates Invoice correctly
* Tested: Assigns Payment to invoice

TODO: Test Invoice emailing

= DEV Version - 20180317 ==
* Multi-currency KF fields now set when non-base currency is used in transaction
* Exchange rate calculated from $order->get_total() / $order_base;
* Resolved debug log information using a workaround (chip in if you know what value the settings should be giving for checkbox)
* Detects and create invoices for "WooCommerce Advanced Purchase Order Gateway" regardless of "Only send completed orders..." setting
* Workaround for Kashflows 2 decimal point limitation tested and working.
[ NB: Can't get it to accept newline characters or codes for line description ]
* Tested as working for quotes


= DEV Version - 20180301 ==
* Forked project to revive code base for use with WooCommerce 3.3.3
* Initial WC_Order calls updated to ver 3.0 functions instead of earlier direct variable calls which were broken
* Workaround applied to deal with 2 decimal point limitation for rates on KF invoice lines
* THIS IS NOT A STABLE VERSION AND IS CURRENTLY UNTESTED


= Version 0.0.9 - 20140621 =
* Update - Added support for Coupons by adding a discount line to invoices
*
= Version 0.0.8 - 20140512 =
* Update - Added support for WooCommerce Sequential Order Numbers
*
= Version 0.0.7 - 20140122 =
* Update - Changed Woocommerce Settings tabs inline with version 2.1.0

= Version 0.0.6 - 20131024 =
* Feature - option to produce quotes or invoices

= Version 0.0.5 - 20131003 =
* Feature - logging debug information
* Update - Product SKU conversion

= Version 0.0.4 - 20130709 =
* Feature - assign a customer source of business to woocommerce.

= Version 0.0.3 - 20130527 =
* Feature - registering products within kashflow - allow stock consistancy between purchase and sales.
* Fix - VAT patch.

= Version 0.0.2 - 20130527 =
* Fix - Username & password API error resolved.

= Version 0.0.1 - 20130516 =
* Feature - Initial release.
