<?php

namespace Ledc\Curl;

use CurlFile;
use CurlHandle;
use CURLStringFile;
use Error;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Curl
 */
class Curl
{
    /**
     * @var string The user agent name which is set when making a request
     */
    const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36';

    // The HTTP authentication method(s) to use.

    /**
     * @var int Type AUTH_BASIC
     */
    const AUTH_BASIC = CURLAUTH_BASIC;

    /**
     * @var int Type AUTH_DIGEST
     */
    const AUTH_DIGEST = CURLAUTH_DIGEST;

    /**
     * @var int Type AUTH_GSSNEGOTIATE
     */
    const AUTH_GSSNEGOTIATE = CURLAUTH_GSSNEGOTIATE;

    /**
     * @var int Type AUTH_NTLM
     */
    const AUTH_NTLM = CURLAUTH_NTLM;

    /**
     * @var int Type AUTH_ANY
     */
    const AUTH_ANY = CURLAUTH_ANY;

    /**
     * @var int Type AUTH_ANYSAFE
     */
    const AUTH_ANYSAFE = CURLAUTH_ANYSAFE;

    /**
     * @var array
     */
    protected array $_headers = [];
    /**
     * @var array<string, CURLStringFile>
     */
    protected array $files = [];
    /**
     * Cookies
     * @var array
     */
    protected array $_cookies = [];

    /**
     * @var resource|false|CurlHandle Contains the curl resource created by `curl_init()` function
     */
    public $curl;

    /**
     * @var bool Whether an error occurred or not
     */
    public bool $error = false;

    /**
     * @var int Contains the error code of the current request, 0 means no error happened
     */
    public int $error_code = 0;

    /**
     * @var string|null If the curl request failed, the error message is contained
     */
    public ?string $error_message = null;

    /**
     * @var bool Whether an error occurred or not
     */
    public bool $curl_error = false;

    /**
     * @var int Contains the error code of the current request, 0 means no error happened.
     * @see https://curl.haxx.se/libcurl/c/libcurl-errors.html
     */
    public int $curl_error_code = 0;

    /**
     * @var string|null If the curl request failed, the error message is contained
     */
    public ?string $curl_error_message = null;

    /**
     * @var bool Whether an error occurred or not
     */
    public bool $http_error = false;

    /**
     * @var int Contains the status code of the current processed request.
     */
    public int $http_status_code = 0;

    /**
     * @var string|null If the curl request failed, the error message is contained
     */
    public ?string $http_error_message = null;

    /**
     * @var string|array|null TBD (ensure type) Contains the request header information
     */
    public string|array|null $request_headers = null;

    /**
     * @var string|array TBD (ensure type) Contains the response header information
     */
    public string|array $response_headers = [];

    /**
     * @var string|false|null Contains the response from the curl request
     */
    public string|null|false $response = null;

    /**
     * @var bool Whether the current section of response headers is after 'HTTP/1.1 100 Continue'
     */
    protected bool $response_header_continue = false;

    /**
     * 构造函数
     * @throws RuntimeException
     */
    public function __construct()
    {
        if (!extension_loaded('curl')) {
            throw new RuntimeException('The cURL extensions is not loaded, make sure you have installed the cURL extension: https://php.net/manual/curl.setup.php');
        }

        $this->init();
    }

    /**
     * Initializer for the curl resource.
     *
     * Is called by the __construct() of the class or when the curl request is reset.
     * @access private
     * @return void
     */
    private function init(): void
    {
        $this->curl = curl_init();
        $this->setUserAgent(static::USER_AGENT);
        $this->setTimeout(3, 5);
        $this->setOpt(CURLINFO_HEADER_OUT, true);
        $this->setOpt(CURLOPT_HEADER, false);
        $this->setOpt(CURLOPT_RETURNTRANSFER, true);
    }

    /**
     * Handle writing the response headers
     *
     * @param resource $curl The current curl resource
     * @param string $header_line A line from the list of response headers
     *
     * @return int Returns the length of the $header_line
     */
    public function addResponseHeaderLine($curl, string $header_line): int
    {
        $trimmed_header = trim($header_line, "\r\n");

        if ($trimmed_header === "") {
            $this->response_header_continue = false;
        } elseif (strtolower($trimmed_header) === 'http/1.1 100 continue') {
            $this->response_header_continue = true;
        } elseif (!$this->response_header_continue) {
            $this->response_headers[] = $trimmed_header;
        }

        return strlen($header_line);
    }

