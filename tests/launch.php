<?php
define('ABSPATH',__DIR__);require __DIR__.'/../wordpress/todayuk-engagement/launch-model.php';
function ok($b,$s){if(!$b)throw new Exception($s);}
ok(tul_unit('/12345/todayuk/header')==='/12345/todayuk/header','Valid GAM path');ok(tul_unit('ca-pub-123')==='','Reject AdSense ID as GAM path');ok(tul_unit('/12/<script>')==='','Reject markup');
$c=array('name'=>'Test','advertiser'=>'Example','type'=>'sponsorship','placement'=>'header_banner','start'=>'2026-10-01','end'=>'2026-10-10','goal'=>50);
ok(tul_campaign_valid($c),'Valid sponsorship');$c['goal']=101;ok(!tul_campaign_valid($c),'Reject over 100 percent');$c['goal']=50;$c['end']='2026-09-01';ok(!tul_campaign_valid($c),'Reject backwards dates');$c['end']='not a date';ok(!tul_campaign_valid($c),'Reject invalid dates');
ok(tul_org_key('https://WWW.Example.com/about')==='example.com','Domain matching normalisation');$r=array('class'=>'reader','permission'=>'Requested contact','organisation'=>'Example','website'=>'https://example.com');ok(!tul_can_handoff($r),'Reader excluded');$r['class']='public_sector';ok(!tul_can_handoff($r),'Public sector excluded');$r['class']='community';ok(!tul_can_handoff($r),'Community excluded');$r['class']='commercial';ok(tul_can_handoff($r),'Permissioned commercial record');$r['permission']='';ok(!tul_can_handoff($r),'Permission evidence required');echo "Launch controls passed\n";
