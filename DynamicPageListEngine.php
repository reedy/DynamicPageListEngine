<?php

if( !defined( 'MEDIAWIKI' ) ) {
	echo( "This is an extension to the MediaWiki package and cannot be run standalone.\n" );
	die( -1 );
}

/**
 * @brief MediaWiki extension providing dynamic page lists to
 * Scribunto and to other extensions, with configurable and extensible
 * features.
 *
 * @defgroup Extensions-DynamicPageListEngine DynamicPageListEngine extension
 *
 * @ingroup Extensions
 *
 * To activate this extension, put the source files into
 * `$IP/extensions/DynamicPageListEngine` and add the following into your
 * `LocalSettings.php` file:
 *
 * @code
 * require_once("$IP/extensions/DynamicPageListEngine/DynamicPageListEngine.php");
 * @endcode
 *
 * You can customize @ref $wgDpleMaxCost, @ref $wgDpleMaxResultCount,
 * @ref $wgDpleFeatures, @ref $wgDpleCondCostMap, @ref
 * $wgDpleOrderCostMap and the @ref Dple.i18n.php "messages".
 *
 * @version 1.0.0
 *
 * @copyright [GPL-3.0+](https://gnu.org/licenses/gpl-3.0-standalone.html)
 *
 * @author [RV1971](http://www.mediawiki.org/wiki/User:RV1971)
 *
 * @sa User documentation:
 * - [on mediawiki.org](http://www.mediawiki.org/wiki/Extension:DynamicPageListEngine)
 *
 * @sa Related extensions:
 * - [Extension:DynamicPageList (Wikimedia)](https://www.mediawiki.org/wiki/Extension:DynamicPageList_(Wikimedia%29)
 * - [Extension:DynamicPageList (third-party)](https://www.mediawiki.org/wiki/Extension:DynamicPageList_(third-party%29)
 * - [Extension:Scribunto](https://www.mediawiki.org/wiki/Extension:Scribunto)
 *
 * @sa [MediaWiki Manual](http://www.mediawiki.org/wiki/Manual:Contents):
 * - [Developing extensions]
 * (http://www.mediawiki.org/wiki/Manual:Developing_extensions)
 * - [Hooks](http://www.mediawiki.org/wiki/Manual:Hooks)
 * - [Messages API](http://www.mediawiki.org/wiki/Manual:Messages_API)
 * - [Database access](http://www.mediawiki.org/wiki/Manual:Database access)
 * - [Profiling](http://www.mediawiki.org/wiki/Manual:How_to_debug#Profiling)
 *
 * @sa [Semantic Versioning](http://semver.org)
 */

/**
 * @brief Setup for the @ref Extensions-DynamicPageListEngine.
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
 * @brief Maxiumum cost of a dynamic page list in terms of database
 * load.
 *
 * Default NULL means that it is set to the corresponding value for
 * [Extension:DynamicPageList](https://www.mediawiki.org/wiki/Extension:DynamicPageList_(Wikimedia%29#Setup).
 */
$wgDpleMaxCost = null;

/**
 * @brief Maxiumum number of records to fetch.
 *
 * Default NULL means that it is set to the corresponding value for
 * [Extension:DynamicPageList](https://www.mediawiki.org/wiki/Extension:DynamicPageList_(Wikimedia%29#Setup).
 */
$wgDpleMaxResultCount = null;

/**
 * @brief Features to enable.
 *
 * This is an array of classes dervied from DpleFeature.
 *
 * The default value corresponds to the features of
 * [Extension:DynamicPageList](https://www.mediawiki.org/wiki/Extension:DynamicPageList_(Wikimedia%29)
 * plus `notnamespace`. You should *always* include
 * DpleFeatureCount (or a class doing something
 * similar), otherwise there is no limit to the number of fetched
 * records, and DpleFeatureCheck (or a class doing
 * something similar), otherwise there is no limit on the complexity of
 * the query.
 *
 * You may uncomment some or all of the features which are commented
 * out. You may also derive other classes from
 * DpleFeature and add them to the array.
 *
 * The order of entries can be significant since the
 * (DpleFeature::modifyQuery() method of a feature
 * may (in rare cases) override a setting by a previous one.
 */
$wgDpleFeatures = array(
	'DpleFeatureNamespace',
	'DpleFeatureCategory',
	'DpleFeatureRedirects',
	// 'DpleFeatureTitlematch',
	// 'DpleFeatureSubpages',
	// 'DpleFeatureLinksto',
	// 'DpleFeatureLinksfrom',
	// 'DpleFeatureUses',
	// 'DpleFeatureUsedby',
	// 'DpleFeatureImageused',
	// 'DpleFeatureImagecontainer',
	// 'DpleFeatureContains',
	// 'DpleFeatureExtra',
	// 'DpleFeatureUser',
	'DpleFeatureCount',
	'DpleFeatureOrder',
	'DpleFeatureCheck',
	'DpleFeatureResults',
);

/**
 * @brief Cost of conditions in terms of database load.
 *
 * A unit of 1 should correspond to an efficient table join. You can
 * tune this map to reflect the real cost on your particular
 * installation and/or prevent users from excessive use of certain
 * features. Any conditions not mentioned in this map are assigned a
 * cost of 0.
 */
