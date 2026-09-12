<?php
if (!defined('ABSPATH')) { exit; }
add_action('after_setup_theme', function () {
 add_theme_support('title-tag'); add_theme_support('post-thumbnails');
 add_theme_support('responsive-embeds'); add_theme_support('html5', ['search-form','gallery','caption','style','script']);
 register_nav_menus(['primary'=>'Main navigation']);
});
add_action('wp_enqueue_scripts', function () { wp_enqueue_style('todayuk', get_stylesheet_uri(), [], '1.3.0'); });
function todayuk_sections() { return ['local-news'=>'Local news','planning'=>'Planning','whats-on'=>"What’s on",'business'=>'Business','schools'=>'Schools','sport'=>'Sport']; }
function todayuk_category_url($slug) { $modules=['planning'=>'planning','whats-on'=>'event','directory'=>'directory','business'=>'business','schools'=>'schools','sport'=>'sport','lost-found'=>'lost','free-stuff'=>'free','community'=>'news']; if(function_exists('tuc_url')&&isset($modules[$slug]))return add_query_arg('section',$modules[$slug],tuc_url('local'));  $term=get_category_by_slug($slug); return $term ? get_category_link($term) : home_url('/?category_name='.rawurlencode($slug)); }
function todayuk_card($lead=false) { ?>
<article class="card <?php echo $lead ? 'lead' : ''; ?>">
<?php if(has_post_thumbnail()): ?><a href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php the_post_thumbnail($lead?'large':'medium_large'); ?></a><?php endif; ?>
<p class="eyebrow"><?php $cats=get_the_category();echo esc_html($cats ? $cats[0]->name : 'CR News'); ?></p>
<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
<div class="meta"><time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date('j F Y')); ?></time></div>
<?php if($lead): ?><p><?php echo esc_html(wp_trim_words(get_the_excerpt(),35)); ?></p><?php endif; ?>
</article><?php }

add_action('wp_enqueue_scripts', function(){wp_enqueue_script('todayuk-home',get_template_directory_uri().'/assets/home.js',[], '1.3.0',true);wp_localize_script('todayuk-home','todayukHome',['cr'=>todayuk_category_url('cr-news'),'endpoint'=>rest_url('todayuk/v1/latest'),'selected'=>todayuk_selected_area(),'areas'=>todayuk_territories()]);});
function todayuk_ad($slot){
 $unit=apply_filters('todayuk_gam_ad_unit',get_theme_mod('todayuk_ad_unit_'.$slot,''),$slot);
 ?><aside class="ad-placement <?php echo esc_attr($slot); ?>" aria-label="Advertisement" data-ad-slot="<?php echo esc_attr($slot); ?>" data-ad-unit="<?php echo esc_attr($unit); ?>"><div class="ad-label">Advertisement · Digital Unicorn</div><div id="gam-<?php echo esc_attr($slot); ?>" class="gam-slot"></div><a class="house-ad" href="https://digitalunicorn.co.uk/" target="_blank" rel="noopener noreferrer"><span class="du-brand">Digital Unicorn<small>Connection established</small></span><strong><?php echo $slot==='home_rectangle'?'IT SUPPORT FOR LOCAL BUSINESSES':'WEBSITES THAT GET YOU NOTICED'; ?></strong><span class="ad-services">Web Design · SEO · AI Search Optimisation · IT Support</span><span class="ad-cta">Find out more →</span></a></aside><?php
}
// Create only the categories this theme uses; preserve existing content and launch settings.
add_action('after_switch_theme',function(){foreach(['cr-news'=>'CR News','rh-news'=>'RH News','sm-news'=>'SM News','br-news'=>'BR News','tn-news'=>'TN News','planning'=>'Planning','whats-on'=>"What’s On",'business'=>'New Near You','lost-found'=>'Lost & Found','free-stuff'=>'Free Stuff','sport'=>'Local Sport','directory'=>'Directory','community'=>'Community'] as $slug=>$name){if(!term_exists($slug,'category'))wp_insert_term($name,'category',['slug'=>$slug]);}});

require_once get_template_directory().'/inc/advertising.php';

require_once get_template_directory().'/inc/network.php';
