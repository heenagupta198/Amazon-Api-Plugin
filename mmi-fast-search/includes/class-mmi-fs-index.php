<?php
/**
 * Static JSON search index (one rebuild = one lightweight DB pass).
 */

defined( 'ABSPATH' ) || exit;

class MMI_FS_Index {

	const META_OPTION = 'mmi_fs_index_meta';

	private static bool $rebuild_queued = false;

	public static function init_hooks(): void {
		add_action( 'mmi_fs_rebuild_index', array( __CLASS__, 'rebuild' ) );
		add_action( 'save_post', array( __CLASS__, 'on_save_post' ), 20, 3 );
		add_action( 'shutdown', array( __CLASS__, 'maybe_rebuild_queued' ) );
	}

	/**
	 * @return array{dir:string,url:string}
	 */
	public static function get_storage(): array {
		$upload = wp_upload_dir();
		$dir    = trailingslashit( $upload['basedir'] ) . 'mmi-fast-search';
		$url    = trailingslashit( $upload['baseurl'] ) . 'mmi-fast-search';

		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
			// Block direct PHP execution in uploads folder.
			if ( ! file_exists( $dir . '/index.php' ) ) {
				file_put_contents( $dir . '/index.php', "<?php\n// Silence.\n" );
			}
		}

		return array(
			'dir' => $dir,
			'url' => $url,
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function get_index_meta(): array {
		$meta = get_option( self::META_OPTION, array() );
		return is_array( $meta ) ? $meta : array();
	}

	public static function get_index_url(): string {
		$meta = self::get_index_meta();
		return ! empty( $meta['url'] ) ? (string) $meta['url'] : '';
	}

	/**
	 * Queue a single rebuild per request (avoids hammering DB on bulk edits).
	 */
	public static function on_save_post( int $post_id, $post, bool $update ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$s = MMI_FS_Settings::get();
		if ( ! in_array( $post->post_type, $s['post_types'], true ) ) {
			return;
		}

		if ( 'publish' !== $post->post_status && $update ) {
			self::$rebuild_queued = true;
			return;
		}

		if ( 'publish' === $post->post_status ) {
			self::$rebuild_queued = true;
		}
	}

	public static function maybe_rebuild_queued(): void {
		if ( self::$rebuild_queued ) {
			self::rebuild();
		}
	}

	/**
	 * Build compact JSON: { v, gen, items:[{i,t,u,d,th}] }
	 */
	public static function rebuild(): bool {
		$s     = MMI_FS_Settings::get();
		$limit = (int) $s['max_posts'];
		$types = array_values( array_filter( array_map( 'sanitize_key', $s['post_types'] ) ) );
		if ( empty( $types ) ) {
			$types = array( 'post' );
		}

		global $wpdb;

		$type_placeholders = implode( ',', array_fill( 0, count( $types ), '%s' ) );
		$where_date        = '';
		$prepare_args      = $types;

		if ( (int) $s['days_limit'] > 0 ) {
			$after        = gmdate( 'Y-m-d H:i:s', time() - ( (int) $s['days_limit'] * DAY_IN_SECONDS ) );
			$where_date   = ' AND post_date_gmt >= %s';
			$prepare_args[] = $after;
		}

		$prepare_args[] = $limit;

		$sql = "
			SELECT ID, post_title, post_name, post_date_gmt
			FROM {$wpdb->posts}
			WHERE post_status = 'publish'
				AND post_type IN ($type_placeholders)
				$where_date
			ORDER BY post_date_gmt DESC
			LIMIT %d
		";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $prepare_args ), ARRAY_A );
		if ( ! is_array( $rows ) ) {
			$rows = array();
		}

		$items = array();
		foreach ( $rows as $row ) {
			$id    = (int) $row['ID'];
			$title = html_entity_decode( wp_strip_all_tags( (string) $row['post_title'] ), ENT_QUOTES, 'UTF-8' );
			if ( '' === $title ) {
				continue;
			}

			$thumb = '';
			$t_id  = get_post_thumbnail_id( $id );
			if ( $t_id ) {
				$src = wp_get_attachment_image_url( $t_id, 'thumbnail' );
				if ( is_string( $src ) && '' !== $src ) {
					$thumb = $src;
				}
			}

			$items[] = array(
				'i'  => $id,
				't'  => $title,
				'u'  => get_permalink( $id ),
				'd'  => mysql2date( 'c', (string) $row['post_date_gmt'], false ),
				'th' => $thumb,
			);
		}

		$payload = array(
			'v'     => 1,
			'gen'   => time(),
			'count' => count( $items ),
			'items' => $items,
		);

		$json  = wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		if ( ! is_string( $json ) ) {
			return false;
		}

		$storage = self::get_storage();
		$hash    = substr( md5( $json ), 0, 12 );
		$file    = 'search-' . $hash . '.json';
		$path    = $storage['dir'] . '/' . $file;

		if ( false === file_put_contents( $path, $json ) ) {
			return false;
		}

		// Remove older index files to save disk.
		foreach ( glob( $storage['dir'] . '/search-*.json' ) ?: array() as $old ) {
			if ( $old !== $path ) {
				wp_delete_file( $old );
			}
		}

		$url  = $storage['url'] . '/' . $file;
		$size = filesize( $path );
		update_option(
			self::META_OPTION,
			array(
				'url'       => $url,
				'file'      => $file,
				'count'     => count( $items ),
				'size'      => $size,
				'size_h'    => size_format( (int) $size ),
				'updated'   => time(),
				'updated_h' => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), time() ),
				'hash'      => $hash,
			),
			false
		);

		return true;
	}
}
