<?php

namespace Webman\Push;

/**
 * Modified from https://github.com/pusher/pusher-http-php
 */
class Api
{
    /**
     * @var array
     */
    protected $_settings = [
        'timeout' => 2,
    ];

    /**
     * @param $api_address
     * @param $auth_key
     * @param $secret
     * @throws PushException
     */
    public function __construct($api_address, $auth_key, $secret)
    {
        $this->checkCompatibility();
        $this->_settings['api_address'] = $api_address;
        $this->_settings['auth_key'] = $auth_key;
        $this->_settings['secret'] = $secret;
        $this->_settings['base_path'] = '/apps/1024';
    }

    /**
     * trigger an event by providing event name and payload.
     * Optionally provide a socket ID to exclude a client (most likely the sender).
     *
     * @param array|string $channels An array of channel names to publish the event on.
     * @param string $event
     * @param mixed $data Event data
     * @param string $socket_id [optional]
     * @return bool|string
     */
    public function trigger($channels, $event, $data, $socket_id = null)
    {
        if (is_string($channels)) {
            $channels = array($channels);
        }
        $query_params = array();
        $s_url = $this->_settings['base_path'] . '/events';
        $data_encoded = json_encode($data);
        $post_params = array();
        $post_params['name'] = $event;
        $post_params['data'] = $data_encoded;
        $post_params['channels'] = $channels;
        if ($socket_id !== null) {
            $post_params['socket_id'] = $socket_id;
        }
        $post_value = json_encode($post_params);
        $query_params['body_md5'] = md5($post_value);
        $ch = $this->createCurl($this->_settings['api_address'], $s_url, 'POST', $query_params);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_value);
        $response = $this->execCurl($ch);
        if ($response['status'] === 200) {
            return true;
        } else {
            return false;
        }
    }

    public function getChannelInfo($channel, $params = array())
    {
        $this->validateChannel($channel);

        $response = $this->get('/channels/' . $channel, $params);

        if ($response['status'] === 200) {
            $response = json_decode($response['body']);
        } else {
            $response = false;
        }

        return $response;
    }

    public function getChannels($params = array())
    {
        return $this->get('/channels', $params);
    }

    private function checkCompatibility()
    {
        if (!in_array('sha256', hash_algos())) {
            throw new PushException('SHA256 appears to be unsupported - make sure you have support for it, or upgrade your version of PHP.');
        }
    }

    private function validateChannel($channel)
    {
        if (!preg_match('/\A[-a-zA-Z0-9_=@,.;]+\z/', $channel)) {
            throw new PushException('Invalid channel name ' . $channel);
        }
    }

    private function validateSocketId($socket_id)
    {
        if ($socket_id !== null && !preg_match('/\A\d+\.\d+\z/', $socket_id)) {
            throw new PushException('Invalid socket ID ' . $socket_id);
        }
    }

    private function createCurl($domain, $s_url, $request_method = 'GET', $query_params = array())
    {
        static $ch = null;
        $signed_query = self::buildAuthQueryString(
            $this->_settings['auth_key'],
            $this->_settings['secret'],
            $request_method,
            $s_url,
            $query_params);

        $full_url = $domain . $s_url . '?' . $signed_query;

        if (null === $ch) {
            $ch = curl_init();
            if ($ch === false) {
                throw new PushException('Could not initialise cURL!');
            }
        }

        if (function_exists('curl_reset')) {
            curl_reset($ch);
        }

        curl_setopt($ch, CURLOPT_URL, $full_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Expect:',
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_settings['timeout']);
        if ($request_method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
        } elseif ($request_method === 'GET') {
            curl_setopt($ch, CURLOPT_POST, 0);
        } // Otherwise let the user configure it

        return $ch;
    }

    private function execCurl($ch)
    {
        $response = array();
        $response['body'] = curl_exec($ch);
        $response['status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return $response;
    }

    public static function buildAuthQueryString($auth_key, $auth_secret, $request_method, $request_path,
                                                $query_params = array())
    {
        $params = array();
        $params['auth_key'] = $auth_key;
        $params['auth_timestamp'] = time();

        $params = array_merge($params, $query_params);
        ksort($params);

        $string_to_sign = "$request_method\n" . $request_path . "\n" . self::arrayImplode('=', '&', $params);

        $auth_signature = hash_hmac('sha256', $string_to_sign, $auth_secret, false);

        $params['auth_signature'] = $auth_signature;
        ksort($params);

        $auth_query_string = self::arrayImplode('=', '&', $params);

        return $auth_query_string;
    }

    public static function arrayImplode($glue, $separator, $array)
    {
        if (!is_array($array)) {
            return $array;
        }
        $string = array();
        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $val = implode(',', $val);
            }
            $string[] = "{$key}{$glue}{$val}";
        }

        return implode($separator, $string);
    }

    public function get($path, $params = array())
    {
        $ch = $this->createCurl($this->_settings['api_address'], $this->_settings['base_path'] . $path, 'GET', $params);

        $response = $this->execCurl($ch);

        if ($response['status'] === 200) {
            $response['result'] = json_decode($response['body'], true);
        }

        return $response;
    }

    public function socketAuth($channel, $socket_id, $custom_data = null)
    {
        $this->validateChannel($channel);
        $this->validateSocketId($socket_id);

        if ($custom_data) {
            $signature = hash_hmac('sha256', $socket_id . ':' . $channel . ':' . $custom_data, $this->_settings['secret'], false);
        } else {
            $signature = hash_hmac('sha256', $socket_id . ':' . $channel, $this->_settings['secret'], false);
        }

        $signature = array('auth' => $this->_settings['auth_key'] . ':' . $signature);
        if ($custom_data) {
            $signature['channel_data'] = $custom_data;
        }

        return json_encode($signature);
    }

    public function presenceAuth($channel, $socket_id, $user_id, $user_info = null)
    {
        $user_data = array('user_id' => $user_id);
        if ($user_info) {
            $user_data['user_info'] = $user_info;
        }

        return $this->socketAuth($channel, $socket_id, json_encode($user_data));
    }
}

class PushException extends \Exception
{
}
