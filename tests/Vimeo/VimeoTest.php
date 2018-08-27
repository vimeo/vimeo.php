<?php
namespace Vimeo;

use PHPUnit\Framework\TestCase;
use Vimeo\Vimeo;

class VimeoTest extends TestCase
{
    protected $clientId = 'client_id';
    protected $clientSecret = 'client_secret';

    public function testRequestGetUserInformation()
    {
        $this->markTestSkipped('Skipping until we have time to set up real tests with Travis secret storage.');

        // Arrange
        $vimeo = new Vimeo($this->clientId, $this->clientSecret);

        // Act
        $result = $vimeo->request('/users/userwillnotbefound');

        // Assert
        $this->assertSame('You must provide a valid authenticated access token.', $result['body']['error']);
    }

    public function testRequestGetUserInformationWithAccessToken()
    {
        $this->markTestSkipped('Skipping until we have time to set up real tests with Travis secret storage.');

        // Arrange
        $vimeo = new Vimeo($this->clientId, $this->clientSecret, 'fake_access_token');

        // Act
        $result = $vimeo->request('/users/userwillnotbefound');

        // Assert
        $this->assertSame('You must provide a valid authenticated access token.', $result['body']['error']);
    }

    public function testRequestGetUserInformationWithParams()
    {
        $this->markTestSkipped('Skipping until we have time to set up real tests with Travis secret storage.');

        // Arrange
        $vimeo = new Vimeo($this->clientId, $this->clientSecret);

        // Act
        $result = $vimeo->request('/users/userwillnotbefound', array('fake_key=fake_value'));

        // Assert
        $this->assertSame('You must provide a valid authenticated access token.', $result['body']['error']);
    }

    public function testGetToken()
    {
        $this->markTestSkipped('Skipping until we have time to set up real tests with Travis secret storage.');

        // Arrange
        $vimeo = new Vimeo($this->clientId, $this->clientSecret);

        // Act
        $vimeo->setToken('fake_access_token');

        // Assert
        $this->assertSame('fake_access_token', $vimeo->getToken());
    }

    public function testGetCurlOptions()
    {
        $this->markTestSkipped('Skipping until we have time to set up real tests with Travis secret storage.');

        // Arrange
        $vimeo = new Vimeo($this->clientId, $this->clientSecret);

        // Act
        $vimeo->setCurlOptions(array('custom_name' => 'custom_value'));
        $result = $vimeo->getCurlOptions();

        // Assert
        $this->assertInternalType('array', $result);
        $this->assertSame('custom_value', $result['custom_name']);
    }

    public function testAccessTokenWithCallingFakeRedirectUri()
    {
        $this->markTestSkipped('Skipping until we have time to set up real tests with Travis secret storage.');

        // Arrange
        $vimeo = new Vimeo($this->clientId, $this->clientSecret);

        // Act
        $result = $vimeo->accessToken('fake_auth_code', 'https://fake.redirect.uri');

        // Assert
        $this->assertSame('invalid_client', $result['body']['error']);
    }

    public function testClientCredentialsWithDefaultScope()
    {
        $this->markTestSkipped('Skipping until we have time to set up real tests with Travis secret storage.');

        // Arrange
        $vimeo = new Vimeo($this->clientId, $this->clientSecret);

        // Act
        $result = $vimeo->clientCredentials();

        // Assert
        $this->assertSame('You must provide a valid authenticated access token.', $result['body']['error']);
    }

    public function testClientCredentialsWithArrayScope()
    {
        $this->markTestSkipped('Skipping until we have time to set up real tests with Travis secret storage.');

        // Arrange
        $vimeo = new Vimeo($this->clientId, $this->clientSecret);

        // Act
        $result = $vimeo->clientCredentials(array('public'));

        // Assert
        $this->assertSame('You must provide a valid authenticated access token.', $result['body']['error']);
    }

    public function testBuildAuthorizationEndpointWithDefaultScopeAndNullState()
    {
        $this->markTestSkipped('Skipping until we have time to set up real tests with Travis secret storage.');

        // Arrange
        $vimeo = new Vimeo($this->clientId, $this->clientSecret);

        // Act
        $result = $vimeo->buildAuthorizationEndpoint('https://fake.redirect.uri');

        // Assert
        $this->assertSame('https://api.vimeo.com/oauth/authorize?response_type=code&client_id=client_id&redirect_uri=https%3A%2F%2Ffake.redirect.uri&scope=public', $result);
    }

    public function testBuildAuthorizationEndpointWithNullScopeAndNullState()
    {
        $this->markTestSkipped('Skipping until we have time to set up real tests with Travis secret storage.');

        // Arrange
        $vimeo = new Vimeo($this->clientId, $this->clientSecret);

        // Act
        $result = $vimeo->buildAuthorizationEndpoint('https://fake.redirect.uri', null);

        // Assert
        $this->assertSame('https://api.vimeo.com/oauth/authorize?response_type=code&client_id=client_id&redirect_uri=https%3A%2F%2Ffake.redirect.uri&scope=public', $result);
    }

