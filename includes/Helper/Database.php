<?php
namespace YayExtra\Helper;

/**
 * Main class of plugin
 *
 * @class Database
 */
class Database {

	protected $filters = array();
	protected $apply   = 'any';
	protected $get_all = true;
	protected $limit   = 10;
	protected $offset  = 1;
	protected $params = array();

	/**
	 * Create query for product name filter
	 *
	 * @param object $filter Product name filter.
	 *
	 * @return string
	 */
	public static function get_product_name_query( $filter ) {
		global $wpdb;
		$comparation         = 'is_one_of' === $filter['comparation']['value'] ? 'IN' : 'NOT IN';
		$array_values        = array_map(
			function( $member ) {
				return "'{$member['label']}'";
			},
			$filter['value']
		);
		$string_array_values = join( ',', $array_values );

		$query = '';
		if ( ! empty( $string_array_values ) ) {
			$query = "{$wpdb->prefix}posts.post_title {$comparation} ({$string_array_values})";
		}

		return $query;
	}

	/**
	 * Create query for product id filter
	 *
	 * @param object $filter Product id filter.
	 *
	 * @return string
	 */
	public static function get_product_id_query( $filter ) {
		global $wpdb;
		$comparation         = 'is_one_of' === $filter['comparation']['value'] ? 'IN' : 'NOT IN';
		$array_values        = array_map(
			function( $member ) {
				return (int) $member['value'];
			},
			$filter['value']
		);
		$int_array_values = join( ',', $array_values );

		$query = '';
		if ( ! empty( $int_array_values ) ) {
			$query = "{$wpdb->prefix}posts.ID {$comparation} ({$int_array_values})";
		}

		return $query;
	}

	/**
	 * Create query for product category filter
	 *
	 * @param object $filter Product category filter.
	 *
	 * @return string
	 */
	public static function get_product_category_query( $filter ) {
		$comparation         = 'is_one_of' === $filter['comparation']['value'] ? 'IN' : 'NOT IN';
		$array_values        = array_map(
			function( $member ) {
				return "'{$member['label']}'";
			},
			$filter['value']
		);
		$string_array_values = join( ',', $array_values );
		$query               = '';
		if ( ! empty( $string_array_values ) ) {
			$query = "( term_taxonomy.taxonomy = 'product_cat' AND terms.name {$comparation} ({$string_array_values}) )";
		}

		return $query;
	}

