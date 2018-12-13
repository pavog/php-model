# PHP Model
PHP library for simple and complex database models

## About
This library was created to provide a flexible, but yet easy to use Model system, 
which can be used for new projects but also integrated into existing projects.
Every part of the library can be overwritten and replaced separately for custom logic.

**Note: This library is still in development and some features especially regarding more
driver functions will be added in the future.** 

## Installation
```
composer require aternos/model
```

## Basic usage

### Driver
The library includes some drivers, but you can also create your own drivers
and use them in your project or submit them to be added to the library.

Currently included drivers are:

* [Cache\Redis](src/Driver/Cache/Redis.php)
* [NoSQL\Cassandra](src/Driver/NoSQL/Cassandra.php)
* [Relational\Mysqli](src/Driver/Relational/Mysqli.php)
* [Search\Elasticsearch](src/Driver/Search/Elasticsearch.php)

Most drivers will work out of the box with a local database set up without
password, but for most use cases you have to use different credentials. To
do that with the included drivers, you have to create a new driver class
extending the library driver and overwrite the protected credential properties 
(either in the class itself or in the constructor), e.g.:
```php
<?php

class Mysqli extends \Aternos\Model\Driver\Relational\Mysqli 
{
    protected $user = 'username';
    protected $password = 'password';

    public function __construct()
    {
        $this->host = \Config::getHost();
    }
}
```

After that you have to register the class in the [DriverFactory](src/Driver/DriverFactory.php) 
(or create your own DriverFactory overwriting the $drivers property):
```php
<?php

\Aternos\Model\Driver\DriverFactory::getInstance()->registerDriver('relational', '\\Mysqli');
```

### Model
Now you can create a model class. All model classes have to follow the [ModelInterface](src/ModelInterface.php).
This library includes three different abstract model classes to make the model creation
easier:
 
* [BaseModel](src/BaseModel.php) - Implements the basic model logic and is not related to any Driver
* [SimpleModel](src/SimpleModel.php) - Minimal implementation for the NoSQL driver, mainly for demonstration purposes
* [GenericModel](src/GenericModel.php) - Optional implementation of all drivers and registry, by default only the relational driver is enabled

It's recommended to start with the [GenericModel](src/GenericModel.php) since it's already implements
all drivers and you can enable whatever you need (e.g. caching, searching) for every model or for
all models (by using your own parent model for all your models).

This is an example implementation of a Model using the [GenericModel](src/GenericModel.php) with a NoSQL database
as backend and caching:

```php
<?php

class User extends \Aternos\Model\GenericModel {
    // configure the generic model drivers
    // enable nosql driver
    protected static $nosql = true; 
    
    // cache the model for 60 seconds
    protected static $cache = 60;
    
    // disable default relational driver
    protected static $relational = false;
    
    // the name of your model (and table)
    public static function getName() : string
    {
        return "users";
    }
    
    // all public properties are database fields
    public $id;
    public $username;
    public $email;
}
```

If you want to implement your own driver logic in your model take a look at the [SimpleModel](src/SimpleModel.php), 
which should give you a good idea of the minimal requirements.

## Advanced usage
*More information about more advanced usage, such as writing your own drivers, driver factory or models
will be added in the future, in the meantime just take a look at the source code.*