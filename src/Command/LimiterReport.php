<?php

namespace App\Command;

use App\Client\LogstashInterface;
use App\Client\MediaWikiInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LimiterReport extends Command {

	/**
	 * @var LogstashInterface
	 */
	protected $logstash;

	/**
	 * @var MediaWikiInterface
	 */
	protected $mediaWiki;

	/**
	 * Limiter Report
	 *
	 * @param LogstashInterface $logstash Logstash Client
	 * @param MediaWikiInterface $mediaWiki MediaWiki Client
	 */
	public function __construct( LogstashInterface $logstash, MediaWikiInterface $mediaWiki ) {
		$this->logstash = $logstash;
		$this->mediaWiki = $mediaWiki;

		parent::__construct();
	}

	/**
	 * {@inheritdoc}
	 */
	protected function configure() {
		$this->setName( 'app:report:limiter' )
				->setDescription( 'Report of limiter trip' );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param InputInterface $input Input
	 * @param OutputInterface $output Output
	 */
	protected function execute( InputInterface $input, OutputInterface $output ) {
		// Get all of the search results.
		$result = [];
		$index = 0;
		$count = 10;
		while ( $count === 10 ) {
			// @TODO Make the 'wiki' and the limiter action configurable.
			$data = $this->logstash->search( 'wiki:plwiki message:"tripped plwiki:limiter:thanks"', $index )
				->wait();
			$count = count( $data );
			$result = array_merge( $result, $data );
			$index = $index + 10;
		}

		$result = array_map( function ( $item ) {
			return array_merge( $item, [
				'username' => preg_replace( "/^User '(.*)' .*$/u", '$1', $item['_source']['message'] )
			] );
		}, $result );

		$siteUsers = array_reduce( $result, function ( $carry, $item ) {
			$carry[$item['_source']['server']][] = $item['username'];
			return $carry;
		}, [] );

		// Ensure all the users are unique.
		$siteUsers = array_map( function ( $userNames ) {
			return array_unique( $userNames );
		}, $siteUsers );

		foreach ( $siteUsers as $site => $userNames ) {
			$editCounts[$site] = $this->mediaWiki->editCount( $site, $userNames );
		}

		$editCounts = \GuzzleHttp\Promise\all( $editCounts )->wait();

		$result = array_map( function ( $item ) use ( $editCounts ) {
			$user = reset( $editCounts[$item['_source']['server']] );
			while ( $user !== false ) {
					if ( $user['name'] === $item['username'] ) {
							break;
					};
					$user = next( $editCounts[$item['_source']['server']] );
			}

			return array_merge( $item, [
				'editcount' => $user['editcount'],
			] );
		}, $result );

		$data = array_reduce( $result, function ( $carry, $item ) {
			$date = new \DateTime( $item['_source']['@timestamp'] );
			$key = $date->format( 'Y-m-d' );
			if ( !isset( $carry[$key] ) ) {
				$carry[$key] = [
					'zero' => [],
					'more' => [],
				];
			}

			if ( $item['editcount'] === 0 ) {
				$carry[$key]['zero'][] = $item['username'];
			} else {
				$carry[$key]['more'][] = $item['username'];
			}
			return $carry;
		}, [] );

		$data = array_map( function ( $item ) {
			return [
				'zero' => count( array_unique( $item['zero'] ) ),
				'more' => count( array_unique( $item['more'] ) ),
			];
		}, $data );

		// Sort the array by date.
		ksort( $data );

		$rows = [];
		foreach ( $data as $date => $item ) {
			$rows[] = [
				$date,
				$item['zero'] + $item['more'],
				$item['zero'],
				$item['more'],
			];
		}

		$table = new Table( $output );
		$table->setHeaders( [
			'Date',
			'Number of Users',
			'Number of Users with Zero Edits',
			'Number of Users with One or More Edits',
		] );
		$table->setRows( $rows );
		$table->render();
	}
}
