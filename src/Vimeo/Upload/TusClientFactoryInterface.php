<?php

namespace Vimeo\Upload;

interface TusClientFactoryInterface
{
	/**
	 * Given a base URL, return a TusClient.
	 */
	public function fromBaseUrl(string $baseUrl) : TusClient;
}
