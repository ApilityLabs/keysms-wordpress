<?php

namespace KeySMS;

use WP_Error;
use WpOrg\Requests\Exception\InvalidArgument;

class KeySMS
{
    protected $username;
    protected $api_key;
    protected $default_sender;
    protected static $instance;
    protected static $account_info;

    protected function __construct($username, $api_key, $default_sender = null)
    {
        $this->username = $username;
        $this->api_key = $api_key;
        $this->default_sender = $default_sender;
    }

    public static function init()
    {
        if (self::$instance === null) {
            $options = get_option('keysms_options');
            $username = $options['username'] ?? null;
            $api_key = $options['api_key'] ?? null;
            $default_sender = $options['default_sender'] ?? null;

            if ((!$username || !$api_key)) {
                return;
            }

            self::$instance = new self($username, $api_key, $default_sender);
        }
    }

    /**
     * @return KeySMS
     */
    public static function get_instance()
    {
        return self::$instance;
    }

    protected function signature($payload)
    {
        return md5(wp_json_encode($payload) . $this->api_key);
    }

    protected function create_request($payload)
    {
        return [
            'payload' => wp_json_encode($payload),
            'username' => $this->username,
            'signature' => $this->signature($payload),
        ];
    }

    protected function post($endpoint, $payload)
    {
        return wp_remote_post(
            'https://app.keysms.no/' . $endpoint,
            [
                'body' => wp_json_encode($this->create_request($payload)),
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'redirection' => 0,
            ]
        );
    }

    public function get_account_info()
    {
        if (self::$account_info === null) {
            $request = $this->post('auth/current.json', [
                'user' => true,
                'account' => true,
                'aliases' => true,
                'features' => true,
            ]);

            $code = wp_remote_retrieve_response_code($request);
            $body = wp_remote_retrieve_body($request);
            $response = json_decode($body, true);

            if ($code !== 200 && is_wp_error($request)) {
                return $request;
            }

            self::$account_info = $response;
        }

        return self::$account_info;
    }

    /**
     * @return array
     */
    public function get_senders($refresh = false)
    {
        $senders = [];
        $options = get_option('keysms_senders');

        if ($refresh || $options === null) {
            $account_info = $this->get_account_info();
            if (isset($account_info['aliases'])) {
                $senders = $account_info['aliases'];

                update_option('keysms_senders', $senders);
            } else {
                update_option('keysms_senders', []);
            }
        } else {
            $senders = $options;
        }

        return $senders;
    }

    public function refresh_senders()
    {
        update_option('keysms_senders', null);
        $this->get_senders();
    }

    /**
     * 
     * @param string $receivers 
     * @param string $message 
     * @param array $options 
     * @return array|WP_Error
     */
    public function send_sms($receivers, $message, $sender = null, $options = [])
    {
        if ($sender === null) {
            $sender = $this->default_sender;
        }

        $request = $this->post(
            'messages',
            array_filter([
                'receivers' => [$receivers],
                'message' => $message,
                'sender' => $sender,
                'options' => $options
            ])
        );

        $code = wp_remote_retrieve_response_code($request);
        $body = wp_remote_retrieve_body($request);
        $response = json_decode($body, true);

        if ($code !== 200 && is_wp_error($request)) {
            return $request;
        }

        if ($response['ok']) {
            return $response;
        }

        $message = __('An error occurred.');

        if (isset($response['messages'])) {
            $message = $response['messages'][0];
        }

        if (isset($response['error'])) {
            if (!is_array($response['error'])) {
                return new WP_Error('keysms_error', $response['error']);
            }

            foreach ($response['error'] as $error) {
                return new WP_Error('keysms_' . $error['code'], $error['text']);
            }
        }

        return new WP_Error('keysms_' . $response['error'], $message);
    }
}
