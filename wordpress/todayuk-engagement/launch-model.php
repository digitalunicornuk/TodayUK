<?php
if(!defined('ABSPATH'))exit;
function tul_slots(){return array('header_banner'=>'Header banner','home_leaderboard'=>'Homepage leaderboard','home_rectangle'=>'Homepage rectangle','article_middle'=>'Article middle','article_bottom'=>'Article bottom');}
function tul_unit($s){return is_string($s)&&preg_match('~^/[0-9]+/[a-zA-Z0-9_/-]+$~D',$s)?$s:'';}
function tul_org_key($url){$host=strtolower((string)parse_url($url,PHP_URL_HOST));return preg_replace('/^www\./','',$host);}
function tul_campaign_valid($c){return in_array($c['type']??'',array('sponsorship','standard','time'),true)&&isset(tul_slots()[$c['placement']??''])&&!empty($c['name'])&&!empty($c['advertiser'])&&!empty($c['start'])&&!empty($c['end'])&&strtotime($c['start'])!==false&&strtotime($c['end'])!==false&&strtotime($c['end'])>strtotime($c['start'])&&is_numeric($c['goal']??null)&&(float)$c['goal']>0&&(($c['type']??'')!=='sponsorship'||(float)$c['goal']<=100);}
function tul_can_handoff($r){return ($r['class']??'')==='commercial'&&!empty($r['permission'])&&!empty($r['organisation'])&&!empty($r['website'])&&tul_org_key($r['website'])!=='';}
