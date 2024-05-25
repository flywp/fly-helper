<?php

namespace FlyWP\Optimizations;

use FlyWP\Optimizations;

abstract class Base {

    /**
     * Optimizations instance
     *
     * @var Optimizations
     */
    protected $optimizations;

    /**
     * Group name
     *
     * @var string
     */
    protected $group;

    /**
     * Features to remove.
     *
     * @var array
     */
    protected $features = [];

    /**
     * Class constructor
     *
     * @param Optimizations $optimizations optimizations instance
     *
     * @return void
     */
    public function __construct( Optimizations $optimizations ) {
        $this->optimizations = $optimizations;

        foreach ( $this->features as $feature => $method ) {
            if ( $this->optimizations->feature_enabled( $feature, $this->group ) ) {
                $this->$method();
            }
        }
    }
}
