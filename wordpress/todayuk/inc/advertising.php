<?php
if (!defined('ABSPATH')) { exit; }
function todayuk_ad_placements(){return ['header_banner'=>'Below the header','home_leaderboard'=>'Homepage wide banner','home_rectangle'=>'Homepage lower rectangle','article_middle'=>'Within longer articles','article_bottom'=>'Below articles'];}
function todayuk_ad_provider($value){return in_array($value,['house','adsense','gam'],true)?$value:'house';}
function todayuk_publisher_id($value){$value=trim($value);return preg_match('/^ca-pub-[0-9]{16}$/',$value)?$value:'';}
function todayuk_ad_unit_id($value){$value=trim($value);return preg_match('~^(?:[0-9]{6,20}|/[0-9]+/[a-zA-Z0-9_/-]+)$~',$value)?$value:'';}
add_action('customize_register',function($customizer){
 $customizer->add_section('todayuk_advertising',['title'=>'TodayUK advertising','priority'=>160,'description'=>'Configure placement identifiers here. House ads remain active until the Google integration is connected.']);
 $customizer->add_setting('todayuk_ad_provider',['default'=>'gam','sanitize_callback'=>'todayuk_ad_provider']);
 $customizer->add_control('todayuk_ad_provider',['section'=>'todayuk_advertising','label'=>'Planned ad service','type'=>'select','choices'=>['house'=>'Digital Unicorn house ads','adsense'=>'Google AdSense','gam'=>'Google Ad Manager']]);
 $customizer->add_setting('todayuk_adsense_publisher',['default'=>'ca-pub-1092013053642804','sanitize_callback'=>'todayuk_publisher_id']);
 $customizer->add_control('todayuk_adsense_publisher',['section'=>'todayuk_advertising','label'=>'AdSense publisher ID','description'=>'Public ID starting ca-pub-. Never enter a password.']);
 foreach(todayuk_ad_placements() as $slot=>$label){
  $key='todayuk_ad_unit_'.$slot;
  $customizer->add_setting($key,['default'=>'','sanitize_callback'=>'todayuk_ad_unit_id']);
  $customizer->add_control($key,['section'=>'todayuk_advertising','label'=>$label,'description'=>'AdSense numeric slot ID, or Ad Manager /network/ad-unit path.']);
 }
});

// Insert once, only between top-level paragraphs in sufficiently long classic articles.
// Block articles can contain nested layouts; keep those intact and use the bottom slot.
add_filter('the_content', function ($content) {
 if (!is_singular('post') || !in_the_loop() || !is_main_query() || is_feed() || is_preview() || post_password_required() || has_blocks($content)) return $content;
 if (str_word_count(wp_strip_all_tags($content)) < 600) return $content;
 $parts = preg_split('~(</p>)~i', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
 // Only simple paragraph markup is eligible: never cut lists, figures or other containers.
 if (preg_match('~<(?:div|section|aside|blockquote|ul|ol|table|figure)\b~i', $content)) return $content;
 $paragraphs = 0;
 foreach ($parts as $index => $part) {
  if (strtolower($part) === '</p>' && ++$paragraphs === 4) {
   ob_start(); todayuk_ad('article_middle'); $parts[$index] .= ob_get_clean(); break;
  }
 }
 return implode('', $parts);
}, 20);
