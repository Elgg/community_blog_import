<?php

namespace Community\Blog\Import;

const PLUGIN_ID = 'community_blog_import';
const BLOG_GROUP = 559729;


function importblogs() {
	set_time_limit(0);
	
	// get our discussions
	$options = array(
		'type' => 'object',
		'subtype' => 'groupforumtopic',
		'container_guid' => BLOG_GROUP,
		'limit' => 1
	);
	// inc_offset = false because we're changing query results
	//$discussions = new \ElggBatch('elgg_get_entities', $options, null, 25, false);
	$discussions = elgg_get_entities($options);
	
	$dbprefix = elgg_get_config('dbprefix');
	$blog_id = get_subtype_id('object', 'blog');
	$reply_id = get_subtype_id('object', 'discussion_reply');
	$comment_id = get_subtype_id('object', 'comment');
	foreach ($discussions as $d) {
		$d->container_guid = $d->owner_guid; // pull it out of the group
		$d->comments_on = 'On';
		$d->status = 'published';
		$d->save();
		
		$sql = "UPDATE {$dbprefix}entities SET subtype = {$blog_id} WHERE guid = {$d->guid}";
		update_data($sql);
		
		// now convert all responses to comments
		$sql = "UPDATE {$dbprefix}entities SET subtype = {$comment_id} WHERE type = 'object' AND subtype = {$reply_id} AND container_guid = {$d->guid}";
		update_data($sql);
	}
}