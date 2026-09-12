<?php
if(!defined('ABSPATH'))exit;
function todayuk_territories(){return array('cr'=>'CR News','rh'=>'RH News','sm'=>'SM News','br'=>'BR News','tn'=>'TN News');}
function todayuk_selected_area(){
 $requested=strtolower(sanitize_key($_GET['area']??''));if(isset(todayuk_territories()[$requested]))return $requested;
 if(is_user_logged_in()&&function_exists('tur_preferences')){$home=tur_preferences(get_current_user_id())['home'];$prefix=strtolower(preg_replace('/[0-9].*/','',$home));if(isset(todayuk_territories()[$prefix]))return $prefix;}return 'cr';
}
function todayuk_latest_data($area){$posts=get_posts(array('post_type'=>'post','post_status'=>'publish','has_password'=>false,'numberposts'=>5,'category_name'=>$area.'-news','orderby'=>'date','order'=>'DESC'));return array_map(function($p){return array('title'=>get_the_title($p),'url'=>get_permalink($p),'date'=>get_the_date('j M Y',$p),'image'=>get_the_post_thumbnail_url($p,'thumbnail')?:'');},$posts);}
add_action('rest_api_init',function(){register_rest_route('todayuk/v1','/latest',array('methods'=>'GET','permission_callback'=>'__return_true','callback'=>function($r){$area=sanitize_key($r->get_param('area')?:'cr');if(!isset(todayuk_territories()[$area]))return new WP_Error('area','Choose a supported area.',array('status'=>400));return rest_ensure_response(array('area'=>$area,'label'=>todayuk_territories()[$area],'stories'=>todayuk_latest_data($area)));}));});
