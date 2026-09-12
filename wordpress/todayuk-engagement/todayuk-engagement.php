<?php
/**
 * Plugin Name: TodayUK Engagement
 * Description: One configurable engine for article reactions, polls, follows and private reader responses.
 * Version: 1.1.0
 * Author: TodayUK
 */
if (!defined('ABSPATH')) exit;
require_once __DIR__.'/catalog.php';
function tue_table(){global $wpdb; return $wpdb->prefix.'todayuk_engagement';}
register_activation_hook(__FILE__,function(){
 global $wpdb; require_once ABSPATH.'wp-admin/includes/upgrade.php';
 $table=tue_table(); $collate=$wpdb->get_charset_collate();
 dbDelta("CREATE TABLE $table (
 id bigint unsigned NOT NULL AUTO_INCREMENT,
 user_id bigint unsigned NOT NULL,
 post_id bigint unsigned NOT NULL,
 tool varchar(32) NOT NULL,
 scope varchar(64) NOT NULL,
 value text NOT NULL,
 state varchar(20) NOT NULL DEFAULT 'open',
 updated_at datetime NOT NULL,
 PRIMARY KEY (id),
 UNIQUE KEY response (user_id,tool,scope),
 KEY article (post_id,tool)
 ) $collate;");
});
function tue_config($id){
 $saved=get_post_meta($id,'_tue_config',true);
 $c=is_array($saved)?$saved:tue_clean_config(array());
 if($c['mode']==='off')$c['tools']=array();
 if($c['mode']==='auto')$c['tools']=tue_suggest(get_the_title($id).' '.wp_strip_all_tags(get_post_field('post_content',$id)));
 if(empty($c['question'])||count($c['options'])<2)$c['tools']=array_diff($c['tools'],array('poll'));
 foreach(array('area','topic','org') as $k)if(empty($c[$k]))$c['tools']=array_diff($c['tools'],array('follow_'.$k));
 if(empty($c['event_date']))$c['tools']=array_diff($c['tools'],array('remind'));
 return $c;
}
function tue_scope($id,$tool,$c){
 $subject=(strpos($tool,'follow_')===0)?strtolower($c[substr($tool,7)]):strval($id);
 if($tool==='poll')$subject.=wp_json_encode(array($c['question'],$c['options']));
 return hash('sha256',$tool.'|'.$subject);
}
function tue_version($c){return hash('sha256',wp_json_encode($c));}
function tue_choices($tool,$c){return $tool==='poll'?array_combine(array_map('strval',range(0,count($c['options'])-1)),$c['options']):tue_catalog()[$tool][2];}
add_action('add_meta_boxes',function(){add_meta_box('tue-settings','Reader engagement','tue_metabox','post','normal','default');});
function tue_metabox($post){
 $saved=get_post_meta($post->ID,'_tue_config',true);$c=is_array($saved)?$saved:tue_clean_config(array());
 wp_nonce_field('tue-config','tue_nonce');
 echo '<p>Automatic tools use the story text. AI selection is paused. Choose Custom to override.</p><select name="tue[mode]">';
 foreach(array('auto'=>'Automatic','custom'=>'Custom','off'=>'Off')as $k=>$v)echo '<option value="'.esc_attr($k).'" '.selected($c['mode'],$k,false).'>'.esc_html($v).'</option>';
 echo '</select><p>For Custom mode:</p><div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:10px">';
 foreach(tue_catalog()as $k=>$v)echo '<label><input type="checkbox" name="tue[tools][]" value="'.esc_attr($k).'" '.checked(in_array($k,$c['tools'],true),true,false).'> '.esc_html($v[0]).'</label>';
 echo '</div>';
 foreach(array('question'=>'Poll question','area'=>'Area to follow','topic'=>'Topic to follow','org'=>'Organisation to follow','event_date'=>'Event date (calendar reminder)')as $k=>$v)echo '<p><label>'.esc_html($v).'<br><input class="widefat" type="'.($k==='event_date'?'date':'text').'" name="tue['.esc_attr($k).']" value="'.esc_attr($c[$k]).'"></label></p>';
 echo '<p><label>Poll answers (2–6, one per line)<textarea class="widefat" name="tue[options]">'.esc_textarea(implode("\n",$c['options'])).'</textarea></label></p><p>Changing poll wording starts a new result set. Private messages are only visible to editors. Reminders download to the reader’s calendar; no email or push delivery is enabled.</p>';
 echo '<p><a href="'.esc_url(admin_url('edit.php?page=todayuk-engagement')).'">View reader responses →</a></p>';
}
add_action('save_post_post',function($id){
 if(wp_is_post_revision($id)||defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE||!current_user_can('edit_post',$id)||empty($_POST['tue_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tue_nonce'])),'tue-config'))return;
 if(isset($_POST['tue'])&&is_array($_POST['tue']))update_post_meta($id,'_tue_config',tue_clean_config(wp_unslash($_POST['tue'])));
});
function tue_notice($message){wp_die(esc_html($message),'TodayUK',array('response'=>400,'back_link'=>true));}
add_action('admin_post_tue_respond','tue_respond');
add_action('admin_post_nopriv_tue_respond',function(){auth_redirect();});
function tue_respond(){
 global $wpdb;$id=absint($_POST['post_id']??0);$tool=sanitize_key($_POST['tool']??'');
 check_admin_referer('tue-response-'.$id);
 if(!is_user_logged_in()||get_post_status($id)!=='publish'||get_post_type($id)!=='post'||post_password_required($id))tue_notice('This article is not available.');
 $c=tue_config($id);$catalog=tue_catalog();
 if(!isset($catalog[$tool])||!in_array($tool,$c['tools'],true)||!hash_equals(tue_version($c),sanitize_text_field($_POST['version']??'')))tue_notice('The available choices changed. Reload the article and try again.');
 $uid=get_current_user_id();$scope=tue_scope($id,$tool,$c);$table=tue_table();$kind=$catalog[$tool][1];
 if(isset($_POST['remove'])){if($wpdb->delete($table,array('user_id'=>$uid,'tool'=>$tool,'scope'=>$scope))===false)tue_notice('Your response could not be removed. Please retry.');}
 else{
 $value=sanitize_textarea_field(wp_unslash($_POST['value']??''));
 if($kind==='choice'&&!array_key_exists($value,tue_choices($tool,$c)))tue_notice('Choose one of the displayed answers.');
 if($kind==='message'&&(strlen($value)<5||strlen($value)>2000))tue_notice('Enter a message between 5 and 2,000 characters.');
 if(!in_array($kind,array('choice','message','toggle'),true))tue_notice('Use the displayed link for this action.');
 if($kind==='toggle')$value=strpos($tool,'follow_')===0?$c[substr($tool,7)]:'yes';
 if(get_transient('tue-rate-'.$uid))tue_notice('Please wait a moment before sending another response.');
 $ok=$wpdb->query($wpdb->prepare("INSERT INTO $table (user_id,post_id,tool,scope,value,state,updated_at) VALUES (%d,%d,%s,%s,%s,'open',%s) ON DUPLICATE KEY UPDATE value=VALUES(value),state='open',updated_at=VALUES(updated_at)",$uid,$id,$tool,$scope,$value,current_time('mysql',true)));
 if($ok===false)tue_notice('Your response was not saved. Please try again.');
 set_transient('tue-rate-'.$uid,1,2);
 }
 wp_safe_redirect(add_query_arg('tue_saved','1',get_permalink($id)).'#reader-engagement');exit;
}
function tue_hidden($id,$tool,$c){echo '<input type="hidden" name="action" value="tue_respond"><input type="hidden" name="post_id" value="'.absint($id).'"><input type="hidden" name="tool" value="'.esc_attr($tool).'"><input type="hidden" name="version" value="'.esc_attr(tue_version($c)).'">';wp_nonce_field('tue-response-'.$id);}
add_filter('the_content',function($content){
 if(!is_singular('post')||!in_the_loop()||!is_main_query()||is_feed()||post_password_required())return $content;
 global $wpdb;$id=get_the_ID();$c=tue_config($id);if(!$c['tools'])return $content;
 $uid=get_current_user_id();$table=tue_table();ob_start();
 echo '<section id="reader-engagement" class="tue"><h2>Have your say</h2>';
 if(isset($_GET['tue_saved']))echo '<p role="status">Your response has been saved.</p>';
 if(!$uid)echo '<p><a href="'.esc_url(wp_login_url(get_permalink($id).'#reader-engagement')).'">Sign in to take part</a>. Reading and sharing are open to everyone.</p>';
 foreach($c['tools']as $tool){$spec=tue_catalog()[$tool];$kind=$spec[1];$scope=tue_scope($id,$tool,$c);
 $mine=$uid?$wpdb->get_row($wpdb->prepare("SELECT value FROM $table WHERE user_id=%d AND tool=%s AND scope=%s",$uid,$tool,$scope)):null;
 $label=$spec[0];if($tool==='poll')$label=$c['question'];if(strpos($tool,'follow_')===0)$label.=': '.$c[substr($tool,7)];
 echo $kind==='message'?'<details class="tue-tool"><summary>'.esc_html($label).'</summary>':'<div class="tue-tool"><h3>'.esc_html($label).'</h3>';
 if($kind==='share'){echo '<button type="button" class="tue-share" data-url="'.esc_url(get_permalink($id)).'" data-title="'.esc_attr(get_the_title($id)).'">Share this story</button><p class="tue-share-status" role="status"></p>';}
 elseif($kind==='reminder'){echo '<p>Add '.esc_html($c['event_date']).' to your calendar.</p><a href="'.esc_url(add_query_arg(array('action'=>'tue_calendar','post_id'=>$id),admin_url('admin-post.php'))).'">Download calendar reminder</a>';}
 else{
 if($uid){echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';tue_hidden($id,$tool,$c);
 if($kind==='choice'){echo '<fieldset><legend class="screen-reader-text">'.esc_html($label).'</legend>';foreach(tue_choices($tool,$c)as $k=>$label2)echo '<label><input type="radio" name="value" value="'.esc_attr($k).'" '.checked($mine?$mine->value:null,(string)$k,false).' required> '.esc_html($label2).'</label>';echo '</fieldset><button>Save answer</button>';}
 elseif($kind==='message')echo '<label>Private message to the newsroom<textarea name="value" minlength="5" maxlength="2000" required rows="3">'.esc_textarea($mine?$mine->value:'').'</textarea></label><p>Your message is not published or sent to another reader. Do not include passwords or bank details.</p><button>Send to newsroom</button>';
 else echo '<button>'.($mine?'Saved ✓':esc_html($label)).'</button>';
 if($mine)echo ' <button name="remove" value="1" formnovalidate>Remove my response</button>';
 echo '</form>';}
 if($kind==='choice'){ $rows=$wpdb->get_results($wpdb->prepare("SELECT value,COUNT(*) total FROM $table WHERE tool=%s AND scope=%s GROUP BY value",$tool,$scope),OBJECT_K);echo '<ul class="tue-results">';foreach(tue_choices($tool,$c)as $k=>$v)echo '<li>'.esc_html($v).': '.absint(isset($rows[$k])?$rows[$k]->total:0).'</li>';echo '</ul><small>Reader responses; not a representative survey.</small>';}
 if($tool==='track'||strpos($tool,'follow_')===0)echo '<p>'.($mine?'Saved to your reading list.':'Keep this in your reading list.').' Email and push alerts are not enabled.</p>';
 }
 echo $kind==='message'?'</details>':'</div>';}
 if($uid)echo '<p><a href="'.esc_url(admin_url('profile.php#tue-saved')).'">My saved stories and follows →</a></p>';
 echo '</section>';return $content.ob_get_clean();
},30);
add_action('wp_enqueue_scripts',function(){if(is_singular('post')){wp_enqueue_style('tue',plugins_url('style.css',__FILE__),array(),'1.0.0');wp_enqueue_script('tue',plugins_url('share.js',__FILE__),array(),'1.0.0',true);}});
add_action('admin_post_tue_calendar','tue_calendar');add_action('admin_post_nopriv_tue_calendar','tue_calendar');
function tue_calendar(){
 $id=absint($_GET['post_id']??0);$c=tue_config($id);if(get_post_status($id)!=='publish'||post_password_required($id)||!in_array('remind',$c['tools'],true))tue_notice('No reminder is available.');
 $date=str_replace('-','',$c['event_date']);$summary=str_replace(array('\\',"\r","\n",';',','),array('\\\\','',' ','\\;','\\,'),wp_strip_all_tags(get_the_title($id)));
 nocache_headers();header('Content-Type: text/calendar; charset=utf-8');header('Content-Disposition: attachment; filename="todayuk-reminder.ics"');
 echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//TodayUK//Reader reminder//EN\r\nBEGIN:VEVENT\r\nUID:todayuk-".$id.'@'.wp_parse_url(home_url(),PHP_URL_HOST)."\r\nDTSTAMP:".gmdate('Ymd\THis\Z')."\r\nDTSTART;VALUE=DATE:$date\r\nSUMMARY:$summary\r\nURL:".get_permalink($id)."\r\nBEGIN:VALARM\r\nTRIGGER:-PT12H\r\nACTION:DISPLAY\r\nDESCRIPTION:TodayUK event reminder\r\nEND:VALARM\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";exit;
}
add_action('admin_menu',function(){add_posts_page('Reader engagement','Reader engagement','edit_posts','todayuk-engagement','tue_admin');});
function tue_admin(){
 global $wpdb;$table=tue_table();echo '<div class="wrap"><h1>Reader engagement</h1><p>Choose an article below to configure its tools. AI selection is paused; automatic choices use story keywords. Private messages below are only available to editors with access to that article.</p>';
 if(isset($_GET['saved']))echo '<div class="notice notice-success"><p>Engagement settings saved.</p></div>';
 $selected=absint($_GET['article']??0);echo '<form method="get"><input type="hidden" name="page" value="todayuk-engagement"><label>Choose an article <select name="article"><option value="">Choose an article</option>';
 foreach(get_posts(array('post_type'=>'post','post_status'=>array('publish','draft'),'numberposts'=>200)) as $article){if(current_user_can('edit_post',$article->ID))echo '<option value="'.absint($article->ID).'" '.selected($selected,$article->ID,false).'>'.esc_html($article->post_title).'</option>';}
 echo '</select></label> <button class="button">Open settings</button></form>';
 if($selected&&current_user_can('edit_post',$selected)&&get_post_type($selected)==='post'){echo '<h2>'.esc_html(get_the_title($selected)).'</h2><form method="post" action="'.esc_url(admin_url('admin-post.php')).'" style="max-width:850px;background:white;padding:20px;margin:20px 0"><input type="hidden" name="action" value="tue_settings"><input type="hidden" name="post_id" value="'.$selected.'">';tue_metabox(get_post($selected));echo '<button class="button button-primary">Save engagement settings</button></form>';}
 echo '<h2>Reader responses</h2>';
 $rows=$wpdb->get_results("SELECT * FROM $table ORDER BY updated_at DESC LIMIT 200");
 echo '<table class="widefat striped"><thead><tr><th>Article / tool</th><th>Response</th><th>Reader</th><th>Status</th></tr></thead><tbody>';
 foreach($rows as $r){if(!current_user_can('edit_post',$r->post_id))continue;$user=get_userdata($r->user_id);echo '<tr><td><a href="'.esc_url(get_edit_post_link($r->post_id)).'">'.esc_html(get_the_title($r->post_id)).'</a><br>'.esc_html(tue_catalog()[$r->tool][0]??$r->tool).'</td><td>'.esc_html($r->value).'<br><small>'.esc_html($r->updated_at).' UTC</small></td><td>'.esc_html($user?$user->display_name:'Deleted account').'</td><td>'.esc_html($r->state);
 if(tue_catalog()[$r->tool][1]==='message'){echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="tue_resolve"><input type="hidden" name="id" value="'.absint($r->id).'">';wp_nonce_field('tue-resolve-'.$r->id);echo '<button class="button">'.($r->state==='open'?'Mark handled':'Reopen').'</button></form>';}
 echo '</td></tr>';}
 echo '</tbody></table><p>Showing the latest 200 responses. No private messages are shown publicly.</p></div>';
}
add_action('admin_post_tue_resolve',function(){global $wpdb;$id=absint($_POST['id']??0);check_admin_referer('tue-resolve-'.$id);$r=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.tue_table().' WHERE id=%d',$id));if(!$r||!current_user_can('edit_post',$r->post_id))wp_die('Not allowed.',403);$wpdb->update(tue_table(),array('state'=>$r->state==='open'?'handled':'open'),array('id'=>$id));wp_safe_redirect(admin_url('edit.php?page=todayuk-engagement'));exit;});
add_action('show_user_profile',function($user){global $wpdb;echo '<h2 id="tue-saved">My saved stories and follows</h2><ul>';foreach($wpdb->get_results($wpdb->prepare('SELECT * FROM '.tue_table()." WHERE user_id=%d AND tool IN ('track','follow_area','follow_topic','follow_org') ORDER BY updated_at DESC",$user->ID))as $r){if(get_post_status($r->post_id)!=='publish')continue;echo '<li><a href="'.esc_url(get_permalink($r->post_id).'#reader-engagement').'">'.esc_html(get_the_title($r->post_id)).'</a> — '.esc_html(tue_catalog()[$r->tool][0]).'</li>';}echo '</ul><p>Open a story to remove a saved response. No email or push alerts are sent.</p>';});
add_action('deleted_user',function($id){global $wpdb;$wpdb->delete(tue_table(),array('user_id'=>$id));});

