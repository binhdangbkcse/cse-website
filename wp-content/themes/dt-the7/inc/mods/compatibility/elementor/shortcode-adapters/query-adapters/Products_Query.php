<?php

namespace The7\Mods\Compatibility\Elementor\Shortcode_Adapters\Query_Adapters;

use The7\Inc\Mods\Compatibility\WooCommerce\Front\Recently_Viewed_Products;
use The7\Mods\Compatibility\Elementor\Shortcode_Adapters\Query_Interface;
use The7\Mods\Compatibility\Elementor\The7_Elementor_Widget_Base;

class Products_Query extends Query_Interface {

	/**
	 * Create a new WP_Qury instance.
	 *
	 * @return mixed|\WP_Query
	 */
	public function create() {
		if ( 'current_query' === $this->get_att( $this->query_prefix . 'post_type' ) ) {
			return The7_Elementor_Widget_Base::get_current_query( $this->atts );
		}

		$this->add_pre_query_hooks();

		$query = new \WP_Query( $this->parse_query_args() );

		$this->remove_pre_query_hooks();

		return $query;
	}

	public function parse_query_args() {
		// Get order + orderby args from string.
		$orderig_args = $this->get_ordering_args( $this->get_att( $this->query_prefix . 'orderby' ), $this->get_att( $this->query_prefix . 'order' ) );

		$query_args = [
			'post_type'           => 'product',
			'ignore_sticky_posts' => true,
			'orderby'             => $orderig_args['orderby'],
			'order'               => $orderig_args['order'],
		];

		$query_args['meta_query'] = WC()->query->get_meta_query();
		$query_args['tax_query']  = [];

		$loading_mode                 = $this->get_att( 'loading_mode', 'disabled' );
		$query_args['posts_per_page'] = (int) $this->get_posts_per_page( $loading_mode, $this->atts );
		if ( 'standard' === $loading_mode ) {
			$query_args['paged'] = the7_get_paged_var();
		}

		// Visibility.
		$this->set_visibility_query_args( $query_args );

		// Featured.
		$this->set_featured_query_args( $query_args );

		// Sale.
		$this->set_sale_products_query_args( $query_args );

		// Best sellings.
		$this->set_best_sellings_products_query_args( $query_args );

		// Top rated.
		$this->set_top_rated_products_query_args( $query_args );

		// IDs.
		$this->set_ids_query_args( $query_args );

		// Categories & Tags.
		$this->set_terms_query_args( $query_args );

		// Exclude.
		$this->set_exclude_query_args( $query_args );

		// Related.
		$this->set_related_products_query_args( $query_args );

		// Recently viewed.
		$this->set_recently_viewed_query_args( $query_args );

		// Query filters.
		$this->apply_query_filters( $query_args );

		// Up-sales.
		$this->set_up_sales( $query_args );

		// Cross-sales.
		$this->set_cross_sales( $query_args );

		$ordering_args = WC()->query->get_catalog_ordering_args( $query_args['orderby'], $query_args['order'] );

		$query_args['orderby'] = $ordering_args['orderby'];
		$query_args['order']   = $ordering_args['order'];
		if ( $ordering_args['meta_key'] ) {
			$query_args['meta_key'] = $ordering_args['meta_key']; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		}

		$query_args = apply_filters( 'the7_woocommerce_widget_products_query', $query_args );

		// Load only id fileds.
		$query_args['fields'] = [ 'ids' ];

		return $query_args;
	}

	/**
	 * @param array $query_args Query args.
	 *
	 * @return void
	 */
	protected function set_up_sales( &$query_args ) {
		if ( 'up_sales' === $this->get_att( $this->query_prefix . 'post_type' ) ) {
			$product = wc_get_product();

			if ( ! $product ) {
				return;
			}

			// Get visible upsells.
			$upsells = array_filter( array_map( 'wc_get_product', $product->get_upsell_ids() ), 'wc_products_array_filter_visible' );

			$query_args['post__in']   = array_map(
				function ( $p ) {
					return $p->get_id();
				},
				$upsells
			);
			$query_args['post__in'][] = 0;
			$query_args['post_type']  = [ 'product_variation', 'product' ];
			$query_args['meta_query'] = [];
			$query_args['tax_query']  = [];
		}
	}

