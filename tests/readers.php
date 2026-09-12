<?php
define('ABSPATH',__DIR__);
require __DIR__.'/../wordpress/todayuk-engagement/reader-model.php';
function verify($ok,$message){if(!$ok)throw new Exception($message);}
$p=tur_clean_preferences(array('home'=>'CR8','areas'=>array('CR0','CR0','evil'),'topics'=>array('schools','evil'),'role'=>'administrator','remember'=>'1'));
verify($p['home']==='CR8'&&$p['areas']===array('CR0')&&$p['topics']===array('schools'),'Validate explicit selections');
verify(!isset($p['role'])&&!$p['notify']&&$p['remember'],'Preferences cannot change membership or opt in by default');
verify(tur_clean_preferences(array('home'=>'invalid'))['home']==='','Reject unknown home');
verify(tur_clean_preferences(null)['remember']===false,'No reading history by default');
$feed=tur_mix_feed(range(1,20),range(21,30),20);
verify(count($feed)===20&&count(array_unique($feed))===20,'Unique feed');
verify($feed[4]===21&&$feed[9]===22&&$feed[14]===23&&$feed[19]===24,'Twenty percent discovery');
verify(tur_mix_feed(array(),array(3,2,1),20)===array(3,2,1),'Preserve editorial order without matches');
verify(tur_mix_feed(array(1,2),array(2,3),20)===array(1,2,3),'Deduplicate sparse feed');
echo "Reader preference and feed checks passed\n";
