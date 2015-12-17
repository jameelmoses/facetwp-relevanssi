<?php
/*
Plugin Name: FacetWP - Relevanssi integration
Plugin URI: https://facetwp.com/
Description: Relevanssi integration for FacetWP
Version: 0.3
Author: Matt Gibbs
GitHub Plugin URI: https://github.com/FacetWP/facetwp-relevanssi

Copyright 2015 Matt Gibbs

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, see <http://www.gnu.org/licenses/>.
*/

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


class FacetWP_Relevanssi
{
    function __construct() {
        add_action( 'init', array( $this, 'init' ) );
    }


    function init() {
        if ( function_exists( 'relevanssi_search' ) ) {
            add_filter( 'facetwp_query_args', array( $this, 'search_args' ), 10, 2 );
            add_filter( 'facetwp_pre_filtered_post_ids', array( $this, 'search_page' ), 10, 2 );
            add_filter( 'facetwp_facet_filter_posts', array( $this, 'search_facet' ), 10, 2 );
            add_filter( 'facetwp_facet_search_engines', array( $this, 'search_engines' ) );
        }
    }


    /**
     * Prevent the default WP search from running when Relevanssi is enabled
     * @since 0.4
     */
    function search_args( $args, $class ) {

        if ( $class->is_search ) {
            $this->search_terms = $args['s'];
            unset( $args['s'] );

            $args['suppress_filters'] = true;
            if ( empty( $args['post_type'] ) ) {
                $args['post_type'] = 'any';
            }
        }

        return $args;
    }


    /**
     * Use relevanssi_do_query() to retrieve matching post IDs
     * @since 0.4
     */
    function search_page( $post_ids, $class ) {

        if ( empty( $this->search_terms ) ) {
            return $post_ids;
        }

        $query = (object) array(
            'is_admin' => false,
            'query_vars' => array(
                's' => $this->search_terms,
                'paged' => 1,
                'posts_per_page' => -1
            )
        );

        relevanssi_do_query( $query );

        $intersected_ids = array();
        foreach ( $query->posts as $post ) {
            if ( in_array( $post->ID, $post_ids ) ) {
                $intersected_ids[] = $post->ID;
            }
        }
        $post_ids = $intersected_ids;

        return empty( $post_ids ) ? array( 0 ) : $post_ids;
    }


    /**
     * Intercept search facets using Relevanssi
     * @since 0.4
     */
    function search_facet( $return, $params ) {
        $facet = $params['facet'];
        $selected_values = $params['selected_values'];
        $selected_values = is_array( $selected_values ) ? $selected_values[0] : $selected_values;

        if ( 'search' == $facet['type'] && 'relevanssi' == $facet['search_engine'] ) {
            if ( empty( $selected_values ) ) {
                return 'continue';
            }

            $query = (object) array(
                'is_admin' => false,
                'query_vars' => array(
                    's' => $selected_values,
                    'paged' => 1,
                    'posts_per_page' => -1
                )
            );

            relevanssi_do_query( $query );

            $matches = array();
            foreach ( $query->posts as $result ) {
                $matches[] = $result->ID;
            }

            return $matches;
        }

        return $return;
    }


    /**
     * Add engines to the search facet
     */
    function search_engines( $engines ) {
        $engines['relevanssi'] = 'Relevanssi';
        return $engines;
    }
}


new FacetWP_Relevanssi();
