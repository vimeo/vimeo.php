<?php
namespace Vimeo;

use Vimeo\Exceptions\VimeoUploadException;
use Vimeo\Exceptions\VimeoRequestException;

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

if (!function_exists('json_decode'))
{
    throw new \Exception('We could not find json_decode. json_decode is found in php 5.2 and up, but may be missing on some Linux systems due to licensing conflicts. If you are running ubuntu try "sudo apt-get install php5-json".');
}

class Vimeo
{
    const ROOT_ENDPOINT = 'https://api.vimeo.com';
    const AUTH_ENDPOINT = 'https://api.vimeo.com/oauth/authorize';
    const ACCESS_TOKEN_ENDPOINT = '/oauth/access_token';
    const CLIENT_CREDENTIALS_TOKEN_ENDPOINT = '/oauth/authorize/client';
    const REPLACE_ENDPOINT = '/files';
    const VERSION_STRING = 'application/vnd.vimeo.*+json; version=3.2';
    const USER_AGENT = 'vimeo.php 1.2.6; (http://developer.vimeo.com/api/docs)';
    const CERTIFICATE_PATH = '/certificates/vimeo-api.pem';

    private $clientId = null;
    private $clientSecret = null;
    private $accessToken = null;

    protected $curlOpts = array();
    protected $curlDefaults = array();

