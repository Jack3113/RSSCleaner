<?php

//error_reporting(E_ALL);
//ini_set('display_errors', TRUE);
//ini_set('display_startup_errors', TRUE);
header('Content-Type: application/xml; charset=utf-8');

$url	 = 'http://feeds.feedburner.com/LeJournalduGamer';
$curl	 = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_TIMEOUT, 10);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$content = curl_exec($curl);
$content = preg_replace('#<\?xml-stylesheet.*\?>#isU', '', $content);

$xml	 = new DomDocument();
$xml->loadXML($content);
$items	 = $xml->getElementsByTagName('item');

for ($k = 0; $k < $items->length; $k++)
{
	$descriptions	 = $items->item($k)->getElementsByTagName('description');
	$urls			 = $items->item($k)->getElementsByTagName('link');
	$title			 = $items->item($k)->getElementsByTagName('title')->item(0)->nodeValue;
	$url			 = $urls->item(0)->nodeValue;
	$urlInfo		 = parse_url($url);
	$urlCleaned		 = $urlInfo['scheme'] . '://' . $urlInfo['host'] . $urlInfo['path'];
	$urlDivided		 = explode('/', $urlInfo['path']);
	$name			 = $urlDivided[count($urlDivided) - 2];
	$cache			 = 'cache/' . $name;
	if (!file_exists($cache))
	{
		$curl	 = curl_init();
		curl_setopt($curl, CURLOPT_URL, $urlCleaned);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$content = curl_exec($curl);
		file_put_contents($cache, $content);
	}
	$content = file_get_contents($cache);
	$html	 = new DomDocument();
	$html->loadHTML($content);

	$scripts = $html->getElementsByTagName('script');
	while ($script	 = $scripts->item(0))
		$script->parentNode->removeChild($script);

	$section = $html->getElementsByTagName('section')->item(0);

	$desc		 = '';
	$divs		 = $section->getElementsByTagName('div');
	foreach ($divs as $div)
		if ($div->attributes->getNamedItem("class")->nodeValue == 'post-content')
			$desc		 = $html->saveHTML($div);



	$items->item($k)->removeChild($descriptions->item(0));

	$new = $xml->createDocumentFragment();
	$new->appendXML('<description><![CDATA[<h2>' . $title . '</h2><div>' . $desc . '</div>]]></description>');
	$items->item($k)->appendChild($new);
}
echo $xml->saveXML();