	/**
	 * @param array $query_args Query args.
	 *
	 * @return void
	 */
	protected function set_cross_sales( &$query_args ) {
		if ( 'cross_sales' === $this->get_att( $this->query_prefix . 'post_type' ) ) {
			$product = wc_get_product();

			if ( ! $product ) {
				return;
			}

			// Get visible cross-sells.
			$cross_sells = array_filter( array_map( 'wc_get_product', $product->get_cross_sell_ids() ), 'wc_products_array_filter_visible' );

			$query_args['post__in']   = array_map(
				function ( $p ) {
					return $p->get_id();
				},
				$cross_sells
			);
			$query_args['post__in'][] = 0;
			$query_args['post_type']  = [ 'product_variation', 'product' ];
			$query_args['meta_query'] = [];
			$query_args['tax_query']  = [];
		}
	}

	protected function set_visibility_query_args( &$query_args ) {
		$query_args['tax_query'] = array_merge( $query_args['tax_query'], WC()->query->get_tax_query() ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}

	protected function set_featured_query_args( &$query_args ) {
		if ( 'featured' === $this->get_att( $this->query_prefix . 'post_type' ) ) {
			$product_visibility_term_ids = wc_get_product_visibility_term_ids();

			$query_args['tax_query'][] = [
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => [ $product_visibility_term_ids['featured'] ],
			];
		}
	}

	protected function set_sale_products_query_args( &$query_args ) {
		if ( 'sale' === $this->get_att( $this->query_prefix . 'post_type' ) ) {
			$query_args['post__in'] = array_merge( array( 0 ), wc_get_product_ids_on_sale() );
		}
	}

	protected function set_ids_query_args( &$query_args ) {

		switch ( $this->get_att( $this->query_prefix . 'post_type' ) ) {
			case 'by_id':
				$post__in = $this->get_att( $this->query_prefix . 'posts_ids' );
				break;
			case 'sale':
				$post__in = wc_get_product_ids_on_sale();
				break;
		}

		if ( ! empty( $post__in ) ) {
			$query_args['post__in'] = $post__in;
		}
	}

	private function set_terms_query_args( &$query_args ) {

		$query_type = $this->get_att( $this->query_prefix . 'post_type' );

		if ( in_array( $query_type, [ 'current_query', 'by_id', 'related' ], true ) ) {
			return;
		}

		if ( empty( $this->get_att( $this->query_prefix . 'include' ) ) || empty( $this->get_att( $this->query_prefix . 'include_term_ids' ) ) || ! in_array( 'terms', $this->get_att( $this->query_prefix . 'include' ), true ) ) {
			return;
		}

		$terms = [];
		foreach ( $this->get_att( $this->query_prefix . 'include_term_ids' ) as $id ) {
			$term_data = get_term_by( 'term_taxonomy_id', $id );
			if ( isset( $term_data->taxonomy ) ) {
				$terms[ $term_data->taxonomy ][] = $id;
			}
		}
		$this->insert_tax_query( $query_args, $terms );
	}

	protected function set_exclude_query_args( &$query_args ) {
		if ( empty( $this->get_att( $this->query_prefix . 'exclude' ) ) ) {
			return;
		}
		$post__not_in = [];
		if ( in_array( 'current_post', $this->get_att( $this->query_prefix . 'exclude' ) ) ) {
			if ( is_singular() ) {
				$post__not_in[] = get_queried_object_id();
			}
		}

		if ( in_array( 'manual_selection', $this->get_att( $this->query_prefix . 'exclude' ) ) && ! empty( $this->get_att( $this->query_prefix . 'exclude_ids' ) ) ) {
			$post__not_in = array_merge( $post__not_in, $this->get_att( $this->query_prefix . 'exclude_ids' ) );
		}

		$query_args['post__not_in'] = empty( $query_args['post__not_in'] ) ? $post__not_in : array_merge( $query_args['post__not_in'], $post__not_in );

		/**
		 * WC populates `post__in` with the ids of the products that are on sale.
		 * Since WP_Query ignores `post__not_in` once `post__in` exists, the ids are filtered manually, using `array_diff`.
		 */
		if ( 'sale' === $this->get_att( $this->query_prefix . 'post_type' ) ) {
			$query_args['post__in'] = array_diff( $query_args['post__in'], $query_args['post__not_in'] );
		}

		if ( in_array( 'terms', $this->get_att( $this->query_prefix . 'exclude' ) ) && ! empty( $this->get_att( $this->query_prefix . 'exclude_term_ids' ) ) ) {
			$terms = [];
			foreach ( $this->get_att( $this->query_prefix . 'exclude_term_ids' ) as $id ) {
				$term_data = get_term_by( 'term_taxonomy_id', $id );
				if ( isset( $term_data->taxonomy ) ) {
					$terms[ $term_data->taxonomy ][] = $id;
				}
			}
			$this->insert_tax_query( $query_args, $terms, true );
		}
	}

	protected function insert_tax_query(&$query_args, $terms, $exclude = false ) {
		$tax_query = [];
		foreach ( $terms as $taxonomy => $ids ) {
			$query = [
				'taxonomy' => $taxonomy,
				'field' => 'term_taxonomy_id',
				'terms' => $ids,
			];

			if ( $exclude ) {
				$query['operator'] = 'NOT IN';
			}

			$tax_query[] = $query;
		}

		if ( empty( $tax_query ) ) {
			return;
		}

		if ( empty( $query_args['tax_query'] ) ) {
			$query_args['tax_query'] = $tax_query;
		} else {
			$query_args['tax_query']['relation'] = 'AND';
			$query_args['tax_query'][] = $tax_query;
		}
	}

	protected function set_best_sellings_products_query_args( &$query_args ) {
		if ( 'best_selling' === $this->get_att( $this->query_prefix . 'post_type' ) ) {
			$query_args['meta_key'] = 'total_sales';
			$query_args['orderby']  = 'meta_value_num';
		}
	}

	protected function set_top_rated_products_query_args( &$query_args ) {
		if ( 'top' === $this->get_att( $this->query_prefix . 'post_type' ) ) {
			add_filter( 'posts_clauses', array( 'WC_Shortcodes', 'order_by_rating_post_clauses' ) );
			$query_args['meta_key'] = '_wc_average_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$query_args['orderby']  = 'meta_value_num';
		}
	}

	protected function set_related_products_query_args( &$query_args ) {
		global $product;

		if ( 'related' !== $this->get_att( $this->query_prefix . 'post_type' ) ) {
			return;
		}

		$product = wc_get_product();

		if ( ! $product ) {
			return;
		}

		$posts_per_page = ! empty( $query_args['posts_per_page'] ) ? $query_args['posts_per_page'] : 9999;

		$is_related_by_taxonomy = $this->get_query_att( 'related_products_by' ) === 'taxonomy';
		if ( $is_related_by_taxonomy ) {
			add_filter( 'woocommerce_product_related_posts_relate_by_tag', '__return_false', 20 );
		}

		// Get visible related products then sort them at random.
		$products = array_filter(
			array_map(
				'wc_get_product',
				wc_get_related_products( $product->get_id(), $posts_per_page, $product->get_upsell_ids() )
			),
			'wc_products_array_filter_visible'
		);

		if ( $is_related_by_taxonomy ) {
			remove_filter( 'woocommerce_product_related_posts_relate_by_tag', '__return_false', 20 );
		}

		// Handle orderby.
		$products = wc_products_array_orderby( $products, $query_args['orderby'], $query_args['order'] );

		if ( $products ) {
			$query_args['post__in']   = array_map(
				function ( $p ) {
					return $p->get_id();
				},
				$products
			);
			$query_args['meta_query'] = [];
			$query_args['tax_query']  = [];
			$query_args['orderby']    = 'post__in';
		} else {
			$query_args['orderby'] = 'rand';
		}
	}

	/**
	 * Set the query args for the recently viewed products.
	 *
	 * @see woocommerce/includes/widgets/class-wc-widget-recently-viewed.php
	 *
	 * @param array $query_args WP_Query args.
	 */
	protected function set_recently_viewed_query_args( &$query_args ) {
		if ( 'recently_viewed' !== $this->get_att( $this->query_prefix . 'post_type' ) ) {
			return;
		}

		$viewed_products = array_reverse( Recently_Viewed_Products::get() );

		if ( empty( $viewed_products ) ) {
			$viewed_products[] = 0;
		}

		$query_args = array_merge(
			$query_args,
			[
				'post__in' => $viewed_products,
				'orderby'  => 'post__in',
			]
		);

		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$query_args['tax_query']['relation'] = 'AND';
			$query_args['tax_query'][]           = [
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => 'outofstock',
				'operator' => 'NOT IN',
			]; // WPCS: slow query ok.
		}
	}

