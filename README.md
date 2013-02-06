Paymill-Prestashop
==================

Payment plugin for Prestashop Version 1.5.3.1.

  git clone --recursive https://github.com/Paymill/Paymill-PrestaShop

- Merge the content of the Paymill-Prestashop-Module directory with your Prestashop installation.
- In your administration backend install the PigmbhPaymill plugin and go to the configuration section where you can insert your private and public key (that you can find in your Paymill cockpit [https://app.paymill.de/](https://app.paymill.de/ "Paymill cockpit")).
- Finally activate the plugin and customize it to your needs under Settings > Payment methods.

# Logging

- If you enable logging in the plugin configuration make sure that log.txt inside the plugin directory is writable. Otherwise logging information will not be stored to the logfile.