<?php
/**
 * Filter UI helpers.
 *
 * @package WorldCupData
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders frontend filters.
 */
class WCD_Filters {

	/**
	 * Renders the team filter.
	 *
	 * @param array  $teams         Team names.
	 * @param string $selected_team Selected team.
	 * @return string
	 */
	public function render_team_filter( $teams, $selected_team ) {
		ob_start();
		?>
		<div class="wcd-filter" data-wcd-team-filter-wrap>
			<label class="wcd-filter-label" for="wcd-team-filter"><?php echo esc_html( wcd_get_text( 'filter_by_team' ) ); ?></label>
			<select id="wcd-team-filter" class="wcd-team-filter" data-wcd-team-filter>
				<option value=""><?php echo esc_html( wcd_get_text( 'all_teams' ) ); ?></option>
				<?php foreach ( $teams as $team ) : ?>
					<option value="<?php echo esc_attr( $team ); ?>" <?php selected( $selected_team, $team ); ?>>
						<?php echo esc_html( $team ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
		return ob_get_clean();
	}
}
