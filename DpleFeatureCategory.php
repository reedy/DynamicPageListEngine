<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
	die( 1 );
}

/**
 * @brief Class DpleFeatureCategory.
 *
 * @file
 *
 * @ingroup Extensions
 * @ingroup Extensions-DynamicPageListEngine
 *
 * @author [RV1971](http://www.mediawiki.org/wiki/User:RV1971)
 *
 */

/**
 * @brief Selection by category.
 *
 * Recognizes the parameters `category` and `notcategory`. Each of
 * them may be a string or an array. If `category` is an array, the
 * result is the intersection of categories.
 *
 * @ingroup Extensions
 * @ingroup Extensions-DynamicPageListEngine
 */
class DpleFeatureCategory extends DpleFeatureLinksBase 
implements DpleFeatureInterface {
	/* == magic methods == */

	/// Constructor. Evaluate parameters.
	public function __construct( array $params, array &$features ) {
		parent::__construct( $params, $features,
			'category', NS_CATEGORY,
			'categorylinks', 'cl', 'cl_to',
			array( 'page_id = $table.cl_from', '$table.cl_to = $dbkey' ) );
	}

	/* == operations == */

	/// Modify a given query. @copydetails DpleFeatureBase::modifyQuery()
	public function modifyQuery( DpleQuery &$query ) {
		parent::modifyQuery( $query );

		/** Also select timestamp if at least one category is specified. */
		if ( $this->getLinkedCount() ) {
			$query->addVars( array( 'cl_timestamp' => 'cl1.cl_timestamp' ) );
		}
	}
}
