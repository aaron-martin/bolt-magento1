# Bolt Magento1 Plugin [![CircleCI](https://circleci.com/gh/BoltApp/bolt-magento1.svg?style=shield)](https://circleci.com/gh/BoltApp/bolt-magento1)

Plugin to integrate Bolt with Magento

See [CHANGELOG.md](./CHANGELOG.md) for change history.

## Supported Magento versions
+ 1.7
+ 1.9

## Supported PHP versions
5.4+

## Installation guide
[Magento 1 plugin installation guide](https://docs.bolt.com/docs/magento-integration-guide)

## Run tests

Run the following from root magento folder:

> php tests/unit/phpunit-5.7.9.phar -c tests/unit/phpunit.xml

Run phpunit test with coverage html report:

> php tests/unit/phpunit-5.7.9.phar -c tests/unit/phpunit.xml --coverage-html tests/unit/coverage

If you prefer to run test through PHPStorm, please read:

> http://devdocs.magento.com/guides/v2.2/test/unit/unit_test_execution_phpstorm.html 

## Modman guide
Our extension is set up for local development with [modman](https://github.com/colinmollenhour/modman).

To install modman, see the installation instructions [here](https://github.com/colinmollenhour/modman#installation).

Once modman is installed, change directory to your root Magento installation and run:
> modman init 

This will create an empty .modman folder in the Magento root directory. Then clone the repo by running:
> modman clone git@github.com:BoltApp/bolt-magento1.git

This will download the Bolt repository and symlink the modman Bolt files to the symlinked Magento files.
If you would like to pull the latest Bolt code from the Git repo and update Magento, simply run:
> modman update

## Custom Bolt Standard Event Reference

| Name | Area | Description | Parameters |
| --- | --- | --- | --- |
| bolt_boltpay_order_creation_before | global | Entry-point for applying any pre-conditions prior to the conversion of a Bolt order to Magento order | **immutableQuote**<br>_Mage_Sales_Model_Quote_<br>the Magento cart copy of the Bolt order<br><br>**parentQuote**<br>_Mage_Sales_Model_Quote_<br>the original Magento cart session<br><br>**transaction**<br>_object_<br>Bolt payload | 
| bolt_boltpay_order_creation_after | global | Allows for post order creation actions to be applied exclusively to Bolt orders | **order**<br>_Mage_Sales_Model_Order_<br>the converted and saved Magento order<br><br>**quote**<br>_Mage_Sales_Model_Quote_<br>the immutable quote<br><br>**transaction**<br>_object_<br>Bolt payload |
| bolt_boltpay_shipping_estimate_before | global | Performed before all shipping and tax estimates are calculated.  Custom environment state initialization logic can be set here. | **quote**<br>_Mage_Sales_Model_Quote_<br>the immutable quote<br><br>**transaction**<br>_object_<br>Bolt payload |
| bolt_boltpay_shipping_method_applied_before | global | Executed prior to a specific shipping method is applied to quote for shipping and tax calculation.  This is used for setting any shipping method specific conditions. Shipping method can be prevented from being added to Bolt by setting _quote->setShouldSkipThisShippingMethod(true)_.|  **quote**<br>_Mage_Sales_Model_Quote_<br>the immutable quote<br><br>**shippingMethodCode**<br>_string_ Shipping rate code composed of {carrier}_{method}|
| bolt_boltpay_shipping_method_applied_after | global | Executed after a specific shipping method is applied to quote for shipping and tax calculation.  Shipping method specific cleanup logic is typically performed from here. Shipping method can be prevented from being added to Bolt by setting _quote->setShouldSkipThisShippingMethod(true)_. |  **quote**<br>_Mage_Sales_Model_Quote_<br>the immutable quote<br><br>**shippingMethodCode**<br>_string_<br>Shipping rate code composed of {carrier}_{method}|
| bolt_boltpay_shipping_option_added | global | Executed after a shipping option is successfully added to what is sent to Bolt |  **quote**<br>_Mage_Sales_Model_Quote_<br>the immutable quote<br><br>**rate**<br>_Mage_Sales_Model_Quote_Address_Rate_<br>rate object of the option that was added<br><br>**option**<br>_array_<br>the actual data that is sent to Bolt for this option|

## Custom Bolt Filter Event Reference

| Name | Area | Description | Parameters | Filtered Value | 
| --- | --- | --- | --- | --- |
| bolt_boltpay_filter_shipping_label | global | Allows changing of individual shipping labels that are displayed in Bolt | **rate**<br>_Mage_Sales_Model_Quote_Address_Rate_<br>The information for this calculated rate, including method, carrier, and price | **string**<br>The label to be displayed in the Bolt order |
| bolt_boltpay_filter_success_url | global | Provides means for custom success order urls | **order**<br>_Mage_Sales_Model_Order_<br>The order to be authorized<br><br>**quoteId**<br>_int_<br>The quote id of the order which maps to the Bolt order reference | **string**<br>The url that the BoltCheckout modal will forward the customer to on successful order authorization |

