<?php
/**
 * Frontend shortcode coordinator.
 *
 * @package WorldCupData
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the configured display timezone.
 *
 * @return DateTimeZone
 */
function wcd_get_display_timezone() {
	$timezone = (string) get_option( 'wcd_timezone', wp_timezone_string() );

	try {
		return new DateTimeZone( $timezone );
	} catch ( Exception $exception ) {
		return wp_timezone();
	}
}

/**
 * Renders a team flag/crest image when the API provides one.
 *
 * @param array $team Team data.
 * @return string
 */
function wcd_render_team_flag( $team ) {
	if ( empty( $team['crest'] ) ) {
		return '<span class="wcd-team-flag wcd-team-flag-placeholder" aria-hidden="true"></span>';
	}

	$name = $team['name'] ?? wcd_get_text( 'team' );

	return sprintf(
		'<img class="wcd-team-flag" src="%s" alt="%s" loading="lazy" decoding="async" />',
		esc_url( $team['crest'] ),
		esc_attr(
			sprintf(
				/* translators: %s: Team name. */
				__( '%s flag', 'world-cup-data' ),
				$name
			)
		)
	);
}

/**
 * Returns frontend text for the selected plugin language.
 *
 * @param string $key Text key.
 * @return string
 */
function wcd_get_text( $key ) {
	$language     = (string) get_option( 'wcd_language', 'en' );
	$translations = array(
		'en' => array(
			'all_teams'              => 'All Teams',
			'away_team'              => 'Away Team',
			'date_tba'               => 'Date TBA',
			'draw_short'             => 'D',
			'filter_by_team'         => 'Filter by Team:',
			'goal_difference_short'  => 'GD',
			'goals_against_short'    => 'GA',
			'goals_for_short'        => 'GF',
			'home_team'              => 'Home Team',
			'live'                   => 'Live',
			'lost_short'             => 'L',
			'no_matches'             => 'No World Cup matches found for this view.',
			'no_standings'           => 'No World Cup standings are available yet.',
			'no_team_matches'        => 'No matches found for the selected team in this tab.',
			'no_today_matches'       => 'No World Cup matches scheduled for today.',
			'played_short'           => 'P',
			'points_short'           => 'Pts',
			'position_short'         => 'Pos',
			'results'                => 'Results',
			'standings'              => 'Standings',
			'status'                 => 'Status',
			'tables'                 => 'Tables',
			'team'                   => 'Team',
			'tba'                    => 'TBA',
			'upcoming'               => 'Upcoming',
			'versus'                 => 'vs',
			'won_short'              => 'W',
		),
		'de' => array(
			'all_teams'              => 'Alle Teams',
			'away_team'              => 'Auswaertsteam',
			'date_tba'               => 'Datum offen',
			'draw_short'             => 'U',
			'filter_by_team'         => 'Nach Team filtern:',
			'goal_difference_short'  => 'TD',
			'goals_against_short'    => 'T-',
			'goals_for_short'        => 'T+',
			'home_team'              => 'Heimteam',
			'live'                   => 'Live',
			'lost_short'             => 'N',
			'no_matches'             => 'Keine WM-Spiele fuer diese Ansicht gefunden.',
			'no_standings'           => 'Noch keine WM-Tabelle verfuegbar.',
			'no_team_matches'        => 'Keine Spiele fuer das ausgewaehlte Team in diesem Tab gefunden.',
			'no_today_matches'       => 'Heute sind keine WM-Spiele angesetzt.',
			'played_short'           => 'Sp',
			'points_short'           => 'Pkt',
			'position_short'         => 'Pos',
			'results'                => 'Ergebnisse',
			'standings'              => 'Tabelle',
			'status'                 => 'Status',
			'tables'                 => 'Tabellen',
			'team'                   => 'Team',
			'tba'                    => 'Offen',
			'upcoming'               => 'Bevorstehend',
			'versus'                 => 'gegen',
			'won_short'              => 'S',
		),
		'fr' => array(
			'all_teams'              => 'Toutes les equipes',
			'away_team'              => 'Equipe exterieure',
			'date_tba'               => 'Date a confirmer',
			'draw_short'             => 'N',
			'filter_by_team'         => 'Filtrer par equipe :',
			'goal_difference_short'  => 'Diff',
			'goals_against_short'    => 'BC',
			'goals_for_short'        => 'BP',
			'home_team'              => 'Equipe domicile',
			'live'                   => 'Direct',
			'lost_short'             => 'P',
			'no_matches'             => 'Aucun match de Coupe du monde trouve pour cette vue.',
			'no_standings'           => 'Aucun classement de Coupe du monde disponible pour le moment.',
			'no_team_matches'        => 'Aucun match trouve pour cette equipe dans cet onglet.',
			'no_today_matches'       => 'Aucun match de Coupe du monde prevu aujourd hui.',
			'played_short'           => 'J',
			'points_short'           => 'Pts',
			'position_short'         => 'Pos',
			'results'                => 'Resultats',
			'standings'              => 'Classement',
			'status'                 => 'Statut',
			'tables'                 => 'Tableaux',
			'team'                   => 'Equipe',
			'tba'                    => 'A confirmer',
			'upcoming'               => 'A venir',
			'versus'                 => 'vs',
			'won_short'              => 'G',
		),
		'es' => array(
			'all_teams'              => 'Todos los equipos',
			'away_team'              => 'Equipo visitante',
			'date_tba'               => 'Fecha por confirmar',
			'draw_short'             => 'E',
			'filter_by_team'         => 'Filtrar por equipo:',
			'goal_difference_short'  => 'DG',
			'goals_against_short'    => 'GC',
			'goals_for_short'        => 'GF',
			'home_team'              => 'Equipo local',
			'live'                   => 'En vivo',
			'lost_short'             => 'P',
			'no_matches'             => 'No se encontraron partidos del Mundial para esta vista.',
			'no_standings'           => 'Todavia no hay clasificacion del Mundial disponible.',
			'no_team_matches'        => 'No se encontraron partidos para el equipo seleccionado en esta pestana.',
			'no_today_matches'       => 'No hay partidos del Mundial programados para hoy.',
			'played_short'           => 'J',
			'points_short'           => 'Pts',
			'position_short'         => 'Pos',
			'results'                => 'Resultados',
			'standings'              => 'Clasificacion',
			'status'                 => 'Estado',
			'tables'                 => 'Tablas',
			'team'                   => 'Equipo',
			'tba'                    => 'Por confirmar',
			'upcoming'               => 'Proximos',
			'versus'                 => 'vs',
			'won_short'              => 'G',
		),
		'hr' => array(
			'all_teams'              => 'Sve momcadi',
			'away_team'              => 'Gostujući tim',
			'date_tba'               => 'Datum nije određen',
			'draw_short'             => 'N',
			'filter_by_team'         => 'Filtriraj po momcadi:',
			'goal_difference_short'  => 'GR',
			'goals_against_short'    => 'G-',
			'goals_for_short'        => 'G+',
			'home_team'              => 'Domaći tim',
			'live'                   => 'Uživo',
			'lost_short'             => 'I',
			'no_matches'             => 'Nisu pronađene utakmice Svjetskog prvenstva za ovaj prikaz.',
			'no_standings'           => 'Poredak Svjetskog prvenstva još nije dostupan.',
			'no_team_matches'        => 'Nema utakmica za odabranu momcad u ovoj kartici.',
			'no_today_matches'       => 'Danas nema zakazanih utakmica Svjetskog prvenstva.',
			'played_short'           => 'O',
			'points_short'           => 'Bod',
			'position_short'         => 'Poz',
			'results'                => 'Rezultati',
			'standings'              => 'Poredak',
			'status'                 => 'Status',
			'tables'                 => 'Tablice',
			'team'                   => 'Momcad',
			'tba'                    => 'Nije određeno',
			'upcoming'               => 'Nadolazeće',
			'versus'                 => 'vs',
			'won_short'              => 'P',
		),
		'bs' => array(
			'all_teams'              => 'Svi timovi',
			'away_team'              => 'Gostujući tim',
			'date_tba'               => 'Datum nije određen',
			'draw_short'             => 'N',
			'filter_by_team'         => 'Filtriraj po timu:',
			'goal_difference_short'  => 'GR',
			'goals_against_short'    => 'G-',
			'goals_for_short'        => 'G+',
			'home_team'              => 'Domaći tim',
			'live'                   => 'Uživo',
			'lost_short'             => 'I',
			'no_matches'             => 'Nisu pronađene utakmice Svjetskog prvenstva za ovaj prikaz.',
			'no_standings'           => 'Tabela Svjetskog prvenstva još nije dostupna.',
			'no_team_matches'        => 'Nema utakmica za odabrani tim u ovoj kartici.',
			'no_today_matches'       => 'Danas nema zakazanih utakmica Svjetskog prvenstva.',
			'played_short'           => 'O',
			'points_short'           => 'Bod',
			'position_short'         => 'Poz',
			'results'                => 'Rezultati',
			'standings'              => 'Tabela',
			'status'                 => 'Status',
			'tables'                 => 'Tabele',
			'team'                   => 'Tim',
			'tba'                    => 'Nije određeno',
			'upcoming'               => 'Nadolazeće',
			'versus'                 => 'vs',
			'won_short'              => 'P',
		),
		'pt' => array(
			'all_teams'              => 'Todas as equipes',
			'away_team'              => 'Equipe visitante',
			'date_tba'               => 'Data a confirmar',
			'draw_short'             => 'E',
			'filter_by_team'         => 'Filtrar por equipe:',
			'goal_difference_short'  => 'SG',
			'goals_against_short'    => 'GC',
			'goals_for_short'        => 'GP',
			'home_team'              => 'Equipe da casa',
			'live'                   => 'Ao vivo',
			'lost_short'             => 'D',
			'no_matches'             => 'Nenhuma partida da Copa do Mundo encontrada para esta visualização.',
			'no_standings'           => 'A tabela da Copa do Mundo ainda não está disponível.',
			'no_team_matches'        => 'Nenhuma partida encontrada para a equipe selecionada nesta aba.',
			'no_today_matches'       => 'Não há partidas da Copa do Mundo programadas para hoje.',
			'played_short'           => 'J',
			'points_short'           => 'Pts',
			'position_short'         => 'Pos',
			'results'                => 'Resultados',
			'standings'              => 'Classificação',
			'status'                 => 'Status',
			'tables'                 => 'Tabelas',
			'team'                   => 'Equipe',
			'tba'                    => 'A confirmar',
			'upcoming'               => 'Próximas',
			'versus'                 => 'vs',
			'won_short'              => 'V',
		),
		'ja' => array(
			'all_teams'              => 'すべてのチーム',
			'away_team'              => 'アウェイチーム',
			'date_tba'               => '日程未定',
			'draw_short'             => '分',
			'filter_by_team'         => 'チームで絞り込み:',
			'goal_difference_short'  => '得失',
			'goals_against_short'    => '失点',
			'goals_for_short'        => '得点',
			'home_team'              => 'ホームチーム',
			'live'                   => 'ライブ',
			'lost_short'             => '敗',
			'no_matches'             => 'この表示に該当するワールドカップの試合はありません。',
			'no_standings'           => 'ワールドカップの順位表はまだ利用できません。',
			'no_team_matches'        => 'このタブには選択したチームの試合がありません。',
			'no_today_matches'       => '本日予定されているワールドカップの試合はありません。',
			'played_short'           => '試',
			'points_short'           => '点',
			'position_short'         => '位',
			'results'                => '結果',
			'standings'              => '順位表',
			'status'                 => 'ステータス',
			'tables'                 => '順位表',
			'team'                   => 'チーム',
			'tba'                    => '未定',
			'upcoming'               => '今後の試合',
			'versus'                 => 'vs',
			'won_short'              => '勝',
		),
		'tr' => array(
			'all_teams'              => 'Tüm takımlar',
			'away_team'              => 'Deplasman takımı',
			'date_tba'               => 'Tarih belirlenecek',
			'draw_short'             => 'B',
			'filter_by_team'         => 'Takıma göre filtrele:',
			'goal_difference_short'  => 'AV',
			'goals_against_short'    => 'GA',
			'goals_for_short'        => 'GF',
			'home_team'              => 'Ev sahibi takım',
			'live'                   => 'Canlı',
			'lost_short'             => 'M',
			'no_matches'             => 'Bu görünüm için Dünya Kupası maçı bulunamadı.',
			'no_standings'           => 'Dünya Kupası puan durumu henüz mevcut değil.',
			'no_team_matches'        => 'Bu sekmede seçilen takım için maç bulunamadı.',
			'no_today_matches'       => 'Bugün planlanmış Dünya Kupası maçı yok.',
			'played_short'           => 'O',
			'points_short'           => 'Puan',
			'position_short'         => 'Sıra',
			'results'                => 'Sonuçlar',
			'standings'              => 'Puan durumu',
			'status'                 => 'Durum',
			'tables'                 => 'Tablolar',
			'team'                   => 'Takım',
			'tba'                    => 'Belirlenecek',
			'upcoming'               => 'Yaklaşan',
			'versus'                 => 'vs',
			'won_short'              => 'G',
		),
	);

	if ( ! isset( $translations[ $language ] ) ) {
		$language = 'en';
	}

	return $translations[ $language ][ $key ] ?? $translations['en'][ $key ] ?? '';
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
		add_shortcode( 'worldcup', array( $this, 'render_worldcup_shortcode' ) );
		add_shortcode( 'worldcup_today', array( $this, 'render_today_shortcode' ) );

		// Keep older shortcodes functional while the new [worldcup] shortcode replaces them.
		add_shortcode( 'worldcup_matches', array( $this, 'render_worldcup_shortcode' ) );
		add_shortcode( 'worldcup_results', array( $this, 'render_results_legacy_shortcode' ) );
		add_shortcode( 'worldcup_standings', array( $this, 'render_standings_legacy_shortcode' ) );

		add_action( 'wp_ajax_wcd_lazy_worldcup', array( $this, 'handle_lazy_worldcup_request' ) );
		add_action( 'wp_ajax_nopriv_wcd_lazy_worldcup', array( $this, 'handle_lazy_worldcup_request' ) );
	}

	/**
	 * Registers frontend assets. They are enqueued only when a shortcode renders.
	 */
	public function register_assets() {
		wp_register_style(
			'wcd-world-cup-data',
			WCD_PLUGIN_URL . 'assets/css/world-cup-data.css',
			array(),
			WCD_VERSION
		);

		wp_register_script(
			'wcd-world-cup-data',
			WCD_PLUGIN_URL . 'assets/js/world-cup-data.js',
			array(),
			WCD_VERSION,
			true
		);
	}

	/**
	 * Renders [worldcup].
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_worldcup_shortcode( $atts = array() ) {
		wp_enqueue_style( 'wcd-world-cup-data' );
		wp_enqueue_script( 'wcd-world-cup-data' );

		$atts = $this->normalize_worldcup_atts( $atts );

		if ( 'yes' === $atts['lazy'] ) {
			$this->localize_lazy_assets();

			return $this->render_lazy_placeholder( $atts );
		}

		return $this->render_worldcup_markup( $atts );
	}

	/**
	 * Handles lazy [worldcup] rendering over admin-ajax.
	 */
	public function handle_lazy_worldcup_request() {
		check_ajax_referer( 'wcd_lazy_worldcup', 'nonce' );

		$raw_atts = isset( $_POST['atts'] ) && is_array( $_POST['atts'] )
			? wp_unslash( $_POST['atts'] )
			: array();

		$atts          = $this->normalize_worldcup_atts( $raw_atts );
		$atts['lazy']  = 'no';
		$atts['limit'] = 10;

		wp_send_json_success(
			array(
				'html' => $this->render_worldcup_markup( $atts ),
			)
		);
	}

	/**
	 * Normalizes [worldcup] shortcode attributes.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return array
	 */
	private function normalize_worldcup_atts( $atts ) {
		$atts = shortcode_atts(
			array(
				'default_tab'  => 'upcoming',
				'display_only' => '',
				'limit'        => 0,
				'lazy'         => 'no',
			),
			$atts,
			'worldcup'
		);

		$atts['default_tab']  = sanitize_key( $atts['default_tab'] );
		$atts['display_only'] = sanitize_text_field( $atts['display_only'] );
		$atts['limit']        = absint( $atts['limit'] );
		$atts['lazy']         = 'yes' === strtolower( sanitize_text_field( $atts['lazy'] ) ) ? 'yes' : 'no';

		return $atts;
	}

	/**
	 * Renders the full [worldcup] interface from local stored data.
	 *
	 * @param array $atts Normalized shortcode attributes.
	 * @return string
	 */
	private function render_worldcup_markup( $atts ) {

		$tabs      = new WCD_Tabs();
		$matches   = new WCD_Matches();
		$standings = new WCD_Standings();
		$filters   = new WCD_Filters();

		$visible_tabs  = $this->parse_display_only_tabs( $atts['display_only'], array_keys( $tabs->get_tabs() ) );
		$default_tab   = $tabs->sanitize_tab( $atts['default_tab'] );
		$url_tab       = isset( $_GET['tab'] ) ? $tabs->sanitize_tab( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$selected_tab  = '' !== $url_tab ? $url_tab : $default_tab;
		$selected_team = isset( $_GET['team'] ) ? sanitize_text_field( wp_unslash( $_GET['team'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$limit         = absint( $atts['limit'] );

		if ( ! in_array( $selected_tab, $visible_tabs, true ) ) {
			$selected_tab = reset( $visible_tabs );
		}

		$matches_data = $this->api->get_matches();

		if ( is_wp_error( $matches_data ) ) {
			return $this->render_notice( WCD_API::LOADING_MESSAGE );
		}

		$is_stale_data = 'stale' === ( $matches_data['__wcd_cache_status'] ?? '' );
		unset( $matches_data['__wcd_cache_status'] );

		$all_matches = $matches_data['matches'] ?? array();
		$teams       = $matches->get_teams( $all_matches );
		$live_statuses = array( 'IN_PLAY', 'PAUSED', 'LIVE' );

		if ( ! $matches->has_matches_with_status( $all_matches, $live_statuses ) ) {
			$visible_tabs = array_values( array_diff( $visible_tabs, array( 'live' ) ) );
		}

		if ( empty( $visible_tabs ) ) {
			return $this->render_notice( wcd_get_text( 'no_matches' ) );
		}

		if ( ! in_array( $selected_tab, $visible_tabs, true ) ) {
			$selected_tab = reset( $visible_tabs );
		}

		if ( '' !== $selected_team && ! in_array( $selected_team, $teams, true ) ) {
			$selected_team = '';
		}

		$panels = array();

		foreach ( $visible_tabs as $tab ) {
			if ( 'upcoming' === $tab ) {
				$panels['upcoming'] = $matches->render_tab_matches( $all_matches, 'upcoming', array( 'SCHEDULED', 'TIMED' ), $limit );
			}

			if ( 'live' === $tab ) {
				$panels['live'] = $matches->render_tab_matches( $all_matches, 'live', $live_statuses, $limit );
			}

			if ( 'results' === $tab ) {
				$panels['results'] = $matches->render_tab_matches( $all_matches, 'results', array( 'FINISHED' ), $limit );
			}

			if ( 'tables' === $tab ) {
				$standings_data   = $this->api->get_standings();
				$is_stale_data    = $is_stale_data || ( ! is_wp_error( $standings_data ) && 'stale' === ( $standings_data['__wcd_cache_status'] ?? '' ) );
				$panels['tables'] = is_wp_error( $standings_data )
					? '<p class="wcd-empty">' . esc_html( WCD_API::LOADING_MESSAGE ) . '</p>'
					: $standings->render( $standings_data['standings'] ?? array() );
			}
		}

		ob_start();
		?>
		<div class="wcd-wrap wcd-worldcup <?php echo $is_stale_data ? 'wcd-data-stale' : ''; ?>" data-wcd-worldcup data-active-tab="<?php echo esc_attr( $selected_tab ); ?>">
			<?php if ( count( $panels ) > 1 ) : ?>
				<?php echo $tabs->render_nav( $selected_tab, array_keys( $panels ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
			<?php echo $filters->render_team_filter( $teams, $selected_team ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<?php if ( count( $panels ) > 1 ) : ?>
				<div class="wcd-tab-panels">
					<?php foreach ( $panels as $key => $content ) : ?>
						<?php echo $tabs->render_panel( $key, $content, $selected_tab ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<?php echo reset( $panels ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>

			<?php echo $this->render_credit(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Passes lazy loading settings to the frontend script.
	 */
	private function localize_lazy_assets() {
		wp_localize_script(
			'wcd-world-cup-data',
			'wcdWorldCupData',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'wcd_lazy_worldcup' ),
				'loadingText' => __( 'Loading World Cup data...', 'world-cup-data' ),
				'errorText'   => __( 'Could not load World Cup data. Please try again.', 'world-cup-data' ),
			)
		);
	}

	/**
	 * Renders the lightweight lazy shortcode placeholder.
	 *
	 * @param array $atts Normalized shortcode attributes.
	 * @return string
	 */
	private function render_lazy_placeholder( $atts ) {
		$payload = wp_json_encode(
			array(
				'default_tab'  => $atts['default_tab'],
				'display_only' => $atts['display_only'],
			)
		);

		if ( false === $payload ) {
			$payload = '{}';
		}

		return sprintf(
			'<div class="wcd-wrap wcd-worldcup wcd-worldcup-lazy" data-wcd-worldcup-lazy data-wcd-atts="%s"><p class="wcd-notice" data-wcd-lazy-status>%s</p></div>',
			esc_attr( $payload ),
			esc_html__( 'Loading World Cup data...', 'world-cup-data' )
		);
	}

	/**
	 * Renders [worldcup_today].
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_today_shortcode( $atts = array() ) {
		wp_enqueue_style( 'wcd-world-cup-data' );

		$atts = shortcode_atts(
			array(
				'show_finished' => 'no',
				'limit'         => 0,
				'title'         => '',
			),
			$atts,
			'worldcup_today'
		);

		$matches_renderer = new WCD_Matches();
		$show_finished    = 'yes' === strtolower( sanitize_text_field( $atts['show_finished'] ) );
		$limit            = absint( $atts['limit'] );
		$title            = sanitize_text_field( $atts['title'] );
		$matches_data     = $this->api->get_cached_matches();

		if ( false === $matches_data ) {
			return $this->render_loading_today_notice();
		}

		$is_stale_data = 'stale' === ( $matches_data['__wcd_cache_status'] ?? '' );
		unset( $matches_data['__wcd_cache_status'] );

		return $matches_renderer->render_today_matches( $matches_data['matches'] ?? array(), $show_finished, $limit, $title, $is_stale_data );
	}

	/**
	 * Renders a discreet creator credit.
	 *
	 * @return string
	 */
	private function render_credit() {
		return sprintf(
			'<div class="wcd-credit">%s <a href="%s" target="_blank" rel="noopener noreferrer">%s</a></div>',
			esc_html__( 'Created by', 'world-cup-data' ),
			esc_url( 'https://masterymesh.com' ),
			esc_html__( 'MasteryMesh', 'world-cup-data' )
		);
	}

	/**
	 * Parses the optional display_only tab list.
	 *
	 * @param string $display_only Comma-separated tab keys.
	 * @param array  $valid_tabs    Valid tab keys in display order.
	 * @return array
	 */
	private function parse_display_only_tabs( $display_only, $valid_tabs ) {
		$display_only = (string) $display_only;

		if ( '' === trim( $display_only ) ) {
			return $valid_tabs;
		}

		$requested = array_filter(
			array_map(
				'sanitize_key',
				array_map( 'trim', explode( ',', $display_only ) )
			)
		);

		$selected = array_values( array_intersect( $valid_tabs, $requested ) );

		return empty( $selected ) ? $valid_tabs : $selected;
	}

	/**
	 * Legacy results shortcode now renders the full tab UI on Results.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_results_legacy_shortcode( $atts = array() ) {
		$atts['default_tab'] = 'results';

		return $this->render_worldcup_shortcode( $atts );
	}

	/**
	 * Legacy standings shortcode now renders the full tab UI on Tables.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_standings_legacy_shortcode( $atts = array() ) {
		$atts['default_tab'] = 'tables';

		return $this->render_worldcup_shortcode( $atts );
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

	/**
	 * Renders the lightweight loading message for [worldcup_today].
	 *
	 * @return string
	 */
	private function render_loading_today_notice() {
		wp_enqueue_style( 'wcd-world-cup-data' );

		return '<div class="wcd-wrap wcd-today-wrap wcd-worldcup-today"><p class="wcd-notice">' . esc_html( WCD_API::LOADING_MESSAGE ) . '</p></div>';
	}
}
