<?php
/**
 * football-data.org API client and local cache handling.
 *
 * @package WorldCupData
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles cached World Cup data and background API refreshes.
 */
class WCD_API {

	const MATCHES_TRANSIENT       = 'wcd_matches_cache';
	const STANDINGS_TRANSIENT     = 'wcd_standings_cache';
	const FETCH_LOCK_TRANSIENT    = 'wcd_fetch_lock';
	const LAST_MATCHES_OPTION     = 'wcd_last_successful_matches';
	const LAST_STANDINGS_OPTION   = 'wcd_last_successful_standings';
	const LAST_SUCCESS_OPTION     = 'wcd_last_successful_fetch_time';
	const LAST_ERROR_OPTION       = 'wcd_last_api_error';
	const DATA_STATUS_OPTION      = 'wcd_data_status';
	const CRON_HOOK               = 'wcd_refresh_data_event';
	const LOADING_MESSAGE         = 'Match data is currently loading. Please check again soon.';

	/**
	 * Returns locally stored World Cup matches.
	 *
	 * This method never performs a remote request.
	 *
	 * @return array|WP_Error
	 */
	public function get_matches() {
		return $this->get_local_response( self::MATCHES_TRANSIENT, self::LAST_MATCHES_OPTION );
	}

	/**
	 * Returns locally stored World Cup matches.
	 *
	 * This method never performs a remote request.
	 *
	 * @return array|false
	 */
	public function get_cached_matches() {
		$response = $this->get_matches();

		return is_wp_error( $response ) ? false : $response;
	}

	/**
	 * Returns locally stored World Cup standings.
	 *
	 * This method never performs a remote request.
	 *
	 * @return array|WP_Error
	 */
	public function get_standings() {
		return $this->get_local_response( self::STANDINGS_TRANSIENT, self::LAST_STANDINGS_OPTION );
	}

	/**
	 * Refreshes all World Cup data from football-data.org.
	 *
	 * This is intended for WP-Cron and explicit admin refreshes only.
	 *
	 * @param bool $force Deprecated. Request locking is always respected.
	 * @return bool
	 */
	public function refresh_data( $force = false ) {
		unset( $force );

		if ( ! $this->can_refresh_data() ) {
			wcd_debug_log( 'Blocked refresh_data() outside allowed cron/admin refresh context. current_filter=' . current_filter() );
			return false;
		}

		if ( get_transient( self::FETCH_LOCK_TRANSIENT ) ) {
			return false;
		}

		set_transient( self::FETCH_LOCK_TRANSIENT, 1, MINUTE_IN_SECONDS );

		$success = false;

		try {
			$matches = $this->request( '/competitions/' . WCD_COMPETITION_CODE . '/matches', 'matches' );

			if ( is_wp_error( $matches ) ) {
				$this->store_error( $matches );
			} else {
				$matches = $this->enrich_matches_with_goal_events( $matches );
				$this->store_successful_response( self::MATCHES_TRANSIENT, self::LAST_MATCHES_OPTION, $matches );
				$success = true;
			}

			$standings = $this->request( '/competitions/' . WCD_COMPETITION_CODE . '/standings', 'standings' );

			if ( is_wp_error( $standings ) ) {
				$this->store_error( $standings );
			} else {
				$this->store_successful_response( self::STANDINGS_TRANSIENT, self::LAST_STANDINGS_OPTION, $standings );
				$success = true;
			}

			if ( $success ) {
				update_option( self::LAST_SUCCESS_OPTION, time(), false );
				delete_option( self::LAST_ERROR_OPTION );
				update_option( self::DATA_STATUS_OPTION, 'fresh', false );
			}
		} finally {
			delete_transient( self::FETCH_LOCK_TRANSIENT );
		}

		return $success;
	}

	/**
	 * Adds scorer/minute events to finished matches when football-data.org provides match details.
	 *
	 * @param array $matches Matches response.
	 * @return array
	 */
	private function enrich_matches_with_goal_events( $matches ) {
		if ( empty( $matches['matches'] ) || ! is_array( $matches['matches'] ) ) {
			return $matches;
		}

		foreach ( $matches['matches'] as $index => $match ) {
			if ( ! $this->should_fetch_goal_events( $match ) ) {
				continue;
			}

			$details = $this->request( '/matches/' . absint( $match['id'] ) );

			if ( is_wp_error( $details ) || empty( $details['goals'] ) || ! is_array( $details['goals'] ) ) {
				continue;
			}

			$matches['matches'][ $index ]['goals'] = $details['goals'];
		}

		return $matches;
	}

	/**
	 * Returns whether a finished match needs goal-event enrichment.
	 *
	 * @param array $match Match data.
	 * @return bool
	 */
	private function should_fetch_goal_events( $match ) {
		if ( empty( $match['id'] ) || 'FINISHED' !== ( $match['status'] ?? '' ) ) {
			return false;
		}

		if ( ! empty( $match['goals'] ) && is_array( $match['goals'] ) ) {
			return false;
		}

		$home_score = $match['score']['fullTime']['home'] ?? 0;
		$away_score = $match['score']['fullTime']['away'] ?? 0;

		return ( absint( $home_score ) + absint( $away_score ) ) > 0;
	}

