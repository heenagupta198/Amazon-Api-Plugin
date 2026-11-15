<?php
/**
 * TV attribute schema. Slugs are prefixed tv- so they never collide with mobile attributes.
 * WooCommerce taxonomy names are pa_{slug} and must stay <= 32 characters, so slug <= 28.
 */

defined( 'ABSPATH' ) || exit;

final class MMI_TV_Schema {

	/**
	 * @return array<int, array{group:string, label:string, slug:string, archives:bool, order_by:string, terms:array<int,string>}>
	 */
	public static function attributes(): array {
		return array(
			// General
			self::attr( 'General', 'Brand', 'tv-brand', true, 'name', self::brands() ),
			self::attr( 'General', 'Model Name', 'tv-model', false, 'name', array() ),
			self::attr( 'General', 'Series', 'tv-series', false, 'name', array() ),
			self::attr( 'General', 'TV Type', 'tv-type', true, 'name', array( 'LED TV', 'QLED TV', 'OLED TV', 'Mini LED TV', 'Smart TV', 'Google TV', 'Android TV', 'Fire TV' ) ),
			self::attr( 'General', 'Launch Date', 'tv-launch-date', false, 'name', array() ),
			self::attr( 'General', 'Launch Year', 'tv-launch-year', true, 'name_num', array( '2023', '2024', '2025', '2026' ) ),
			self::attr( 'General', 'Warranty', 'tv-warranty', true, 'name', array( '1 Year', '2 Years', '3 Years' ) ),

			// Display
			self::attr( 'Display', 'Screen Size', 'tv-screen-size', true, 'menu_order', array( '24 Inch', '32 Inch', '40 Inch', '43 Inch', '50 Inch', '55 Inch', '65 Inch', '75 Inch', '85 Inch', '98 Inch' ) ),
			self::attr( 'Display', 'Screen Size (cm)', 'tv-screen-cm', false, 'name', array() ),
			self::attr( 'Display', 'Resolution', 'tv-resolution', true, 'menu_order', array( 'HD Ready', 'Full HD', '4K Ultra HD', '8K' ) ),
			self::attr( 'Display', 'Panel Type', 'tv-panel-type', true, 'name', array( 'LED', 'LCD', 'QLED', 'OLED', 'Mini LED', 'QD-Mini LED', 'Crystal UHD', 'NanoCell', 'QNED' ) ),
			self::attr( 'Display', 'Display Type', 'tv-display-type', false, 'name', array() ),
			self::attr( 'Display', 'Refresh Rate', 'tv-refresh-rate', true, 'menu_order', array( '50 Hz', '60 Hz', '100 Hz', '120 Hz', '144 Hz' ) ),
			self::attr( 'Display', 'HDR', 'tv-hdr', true, 'name', array( 'HDR10', 'HDR10+', 'Dolby Vision', 'HLG', 'HDR10+ Adaptive' ) ),
			self::attr( 'Display', 'Brightness', 'tv-brightness', false, 'name', array() ),
			self::attr( 'Display', 'Contrast Ratio', 'tv-contrast', false, 'name', array() ),
			self::attr( 'Display', 'Viewing Angle', 'tv-view-angle', false, 'name', array() ),
			self::attr( 'Display', 'Aspect Ratio', 'tv-aspect-ratio', true, 'name', array( '16:9' ) ),
			self::attr( 'Display', 'Bezel Type', 'tv-bezel', false, 'name', array() ),

			// Picture
			self::attr( 'Picture', 'Processor', 'tv-processor', false, 'name', array() ),
			self::attr( 'Picture', 'Picture Engine', 'tv-pic-engine', false, 'name', array() ),
			self::attr( 'Picture', 'MEMC', 'tv-memc', true, 'name', array( 'Yes', 'No' ) ),
			self::attr( 'Picture', 'Variable Refresh Rate', 'tv-vrr', true, 'name', array( 'Yes', 'No' ) ),
			self::attr( 'Picture', 'HDMI 2.1', 'tv-hdmi-21', true, 'name', array( 'Yes', 'No' ) ),
			self::attr( 'Picture', 'Filmmaker Mode', 'tv-filmmaker', true, 'name', array( 'Yes', 'No' ) ),

			// Sound
			self::attr( 'Sound', 'Speaker Output', 'tv-speaker-out', false, 'name', array() ),
			self::attr( 'Sound', 'Number of Speakers', 'tv-speakers', false, 'name', array() ),
			self::attr( 'Sound', 'Sound Technology', 'tv-sound-tech', true, 'name', array( 'Dolby Audio', 'Dolby Atmos', 'DTS Virtual:X', 'DTS-HD', 'ONKYO' ) ),
			self::attr( 'Sound', 'Subwoofer', 'tv-subwoofer', true, 'name', array( 'Yes', 'No' ) ),
			self::attr( 'Sound', 'Soundbar Support', 'tv-soundbar', true, 'name', array( 'Yes', 'No' ) ),

			// Smart features
			self::attr( 'Smart Features', 'Operating System', 'tv-os', true, 'name', array( 'Google TV', 'Android TV', 'Tizen', 'webOS', 'Fire TV', 'VIDAA', 'Linux' ) ),
			self::attr( 'Smart Features', 'Voice Assistant', 'tv-voice', true, 'name', array( 'Google Assistant', 'Alexa', 'Bixby', 'None' ) ),
			self::attr( 'Smart Features', 'Chromecast', 'tv-chromecast', true, 'name', array( 'Yes', 'No' ) ),
			self::attr( 'Smart Features', 'Screen Mirroring', 'tv-mirroring', true, 'name', array( 'Miracast', 'AirPlay', 'Chromecast', 'Yes', 'No' ) ),
			self::attr( 'Smart Features', 'Supported Apps', 'tv-apps', false, 'name', array() ),

			// Connectivity
			self::attr( 'Connectivity', 'HDMI Ports', 'tv-hdmi', true, 'menu_order', array( '2 HDMI', '3 HDMI', '4 HDMI' ) ),
			self::attr( 'Connectivity', 'USB Ports', 'tv-usb', true, 'menu_order', array( '1 USB', '2 USB', '3 USB' ) ),
			self::attr( 'Connectivity', 'Wi-Fi', 'tv-wifi', true, 'name', array( 'Yes', 'No', 'Wi-Fi 5', 'Wi-Fi 6' ) ),
			self::attr( 'Connectivity', 'Bluetooth', 'tv-bluetooth', true, 'name', array( 'Yes', 'No', 'v5.0', 'v5.1', 'v5.2', 'v5.3' ) ),
			self::attr( 'Connectivity', 'Ethernet', 'tv-ethernet', true, 'name', array( 'Yes', 'No' ) ),
			self::attr( 'Connectivity', 'Optical Audio Out', 'tv-optical', true, 'name', array( 'Yes', 'No' ) ),
			self::attr( 'Connectivity', 'CI Slot', 'tv-ci-slot', true, 'name', array( 'Yes', 'No' ) ),
			self::attr( 'Connectivity', 'AV / Composite', 'tv-av', true, 'name', array( 'Yes', 'No' ) ),

			// Design
			self::attr( 'Design', 'Height', 'tv-height', false, 'name', array() ),
			self::attr( 'Design', 'Width', 'tv-width', false, 'name', array() ),
			self::attr( 'Design', 'Thickness', 'tv-thickness', false, 'name', array() ),
			self::attr( 'Design', 'Weight', 'tv-weight', false, 'name', array() ),
			self::attr( 'Design', 'Stand Type', 'tv-stand', false, 'name', array() ),
			self::attr( 'Design', 'Wall Mount', 'tv-wall-mount', true, 'name', array( 'Yes', 'No' ) ),
			self::attr( 'Design', 'Colour', 'tv-colour', true, 'name', array( 'Black', 'Grey', 'Silver' ) ),

			// Power
			self::attr( 'Power', 'Power Consumption', 'tv-power', false, 'name', array() ),
			self::attr( 'Power', 'Voltage', 'tv-voltage', false, 'name', array() ),
			self::attr( 'Power', 'Energy Rating', 'tv-energy', true, 'name', array( '1 Star', '2 Star', '3 Star', '4 Star', '5 Star' ) ),
		);
	}