add_action('admin_post_tue_settings',function(){
 $id=absint($_POST['post_id']??0);check_admin_referer('tue-config','tue_nonce');
 if(!current_user_can('edit_post',$id)||get_post_type($id)!=='post')wp_die('Not allowed.',403);
 update_post_meta($id,'_tue_config',tue_clean_config(wp_unslash((array)($_POST['tue']??array()))));
 wp_safe_redirect(add_query_arg(array('page'=>'todayuk-engagement','article'=>$id,'saved'=>1),admin_url('edit.php')));exit;
});

// The newsroom sends its approved choice with the draft, before publication.
add_action('rest_api_init',function(){
 register_rest_field('post','todayuk_engagement',array(
  'get_callback'=>function($post){return tue_config($post['id']);},
  'update_callback'=>function($value,$post){
   if(!current_user_can('edit_post',$post->ID))return new WP_Error('rest_forbidden','Cannot edit this article.',array('status'=>403));
   if(!is_array($value)||!in_array($value['mode']??'',array('custom','off'),true)||!is_array($value['options']??null))return new WP_Error('invalid_engagement','Invalid engagement configuration.',array('status'=>400));
   $value['options']=implode("\n",$value['options']);
   $clean=tue_clean_config($value);
   if(in_array('poll',$clean['tools'],true)&&(empty($clean['question'])||count($clean['options'])<2))return new WP_Error('invalid_engagement','A poll needs a question and answers.',array('status'=>400));
   update_post_meta($post->ID,'_tue_config',$clean);return true;
  },
  'schema'=>array('description'=>'Approved TodayUK engagement','type'=>'object','context'=>array('view','edit'),'additionalProperties'=>true)
 ));
});
