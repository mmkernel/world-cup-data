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
	 * @return string
	 */
	public function render_tab_matches( $matches, $tab, $statuses ) {
		$filtered = $this->sort_matches( $this->filter_by_status( $matches, $statuses ), $tab );

		if ( empty( $filtered ) ) {
			return '<p class="wcd-empty" data-wcd-empty>' . esc_html( wcd_get_text( 'no_matches' ) ) . '</p>';
		}

		ob_start();
		?>
		<div class="wcd-match-list">
			<?php foreach ( $filtered as $match ) : ?>
				<?php echo $this->render_card( $match, $tab ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
	 * Renders one match card.
	 *
	 * @param array  $match Match data.
	 * @param string $tab   Tab key.
	 * @return string
	 */
	private function render_card( $match, $tab ) {
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
		<article class="wcd-match-card wcd-card-<?php echo esc_attr( $tab ); ?>" data-wcd-match-card data-teams="<?php echo esc_attr( $team_filter ); ?>">
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
