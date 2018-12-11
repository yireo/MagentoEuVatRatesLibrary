# Yireo MagentoEuVatRates Library
This library offers a client to load EU tax rates via a [feed](https://github.com/yireo/Magento_EU_Tax_Rates), including parsing and caching:

    use Yireo\MagentoEuVatRates\Rates;
    $rates = (new MagentoRates)->getRatesTable();

## Installation
To install this library, use composer:

    composer require yireo/magento-eu-vat-rates-library

## Testing
This library ships with some unit tests. However, these tests rely on an internal Yireo library to validate actual rates.
