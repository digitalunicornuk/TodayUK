<?php
if (!defined('ABSPATH')) exit;
function tue_catalog() {
 return array(
 'nimby'=>array('NIMBY rating','choice',array('1'=>'Strongly oppose','2'=>'Oppose','3'=>'Neutral','4'=>'Support','5'=>'Strongly support')),
 'sentiment'=>array('What do you think?','choice',array('good'=>'Good news','bad'=>'Bad news')),
 'poll'=>array('Poll','choice',array()),
 'track'=>array('Track this story','toggle',array()),
 'seen'=>array('I’ve seen this','toggle',array()),
 'mine'=>array('This might be mine','message',array()),
 'attendance'=>array('Your plans','choice',array('going'=>'Going','interested'=>'Interested','was_there'=>'I was there')),
 'helpful'=>array('Helpful','toggle',array()),
 'remind'=>array('Remind me','reminder',array()),
 'share'=>array('Share locally','share',array()),
 'update'=>array('Submit an update','message',array()),
 'report'=>array('Report an issue','message',array()),
 'claim'=>array('Claim / respond','message',array()),
 'follow_area'=>array('Follow area','toggle',array()),
 'follow_topic'=>array('Follow topic','toggle',array()),
 'follow_org'=>array('Follow organisation','toggle',array())
 );
}
function tue_suggest($text) {
 $tools=array('helpful','track','share','update','report');
 if(preg_match('/planning|development|infrastructure levy|housing proposal/i',$text)) $tools[]='nimby';
 if(preg_match('/festival|concert|event|harvest|what.s on/i',$text)) $tools[]='attendance';
 if(preg_match('/missing|lost pet|lost cat|lost dog|found pet|lost property/i',$text)) { $tools[]='seen'; $tools[]='mine'; }
 if(preg_match('/business opening|new shop|restaurant opens|business directory/i',$text)) $tools[]='claim';
 return array_values(array_unique($tools));
}
function tue_clean_config($raw) {
 $catalog=tue_catalog(); $out=array('mode'=>in_array($raw['mode']??'',array('auto','custom','off'),true)?$raw['mode']:'auto');
 $out['tools']=array_values(array_intersect(array_keys($catalog),(array)($raw['tools']??array())));
 foreach(array('question','area','topic','org') as $k) $out[$k]=substr(sanitize_text_field($raw[$k]??''),0,180);
 $out['options']=array_values(array_unique(array_filter(array_map(function($v){return substr(sanitize_text_field($v),0,100);},explode("\n",(string)($raw['options']??''))))));
 $out['options']=array_slice($out['options'],0,6);
 $out['event_date']=preg_match('/^\d{4}-\d{2}-\d{2}$/',$raw['event_date']??'')?$raw['event_date']:'';
 return $out;
}