    /**
     * Creates the Vimeo library, and tracks the client and token information.
     *
     * @param string $clientId Your applications client id. Can be found on developer.vimeo.com/apps
     * @param string $clientSecret Your applications client secret. Can be found on developer.vimeo.com/apps
     * @param string $accessToken Your applications client id. Can be found on developer.vimeo.com/apps or generated using OAuth 2.
     */
    public function __construct($clientId, $clientSecret, $accessToken = null)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->accessToken = $accessToken;
        $this->curlDefaults = array(
            CURLOPT_HEADER => 1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            //Certificate must indicate that the server is the server to which you meant to connect.
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CAINFO => realpath(__DIR__ .'/../..') . self::certificatePath
        );
    }

    /**
     * Make an API request to Vimeo.
     *
     * @param string $url A Vimeo API Endpoint. Should not include the host
     * @param array $params An array of parameters to send to the endpoint. If the HTTP method is GET, they will be added to the url, otherwise they will be written to the body
     * @param string $method The HTTP Method of the request
     * @param bool $jsonBody
     * @return array This array contains three keys, 'status' is the status code, 'body' is an object representation of the json response body, and headers are an associated array of response headers
     */
    public function doRequest($url, $params = array(), $method = 'GET', $jsonBody = true)
    {
        // add accept header hardcoded to version 3.0
        $headers[] = 'Accept: ' . self::versionString;
        $headers[] = 'User-Agent: ' . self::userAgent;
        $method = strtoupper($method);

        // add bearer token, or client information
        if (!empty($this->accessToken)) {
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
        }
        else {
            //  this may be a call to get the tokens, so we add the client info.
            $headers[] = 'Authorization: Basic ' . $this->authHeader();
        }

        //  Set the methods, determine the URL that we should actually request and prep the body.
        $curlOpts = array();
        switch ($method) {
            case 'GET' :
                if (!empty($params)) {
                    $queryComponent = '?' . http_build_query($params, '', '&');
                } else {
                    $queryComponent = '';
                }

                $curlUrl = self::rootEndPoint . $url . $queryComponent;
                break;

            case 'POST' :
            case 'PATCH' :
            case 'PUT' :
            case 'DELETE' :
                if ($jsonBody && !empty($params)) {
                    $headers[] = 'Content-Type: application/json';
                    $body = json_encode($params);
                } else {
                    $body = http_build_query($params, '', '&');
                }

                $curlUrl = self::rootEndPoint . $url;
                $curlOpts = array(
                    CURLOPT_POST => true,
                    CURLOPT_CUSTOMREQUEST => $method,
                    CURLOPT_POSTFIELDS => $body
                );
                break;
        }

        // Set the headers
        $curlOpts[CURLOPT_HTTPHEADER] = $headers;

        $response = $this->doRequest($curlUrl, $curlOpts);

        $response['body'] = json_decode($response['body'], true);

        return $response;
    }

    /**
     * Internal function to handle requests, both authenticated and by the upload function.
     *
     * @param string $url
     * @param array $curlOpts
     * @return array
     */
    private function request($url, $curlOpts = array()) {
        // Merge the options (custom options take precedence).
        $curlOpts = $this->curlOpts + $curlOpts + $this->curlDefaults;

        // Call the API.
        $curl = curl_init($url);
        curl_setopt_array($curl, $curlOpts);
        $response = curl_exec($curl);
        $curlInfo = curl_getinfo($curl);

        if(isset($curlInfo['http_code']) && $curlInfo['http_code'] === 0){
            $curlError = curl_error($curl);
            $curlError = !empty($curlError) ? '[' . $curlError .']' : '';
            throw new VimeoRequestException('Unable to complete request.' . $curlError);
        }

        curl_close($curl);

        // Retrieve the info
        $headerSize = $curlInfo['header_size'];
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        // Return it raw.
        return array(
            'body' => $body,
            'status' => $curlInfo['http_code'],
            'headers' => self::parseHeaders($headers)
        );
    }

    /**
     * Request the access token associated with this library.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->accessToken;
    }

    /**
     * Assign a new access token to this library.
     *
     * @param string $accessToken the new access token
     */
    public function setToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * Sets custom cURL options.
     *
     * @param array $curlOpts An associative array of cURL options.
     */
    public function setCURLOptions($curlOpts = array())
    {
        $this->curlOpts = $curlOpts;
    }

    /**
     * Gets custom cURL options.
     *
     * @param array $curlOpts An associative array of cURL options.
     */
    public function getCURLOptions()
    {
        return $this->curlOpts;
    }

    /**
     * Convert the raw headers string into an associated array
     *
     * @param string $headers
     * @return array
     */
    public static function parseHeaders($headers)
    {
        $finalHeaders = array();
        $list = explode("\n", trim($headers));

        $http = array_shift($list);

        foreach ($list as $header) {
            $parts = explode(':', $header, 2);
            $finalHeaders[trim($parts[0])] = isset($parts[1]) ? trim($parts[1]) : '';
        }

        return $finalHeaders;
    }

    /**
     * Request an access token. This is the final step of the
     * OAuth 2 workflow, and should be called from your redirect url.
     *
     * @param string $code The authorization code that was provided to your redirect url
     * @param string $redirectUri The redirectUri that is configured on your app page, and was used in buildAuthorizationEndpoint
     * @return array This array contains three keys, 'status' is the status code, 'body' is an object representation of the json response body, and headers are an associated array of response headers
     */
    public function accessToken($code, $redirectUri) {
        return $this->doRequest(self::accessTokenEndPoint, array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirectUri' => $redirectUri
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

        $tokenResponse = $this->doRequest(self::clientCredentialsTokenEndPoint, array(
            'grant_type' => 'client_credentials',
            'scope' => $scope
        ), "POST", false);

        return $tokenResponse;
    }

    /**
     * Get authorization header for retrieving tokens/credentials.
     *
     * @return string
     */
    private function authHeader() {
        return base64_encode($this->clientId . ':' . $this->clientSecret);
    }

    /**
     * Build the url that your user.
     *
     * @param string $redirectUri The redirect url that you have configured on your app page
     * @param string $scope An array of scopes that your final access token needs to access
     * @param string $state A random variable that will be returned on your redirect url. You should validate that this matches
     * @return string
     */
    public function buildAuthorizationEndpoint($redirectUri, $scope = 'public', $state = null) {
        $query = array(
            "response_type" => 'code',
            "cleintId" => $this->clientId,
            "redirect_uri" => $redirectUri
        );

        $query['scope'] = $scope;
        if (empty($scope)) {
            $query['scope'] = 'public';
        } else if (is_array($scope)) {
            $query['scope'] = implode(' ', $scope);
        }

        if (!empty($state)) {
            $query['state'] = $state;
        }

        return self::authEndPoint . '?' . http_build_query($query);
    }

    /**
     * Upload a file. This should be used to upload a local file.
     * If you want a form for your site to upload direct to Vimeo,
     * you should look at the POST /me/videos endpoint.
     *
     * @param string $filePath Path to the video file to upload.
     * @param boolean $isHighQuality Should we automatically upgrade the video file to 1080p
     * @throws VimeoUploadException
     * @return array Status
     */
    public function upload($filePath, $isHighQuality = false, $machineId = null)
    {
        // Validate that our file is real.
        if (!is_file($filePath)) {
            throw new VimeoUploadException('Unable to locate file to upload.');
        }

        // Begin the upload request by getting a ticket
        $ticketArgs = array('type' => 'streaming', 'upgrade_to_1080' => $isHighQuality);
        if ($machineId !== null) {
            $ticket_args['machine_id'] = $machineId;
        }
        $ticket = $this->doRequest('/me/videos', $ticketArgs, 'POST');

        return $this->performUpload($filePath, $ticket);
    }

    /**
     * Replace the source of a single Vimeo video.
     *
     * @param string $videoUri Video uri of the video file to replace.
     * @param string $filePath Path to the video file to upload.
     * @param boolean $isHighQuality Should we automatically upgrade the video file to 1080p
     * @throws VimeoUploadException
     * @return array Status
     */
    public function replace($videoUri, $filePath, $isHighQuality = false, $machineId = null)
    {
        //  Validate that our file is real.
        if (!is_file($filePath)) {
            throw new VimeoUploadException('Unable to locate file to upload.');
        }

        $uri = $videoUri . self::replaceEndPoint;

        // Begin the upload request by getting a ticket
        $ticketArgs = array('type' => 'streaming', 'upgrade_to_1080' => $isHighQuality);
        if ($machineId !== null) {
            $ticketArgs['machine_id'] = $machineId;
        }
        $ticket = $this->doRequest($uri, $ticketArgs, 'PUT');

        return $this->performUpload($filePath, $ticket);
    }

    /**
     * Take an upload ticket and perform the actual upload
     *
     * @param string $filePath Path to the video file to upload.
     * @param Ticket $ticket Upload ticket data.
     * @throws VimeoUploadException
     * @return array Status
     */
    private function performUpload($filePath, $ticket)
    {
        if ($ticket['status'] != 201) {
            $ticketError = !empty($ticket['body']['error']) ? '[' . $ticket['body']['error'] . ']' : '';
            throw new VimeoUploadException('Unable to get an upload ticket.' . $ticketError);
        }

        // We are going to always target the secure upload URL.
        $url = $ticket['body']['upload_link_secure'];

        // We need a handle on the input file since we may have to send segments multiple times.
        $file = fopen($filePath, 'r');

        // PUTs a file in a POST....do for the streaming when we get there.
        $curlOpts = array(
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $file,
            CURLOPT_INFILESIZE => filesize($filePath),
            CURLOPT_UPLOAD => true,
            CURLOPT_HTTPHEADER => array('Expect: ', 'Content-Range: replaced...')
        );

        // These are the options that set up the validate call.
        $curlOptsCheckProgress = array(
            CURLOPT_PUT => true,
            CURLOPT_HTTPHEADER => array('Content-Length: 0', 'Content-Range: bytes */*')
        );

        // Perform the upload by streaming as much to the server as possible and ending when we reach the filesize on the server.
        $size = filesize($filePath);
        $serverAt = 0;
        do {
            // The last HTTP header we set MUST be the Content-Range, since we need to remove it and replace it with a proper one.
            array_pop($curlOpts[CURLOPT_HTTPHEADER]);
            $curlOpts[CURLOPT_HTTPHEADER][] = 'Content-Range: bytes ' . $serverAt . '-' . $size . '/' . $size;

            fseek($file, $serverAt);   //  Put the FP at the point where the server is.

            try {
                $this->request($url, $curlOpts);   //Send what we can.
            } catch (VimeoRequestException $exception) {
                // ignored, it's likely a timeout, and we should only consider a failure from the progress check as a legit failure
            }

            $progressCheck = $this->request($url, $curlOptsCheckProgress); //  Check on what the server has.

            // Figure out how much is on the server.
            list(, $serverAt) = explode('-', $progressCheck['headers']['Range']);
            $serverAt = (int)$serverAt;
        } while ($serverAt < $size);

        // Complete the upload on the server.
        $completion = $this->doRequest($ticket['body']['complete_uri'], array(), 'DELETE');

        // Validate that we got back 201 Created
        $status = (int) $completion['status'];
        if ($status != 201) {
            $error = !empty($completion['body']['error']) ? '[' . $completion['body']['error'] . ']' : '';
            throw new VimeoUploadException('Error completing the upload.'. $error);
        }

        // Furnish the location for the new clip in the API via the Location header.
        return $completion['headers']['Location'];
    }

    /**
     * Uploads an image to an individual picture response.
     *
     * @param string $picturesUri The pictures endpoint for a resource that allows picture uploads (eg videos and users)
     * @param string $filePath The path to your image file
     * @param boolean $activate Activate image after upload
     * @throws VimeoUploadException
     * @return string The URI of the uploaded image.
     */
    public function uploadImage($picturesUri, $filePath, $activate = false) {
        // Validate that our file is real.
        if (!is_file($filePath)) {
            throw new VimeoUploadException('Unable to locate file to upload.');
        }

        $picturesResponse = $this->doRequest($picturesUri, array(), 'POST');
        if ($picturesResponse['status'] != 201) {
            throw new VimeoUploadException('Unable to request an upload url from vimeo');
        }

        $uploadUrl = $picturesResponse['body']['link'];

        $imageResource = fopen($filePath, 'r');

        $curlOpts = array(
            CURLOPT_TIMEOUT => 240,
            CURLOPT_UPLOAD => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_READDATA => $imageResource
        );

        $curl = curl_init($uploadUrl);

        // Merge the options
        curl_setopt_array($curl, $curlOpts + $this->curlDefaults);
        $response = curl_exec($curl);
        $curlInfo = curl_getinfo($curl);

        if (!$response) {
            $error = curl_error($curl);
            throw new VimeoUploadException($error);
        }
        curl_close($curl);

        if ($curlInfo['http_code'] != 200) {
            throw new VimeoUploadException($response);
        }

        // Activate the uploaded image
        if ($activate) {
            $completion = $this->doRequest($picturesResponse['body']['uri'], array('active' => true), 'PATCH');
        }

        return $picturesResponse['body']['uri'];
    }

    /**
     * Uploads a text track.
     *
     * @param string $textTracksUri The text tracks uri that we are adding our text track to
     * @param string $filePath The path to your text track file
     * @param string $trackType The type of your text track
     * @param string $language The language of your text track
     * @throws VimeoUploadException
     * @return string The URI of the uploaded text track.
     */
    public function uploadTextTrack ($textTracksUri, $filePath, $trackType, $language) {
        // Validate that our file is real.
        if (!is_file($filePath)) {
            throw new VimeoUploadException('Unable to locate file to upload.');
        }

        // To simplify the script we provide the filename as the text track name, but you can provide any value you want.
        $name = array_slice(explode("/", $filePath), -1);
        $name = $name[0];

        $textTrackResponse = $this->doRequest($textTracksUri, array('type' => $trackType, 'language' => $language, 'name' => $name), 'POST');

        if ($textTrackResponse['status'] != 201) {
            throw new VimeoUploadException('Unable to request an upload url from vimeo');
        }

        $uploadUrl = $textTrackResponse['body']['link'];

        $textTrackResource = fopen($filePath, 'r');

        $curlOpts = array(
            CURLOPT_TIMEOUT => 240,
            CURLOPT_UPLOAD => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_READDATA => $textTrackResource
        );

        $curl = curl_init($uploadUrl);

        // Merge the options
        curl_setopt_array($curl, $curlOpts + $this->curlDefaults);
        $response = curl_exec($curl);
        $curlInfo = curl_getinfo($curl);

        if (!$response) {
            $error = curl_error($curl);
            throw new VimeoUploadException($error);
        }
        curl_close($curl);

        if ($curlInfo['http_code'] != 200) {
            throw new VimeoUploadException($response);
        }

        return $textTrackResponse['body']['uri'];
    }

}
