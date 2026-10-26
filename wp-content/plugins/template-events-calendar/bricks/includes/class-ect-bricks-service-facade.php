<?php
/**
 * Static method delegation base for Bricks service facades.
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECT_Bricks_Service_Facade', false ) ) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	abstract class ECT_Bricks_Service_Facade {

		/** @var array<class-string,array<string,string>> */
		private static $delegates_by_class = array();

		/**
		 * Map service class names to the static methods they implement.
		 *
		 * @return array<string,array<int,string>>
		 */
		abstract protected static function delegate_map(): array;

		/** @return array<string,string> */
		private static function delegates(): array {
			$class = static::class;
			if ( isset( self::$delegates_by_class[ $class ] ) ) {
				return self::$delegates_by_class[ $class ];
			}

			$map = array();
			foreach ( static::delegate_map() as $service_class => $methods ) {
				foreach ( $methods as $method ) {
					$map[ $method ] = $service_class;
				}
			}

			self::$delegates_by_class[ $class ] = $map;
			return $map;
		}

		/**
		 * @param string           $name Method name.
		 * @param array<int,mixed> $args Call arguments.
		 * @return mixed
		 */
		public static function __callStatic( $name, $args ) {
			$map = static::delegates();
			if ( isset( $map[ $name ] ) ) {
				return forward_static_call_array( array( $map[ $name ], $name ), $args );
			}
			throw new BadMethodCallException( static::class . '::' . $name . ' is not defined.' );//phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
	}
}
