<?php
if(!defined('ABSPATH'))exit;
function tus_page(){return 'https://www.facebook.com/profile.php?id=61594511422562';}
add_action('admin_menu',function(){add_submenu_page('edit.php','Social queue','Social queue','publish_posts','todayuk-social','tus_admin');});
function tus_queue($id){
 if(get_post_type($id)!=='post'||get_post_status($id)!=='publish'||post_password_required($id))return;
 if(!metadata_exists('post',$id,'_tus_state')){
 update_post_meta($id,'_tus_copy',wp_strip_all_tags(get_the_title($id)));
 update_post_meta($id,'_tus_state','ready');
 }
}
add_action('transition_post_status',function($new,$old,$post){if($new==='publish'&&$old!=='publish')tus_queue($post->ID);},10,3);
function tus_link($id){return add_query_arg(array('utm_source'=>'facebook','utm_medium'=>'social','utm_campaign'=>'todayuk','utm_content'=>'story-'.$id),get_permalink($id));}
function tus_admin(){
 if(!current_user_can('publish_posts'))return;
 echo '<div class="wrap" style="max-width:950px"><h1>Social queue</h1><p>Published stories appear here automatically. Review the suggested Facebook copy, then open Facebook to share. Opening Facebook does not publish a post.</p><p><a class="button" href="'.esc_url(tus_page()).'" target="_blank" rel="noopener">Open Today UK Facebook page</a></p>';
 if(isset($_GET['saved']))echo '<p role="status">Social record saved.</p>';
 $posts=get_posts(array('post_type'=>'post','post_status'=>'publish','has_password'=>false,'numberposts'=>100,'category_name'=>'cr-news'));
 if(!$posts)echo '<p>No published CR News stories yet.</p>';
 foreach($posts as $p){tus_queue($p->ID);$state=get_post_meta($p->ID,'_tus_state',true);$copy=get_post_meta($p->ID,'_tus_copy',true);$receipt=get_post_meta($p->ID,'_tus_receipt',true);
 echo '<section style="background:white;border:1px solid #ccd0d4;padding:20px;margin:18px 0"><h2>'.esc_html($p->post_title).'</h2><p>Status: <strong>'.esc_html($state==='shared'?'Shared — editor confirmed':'Ready to share').'</strong></p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="tus_save"><input type="hidden" name="id" value="'.absint($p->ID).'">';wp_nonce_field('tus_save_'.$p->ID);
 echo '<label>Facebook copy<textarea class="widefat" rows="3" name="copy" required>'.esc_textarea($copy).'</textarea></label><p><label>Tracked article link<input class="widefat" readonly value="'.esc_attr(tus_link($p->ID)).'"></label></p><p><a class="button" target="_blank" rel="noopener" href="'.esc_url('https://www.facebook.com/sharer/sharer.php?u='.rawurlencode(tus_link($p->ID))).'">Open Facebook share</a> · Copy the text above into Facebook and select Today UK as the posting identity.</p><details><summary>Record completed share</summary><p><label>Facebook post link<input class="widefat" type="url" name="receipt" value="'.esc_attr($receipt).'"></label></p><p>This confirms a share you have already completed. It does not send anything to Facebook.</p></details><p><button class="button button-primary">Save copy / share record</button></p></form>';
 $c=tue_config($p->ID);echo '<p>Group sharing: use a group covering '.esc_html($c['area']?:'the article’s local area').'. Check its rules before posting. The article link above carries Facebook referral tags; audience and engagement results remain in Analytics and Facebook.</p></section>';
 }
 echo '<p>Automatic Meta API posting is not connected. This queue uses assisted sharing and does not incur AI charges.</p></div>';
}
add_action('admin_post_tus_save',function(){
 $id=absint($_POST['id']??0);if(!current_user_can('publish_posts')||!current_user_can('edit_post',$id)||get_post_type($id)!=='post'||get_post_status($id)!=='publish')wp_die('Not allowed.');check_admin_referer('tus_save_'.$id);
 $copy=sanitize_textarea_field(wp_unslash($_POST['copy']??''));$receipt=esc_url_raw(wp_unslash($_POST['receipt']??''));
 if(!$copy)tue_notice('Add Facebook copy.');
 if($receipt&&(!in_array(strtolower((string)wp_parse_url($receipt,PHP_URL_HOST)),array('facebook.com','www.facebook.com','m.facebook.com'),true)||wp_parse_url($receipt,PHP_URL_SCHEME)!=='https'))tue_notice('Use the HTTPS Facebook post link.');
 update_post_meta($id,'_tus_copy',$copy);update_post_meta($id,'_tus_receipt',$receipt);update_post_meta($id,'_tus_state',$receipt?'shared':'ready');add_post_meta($id,'_tus_audit',array('at'=>gmdate('c'),'actor'=>get_current_user_id(),'state'=>$receipt?'shared':'ready'));
 wp_safe_redirect(admin_url('edit.php?page=todayuk-social&saved=1'));exit;
});
