<?php

namespace App\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface;

/**
 * Logstash Client.
 */
interface MediaWikiInterface {

		/**
		 * Retrieve Edit Counts.
		 *
		 * @param string $domain Domain to execute the query on.
		 * @param array $userNames to retrieve edit count for.
		 *
		 * @return PromiseInterface
		 */
		public function editCount( $domain, array $userNames ) : PromiseInterface;
}
