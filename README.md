Laradic Laravel Extensions
===============================

Laravel extensions work like addons. By default it scans the `extensions` directory for package directories containing a `extension.php` file.
An extension can depend on other extensions. 

#### Example extension
###### Directory structure
```js
- app
- bootstrap
- extensions
    - vendor
        - package
            - resources
            - src
                - PackageServiceProvider.php
            - composer.json
            - extension.php
- vendor
```

###### extension.php
```php
use Illuminate\Contracts\Foundation\Application;
use Laradic\Extensions\Extension;
use Laradic\Extensions\ExtensionCollection;
return array(
    'name' => 'Package',
    'slug' => 'vendor/package',
    'dependencies' => [
    ],
    'register' => function(Application $app, Extension $extension, ExtensionCollection $extensions)
    {
        
    },
    'boot' => function(Application $app, Extension $extension, ExtensionCollection $extensions)
    {
        $app->register('Vendor\Package\PackageServiceProvider');
    },
    'install' => function(Application $app, Extension $extension, ExtensionCollection $extensions)
    {

    },
    'uninstall' => function(Application $app, Extension $extension, ExtensionCollection $extensions)
    {

    }
);
```
  
- `register` is always called
- `boot` is called only if the extension is installed
- `install` and `uninstall` are called on installation and uninstall.


#### Commands
```sh
# Shows an overview of all extensions
php artisan extensions:list 

# Install an extension
php artisan extensions:install vendor/package
 
# Uninstall an extension
php artisan extensions:uninstall vendor/package 
```

#### Class methods
| Method | Description |
|--------|-------------|
| `Extensions::get('vendor/package')` | Returns the `Extension` instance |
| `Extensions::has('vendor/package')` | Returns `bool` |
| `Extensions::all()` | Returns a sorted by dependency `array` containing `Extension` instances |
| `Extensions::addPath($path)` | Adds a path to search for extensions (like the `extensions` directory) |
| `$extension->isInstalled()` | Returns `bool` |
| `$extension->install()` | Installs the extension |
| `$extension->uninstall()` | Uninstalls the extension |
| `$extension->getDependencies()` | Returns `array` |
| `$extension->getSlug()` | Returns `string` |
| `$extension->getName()` | Returns `string` |
| `$extension->getProperties()` | Returns the `extension.php` `array` |

#### Events
- `extension.installing`
- `extension.installed`
- `extension.uninstalling`
- `extension.uninstalled`
- `extension.registering`
- `extension.registered`
- `extension.booting`
- `extension.booted`

#### Config
```php
return array(
    'paths' => array(
        base_path('extensions')
    )
);
```
