<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
	die( 1 );
}

/**
 * @brief Class DpleFeatureNamespace.
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
 * @brief Selection by namespace.
 *
 * Recognizes the parameters `namespace` and `notnamespace` which may
 * be a namespace name or index or an array thereof. Any invalid
 * value, including the empty string, is interpreted as the main
 * namespace, for compatibility with
 * [Extension:DynamicPageList](https://www.mediawiki.org/wiki/Extension:DynamicPageList_(Wikimedia%29).
 *
 * If `namespace` is an array, the result is (obviously) the union of
 * the record sets satisfying its elements, unlike other parameters
 * (including `notnamespace`) where the result is the intersection.
 *
 * @ingroup Extensions
 * @ingroup Extensions-DynamicPageListEngine
 */
class DpleFeatureNamespace extends DpleFeatureBase
implements DpleFeatureInterface {
	/* == private variables == */

	/** @brief Array of [Namespace]
	 * (http://www.mediawiki.org/wiki/Help:Namespace) indexes to
	 * choose from.*/
	private $namespaces_;

	/** @brief Array of [Namespace]
	 * (http://www.mediawiki.org/wiki/Help:Namespace) indexes to
	 * exclude.*/
	private $notNamespaces_;

	/* == magic methods == */

	/// Constructor. Evaluate parameters.
	public function __construct( array $params, array &$features ) {
		parent::__construct( $features );

		if ( isset( $params['namespace'] ) ) {
			$this->namespaces_ =
				array_map( array( $this, 'parseNamespace' ),
					(array)$params['namespace'] );
		}

		if ( isset( $params['notnamespace'] ) ) {
			$this->notNamespaces_ =
				array_map( array( $this, 'parseNamespace' ),
					(array)$params['notnamespace'] );
		}
	}

	/* == accessors == */

	/// Get @ref $namespaces_.
	public function getNamespaces() {
		return $this->namespaces_;
	}

	/// Get @ref $notNamespaces_.
	public function getNotNamespaces() {
		return $this->notNamespaces_;
	}

	/// Get the database cost generated by this feature instance.
	public function getCost() {
		return isset( $this->namespaces_ )
			|| isset( $this->notNamespaces_ )
			? parent::getCost() : 0;
	}

	/* == operations == */

	/// Modify a given query. @copydetails DpleFeatureBase::modifyQuery()
	public function modifyQuery( DpleQuery &$query ) {
		switch ( count( $this->namespaces_ ) ) {
			case 0:
				break;

			case 1:
				$query->addConds( "page_namespace = {$this->namespaces_[0]}" );
				break;

			default:
				$query->addConds( 'page_namespace IN ('
					. implode( ',', $this->namespaces_ ) . ')' );
		}

		switch ( count( $this->notNamespaces_ ) ) {
			case 0:
				break;

			case 1:
				$query->addConds(
					"page_namespace != {$this->notNamespaces_[0]}" );
				break;

			default:
				$query->addConds( 'page_namespace NOT IN ('
					. implode( ',', $this->notNamespaces_ ) . ')' );
		}
	}
}
