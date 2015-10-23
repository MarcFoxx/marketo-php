## Install

Using the composer CLI:

```
composer require micfai/marketo-php
```

Or manually add it to your composer.json:

```json
{
    "require": {
        "micfai/marketo-php": "^1"
    }
}
```

## Laravel Providers

In config/app.php, register the service provider

```
Marketo\Laravel\MarketoServiceProvider::class,
```

Register the Facade (optional)

```
'Marketo'       => Marketo\Laravel\MarketoFacade::class
```

Publish the config

```
php artisan vendor:publish --provider="Marketo\Laravel\MarketoServiceProvider"
```

Set your env variables

```
MARKETO_ACCESS_KEY=xxxxxxxx
MARKETO_SECRET_KEY=xxxxxxxx
MARKETO_ENDPOINT=http://localhost/auth/callback
```

Access Marketo from the Facade or Binding

```
 $lead = Marketo::getLead('type','value');

 $lead = app('Marketo')->getLead('type','value');
```
