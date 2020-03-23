<?php

namespace Vimeo\Upload;

final class TusClientFactory implements TusClientFactoryInterface
{

	public function fromBaseUrl(string $baseUrl) : TusClient
	{
		return new TusClient($baseUrl);
	}
}