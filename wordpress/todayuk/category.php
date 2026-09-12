<?php get_header(); $section=get_queried_object();
$descriptions=[
 'cr-news'=>'Local life across Croydon, Purley, Caterham and Coulsdon. Follow the decisions, people and places that shape your area.',
 'planning'=>'What could change on your street? Follow local planning applications, development proposals and council decisions.',
 'whats-on'=>'Make more of your local area. Discover events, activities and things to do as our coverage grows.',
 'business'=>'Meet the businesses on your doorstep, from independent shops to new openings and local services.',
 'lost-found'=>'A place for local lost-and-found reports. Contact details and reports will be checked before publication.',
 'free-stuff'=>'Find a new home for useful things. Local giveaway listings will appear here once reviewed.',
 'sport'=>'Local teams, fixtures and the stories behind the results.',
 'directory'=>'Explore local businesses and services. Our directory is being prepared; reviewed listings will appear here.',
 'community'=>'The people, groups and everyday stories bringing your neighbourhood together.'
];
$is_cr=$section->slug==='cr-news'; $description=$descriptions[$section->slug]??'Local coverage is being prepared for this area. Reviewed stories will appear here as we expand.';
?>
<main class="wrap section-shell" id="main">
<nav class="breadcrumbs" aria-label="Breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">TodayUK</a><span aria-hidden="true">/</span><span><?php echo esc_html(single_cat_title('',false)); ?></span></nav>
<section class="section-hero"><p class="eyebrow"><?php echo $is_cr?'YOUR LOCAL NEWSROOM':'EXPLORE TODAYUK'; ?></p><h1><?php single_cat_title(); ?></h1><p><?php echo esc_html($description); ?></p><?php if($is_cr): ?><div class="area-chips" aria-label="Coverage area"><span>Croydon</span><span>Purley</span><span>Caterham</span><span>Coulsdon</span></div><?php endif; ?></section>
<nav class="section-topics" aria-label="Explore sections"><?php foreach(['cr-news'=>'All CR News','planning'=>'Planning','whats-on'=>"What’s On",'business'=>'Business','sport'=>'Sport','community'=>'Community'] as $slug=>$label): ?><a <?php if($section->slug===$slug)echo 'aria-current="page"'; ?> href="<?php echo esc_url(todayuk_category_url($slug)); ?>"><?php echo esc_html($label); ?></a><?php endforeach; ?></nav>
<div class="section-columns"><section aria-label="Stories"><div class="panel-title"><h2><?php echo $is_cr?'Latest in your area':'Latest updates'; ?></h2></div>
<?php if(have_posts()): ?><div class="section-story-grid"><?php while(have_posts()):the_post();todayuk_card();endwhile; ?></div><div class="pagination"><?php the_posts_pagination(['prev_text'=>'← Newer','next_text'=>'Older →']); ?></div>
<?php else: ?><div class="section-empty"><span class="section-empty-icon" aria-hidden="true">⌖</span><h2><?php echo $is_cr?'Your area. Your stories.':'This section is taking shape'; ?></h2><p><?php echo $is_cr?'We’re preparing our first local reports. Explore the sections below to see what TodayUK will cover.':'There are no published updates in this section yet. Browse other sections while we prepare our first reports.'; ?></p><a class="button" href="<?php echo esc_url(todayuk_category_url($is_cr?'community':'cr-news')); ?>"><?php echo $is_cr?'Explore community →':'Explore CR News →'; ?></a></div><?php endif; ?>
</section><aside class="section-aside"><div class="section-note"><p class="eyebrow">LOCAL COVERAGE</p><h2>Close to home</h2><p>Planning, places, local sport and community life. TodayUK starts in the CR area, with more areas to follow.</p><a href="<?php echo esc_url(home_url('/#find-area')); ?>">Find your area →</a></div><div class="section-note"><h2>Before a story appears</h2><p>Our newsroom reviews reports before publication. Updates and corrections will be shown on the story.</p></div></aside></div>
<section class="section-explore"><div class="panel-title"><h2>Keep exploring</h2></div><div class="coverage-grid"><?php foreach(['planning'=>'Planning & development','whats-on'=>'Things to do','business'=>'Local businesses','community'=>'Community life'] as $slug=>$label): ?><a href="<?php echo esc_url(todayuk_category_url($slug)); ?>"><span><?php echo esc_html($label); ?></span><small>Explore this section →</small></a><?php endforeach; ?></div></section>
</main><?php get_footer(); ?>
