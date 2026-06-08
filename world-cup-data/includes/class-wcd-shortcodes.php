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
			return $this->render_notice( $this->get_text( 'no_standings' ) );
		}

		ob_start();
		?>
		<div class="wcd-wrap wcd-standings">
			<?php foreach ( $data['standings'] as $standing ) : ?>
				<?php
				if ( empty( $standing['table'] ) || ! is_array( $standing['table'] ) ) {
					continue;
				}

				$group_title = ! empty( $standing['group'] ) ? $standing['group'] : ( $standing['stage'] ?? $this->get_text( 'standings' ) );
				?>
				<section class="wcd-standings-group">
					<h3 class="wcd-group-title"><?php echo esc_html( $group_title ); ?></h3>
					<div class="wcd-table-scroll">
						<table class="wcd-table wcd-standings-table">
							<thead>
								<tr>
									<th><?php echo esc_html( $this->get_text( 'position_short' ) ); ?></th>
									<th><?php echo esc_html( $this->get_text( 'team' ) ); ?></th>
									<th><?php echo esc_html( $this->get_text( 'played_short' ) ); ?></th>
									<th><?php echo esc_html( $this->get_text( 'won_short' ) ); ?></th>
									<th><?php echo esc_html( $this->get_text( 'draw_short' ) ); ?></th>
									<th><?php echo esc_html( $this->get_text( 'lost_short' ) ); ?></th>
									<th><?php echo esc_html( $this->get_text( 'goals_for_short' ) ); ?></th>
									<th><?php echo esc_html( $this->get_text( 'goals_against_short' ) ); ?></th>
									<th><?php echo esc_html( $this->get_text( 'goal_difference_short' ) ); ?></th>
									<th><?php echo esc_html( $this->get_text( 'points_short' ) ); ?></th>
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
			return $this->render_notice( $this->get_text( 'no_matches' ) );
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
			$date_key  = $timestamp ? wp_date( 'F j, Y', $timestamp, $this->get_display_timezone() ) : $this->get_text( 'date_tba' );

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
		$time      = $timestamp ? wp_date( get_option( 'time_format' ), $timestamp, $this->get_display_timezone() ) : $this->get_text( 'tba' );
		$status    = $match['status'] ?? '';
		$home_team = $match['homeTeam']['name'] ?? $this->get_text( 'home_team' );
		$away_team = $match['awayTeam']['name'] ?? $this->get_text( 'away_team' );
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
	 * Returns the configured display timezone.
	 *
	 * @return DateTimeZone
	 */
	private function get_display_timezone() {
		$timezone = (string) get_option( 'wcd_timezone', wp_timezone_string() );

		try {
			return new DateTimeZone( $timezone );
		} catch ( Exception $exception ) {
			return wp_timezone();
		}
	}

	/**
	 * Returns frontend text for the selected plugin language.
	 *
	 * @param string $key Text key.
	 * @return string
	 */
	private function get_text( $key ) {
		$language     = (string) get_option( 'wcd_language', 'en' );
		$translations = array(
			'en' => array(
				'no_matches'             => 'No World Cup matches found for this view.',
				'no_standings'           => 'No World Cup standings are available yet.',
				'standings'              => 'Standings',
				'position_short'         => 'Pos',
				'team'                   => 'Team',
				'played_short'           => 'P',
				'won_short'              => 'W',
				'draw_short'             => 'D',
				'lost_short'             => 'L',
				'goals_for_short'        => 'GF',
				'goals_against_short'    => 'GA',
				'goal_difference_short'  => 'GD',
				'points_short'           => 'Pts',
				'date_tba'               => 'Date TBA',
				'tba'                    => 'TBA',
				'home_team'              => 'Home Team',
				'away_team'              => 'Away Team',
			),
			'de' => array(
				'no_matches'             => 'Keine WM-Spiele fuer diese Ansicht gefunden.',
				'no_standings'           => 'Noch keine WM-Tabelle verfuegbar.',
				'standings'              => 'Tabelle',
				'position_short'         => 'Pos',
				'team'                   => 'Team',
				'played_short'           => 'Sp',
				'won_short'              => 'S',
				'draw_short'             => 'U',
				'lost_short'             => 'N',
				'goals_for_short'        => 'T+',
				'goals_against_short'    => 'T-',
				'goal_difference_short'  => 'TD',
				'points_short'           => 'Pkt',
				'date_tba'               => 'Datum offen',
				'tba'                    => 'Offen',
				'home_team'              => 'Heimteam',
				'away_team'              => 'Auswaertsteam',
			),
			'fr' => array(
				'no_matches'             => 'Aucun match de Coupe du monde trouve pour cette vue.',
				'no_standings'           => 'Aucun classement de Coupe du monde disponible pour le moment.',
				'standings'              => 'Classement',
				'position_short'         => 'Pos',
				'team'                   => 'Equipe',
				'played_short'           => 'J',
				'won_short'              => 'G',
				'draw_short'             => 'N',
				'lost_short'             => 'P',
				'goals_for_short'        => 'BP',
				'goals_against_short'    => 'BC',
				'goal_difference_short'  => 'Diff',
				'points_short'           => 'Pts',
				'date_tba'               => 'Date a confirmer',
				'tba'                    => 'A confirmer',
				'home_team'              => 'Equipe domicile',
				'away_team'              => 'Equipe exterieure',
			),
			'es' => array(
				'no_matches'             => 'No se encontraron partidos del Mundial para esta vista.',
				'no_standings'           => 'Todavia no hay clasificacion del Mundial disponible.',
				'standings'              => 'Clasificacion',
				'position_short'         => 'Pos',
				'team'                   => 'Equipo',
				'played_short'           => 'J',
				'won_short'              => 'G',
				'draw_short'             => 'E',
				'lost_short'             => 'P',
				'goals_for_short'        => 'GF',
				'goals_against_short'    => 'GC',
				'goal_difference_short'  => 'DG',
				'points_short'           => 'Pts',
				'date_tba'               => 'Fecha por confirmar',
				'tba'                    => 'Por confirmar',
				'home_team'              => 'Equipo local',
				'away_team'              => 'Equipo visitante',
			),
			'hr' => array(
				'no_matches'             => 'Nisu pronadjene utakmice Svjetskog prvenstva za ovaj prikaz.',
				'no_standings'           => 'Poredak Svjetskog prvenstva jos nije dostupan.',
				'standings'              => 'Poredak',
				'position_short'         => 'Poz',
				'team'                   => 'Momcad',
				'played_short'           => 'O',
				'won_short'              => 'P',
				'draw_short'             => 'N',
				'lost_short'             => 'I',
				'goals_for_short'        => 'G+',
				'goals_against_short'    => 'G-',
				'goal_difference_short'  => 'GR',
				'points_short'           => 'Bod',
				'date_tba'               => 'Datum nije odredjen',
				'tba'                    => 'Nije odredjeno',
				'home_team'              => 'Domaca momcad',
				'away_team'              => 'Gostujuca momcad',
			),
		);

		if ( ! isset( $translations[ $language ] ) ) {
			$language = 'en';
		}

		return $translations[ $language ][ $key ] ?? $translations['en'][ $key ] ?? '';
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