    /**
     * Execute the curl request based on the respective settings.
     *
     * @return int Returns the error code for the current curl request
     * @access protected
     */
    public function exec(): int
    {
        $this->setOpt(CURLOPT_HEADERFUNCTION, array($this, 'addResponseHeaderLine'));
        $this->response_headers = [];
        $this->response = curl_exec($this->curl);
        $this->curl_error_code = curl_errno($this->curl);
        $this->curl_error_message = curl_error($this->curl);
        $this->curl_error = !($this->getErrorCode() === 0);
        $this->http_status_code = intval(curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
        $this->http_error = $this->isError();
        $this->error = $this->curl_error || $this->http_error;
        $this->error_code = $this->error ? ($this->curl_error ? $this->getErrorCode() : $this->getHttpStatus()) : 0;
        $this->request_headers = preg_split('/\r\n/', curl_getinfo($this->curl, CURLINFO_HEADER_OUT), -1, PREG_SPLIT_NO_EMPTY);
        $this->http_error_message = $this->error ? ($this->response_headers['0'] ?? '') : '';
        $this->error_message = $this->curl_error ? $this->getErrorMessage() : $this->http_error_message;
        $this->setOpt(CURLOPT_HEADERFUNCTION, null);
        return $this->error_code;
    }

    /**
     * @param object|array|string $data
     */
    protected function preparePayload(object|array|string $data): void
    {
        $this->setOpt(CURLOPT_POST, true);

        if (is_array($data) || is_object($data)) {
            $skip = false;
            foreach ($data as $key => $value) {
                if (($value instanceof CurlFile) || ($value instanceof CURLStringFile)) {
                    $skip = true;
                }
            }

            if (!$skip) {
                $data = http_build_query($data);
            }
        }

        $this->setOpt(CURLOPT_POSTFIELDS, $data);
    }

    /**
     * Set the json payload informations to the postfield curl option.
     *
     * @param object|array|string $data The data to be sent.
     * @return void
     */
    protected function prepareJsonPayload(object|array|string $data): void
    {
        $this->setOpt(CURLOPT_POST, true);
        $this->setOpt(CURLOPT_POSTFIELDS, json_encode($data));
        $this->setContentType('application/json; charset=UTF-8');
    }

    /**
     * Set auth options for the current request.
     *
     * Available auth types are:
     *
     * + self::AUTH_BASIC
     * + self::AUTH_DIGEST
     * + self::AUTH_GSSNEGOTIATE
     * + self::AUTH_NTLM
     * + self::AUTH_ANY
     * + self::AUTH_ANYSAFE
     *
     * @param int $httpauth The type of authentication
     */
    protected function setHttpAuth(int $httpauth): void
    {
        $this->setOpt(CURLOPT_HTTPAUTH, $httpauth);
    }

    /**
     * Make a get request with optional data.
     *
     * The get request has no body data, the data will be correctly added to the $url with the http_build_query() method.
     *
     * @param string $url The url to make the get request for
     * @param array $data Optional arguments who are part of the url
     * @return self
     */
    public function get(string $url, array $data = []): static
    {
        $this->setOpt(CURLOPT_CUSTOMREQUEST, "GET");
        if (count($data) > 0) {
            $this->setOpt(CURLOPT_URL, $url . '?' . http_build_query($data));
        } else {
            $this->setOpt(CURLOPT_URL, $url);
        }
        $this->setOpt(CURLOPT_HTTPGET, true);
        $this->exec();
        return $this;
    }

    /**
     * Purge Request
     *
     * A very common scenario to send a purge request is within the use of varnish, therefore
     * the optional hostname can be defined.
     *
     * @param string $url The url to make the purge request
     * @param string|null $hostName An optional hostname which will be sent as http host header
     * @return self
     */
    public function purge(string $url, string $hostName = null): static
    {
        $this->setOpt(CURLOPT_URL, $url);
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'PURGE');
        if ($hostName) {
            $this->setHeader('Host', $hostName);
        }
        $this->exec();
        return $this;
    }

    /**
     * Make a post request with optional post data.
     *
     * @param string $url The url to make the post request
     * @param object|array|string $data Post data to pass to the url
     * @param boolean $asJson Whether the data should be passed as json or not. {@insce 2.2.1}
     * @return self
     */
    public function post(string $url, object|array|string $data = [], bool $asJson = false): static
    {
        $this->setOpt(CURLOPT_CUSTOMREQUEST, "POST");
        $this->setOpt(CURLOPT_URL, $url);
        if ($asJson) {
            $this->prepareJsonPayload($data);
        } else {
            $this->preparePayload($data);
        }
        $this->exec();
        return $this;
    }

