<?php

/**
 * 
 */
class Vimeo
{
    const ROOT_ENDPOINT = 'https://api.vimeo.com';
    const AUTH_ENDPOINT = 'https://api.vimeo.com/oauth/authorize';
    const ACCESS_TOKEN_ENDPOINT = '/oauth/access_token';
    const VERSION_STRING = 'application/vnd.vimeo.*+json; version=3.0';

    private $_client_id = null;
    private $_client_secret = null;
    private $_access_token = null;

    /**
     * [__construct description]
     * @param [type] $client_id     [description]
     * @param [type] $client_secret [description]
     * @param [type] $access_token  [description]
     */
    public function __construct($client_id, $client_secret, $access_token = null)
    {
        $this->_client_id = $client_id;
        $this->_client_secret = $client_secret;
        $this->_access_token = $access_token;
    }

    /**
     * [request description]
     * @param  [type] $url    [description]
     * @param  array  $params [description]
     * @param  string $method [description]
     * @return [type]         [description]
     */
    public function request($url, $params = array(), $method = 'GET')
    {
        // add accept header hardcoded to version 3.0
        $headers[] = 'Accept: ' . self::VERSION_STRING;

        // add bearer token, or client information
        if (!empty($this->_access_token)) {
            $headers[] = 'Authorization: Bearer ' . $this->_access_token;
        } else if (!empty($this->_client_id) && !empty($this->_client_secret)) {
            $headers[] = 'Authorization: Basic ' . base64_encode($this->_client_id . ':' . $this->_client_secret);
        } else if (!empty($this->_client_id) && empty($this->_client_secret)) {
	       $params['client_id'] = $this->_client_id;
    	}

        $curl_opts = array();
        switch (strtoupper($method)) {
            case 'GET' :
                $curl_url = self::ROOT_ENDPOINT . $url . '?' . http_build_query($params, '', '&');
                break;

            case 'POST' :
            case 'PATCH' :
            case 'DELETE' :
                $curl_url = self::ROOT_ENDPOINT . $url;
                $curl_opts = array(
                    CURLOPT_POST => true,
                    CURLOPT_CUSTOMREQUEST => $method,
                    CURLOPT_POSTFIELDS => http_build_query($params, '', '&')
                );
                break;
        }

        $curl_opts[CURLOPT_HEADER] = 1;
        $curl_opts[CURLOPT_RETURNTRANSFER] = true;
        $curl_opts[CURLOPT_TIMEOUT] = 10;
        $curl_opts[CURLOPT_HTTPHEADER] = $headers;

        // Call the API
        $curl = curl_init($curl_url);
        curl_setopt_array($curl, $curl_opts);
        $response = curl_exec($curl);
        $curl_info = curl_getinfo($curl);
        curl_close($curl);

        $header_size = $curl_info['header_size'];
        $headers = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        return array(
            'body' => json_decode($body),
            'status' => $curl_info['http_code'],
            'headers' => self::parse_headers($headers)
        );
    }

    /**
     * [getToken description]
     * @return [type] [description]
     */
    public function getToken()
    {
        return $this->_access_token;
    }

    /**
     * [setToken description]
     * @param [type] $access_token [description]
     */
    public function setToken($access_token)
    {
        $this->_access_token = $token;
    }

    /**
     * [parse_headers description]
     * @param  [type] $headers [description]
     * @return [type]          [description]
     */
    public static function parse_headers($headers)
    {
        $final_headers = array();
        $list = explode("\n", trim($headers));

        $http = array_shift($list);

        foreach ($list as $header) {
            $parts = explode(':', $header);
            $final_headers[trim($parts[0])] = trim($parts[1]);
        }

        return $final_headers;
    }

    /**
     * [accessToken description]
     * @param  [type] $code         [description]
     * @param  [type] $redirect_uri [description]
     * @return [type]               [description]
     */
    public function accessToken ($code, $redirect_uri) {
        return $this->request(self::ACCESS_TOKEN_ENDPOINT, array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirect_uri
        ), "POST");
    }

    /**
     * [buildAuthorizationEndpoint description]
     * @param  [type] $redirect_uri [description]
     * @param  string $scope        [description]
     * @param  [type] $state        [description]
     * @return [type]               [description]
     */
    public function buildAuthorizationEndpoint ($redirect_uri, $scope = 'public', $state = null) {
        $query = array(
            "response_type" => 'code',
            "client_id" => $this->_client_id,
            "redirect_uri" => $redirect_uri
        );

        if (empty($scope)) {
            $query['scope'] = 'public';
        } elseif (is_array($scope)) {
            $query['scope'] = implode(' ', $scope);
        }

        if (!empty($state)) {
            $query['state'] = $state;
        }

        return self::AUTH_ENDPOINT . '?' . http_build_query($query);
    }
}
