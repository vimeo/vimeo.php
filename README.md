vimeo.php
=========
**Vimeo.php** is a library for interacting with the latest verion of the [Vimeo](https://vimeo.com) API.

### Installation
To install vimeo.php, you can download the latest version from https://github.com/vimeo/vimeo.php.

To utilize the library in your code, you should include `vimeo.php`.

### Usage
##### Making an API Request
In the Vimeo API, all requests must be authenticated in some method.  For applications that only require access to public information, we offer oAuth2's client credentials grant.  For applications that reqiure users to authenticate, oAuth2's authorization code grant.

An example of this usage is:

```php
//  Create a handle for the Vimeo API, with the access token.
$vimeo = new Vimeo(YOU_APPLICATON_ID, YOUR_APPLCATION_SECRET, ACCESS_TOKEN);
//  Request the authenticated user's information
$user_data = $vimeo->request('/me');
```


###### Application Only Access Token
To get an application only token, you should run the following code (commented for clarity).

```php
//  Create a handle for the Vimeo API.
$vimeo = new Vimeo(YOU_APPLICATON_ID, YOUR_APPLCATION_SECRET);
//  Request the client credentials from the authentication server.
$token_response = $vimeo->clientCredentials();
```

This response will contain the token you can add into this handle with `$vimeo->setAccessToken($token_from_response)`.  The token should be stored for later use, and you can save time in future runs by using it and skipping the request step.

###### User authenticated Access Tokens
Getting a user to authenticate with your application is a bit more complicated.  Your application will have to redirect the user to Vimeo where they log in and verify that they will grant your application the requested scopes.  The basic required steps are:

1. Initialize the Vimeo API handle, `$vimeo = new Vimeo(YOU_APPLICATON_ID, YOUR_APPLCATION_SECRET);`
2. Send the user to the authorization page on Vimeo.  The link is given by `$vimeo->buildAuthorizationEndpoint($redirect_uri, $scopes);` (Note: if your application only requires public information, you can omit the scopes argument).
3. The user authenicates and authorizes your app on the Vimeo site and is redirected back to the location provided in $redirect_uri. (Note: This URI must be registered with Vimeo on the [developer site](https://developer.vimeo.com/)).
4. When the user is returned we will have a querystring parameter `code`.
5. Now that we have the code, we need to exchange it for an access token.  We repeat step 1 to get ourselves a handle for the Vimeo API.
6. The code can be exchanged by calling `$vimeo->accessToken($code, $redirect_uri);`

An example of this can be seen in `examples/auth.php`.


#### Uploading a file
Uploading a file can occur in one of two ways:

1. Streaming the file to the Vimeo servers
2. POSTing the file from a client web browser

The library provides a sample tool for method #1 in `examples/upload.php`.

If you want to integrate the upload functionality with an existing PHP application, you should follow these steps:

1. Initialize a `Vimeo` class to interact with the server with the proper credentials. \*
2. With the path to your file, call `$vimeo->upload($file_path)`.
3. The response from that function will contain a `Location` header with the URI to the newly created resource.  You can call that to set metadata such as the title or check on the transcode status.

**\* Note**: Vimeo requires applications that perform uploads to request and be granted special permissions.  This can be done on the [Vimeo developer site](https://developer.vimeo.com/).


If you are developing a web application and would like to have the users upload directly to Vimeo's servers instead of relaying through yours, you can utilize the POST method.  The simplest implementation of this is as follows:

1. Configure your redirect target with your app on the [Vimeo developer site](https://developer.vimeo.com).
2. Make sure that your callback is functional, it will recieve the data when Vimeo has finalized the upload.
3. Initialize a `Vimeo` class to interact with the server with the proper credentials (requires the same special permission as the streaming API).
4. Call `$vimeo->request('/me/videos', array('type' => 'POST', 'redirect_url' => $redirect_target), 'POST')`
5. The response body should contain a field called `form`, this can be accessed via `$response['body']->form`.  The contents of that should be printed into your page and sent to the end user.  Once they submit the form it will send the video to Vimeo's servers and we will complete the flow before sending them back to the provided redirect_url.
6. When the user comes back to the redirect_url, you will have an additional query param (to any you may have included) called `video_uri`.  This can be used to load and edit the newly created clip via the standard API methods.
7. 

# Troubleshooting

1. *todo* explain "curl not found" error
2. If your api request returns with an empty array, it likely means you do not have the proper https certificates. You can find more information on how to solve the problem here : http://unitstep.net/blog/2009/05/05/using-curl-in-php-to-access-https-ssltls-protected-sites/ 
