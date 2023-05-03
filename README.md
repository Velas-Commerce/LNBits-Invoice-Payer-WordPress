# LNBits Bitcoin Lightning Network Invoice Payer WordPress Plugin

This WordPress plugin allows users to easily add Bitcoin rewards to their WordPress site by programmatically paying bolt 11 Lightning invoices using a low-risk LNBits wallet and API key.

## Features

- Automatically pay bolt 11 Lightning invoices with Bitcoin rewards
- Low-risk wallet integration using LNBits wallet and API key
- Simple installation and configuration

## Installation

1. Download the plugin from [GitHub](https://github.com/Velas-Commerce/LNBits-Invoice-Payer-WordPress/tree/main/bitcoin-lightning-invoice-payer).
2. Zip and upload the plugin to your WordPress `wp-content/plugins` directory.
3. Activate the plugin in your WordPress dashboard.
4. Add an `api_key.php` file to include your LNBits API key in the following format:

```php
<?php
define('LNbits_API_KEY', 'your_lnbits_api_key_here');
```

## Usage

After activating the plugin and configuring the API key, the plugin will automatically handle the payment of bolt 11 Lightning invoices using your LNBits wallet.

## Command-line arguments

There are no command-line arguments or options for this plugin.

## Examples

The plugin can be used to reward users for completing tasks, participating in events, or engaging with your WordPress site. Once the plugin is installed and configured, it will handle invoice payments automatically.

## Requirements

- WordPress 5.2 or later
- PHP 7.2 or later
- LNBits wallet and API key

For more information and support, visit the [plugin's GitHub repository](https://github.com/Velas-Commerce/LNBits-Invoice-Payer-WordPress).
