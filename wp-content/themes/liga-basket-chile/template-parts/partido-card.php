<?php
/**
 * Card base de partido.
 *
 * @package LigaBasketChile
 */

$match_id    = get_the_ID();
$local_id    = (int) get_post_meta( $match_id, 'liga_equipo_local', true );
$visita_id   = (int) get_post_meta( $match_id, 'liga_equipo_visita', true );
$local_name  = $local_id > 0 ? liga_get_equipo_nombre( $local_id ) : '';
$visita_name = $visita_id > 0 ? liga_get_equipo_nombre( $visita_id ) : '';

$local_name  = '' !== $local_name ? $local_name : __( 'Local', 'liga-basket-chile' );
$visita_name = '' !== $visita_name ? $visita_name : __( 'Visita', 'liga-basket-chile' );
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'liga-article liga-partido-card' ); ?>>
	<h2 class="liga-article__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
	<div class="liga-fixture-teams">
		<figure class="liga-fixture-team-logo"><?php echo wp_kses_post( liga_get_team_logo_html( $local_id, array( 'class' => 'liga-team-logo liga-fixture-team-logo__image', 'size' => 'thumbnail' ) ) ); ?></figure>
		<p class="liga-fixture-team-name"><?php echo esc_html( $local_name ); ?></p>
		<span class="liga-fixture-versus">vs</span>
		<p class="liga-fixture-team-name"><?php echo esc_html( $visita_name ); ?></p>
		<figure class="liga-fixture-team-logo"><?php echo wp_kses_post( liga_get_team_logo_html( $visita_id, array( 'class' => 'liga-team-logo liga-fixture-team-logo__image', 'size' => 'thumbnail' ) ) ); ?></figure>
	</div>
	<p>
		<strong><?php esc_html_e( 'Fecha:', 'liga-basket-chile' ); ?></strong>
		<?php echo esc_html( (string) get_post_meta( $match_id, 'liga_fecha_partido', true ) ); ?>
		<?php echo esc_html( (string) get_post_meta( $match_id, 'liga_hora_partido', true ) ); ?>
	</p>
	<p>
		<strong><?php esc_html_e( 'Cancha:', 'liga-basket-chile' ); ?></strong>
		<?php echo esc_html( (string) get_post_meta( $match_id, 'liga_cancha', true ) ); ?>
	</p>
	<div class="liga-article__excerpt"><?php the_excerpt(); ?></div>
</article>
