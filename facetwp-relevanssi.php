<?php
/*
Plugin Name: FacetWP - Relevanssi integration
Plugin URI: https://facetwp.com/
Description: Use Relevanssi with search facets
Version: 0.2
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
            add_filter( 'facetwp_facet_filter_posts', array( $this, 'search_facet' ), 10, 2 );
            add_filter( 'facetwp_facet_search_engines', array( $this, 'search_engines' ) );
        }
    }


    function search_facet( $return, $params ) {
        $facet = $params['facet'];
        $selected_values = $params['selected_values'];
        $selected_values = is_array( $selected_values ) ? $selected_values[0] : $selected_values;

        if ( 'search' == $facet['type'] && 'relevanssi' == $facet['search_engine'] ) {
            if ( empty( $selected_values ) ) {
                return 'continue';
            }

            $query = (object) array(
                'query_vars' => array(
                    's' => $selected_values
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


    function search_engines( $engines ) {
        $engines['relevanssi'] = 'Relevanssi';
        return $engines;
    }
}


new FacetWP_Relevanssi();
