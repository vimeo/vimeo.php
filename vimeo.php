<?php

/**
 *   Copyright 2013 Vimeo
 *
 *   Licensed under the Apache License, Version 2.0 (the "License");
 *   you may not use this file except in compliance with the License.
 *   You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *   Unless required by applicable law or agreed to in writing, software
 *   distributed under the License is distributed on an "AS IS" BASIS,
 *   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *   See the License for the specific language governing permissions and
 *   limitations under the License.
 */

/**
 *
 */
class Vimeo
{
    const ROOT_ENDPOINT = 'https://api.vimeo.com';
    const AUTH_ENDPOINT = 'https://api.vimeo.com/oauth/authorize';
    const ACCESS_TOKEN_ENDPOINT = '/oauth/access_token';
    const CLIENT_CREDENTIALS_TOKEN_ENDPOINT = '/oauth/authorize/client';
    const VERSION_STRING = 'application/vnd.vimeo.*+json; version=3.0';
    const USER_AGENT = 'vimeo.php 0.1; (http://developer.vimeo.com/api/docs)';

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
        $headers[] = 'User-Agent: ' . self::USER_AGENT;

        // add bearer token, or client information
        if (!empty($this->_access_token)) {
            $headers[] = 'Authorization: Bearer ' . $this->_access_token;
        }
        else {
            //  this may be a call to get the tokens, so we add the client info.
            $headers[] = 'Authorization: Basic ' . $this->_authHeader();
        }

        //  Set the methods, determine the URL that we should actually request and prep the body.
        $curl_opts = array();
        switch (strtoupper($method)) {
            case 'GET' :
                $curl_url = self::ROOT_ENDPOINT . $url . '?' . http_build_query($params, '', '&');
                break;

            case 'POST' :
            case 'PATCH' :
            case 'PUT' :
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

        $response['body'] = json_decode($response['body'], true);

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
        $overwritten_keys = array_intersect(array_keys($curl_opts), array_keys($curl_opt_defaults));
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
        $this->_access_token = $access_token;
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
     * Get client credentials for requests
     * @param  string or array $scope Scopes to request for this token from the server.
     * @return array               Response from the server with the tokens, we also set it into this object.
     */
    public function clientCredentials ($scope = 'public') {
        if (is_array($scope)) {
            $scope = implode(' ', $scope);
        }

        $token_response = $this->request(self::CLIENT_CREDENTIALS_TOKEN_ENDPOINT, array(
            'grant_type' => 'client_credentials',
            'scope' => $scope
        ), "POST");

        return $token_response;
    }

    /**
     * Get authorization header for retrieving tokens/credentials.
     *
     * @return string
     */
    private function _authHeader() {
        return base64_encode($this->_client_id . ':' . $this->_client_secret);
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

    /**
     * Upload a file
     *
     * This should be used to upload a local file.  If you want a form for your site to upload direct to Vimeo, you should look at the POST /me/videos endpoint.
     *
     * @param string $file_path Path to the video file to upload.
     * @return array Status
     */
    public function upload ($file_path, $machine_id = null) {
        //  Validate that our file is real.
        if (!is_file($file_path)) {
            throw new VimeoUploadException('Unable to locate file to upload.');
        }

        //  Begin the upload request by getting a ticket
        $ticket_args = array('type' => 'streaming');
        if ($machine_id !== null) {
            $ticket_args['machine_id'] = $machine_id;
        }
        $ticket = $this->request('/me/videos', $ticket_args, 'POST');
        if ($ticket['status'] != 200) {
            throw new VimeoUploadException('Unable to get an upload ticket.');
        }

        //  We are going to always target the secure upload URL.
        $url = $ticket['body']['upload_link_secure'];

        //  We need a handle on the input file since we may have to send segments multiple times.
        $file = fopen($file_path, 'r');

        //  PUTs a file in a POST....do for the streaming when we get there.
        $curl_opts = array(
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $file,
            CURLOPT_INFILESIZE => filesize($file_path),
            CURLOPT_UPLOAD => true,
            CURLOPT_HTTPHEADER => array('Expect: ', 'Content-Range: replaced...')
        );

        //  These are the options that set up the validate call.
        $curl_opts_check_progress = array(
            CURLOPT_PUT => true,
            CURLOPT_HTTPHEADER => array('Content-Length: 0', 'Content-Range: bytes */*')
        );

        //  Perform the upload by streaming as much to the server as possible and ending when we reach the filesize on the server.
        $size = filesize($file_path);
        $server_at = 0;
        do {
            //  The last HTTP header we set MUST be the Content-Range, since we need to remove it and replace it with a proper one.
            array_pop($curl_opts[CURLOPT_HTTPHEADER]);
            $curl_opts[CURLOPT_HTTPHEADER][] = 'Content-Range: bytes ' . $server_at . '-' . $size . '/' . $size;

            fseek($file, $server_at);   //  Put the FP at the point where the server is.
            $upload_response = $this->_request($url, $curl_opts);   //  Send what we can.
            $progress_check = $this->_request($url, $curl_opts_check_progress); //  Check on what the server has.

            //  Figure out how much is on the server.
            list(, $server_at) = explode('-', $progress_check['headers']['Range']);
            $server_at = (int)$server_at;
        } while ($server_at < $size);

        //  Complete the upload on the server.
        $completion = $this->request($ticket['body']['complete_uri'], array(), 'DELETE');

        //  Validate that we got back 201 Created
        $status = (int) $completion['status'];
        if ($status != 201) {
            throw new VimeoUploadException('Error completing the upload.');
        }

        //  Furnish the location for the new clip in the API via the Location header.
        return $completion['headers']['Location'];
    }
}

/**
 * VimeoUploadException class for failure to upload to the server.
 */
class VimeoUploadException extends Exception {}
