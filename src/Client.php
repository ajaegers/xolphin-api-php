<?php

namespace Xolphin;

use GuzzleHttp\Exception\RequestException;
use Xolphin\Endpoint\Certificate;
use Xolphin\Endpoint\Request;
use Xolphin\Endpoint\Support;

class Client {
    const BASE_URI = 'https://api.xolphin.com/v%d/';
    const BASE_URI_TEST = 'https://test-api.xolphin.com/v%d/';
    const API_VERSION = 1;
    const VERSION = '1.6.0';

    private $username = '';
    private $password = '';
    private $guzzle;

    /** @var null|\Psr\Http\Message\RequestInterface */
    public $last_response = null;

    /**
     * Client constructor.
     * @param string $username
     * @param string $password
     * @param boolean $test|false
     */
    function __construct($username, $password, $test=false) {
        $this->username = $username;
        $this->password = $password;

        $options = [
            'base_uri' => sprintf(($test ? Client::BASE_URI_TEST : Client::BASE_URI), Client::API_VERSION),
            'auth' => [$this->username, $this->password],
            'headers' => [
                'Accept'     => 'application/json',
                'User-Agent' => 'xolphin-api-php/'. Client::VERSION
            ]
        ];
        if(getenv('TEST_PROXY') !== FALSE) {
            $options['proxy'] = getenv('TEST_PROXY');
            $options['verify'] = false;
        }
        $this->guzzle = new \GuzzleHttp\Client($options);
    }

    /**
     * @param string $method
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public function get($method, $data = []) {
        try {
            $result = $this->guzzle->get($method, ['query' => $data]);
            $this->last_response = $result;
            return json_decode($result->getBody());
        } catch (RequestException $e) {
            $this->last_response = $e->getResponse();
            $data = json_decode($e->getResponse()->getBody());
            if($data == NULL) {
                throw new \Exception($e->getResponse()->getBody());
            } else {
                if(isset($data->message) || isset($data->errors)) {
                    throw new \Exception(json_encode($data), $e->getCode());
                } else {
                    throw new \Exception($e->getMessage(), $e->getCode());
                }
            }
        }
    }

    /**
     * @param string $method
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public function post($method, $data = []) {
        try {
            $mp = [];
            foreach($data as $k => $v) {
                if($k == 'document') {
                    $mp[] = [
                        'name' => $k,
                        'contents' => $v,
                        'filename' => 'document.pdf'
                    ];
                } else {
                    $mp[] = [
                        'name' => $k,
                        'contents' => (string)$v
                    ];
                }
            }

            $result = $this->guzzle->post($method, ['multipart' => $mp]);
            $this->last_response = $result;
            return json_decode($result->getBody());
        } catch (RequestException $e) {
            $this->last_response = $e->getResponse();
            $data = json_decode($e->getResponse()->getBody());
            if($data == NULL) {
                throw new \Exception($e->getResponse()->getBody());
            } else {
                throw new \Exception(json_encode($data), $e->getCode());
            }
        }
    }

    /**
     * @param string $method
     * @param array $data
     * @return \Psr\Http\Message\StreamInterface
     * @throws \Exception
     */
    public function download($method, $data = []) {
        try {
            $result = $this->guzzle->get($method, ['query' => $data]);
            $this->last_response = $result;
            return $result->getBody();
        } catch (RequestException $e) {
            $this->last_response = $e->getResponse();
            try {
                $data = json_decode($e->getResponse()->getBody());
                throw new \Exception($data->message);
            } catch (\Exception $ex) {
                throw new \Exception($e->getResponse()->getBody());
            }
        }
    }

    /**
     * @return Request
     */
    public function request() {
        return new Request($this);
    }

    /**
     * @return Certificate
     */
    public function certificate() {
        return new Certificate($this);
    }

    /**
     * @return Support
     */
    public function support() {
        return new Support($this);
    }
}
