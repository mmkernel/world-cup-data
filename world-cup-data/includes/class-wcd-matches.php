<?php
/**
 * Match rendering helpers.
 *
 * @package WorldCupData
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders World Cup match cards.
 */
class WCD_Matches {

	/**
	 * Renders matches for a tab.
	 *
	 * @param array  $matches  Match data.
	 * @param string $tab      Tab key.
	 * @param array  $statuses Allowed statuses.
	 * @param int    $limit    Maximum cards to show. Zero means no limit.
	 * @return string
	 */
	public function render_tab_matches( $matches, $tab, $statuses, $limit = 0 ) {
		$filtered = $this->sort_matches( $this->filter_by_status( $matches, $statuses ), $tab );
		$limit    = absint( $limit );

		if ( empty( $filtered ) ) {
			return '<p class="wcd-empty" data-wcd-empty>' . esc_html( wcd_get_text( 'no_matches' ) ) . '</p>';
		}

		ob_start();
		?>
		<div class="wcd-match-list" data-wcd-match-list data-wcd-limit="<?php echo esc_attr( $limit ); ?>">
			<?php foreach ( $filtered as $index => $match ) : ?>
				<?php echo $this->render_card( $match, $tab, $limit > 0 && $index >= $limit ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endforeach; ?>
		</div>
		<p class="wcd-empty wcd-empty-filtered" data-wcd-filter-empty hidden><?php echo esc_html( wcd_get_text( 'no_team_matches' ) ); ?></p>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns unique participating teams sorted alphabetically.
	 *
	 * @param array $matches Match data.
	 * @return array
	 */
	public function get_teams( $matches ) {
		$teams = array();

		foreach ( $matches as $match ) {
			foreach ( array( 'homeTeam', 'awayTeam' ) as $side ) {
				if ( empty( $match[ $side ]['name'] ) ) {
					continue;
				}

				$name           = (string) $match[ $side ]['name'];
				$teams[ $name ] = $name;
			}
		}

		natcasesort( $teams );

		return array_values( $teams );
	}

	/**
	 * Renders today's matches from cached match data.
	 *
	 * @param array  $matches       Match data.
	 * @param bool   $show_finished Whether to include finished matches.
	 * @param int    $limit         Maximum cards to show. Zero means no limit.
	 * @param string $title         Optional section title.
	 * @param bool   $is_stale      Whether stale fallback data is being rendered.
	 * @return string
	 */
	public function render_today_matches( $matches, $show_finished, $limit, $title, $is_stale = false ) {
		$today_matches = $this->get_today_matches( $matches, $show_finished );

		if ( $limit > 0 ) {
			$today_matches = array_slice( $today_matches, 0, $limit );
		}

		ob_start();
		?>
		<div class="wcd-wrap wcd-today-wrap wcd-worldcup-today <?php echo $is_stale ? 'wcd-data-stale' : ''; ?>" data-wcd-worldcup-today>
			<?php if ( '' !== $title ) : ?>
				<h2 class="wcd-today-title"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>

			<?php if ( empty( $today_matches ) ) : ?>
				<p class="wcd-today-empty"><?php echo esc_html( wcd_get_text( 'no_today_matches' ) ); ?></p>
			<?php else : ?>
				<div class="wcd-match-list wcd-today-list">
					<?php foreach ( $today_matches as $match ) : ?>
						<?php echo $this->render_today_card( $match ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="wcd-credit">
				<?php echo esc_html__( 'Created by', 'world-cup-data' ); ?>
				<a href="<?php echo esc_url( 'https://masterymesh.com' ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'MasteryMesh', 'world-cup-data' ); ?></a>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Filters matches by football-data.org status.
	 *
	 * @param array $matches  Match data.
	 * @param array $statuses Allowed statuses.
	 * @return array
	 */
	private function filter_by_status( $matches, $statuses ) {
		$filtered = array();

		foreach ( $matches as $match ) {
			$status = $match['status'] ?? '';

			if ( in_array( $status, $statuses, true ) ) {
				$filtered[] = $match;
			}
		}

		return $filtered;
	}

	/**
	 * Sorts matches for predictable tab output.
	 *
	 * @param array  $matches Match data.
	 * @param string $tab     Tab key.
	 * @return array
	 */
	private function sort_matches( $matches, $tab ) {
		usort(
			$matches,
			function ( $first, $second ) use ( $tab ) {
				$first_time  = $this->get_match_sort_time( $first );
				$second_time = $this->get_match_sort_time( $second );

				if ( 'results' === $tab ) {
					return $second_time <=> $first_time;
				}

				return $first_time <=> $second_time;
			}
		);

		return $matches;
	}

	/**
	 * Returns a timestamp for sorting, pushing missing dates to the bottom.
	 *
	 * @param array $match Match data.
	 * @return int
	 */
	private function get_match_sort_time( $match ) {
		if ( empty( $match['utcDate'] ) ) {
			return PHP_INT_MAX;
		}

		$timestamp = strtotime( $match['utcDate'] );

		return false === $timestamp ? PHP_INT_MAX : $timestamp;
	}

	/**
	 * Returns today's matches based on the WordPress site timezone.
	 *
	 * @param array $matches       Match data.
	 * @param bool  $show_finished Whether to include finished matches.
	 * @return array
	 */
	private function get_today_matches( $matches, $show_finished ) {
		$timezone         = wp_timezone();
		$today            = wp_date( 'Y-m-d', time(), $timezone );
		$allowed_statuses = array( 'SCHEDULED', 'TIMED', 'IN_PLAY', 'PAUSED', 'LIVE' );

		if ( $show_finished ) {
			$allowed_statuses[] = 'FINISHED';
		}

		$today_matches = array();

		foreach ( $matches as $match ) {
			$status = $match['status'] ?? '';

			if ( ! in_array( $status, $allowed_statuses, true ) || empty( $match['utcDate'] ) ) {
				continue;
			}

			$timestamp = strtotime( $match['utcDate'] );

			if ( false === $timestamp || wp_date( 'Y-m-d', $timestamp, $timezone ) !== $today ) {
				continue;
			}

			$today_matches[] = $match;
		}

		return $this->sort_matches( $today_matches, 'upcoming' );
	}

	/**
	 * Renders one match card.
	 *
	 * @param array  $match  Match data.
	 * @param string $tab    Tab key.
	 * @param bool   $hidden Whether the card is initially hidden by a display limit.
	 * @return string
	 */
	private function render_card( $match, $tab, $hidden = false ) {
		$home_data   = $match['homeTeam'] ?? array();
		$away_data   = $match['awayTeam'] ?? array();
		$home_team   = $home_data['name'] ?? wcd_get_text( 'home_team' );
		$away_team   = $away_data['name'] ?? wcd_get_text( 'away_team' );
		$status      = $match['status'] ?? '';
		$stage       = $this->format_stage( $match );
		$date_parts  = $this->format_match_datetime( $match );
		$score       = $this->format_score( $match );
		$is_finished = 'FINISHED' === $status;
		$team_filter = strtolower( $home_team . '|' . $away_team );

		ob_start();
		?>
		<article class="wcd-match-card wcd-card-<?php echo esc_attr( $tab ); ?>" data-wcd-match-card data-teams="<?php echo esc_attr( $team_filter ); ?>" <?php echo $hidden ? 'hidden data-wcd-limit-hidden="true"' : ''; ?>>
			<div class="wcd-card-teams <?php echo $is_finished ? 'wcd-card-teams-scored' : 'wcd-card-teams-upcoming'; ?>">
				<div class="wcd-card-team wcd-card-team-home">
					<?php echo wp_kses_post( wcd_render_team_flag( $home_data ) ); ?>
					<span><?php echo esc_html( $home_team ); ?></span>
				</div>

				<?php if ( $is_finished ) : ?>
					<div class="wcd-card-score"><?php echo esc_html( $score ); ?></div>
				<?php else : ?>
					<div class="wcd-card-versus"><?php echo esc_html( wcd_get_text( 'versus' ) ); ?></div>
				<?php endif; ?>

				<div class="wcd-card-team wcd-card-team-away">
					<?php echo wp_kses_post( wcd_render_team_flag( $away_data ) ); ?>
					<span><?php echo esc_html( $away_team ); ?></span>
				</div>
			</div>

			<div class="wcd-card-meta">
				<span><?php echo esc_html( $date_parts['date'] ); ?></span>
				<span><?php echo esc_html( $date_parts['time'] ); ?></span>
				<?php if ( '' !== $stage ) : ?>
					<span><?php echo esc_html( $stage ); ?></span>
				<?php endif; ?>
				<?php if ( 'SCHEDULED' !== $status ) : ?>
					<span><?php echo esc_html( wcd_get_text( 'status' ) . ': ' . $this->format_status( $status ) ); ?></span>
				<?php endif; ?>
			</div>
		</article>
		<?php
		return ob_get_clean();
	}

	/**
	 * Renders one compact today match card.
	 *
	 * @param array $match Match data.
	 * @return string
	 */
	private function render_today_card( $match ) {
		$home_data  = $match['homeTeam'] ?? array();
		$away_data  = $match['awayTeam'] ?? array();
		$home_team  = $home_data['name'] ?? wcd_get_text( 'home_team' );
		$away_team  = $away_data['name'] ?? wcd_get_text( 'away_team' );
		$status     = $match['status'] ?? '';
		$show_score = in_array( $status, array( 'IN_PLAY', 'PAUSED', 'LIVE', 'FINISHED' ), true );
		$time       = $this->format_today_time( $match );
		$score      = $this->format_score( $match );
		?>
		<article class="wcd-match-card wcd-today-card">
			<div class="wcd-card-teams wcd-today-teams">
				<span class="wcd-card-team wcd-card-team-home wcd-today-team wcd-today-home-team">
					<?php echo wp_kses_post( wcd_render_team_flag( $home_data ) ); ?>
					<span><?php echo esc_html( $home_team ); ?></span>
				</span>

				<span class="wcd-today-center"><?php echo esc_html( $show_score ? $score : wcd_get_text( 'versus' ) ); ?></span>

				<span class="wcd-card-team wcd-card-team-away wcd-today-team wcd-today-away-team">
					<?php echo wp_kses_post( wcd_render_team_flag( $away_data ) ); ?>
					<span><?php echo esc_html( $away_team ); ?></span>
				</span>
			</div>

			<div class="wcd-card-meta wcd-today-meta">
				<span class="wcd-today-time"><?php echo esc_html( $time ); ?></span>
				<span class="wcd-today-status"><?php echo esc_html( $this->format_status( $status ) ); ?></span>
			</div>
		</article>
		<?php
		return ob_get_clean();
	}

	/**
	 * Formats today's kickoff time using the WordPress site timezone.
	 *
	 * @param array $match Match data.
	 * @return string
	 */
	private function format_today_time( $match ) {
		if ( empty( $match['utcDate'] ) ) {
			return wcd_get_text( 'tba' );
		}

		$timestamp = strtotime( $match['utcDate'] );

		if ( false === $timestamp ) {
			return wcd_get_text( 'tba' );
		}

		return wp_date( get_option( 'time_format' ), $timestamp, wp_timezone() );
	}

	/**
	 * Formats match date and time.
	 *
	 * @param array $match Match data.
	 * @return array
	 */
	private function format_match_datetime( $match ) {
		if ( empty( $match['utcDate'] ) ) {
			return array(
				'date' => wcd_get_text( 'date_tba' ),
				'time' => wcd_get_text( 'tba' ),
			);
		}

		$timestamp = strtotime( $match['utcDate'] );

		if ( false === $timestamp ) {
			return array(
				'date' => wcd_get_text( 'date_tba' ),
				'time' => wcd_get_text( 'tba' ),
			);
		}

		$timezone = wcd_get_display_timezone();

		return array(
			'date' => wp_date( 'j F Y', $timestamp, $timezone ),
			'time' => wp_date( get_option( 'time_format' ), $timestamp, $timezone ),
		);
	}

	/**
	 * Formats stage text.
	 *
	 * @param array $match Match data.
	 * @return string
	 */
	private function format_stage( $match ) {
		$stage = $match['stage'] ?? '';

		if ( '' === $stage ) {
			return '';
		}

		return ucwords( strtolower( str_replace( '_', ' ', $stage ) ) );
	}

	/**
	 * Formats match status.
	 *
	 * @param string $status Raw status.
	 * @return string
	 */
	private function format_status( $status ) {
		if ( '' === $status ) {
			return '';
		}

		return ucwords( strtolower( str_replace( '_', ' ', $status ) ) );
	}

	/**
	 * Formats score.
	 *
	 * @param array $match Match data.
	 * @return string
	 */
	private function format_score( $match ) {
		$home_score = $match['score']['fullTime']['home'] ?? null;
		$away_score = $match['score']['fullTime']['away'] ?? null;

		if ( null === $home_score || null === $away_score ) {
			return '-';
		}

		return absint( $home_score ) . ' : ' . absint( $away_score );
	}
}
