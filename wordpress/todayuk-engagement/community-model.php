<?php
if (!defined('ABSPATH')) exit;
function tuc_types(){return array('news'=>'News tip','planning'=>'Planning','event'=>"What's On",'directory'=>'Local Directory','business'=>'Business update','schools'=>'Schools','sport'=>'Local sport','lost'=>'Lost & Found','free'=>'Free items','competition'=>'Business competition');}
function tuc_detect($text){
 foreach(array('competition'=>'/competition|giveaway|prize draw/i','free'=>'/free (sofa|item|furniture)|give away|unwanted|collection only/i','lost'=>'/lost |missing |found (cat|dog|pet|keys|phone)/i','planning'=>'/planning|development|housing proposal/i','event'=>'/concert|festival|event|tickets|workshop/i','schools'=>'/school|pupils|education/i','sport'=>'/football|rugby|cricket|match report/i','business'=>'/opens|opening|closure|relocat|business/i') as $type=>$pattern)if(preg_match($pattern,$text))return $type;
 return 'news';
}
function tuc_fields(){return array('area'=>'Postcode district','locality'=>'Locality','organisation'=>'Organisation / organiser','venue'=>'Venue / address (public)','start'=>'Start date and time','end'=>'End date and time','recurrence'=>'Recurrence','category'=>'Category','price'=>'Price or Free','booking'=>'Booking / website link','source'=>'Source link','status'=>'Current status','business_change'=>'Opening / closure / relocation','collection'=>'Collection window','removal'=>'Stairs / removal requirements','vehicle'=>'Car / van suitability','rules'=>'Competition rules','sponsor'=>'Sponsor','prize'=>'Donated prize / competition prize');}
function tuc_quiz_parse($text){
 $out=array();foreach(preg_split('/\r?\n/',trim($text)) as $line){if(trim($line)==='')continue;$p=array_map('trim',explode('|',$line));if(count($p)!==7||!in_array($p[0],array('news','local'),true)||!ctype_digit($p[6])||(int)$p[6]<1||(int)$p[6]>4)return false;if(count(array_unique(array_slice($p,2,4)))!==4||in_array('',array_slice($p,1,5),true))return false;$out[]=array('kind'=>$p[0],'question'=>$p[1],'answers'=>array_slice($p,2,4),'correct'=>(int)$p[6]-1);}
 if(count($out)<2||count($out)>20||count(array_unique(array_column($out,'kind')))!==2)return false;return $out;
}
function tuc_quiz_score($questions,$answers){$score=0;if(count($answers)!==count($questions))return false;foreach($questions as $i=>$q){if(!isset($answers[$i])||!in_array((string)$answers[$i],array('0','1','2','3'),true))return false;if((int)$answers[$i]===$q['correct'])$score++;}return $score;}
