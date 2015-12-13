<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
	die( 1 );
}

/**
 * @brief Class DpleFeatureResults.
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
 * @brief Convert query results.
 *
 * @ingroup Extensions
 * @ingroup Extensions-DynamicPageListEngine
 */
class DpleFeatureResults extends DpleFeatureBase
implements DpleFeatureInterface {
	/* == private data members == */

	private $pagenames_; ///< Array of page names.
	private $fullpagenames_; ///< Array of full page names.
	private $titles_; ///< Array of title objects.
	private $arrays_; ///< Array of associative arrays, each representing a page.

	/* == magic methods == */

	/// Constructor. Evaluate parameters.
	public function __construct( array $params, array &$features ) {
		parent::__construct( $features );
	}

	/* == accessors == */

	/// @copydoc DpleFeatureBase::getResultConverters
	public function getResultConverters() {
		return array(
			'toPagenames',
			'toFullpagenames',
			'toTitles',
			'toArrays',
		);
	}

	/* == operations == */

	/**
	 * @brief Transform a query result to an array of page names.
	 *
	 * @param ResultWrapper $result Result of a query.
	 *
	 * @return array Array of page names.
	 */
	public function toPagenames( ResultWrapper $result ) {
		/** Use @ref $pagenames_ if already processed. */
		if ( isset( $this->pagenames_ ) ) {
			return $this->pagenames_;
		}

		/** Otherwise create it from $result. */
		$this->pagenames_ = array();

		foreach ( $result as $row ) {
			$this->pagenames_[] = strtr( $row->page_title, '_', ' ' );
		}

		return $this->pagenames_;
	}

	/**
	 * @brief Transform a query result to an array of full page names.
	 *
	 * @param ResultWrapper $result Result of a query.
	 *
	 * @return array Array of full page names.
	 */
	public function toFullpagenames( ResultWrapper $result ) {
		/** Use @ref $fullpagenames_ if already processed. */
		if ( isset( $this->fullpagenames_ ) ) {
			return $this->fullpagenames_;
		}

		/** Otherwise create it from $result. */
		$this->fullpagenames_ = array();

		global $wgContLang;

		foreach ( $result as $row ) {
			$this->fullpagenames_[] =
				$wgContLang->getNsText( $row->page_namespace ) . ':'
				. strtr( $row->page_title, '_', ' ' );
		}

		return $this->fullpagenames_;
	}

	/**
	 * @brief Transform a query result to an array of Title objects.
	 *
	 * In category tags, you can add *extra information* in the sort
	 * key, e.g. in constructs like
	 * `[[Category:...|user:{{PAGENAME}}|head of team]]`. For
	 * MediaWiki, the whole string `user:{{PAGENAME}}|head of team` is
	 * the sort key. The DynamicPageListEngine extension considers the
	 * part after the second pipe character (`head of team` in the
	 * example) as extra information.
	 *
	 * @return array Array of Title objects.
	 */
	public function toTitles( ResultWrapper $result ) {
		global $wgDisableCounters;

		/** Use @ref $titles_ if already processed. */
		if ( isset( $this->titles_ ) ) {
			return $this->titles_;
		}

		/** Otherwise create it from $result. */
		$this->titles_ = array();

		$extraFeature = $this->getFeature( 'DpleFeatureExtra' );

		$extraxFeature = $this->getFeature( 'DpleFeatureExtrax' );

		foreach ( $result as $row ) {
			$title = Title::makeTitle( $row->page_namespace,
				$row->page_title );

			if ( $row->page_is_redirect ) {
				$title->mRedirect = (bool)$row->page_is_redirect;
			}

			/** Store additional information in property `dpleCustom`:
			 * - `length` => Uncompressed length in bytes of the page's
			 * current source text.
			 * - `categoryadd` => Timestamp of addition to the first
			 * category, if any.
			 * - `counter` Page view counter, unless counters are disabled.
			 * - `sortkey`=> Sort key in first category, if any.
			 * - `extra` => Extra information given with sort key, if any.
			 */
			$title->dpleCustom = array( 'length' => $row->page_len );

			if ( !$wgDisableCounters ) {
				$title->dpleCustom['counter'] = $row->page_counter;
			}

			if( isset( $row->cl_timestamp ) ) {
				$title->dpleCustom['categoryadd'] = $row->cl_timestamp;

				if( $extraFeature ) {
					$sortkey = $row->sortkey;

					if( isset( $sortkey ) && $sortkey != '' ) {
						$title->dpleCustom['sortkey'] = $sortkey;

						if ( strpos( $sortkey, '|' ) !== FALSE ) {
							list( , $title->dpleCustom['extra'] ) =
								explode( '|', $sortkey, 2 );
						}
					}
				}
			}

			if( isset( $row->clx_timestamp ) ) {
				$title->dpleCustom['categoryaddx'] = $row->clx_timestamp;

				if( $extraxFeature ) {
					$sortkeyx = $row->sortkeyx;

					if( isset( $sortkeyx ) && $sortkeyx != '' ) {
						$title->dpleCustom['sortkeyx'] = $sortkeyx;

						if ( strpos( $sortkeyx, '|' ) !== FALSE ) {
							list( , $title->dpleCustom['extrax'] ) =
								explode( '|', $sortkeyx, 2 );
						}
					}
				}
			}

			$this->titles_[] = $title;
		}

		return $this->titles_;
	}

	/**
	 * @brief Transform a query result to an array of associative arrays.
	 *
	 * Useful for
	 * [Extension:Scribunto](https://www.mediawiki.org/wiki/Extension:Scribunto)
	 * languages like Lua where currently it is not possible to return
	 * a Title object from php to the caller.
	 *
	 * @return array Array of arrays.
	 */
	public function toArrays( ResultWrapper $result ) {
		/** Use @ref $arrays_ if already created. */
		if ( isset( $this->arrays_ ) ) {
			return $this->arrays_;
		}

		/** Otherwise create from result of toTitles(). */
		$this->arrays_ = array();

		/**
		 * @var Title $title
		 */
		foreach ( $this->toTitles( $result ) as $title ) {
			/** Extract all those Title properties which are cheap
			 * (i.e. do not require database access):
			 * - namespace
			 * - nsText
			 * - text
			 * - prefixedText
			 * - baseText
			 * - subpageText
			 * - canTalk
			 * - isContentPage
			 * - isSubpage
			 * - isTalkPage
			 * - isRedirect
			 */
			$array = array(
				'id' => $title->getArticleID(),
				'namespace' => $title->getNamespace(),
				'nsText' => $title->getNsText(),
				'text' => $title->getText(),
				'prefixedText' => $title->getPrefixedText(),
				'baseText' => $title->getBaseText(),
				'subpageText' => $title->getSubpageText(),
				'canTalk' => $title->canTalk(),
				'isContentPage' => $title->isContentPage(),
				'isRedirect' => $title->isRedirect(),
				'isSubpage' => $title->isSubpage(),
				'isTalkPage' => $title->isTalkPage()
			);

			/** Add the contents of `dpleCustom` (see  toTitles()). */
			$array += $title->dpleCustom;

			$this->arrays_[] = $array;
		}

		return $this->arrays_;
	}
}
