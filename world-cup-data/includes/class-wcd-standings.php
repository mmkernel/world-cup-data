<?php
/**
 * Standings rendering helpers.
 *
 * @package WorldCupData
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders World Cup standings tables.
 */
class WCD_Standings {

	/**
	 * Renders standings.
	 *
	 * @param array $standings Standings data.
	 * @return string
	 */
	public function render( $standings ) {
		if ( empty( $standings ) || ! is_array( $standings ) ) {
			return '<p class="wcd-empty">' . esc_html( wcd_get_text( 'no_standings' ) ) . '</p>';
		}

		ob_start();
		?>
		<div class="wcd-standings">
			<?php foreach ( $standings as $standing ) : ?>
				<?php
				if ( empty( $standing['table'] ) || ! is_array( $standing['table'] ) ) {
					continue;
				}

				$group_title = ! empty( $standing['group'] ) ? $standing['group'] : ( $standing['stage'] ?? wcd_get_text( 'standings' ) );
				?>
				<section class="wcd-standings-group">
					<h3 class="wcd-group-title"><?php echo esc_html( $group_title ); ?></h3>
					<div class="wcd-table-scroll">
						<table class="wcd-table wcd-standings-table">
							<thead>
								<tr>
									<th><?php echo esc_html( wcd_get_text( 'position_short' ) ); ?></th>
									<th><?php echo esc_html( wcd_get_text( 'team' ) ); ?></th>
									<th><?php echo esc_html( wcd_get_text( 'played_short' ) ); ?></th>
									<th><?php echo esc_html( wcd_get_text( 'won_short' ) ); ?></th>
									<th><?php echo esc_html( wcd_get_text( 'draw_short' ) ); ?></th>
									<th><?php echo esc_html( wcd_get_text( 'lost_short' ) ); ?></th>
									<th><?php echo esc_html( wcd_get_text( 'goals_for_short' ) ); ?></th>
									<th><?php echo esc_html( wcd_get_text( 'goals_against_short' ) ); ?></th>
									<th><?php echo esc_html( wcd_get_text( 'goal_difference_short' ) ); ?></th>
									<th><?php echo esc_html( wcd_get_text( 'points_short' ) ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $standing['table'] as $row ) : ?>
									<tr>
										<td><?php echo esc_html( $row['position'] ?? '' ); ?></td>
										<td class="wcd-team-cell">
											<?php echo wp_kses_post( wcd_render_team_flag( $row['team'] ?? array() ) ); ?>
											<span><?php echo esc_html( $row['team']['name'] ?? '' ); ?></span>
										</td>
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
}
