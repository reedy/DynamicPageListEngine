<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
	die( 1 );
}

/**
 * @brief Class DpleFeatureExtra.
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
 * @brief Selection by extra information.
 *
 * Recognizes the parameters `extra` and `notextra`. `extra` selects
 * exact matches of the extra information appended to the sort key
 * (see documentation of
 * DpleFeatureResults::toTitles()) for the first
 * selected category. `notextra` select the complement of this. If no
 * categories are selected, the parameters are silently ignored.
 *
 * If `extra` is an array, the result is
 * (obviously) the union of the record sets satisfying its elements,
 * unlike other parameters (including `notextra`) where the
 * result is the intersection.
 *
 * @ingroup Extensions
 * @ingroup Extensions-DynamicPageListEngine
 */
class DpleFeatureExtra extends DpleFeatureBase
implements DpleFeatureInterface {
	/* == private variables == */

	/** @brief Extra information pages to select should provide with
	 * the category tag for the first category. */
	private $extras_;

	/** @brief Extra information pages to select should not provide
	 * with the category tag for the first category. */
	private $notExtras_;

	/* == magic methods == */

	/// Constructor. Evaluate parameters.
	public function __construct( array $params, array &$features ) {
		parent::__construct( $features );

		if ( isset( $params['extra'] ) ) {
			$this->extras_ = array_map( array( $this, 'parseText' ),
				(array)$params['extra'] );
		}

		if ( isset( $params['notextra'] ) ) {
			$this->notExtras_ = array_map( array( $this, 'parseText' ),
				(array)$params['notextra'] );
		}
	}

	/* == accessors == */

	/// Get @ref $extras_.
	public function getExtras() {
		return $this->extras_;
	}

	/// Get @ref $notExtras_.
	public function getNotExtras() {
		return $this->notExtras_;
	}

	/// Get the database cost generated by this feature instance.
	public function getCost() {
		/** If @ref $extras_ is an array, its items are put together
		 *	with OR operators, which is typcially slow, therefore one
		 *	unit of @ref DpleFeature::getCost() is
		 *	accounted for each item. Items in @ref $notExtras_ instead
		 *	are put together with AND, which is typically efficient,
		 *	and therefore only one unit is accounted for the whole
		 *	array, if any. */
		return (count( $this->extras_ ) + (int)(bool)$this->notExtras_)
			* parent::getCost();
	}

	/* == operations == */

	/// Modify a given query. @copydetails DpleFeatureBase::modifyQuery()
	public function modifyQuery( DpleQuery &$query ) {
		$categoryFeature = $this->getFeature( 'DpleFeatureCategory' );

		/** Do nothing if no category was defined. */
		if ( !isset( $categoryFeature )
			|| !$categoryFeature->getLinkedCount() ) {
			return;
		}

		$dbr = $query->getDbr();

		/** Otherwise, also fetch the sort key for the first
		 *	category. */
		$query->addVars( array( 'sortkey' => 'cl1.cl_sortkey_prefix' ) );

		if ( $this->extras_ ) {
			$extraConds = array();

			/** Add conditions based on @ref $extras_. */
			foreach ( $this->extras_ as $extra ) {
				$extraConds[] = 'cl1.cl_sortkey_prefix'
					. $dbr->buildLike( $dbr->anyString(), "|$extra" );
			}

			$query->addConds( '(' . implode( ' OR ', $extraConds ) . ')' );
		}

		/** Add conditions based on @ref $notExtras_. */
		foreach ( (array)$this->notExtras_ as $extra ) {
			$query->addConds( 'cl1.cl_sortkey_prefix NOT '
				. $dbr->buildLike( $dbr->anyString(), "|$extra" ) );
		}
	}
}
?>