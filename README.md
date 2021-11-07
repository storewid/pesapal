This is a php library for intergrating with - [pesapal service] (https://developer.pesapal.com/) More information of this can be found here

[![Latest Version on Packagist](https://img.shields.io/packagist/v/storewid/pesapal.svg?style=flat-square)](https://packagist.org/packages/storewid/pesapal)
[![Total Downloads](https://img.shields.io/packagist/dt/storewid/pesapal.svg?style=flat-square)](https://packagist.org/packages/storewid/pesapal)
![GitHub Actions](https://github.com/storewid/pesapal/actions/workflows/main.yml/badge.svg)

This is where your description should go. Try and limit it to a paragraph or two, and maybe throw in a mention of what PSRs you support to avoid any confusion with users and contributors.

## Installation

You can install the package via composer:

```bash
composer require storewid/pesapal
```

## Usage

```php
<?php

namespace App\Http\Controllers;

use   Storewid\Pesapal;

use Illuminate\Http\Request;
class TransactionController extends Controller
{
//


    public function customer_makepayment(){


      $payment=new Pesapal($key, $secret, $endpoint, $currency, $callback,null);

     $response=$payment->processpayment($firstname, $lastname, $phone_number, $email, $amount, $description, $reference, $type = "MERCHANT");

   //response will be an iframe
    echo  $response;

    }
```

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email devs@storewid.com instead of using the issue tracker.

## Credits

- [Storewid](https://github.com/storewid)
- [Emmanuel Mnzava](https://github.com/dbrax)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
