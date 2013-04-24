<?
require_once('../vimeo.php');
$config = json_decode(file_get_contents('./config.json'), true);

$lib = new Vimeo($config['client_id'], $config['client_secret']);
$user = $lib->request('/users/dashron');
var_dump($user);
