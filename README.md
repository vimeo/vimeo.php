vimeo.php
=========

## Index

 - [Get Started](#get-started-with-the-vimeo-api)
 - [Help](#direct-help)
 - [Troubleshooting](#troubleshooting)
 - [Installation](#installation)
 - [Authentication/Access Tokens](#generate-your-access-token)
  - [Unauthenticated tokens](#unauthenticated)
  - [Authenticated tokens](#authenticated)
 - [Make requests](#make-requests)
 - [Upload videos from a server](#upload-videos-from-the-server)
 - [Replace videos from a server](#replace-videos-from-the-server)
 - [Client side uploads](#upload-or-replace-videos-from-the-client)
 - [Upload images](#upload-images)
 

## Get started with the Vimeo API

There is a lot of information about the Vimeo API at https://developer.vimeo.com/api/start. Most of your questions will be answered there!

## Direct Help

 * [Stack Overflow](http://stackoverflow.com/questions/tagged/vimeo-api)
 * [Google Group](https://groups.google.com/forum/#!forum/vimeo-api)
 * [Vimeo Support](https://vimeo.com/help/contact)

#### NOTE: How to use the PHP library with the Vimeo Docs.

The API docs often uses dot notation to represent a hierarchy of data (eg. privacy.view). Because this library sends all data using JSON, you must use nested associative arrays, not dot notation.

```php
	// The docs refer to the following as "privacy.view"
    array('privacy' => array('view' => 'disable'));
```


## Installation

### Composer

1. Add the vimeo library to your composer.json file's require field

```
    {
        "require" : {
            "vimeo/vimeo-api" : "1.1.*"
        }
    }
```

2. Use the library `$lib = new \Vimeo\Vimeo($client_id, $client_secret)`


### Manual
    
1. Download the latest release : [v1.1.0]
2. Include the autoloader `require("/path/to/vimeo.php/autoload.php");`
3. Use the library `$lib = new \Vimeo\Vimeo($client_id, $client_secret)`


## Generate your Access token

All requests require access tokens. There are two types of access tokens.
 - [Unauthenticated](#unauthenticated) - Access tokens without a user. These tokens can only view public data
 - [Authenticated](#authenticated) - Access tokens with a user. These tokens interact on behalf of the authenticated user.

### Unauthenticated

Unauthenticated API requests must generate an access token. You should not generate a new access token for each request, you should request an access token once and use it forever.

```PHP
    // scope is an array of permissions your token needs to access. You can read more at https://developer.vimeo.com/api/authentication#scopes
    $token = $lib->clientCredentials(scope);

    // usable access token
    var_dump(access_token->access_token);
    
    // accepted scopes
    var_dump(access_token->scope);
```


### Authenticated


1. Build a link to Vimeo so your users can authorize your app.

```PHP
        $url = $lib->buildAuthorizationEndpoint($redirect_uri, $scopes, $state)
```

Name         | Type     | Description
-------------|----------|------------
redirect_uri | string   | The uri the user is redirected to in step 3. This value must be provided to every step of the authorization process including creating your app, building your authorization endpoint and exchanging your authorization code for an access token.
scope        | array    | An array of permissions your token needs to access. You can read more at https://developer.vimeo.com/api/authentication#scopes.
state        | string   | A value unique to this authorization request. You should generate it randomly, and validate it in step 3.
        
        
2. Your user will need to access the authorization endpoint (either by clicking the link or through a redirect). On the authorization endpoint the user will have the option to deny your app any scopes you have requested. If they deny your app, they will be redirected back to your redirect_url with an ````error```` parameter.

3. If the user accepts your app, they will be redirected back to your redirect\_uri with a ````code```` and ````state```` query parameter (eg. http://yourredirect.com?code=abc&state=xyz). 
    1. You must validate that the ```state``` matches your state from step 1. 
    2. If the state is valid, you can exchange your code and redirect\_uri for an access token.

```PHP
        // redirect_uri must be provided, and must match your configured uri
        $token = $lib->accessToken(code, redirect_uri);

        // usable access token
        var_dump($token->access_token);
        
        // accepted scopes
        var_dump(token->scope);
                    
        // authenticated user
        var_dump(access_token->user);
```

For additional information, check out the [example](https://github.com/vimeo/vimeo.php/blob/master/example/auth.php)

## Make requests

The API library has a `request` method which takes three parameters. It returns an associative array containing all of the relvant request information.

**Usage**

Name        | Type     | Description
------------|----------|------------
 url        | string   | The URL path (e.g.: `/users/dashron`).
 params     | string   | An object containing all of your parameters (e.g.: `{ "per_page": 5, "filter" : "featured"}` ).
 method     | string   | The HTTP method (e.g.: `GET`).

```PHP
    $response = $lib->request('/me/videos', array('per_page' => 2), 'GET');
```

**Response**

The response array will contain three keys.

Name        | Type        | Description
------------|-------------|------------
body        | assoc array | The parsed request body. All responses are JSON so we parse this for you, and give you the result.
status      | number      | The HTTP status code of the response. This partially informs you about the success of your API request.
headers     | assoc array | An associative array containing all of the response headers.

```PHP
    $response = $lib->request('/me/videos', array('per_page' => 2), 'GET');
    var_dump($response['body']);
```


## Upload videos from the Server

To upload videos you must call the `upload` method. It accepts two parameters. It will return the URI of the new video.
For more information check out the [example](https://github.com/vimeo/vimeo.php/blob/master/example/upload.php)

Name      | Type     | Description
----------|----------|------------
file      | string   | Full path to the upload file on the local system.
upgrade   | boolean  | (Optional) Defaults to false. Requests for a 1080p encode to be made from this video. This feature is only available to [Vimeo PRO](https://vimeo.com/pro) members. For more information, check out the [FAQ](https://vimeo.com/help/faq/uploading-to-vimeo/uploading-basics#does-vimeo-support-1080p-hd).

```PHP
    $response = $lib->upload('/home/aaron/Downloads/ada.mp4', false)
```

## Replace videos from the Server

To replace the source file of a video, you must call the `replace` method. It accepts three parameters. It will return the URI of the replaced video.

Name      | Type     | Description
----------|----------|------------
video_uri | string   | The URI of the original video. Once uploaded and successfully transcoded your source video file will be swapped with this new video file.
file      | string   | Full path to the upload file on the local system.
upgrade   | boolean  | (Optional) Defaults to false. Requests for a 1080p encode to be made from this video. This feature is only available to [Vimeo PRO](https://vimeo.com/pro) members. For more information, check out the [FAQ](https://vimeo.com/help/faq/uploading-to-vimeo/uploading-basics#does-vimeo-support-1080p-hd).

```PHP
    $response = $lib->upload('/videos/12345', '/home/aaron/Downloads/ada.mp4', false)
```

## Upload or replace videos from the client

To upload from the client, you will have to mix some server side, and client side API requests. We support two workflows, the first of which is much easier than the second.

### Simple POST uploads

This workflow is well documented on Vimeo's developer site. You can read more here : https://developer.vimeo.com/api/upload#http-post-uploading.


### Streaming uploads

Streaming uploads support progress bars, and resumable uploading. If you want to perform these uploads client side you will need to start with some server side requests.
Read through the [Vimeo documentation](https://developer.vimeo.com/api/upload#http-put-uploading) first. Step 1 and 4 should be performed on the server, while step 2 and 3 can be performed on the client. With this workflow the video will never be transferred to your servers.

## Upload images

To upload an image, call the `uploadImage` method. It takes three parameters.
For more information check out the [example](https://github.com/vimeo/vimeo.php/blob/master/example/upload_image.php)

Name         | Type     | Description
-------------|----------|------------
pictures_uri | string   | The URI to the pictures collection for a single resource. eg. `/videos/12345/pictures`. You can always find this in the resource representation.
file         | string   | Full path to the upload file on the local system.
activate     | boolean  | (Optional) Defaults to false. If true this picture will become the default picture for the associated resource.

```PHP
    $response = $lib->uploadImage('/videos/12345/pictures', '/home/aaron/Downloads/ada.png', true)
```

# Troubleshooting

If your API request returns with an empty array, or a status code of 0 it likely means you do not have the proper https certificates. You can read more here : http://unitstep.net/blog/2009/05/05/using-curl-in-php-to-access-https-ssltls-protected-sites/. Note that we highly recommend the full solution. The quick fix will leave your tokens vulnerable.

If you have any additional questions or problems, create a [ticket](https://github.com/vimeo/vimeo.php/issues) or [contact us](https://vimeo.com/help/contact)

# Contributors
- [dashron](https://github.com/dashron)
- [sclm](https://github.com/sclm)
- [greedo](https://github.com/greedo)
- [sgmendez](https://github.com/sgmendez) ([32e4d7753a66e71dd158bf55b8a4b30ed564ef30](https://github.com/vimeo/vimeo.php/commit/32e4d7753a66e71dd158bf55b8a4b30ed564ef30))
- [vinkla](https://github.com/vinkla) ([033a43156d6adae9e5757aefbfcf67c9ecdc605c](https://github.com/vimeo/vimeo.php/commit/033a43156d6adae9e5757aefbfcf67c9ecdc605c))
