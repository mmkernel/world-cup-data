<?php
/**
 * Frontend shortcode rendering.
 *
 * @package WorldCupData
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders World Cup shortcodes.
 */
class WCD_Shortcodes {

	/**
	 * API client.
	 *
	 * @var WCD_API
	 */
	private $api;

	/**
	 * Constructor.
	 *
	 * @param WCD_API $api API client.
	 */
	public function __construct( WCD_API $api ) {
		$this->api = $api;

		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_shortcode( 'worldcup_matches', array( $this, 'render_matches_shortcode' ) );
		add_shortcode( 'worldcup_results', array( $this, 'render_results_shortcode' ) );
		add_shortcode( 'worldcup_standings', array( $this, 'render_standings_shortcode' ) );
	}

	/**
	 * Registers frontend CSS. It is enqueued only when a shortcode renders.
	 */
	public function register_assets() {
		wp_register_style(
			'wcd-world-cup-data',
			WCD_PLUGIN_URL . 'assets/css/world-cup-data.css',
			array(),
			WCD_VERSION
		);
	}

	/**
	 * Renders [worldcup_matches].
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_matches_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'status' => 'all',
				'limit'  => 20,
			),
			$atts,
			'worldcup_matches'
		);

		return $this->render_matches( $atts['status'], absint( $atts['limit'] ), false );
	}

	/**
	 * Renders [worldcup_results].
	 *
	 * @return string
	 */
	public function render_results_shortcode( $atts = array() ) {
		unset( $atts );

		return $this->render_matches( 'FINISHED', 20, true );
	}