    public function testBuildAuthorizationEndpointWithArrayScopeAndNullState()
    {
        $this->markTestSkipped('Skipping until we have time to set up real tests with Travis secret storage.');

        // Arrange
        $vimeo = new Vimeo($this->clientId, $this->clientSecret);

        // Act
        $result = $vimeo->buildAuthorizationEndpoint('https://fake.redirect.uri', array('public', 'private'));

        // Assert
        $this->assertSame('https://api.vimeo.com/oauth/authorize?response_type=code&client_id=client_id&redirect_uri=https%3A%2F%2Ffake.redirect.uri&scope=public+private', $result);
    }

    public function testBuildAuthorizationEndpointWithArrayScopeAndState()
    {
        $this->markTestSkipped('Skipping until we have time to set up real tests with Travis secret storage.');

        // Arrange
        $vimeo = new Vimeo($this->clientId, $this->clientSecret);

        // Act
        $result = $vimeo->buildAuthorizationEndpoint('https://fake.redirect.uri', array('public'), 'fake_state');

        // Assert
        $this->assertSame('https://api.vimeo.com/oauth/authorize?response_type=code&client_id=client_id&redirect_uri=https%3A%2F%2Ffake.redirect.uri&scope=public&state=fake_state', $result);
    }

    /**
     * @expectedException Vimeo\Exceptions\VimeoUploadException
     */
    public function testUploadWithNonExistedFile()
    {
        $this->markTestSkipped('Skipping until we have time to set up real tests with Travis secret storage.');

        // Arrange
        $vimeo = new Vimeo($this->clientId, $this->clientSecret);

        // Act
        $result = $vimeo->upload('./the_file_is_invalid');
    }

    /**
     * @expectedException Vimeo\Exceptions\VimeoUploadException
     */
    public function testUploadWithInvalidParamShouldReturnVimeoRequestException()
    {
        $this->markTestSkipped('Skipping until we have time to set up real tests with Travis secret storage.');

        // Arrange
        $vimeo = new Vimeo($this->clientId, $this->clientSecret);

        // Act
        $result = $vimeo->upload(__DIR__.'/../../composer.json', array('invalid_param'));
    }

    /**
     * @expectedException Vimeo\Exceptions\VimeoUploadException
     */
    public function testReplaceWithNonExistedFile()
    {
        $this->markTestSkipped('Skipping until we have time to set up real tests with Travis secret storage.');

        // Arrange
        $vimeo = new Vimeo($this->clientId, $this->clientSecret);

        // Act
        $result = $vimeo->replace('https://vimeo.com/241711006', './the_file_is_invalid');
    }

    /**
     * @expectedException Vimeo\Exceptions\VimeoUploadException
     */
    public function testUploadImageWithNonExistedFile()
    {
        $this->markTestSkipped('Skipping until we have time to set up real tests with Travis secret storage.');

        // Arrange
        $vimeo = new Vimeo($this->clientId, $this->clientSecret);

        // Act
        $result = $vimeo->uploadImage('https://vimeo.com/241711006', './the_file_is_invalid');
    }

    /**
     * @expectedException Vimeo\Exceptions\VimeoUploadException
     */
    public function testUploadTexttrackWithNonExistedFile()
    {
        $this->markTestSkipped('Skipping until we have time to set up real tests with Travis secret storage.');

        // Arrange
        $vimeo = new Vimeo($this->clientId, $this->clientSecret);

        // Act
        $result = $vimeo->uploadTexttrack('https://vimeo.com/241711006', './the_file_is_invalid', 'fake_track_type', 'zh_TW');
    }

    /**
     * @expectedException Vimeo\Exceptions\VimeoRequestException
     */
    public function testReplaceWithVideoUriShouldReturnVimeoRequestException()
    {
        $this->markTestSkipped('Skipping until we have time to set up real tests with Travis secret storage.');

        // Arrange
        $vimeo = new Vimeo($this->clientId, $this->clientSecret);

        // Act
        $result = $vimeo->replace('https://vimeo.com/241711006', __DIR__.'/../../composer.json');
    }

    /**
     * @expectedException Vimeo\Exceptions\VimeoRequestException
     */
    public function testUploadImageWithPictureUriShouldReturnVimeoRequestException()
    {
        $this->markTestSkipped('Skipping until we have time to set up real tests with Travis secret storage.');

        // Arrange
        $vimeo = new Vimeo($this->clientId, $this->clientSecret);

        // Act
        $result = $vimeo->uploadImage('https://vimeo.com/user59081751', __DIR__.'/../../composer.json');
    }

    /**
     * @expectedException Vimeo\Exceptions\VimeoRequestException
     */
    public function testUploadTexttrackWithPictureUriAndInvalidParamShouldReturnVimeoRequestException()
    {
        $this->markTestSkipped('Skipping until we have time to set up real tests with Travis secret storage.');

        // Arrange
        $vimeo = new Vimeo($this->clientId, $this->clientSecret);

        // Act
        $result = $vimeo->uploadTexttrack('https://vimeo.com/user59081751', __DIR__.'/../../composer.json', 'fake_track_type', 'zh_TW');
    }
}
