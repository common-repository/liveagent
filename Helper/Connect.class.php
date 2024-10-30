<?php
/**
 *   @copyright Copyright (c) 2015 Quality Unit s.r.o.
 *   @author Martin Pullmann
 *   @package WpLiveAgentPlugin
 *   @version 1.0.0
 *
 *   Licensed under GPL2
 */

class liveagent_Helper_Connect {
    /**
     * @param $initUrl
     * @param string $postParams
     * @return mixed
     * @throws liveagent_Exception_ConnectProblem
     */
    private function connect($initUrl, $postParams = '') {
        if ($postParams != '') {
            $args = array(
                'body' => $postParams,
                'timeout' => '15',
                'redirection' => '5',
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(),
                'cookies' => array()
            );
            $response = wp_remote_post($initUrl, $args);
        } else {
            $response = wp_remote_get($initUrl);
        }

        if (is_a($response, 'WP_Error')) {
            throw new liveagent_Exception_ConnectProblem('Connection failed ('.$response->errors['http_request_failed'][0].')');
        }

        $errmsg = $response['response']['message'];
        $decodedResponse = '';
        if (isset($response['body']) && ($response['body'] != '')) {
            $decodedResponse = json_decode($response['body']);
        }

        if ($response['response']['code'] != '200') {
            if ($decodedResponse != '') {
                $errmsg = $decodedResponse->response->errormessage;
            }
            throw new liveagent_Exception_ConnectProblem('Error connecting to the account (' . $initUrl . '). The call failed with status code: ' . $response['response']['code'] . ' ' . $errmsg);
        }

        if (isset($decodedResponse->response->status) && $decodedResponse->response->status === 'ERROR') {
            throw new liveagent_Exception_ConnectProblem('An error occurred: "' . $decodedResponse->response->errormessage . '"');
        }
        return $decodedResponse->response;
    }

    /**
     * @param $url
     * @param $apikey
     * @throws liveagent_Exception_ConnectProblem
     */
    public function ping($url, $apikey) {
        if (empty($url) || empty($apikey)) {
            throw new liveagent_Exception_ConnectProblem('Please, fill in all credentials!');
        }
        $this->connect($this->getUrlWithTrailingSlash($url) . 'api/application/status?apikey=' . $apikey);
    }

    /**
     * @param $url
     * @param $params
     * @return array
     * @throws liveagent_Exception_ConnectProblem
     */
    public function createWidget($url, $params) {
        if (empty($url) || empty($params)) {
            throw new liveagent_Exception_ConnectProblem('Please, fill in all parameters!');
        }
        $postParams = '';
        foreach ($params as $key => $value) {
            $postParams .= $key . '='. urlencode($value) .'&';
        }
        $postParams = substr($postParams, 0, -1);
        $response = $this->connect($this->getUrlWithTrailingSlash($url) . 'api/widgets', $postParams);
        return array(0 => $response);
    }

    /**
     * @param $url
     * @param $params
     * @return array
     * @throws liveagent_Exception_ConnectProblem
     */
    public function createCustomer($url, $params) {
        if (empty($url) || empty($params)) {
            throw new liveagent_Exception_ConnectProblem('Please, fill in all parameters!');
        }
        $postParams = '';
        foreach ($params as $key => $value) {
            $postParams .= $key . '='. urlencode($value) .'&';
        }
        $postParams = substr($postParams, 0, -1);
        return $this->connect($this->getUrlWithTrailingSlash($url) . 'api/customers', $postParams);
    }

    /**
     * @param $url
     * @param $email
     * @param $apikey
     * @return array
     * @throws liveagent_Exception_ConnectProblem
     */
    public function getCustomer($url, $email, $apikey) {
        return $this->connect($this->getUrlWithTrailingSlash($url) . 'api/customers/' . urlencode($email) . '?apikey=' . $apikey);
    }

    /**
     * @param $url
     * @param $email
     * @param $apikey
     * @return mixed
     * @throws liveagent_Exception_ConnectProblem
     */
    public function connectWithLA($url, $email, $apikey) {
        if (empty($url) || empty($apikey)) {
            throw new liveagent_Exception_ConnectProblem('Please, fill in all credentials!');
        }
        return $this->connect($this->getUrlWithTrailingSlash($url) . 'api/agents/' . urlencode($email) . '?apikey=' . $apikey);
    }

    /**
     * @param $url
     * @param $apikey
     * @return mixed
     * @throws liveagent_Exception_ConnectProblem
     */
    public function getWidgets($url, $apikey) {
        if (empty($url) || empty($apikey)) {
            throw new liveagent_Exception_ConnectProblem('Please, fill in all credentials!');
        }
        // chat widgets only
        return $this->connect($this->getUrlWithTrailingSlash($url) . 'api/widgets?apikey=' . $apikey . '&rtype=C')->widgets;
    }

    /**
     * @param $url
     * @param $apikey
     * @return mixed
     * @throws liveagent_Exception_ConnectProblem
     */
    public function getOverview($url, $apikey) {
        if (empty($url) || empty($apikey)) {
            throw new liveagent_Exception_ConnectProblem('Please, fill in all credentials!');
        }
        $response = $this->connect($this->getUrlWithTrailingSlash($url) . 'api/chats/overview?apikey=' . $apikey);
        return $response->chatsOverview[0];
    }

    private function getUrlWithTrailingSlash($url) {
        if (substr($url, -1) !== '/') {
            $url .= '/';
        }
        return $url;
    }
}