<?php
/**
 * Inline SVG icons for spec rows (matches live mmi-mobile-list).
 */

defined( 'ABSPATH' ) || exit;

class MMI_MLL_Icons {

	public static function render( string $type ): string {
		$icons = array(
			'processor' => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><rect x="5" y="5" width="14" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3" stroke="currentColor" stroke-width="1.6"/></svg>',
			'memory'    => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><rect x="4" y="7" width="16" height="10" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M8 7V5M12 7V5M16 7V5M8 17v2M12 17v2M16 17v2" stroke="currentColor" stroke-width="1.6"/></svg>',
			'rear'      => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M4 8h4l2-2h4l2 2h4v10H4V8z" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="13" r="3" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>',
			'front'     => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><rect x="7" y="3" width="10" height="18" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="14" r="2" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>',
			'battery'   => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><rect x="3" y="8" width="16" height="8" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M21 11v2" stroke="currentColor" stroke-width="1.6"/><path d="M7 12h6" stroke="currentColor" stroke-width="1.6"/></svg>',
			'display'   => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><rect x="5" y="4" width="14" height="16" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M10 20h4" stroke="currentColor" stroke-width="1.6"/></svg>',
		);

		return $icons[ $type ] ?? $icons['processor'];
	}
}