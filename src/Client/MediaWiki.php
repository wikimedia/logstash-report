<?php

namespace App\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface;

/**
 * Logstash Client.
 */
class MediaWiki implements MediaWikiInterface {

		/**
		 * @var ClientInterface
		 */
		protected $client;

		/**
		 * @var DecoderInterface
		 */
		protected $decoder;

		/**
		 * Logstash Client
		 *
		 * @param ClientInterface $client Fully-configured Guzzle Client.
		 * @param DecoderInterface $decoder Symfony Decoder.
		 */
		public function __construct( ClientInterface $client, DecoderInterface $decoder ) {
			$this->client = $client;
			$this->decoder = $decoder;
		}

		/**
		 * Retrieve Edit Counts.
		 *
		 * @param string $domain Domain to execute the query on.
		 * @param array $userNames to retrieve edit count for.
		 *
		 * @return PromiseInterface
		 */
		public function editCount( $domain, array $userNames ) : PromiseInterface {
			return $this->client->requestAsync( 'GET', 'https://' . $domain . '/w/api.php', [
				'query' => [
					'action' => 'query',
					'list' => 'users',
					'format' => 'json',
					'formatversion' => 2,
					'usprop' => 'editcount',
					'ususers' => implode( '|', $userNames ),
				],
			] )->then( function ( ResponseInterface $response ) {
				$data = $this->decoder->decode( (string)$response->getBody(), 'json' );
				return $data['query']['users'] ?? [];
			} );
		}
}
