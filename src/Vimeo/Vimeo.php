<?php namespace Vimeo;

use Vimeo\Exceptions\VimeoUploadException;

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

if (!function_exists('json_decode')) {
    throw new Exception('We could not find json_decode. json_decode is found in php 5.2 and up, but not found on many linux systems due to licensing conflicts. If you are running ubuntu try "sudo apt-get install php5-json".');
}

class Vimeo
{
    const ROOT_ENDPOINT = 'https://api.vimeo.com';
    const AUTH_ENDPOINT = 'https://api.vimeo.com/oauth/authorize';
    const ACCESS_TOKEN_ENDPOINT = '/oauth/access_token';
    const CLIENT_CREDENTIALS_TOKEN_ENDPOINT = '/oauth/authorize/client';
    const REPLACE_ENDPOINT = '/files';
    const VERSION_STRING = 'application/vnd.vimeo.*+json; version=3.2';
    const USER_AGENT = 'vimeo.php 1.0; (http://developer.vimeo.com/api/docs)';

    private $_client_id = null;
    private $_client_secret = null;
    private $_access_token = null;

    protected $_curl_opts = array();

    /**
     * Creates the Vimeo library, and tracks the client and token information.
     *
     * @param string $client_id Your applications client id. Can be found on developer.vimeo.com/apps
     * @param string $client_secret Your applications client secret. Can be found on developer.vimeo.com/apps
     * @param string $access_token Your applications client id. Can be found on developer.vimeo.com/apps or generated using OAuth 2.
     */
    public function __construct($client_id, $client_secret, $access_token = null)
    {
        $this->_client_id = $client_id;
        $this->_client_secret = $client_secret;
        $this->_access_token = $access_token;
    }

    /**
     * Make an API request to Vimeo.
     *
     * @param string $url A Vimeo API Endpoint. Should not include the host
     * @param array $params An array of parameters to send to the endpoint. If the HTTP method is GET, they will be added to the url, otherwise they will be written to the body
     * @param string $method The HTTP Method of the request
     * @param bool $json_body
     * @return array This array contains three keys, 'status' is the status code, 'body' is an object representation of the json response body, and headers are an associated array of response headers
     */
    public function request($url, $params = array(), $method = 'GET', $json_body = true)
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
                if (!empty($params)) {
                    $query_component = '?' . http_build_query($params, '', '&');
                } else {
                    $query_component = '';
                }

                $curl_url = self::ROOT_ENDPOINT . $url . $query_component;
                break;

            case 'POST' :
            case 'PATCH' :
            case 'PUT' :
            case 'DELETE' :
                if ($json_body && !empty($params)) {
                    $headers[] = 'Content-Type: application/json';
                    $body = json_encode($params);
                } else {
                    $body = http_build_query($params, '', '&');
                }