	/**
	 * @param array $query_args WP_Query args.
	 */
	protected function apply_query_filters( array &$query_args ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['orderby'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$ordering_args = $this->get_ordering_args( wc_clean( (string) wp_unslash( $_GET['orderby'] ) ) );

			if ( $ordering_args['orderby'] === 'price' ) {
				$ordering_args['order'] = $ordering_args['order'] ?: 'ASC';
			}

			$query_args['orderby'] = $ordering_args['orderby'];
			$query_args['order']   = $ordering_args['order'] ?: 'DESC';
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$term = isset( $_GET['term'] ) ? wc_clean( (string) wp_unslash( $_GET['term'] ) ) : null;

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$taxonomy = isset( $_GET['taxonomy'] ) ? wc_clean( (string) wp_unslash( $_GET['taxonomy'] ) ) : null;

		if ( $term && $taxonomy && is_object_in_taxonomy( $query_args['post_type'], $taxonomy ) ) {
			$query_args['tax_query']['relation'] = 'AND';
			$query_args['tax_query'][]           = [
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => [ $term ],
				'operator' => 'IN',
			];
		}

		// phpcs:enable

		return $query_args;
	}

	/**
	 * @param  string $orderby  Orderby.
	 * @param  string $order    Default order. Optional.
	 *
	 * @return string[]
	 */
	protected function get_ordering_args( $orderby, $order = '' ) {
		$orderby_value = explode( '-', (string) $orderby );

		return [
			'orderby' => esc_attr( $orderby_value[0] ),
			'order'   => strtoupper( empty( $orderby_value[1] ) ? $order : $orderby_value[1] ),
		];
	}

	protected function get_posts_offset() {
		return (int) $this->get_att( 'posts_offset', 0 );
	}

	public function get_posts_per_page( $pagination_mode, $settings ) {
		$settings = wp_parse_args(
			$settings,
			[
				'dis_posts_total'   => -1,
				'st_posts_per_page' => -1,
				'jsp_posts_total'   => -1,
				'jsm_posts_total'   => -1,
				'jsl_posts_total'   => -1,
			]
		);

		$max_posts_per_page = 99999;
		switch ( $pagination_mode ) {
			case 'disabled':
				$posts_per_page = $settings['dis_posts_total'];
				break;
			case 'standard':
				$posts_per_page = $settings['st_posts_per_page'] ?: get_option( 'posts_per_page' );
				break;
			case 'js_pagination':
				$posts_per_page = $settings['jsp_posts_total'];
				break;
			case 'js_more':
				$posts_per_page = $settings['jsm_posts_total'];
				break;
			case 'js_lazy_loading':
				$posts_per_page = $settings['jsl_posts_total'];
				break;
			default:
				return $max_posts_per_page;
		}

		$posts_per_page = (int) $posts_per_page;
		if ( $posts_per_page === -1 || ! $posts_per_page ) {
			return $max_posts_per_page;
		}

		return $posts_per_page;
	}

	/**
	 * Add offset to the posts query.
	 *
	 * @param WP_Query $query
	 *
	 * @since 1.15.0
	 */
	public function add_offset( $query ) {
		$offset  = $this->get_posts_offset();
		$ppp     = (int) $query->query_vars['posts_per_page'];
		$current = (int) $query->query_vars['paged'];

		if ( $query->is_paged ) {
			$page_offset = $offset + ( $ppp * ( $current - 1 ) );
			$query->set( 'offset', $page_offset );
		} else {
			$query->set( 'offset', $offset );
		}
	}

	/**
	 * Fix pagination accordingly with posts offset.
	 *
	 * @param int $found_posts
	 *
	 * @return int
	 */
	public function fix_pagination( $found_posts ) {
		return $found_posts - $this->get_posts_offset();
	}

	/**
	 * Add necessary pre-query hooks. Should be used BEFORE quering with WP_Query.
	 *
	 * Include posts offset support.
	 */
	public function add_pre_query_hooks() {
		add_action( 'pre_get_posts', [ $this, 'add_offset' ], 1 );
		add_filter( 'found_posts', [ $this, 'fix_pagination' ], 1, 2 );
	}

	/**
	 * Remove pre-query hooks. Should be used AFTER quering with WP_Query.
	 *
	 * @see Products_Query::add_pre_query_hooks()
	 */
	public function remove_pre_query_hooks() {
		remove_action( 'pre_get_posts', [ $this, 'add_offset' ], 1 );
		remove_filter( 'found_posts', [ $this, 'fix_pagination' ], 1 );
		remove_filter( 'posts_clauses', [ 'WC_Shortcodes', 'order_by_rating_post_clauses' ] );
		WC()->query->remove_ordering_args();
	}
}
