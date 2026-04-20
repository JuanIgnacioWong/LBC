<?php
/**
 * Archivo de equipos.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<section class="liga-section">
	<div class="liga-container">
		<h1 class="liga-article__title"><?php post_type_archive_title(); ?></h1>
		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : ?>
				<?php the_post(); ?>
				<?php get_template_part( 'template-parts/equipo-card' ); ?>
			<?php endwhile; ?>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'No hay equipos disponibles.', 'liga-basket-chile' ); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
