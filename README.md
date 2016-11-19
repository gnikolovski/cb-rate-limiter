# CB Rate Limiter

Couchbase API Rate Limiter is a super simple PHP package for limiting access to 
your public API. It was originally created for one of my projects, and I'm using
it with Slimframework, but it could be used in any of your projects.

I decided to go with Couchbase, because the rest of my application was using 
this database to store data. Couchbase is a super fast NoSQL database, so it is 
perfect for this kind of tasks.

## How to install?

The easiest and recommended method to install CB Rate Limiter is via composer:

```
composer require gnikolovski/cb-rate-limiter
```

## How to use it?

Best way to use this package is as a middleware on your routes. Put it first -
before any other middleware, and if user has reached the limit you imposed, you
should return http code 429 (too many requests) with appropriate headers.

This is how you could use this package with Slimframework as a middleware:

```php
$app = new \Slim\App;

$app->add(function ($request, $response, $next) {
  $limiter = new CbRateLimiter($hostname, $bucket, $password);
  $exceeded = $limiter->isExceeded($ip_address, $max_requests, $in_minutes);

  if (!$exceeded) {
    $resp = $next($request, $response)
      ->withHeader('X-RateLimit-Limit', 10)
      ->withHeader('X-RateLimit-Remaining', $limiter->getRemaining());
  }
  else {
    $resp = $response->withStatus(429)
      ->withHeader('X-RateLimit-Limit', 10)
      ->withHeader('X-RateLimit-Remaining', 0);
  }

  return $resp;
});

$app->get('/', function ($request, $response, $args) {
	$response->getBody()->write('Hello world');
	return $response;
});

$app->run();
```

Class is instantiated with the following three variables in the constructor:

$hostname - IP address of your Couchbase server

$bucket - name of the bucket where you will store data

$password - database password

After you create $limiter object you have to call isExceeded() method and provide
the following data:

$ip_address - IP address of user trying to access API route

$max_requests - max requests you are willing to accept in a unit of time

$in_minutes - unit of time

IP address of the user could be supplied using global PHP variable:

```php
$_SERVER['REMOTE_ADDR']
```
or by using some package, like akrabat/rka-ip-address-middleware.

Variables $max_requests and $in_minutes should be integers. If you want to accept
100 requests from one IP address in 60 minutes you would write:

```php
$limiter->isExceeded($_SERVER['REMOTE_ADDR'], 100, 60);
```

## Requirements

To use Couchbase in PHP you must install PHP SDK. To find out more visit: (http://developer.couchbase.com/documentation/server/4.0/sdks/php-2.0/download-links.html)

### AUTHOR

Goran Nikolovski

Website: (http://www.gorannikolovski.com)

Email: nikolovski84@gmail.com