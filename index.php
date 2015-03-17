<?php

date_default_timezone_set('America/New_York');

$last_month = mktime(0,0,0,date('m')-1, 1, date('Y'));

define('USERNAME', '<username>');
define('PASSWORD', '<password>');
define('MONTH', date('Y-m', $last_month));

$sitenames = array(
  'sitename1' => 'Site Name #1',
  'sitename2' => 'Site Name #2',
  'sitename3' => 'Site Name #3',
);

//minutes in the month
$days_last_month = date("t", $last_month);
$minutes_last_month = $days_last_month * 24 * 60;

$interval = ($days_last_month+date('d'));
echo 'INTERVAL: '.$interval."\n";
echo 'MONTH: '.MONTH."\n";

// 

foreach ($sitenames as $docroot => $title) {

	$url = "https://perf-mon.acquia.com/site_downtime.php?stage=mc&period=d&interval=".$interval."&sitename=".$docroot;
	
	$process = curl_init($url);

	//echo "https://perf-mon.acquia.com/site_downtime.php?stage=mc&period=d&interval=".$interval."&sitename=".$docroot; exit;
	curl_setopt($process, CURLOPT_HTTPHEADER, array('Content-Type: text/html', $additionalHeaders));
	curl_setopt($process, CURLOPT_HEADER, 1);
	curl_setopt($process, CURLOPT_USERPWD, USERNAME . ":" . PASSWORD);
	curl_setopt($process, CURLOPT_TIMEOUT, 30);
	curl_setopt($process, CURLOPT_POST, 1);
	curl_setopt($process, CURLOPT_POSTFIELDS, $payloadName);
	curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
	$html = curl_exec($process);
	curl_close($process);

	sleep(1);

	list($waste, $downtime) = explode("Detected as having downtime at: <br><br>", $html);
	
	//echo 'DOWNTIME<xmp>'; print_r($downtime); echo '</xmp>';
	
	$occurances = explode('<br>', $downtime);
	
	//echo 'OCCURANCES<xmp>'; print_r($occurances); echo '</xmp>';
	
	$minutes = 0;
	
	foreach($occurances as $occurance) {
		
		//echo 'TEST: '.substr($occurance, 0, 7).' == '.MONTH."\n";
		
		if(substr($occurance, 0, 7) == MONTH) {
			$minutes++;
		}	
		
	}
	
	$docroot_uptime = (100 - ($minutes / $minutes_last_month * 100));
	
	$uptime_environments[] = array(
		'title' => $title,
		'link' => $url,
		'docroot' => $docroot,
		'minutes' => $minutes,
		'docroot_uptime' => $docroot_uptime,
	);
	
}

$output = array();

usort($uptime_environments, "uptime_sort");
$uptime_environments = array_reverse($uptime_environments);

foreach($uptime_environments as $environment) {
	$output[] = '<tr dir="ltr">';
	$output[] = '<td class="s2">'.$environment['title'].'</td>';
	//$output[] = '<td class="s3"><a href="'.$environment['link'].'" target="_blank">'.$environment['docroot'].'</a></td>';
	$output[] = '<td class="s3">'.$environment['docroot'].'</td>';
	$output[] = '<td class="s4">'.$environment['minutes'].'</td>';
	$output[] = '<td class="s5">'.number_format($environment['docroot_uptime'], 2).'%</td>';
	$output[] = '<td class="s6">'.(($environment['docroot_uptime'] > 99.95) ? 'Yes' : 'No').'</td>';
	$output[] = '</tr>';
}

file_put_contents('application-uptime.html', implode("\n", $output));
exit;

function uptime_sort($a, $b) {
	if ($a['minutes'] == $b['minutes']) {
		return 0;
	}
	return ($a['minutes'] < $b['minutes']) ? -1 : 1;
}
