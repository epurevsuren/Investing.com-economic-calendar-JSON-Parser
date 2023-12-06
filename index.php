<?php
date_default_timezone_set('America/New_York');
header("Content-Type: application/json");

include_once(getcwd() . "/jsonParser/PhpSimple/HtmlDomParser.php");
use \PhpSimple\HtmlDomParser;

$opts = array(
  'http'=>array(
    'method'=>"GET",
    'header'=>"Accept-language: en\r\n" .
    		  "Connection: keep-alive\r\n" .
    		  "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.66 Safari/537.36\r\n"
  )
);

$context = stream_context_create($opts);

$dom = HtmlDomParser::file_get_html("https://sslecal2.investing.com/?calType=week&countries=25,6,37,72,22,17,39,10,35,7,43,60,36,110,26,12,4,5", false, $context, 0);
$elems = $dom->getElementById("#ecEventsTable")->find("tr[id*='eventRowId']");
$data = [];

function sanitize($str) {
	return trim(str_replace("&nbsp;", "", $str));
}

foreach($elems as $element) {
	try{
		$date = new DateTime(isset($element->attr["event_timestamp"]) ? $element->attr["event_timestamp"] : '1991-01-01 00:00:00', new DateTimeZone('Europe/London'));
		$date->setTimezone(new DateTimeZone('America/New_York'));
		if($date->format('I')==1)
		{
			$date->modify('+1 hour');
		}
		array_push($data, [
			"economy" => sanitize($element->find("td.flagCur")[0]->text()),
			"impact" =>  count($element->find("td.sentiment")[0]->find("i.grayFullBullishIcon")),
			"data" => $date->format('Y-m-d H:i:s'),
			"name" => count($name = $element->find("td.event")) > 0 ? sanitize($name[0]->text()) : null,
			"actual" => count($actual = $element->find("td.act")) > 0 ? sanitize($actual[0]->text()) : null,
			"forecast" => count($forecast = $element->find("td.fore")) > 0 ? sanitize($forecast[0]->text()) : null,
			"previous" => count($previous = $element->find("td.prev")) > 0 ? sanitize($previous[0]->text()) : null,
		]);
		
	}
	catch (Throwable $t) {
		//nothing
	}
}

echo json_encode($data);
