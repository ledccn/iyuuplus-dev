# PHP Curl Class

This library provides an object-oriented and dependency free wrapper of the PHP cURL extension.

[![Maintainability](https://api.codeclimate.com/v1/badges/6c34bb31f3eb6df36c7d/maintainability)](https://codeclimate.com/github/php-mod/curl/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/6c34bb31f3eb6df36c7d/test_coverage)](https://codeclimate.com/github/php-mod/curl/test_coverage)
[![Total Downloads](https://poser.pugx.org/curl/curl/downloads)](//packagist.org/packages/curl/curl)
[![Tests](https://github.com/php-mod/curl/actions/workflows/tests.yml/badge.svg)](https://github.com/php-mod/curl/actions/workflows/tests.yml)

If you have questions or problems with installation or usage [create an Issue](https://github.com/php-mod/curl/issues).

## Installation

In order to install this library via composer run the following command in the console:

```sh
composer require ledc/curl
```

## Usage examples

A few example for using CURL with get:

```php
use Ledc\Curl\Curl;

$curl = (new Curl())->get('http://www.example.com/');
if ($curl->isSuccess()) {
    // do something with response
    var_dump($curl->response);
}
// ensure to close the curl connection
$curl->close();
```

upload file metadata

```php
use Ledc\Curl\Curl;

$curl = new Curl();
$json = '[{"length":7533293969,"path":["Brotherhood.of.the.Wolf.2001.BluRay.1080p.x265.10bit.2Audio.mkv"]},{"length":958431,"path":["cover.jpg"]},{"length":760,"path":["Brotherhood.of.the.Wolf.2001.BluRay.1080p.x265.10bit.2Audio.nfo"]}]';
$curl->addFile('file', '1.json', $json, 'application/json');
$curl->upload('http://www.example.com/upload');
$response = $curl->response;
printf($response);
```

Or with params, values will be encoded with `PHP_QUERY_RFC1738`:

```php
use Ledc\Curl\Curl;
$curl = (new Curl())->get('http://www.example.com/search', [
    'q' => 'keyword',
]);
```

An example using post

```php
use Ledc\Curl\Curl;
$curl = new Curl();
$curl->post('http://www.example.com/login/', [
    'username' => 'myusername',
    'password' => 'mypassword',
]);
```

An exampling using basic authentication, remove default user agent and working with error handling

```php
use Ledc\Curl\Curl;
$curl = new Curl();
$curl->setBasicAuthentication('username', 'password');
$curl->setUserAgent('');
$curl->setHeader('X-Requested-With', 'XMLHttpRequest');
$curl->setCookie('key', 'value');
$curl->get('http://www.example.com/');

if ($curl->error) {
    echo $curl->error_code;
} else {
    echo $curl->response;
}

var_dump($curl->request_headers);
var_dump($curl->response_headers);
```

SSL verification setup:

```php
use Ledc\Curl\Curl;
$curl = new Curl();
$curl->setOpt(CURLOPT_RETURNTRANSFER, TRUE);
$curl->setOpt(CURLOPT_SSL_VERIFYPEER, FALSE);
$curl->get('https://encrypted.example.com/');
```

Example access to curl object:

```php
curl_set_opt($curl->curl, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1');
curl_close($curl->curl);
```

Example of downloading a file or any other content

```php
use Ledc\Curl\Curl;
$curl = new Curl();
// open the file where the request response should be written
$file_handle = fopen($target_file, 'w+');
// pass it to the curl resource
$curl->setOpt(CURLOPT_FILE, $file_handle);
// do any type of request
$curl->get('https://github.com');
// disable writing to file
$curl->setOpt(CURLOPT_FILE, null);
// close the file for writing
fclose($file_handle);
```