    /**
     * Make a put request with optional data.
     *
     * The put request data can be either sent via payload or as get parameters of the string.
     *
     * @param string $url The url to make the put request
     * @param object|array|string $data Optional data to pass to the $url
     * @param bool $payload Whether the data should be transmitted trough payload or as get parameters of the string
     * @param boolean $asJson Whether the data should be passed as json or not. {@insce 2.4.0}
     * @return self
     */
    public function put(string $url, object|array|string $data = [], bool $payload = false, bool $asJson = false): static
    {
        if (!empty($data)) {
            if ($payload === false) {
                $url .= '?' . http_build_query($data);
            } else {
                if ($asJson) {
                    $this->prepareJsonPayload($data);
                } else {
                    $this->preparePayload($data);
                }
            }
        }

        $this->setOpt(CURLOPT_URL, $url);
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'PUT');
        $this->exec();
        return $this;
    }

    /**
     * Make a patch request with optional data.
     *
     * The patch request data can be either sent via payload or as get parameters of the string.
     *
     * @param string $url The url to make the patch request
     * @param object|array|string $data Optional data to pass to the $url
     * @param bool $payload Whether the data should be transmitted trough payload or as get parameters of the string
     * @param boolean $asJson Whether the data should be passed as json or not. {@insce 2.4.0}
     * @return self
     */
    public function patch(string $url, object|array|string $data = [], bool $payload = false, bool $asJson = false): static
    {
        if (!empty($data)) {
            if ($payload === false) {
                $url .= '?' . http_build_query($data);
            } else {
                if ($asJson) {
                    $this->prepareJsonPayload($data);
                } else {
                    $this->preparePayload($data);
                }
            }
        }

        $this->setOpt(CURLOPT_URL, $url);
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'PATCH');
        $this->exec();
        return $this;
    }

    /**
     * Make a delete request with optional data.
     *
     * @param string $url The url to make the delete request
     * @param object|array|string $data Optional data to pass to the $url
     * @param bool $payload Whether the data should be transmitted trough payload or as get parameters of the string
     * @return self
     */
    public function delete(string $url, object|array|string $data = [], bool $payload = false): static
    {
        if (!empty($data)) {
            if ($payload === false) {
                $url .= '?' . http_build_query($data);
            } else {
                $this->preparePayload($data);
            }
        }

        $this->setOpt(CURLOPT_URL, $url);
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'DELETE');
        $this->exec();
        return $this;
    }

    // setters

    /**
     * Pass basic auth data.
     *
     * If the the requested url is secured by an htaccess basic auth mechanism you can use this method to provided the auth data.
     *
     * ```php
     * $curl = new Curl();
     * $curl->setBasicAuthentication('john', 'doe');
     * $curl->get('http://example.com/secure.php');
     * ```
     *
     * @param string $username The username for the authentication
     * @param string $password The password for the given username for the authentication
     * @return self
     */
    public function setBasicAuthentication(string $username, string $password): static
    {
        $this->setHttpAuth(self::AUTH_BASIC);
        $this->setOpt(CURLOPT_USERPWD, $username . ':' . $password);
        return $this;
    }

    /**
     * Provide optional header information.
     *
     * In order to pass optional headers by key value pairing:
     *
     * ```php
     * $curl = new Curl();
     * $curl->setHeader('X-Requested-With', 'XMLHttpRequest');
     * $curl->get('http://example.com/request.php');
     * ```
     *
     * @param string $key The header key
     * @param string $value The value for the given header key
     * @return self
     */
    public function setHeader(string $key, string $value): static
    {
        $this->_headers[$key] = $key . ': ' . $value;
        $this->setOpt(CURLOPT_HTTPHEADER, array_values($this->_headers));
        return $this;
    }

    /**
     * Provide a User Agent.
     *
     * In order to provide you customized user agent name you can use this method.
     *
     * ```php
     * $curl = new Curl();
     * $curl->setUserAgent('My John Doe Agent 1.0');
     * $curl->get('http://example.com/request.php');
     * ```
     *
     * @param string $useragent The name of the user agent to set for the current request
     * @return self
     */
    public function setUserAgent(string $useragent): static
    {
        $this->setOpt(CURLOPT_USERAGENT, $useragent);
        return $this;
    }

    /**
     * Set the HTTP referer header.
     *
     * The $referer Information can help identify the requested client where the requested was made.
     *
     * @param string $referer An url to pass and will be set as referer header
     * @return self
     */
    public function setReferer(string $referer): static
    {
        $this->setOpt(CURLOPT_REFERER, $referer);
        return $this;
    }

    /**
     * Set customized curl options.
     *
     * To see a full list of options: http://php.net/curl_setopt
     *
     * @see http://php.net/curl_setopt
     * @param int $option The curl option constant e.g. `CURLOPT_AUTOREFERER`, `CURLOPT_COOKIESESSION`
     * @param mixed $value The value to pass for the given $option
     * @return bool
     */
    public function setOpt(int $option, mixed $value): bool
    {
        return curl_setopt($this->curl, $option, $value);
    }

    /**
     * Get curl option for a certain name
     *
     * To see a full list of options: http://php.net/curl_getinfo
     *
     * @see http://php.net/curl_getinfo
     * @param int $option The curl option constant e.g. `CURLOPT_AUTOREFERER`, `CURLOPT_COOKIESESSION`
     * @return mixed
     */
    public function getOpt(int $option): mixed
    {
        return curl_getinfo($this->curl, $option);
    }

    /**
     * Return the all options for current curl ressource
     *
     * To see a full list of options: http://php.net/curl_getinfo
     *
     * @see http://php.net/curl_getinfo
     * @return array
     * @since 2.5.0
     */
    public function getOpts(): array
    {
        return curl_getinfo($this->curl);
    }

    /**
     * Return the endpoint set for curl
     *
     * @see http://php.net/curl_getinfo
     * @return string of endpoint
     */
    public function getEndpoint(): string
    {
        return $this->getOpt(CURLINFO_EFFECTIVE_URL);
    }

    /**
     * Enable verbosity.
     *
     * @param bool $on
     * @return static
     */
    public function setVerbose(bool $on = true): static
    {
        $this->setOpt(CURLOPT_VERBOSE, $on);
        return $this;
    }

    /**
     * @param bool $on
     * @return static
     * @deprecated Call setVerbose() instead. Will be removed in 3.0
     *
     */
    public function verbose(bool $on = true): static
    {
        return $this->setVerbose($on);
    }

    /**
     * Reset all curl options.
     *
     * In order to make multiple requests with the same curl object all settings requires to be reset.
     * @return self
     */
    public function reset(): static
    {
        $this->close();
        $this->_cookies = [];
        $this->_headers = [];
        $this->error = false;
        $this->error_code = 0;
        $this->error_message = null;
        $this->curl_error = false;
        $this->curl_error_code = 0;
        $this->curl_error_message = null;
        $this->http_error = false;
        $this->http_status_code = 0;
        $this->http_error_message = null;
        $this->request_headers = null;
        $this->response_headers = [];
        $this->response = false;
        //自定义
        $this->files = [];
        $this->init();
        return $this;
    }

    /**
     * Closing the current open curl resource.
     * @return self
     */
    public function close(): static
    {
        if (is_resource($this->curl)) {
            curl_close($this->curl);
        }
        return $this;
    }

    /**
     * Close the connection when the Curl object will be destroyed.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Was an 'info' header returned.
     * @return bool
     */
    public function isInfo(): bool
    {
        return $this->getHttpStatus() >= 100 && $this->getHttpStatus() < 200;
    }

    /**
     * Was an 'OK' response returned.
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->getHttpStatus() >= 200 && $this->getHttpStatus() < 300;
    }

    /**
     * Was a 'redirect' returned.
     * @return bool
     */
    public function isRedirect(): bool
    {
        return $this->getHttpStatus() >= 300 && $this->getHttpStatus() < 400;
    }

    /**
     * Was an 'error' returned (client error or server error).
     * @return bool
     */
    public function isError(): bool
    {
        return $this->getHttpStatus() >= 400 && $this->getHttpStatus() < 600;
    }

    /**
     * Was a 'client error' returned.
     * @return bool
     */
    public function isClientError(): bool
    {
        return $this->getHttpStatus() >= 400 && $this->getHttpStatus() < 500;
    }

    /**
     * Was a 'server error' returned.
     * @return bool
     */
    public function isServerError(): bool
    {
        return $this->getHttpStatus() >= 500 && $this->getHttpStatus() < 600;
    }

    /**
     * Get a specific response header key or all values from the response headers array.
     *
     * Usage example:
     *
     * ```php
     * $curl = (new Curl())->get('http://example.com');
     *
     * echo $curl->getResponseHeaders('Content-Type');
     * ```
     *
     * Or in order to dump all keys with the given values use:
     *
     * ```php
     * $curl = (new Curl())->get('http://example.com');
     *
     * var_dump($curl->getResponseHeaders());
     * ```
     *
     * @param string|null $headerKey Optional key to get from the array.
     * @return bool|string|array
     * @since 1.9
     */
    public function getResponseHeaders(string $headerKey = null): bool|array|string
    {
        $headers = [];
        if (!is_null($headerKey)) {
            $headerKey = strtolower($headerKey);
        }

        foreach ($this->response_headers as $header) {
            $parts = explode(":", $header, 2);

            $key = $parts[0] ?? '';
            $value = $parts[1] ?? '';

            $headers[trim(strtolower($key))] = trim($value);
        }

        if ($headerKey) {
            return $headers[$headerKey] ?? false;
        }

        return $headers;
    }

    /**
     * Get response from the curl request
     * @return bool|string|null
     */
    public function getResponse(): bool|string|null
    {
        return $this->response;
    }

    /**
     * Get curl error code
     * @return int
     */
    public function getErrorCode(): int
    {
        return $this->curl_error_code;
    }

    /**
     * Get curl error message
     * @return string|null
     */
    public function getErrorMessage(): ?string
    {
        return $this->curl_error_message;
    }

    /**
     * Get http status code from the curl request
     * @return int
     */
    public function getHttpStatus(): int
    {
        return $this->http_status_code;
    }

    /**
     * 创建一个新实例
     * @return static
     */
    public static function make(): static
    {
        return new static();
    }

    /**
     * 单例模式
     * @param bool $reset 是否重置Curl(默认true)
     * @return static
     */
    public static function getInstance(bool $reset = true): static
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
            return $instance;
        } else {
            return $reset ? $instance->reset() : $instance;
        }
    }

    /**
     * 设置Accept
     * @param string $value
     * @return $this
     */
    public function setAccept(string $value): static
    {
        return $this->setHeader('Accept', $value);
    }

    /**
     * 设置Content-Type
     * - application/json; charset=UTF-8
     * - application/x-www-form-urlencoded; charset=UTF-8
     * @param string $value
     * @return $this
     */
    public function setContentType(string $value = 'application/json; charset=UTF-8'): static
    {
        return $this->setHeader('Content-Type', $value);
    }

    /**
     * 设置X-Requested-With
     * @param string $value
     * @return $this
     */
    public function setXRequestedWith(string $value = 'XMLHttpRequest'): static
    {
        return $this->setHeader('X-Requested-With', $value);
    }

    /**
     * 添加待上传的文件
     * @param string $name 表单字段名
     * @param string $filename 上传数据中的文件名称
     * @param string $metadata 文件的元数据
     * @param string $mime_type mime类型
     * @return self
     */
    public function addFile(string $name, string $filename, string $metadata, string $mime_type = 'application/octet-stream'): self
    {
        $this->files[$name] = new CURLStringFile($metadata, $filename, $mime_type ?: 'application/octet-stream');
        return $this;
    }

    /**
     * 上传文件元数据
     * @param string $url
     * @param array $data
     * @param array $files
     * @return self
     */
    public function upload(string $url, array $data = [], array $files = []): self
    {
        $this->setOpt(CURLOPT_CUSTOMREQUEST, "POST");
        $this->setOpt(CURLOPT_URL, $url);
        try {
            $this->prepareFormDataPayload($data, $files);
        } catch (Error|Exception|Throwable $throwable) {
            $this->files = [];
            throw new InvalidArgumentException($throwable->getMessage(), $throwable->getCode());
        } finally {
            $this->files = [];
        }
        $this->exec();
        return $this;
    }

    /**
     * 构建form-data数据流
     * @param array $data 正常数据
     * @param array $files 要上传的文件
     * @return void
     */
    protected function prepareFormDataPayload(array $data, array $files = []): void
    {
        $boundary = str_replace('.', '', uniqid('--------------------files', true));
        // invalid characters for "name" and "filename"
        static $disallow = ["\0", "\"", "\r", "\n"];

        $eol = "\r\n";
        $body = '';

        // 拼接文件流
        $build_file_parameters = function (array $files) use (&$body, $boundary, $disallow, $eol) {
            // 拼接文件流 build file parameters
            /**
             * @var string $name
             * @var CURLStringFile $stringFile
             */
            foreach ($files as $name => $stringFile) {
                $name = str_replace($disallow, '_', $name);
                $filename = str_replace($disallow, '_', $stringFile->postname);
                $body .= "--" . $boundary . $eol;
                $body .= 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $filename . '"' . $eol;
                $body .= 'Content-Type: ' . $stringFile->mime . $eol . $eol;
                $body .= $stringFile->data . $eol;
            }
        };
        $build_file_parameters($this->files);
        $build_file_parameters($files);

        // 构建正常参数 build normal parameters
        foreach ($data as $name => $content) {
            $name = str_replace($disallow, '_', $name);
            $body .= "--" . $boundary . $eol;
            $body .= 'Content-Disposition: form-data; name="' . $name . '"' . $eol . $eol;
            $body .= $content . $eol;
        }
        $body .= "--" . $boundary . "--" . $eol;

        $this->setOpt(CURLOPT_POST, true);
        $this->setHeader('Content-Type', 'multipart/form-data; boundary=' . $boundary);
        $this->setHeader('Content-Length', strlen($body));
        $this->setOpt(CURLOPT_POSTFIELDS, $body);
    }

    /**
     * 设置超时
     * @param int $connectTimeout 尝试连接时等待的秒数
     * @param int $timeout 允许 cURL 函数执行的最长秒数
     * @return self
     */
    public function setTimeout(int $connectTimeout = 10, int $timeout = 10): self
    {
        //在尝试连接时等待的秒数。设置为0，则无限等待
        $this->setOpt(CURLOPT_CONNECTTIMEOUT, $connectTimeout);
        //允许 cURL 函数执行的最长秒数
        $this->setOpt(CURLOPT_TIMEOUT, $timeout);
        return $this;
    }

    /**
     * 设置不检查证书
     * @param bool $verifyPeer 验证对等证书
     * @param bool|int $verifyHost 检查名称
     * @return self
     */
    public function setSslVerify(bool $verifyPeer = false, bool|int $verifyHost = false): self
    {
        //false 禁止 cURL 验证对等证书
        $this->setOpt(CURLOPT_SSL_VERIFYPEER, $verifyPeer);
        //0 时不检查名称（SSL 对等证书中的公用名称字段或主题备用名称（Subject Alternate Name，简称 SNA）字段是否与提供的主机名匹配）
        $this->setOpt(CURLOPT_SSL_VERIFYHOST, $verifyHost);
        return $this;
    }

    /**
     * 设置代理服务器
     * @param string $proxy HTTP 代理通道（地址:端口）
     * @param string $auth 一个用来连接到代理的 "[username]:[password]" 格式的字符串
     * @return void
     */
    public function setCurlProxy(string $proxy, string $auth = ''): void
    {
        if ($proxy) {
            $this->setOpt(CURLOPT_PROXY, $proxy);

            if ($auth) {
                $this->setOpt(CURLOPT_PROXYUSERPWD, $auth);
            }
        }
    }

    /**
     * 自动跳转，跟随响应的Location
     * @param int $max 跟随次数
     * @return $this
     */
    public function setFollowLocation(int $max = 2): self
    {
        if (0 < $max) {
            // 自动跳转，跟随请求Location
            $this->setOpt(CURLOPT_FOLLOWLOCATION, 1);
            // 递归次数
            $this->setOpt(CURLOPT_MAXREDIRS, $max);
        }
        return $this;
    }

    /**
     * 设置HTTP请求头的cookie
     * - Set contents of HTTP Cookie header.
     * - 修复被转码的bug
     * @param string $key The name of the cookie
     * @param string $value The value for the provided cookie name
     * @return self
     */
    public function setCookie(string $key, string $value): self
    {
        $this->_cookies[$key] = $value;
        $cookies = [];
        foreach ($this->_cookies as $key => $value) {
            $cookies[] = $key . '=' . $value;
        }
        $this->setOpt(CURLOPT_COOKIE, implode('; ', $cookies));
        return $this;
    }

    /**
     * 批量设置HTTP请求头的cookie
     * @param string|array $cookies
     * @return $this
     */
    public function setCookies(string|array $cookies): static
    {
        if (is_string($cookies)) {
            $this->setOpt(CURLOPT_COOKIE, $cookies);
        } else {
            foreach ($cookies as $key => $value) {
                $this->setCookie($key, $value);
            }
        }
        return $this;
    }

    /**
     * 批量设置headers
     * @param array $herders
     * @return $this
     */
    public function setHeaders(array $herders): static
    {
        foreach ($herders as $key => $value) {
            $this->setHeader($key, $value);
        }
        return $this;
    }
}
