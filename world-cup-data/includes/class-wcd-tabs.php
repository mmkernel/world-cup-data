<?php
/**
 * Tab UI helpers.
 *
 * @package WorldCupData
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders accessible tab navigation.
 */
class WCD_Tabs {

	/**
	 * Tab definitions.
	 *
	 * @return array
	 */
	public function get_tabs() {
		return array(
			'upcoming' => wcd_get_text( 'upcoming' ),
			'live'     => wcd_get_text( 'live' ),
			'results'  => wcd_get_text( 'results' ),
			'tables'   => wcd_get_text( 'tables' ),
		);
	}

	/**
	 * Sanitizes a tab key.
	 *
	 * @param string $tab Raw tab key.
	 * @return string
	 */
	public function sanitize_tab( $tab ) {
		$tab = sanitize_key( $tab );

		return array_key_exists( $tab, $this->get_tabs() ) ? $tab : 'upcoming';
	}

	/**
	 * Renders tab buttons.
	 *
	 * @param string $default_tab Default selected tab.
	 * @return string
	 */
	public function render_nav( $default_tab ) {
		$tabs = $this->get_tabs();

		ob_start();
		?>
		<div class="wcd-tabs" role="tablist" aria-label="<?php echo esc_attr__( 'World Cup Data', 'world-cup-data' ); ?>">
			<?php foreach ( $tabs as $key => $label ) : ?>
				<button
					type="button"
					class="wcd-tab <?php echo $key === $default_tab ? 'is-active' : ''; ?>"
					id="wcd-tab-<?php echo esc_attr( $key ); ?>"
					role="tab"
					aria-selected="<?php echo $key === $default_tab ? 'true' : 'false'; ?>"
					aria-controls="wcd-panel-<?php echo esc_attr( $key ); ?>"
					data-wcd-tab="<?php echo esc_attr( $key ); ?>"
				>
					<?php echo esc_html( $label ); ?>
				</button>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Renders one tab panel.
	 *
	 * @param string $key         Tab key.
	 * @param string $content     Panel content.
	 * @param string $default_tab Default selected tab.
	 * @return string
	 */
	public function render_panel( $key, $content, $default_tab ) {
		ob_start();
		?>
		<section
			class="wcd-tab-panel <?php echo $key === $default_tab ? 'is-active' : ''; ?>"
			id="wcd-panel-<?php echo esc_attr( $key ); ?>"
			role="tabpanel"
			aria-labelledby="wcd-tab-<?php echo esc_attr( $key ); ?>"
			data-wcd-panel="<?php echo esc_attr( $key ); ?>"
			<?php echo $key === $default_tab ? '' : 'hidden'; ?>
		>
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</section>
		<?php
		return ob_get_clean();
	}
}
