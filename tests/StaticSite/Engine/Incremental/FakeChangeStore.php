<?php

namespace FlyWP\Tests\StaticSite\Engine\Incremental;

use FlyWP\StaticSite\Engine\Incremental\ChangeStore;

/**
 * In-memory change rows.
 */
class FakeChangeStore implements ChangeStore {

    /** @var array<int, array> */
    public $rows = [];

    /** @var int Number of next inserts that fail. */
    public $failing_inserts = 0;

    private $next_id = 1;

    public function add( $kind, $object_type = null, $object_id = null, $url = null, $reason = 'test' ) {
        $id = $this->next_id++;

        $this->rows[ $id ] = [
            'id'          => $id,
            'kind'        => $kind,
            'object_type' => $object_type,
            'object_id'   => $object_id,
            'url'         => $url,
            'reason'      => $reason,
        ];

        return $id;
    }

    public function insert( $kind, $object_type, $object_id, $url, $reason ) {
        if ( $this->failing_inserts > 0 ) {
            $this->failing_inserts--;

            return false;
        }

        $this->add( $kind, $object_type, $object_id, $url, $reason );

        return true;
    }

    public function rows( $after_id, $up_to_id, $limit ) {
        $rows = array_filter(
            $this->rows,
            function ( $row ) use ( $after_id, $up_to_id ) {
                return $row['id'] > $after_id && $row['id'] <= $up_to_id;
            }
        );

        return array_slice( array_values( $rows ), 0, $limit );
    }

    public function has_global( $after_id, $up_to_id ) {
        foreach ( $this->rows( $after_id, $up_to_id, PHP_INT_MAX ) as $row ) {
            if ( 'global' === $row['kind'] ) {
                return true;
            }
        }

        return false;
    }

    public function max_id() {
        return $this->rows ? max( array_keys( $this->rows ) ) : 0;
    }

    public function clear_up_to( $change_id ) {
        foreach ( array_keys( $this->rows ) as $id ) {
            if ( $id <= $change_id ) {
                unset( $this->rows[ $id ] );
            }
        }

        return true;
    }

    /**
     * @return array<int, array{0:string, 1:string|null, 2:int|null, 3:string|null}> kind, object type, object ID, URL.
     */
    public function summary() {
        return array_values(
            array_map(
                function ( $row ) {
                    return [ $row['kind'], $row['object_type'], $row['object_id'], $row['url'] ];
                },
                $this->rows
            )
        );
    }
}
