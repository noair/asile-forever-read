<?php

/**
 * asile_forever_read functions: logic, database and output
 *
 * @copyright (C) 2013 noway
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package asile_fr
 */

if (!defined('FORUM'))
	die();

function asile_fr_ignored_topics()
{
	global $forum_db, $forum_user;


	($hook = get_hook('asile_fr_fn_ignored_topics_begin')) ? eval($hook) : null;

	$query = array(
		'SELECT'	=> 'topic_id',
		'FROM'		=> 'asile_forever_read',
		'WHERE'		=> 'poster_id = '.$forum_user['id']
	);

	($hook = get_hook('asile_fr_fn_ignored_topics_pre_query')) ? eval($hook) : null;

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	list($topics) = $forum_db->fetch_row($result);
	
	$return = $topics;

	($hook = get_hook('asile_fr_fn_ignored_topics_end')) ? eval($hook) : null;

	return $return;
}

function asile_fr_is_ignored($topic_id)
{
	global $forum_db, $forum_user;

	($hook = get_hook('asile_fr_fn_ignored_topics_begin')) ? eval($hook) : null;

	$query = array(
		'SELECT'	=> 'count(topic_id)',
		'FROM'		=> 'asile_forever_read',
		'WHERE'		=> 'poster_id = '.$forum_user['id'].' AND topic_id = '.$topic_id
	);

	($hook = get_hook('asile_fr_fn_is_ignored_pre_query')) ? eval($hook) : null;

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	list($topics) = $forum_db->fetch_row($result);
	
	$return = $topics;

	($hook = get_hook('asile_fr_fn_is_ignored_end')) ? eval($hook) : null;

	return $return;
}

function asile_fr_ignore_topic($topic_id){
	global $forum_config, $lang_profile, $forum_url, $lang_common, $forum_user, $forum_db;

	$query = array(
		'INSERT'	=> 'topic_id, poster_id',
		'INTO'		=> 'asile_forever_read',
		'VALUES'	=> $topic_id.', '.$forum_user['id']
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
}

function asile_fr_enable_topic($topic_id){
	global $forum_config, $lang_profile, $forum_url, $lang_common, $forum_user, $forum_db;

	$query = array(
		'DELETE'	=> 'asile_forever_read',
		'WHERE'		=> 'topic_id ='.$topic_id.' AND poster_id = '.$forum_user['id']
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
}

function asile_fr_get_page(&$page)
{
	global $forum_url, $forum_user, $lang_common;

	$return = ($hook = get_hook('asile_fr_fn_get_page_new_page')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	return asile_fr_main_page();
}

function asile_fr_main_page(){
	global $forum_config, $lang_profile, $forum_url, $lang_common, $forum_user, $forum_db;

	$query = array(
		'SELECT'	=> 'afr.topic_id as tid, t.poster, t.subject, t.first_post_id, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_replies, t.closed, t.sticky, t.forum_id, f.forum_name',
		'FROM'		=> 'asile_forever_read afr',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'topics t',
				'ON'			=> '(t.id=topic_id)'
			),
			array(
				'INNER JOIN'	=> 'forums AS f',
				'ON'			=> 'f.id=t.forum_id'
			),
			array(
				'LEFT JOIN'		=> 'forum_perms AS fp',
				'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.$forum_user['g_id'].')'
			)
		),
		'WHERE'		=> 'poster_id = '.$forum_user['id']
	);


	($hook = get_hook('asile_fr_fn_inbox_pre_query')) ? eval($hook) : null;

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$topics = array();
	while ($row = $forum_db->fetch_assoc($result))
		$topics[] = $row;

	$page['list'] = $topics;
	return asile_fr_box($page);
}

function asile_fr_box($forum_page)
{

	global $forum_config, $lang_profile, $forum_url, $lang_common, $forum_user, $forum_db, $lang_asile_fr;

	ob_start();		
	?>
	<div class="main-head">
		<p class="options"><span class="first-item"><!-- --></span></p>		<h2 class="hn"><span><span class="item-info">Sujets ignorés</span></span></h2>
	</div>
	<div class="main-subhead">
		<p class="item-summary forum-noview"><span><strong class="subject-title">Sujets</strong>, <strong class="info-forum">Forum</strong>,  <strong class="info-lastpost">Action</strong>.</span></p>
	</div>

	<div class="main-content main-forum forum-forums">
	<?php
	if (!count($forum_page['list'])){
	?>
			<div class="ct-box info-box">
				<p class="important"><?php echo $lang_asile_fr['Empty list']?></p>
			</div>
	<?php
	}
	$first = true;
	$odd = true;
	if (count($forum_page['list'])){
		foreach($forum_page['list'] as $message){
			($hook = get_hook('pun_pm_fn_box_pre_row_output')) ? eval($hook) : null;
    ?>

		<div class="main-item <?php 
		if ($odd) {
			$odd = false;
			echo "odd ";
		} else {
			$odd = true;
			echo "even ";
		}
		if ($first) {
			echo "main-first-item" ; 
			$first=false; 
		}

		?>">
			<span class="icon posted"><!-- --></span>
			<div class="item-subject">
<?php
			echo'	<h3 class="hn"><span class="item-num"><!-- --></span> <span class="posted-mark">·</span><a href="'.forum_link($forum_url['topic'], array($message['tid'], sef_friendly($message['subject']))).'">'.forum_htmlencode($message['subject']).'</a></h3>';
			echo '<p><span class="item-starter">'.sprintf($lang_forum['Topic starter'], forum_htmlencode($message['poster'])).'</span> ';
			if (!empty($forum_page['item_nav']))
				echo '<span class="item-nav">'.sprintf($lang_forum['Topic navigation'], implode('&#160;&#160;', $forum_page['item_nav'])).'</span></p>';
?>
			</div>
			<ul class="item-info">
				<?php
				echo '<li class="info-forum"><span class="label">'.$lang_search['Posted in'].'</span><a href="'.forum_link($forum_url['forum'], array($message['forum_id'], sef_friendly($message['forum_name']))).'">'.$message['forum_name'].'</a></li>';
				echo '<li class="info-lastpost"><a class="sub-option" href="'.forum_link($forum_url['asile_fr_enable_topic'],$message['tid']).'" title="'.$lang_asile_fr['D&eacute;lire pour toujours'].'">'.$lang_asile_fr['Délire pour toujours'].'</a></li>';
				?>
			</ul>
		</div>
<?php
		}
	}
	echo "	</div>\n";

	$result = ob_get_contents();
	ob_end_clean();

	return $result;
}

define('ASILE_FR_FUNCTIONS_LOADED', 1);
?>