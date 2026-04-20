<?php
/**
 * Front page template.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main class="liga-home" id="liga-main-content">
	<?php get_template_part( 'template-parts/home/hero' ); ?>
	<?php get_template_part( 'template-parts/home/main-panels' ); ?>
	<?php get_template_part( 'template-parts/home/news' ); ?>
	<?php get_template_part( 'template-parts/home/sponsors' ); ?>
</main>
<?php
get_footer();