	/**
	 * Create query for product tag filter
	 *
	 * @param object $filter Product tag filter.
	 *
	 * @return string
	 */
	public static function get_product_tag_query( $filter ) {
		global $wpdb;
		$comparation         = 'is_one_of' === $filter['comparation']['value'] ? 'IN' : 'NOT IN';
		$array_values        = array_map(
			function( $member ) {
				return "'{$member['label']}'";
			},
			$filter['value']
		);
		$string_array_values = join( ',', $array_values );

		$query = '';
		if ( ! empty( $string_array_values ) ) {
			$query = "( {$wpdb->prefix}posts.ID IN (
        SELECT term_relationships.object_id as id
        FROM {$wpdb->prefix}term_relationships AS term_relationships
        JOIN {$wpdb->prefix}term_taxonomy AS term_taxonomy ON term_relationships.term_taxonomy_id = term_taxonomy.term_taxonomy_id
        JOIN {$wpdb->prefix}terms AS terms ON terms.term_id = term_taxonomy.term_id				
       
        WHERE ( term_taxonomy.taxonomy = 'product_tag' AND terms.name {$comparation} ({$string_array_values}) )
        GROUP BY term_relationships.object_id
      ))";
		}

		return $query;
	}

	/**
	 * Create query for product price filter
	 *
	 * @param object $filter Product price filter.
	 *
	 * @return string
	 */
	public static function get_product_price_query( $filter ) {
		switch ( $filter['comparation']['value'] ) {
			case 'equal':
				$comparation = '=';
				break;
			case 'not_equal':
				$comparation = '<>';
				break;
			case 'greater_than':
				$comparation = '>';
				break;
			case 'less_than':
				$comparation = '<';
				break;
			default:
				$comparation = '=';
		}

		$query = "( postmeta.meta_key = '_price' AND postmeta.meta_value {$comparation} {$filter['value']} )";
		return $query;
	}

	/**
	 * Create query for product in stock filter
	 *
	 * @param object $filter Product in stock filter.
	 *
	 * @return string
	 */
	public static function get_product_in_stock_query( $filter ) {
		switch ( $filter['comparation']['value'] ) {
			case 'equal':
				$comparation = '=';
				break;
			case 'not_equal':
				$comparation = '<>';
				break;
			case 'greater_than':
				$comparation = '>';
				break;
			case 'less_than':
				$comparation = '<';
				break;
			default:
				$comparation = '=';
		}

		$is_out_of_stock = false;

		if ( 0 == $filter['value'] && ( '=' === $comparation ) ) {
			$is_out_of_stock = true;
		}

		if ( in_array( $filter['value'], array( 0, 1 ), true ) && ( '<' === $comparation ) ) {
			$is_out_of_stock = true;
		}

		if ( $is_out_of_stock ) {
			$query = "( stock_status = 'outofstock' )";
		} else {
			$query = "( stock_quantity {$comparation} {$filter['value']} OR ( stock_quantity IS NULL AND stock_status = 'instock' ) )";
		}

		return $query;
	}

	public function parse_filters_to_query( $filter ) {
		$query = ' FALSE';
		if ( 'prod_name' === $filter['type']['value'] ) {
			$query = self::get_product_id_query( $filter );
		}
		if ( 'prod_category' === $filter['type']['value'] ) {
			$query = self::get_product_category_query( $filter );
		}
		if ( 'prod_tag' === $filter['type']['value'] ) {
			$query = self::get_product_tag_query( $filter );
		}

		return " {$query}";
	}

	public function get_where_clause() {

		if ( ! isset( $this->filters ) ) {
			if ( isset( $this->params ) ) {
				return ' TRUE';
			}
			return ' FALSE';
		}

		$filters = $this->filters;
		$query   = '';
		foreach ( $filters as $key => $filter ) {
			if ( 0 < $key ) {
				$query .= ' OR';
			}
			$query .= $this->parse_filters_to_query( $filter );
		}
		return "( {$query} )";
	}

	public function get_join_clause() {
		global $wpdb;
		$query  = "JOIN {$wpdb->prefix}postmeta AS postmeta ON {$wpdb->prefix}posts.ID = postmeta.post_id";
		$query .= " LEFT JOIN {$wpdb->prefix}wc_product_meta_lookup AS wc_product_meta_lookup ON {$wpdb->prefix}posts.ID = wc_product_meta_lookup.product_id";
		$query .= " LEFT JOIN {$wpdb->prefix}term_relationships AS term_relationships ON term_relationships.object_id = {$wpdb->prefix}posts.ID";
		$query .= " JOIN {$wpdb->prefix}term_taxonomy AS term_taxonomy ON term_relationships.term_taxonomy_id = term_taxonomy.term_taxonomy_id";
		$query .= " JOIN {$wpdb->prefix}terms AS terms ON terms.term_id = term_taxonomy.term_id";
		return $query;
	}

	public function get_search_query() {

		if ( ! isset( $this->params ) ) {
			return ' TRUE';
		}

		global $wpdb;
		$params = $this->params;

		$product_name = ! empty( $params['product_name'] ) ? $params['product_name'] : '';
		$category_id  = ! empty( $params['category_id'] ) ? (int) $params['category_id'] : '';
		$tag_id       = ! empty( $params['tag_id'] ) ? (int) $params['tag_id'] : '';

		$option_set_id = ! empty( $params['option_set_id'] ) ? (int) $params['option_set_id'] : '';
		$product_type  = ! empty( $params['product_type'] ) ? $params['product_type'] : 'all'; // all, assigned, unassigned.

		$product_filter_one_by_one = array();
		if ( ! empty( $option_set_id ) ) {
			$products_option_set       = get_post_meta( $option_set_id, '_yaye_products', true );
			$product_filter_one_by_one = $products_option_set['product_filter_one_by_one'];
		}
		$string_product_filter_one_by_one = join( ',', $product_filter_one_by_one );

		$search_product_type_where = 'TRUE';
		$search_product_name_where = 'TRUE';
		$search_category_where     = 'TRUE';
		$search_tag_where          = 'TRUE';

		if ( 'assigned' === $product_type ) {
			if ( '' === $string_product_filter_one_by_one ) {
				$search_product_type_where = 'FALSE';
			} else {
				$search_product_type_where = "( {$wpdb->prefix}posts.ID IN ({$string_product_filter_one_by_one}) )";
			}
		} elseif ( 'unassigned' === $product_type && '' !== $string_product_filter_one_by_one ) {
			$search_product_type_where = "( {$wpdb->prefix}posts.ID NOT IN ({$string_product_filter_one_by_one}) )";
		}

		if ( ! empty( $product_name ) ) {
			$search_product_name_where = "( {$wpdb->prefix}posts.post_title LIKE '%{$product_name}%' )";
		};

		if ( ! empty( $category_id ) ) {
			$search_category_where = "( term_taxonomy.taxonomy = 'product_cat' AND terms.term_id = {$category_id}  )";
		};

		if ( ! empty( $tag_id ) ) {
			$search_tag_where = "( {$wpdb->prefix}posts.ID IN (
        SELECT term_relationships.object_id as id
        FROM {$wpdb->prefix}term_relationships AS term_relationships
        JOIN {$wpdb->prefix}term_taxonomy AS term_taxonomy ON term_relationships.term_taxonomy_id = term_taxonomy.term_taxonomy_id
        JOIN {$wpdb->prefix}terms AS terms ON terms.term_id = term_taxonomy.term_id
        WHERE ( term_taxonomy.taxonomy = 'product_tag' AND terms.term_id = {$tag_id} )
        GROUP BY term_relationships.object_id
      ))";
		};

		$search_where = $search_product_type_where . ' AND ' . $search_product_name_where . ' AND ' . $search_category_where . ' AND ' . $search_tag_where;
		return $search_where;
	}

	public function posts_clauses( $args, $wp_query ) {
		global $wpdb;

		$args['fields']  = "{$wpdb->prefix}posts.ID as id, wc_product_meta_lookup.stock_quantity, wc_product_meta_lookup.stock_status";
		$args['join']    = $this->get_join_clause();
		$args['where']   = " AND {$wpdb->prefix}posts.post_type IN ('product') AND {$wpdb->prefix}posts.post_status = 'publish' AND {$this->get_where_clause()} AND {$this->get_search_query()}";
		$args['groupby'] = "{$wpdb->prefix}posts.ID";
		$args['orderby'] = 'post_title';
		$args['limits']  = '';
		if ( ! $this->get_all ) {
			$args['limits'] = "LIMIT {$this->offset}, {$this->limit}";
		}
		return $args;
	}

	/**
	 * Get Products in database.
	 *
	 * @param object $filters Rule filters.
	 * @param object $apply Any or All conditions.
	 * @param object $params Params.
	 *
	 * @return array
	 */
	public function get_products( $filters = null, $apply = null, $params = array() ) {
		global $wpdb;
		$limit         = ! empty( $params['page_size'] ) && is_numeric( $params['page_size'] ) ? (int) $params['page_size'] : 10;
		$page          = ! empty( $params['current'] ) && is_numeric( $params['current'] ) ? (int) $params['current'] : 1;
		$offset        = ( $page - 1 ) * $limit;
		$this->filters = $filters;
		$this->params  = $params;
		$this->apply   = $apply;
		$this->limit   = $limit;
		$this->offset  = $offset;

		add_filter( 'posts_clauses', array( $this, 'posts_clauses' ), 10, 2 );
		$query            = new \WP_Query();
		$result_query_all = $query->query( array() );
		remove_filter( 'posts_clauses', array( $this, 'posts_clauses' ), 10 );
		$total_items = count( $result_query_all );

		$this->get_all = false;
		add_filter( 'posts_clauses', array( $this, 'posts_clauses' ), 10, 2 );
		$query        = new \WP_Query();
		$query_result = $query->query( array() );
		remove_filter( 'posts_clauses', array( $this, 'posts_clauses' ), 10 );

		$result = array(
			'product_list' => $query_result,
			'current_page' => $page,
			'total_items'  => $total_items,
		);

		return $result;
	}

	/**
	 * Get Products in database.
	 *
	 * @param object $filters Rule filters.
	 * @param object $apply Any or All conditions.
	 *
	 * @return array
	 */
	public function get_product_match_option_set_list( $filters = null, $apply = null ) {
		global $wpdb;

		$this->filters = $filters;
		$this->apply   = $apply;

		add_filter( 'posts_clauses', array( $this, 'posts_clauses' ), 10, 2 );
		$query            = new \WP_Query();
		$result_query_all = $query->query( array() );
		remove_filter( 'posts_clauses', array( $this, 'posts_clauses' ), 10 );

		return $result_query_all;
	}
}