	/**
	 * Renders [worldcup_standings].
	 *
	 * @return string
	 */
	public function render_standings_shortcode() {
		wp_enqueue_style( 'wcd-world-cup-data' );

		$data = $this->api->get_standings();

		if ( is_wp_error( $data ) ) {
			return $this->render_notice( $data->get_error_message() );
		}

		if ( empty( $data['standings'] ) || ! is_array( $data['standings'] ) ) {
			return $this->render_notice( __( 'No World Cup standings are available yet.', 'world-cup-data' ) );
		}

		ob_start();
		?>
		<div class="wcd-wrap wcd-standings">
			<?php foreach ( $data['standings'] as $standing ) : ?>
				<?php
				if ( empty( $standing['table'] ) || ! is_array( $standing['table'] ) ) {
					continue;
				}

				$group_title = ! empty( $standing['group'] ) ? $standing['group'] : ( $standing['stage'] ?? __( 'Standings', 'world-cup-data' ) );
				?>
				<section class="wcd-standings-group">
					<h3 class="wcd-group-title"><?php echo esc_html( $group_title ); ?></h3>
					<div class="wcd-table-scroll">
						<table class="wcd-table wcd-standings-table">
							<thead>
								<tr>
									<th><?php echo esc_html__( 'Pos', 'world-cup-data' ); ?></th>
									<th><?php echo esc_html__( 'Team', 'world-cup-data' ); ?></th>
									<th><?php echo esc_html__( 'P', 'world-cup-data' ); ?></th>
									<th><?php echo esc_html__( 'W', 'world-cup-data' ); ?></th>
									<th><?php echo esc_html__( 'D', 'world-cup-data' ); ?></th>
									<th><?php echo esc_html__( 'L', 'world-cup-data' ); ?></th>
									<th><?php echo esc_html__( 'GF', 'world-cup-data' ); ?></th>
									<th><?php echo esc_html__( 'GA', 'world-cup-data' ); ?></th>
									<th><?php echo esc_html__( 'GD', 'world-cup-data' ); ?></th>
									<th><?php echo esc_html__( 'Pts', 'world-cup-data' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $standing['table'] as $row ) : ?>
									<tr>
										<td><?php echo esc_html( $row['position'] ?? '' ); ?></td>
										<td class="wcd-team-cell"><?php echo esc_html( $row['team']['name'] ?? '' ); ?></td>
										<td><?php echo esc_html( $row['playedGames'] ?? '0' ); ?></td>
										<td><?php echo esc_html( $row['won'] ?? '0' ); ?></td>
										<td><?php echo esc_html( $row['draw'] ?? '0' ); ?></td>
										<td><?php echo esc_html( $row['lost'] ?? '0' ); ?></td>
										<td><?php echo esc_html( $row['goalsFor'] ?? '0' ); ?></td>
										<td><?php echo esc_html( $row['goalsAgainst'] ?? '0' ); ?></td>
										<td><?php echo esc_html( $row['goalDifference'] ?? '0' ); ?></td>
										<td><strong><?php echo esc_html( $row['points'] ?? '0' ); ?></strong></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</section>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Renders matches with filtering and grouping.
	 *
	 * @param string $status       Match status filter.
	 * @param int    $limit        Number of matches to show.
	 * @param bool   $results_view Whether this is the results shortcode.
	 * @return string
	 */
	private function render_matches( $status, $limit, $results_view ) {
		wp_enqueue_style( 'wcd-world-cup-data' );

		$allowed_statuses = array( 'SCHEDULED', 'FINISHED', 'LIVE', 'all' );
		$status           = strtoupper( sanitize_text_field( $status ) );

		if ( 'ALL' === $status ) {
			$status = 'all';
		}

		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			$status = 'all';
		}

		$limit = $limit > 0 ? $limit : 20;
		$data  = $this->api->get_matches();

		if ( is_wp_error( $data ) ) {
			return $this->render_notice( $data->get_error_message() );
		}

		$matches = $this->filter_matches( $data['matches'] ?? array(), $status, $limit );

		if ( empty( $matches ) ) {
			return $this->render_notice( __( 'No World Cup matches found for this view.', 'world-cup-data' ) );
		}

		$grouped = $this->group_matches_by_date( $matches );

		ob_start();
		?>
		<div class="wcd-wrap <?php echo $results_view ? 'wcd-results' : 'wcd-matches'; ?>">
			<?php foreach ( $grouped as $date_label => $date_matches ) : ?>
				<section class="wcd-match-day">
					<h3 class="wcd-date-title"><?php echo esc_html( $date_label ); ?></h3>
					<div class="wcd-match-list">
						<?php foreach ( $date_matches as $match ) : ?>
							<?php $this->render_match_card( $match ); ?>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Filters matches by status and limit.
	 *
	 * @param array  $matches Match data.
	 * @param string $status  Match status.
	 * @param int    $limit   Number of matches.
	 * @return array
	 */
	private function filter_matches( $matches, $status, $limit ) {
		$filtered = array();

		foreach ( $matches as $match ) {
			$match_status = $match['status'] ?? '';

			if ( 'all' !== $status && $match_status !== $status ) {
				continue;
			}

			$filtered[] = $match;

			if ( count( $filtered ) >= $limit ) {
				break;
			}
		}

		return $filtered;
	}

	/**
	 * Groups matches by localized date.
	 *
	 * @param array $matches Match data.
	 * @return array
	 */
	private function group_matches_by_date( $matches ) {
		$grouped = array();

		foreach ( $matches as $match ) {
			$timestamp = $this->get_match_timestamp( $match );
			$date_key  = $timestamp ? wp_date( 'F j, Y', $timestamp ) : __( 'Date TBA', 'world-cup-data' );

			if ( ! isset( $grouped[ $date_key ] ) ) {
				$grouped[ $date_key ] = array();
			}

			$grouped[ $date_key ][] = $match;
		}

		return $grouped;
	}

	/**
	 * Renders one match card.
	 *
	 * @param array $match Match data.
	 */
	private function render_match_card( $match ) {
		$timestamp = $this->get_match_timestamp( $match );
		$time      = $timestamp ? wp_date( get_option( 'time_format' ), $timestamp ) : __( 'TBA', 'world-cup-data' );
		$status    = $match['status'] ?? '';
		$home_team = $match['homeTeam']['name'] ?? __( 'Home Team', 'world-cup-data' );
		$away_team = $match['awayTeam']['name'] ?? __( 'Away Team', 'world-cup-data' );
		$score     = $this->format_score( $match );
		?>
		<article class="wcd-match-card">
			<div class="wcd-match-meta">
				<span class="wcd-match-time"><?php echo esc_html( $time ); ?></span>
				<span class="wcd-match-status wcd-status-<?php echo esc_attr( strtolower( $status ) ); ?>"><?php echo esc_html( $status ); ?></span>
			</div>
			<div class="wcd-match-teams">
				<span class="wcd-team wcd-home-team"><?php echo esc_html( $home_team ); ?></span>
				<span class="wcd-score"><?php echo esc_html( $score ); ?></span>
				<span class="wcd-team wcd-away-team"><?php echo esc_html( $away_team ); ?></span>
			</div>
		</article>
		<?php
	}

	/**
	 * Formats final or current score.
	 *
	 * @param array $match Match data.
	 * @return string
	 */
	private function format_score( $match ) {
		$home_score = $match['score']['fullTime']['home'] ?? null;
		$away_score = $match['score']['fullTime']['away'] ?? null;

		if ( null === $home_score || null === $away_score ) {
			$home_score = $match['score']['halfTime']['home'] ?? null;
			$away_score = $match['score']['halfTime']['away'] ?? null;
		}

		if ( null === $home_score || null === $away_score ) {
			return '-';
		}

		return absint( $home_score ) . ' - ' . absint( $away_score );
	}

	/**
	 * Converts UTC match date to a timestamp.
	 *
	 * @param array $match Match data.
	 * @return int|null
	 */
	private function get_match_timestamp( $match ) {
		if ( empty( $match['utcDate'] ) ) {
			return null;
		}

		$timestamp = strtotime( $match['utcDate'] );

		return false === $timestamp ? null : $timestamp;
	}

	/**
	 * Renders a frontend notice.
	 *
	 * @param string $message Notice text.
	 * @return string
	 */
	private function render_notice( $message ) {
		wp_enqueue_style( 'wcd-world-cup-data' );

		return '<div class="wcd-wrap"><p class="wcd-notice">' . esc_html( $message ) . '</p></div>';
	}
}
