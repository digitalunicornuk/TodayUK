<?php
define('ABSPATH',__DIR__);
function sanitize_text_field($s){return trim(strip_tags((string)$s));}
require __DIR__.'/../wordpress/todayuk-engagement/catalog.php';
function check($condition,$message){if(!$condition)throw new Exception($message);}
check(count(tue_catalog())===16,'All blueprint tools represented');
check(in_array('nimby',tue_suggest('Planning proposal for new homes')),'Planning tool');
check(in_array('attendance',tue_suggest('Croydon Harvest festival')),'Event tool');
check(!in_array('nimby',tue_suggest('School safety advice')),'No irrelevant planning vote');
check(in_array('mine',tue_suggest('Missing cat')),'Private claim tool');
$c=tue_clean_config(array('mode'=>'invalid','tools'=>array('poll','evil'),'question'=>'<script>bad</script>Question','options'=>"Yes\nNo\nYes\n\n"));
check($c['mode']==='auto','Invalid mode safe default');check($c['tools']===array('poll'),'Reject unknown tools');
check($c['options']===array('Yes','No'),'Deduplicate poll choices');check(strpos($c['question'],'<')===false,'No markup retained');
echo "Engagement catalog checks passed\n";
