<?php
namespace Vimeo\Tests;

use Vimeo\Vimeo as Client;

/**
 * Better/more unit tests are coming.
 */
class VimeoTest extends \PHPUnit_Framework_TestCase
{
    public function testClient()
    {
        $client = new Client('client_id', 'client_secret');

        $this->assertInstanceOf('\Vimeo\Vimeo', $client);
        $this->assertNull($client->getToken());
    }
}
