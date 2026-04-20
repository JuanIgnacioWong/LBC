<?php
/**
 * Archivo de noticias.
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
		<?php get_template_part( 'template-parts/noticias' ); ?>
	</div>
</section>
<?php
get_footer();
