<?php
/**
 * Bloque tabla de posiciones.
 *
 * @package LigaBasketChile
 */

$season    = liga_get_current_season_label();
$divisions = get_posts(
	array(
		'post_type'      => 'division',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_key'       => 'liga_orden_visual',
		'orderby'        => 'meta_value_num',
		'order'          => 'ASC',
	)
);

if ( empty( $divisions ) ) {
	return;
}

$standings_link = function_exists( 'liga_get_default_public_standings_url' ) ? liga_get_default_public_standings_url( $season ) : '';
if ( '' === $standings_link ) {
	$standings_link = home_url( '/posiciones' );
}

$max_visible_teams = 12;
?>
<section class="liga-card liga-home-table" data-liga-reveal>
	<header class="liga-block-head">
		<h2 class="liga-block-title"><?php esc_html_e( 'Tabla de Posiciones', 'liga-basket-chile' ); ?></h2>
		<a href="<?php echo esc_url( $standings_link ); ?>" class="liga-link-more"><?php esc_html_e( 'Ver completa', 'liga-basket-chile' ); ?></a>
	</header>

	<div class="liga-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Divisiones', 'liga-basket-chile' ); ?>">
		<?php foreach ( $divisions as $index => $division ) : ?>
			<button class="liga-tab-button<?php echo 0 === $index ? ' is-active' : ''; ?>" type="button" role="tab" data-liga-tab-target="division-<?php echo esc_attr( (string) $division->ID ); ?>" aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>">
				<?php echo esc_html( $division->post_title ); ?>
			</button>
		<?php endforeach; ?>
	</div>

	<?php foreach ( $divisions as $index => $division ) : ?>
		<?php
		$table_data = liga_calcular_tabla_posiciones( $division->ID, $season );
		$rows       = array_slice( $table_data['tabla'], 0, $max_visible_teams );
		$leader     = ! empty( $rows ) ? $rows[0] : null;
		?>
		<div class="liga-tab-panel<?php echo 0 === $index ? ' is-active' : ''; ?>" data-liga-tab-panel="division-<?php echo esc_attr( (string) $division->ID ); ?>" role="tabpanel">
			<?php if ( $leader ) : ?>
				<div class="liga-leader-card">
					<span class="liga-badge liga-badge--leader"><?php esc_html_e( 'Lider', 'liga-basket-chile' ); ?></span>
					<strong><?php echo esc_html( (string) $leader['equipo'] ); ?></strong>
					<span><?php echo esc_html( sprintf( '%d pts', (int) $leader['pts'] ) ); ?></span>
				</div>
			<?php endif; ?>
			<div class="liga-table-wrap">
				<table class="liga-table">
					<thead>
							<tr>
								<th><?php esc_html_e( 'Pos', 'liga-basket-chile' ); ?></th>
								<th><?php esc_html_e( 'Equipo', 'liga-basket-chile' ); ?></th>
								<th><?php esc_html_e( 'PJ', 'liga-basket-chile' ); ?></th>
								<th><?php esc_html_e( 'PG', 'liga-basket-chile' ); ?></th>
								<th><?php esc_html_e( 'PP', 'liga-basket-chile' ); ?></th>
								<th><?php esc_html_e( 'INC', 'liga-basket-chile' ); ?></th>
								<th><?php esc_html_e( 'PTS', 'liga-basket-chile' ); ?></th>
								<th><?php esc_html_e( 'PF', 'liga-basket-chile' ); ?></th>
								<th><?php esc_html_e( 'PC', 'liga-basket-chile' ); ?></th>
								<th><?php esc_html_e( 'DIF', 'liga-basket-chile' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $rows ) ) : ?>
								<tr><td colspan="10"><?php esc_html_e( 'Sin resultados cargados.', 'liga-basket-chile' ); ?></td></tr>
							<?php endif; ?>
							<?php foreach ( $rows as $row ) : ?>
								<tr>
								<td><?php echo esc_html( (string) (int) $row['pos'] ); ?></td>
								<td>
									<span class="liga-team-chip">
										<?php echo wp_kses_post( liga_get_team_logo_html( isset( $row['equipo_id'] ) ? (int) $row['equipo_id'] : 0, array( 'class' => 'liga-team-logo liga-team-chip__logo', 'size' => array( 22, 22 ) ) ) ); ?>
										<?php echo esc_html( (string) $row['equipo'] ); ?>
									</span>
								</td>
								<td><?php echo esc_html( (string) (int) $row['pj'] ); ?></td>
								<td><?php echo esc_html( (string) (int) $row['pg'] ); ?></td>
									<td><?php echo esc_html( (string) (int) $row['pp'] ); ?></td>
									<td><?php echo esc_html( (string) (int) $row['inc'] ); ?></td>
									<td><strong><?php echo esc_html( (string) (int) $row['pts'] ); ?></strong></td>
									<td><?php echo esc_html( (string) (int) ( isset( $row['pf'] ) ? $row['pf'] : 0 ) ); ?></td>
									<td><?php echo esc_html( (string) (int) ( isset( $row['pc'] ) ? $row['pc'] : 0 ) ); ?></td>
									<td><?php echo esc_html( (string) (int) ( isset( $row['dif'] ) ? $row['dif'] : 0 ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
				</table>
			</div>
		</div>
	<?php endforeach; ?>
</section>
