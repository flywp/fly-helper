<?php

namespace FlyWP\StaticSite\Engine\Incremental;

/**
 * Change rows in the table {prefix}flywp_static_changes.
 *
 * @since 1.8.0
 */
class WpChangeStore implements ChangeStore {

    /**
     * Table name without prefix.
     */
    const TABLE = 'flywp_static_changes';

    /**
     * @return string Table name with prefix.
     */
    public static function table() {
        global $wpdb;

        return $wpdb->prefix . self::TABLE;
    }

    /**
     * Create or update the table.
     *
     * @return void
     */
    public static function install() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table   = self::table();
        $charset = $wpdb->get_charset_collate();

        dbDelta(
            "CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  kind varchar(10) NOT NULL,
  object_type varchar(20) NULL,
  object_id bigint(20) unsigned NULL,
  url text NULL,
  reason varchar(100) NOT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY kind_id (kind,id)
) {$charset};"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function insert( $kind, $object_type, $object_id, $url, $reason ) {
        global $wpdb;

        $result = $wpdb->insert(
            self::table(),
            [
                'kind'        => $kind,
                'object_type' => $object_type,
                'object_id'   => $object_id,
                'url'         => $url,
                'reason'      => $reason,
                'created_at'  => gmdate( 'Y-m-d H:i:s' ),
            ]
        );

        return false !== $result;
    }

    /**
     * {@inheritdoc}
     */
    public function rows( $after_id, $up_to_id, $limit ) {
        global $wpdb;

        $table = self::table();
        $rows  = $wpdb->get_results(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is the prefix and a constant.
                "SELECT id, kind, object_type, object_id, url, reason FROM {$table} WHERE id > %d AND id <= %d ORDER BY id ASC LIMIT %d",
                (int) $after_id,
                (int) $up_to_id,
                (int) $limit
            ),
            ARRAY_A
        );

        return array_map(
            function ( $row ) {
                $row['id']        = (int) $row['id'];
                $row['object_id'] = null === $row['object_id'] ? null : (int) $row['object_id'];

                return $row;
            },
            is_array( $rows ) ? $rows : []
        );
    }

    /**
     * {@inheritdoc}
     */
    public function has_global( $after_id, $up_to_id ) {
        global $wpdb;

        $table = self::table();

        return (bool) $wpdb->get_var(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is the prefix and a constant.
                "SELECT id FROM {$table} WHERE kind = 'global' AND id > %d AND id <= %d LIMIT 1",
                (int) $after_id,
                (int) $up_to_id
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function max_id() {
        global $wpdb;

        $table = self::table();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is the prefix and a constant.
        return (int) $wpdb->get_var( "SELECT MAX(id) FROM {$table}" );
    }

    /**
     * {@inheritdoc}
     */
    public function clear_up_to( $change_id ) {
        global $wpdb;

        $table = self::table();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is the prefix and a constant.
        return false !== $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE id <= %d", (int) $change_id ) );
    }
}