	/**
	 * @return array<string, array<int, string>> group label => slugs
	 */
	public static function groups(): array {
		$groups = array();
		foreach ( self::attributes() as $attr ) {
			$groups[ $attr['group'] ][] = $attr['slug'];
		}
		return $groups;
	}

	/**
	 * @return array<int, string>
	 */
	public static function slugs(): array {
		return array_values( array_map( static function ( $attr ) {
			return $attr['slug'];
		}, self::attributes() ) );
	}

	/**
	 * @return array<int, string>
	 */
	private static function brands(): array {
		return array(
			'Samsung',
			'LG',
			'Sony',
			'TCL',
			'Xiaomi',
			'Mi',
			'OnePlus',
			'Hisense',
			'Vu',
			'Toshiba',
			'Panasonic',
			'Philips',
			'Haier',
			'Kodak',
			'Lloyd',
			'realme',
			'Motorola',
			'Acer',
			'Hyundai',
			'iFFALCON',
			'Thomson',
			'Compaq',
			'CMF',
			'Sansui',
			'BPL',
		);
	}

	/**
	 * @param array<int, string> $terms
	 * @return array{group:string, label:string, slug:string, archives:bool, order_by:string, terms:array<int,string>}
	 */
	private static function attr( string $group, string $label, string $slug, bool $archives, string $order_by, array $terms ): array {
		return array(
			'group'    => $group,
			'label'    => $label,
			'slug'     => $slug,
			'archives' => $archives,
			'order_by' => $order_by,
			'terms'    => $terms,
		);
	}
}
