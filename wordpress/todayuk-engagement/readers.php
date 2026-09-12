<?php
if (!defined('ABSPATH')) exit;
require_once __DIR__.'/reader-model.php';
function tur_url(){return home_url('/?todayuk_reader=1');}
function tur_preferences($uid){return tur_clean_preferences(get_user_meta($uid,'_tur_preferences',true));}
// A public account surface using the site's existing WordPress identity; no newsroom/DU access.
add_action('template_redirect',function(){
 if(!isset($_GET['todayuk_reader']))return;
 status_header(200);nocache_headers();header('X-Robots-Tag: noindex, nofollow');
 get_header();echo '<main id="main" class="wrap"><section class="tue tur">';tur_account();echo '</section></main>';get_footer();exit;
});
add_action('wp_body_open',function(){echo '<div class="tur-account-link"><a href="'.esc_url(tur_url()).'">'.(is_user_logged_in()?'My TodayUK':'Join / sign in').'</a></div>';});
add_action('wp_enqueue_scripts',function(){
 wp_enqueue_style('tur',plugins_url('reader.css',__FILE__),array(),'1.2.0');
 if(isset($_GET['todayuk_reader']))wp_enqueue_style('tue',plugins_url('style.css',__FILE__),array(),'1.2.0');
 if(is_singular('post')&&is_user_logged_in()&&tur_preferences(get_current_user_id())['remember']){
  wp_enqueue_script('tur-reading',plugins_url('reader.js',__FILE__),array(),'1.2.0',true);
  wp_localize_script('tur-reading','todayukReading',array('url'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('tur-reading'),'post'=>get_the_ID()));
 }
});
function tur_account(){
 echo '<h1>My TodayUK</h1>';
 if(!is_user_logged_in()){
  echo '<p>Read freely. Join to save stories, follow your area and take part.</p><p><a class="tur-primary" href="'.esc_url(wp_login_url(tur_url())).'">Sign in</a> <a href="'.esc_url(wp_lostpassword_url(tur_url())).'">Reset password</a></p>';
  if(isset($_GET['check_email']))echo '<p role="status">If we could create your account, an email with a password-setting link is on its way. If you already have an account, use Sign in or Reset password.</p>';
  echo '<h2>Create a free reader account</h2><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="tur_register">';wp_nonce_field('tur-register');
  echo '<label>Username<input name="username" autocomplete="username" maxlength="60" required></label><label>Email<input type="email" name="email" autocomplete="email" required maxlength="100"></label><label>Home area<select name="home" required><option value="">Choose your area</option>';foreach(tur_areas()as $k=>$v)echo '<option value="'.esc_attr($k).'">'.esc_html($v).'</option>';echo '</select></label><div class="tur-trap" aria-hidden="true"><label>Leave this empty<input name="website" tabindex="-1" autocomplete="off"></label></div><p>We will email you a link to set your password. This does not sign you up for marketing. Your reader account stays with TodayUK.</p><button>Create reader account</button></form>';return;
 }
 $uid=get_current_user_id();$p=tur_preferences($uid);$class=get_user_meta($uid,'_tur_class',true)?:'reader';
 echo '<p><a href="#tur-feed">Your feed</a> · <a href="#tur-saved">Saved stories</a> · <a href="#tur-preferences">Preferences</a> · <a href="'.esc_url(wp_logout_url(home_url('/'))).'">Sign out</a></p>';
 if(isset($_GET['saved']))echo '<p role="status">Your preferences have been saved.</p>';
 echo '<h2 id="tur-preferences">Your area and interests</h2><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="tur_preferences">';wp_nonce_field('tur-preferences');
 echo '<label>Home area<select name="home" required><option value="">Choose your area</option>';foreach(tur_areas()as $k=>$v)echo '<option value="'.esc_attr($k).'" '.selected($p['home'],$k,false).'>'.esc_html($v).'</option>';echo '</select></label><fieldset><legend>Other areas to follow</legend>';
 foreach(tur_areas()as $k=>$v)echo '<label><input type="checkbox" name="areas[]" value="'.esc_attr($k).'" '.checked(in_array($k,$p['areas'],true),true,false).'> '.esc_html($v).'</label>';echo '</fieldset><fieldset><legend>Topics you enjoy</legend>';
 foreach(tur_topics()as $k=>$v)echo '<label><input type="checkbox" name="topics[]" value="'.esc_attr($k).'" '.checked(in_array($k,$p['topics'],true),true,false).'> '.esc_html($v).'</label>';echo '</fieldset><fieldset><legend>Optional preferences</legend><label><input type="checkbox" name="notify" value="1" '.checked($p['notify'],true,false).'> Show new stories for my follows in My TodayUK</label><label><input type="checkbox" name="remember" value="1" '.checked($p['remember'],true,false).'> Remember stories I read to keep my feed fresh</label></fieldset><p>Both are optional. Turning reading history off clears the saved history. Notifications appear here; email and push alerts are not enabled.</p><button>Save preferences</button></form>';
 $posts=get_posts(array('post_type'=>'post','post_status'=>'publish','numberposts'=>100,'has_password'=>false));$preferred=array();$discovery=array();$map=array();$history=$p['remember']?(array)get_user_meta($uid,'_tur_history',true):array();$read=array();
 foreach($posts as $post){$map[$post->ID]=$post;$match=tur_matches($post,$p,$uid);if($p['remember']&&isset($history[$post->ID]))$read[]=$post->ID;elseif($match)$preferred[]=$post->ID;else $discovery[]=$post->ID;}
 echo '<h2 id="tur-feed">Your feed</h2><p>Latest editorial coverage, shaped by your selected areas and topics. Around one in five stories explores other interests when enough stories are available.</p>';
 $feed=tur_mix_feed($preferred,$discovery,20);foreach($read as $id)if(count($feed)<20)$feed[]=$id;
 if(!$feed)echo '<p>Stories will appear here as the newsroom publishes them.</p>';else {echo '<div class="tur-feed">';foreach($feed as $id){$post=$map[$id];echo '<article><small>'.esc_html(get_the_date('j M Y',$post)).'</small><h3><a href="'.esc_url(get_permalink($post)).'">'.esc_html(get_the_title($post)).'</a></h3></article>';}echo '</div>';}
 if($p['notify']){echo '<h2>New for your follows</h2>';$seen=(int)get_user_meta($uid,'_tur_seen',true);$n=0;foreach($posts as $post)if(get_post_time('U',true,$post)>$seen&&tur_matches($post,$p,$uid)){echo '<p><a href="'.esc_url(get_permalink($post)).'">'.esc_html(get_the_title($post)).'</a></p>';if(++$n>=20)break;}if(!$n)echo '<p>You’re up to date.</p>';else{echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="tur_seen">';wp_nonce_field('tur-seen');echo '<button>Mark updates as read</button></form>';}}
 tur_saved($uid);
 echo '<details><summary>Your data</summary><p>Your area, preferences, saved stories and optional reading history stay with TodayUK. They are not sent to Digital Unicorn. Your membership class is '.esc_html($class).'.</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="tur_export">';wp_nonce_field('tur-export');echo '<button>Download my reader data</button></form></details>';
}
function tur_matches($post,$p,$uid){
 $terms=wp_get_post_terms($post->ID,array('category','post_tag'),array('fields'=>'slugs'));if(is_wp_error($terms))$terms=array();
 $selected=array_merge(array(strtolower($p['home'])),array_map('strtolower',$p['areas']),$p['topics']);
 if(array_intersect($terms,array_filter($selected)))return true;
 global $wpdb;static $follow_cache=array();if(!isset($follow_cache[$uid]))$follow_cache[$uid]=$wpdb->get_col($wpdb->prepare('SELECT value FROM '.tue_table()." WHERE user_id=%d AND tool IN ('follow_area','follow_topic','follow_org')",$uid));
 foreach($follow_cache[$uid] as $value)if(in_array(sanitize_title($value),$terms,true))return true;
 return false;
}
function tur_saved($uid){
 global $wpdb;$rows=$wpdb->get_results($wpdb->prepare('SELECT * FROM '.tue_table()." WHERE user_id=%d AND tool IN ('track','follow_area','follow_topic','follow_org') ORDER BY updated_at DESC LIMIT 200",$uid));
 echo '<h2 id="tur-saved">Saved stories and follows</h2>';if(!$rows)echo '<p>Use Track this story or Follow on an article to save it here.</p>';
 foreach($rows as $row){echo '<div class="tur-saved">';if(get_post_status($row->post_id)==='publish'&&!post_password_required($row->post_id))echo '<a href="'.esc_url(get_permalink($row->post_id)).'">'.esc_html(get_the_title($row->post_id)).'</a>';else echo 'Article no longer available';echo '<small>'.esc_html(tue_catalog()[$row->tool][0]).($row->tool==='track'?'':': '.esc_html($row->value)).'</small><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="tur_remove"><input type="hidden" name="response_id" value="'.absint($row->id).'">';wp_nonce_field('tur-remove');echo '<button>Remove</button></form></div>';}
}
add_action('admin_post_nopriv_tur_register',function(){
 check_admin_referer('tur-register');$home=sanitize_text_field(wp_unslash($_POST['home']??''));if(!isset(tur_areas()[$home]))tue_notice('Choose your home area.');
 $email=sanitize_email(wp_unslash($_POST['email']??''));$username=sanitize_user(wp_unslash($_POST['username']??''),true);
 if(!is_email($email)||!$username||strlen($username)>60)tue_notice('Enter a valid email and username.');
 $key='tur-reg-'.hash_hmac('sha256',($_SERVER['REMOTE_ADDR']??'unknown'),wp_salt());$count=(int)get_transient($key);
 if($count>=5)tue_notice('Too many attempts. Please try again later.');set_transient($key,$count+1,HOUR_IN_SECONDS);
 if(empty($_POST['website'])&&!email_exists($email)&&!username_exists($username)){
  $uid=wp_insert_user(array('user_login'=>$username,'user_email'=>$email,'user_pass'=>wp_generate_password(32,true,true),'role'=>'subscriber'));
  if(!is_wp_error($uid)){update_user_meta($uid,'_tur_class','reader');update_user_meta($uid,'_tur_preferences',tur_clean_preferences(array('home'=>$home)));update_user_meta($uid,'_tur_seen',time());wp_new_user_notification($uid,null,'user');}
 }
 wp_safe_redirect(add_query_arg('check_email','1',tur_url()));exit;
});
add_action('admin_post_tur_preferences',function(){
 check_admin_referer('tur-preferences');$uid=get_current_user_id();if(!$uid)auth_redirect();$p=tur_clean_preferences(wp_unslash($_POST));if(!$p['home'])tue_notice('Choose a home area.');$old=tur_preferences($uid);
 update_user_meta($uid,'_tur_preferences',$p);update_user_meta($uid,'_tur_preferences_updated',gmdate('c'));if(!$p['remember'])delete_user_meta($uid,'_tur_history');if(!$old['notify']&&$p['notify'])update_user_meta($uid,'_tur_seen',time());wp_safe_redirect(add_query_arg('saved','1',tur_url()));exit;
});
add_action('admin_post_tur_seen',function(){check_admin_referer('tur-seen');update_user_meta(get_current_user_id(),'_tur_seen',time());wp_safe_redirect(tur_url());exit;});
add_action('admin_post_tur_remove',function(){check_admin_referer('tur-remove');global $wpdb;$wpdb->delete(tue_table(),array('id'=>absint($_POST['response_id']??0),'user_id'=>get_current_user_id()));wp_safe_redirect(tur_url().'#tur-saved');exit;});
add_action('wp_ajax_tur_read',function(){check_ajax_referer('tur-reading','nonce');$uid=get_current_user_id();if(!$uid||!tur_preferences($uid)['remember'])wp_send_json_error(null,403);$id=absint($_POST['post_id']??0);if(get_post_status($id)!=='publish'||get_post_type($id)!=='post'||post_password_required($id))wp_send_json_error(null,400);$h=(array)get_user_meta($uid,'_tur_history',true);$h[$id]=time();arsort($h);update_user_meta($uid,'_tur_history',array_slice($h,0,100,true));wp_send_json_success();});
function tur_data($uid){global $wpdb;return array('preferences'=>tur_preferences($uid),'membership_class'=>get_user_meta($uid,'_tur_class',true)?:'reader','preferences_updated'=>get_user_meta($uid,'_tur_preferences_updated',true),'reading_history'=>(array)get_user_meta($uid,'_tur_history',true),'engagement'=>$wpdb->get_results($wpdb->prepare('SELECT post_id,tool,value,state,updated_at FROM '.tue_table().' WHERE user_id=%d',$uid),ARRAY_A));}
add_action('admin_post_tur_export',function(){check_admin_referer('tur-export');nocache_headers();header('Content-Type: application/json; charset=utf-8');header('Content-Disposition: attachment; filename="todayuk-reader-data.json"');echo wp_json_encode(tur_data(get_current_user_id()),JSON_PRETTY_PRINT);exit;});
add_filter('wp_privacy_personal_data_exporters',function($exporters){$exporters['todayuk']=array('exporter_friendly_name'=>'TodayUK reader data','callback'=>function($email,$page=1){$u=get_user_by('email',$email);return array('data'=>$u&&$page===1?array(array('group_id'=>'todayuk-reader','group_label'=>'TodayUK reader data','item_id'=>'todayuk-reader-'.$u->ID,'data'=>array(array('name'=>'Reader data','value'=>wp_json_encode(tur_data($u->ID)))))):array(),'done'=>true);});return $exporters;});
add_filter('wp_privacy_personal_data_erasers',function($erasers){$erasers['todayuk']=array('eraser_friendly_name'=>'TodayUK reader data','callback'=>function($email,$page=1){$u=get_user_by('email',$email);if($u){global $wpdb;$wpdb->delete(tue_table(),array('user_id'=>$u->ID));foreach(array('_tur_preferences','_tur_history','_tur_seen','_tur_preferences_updated','_tur_class')as $key)delete_user_meta($u->ID,$key);}return array('items_removed'=>(bool)$u,'items_retained'=>false,'messages'=>array(),'done'=>true);});return $erasers;});
function tur_membership_field($user){if(!current_user_can('manage_options'))return;echo '<h2>TodayUK membership</h2><label>Relationship class <select name="tur_class">';foreach(array('reader'=>'Individual reader','community'=>'Community / non-profit','commercial'=>'Commercial / business','public_sector'=>'Public-sector body')as $k=>$v)echo '<option value="'.esc_attr($k).'" '.selected(get_user_meta($user->ID,'_tur_class',true)?:'reader',$k,false).'>'.esc_html($v).'</option>';echo '</select></label><p>Relationship class does not grant editorial access or share data with Digital Unicorn.</p>';}
add_action('show_user_profile','tur_membership_field');add_action('edit_user_profile','tur_membership_field');
function tur_save_class($uid){if(current_user_can('manage_options')&&current_user_can('edit_user',$uid)&&in_array($_POST['tur_class']??'',array('reader','community','commercial','public_sector'),true))update_user_meta($uid,'_tur_class',sanitize_key($_POST['tur_class']));}
add_action('personal_options_update','tur_save_class');add_action('edit_user_profile_update','tur_save_class');