$wgDpleCondCostMap = array(
	/* Selection by `namespace` is efficient and may considerably
	 * restrict the result set, hence it is given a negative cost. */
	'DpleFeatureNamespace' => -0.2,

	/* Selection by `category` means one joined table per category. */
	'DpleFeatureCategory' => 1,

	/* Alternatives for title matches are put together with OR
	 * operators, which is typcially slow. Therefore, the cost is
	 * accounted once for each alternative. As such, the title match
	 * does not involve joins and is therefore considered cheap. */
	'DpleFeatureTitlematch' => 0.3,

	/* Selection by `linksto` means one joined table per condition. */
	'DpleFeatureLinksto' => 1,

	/* Selection by `linksfrom` means one joined table per condition,
	 * but the join is less efficient than for `linksto`, hence the
	 * default cost is higher than 1. */
	'DpleFeatureLinksfrom' => 1.3,

	/* Selection by template use means one joined table per
	 * condition. */
	'DpleFeatureUses' => 1,

	/* Selection by being trancluded means one joined table per
	 * condition, but the join is less efficient than for `uses`,
	 * hence the default cost is higher than 1. */
	'DpleFeatureUsedby' => 1.3,

	/* Selection by `imageused` means one joined table per condition. */
	'DpleFeatureImageused' => 1,

	/* Selection by `imagecontainer` means one joined table per condition,
	 * but the join is less efficient than for `imageused`,
	 * hence the default cost is higher than 1. */
	'DpleFeatureImagecontainer' => 1.3,

	/* Selection by `contains` means one joined table per condition,
	 * but the join is less efficient than for `category`,
	 * hence the default cost is higher than 1. */
	'DpleFeatureContains' => 1.3,

	/* Alternatives for extra information matches are put together
	 * with OR operators, which is typcially slow. Therefore, the cost
	 * is accounted once for each alternative. As such, this match
	 * does not involve joins and is therefore considered cheap. */
	'DpleFeatureExtra' => 0.3,

	/* Selection by user means one joined table per condition. */
	'DpleFeatureUser' => 1,

	/* Selection by `modified` means one joined table per condition,
	 *	but the record sets selected from the joined tables may be
	 *	very large, unlike selection by `lastmodified`,
	 * hence the default cost is higher than 1. */
	'DpleFeatureUserExpensive' => 2
);

/**
 * @brief Cost of order methods in terms of database load.
 *
 * See @ref $wgDpleCondCostMap for an
 * explanation. Any order methods not mentioned in this map are
 * assigned a cost of 0.
 */
$wgDpleOrderCostMap = array(
	/* `categorysortkey` consists of two fields. */
	'categorysortkey' => 0.1,

	/* `title` needs to run a replace function on each record. */
	'title' => 0.1
);

/// [About](http://www.mediawiki.org/wiki/$wgExtensionCredits) this extension.
$wgExtensionCredits['other'][] =
	array(
		'path' => __FILE__,
		'name' => 'DynamicPageListEngine',
		'descriptionmsg' => 'dynamicpagelistengine-desc',
		'version' => '1.0.0',
		'author' => '[http://www.mediawiki.org/wiki/User:RV1971 RV1971]',
		'url' => 'http://www.mediawiki.org/wiki/Extension:DynamicPageListEngine'
	);

/**
 * @brief [Autoloading]
 * (http://www.mediawiki.org/wiki/Manual:$wgAutoloadClasses) the main class
 */
$wgAutoloadClasses['DynamicPageListEngine'] =
	__DIR__ . '/DynamicPageListEngine.body.php';

/** @cond IGNORE */
foreach ( array(
		'DpleFeatureBase',
		'DpleFeatureInterface',
		'DpleFeatureCategory',
		'DpleFeatureCheck',
		'DpleFeatureContains',
		'DpleFeatureCount',
		'DpleFeatureExtra',
		'DpleFeatureExtrax',
		'DpleFeatureImagecontainer',
		'DpleFeatureImageused',
		'DpleFeatureLinksBase',
		'DpleFeatureLinksto',
		'DpleFeatureLinksfrom',
		'DpleFeatureNamespace',
		'DpleFeatureOrder',
		'DpleFeatureRedirects',
		'DpleFeatureResults',
		'DpleFeatureSubpages',
		'DpleFeatureTitlematch',
		'DpleFeatureUser',
		'DpleFeatureUses',
		'DpleFeatureUsedby',
		'DpleQuery',
		'Scribunto_LuaDynamicPageListEngineLibrary'
	) as $class ) {
	/** @endcond */

	/**
	 * @brief [Autoloading]
	 * (http://www.mediawiki.org/wiki/Manual:$wgAutoloadClasses)
	 * further classes.
	 *
	 * The list of classes for this cannot be derived from @ref
	 * $wgDpleFeatures, not even after processing
	 * LocalSettings.php, because the latter could contain classes
	 * contributed by other extensions and therefore stored in
	 * different directories.
	 */

	$wgAutoloadClasses[$class] = __DIR__ . "/$class.php";
}

/**
 * @brief [ScribuntoExternalLibraries]
 * (https://www.mediawiki.org/wiki/Extension:Scribunto/Lua_reference_manual#Library) hook.
 */
$wgHooks['ScribuntoExternalLibraries'][] =
	'Scribunto_LuaDynamicPageListEngineLibrary::onScribuntoExternalLibraries';

/// [Localisation](https://www.mediawiki.org/wiki/Localisation_file_format).
$wgMessagesDirs['DynamicPageListEngine'] = __DIR__ . '/i18n';

/**
 * @brief Old-style [Localisation]
 * (http://www.mediawiki.org/wiki/Localisation) file for MW 1.19 compatibility.
 */
$wgExtensionMessagesFiles['DynamicPageListEngine'] =
	__DIR__ . '/DynamicPageListEngine.i18n.php';
?>

