<?php

namespace App\Client;

use GuzzleHttp\Promise\PromiseInterface;

/**
 * Logstash Client.
 */
interface LogstashInterface {

	/**
	 * Perform a search.
	 *
	 * @param string $query Search Query.
	 *
	 * @return PromiseInterface
	 */
	public function search( $query ) : PromiseInterface;

}
