<?php
if (!defined('ABSPATH')) exit;
function tur_areas(){return array('CR0'=>'CR0 · Croydon','CR2'=>'CR2 · South Croydon','CR3'=>'CR3','CR5'=>'CR5 · Coulsdon','CR6'=>'CR6 · Warlingham','CR7'=>'CR7 · Thornton Heath','CR8'=>'CR8 · Purley','CR9'=>'CR9 · Croydon');}
function tur_topics(){return array('planning'=>'Planning','whats-on'=>'What’s on','business'=>'Business','schools'=>'Schools','sport'=>'Sport','community'=>'Community');}
function tur_clean_preferences($raw){
 $raw=is_array($raw)?$raw:array();$home=is_string($raw['home']??null)?$raw['home']:'';
 return array('home'=>isset(tur_areas()[$home])?$home:'','areas'=>array_values(array_intersect(array_keys(tur_areas()),array_filter((array)($raw['areas']??array()),'is_string'))),'topics'=>array_values(array_intersect(array_keys(tur_topics()),array_filter((array)($raw['topics']??array()),'is_string'))),'remember'=>!empty($raw['remember']),'notify'=>!empty($raw['notify']));
}
// Preserve editorial recency within each set; every fifth card explores beyond selected interests.
function tur_mix_feed($preferred,$discovery,$limit=20){
 $out=array();$seen=array();$pi=0;$di=0;
 while(count($out)<$limit&&($pi<count($preferred)||$di<count($discovery))){
  $explore=(count($out)+1)%5===0;
  if(($explore&&$di<count($discovery))||$pi>=count($preferred))$item=$discovery[$di++];else $item=$preferred[$pi++];
  if(isset($seen[$item]))continue;$seen[$item]=true;$out[]=$item;
 }
 return $out;
}
