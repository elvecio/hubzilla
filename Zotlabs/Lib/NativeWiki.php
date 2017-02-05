<?php

namespace Zotlabs\Lib;

define ( 'NWIKI_ITEM_RESOURCE_TYPE', 'nwiki' );

class NativeWiki {


	static public function listwikis($channel, $observer_hash) {

		$sql_extra = item_permissions_sql($channel['channel_id'], $observer_hash);
		$wikis = q("SELECT * FROM item 
			WHERE resource_type = '%s' AND mid = parent_mid AND uid = %d AND item_deleted = 0 $sql_extra", 
			dbesc(NWIKI_ITEM_RESOURCE_TYPE),
			intval($channel['channel_id'])
		);

		if($wikis) {
			foreach($wikis as &$w) {
				$w['rawName']  = get_iconfig($w, 'wiki', 'rawName');
				$w['htmlName'] = escape_tags($w['rawName']);
				$w['urlName']  = urlencode(urlencode($w['rawName']));
				$w['mimeType'] = get_iconfig($w, 'wiki', 'mimeType');
				$w['lock']     = (($w['item_private'] || $w['allow_cid'] || $w['allow_gid'] || $w['deny_cid'] || $w['deny_gid']) ? true : false);
			}
		}
		// TODO: query db for wikis the observer can access. Return with two lists, for read and write access
		return array('wikis' => $wikis);
	}


	function create_wiki($channel, $observer_hash, $wiki, $acl) {

		// Generate unique resource_id using the same method as item_message_id()
		do {
			$dups = false;
			$resource_id = random_string();
			$r = q("SELECT mid FROM item WHERE resource_id = '%s' AND resource_type = '%s' AND uid = %d LIMIT 1", 
				dbesc($resource_id), 
				dbesc(NWIKI_ITEM_RESOURCE_TYPE),
				intval($channel['channel_id'])
			);
			if($r)
				$dups = true;
		} while($dups == true);

		$ac = $acl->get();
		$mid = item_message_id();

		$arr = array();	// Initialize the array of parameters for the post
		$item_hidden = ((intval($wiki['postVisible']) === 0) ? 1 : 0); 
		$wiki_url = z_root() . '/wiki/' . $channel['channel_address'] . '/' . $wiki['urlName'];
		$arr['aid'] = $channel['channel_account_id'];
		$arr['uid'] = $channel['channel_id'];
		$arr['mid'] = $mid;
		$arr['parent_mid'] = $mid;
		$arr['item_hidden'] = $item_hidden;
		$arr['resource_type'] = NWIKI_ITEM_RESOURCE_TYPE;
		$arr['resource_id'] = $resource_id;
		$arr['owner_xchan'] = $channel['channel_hash'];
		$arr['author_xchan'] = $observer_hash;
		$arr['plink'] = z_root() . '/channel/' . $channel['channel_address'] . '/?f=&mid=' . urlencode($arr['mid']);
		$arr['llink'] = $arr['plink'];
		$arr['title'] = $wiki['htmlName'];  // name of new wiki;
		$arr['allow_cid'] = $ac['allow_cid'];
		$arr['allow_gid'] = $ac['allow_gid'];
		$arr['deny_cid'] = $ac['deny_cid'];
		$arr['deny_gid'] = $ac['deny_gid'];
		$arr['item_wall'] = 1;
		$arr['item_origin'] = 1;
		$arr['item_thread_top'] = 1;
		$arr['item_private'] = intval($acl->is_private());
		$arr['verb'] = ACTIVITY_CREATE;
		$arr['obj_type'] = ACTIVITY_OBJ_WIKI;
		$arr['body'] = '[table][tr][td][h1]New Wiki[/h1][/td][/tr][tr][td][zrl=' . $wiki_url . ']' . $wiki['htmlName'] . '[/zrl][/td][/tr][/table]';

		// Save the wiki name information using iconfig. This is shareable.
		if(! set_iconfig($arr, 'wiki', 'rawName', $wiki['rawName'], true)) {
			return array('item' => null, 'success' => false);
		}
		if(! set_iconfig($arr, 'wiki', 'mimeType', $wiki['mimeType'], true)) {
			return array('item' => null, 'success' => false);
		}
	
		$post = item_store($arr);

		$item_id = $post['item_id'];

		if($item_id) {
			\Zotlabs\Daemon\Master::Summon(array('Notifier', 'activity', $item_id));
			return array('item' => $post['item'], 'item_id' => $item_id, 'success' => true);
		}
		else {
			return array('item' => null, 'success' => false);
		}
	}

