{vendorName}/{packageName} extension
===============================

This package uses [laradic/extensions](https://github.com/laradic/extensions).
If installed, you can skip adding the `{namespace}\{packageName}ServiceProvider`
  
#### Installation  
###### Composer
```JSON
"{vendor}/{package}": "1.0"
```
  
###### Laravel
```php
'{namespace}\{packageName}ServiceProvider'
```

##### Configuration
```sh
php artisan vendor:publish {vendor}/{package} --tag="config"
```
  
#### Copyright/License
Copyright 2015 [Robin Radic](https://github.com/RobinRadic) - [MIT Licensed](http://radic.mit-license.org)
