PAYMILL-Prestashop Extension for credit card and direct debit payments
==================

PAYMILL extension for Prestashop compatible with: 1.5+ (tested for 1.5.5.0) and 1.6 (tested for 1.6.0.5). This extension installs two payment methods: Credit card and direct debit.

## Your Advantages
* PCI DSS compatibility
* Payment means: Credit Card (Visa, Visa Electron, Mastercard, Maestro, Diners, Discover, JCB, AMEX), Direct Debit (ELV)
* Optional fast checkout configuration allowing your customers not to enter their payment detail over and over during checkout
* Improved payment form with visual feedback for your customers
* Supported Languages: German, English, Portuguese, Italian, French, Spanish
* Backend Log with custom View accessible from your shop backend

## Installation from this git repository

Download the complete module by using the link below:

[Latest Version](https://github.com/paymill/paymill-prestashop-1.5/archive/master.zip)

- Unzip the downloaded zip-file
- Rename the folder to `pigmbhpaymill`
- Zip the folder
- Upload the zip-file to your prestashop
- In your administration backend install the PigmbhPaymill plugin and go to the configuration section where you can insert your private and public key (that you can find in your Paymill cockpit [https://app.paymill.de/](https://app.paymill.de/ "Paymill cockpit")).
- Finally activate the plugin and customize it to your needs under Module > Module > PigmbhPaymill.

## In case of errors

In case of any errors turn on the debug mode and logging in the PAYMILL Settings. Open the javascript console in your browser and check what's being logged during the checkout process. To access the logged information not printed in the console please refer to the Paymill Log in the admin backend.

## Notes about the payment process

The payment is processed when an order is placed in the shop frontend.

Fast Checkout: Fast checkout can be enabled by selecting the option in the PAYMILL Basic Settings. If any customer completes a purchase while the option is active this customer will not be asked for data again. Instead a reference to the customer data will be saved allowing comfort during checkout.
