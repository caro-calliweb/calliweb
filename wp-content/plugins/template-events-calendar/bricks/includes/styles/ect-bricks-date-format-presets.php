<?php
/**
 * ECT_Bricks_Date_Format_Presets service (date + time format presets).
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECT_Bricks_Date_Format_Presets', false ) ) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	final class ECT_Bricks_Date_Format_Presets {

		public static function ect_bricks_date_php_format( $preset, $part = 'event_date' ) {
			unset( $part );
			$preset = (string) $preset;

			$legacy_aliases = array(
				'MD,YT' => 'MD,Y',
				'dFT'   => 'DF',
			);
			if ( isset( $legacy_aliases[ $preset ] ) ) {
				$preset = $legacy_aliases[ $preset ];
			}

			if ( in_array( $preset, array( '', 'default', 'full', 'sed', 'sedt' ), true ) ) {
				return (string) get_option( 'date_format' );
			}

			if ( in_array( $preset, array( 'day', 'dr', 'sdl' ), true ) ) {
				return null;
			}

			if ( $preset === 'custom' ) {
				return null;
			}

			$formats = self::ect_bricks_date_formats();

			return isset( $formats[ $preset ] ) ? $formats[ $preset ] : null;
		}

		public static function ect_bricks_date_formats() {
			return array(
				'MD,Y'  => 'M j, Y',
				'FD,Y'  => 'F j, Y',
				'DM'    => 'd/m/Y',
				'DML'   => 'j F l',
				'DF'    => 'j F',
				'MD'    => 'M j',
				'FD'    => 'F j',
				'jMl'   => 'j M l',
				'd.FY'  => 'd.m.Y',
				'd.F'   => 'd.m',
				'ldF'   => 'l j F',
				'Mdl'   => 'M j l',
				'd.Ml'  => 'd.m l',
				'D.j.F' => 'D, j. F',
			);
		}

		/**
		 * Fixed sample timestamps for builder format previews.
		 *
		 * @param bool $same_day When true, end shares the start calendar day (time previews).
		 * @return array{start:int,end:int}
		 */
		private static function ect_bricks_option_sample_ts( $same_day = false ) {
			static $samples = array();

			$key = $same_day ? 'same_day' : 'range';
			if ( isset( $samples[ $key ] ) ) {
				return $samples[ $key ];
			}

			$samples[ $key ] = array(
				'start' => (int) wp_date( 'U', strtotime( '2026-01-15 08:00:00' ) ),
				'end'   => (int) wp_date(
					'U',
					strtotime( $same_day ? '2026-01-15 17:00:00' : '2026-01-16 17:00:00' )
				),
			);

			return $samples[ $key ];
		}

		/**
		 * Localized preview string for a preset (same pipeline as frontend output).
		 *
		 * @param string $preset Preset key.
		 * @return string
		 */
		private static function ect_bricks_date_option_preview( $preset ) {
			$preset  = (string) $preset;
			$samples = self::ect_bricks_option_sample_ts();

			if ( $preset === 'custom' ) {
				return '';
			}

			if ( $preset === 'day' ) {
				return date_i18n( 'l', $samples['start'] );
			}

			if ( $preset === 'sdl' ) {
				$start = date_i18n( 'l', $samples['start'] );
				$end   = date_i18n( 'l', $samples['end'] );
				if ( $start === $end ) {
					return $start;
				}
				return $start . ' - ' . $end;
			}

			if ( $preset === 'dr' ) {
				$php = (string) get_option( 'date_format' );
				return date_i18n( $php, $samples['start'] ) . ' - ' . date_i18n( $php, $samples['end'] );
			}

			$php = self::ect_bricks_date_php_format( $preset, 'event_date' );
			if ( ! is_string( $php ) || $php === '' ) {
				return '';
			}

			if ( in_array( $preset, array( 'sed', 'sedt' ), true ) ) {
				if ( $preset === 'sedt' ) {
					$php = trim(
						(string) get_option( 'date_format' ) . ' ' . ECT_Bricks_Date_Formatter::ect_bricks_time_fmt_lower( (string) get_option( 'time_format' ) )
					);
				}
				return date_i18n( $php, $samples['start'] ) . ' - ' . date_i18n( $php, $samples['end'] );
			}

			return date_i18n( $php, $samples['start'] );
		}

		/**
		 * Builder select label: preset code + live localized preview.
		 *
		 * @param string $preset     Preset key.
		 * @param string $code_label Short preset code shown before the preview.
		 * @return string
		 */
		private static function ect_bricks_date_option_label( $preset, $code_label ) {
			$preview = self::ect_bricks_date_option_preview( $preset );
			if ( $preview === '' ) {
				return esc_html( $code_label );
			}

			return esc_html( $code_label . ' (' . $preview . ')' );
		}

		public static function ect_bricks_date_options() {
			$default_label = esc_html__( 'Default', 'template-events-calendar' );

			return array(
				''     => self::ect_bricks_date_option_label( '', $default_label ),
				'dr'   => self::ect_bricks_date_option_label( 'dr', esc_html__( 'Date range', 'template-events-calendar' ) ),
				'day'  => self::ect_bricks_date_option_label( 'day', esc_html__( 'Day', 'template-events-calendar' ) ),
				'sdl'  => self::ect_bricks_date_option_label( 'sdl', esc_html__( 'Day range', 'template-events-calendar' ) ),
				'MD,Y' => self::ect_bricks_date_option_label( 'MD,Y', 'Md,Y' ),
				'FD,Y'    => self::ect_bricks_date_option_label( 'FD,Y', 'Fd,Y' ),
				'DM'      => self::ect_bricks_date_option_label( 'DM', 'dM' ),
				'DML'     => self::ect_bricks_date_option_label( 'DML', 'dML' ),
				'DF'      => self::ect_bricks_date_option_label( 'DF', 'dF' ),
				'MD'      => self::ect_bricks_date_option_label( 'MD', 'Md' ),
				'FD'      => self::ect_bricks_date_option_label( 'FD', 'Fd' ),
				'full'    => self::ect_bricks_date_option_label( 'full', esc_html__( 'Full', 'template-events-calendar' ) ),
				'jMl'     => self::ect_bricks_date_option_label( 'jMl', 'jMl' ),
				'd.FY'    => self::ect_bricks_date_option_label( 'd.FY', 'd.FY' ),
				'd.F'     => self::ect_bricks_date_option_label( 'd.F', 'd.F' ),
				'ldF'     => self::ect_bricks_date_option_label( 'ldF', 'ldF' ),
				'Mdl'     => self::ect_bricks_date_option_label( 'Mdl', 'Mdl' ),
				'd.Ml'    => self::ect_bricks_date_option_label( 'd.Ml', 'd.Ml' ),
				'sed'     => self::ect_bricks_date_option_label( 'sed', 'SED' ),
				'sedt'    => self::ect_bricks_date_option_label( 'sedt', 'SEDT' ),
				'D.j.F'   => self::ect_bricks_date_option_label( 'D.j.F', 'D.,j. F' ),
				'custom'  => esc_html__( 'Custom…', 'template-events-calendar' ),
			);
		}

		/**
		 * PHP time format for a preset key, or null for custom/str.
		 *
		 * @param string $preset Preset key.
		 * @return string|null
		 */
		public static function ect_bricks_time_php_format( $preset ) {
			$preset = (string) $preset;

			if ( in_array( $preset, array( '', 'default' ), true ) ) {
				return (string) get_option( 'time_format' );
			}

			if ( in_array( $preset, array( 'custom', 'str', 'start', 'end' ), true ) ) {
				return null;
			}

			$formats = self::ect_bricks_time_formats();

			return isset( $formats[ $preset ] ) ? $formats[ $preset ] : null;
		}

		/**
		 * Time-only PHP format strings (no date tokens).
		 *
		 * @return array<string,string>
		 */
		public static function ect_bricks_time_formats() {
			return array(
				'12a'  => 'g:i a',
				'12h'  => 'h:i a',
				'24h'  => 'H:i',
				'24hs' => 'H:i:s',
			);
		}

		/**
		 * Localized preview string for a time preset.
		 *
		 * @param string $preset Preset key.
		 * @return string
		 */
		private static function ect_bricks_time_option_preview( $preset ) {
			$preset  = (string) $preset;
			$samples = self::ect_bricks_option_sample_ts( true );

			if ( $preset === 'custom' ) {
				return '';
			}

			if ( in_array( $preset, array( '', 'default' ), true ) ) {
				$php   = ECT_Bricks_Date_Formatter::ect_bricks_time_fmt_lower( (string) get_option( 'time_format' ) );
				$day   = date_i18n( 'l', $samples['start'] );
				$start = $php !== '' ? date_i18n( $php, $samples['start'] ) : '';
				$end   = $php !== '' ? date_i18n( $php, $samples['end'] ) : '';
				if ( $start === '' ) {
					return $day;
				}
				$time = ( $end === '' || $start === $end ) ? $start : $start . ' - ' . $end;

				return $day . ', ' . $time;
			}

			if ( $preset === 'str' ) {
				$php = ECT_Bricks_Date_Formatter::ect_bricks_time_fmt_lower( (string) get_option( 'time_format' ) );
				if ( $php === '' ) {
					return '';
				}
				$start = date_i18n( $php, $samples['start'] );
				$end   = date_i18n( $php, $samples['end'] );

				return $start . ' - ' . $end;
			}

			if ( in_array( $preset, array( 'start', 'end' ), true ) ) {
				$php = ECT_Bricks_Date_Formatter::ect_bricks_time_fmt_lower( (string) get_option( 'time_format' ) );
				if ( $php === '' ) {
					return '';
				}
				$ts = $preset === 'end' ? $samples['end'] : $samples['start'];

				return date_i18n( $php, $ts );
			}

			$php = self::ect_bricks_time_php_format( $preset );
			if ( ! is_string( $php ) || $php === '' ) {
				return '';
			}

			$php = ECT_Bricks_Date_Formatter::ect_bricks_time_fmt_lower( $php );

			return date_i18n( $php, $samples['start'] );
		}

		/**
		 * Builder select label: time preset code + live localized preview.
		 *
		 * @param string $preset     Preset key.
		 * @param string $code_label Short preset code shown before the preview.
		 * @return string
		 */
		private static function ect_bricks_time_option_label( $preset, $code_label ) {
			$preview = self::ect_bricks_time_option_preview( $preset );
			if ( $preview === '' ) {
				return esc_html( $code_label );
			}

			return esc_html( $code_label . ' (' . $preview . ')' );
		}

		/**
		 * Builder select options for time-only / time-range visibility modes.
		 *
		 * @return array<string,string>
		 */
		public static function ect_bricks_time_options() {
			$default_label = esc_html__( 'Default', 'template-events-calendar' );

			return array(
				''       => self::ect_bricks_time_option_label( '', $default_label ),
				'str'    => self::ect_bricks_time_option_label( 'str', esc_html__( 'Time range', 'template-events-calendar' ) ),
				'start'  => self::ect_bricks_time_option_label( 'start', esc_html__( 'Starting time', 'template-events-calendar' ) ),
				'end'    => self::ect_bricks_time_option_label( 'end', esc_html__( 'Ending time', 'template-events-calendar' ) ),
				'12a'    => self::ect_bricks_time_option_label( '12a', 'g:i a' ),
				'12h'    => self::ect_bricks_time_option_label( '12h', 'h:i a' ),
				'24h'    => self::ect_bricks_time_option_label( '24h', 'H:i' ),
				'24hs'   => self::ect_bricks_time_option_label( '24hs', 'H:i:s' ),
				'custom' => esc_html__( 'Custom…', 'template-events-calendar' ),
			);
		}
	}
}
