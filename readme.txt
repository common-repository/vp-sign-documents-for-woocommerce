=== VP Sign Documents for WooCommerce ===
Contributors: passatgt
Tags: signature, document, pdf, tablet
Requires at least: 5.0
Tested up to: 5.2.2
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Collect signatures from your customers after an order has been created

== Description ==

This extension for WooCommerce lets you setup documents that your customers can sign digitally after an order has been created.
This is mainly for in-person signatures, for example when your customer picks up a product from you and they need to sign a contract or proof of delivery.

* Create documents using a text editor in the admin panel. You can use placeholders to display your customer's details and also the order details.
* On the order details page, you can see a QR code which you or your customer can scan with your phone or tablet quickly to pull up the document to sign.
* After the customer digitally signs the document, it will be saved as a PDF and HTML file in your WooCommerce store.
* During the signature process, the IP address, user agent, current user(if logged in) and the current date and time will be also saved and visible on the order details.
* You can download the signature, attach to WooCommerce emails and more.

= PRO version =

In the PRO version, you can do the following extra things:

* Create multiple documents
* Setup conditional logic based on payment and shipping methods(only generate a blank document for orders with a specific payment or shipping method)
* Automatically change the status of the order after the document was signed(supports third-party statuses too)
* Attached the signed document to any WooCommerce email
* Upload the PDF file automatically to Dropbox or Google Drive using Email It In

== Installation ==

1. Download and install the extension
2. Go to WooCommerce / Settings / Documents and create a document
3. Go to the order details page and see a new box related to the document to collect the signature

== Frequently Asked Questions ==

= Where can i buy the PRO version? =
Just email me your billing details to info@visztpeter.me and i'll send you a payment request link.

= Can i ask for a signature during the checkout process? =
This is not something you can do with this extension, because this was specifically made for signatures after an order has been created.

= Can i collect multiple signatures in the same document? =
Sure, just enter multiple {signature} placeholders in the editor.

= Can i create a totally custom document template? =
Yes. Duplicate the includes/views/html-document-template.php file into your themes woocommerce folder and customize it any way you like.(just make sure you leave the existing javascript files untouched)

== Screenshots ==

1. List of documents
2. Editing a document
3. Sample document
4. Order details

== Changelog ==

= 1.0 =
* First release of this extension
