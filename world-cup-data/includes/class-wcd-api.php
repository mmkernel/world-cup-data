<?php
/**
 * football-data.org API client and transient cache handling.
 *
 * @package WorldCupData
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles all communication with football-data.org.
 */
class WCD_API {

	const MATCHES_TRANSIENT   = 'wcd_matches_cache';
	const STANDINGS_TRANSIENT = 'wcd_standings_cache';

	/**
	 * Returns World Cup matches, using cache when available.
	 *
	 * @param bool $force_refresh Whether to bypass existing cache.
	 * @return array|WP_Error
	 */
	public function get_matches( $force_refresh = false ) {
		return $this->get_cached_response(
			self::MATCHES_TRANSIENT,
			'/competitions/' . WCD_COMPETITION_CODE . '/matches',
			'matches',
			$force_refresh
		);
	}

	/**
	 * Returns cached World Cup matches without making an API request.
	 *
	 * @return array|false
	 */
	public function get_cached_matches() {
		return get_transient( self::MATCHES_TRANSIENT );
	}

	/**
	 * Returns World Cup standings, using cache when available.
	 *
	 * @param bool $force_refresh Whether to bypass existing cache.
	 * @return array|WP_Error
	 */
	public function get_standings( $force_refresh = false ) {
		return $this->get_cached_response(
			self::STANDINGS_TRANSIENT,
			'/competitions/' . WCD_COMPETITION_CODE . '/standings',
			'standings',
			$force_refresh
		);
	}

	/**
	 * Deletes all API transients used by the plugin.
	 */
	public function clear_cache() {
		delete_transient( self::MATCHES_TRANSIENT );
		delete_transient( self::STANDINGS_TRANSIENT );
	}

	/**
	 * Reads from transient cache or fetches fresh data from the API.
	 *
	 * @param string $transient_key Transient key.
	 * @param string $endpoint      API endpoint path.
	 * @param string $data_key      Expected top-level response key.
	 * @param bool   $force_refresh Whether to bypass existing cache.
	 * @return array|WP_Error
	 */
	private function get_cached_response( $transient_key, $endpoint, $data_key, $force_refresh ) {
		if ( ! $force_refresh ) {
			$cached = get_transient( $transient_key );

			if ( false !== $cached ) {
				return $cached;
			}
		}

		$response = $this->request( $endpoint, $data_key );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		set_transient( $transient_key, $response, $this->get_cache_duration_seconds() );

		return $response;
	}

	/**
	 * Performs an authenticated API request.
	 *
	 * @param string $endpoint API endpoint path.
	 * @param string $data_key Expected top-level response key.
	 * @return array|WP_Error
	 */
	private function request( $endpoint, $data_key ) {
		$token = trim( (string) get_option( 'wcd_api_token', '' ) );

		if ( '' === $token ) {
			return new WP_Error(
				'wcd_missing_token',
				__( 'World Cup Data API token is missing. Add it under Settings > World Cup Data.', 'world-cup-data' )
			);
		}

		$url      = WCD_API_BASE_URL . $endpoint;
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'X-Auth-Token' => $token,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'wcd_request_failed',
				sprintf(
					/* translators: %s: API error message. */
					__( 'Could not connect to football-data.org: %s', 'world-cup-data' ),
					$response->get_error_message()
				)
			);
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );

		if ( 429 === $status_code ) {
			return new WP_Error(
				'wcd_rate_limited',
				__( 'football-data.org rate limit reached. Please wait before refreshing World Cup data.', 'world-cup-data' )
			);
		}

		if ( $status_code < 200 || $status_code >= 300 ) {
			return new WP_Error(
				'wcd_api_error',
				sprintf(
					/* translators: %d: HTTP status code. */
					__( 'football-data.org returned an API error. HTTP status: %d.', 'world-cup-data' ),
					$status_code
				)
			);
		}

		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'wcd_invalid_json',
				__( 'football-data.org returned an invalid response.', 'world-cup-data' )
			);
		}

		if ( empty( $data[ $data_key ] ) || ! is_array( $data[ $data_key ] ) ) {
			return new WP_Error(
				'wcd_empty_data',
				__( 'No World Cup data is currently available from football-data.org.', 'world-cup-data' )
			);
		}

		return $data;
	}

	/**
	 * Returns cache duration in seconds.
	 *
	 * @return int
	 */
	private function get_cache_duration_seconds() {
		$minutes = absint( get_option( 'wcd_cache_duration', 30 ) );

		if ( $minutes < 1 ) {
			$minutes = 30;
		}

		return $minutes * MINUTE_IN_SECONDS;
	}
}