	/**
	 * Deletes fresh transients while preserving last successful fallback data.
	 */
	public function clear_cache() {
		delete_transient( self::MATCHES_TRANSIENT );
		delete_transient( self::STANDINGS_TRANSIENT );
		delete_transient( self::FETCH_LOCK_TRANSIENT );
		update_option( self::DATA_STATUS_OPTION, 'stale', false );
	}

	/**
	 * Returns the last successful fetch timestamp.
	 *
	 * @return int
	 */
	public function get_last_success_time() {
		return absint( get_option( self::LAST_SUCCESS_OPTION, 0 ) );
	}

	/**
	 * Returns the last stored API error for admin display.
	 *
	 * @return string
	 */
	public function get_last_error() {
		return (string) get_option( self::LAST_ERROR_OPTION, '' );
	}

	/**
	 * Returns cache duration in seconds.
	 *
	 * @return int
	 */
	public function get_cache_duration_seconds() {
		$minutes = absint( get_option( 'wcd_cache_duration', 30 ) );

		if ( $minutes < 1 ) {
			$minutes = 30;
		}

		return $minutes * MINUTE_IN_SECONDS;
	}

	/**
	 * Reads fresh transient data or stale last-successful data.
	 *
	 * @param string $transient_key Transient key.
	 * @param string $option_key    Last successful option key.
	 * @return array|WP_Error
	 */
	private function get_local_response( $transient_key, $option_key ) {
		$cached = get_transient( $transient_key );

		if ( false !== $cached && is_array( $cached ) ) {
			$cached['__wcd_cache_status'] = 'fresh';
			return $cached;
		}

		$last_successful = get_option( $option_key, false );

		if ( is_array( $last_successful ) ) {
			$last_successful['__wcd_cache_status'] = 'stale';
			return $last_successful;
		}

		return new WP_Error( 'wcd_data_loading', self::LOADING_MESSAGE );
	}

	/**
	 * Stores fresh cache and last successful fallback data.
	 *
	 * @param string $transient_key Transient key.
	 * @param string $option_key    Last successful option key.
	 * @param array  $data          API response data.
	 */
	private function store_successful_response( $transient_key, $option_key, $data ) {
		unset( $data['__wcd_cache_status'] );

		set_transient( $transient_key, $data, $this->get_cache_duration_seconds() );
		update_option( $option_key, $data, false );
	}

	/**
	 * Stores a sanitized API error for admins.
	 *
	 * @param WP_Error $error Error object.
	 */
	private function store_error( WP_Error $error ) {
		update_option( self::LAST_ERROR_OPTION, $error->get_error_message(), false );
	}

	/**
	 * Returns whether remote refreshes are allowed in the current request.
	 *
	 * @return bool
	 */
	private function can_refresh_data() {
		if ( wp_doing_cron() && self::CRON_HOOK === current_filter() ) {
			return true;
		}

		return is_admin() && 'admin_post_wcd_refresh_data' === current_filter();
	}

	/**
	 * Performs an authenticated API request.
	 *
	 * @param string $endpoint API endpoint path.
	 * @param string $data_key Expected top-level response key. Empty means return the decoded response.
	 * @return array|WP_Error
	 */
	private function request( $endpoint, $data_key = '' ) {
		$started = microtime( true );
		wcd_debug_log( 'External request start: wp_remote_get ' . WCD_API_BASE_URL . $endpoint . ' current_filter=' . current_filter() );

		$token = trim( (string) get_option( 'wcd_api_token', '' ) );

		if ( '' === $token ) {
			return new WP_Error(
				'wcd_missing_token',
				__( 'World Cup Data API token is missing. Add it under Settings > World Cup Data.', 'world-cup-data' )
			);
		}

		$response = wp_remote_get(
			WCD_API_BASE_URL . $endpoint,
			array(
				'timeout' => 5,
				'headers' => array(
					'X-Auth-Token' => $token,
				),
			)
		);

		wcd_debug_log( sprintf( 'External request end: wp_remote_get %s duration=%.4fs', WCD_API_BASE_URL . $endpoint, microtime( true ) - $started ) );

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
				__( 'football-data.org rate limit reached. Existing cached data was kept.', 'world-cup-data' )
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

		if ( '' === trim( (string) $body ) ) {
			return new WP_Error(
				'wcd_empty_response',
				__( 'football-data.org returned an empty response.', 'world-cup-data' )
			);
		}

		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'wcd_invalid_json',
				__( 'football-data.org returned an invalid JSON response.', 'world-cup-data' )
			);
		}

		if ( '' === $data_key ) {
			return $data;
		}

		if ( empty( $data[ $data_key ] ) || ! is_array( $data[ $data_key ] ) ) {
			return new WP_Error(
				'wcd_empty_data',
				__( 'No World Cup data is currently available from football-data.org.', 'world-cup-data' )
			);
		}

		return $data;
	}
}
