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

        //  Set the methods, determine the URL that we should actually request and prep the body.
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

        //  Set the headers
        $curl_opts[CURLOPT_HTTPHEADER] = $headers;

        $response = $this->_request($curl_url, $curl_opts);

        $response['body'] = json_decode($response['body']);
        $response['headers'] = self::parse_headers($response['headers']);

        return $response;
    }

    /**
     *  Internal function to handle requests, both authenticated and by the upload function.
     */
    private function _request($url, $curl_opts = array()) {
        //  Apply the defaults to the curl opts.
        $curl_opt_defaults = array(
            CURLOPT_HEADER => 1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30);

        //  Can't use array_merge since it would reset the numbering to 0 and lose the CURLOPT constant values.
        //  Insetad we find the overwritten ones and manually merge.
        $overwritten_keys = array_intersect_assoc($curl_opts, $curl_opt_defaults);
        foreach ($curl_opt_defaults as $setting => $value) {
            if (in_array($setting, $overwritten_keys)) {
                break;
            }
            $curl_opts[$setting] = $value;
        }

        // Call the API
        $curl = curl_init($url);
        curl_setopt_array($curl, $curl_opts);
        $response = curl_exec($curl);
        $curl_info = curl_getinfo($curl);
        curl_close($curl);

        //  Retrieve the info
        $header_size = $curl_info['header_size'];
        $headers = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        //  Return it raw.
        return array(
            'body' => $body,
            'status' => $curl_info['http_code'],
            'headers' => $headers
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
            $final_headers[trim($parts[0])] = isset($parts[1]) ? trim($parts[1]) : '';
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

        $query['scope'] = $scope;
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
