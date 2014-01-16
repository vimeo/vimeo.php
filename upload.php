<?php

include 'vimeo.php';

//  Set up authentication constants.
define('CLIENT_ID', '<your oauth2 token from https://developer.vimeo.com/apps>');
define('CLIENT_SECRET', '<your oauth2 secret from https://developer.vimeo.com/apps>');
define('USER_TOKEN', '<a bearer token with the "upload" scope>');

//  Make our authenticated client handle.
$vimeo = new Vimeo(CLIENT_ID, CLIENT_SECRET, USER_TOKEN);

//  Get the args from the command line to see what files to upload.
$files = $argv;
array_shift($files);

//   Keep track of what we have uploaded.
$uploaded = array();

//  Send the files to the upload script.
foreach ($files as $file_name) {
    //  Update progress.
    print 'Uploading ' . $file_name . "\n";
    try {
        //  Send this to the API library.
        $uri = $vimeo->upload($file_name);

        //  Now that we know where it is in the API, let's get the info about it so we can find the link.
        $video_data = $vimeo->request($uri);

        //  Pull the link out of successful data responses.
        $link = '';
        if($video_data['status'] == 200) {
            $link = $video_data['body']->link;
        }

        //  Store this in our array of complete videos.
        $uploaded[] = array('file' => $file_name, 'api_video_uri' => $uri, 'link' => $link);
    }
    catch (VimeoUploadException $e) {
        //  We may have had an error.  We can't resolve it here necessarily, so report it to the user.
        print 'Error uploading ' . $file_name . "\n";
        print 'Server reported: ' . $e->getMessage() . "\n";
    }
}

//  Provide a summary on completion with links to the videos on the site.
print 'Uploaded ' . count($uploaded) . " files.\n\n";
foreach ($uploaded as $site_video) {
    extract($site_video);
    print "$file is at $link.\n";
}
