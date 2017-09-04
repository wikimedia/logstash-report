<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class Kernel extends BaseKernel {

		use MicroKernelTrait;

		/**
		 * Config Extensions
		 */
		private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

		/**
		 * {@inheritdoc}
		 *
		 * @return string
		 */
		public function getCacheDir() : string {
				return dirname( __DIR__ ) . '/var/cache/' . $this->environment;
		}

		/**
		 * {@inheritdoc}
		 *
		 * @return string
		 */
		public function getLogDir() : string {
				return dirname( __DIR__ ) . '/var/logs';
		}

		/**
		 * {@inheritdoc}
		 *
		 * @return iterable
		 */
		public function registerBundles() : iterable {
				$contents = require dirname( __DIR__ ) . '/config/bundles.php';
				foreach ( $contents as $class => $envs ) {
						if ( isset( $envs['all'] ) || isset( $envs[$this->environment] ) ) {
								yield new $class();
						}
				}
		}

		/**
		 * {@inheritdoc}
		 *
		 * @param ContainerBuilder $container Container Builder
		 * @param LoaderInterface $loader Loader
		 *
		 * @return void
		 */
		protected function configureContainer(
			ContainerBuilder $container,
			LoaderInterface $loader
		) : void {
				$confDir = dirname( __DIR__ ) . '/config';
				$loader->load( $confDir . '/packages/*' . self::CONFIG_EXTS, 'glob' );
				if ( is_dir( $confDir . '/packages/' . $this->environment ) ) {
						$loader->load(
							$confDir . '/packages/' . $this->environment . '/**/*' . self::CONFIG_EXTS,
							'glob'
						);
				}
				$loader->load( $confDir . '/services' . self::CONFIG_EXTS, 'glob' );
		}

		/**
		 * {@inheritdoc}
		 *
		 * @param RouteCollectionBuilder $routes Routes
		 *
		 * @return void
		 */
		protected function configureRoutes( RouteCollectionBuilder $routes ) : void {
				$confDir = dirname( __DIR__ ) . '/config';
				if ( is_dir( $confDir . '/routing/' ) ) {
						$routes->import(
							$confDir . '/routing/*' . self::CONFIG_EXTS,
							'/',
							'glob'
						);
				}
				if ( is_dir( $confDir . '/routing/' . $this->environment ) ) {
						$routes->import(
							$confDir . '/routing/' . $this->environment . '/**/*'.self::CONFIG_EXTS,
							'/',
							'glob'
						);
				}
				$routes->import( $confDir . '/routing' . self::CONFIG_EXTS, '/', 'glob' );
		}
}
