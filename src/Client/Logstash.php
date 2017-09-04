<?php

namespace App\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface;

/**
 * Logstash Client.
 */
class Logstash implements LogstashInterface {

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
	 * Perform a search.
	 *
	 * @param string $query Search Query.
	 * @param int $from Start from index
	 *
	 * @return PromiseInterface
	 */
	public function search( $query, $from = 0 ) : PromiseInterface {
		return $this->client->requestAsync( 'GET', '_search', [
			'query' => [
				'q' => $query,
				'default_operator' => 'AND',
				'from' => $from,
			],
		] )->then( function ( ResponseInterface $response ) {
			$data = $this->decoder->decode( (string)$response->getBody(), 'json' );
			return $data['hits']['hits'] ?? [];
		} );
	}

}
