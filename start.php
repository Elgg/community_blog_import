<?php

namespace Community\Blog\Import;

const PLUGIN_ID = 'community_blog_import';

elgg_register_event_handler('init', 'system', __NAMESPACE__ . '\\init');

function init() {
	elgg_register_plugin_hook_handler('csv_process', 'callbacks', __NAMESPACE__ . '\\callbacks');
}

/**
 * register our function for processing the csv
 * 
 * @param type $hook
 * @param type $type
 * @param array $return
 * @param type $params
 * @return type
 */
function callbacks($hook, $type, $return, $params) {   
    $return[__NAMESPACE__ . '\\import_blogs'] = elgg_echo('community_blog_import:import_blogs');
    return $return;
}


/**
 * get owner guid from old owner username
 * 
 * @param type $username
 * @return type
 */
function get_owner_guid($username) {
	// owner map
	$map = array(
		'matt' => 'Beck24',
		'evan' => 'ewinslow',
		'brett' => 'brett.profitt',
		'cash' => 'costelloc',
		'juho' => 'juho.jaakkola',
		'pawel' => 'srokap',
		'steve' => 'steve_clay',
		'dave' => 'davetosh',
		'nologin' => 'bwerdmuller',
		'nologin2' => 'marcus',
		'nologin3' => 'pete',
		'nick' => 'nickw',
		'ravindra' => 'blacktooth'
	);
	
	$user = false;
	if ($map[$username]) {
		$user = get_user_by_username($map[$username]);
	}
	
	if ($user) {
		return $user->guid;
	}
	
	return elgg_get_logged_in_user_guid();
}

/**
 * import blogs
 * 
 * @param type $data
 */
function import_blogs($params) {
	static $skipped;

    // the $params['last'] flag indicates that there are is no more data
    // this can be used to log any final tallies or information
    // when the 'last' flag is true data will be an empty array
    if ($params['last']) {
		$skipped = (int) $skipped;
        return "{$params['line']} lines processed, {$skipped} skipped blogs";
    }
	
	$count = elgg_get_entities_from_metadata(array(
		'type' => 'object',
		'subtype' => 'blog',
		'metadata_name_value_pairs' => array(
			'name' => '__community_blog_import_guid',
			'value' => $params['data'][0]
		),
		'count' => true
	));

	if ($count) {
		// this has already been imported
		$skipped++;
		return "Skipping previously imported blog";
	}

	$owner_guid = get_owner_guid($params['data'][1]);

    $blog = new \ElggBlog();
	$blog->owner_guid = $owner_guid;
	$blog->container_guid = $owner_guid;
	$blog->access_id = $params['data'][2];
	$blog->title = $params['data'][3];
	$blog->description = $params['data'][4] . $params['data'][5];
	$blog->tags = string_to_tag_array($params['data'][7]);
	$blog->__community_blog_import_guid = $params['data'][0]; // old guid
	$blog->comments_on = 'On';

    $blog->save();
	
	$blog->time_created = $params['data'][6];
	$blog->save();

}