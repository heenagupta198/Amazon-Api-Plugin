<?php
/**
 * Plugin settings and archive detection.
 */

defined( 'ABSPATH' ) || exit;

class MMI_MLL_Settings {

	const OPTION_ARCHIVES       = 'mmi_mll_archive_slugs';
	const OPTION_BRAND_ARCHIVES = 'mmi_mll_brand_archives';
	const OPTION_PER_PAGE       = 'mmi_mll_per_page';
	const OPTION_RELEASE_DATE   = 'mmi_mll_show_release_date';

	const MENU_SLUG = 'mmi-mobile-list';

	/** Known product_cat slugs for Best / Latest / Upcoming. */
	const DEFAULT_ARCHIVE_SLUGS = array(
		'best-mobiles'     => 'Best Mobiles',
		'latest-mobiles'   => 'Latest Mobiles',
		'upcoming-mobiles' => 'Upcoming Mobiles',
	);

	/**
	 * Enabled product_cat slugs => true.
	 *
	 * @return array<string, bool>
	 */
	public static function get_archive_map(): array {
		$stored = get_option( self::OPTION_ARCHIVES, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$map = array();
		foreach ( self::DEFAULT_ARCHIVE_SLUGS as $slug => $label ) {
			$map[ $slug ] = ! empty( $stored[ $slug ] );
		}

		return $map;
	}

	public static function brand_archives_enabled(): bool {
		return (bool) get_option( self::OPTION_BRAND_ARCHIVES, true );
	}

	public static function per_page_default(): int {
		$per_page = (int) get_option( self::OPTION_PER_PAGE, 12 );
		return max( 1, min( 48, $per_page ) );
	}

	public static function show_release_date(): bool {
		return (bool) get_option( self::OPTION_RELEASE_DATE, true );
	}

	/**
	 * Whether the current request should use 91mobiles-style cards.
	 */
	public static function is_spec_archive(): bool {
		if ( ! is_tax() ) {
			return false;
		}

		$term = get_queried_object();
		if ( ! $term instanceof WP_Term ) {
			return false;
		}

		if ( 'product_cat' === $term->taxonomy ) {
			$map = self::get_archive_map();
			return ! empty( $map[ $term->slug ] );
		}

		if ( 'product_brand' === $term->taxonomy ) {
			return self::brand_archives_enabled();
		}

		return false;
	}

	public static function github_zip_url(): string {
		return apply_filters(
			'mmi_mll_github_zip_url',
			'https://github.com/heenagupta198/Amazon-Api-Plugin/raw/main/mmi-mobile-list-v1.3.0.zip'
		);
	}
}