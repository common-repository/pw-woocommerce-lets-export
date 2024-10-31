<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !function_exists( 'wc_get_products' ) ) {
	function wc_get_products( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'status'         => array( 'draft', 'pending', 'private', 'publish' ),
			'type'           => array_merge( array_keys( wc_get_product_types() ) ),
			'parent'         => null,
			'sku'            => '',
			'category'       => array(),
			'tag'            => array(),
			'limit'          => get_option( 'posts_per_page' ),
			'offset'         => null,
			'page'           => 1,
			'include'        => array(),
			'exclude'        => array(),
			'orderby'        => 'date',
			'order'          => 'DESC',
			'return'         => 'objects',
			'paginate'       => false,
			'shipping_class' => array(),
		) );

		// Handle some BW compatibility arg names where wp_query args differ in naming.
		$map_legacy = array(
			'numberposts'    => 'limit',
			'post_status'    => 'status',
			'post_parent'    => 'parent',
			'posts_per_page' => 'limit',
			'paged'          => 'page',
		);

		foreach ( $map_legacy as $from => $to ) {
			if ( isset( $args[ $from ] ) ) {
				$args[ $to ] = $args[ $from ];
			}
		}

		/**
		 * Generate WP_Query args.
		 */
		$wp_query_args = array(
			'post_status'    => $args['status'],
			'posts_per_page' => $args['limit'],
			'meta_query'     => array(),
			'orderby'        => $args['orderby'],
			'order'          => $args['order'],
			'tax_query'      => array(),
		);

		if ( 'variation' === $args['type'] ) {
			$wp_query_args['post_type'] = 'product_variation';
		} elseif ( is_array( $args['type'] ) && in_array( 'variation', $args['type'] ) ) {
			$wp_query_args['post_type']   = array( 'product_variation', 'product' );
			$wp_query_args['tax_query'][] = array(
				'relation' => 'OR',
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => $args['type'],
				),
				array(
					'taxonomy' => 'product_type',
					'field'    => 'id',
					'operator' => 'NOT EXISTS',
				),
			);
		} else {
			$wp_query_args['post_type']   = 'product';
			$wp_query_args['tax_query'][] = array(
				'taxonomy' => 'product_type',
				'field'    => 'slug',
				'terms'    => $args['type'],
			);
		}

		// Do not load unnecessary post data if the user only wants IDs.
		if ( 'ids' === $args['return'] ) {
			$wp_query_args['fields'] = 'ids';
		}

		if ( ! empty( $args['sku'] ) ) {
			$wp_query_args['meta_query'][] = array(
				'key'     => '_sku',
				'value'   => $args['sku'],
				'compare' => 'LIKE',
			);
		}

		if ( ! empty( $args['category'] ) ) {
			$wp_query_args['tax_query'][] = array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'   => $args['category'],
			);
		}

		if ( ! empty( $args['tag'] ) ) {
			$wp_query_args['tax_query'][] = array(
				'taxonomy' => 'product_tag',
				'field'    => 'slug',
				'terms'   => $args['tag'],
			);
		}

		if ( ! empty( $args['shipping_class'] ) ) {
			$wp_query_args['tax_query'][] = array(
				'taxonomy' => 'product_shipping_class',
				'field'    => 'slug',
				'terms'   => $args['shipping_class'],
			);
		}

		if ( ! is_null( $args['parent'] ) ) {
			$wp_query_args['post_parent'] = absint( $args['parent'] );
		}

		if ( ! is_null( $args['offset'] ) ) {
			$wp_query_args['offset'] = absint( $args['offset'] );
		} else {
			$wp_query_args['paged'] = absint( $args['page'] );
		}

		if ( ! empty( $args['include'] ) ) {
			$wp_query_args['post__in'] = array_map( 'absint', $args['include'] );
		}

		if ( ! empty( $args['exclude'] ) ) {
			$wp_query_args['post__not_in'] = array_map( 'absint', $args['exclude'] );
		}

		if ( ! $args['paginate'] ) {
			$wp_query_args['no_found_rows'] = true;
		}

		// Get results.
		$products = new WP_Query( $wp_query_args );

		if ( 'objects' === $args['return'] ) {
			// Prime caches before grabbing objects.
			update_post_caches( $products->posts, array( 'product', 'product_variation' ) );

			$return = array_filter( array_map( 'wc_get_product', $products->posts ) );
		} else {
			$return = $products->posts;
		}

		if ( $args['paginate'] ) {
			return (object) array(
				'products'      => $return,
				'total'         => $products->found_posts,
				'max_num_pages' => $products->max_num_pages,
			);
		} else {
			return $return;
		}
	}
}

?>