	static public function sync_a_wiki_item($uid,$id,$resource_id) {


		$r = q("SELECT * from item WHERE uid = %d AND ( id = %d OR ( resource_type = '%s' and resource_id = %d )) ",
			intval($uid),
			intval($id),
			dbesc(NWIKI_ITEM_RESOURCE_TYPE),
			intval($resource_id)
		);
		if($r) {
			xchan_query($r);
			$sync_item = fetch_post_tags($r);
			build_sync_packet($uid,array('wiki' => array(encode_item($sync_item[0],true))));
		}
	}

	function delete_wiki($channel_id,$observer_hash,$resource_id) {

		$w = self::get_wiki($channel_id,$observer_hash,$resource_id);
		$item = $w['wiki'];
		if(! $item) {
			return array('item' => null, 'success' => false);
		} 
		else {
			$drop = drop_item($item['id'], false, DROPITEM_NORMAL, true);
		}

		info( t('Wiki files deleted successfully'));

		return array('item' => $item, 'item_id' => $item['id'], 'success' => (($drop === 1) ? true : false));
	}


	static public function get_wiki($channel_id, $observer_hash, $resource_id) {
		
		$sql_extra = item_permissions_sql($channel_id,$observer_hash);

		$item = q("SELECT * FROM item WHERE uid = %d AND resource_type = '%s' AND resource_id = '%s' AND item_deleted = 0 
			$sql_extra limit 1",
			intval($channel_id), 
			dbesc(NWIKI_ITEM_RESOURCE_TYPE),
			dbesc($resource_id)
		);
		if(! $item) {
			return array('wiki' => null);
		}
		else {
		
			$w = $item[0];	// wiki item table record
			// Get wiki metadata
			$rawName  = get_iconfig($w, 'wiki', 'rawName');
			$mimeType = get_iconfig($w, 'wiki', 'mimeType');

			return array(
				'wiki' => $w,
				'rawName' => $rawName,
				'htmlName' => escape_tags($rawName),
				'urlName' => urlencode(urlencode($rawName)),
				'mimeType' => $mimeType
			);
		}
	}


	static public function exists_by_name($uid, $urlName) {

		$sql_extra = item_permissions_sql($uid);		

		$item = q("SELECT item.id, resource_id FROM item left join iconfig on iconfig.iid = item.id 
			WHERE resource_type = '%s' AND iconfig.v = '%s' AND uid = %d 
			AND item_deleted = 0 $sql_extra limit 1", 
			dbesc(NWIKI_ITEM_RESOURCE_TYPE), 
			dbesc(urldecode($urlName)), 
			intval($uid)
		);

		if($item) {
			return array('id' => $item[0]['id'], 'resource_id' => $item[0]['resource_id']);
		} 
		else {
			return array('id' => null, 'resource_id' => null);
		}
	}


	static public function get_permissions($resource_id, $owner_id, $observer_hash) {
		// TODO: For now, only the owner can edit
		$sql_extra = item_permissions_sql($owner_id, $observer_hash);

		if(local_channel() && local_channel() == $owner_id) {
			return [ 'read' => true, 'write' => true, 'success' => true ];
		}

		$r = q("SELECT * FROM item WHERE uid = %d and resource_type = '%s' AND resource_id = '%s' $sql_extra LIMIT 1",
			intval($owner_id),
			dbesc(NWIKI_ITEM_RESOURCE_TYPE), 
			dbesc($resource_id)
		);

		if(! $r) {
			return array('read' => false, 'write' => false, 'success' => true);
		}
		else {
			// TODO: Create a new permission setting for wiki analogous to webpages. Until
			// then, use webpage permissions
			$write = perm_is_allowed($owner_id, $observer_hash,'write_wiki');
			return array('read' => true, 'write' => $write, 'success' => true);
		}
	}
}