                $curl_url = self::ROOT_ENDPOINT . $url;
                $curl_opts = array(
                    CURLOPT_POST => true,
                    CURLOPT_CUSTOMREQUEST => $method,
                    CURLOPT_POSTFIELDS => $body
                );
                break;
        }

        // Set the headers
        $curl_opts[CURLOPT_HTTPHEADER] = $headers;

        $response = $this->_request($curl_url, $curl_opts);

        $response['body'] = json_decode($response['body'], true);

        return $response;
    }

    /**
     * Internal function to handle requests, both authenticated and by the upload function.
     *
     * @param string $url
     * @param array $curl_opts
     * @return array
     */
    private function _request($url, $curl_opts = array()) {
        // Apply the defaults to the curl opts.
        $curl_defaults = array(
            CURLOPT_HEADER => 1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        );

        // Merge the options (custom options take precedence).
        $curl_opts = $this->_curl_opts + $curl_opts + $curl_defaults;

        // Call the API.
        $curl = curl_init($url);
        curl_setopt_array($curl, $curl_opts);
        $response = curl_exec($curl);
        $curl_info = curl_getinfo($curl);
        curl_close($curl);

        // Retrieve the info
        $header_size = $curl_info['header_size'];
        $headers = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        // Return it raw.
        return array(
            'body' => $body,
            'status' => $curl_info['http_code'],
            'headers' => self::parse_headers($headers)
        );
    }

    /**
     * Request the access token associated with this library.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->_access_token;
    }

    /**
     * Assign a new access token to this library.
     *
     * @param string $access_token the new access token
     */
    public function setToken($access_token)
    {
        $this->_access_token = $access_token;
    }

    /**
     * Sets custom cURL options.
     *
     * @param array $curl_opts An associative array of cURL options.
     */
    public function setCURLOptions($curl_opts = array())
    {
        $this->_curl_opts = $curl_opts;
    }

    /**
     * Convert the raw headers string into an associated array
     *
     * @param string $headers
     * @return array
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
     * Request an access token. This is the final step of the
     * OAuth 2 workflow, and should be called from your redirect url.
     *
     * @param string $code The authorization code that was provided to your redirect url
     * @param string $redirect_uri The redirect_uri that is configured on your app page, and was used in buildAuthorizationEndpoint
     * @return array This array contains three keys, 'status' is the status code, 'body' is an object representation of the json response body, and headers are an associated array of response headers
     */
    public function accessToken($code, $redirect_uri) {
        return $this->request(self::ACCESS_TOKEN_ENDPOINT, array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirect_uri
        ), "POST", false);
    }

    /**
     * Get client credentials for requests.
     *
     * @param mixed $scope Scopes to request for this token from the server.
     * @return array Response from the server with the tokens, we also set it into this object.
     */
    public function clientCredentials($scope = 'public') {
        if (is_array($scope)) {
            $scope = implode(' ', $scope);
        }

        $token_response = $this->request(self::CLIENT_CREDENTIALS_TOKEN_ENDPOINT, array(
            'grant_type' => 'client_credentials',
            'scope' => $scope
        ), "POST", false);

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
     * Build the url that your user.
     *
     * @param string $redirect_uri The redirect url that you have configured on your app page
     * @param string $scope An array of scopes that your final access token needs to access
     * @param string $state A random variable that will be returned on your redirect url. You should validate that this matches
     * @return string
     */
    public function buildAuthorizationEndpoint($redirect_uri, $scope = 'public', $state = null) {
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
     * Upload a file. This should be used to upload a local file.
     * If you want a form for your site to upload direct to Vimeo,
     * you should look at the POST /me/videos endpoint.
     *
     * @param string $file_path Path to the video file to upload.
     * @param boolean $upgrade_to_1080 Should we automatically upgrade the video file to 1080p
     * @throws VimeoUploadException
     * @return array Status
     */
    public function upload($file_path, $upgrade_to_1080 = false, $machine_id = null)
    {
        // Validate that our file is real.
        if (!is_file($file_path)) {
            throw new VimeoUploadException('Unable to locate file to upload.');
        }

        // Begin the upload request by getting a ticket
        $ticket_args = array('type' => 'streaming', 'upgrade_to_1080' => $upgrade_to_1080);
        if ($machine_id !== null) {
            $ticket_args['machine_id'] = $machine_id;
        }
        $ticket = $this->request('/me/videos', $ticket_args, 'POST');

        return $this->perform_upload($file_path, $ticket);
    }

    /**
     * Replace the source of a single Vimeo video.
     *
     * @param string $video_uri Video uri of the video file to replace.
     * @param string $file_path Path to the video file to upload.
     * @param boolean $upgrade_to_1080 Should we automatically upgrade the video file to 1080p
     * @throws VimeoUploadException
     * @return array Status
     */
    public function replace($video_uri, $file_path, $upgrade_to_1080 = false, $machine_id = null)
    {
        //  Validate that our file is real.
        if (!is_file($file_path)) {
            throw new VimeoUploadException('Unable to locate file to upload.');
        }

        $uri = $video_uri . self::REPLACE_ENDPOINT;

        // Begin the upload request by getting a ticket
        $ticket_args = array('type' => 'streaming', 'upgrade_to_1080' => $upgrade_to_1080);
        if ($machine_id !== null) {
            $ticket_args['machine_id'] = $machine_id;
        }
        $ticket = $this->request($uri, $ticket_args, 'PUT');

        return $this->perform_upload($file_path, $ticket);
    }

    /**
     * Take an upload ticket and perform the actual upload
     *
     * @param string $file_path Path to the video file to upload.
     * @param Ticket $ticket Upload ticket data.
     * @throws VimeoUploadException
     * @return array Status
     */
    private function perform_upload($file_path, $ticket)
    {
        if ($ticket['status'] != 201) {
            $ticket_error = !empty($ticket['body']['error']) ? '[' . $ticket['body']['error'] . ']' : '';
            throw new VimeoUploadException('Unable to get an upload ticket.' . $ticket_error);
        }

        // We are going to always target the secure upload URL.
        $url = $ticket['body']['upload_link_secure'];

        // We need a handle on the input file since we may have to send segments multiple times.
        $file = fopen($file_path, 'r');

        // PUTs a file in a POST....do for the streaming when we get there.
        $curl_opts = array(
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $file,
            CURLOPT_INFILESIZE => filesize($file_path),
            CURLOPT_UPLOAD => true,
            CURLOPT_HTTPHEADER => array('Expect: ', 'Content-Range: replaced...')
        );

        // These are the options that set up the validate call.
        $curl_opts_check_progress = array(
            CURLOPT_PUT => true,
            CURLOPT_HTTPHEADER => array('Content-Length: 0', 'Content-Range: bytes */*')
        );

        // Perform the upload by streaming as much to the server as possible and ending when we reach the filesize on the server.
        $size = filesize($file_path);
        $server_at = 0;
        do {
            // The last HTTP header we set MUST be the Content-Range, since we need to remove it and replace it with a proper one.
            array_pop($curl_opts[CURLOPT_HTTPHEADER]);
            $curl_opts[CURLOPT_HTTPHEADER][] = 'Content-Range: bytes ' . $server_at . '-' . $size . '/' . $size;

            fseek($file, $server_at);   //  Put the FP at the point where the server is.
            $upload_response = $this->_request($url, $curl_opts);   //  Send what we can.
            $progress_check = $this->_request($url, $curl_opts_check_progress); //  Check on what the server has.

            // Figure out how much is on the server.
            list(, $server_at) = explode('-', $progress_check['headers']['Range']);
            $server_at = (int)$server_at;
        } while ($server_at < $size);

        // Complete the upload on the server.
        $completion = $this->request($ticket['body']['complete_uri'], array(), 'DELETE');

        // Validate that we got back 201 Created
        $status = (int) $completion['status'];
        if ($status != 201) {
            throw new VimeoUploadException('Error completing the upload.');
        }

        // Furnish the location for the new clip in the API via the Location header.
        return $completion['headers']['Location'];
    }

    /**
     * Uploads an image to an individual picture response.
     *
     * @param string $pictures_uri The pictures endpoint for a resource that allows picture uploads (eg videos and users)
     * @param string $file_path The path to your image file
     * @param boolean $activate Activate image after upload
     * @throws VimeoUploadException
     * @return string The URI of the uploaded image.
     */
    public function uploadImage($pictures_uri, $file_path, $activate = false) {
        // Validate that our file is real.
        if (!is_file($file_path)) {
            throw new VimeoUploadException('Unable to locate file to upload.');
        }

        $pictures_response = $this->request($pictures_uri, array(), 'POST');
        if ($pictures_response['status'] != 201) {
            throw new VimeoUploadException('Unable to request an upload url from vimeo');
        }

        $upload_url = $pictures_response['body']['link'];

        $image_resource = fopen($file_path, 'r');

        $curl_opts = array(
            CURLOPT_HEADER => 1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 240,
            CURLOPT_UPLOAD => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_READDATA => $image_resource
        );

        $curl = curl_init($upload_url);
        curl_setopt_array($curl, $curl_opts);
        $response = curl_exec($curl);
        $curl_info = curl_getinfo($curl);

        if (!$response) {
            $error = curl_error($curl);
            throw new VimeoUploadException($error);
        }
        curl_close($curl);

        if ($curl_info['http_code'] != 200) {
            throw new VimeoUploadException($response);
        }

        // Activate the uploaded image
        if ($activate) {
            $completion = $this->request($pictures_response['body']['uri'], array('active' => true), 'PATCH');
        }

        return $pictures_response['body']['uri'];
    }

    /**
     * Uploads a text track.
     *
     * @param string $texttracks_uri The text tracks uri that we are adding our text track to
     * @param string $file_path The path to your text track file
     * @param string $track_type The type of your text track
     * @param string $language The language of your text track
     * @throws VimeoUploadException
     * @return string The URI of the uploaded text track.
     */
    public function uploadTexttrack ($texttracks_uri, $file_path, $track_type, $language) {
        // Validate that our file is real.
        if (!is_file($file_path)) {
            throw new VimeoUploadException('Unable to locate file to upload.');
        }

        // To simplify the script we provide the filename as the text track name, but you can provide any value you want.
        $name = array_slice(explode("/", $file_path), -1);
        $name = $name[0];

        $texttrack_response = $this->request($texttracks_uri, array('type' => $track_type, 'language' => $language, 'name' => $name), 'POST');
        if ($texttrack_response['status'] != 201) {
            throw new VimeoUploadException('Unable to request an upload url from vimeo');
        }

        $upload_url = $texttrack_response['body']['link'];

        $texttrack_resource = fopen($file_path, 'r');

        $curl_opts = array(
            CURLOPT_HEADER => 1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 240,
            CURLOPT_UPLOAD => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_READDATA => $texttrack_resource
        );

        $curl = curl_init($upload_url);
        curl_setopt_array($curl, $curl_opts);
        $response = curl_exec($curl);
        $curl_info = curl_getinfo($curl);

        if (!$response) {
            $error = curl_error($curl);
            throw new VimeoUploadException($error);
        }
        curl_close($curl);

        if ($curl_info['http_code'] != 200) {
            throw new VimeoUploadException($response);
        }

        return $texttrack_response['body']['uri'];
    }

}
