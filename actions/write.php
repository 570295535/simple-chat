<?php

header("Content-type: application/json; charset=utf-8");

require_once('../config.php');
require_once('configmsg.php');

if ($logger["messages"])
	require("logger.php");

$dom = new DOMDocument();
$dom->load($msgSrc);
$theMsg = new XMLWriter();

$msg  = format($_POST['msg']);
$who  = $_POST['nick'];
$when = time();

if (!empty($_POST['msg'])) {

	//WRITE PART
	$theMsg->openURI($msgSrc);
	$theMsg->setIndent(true);
	$theMsg->startDocument("1.0", "UTF-8");

	$theMsg->text("<messages>");

	// append old messages
	foreach ($dom->documentElement->childNodes as $node)
		$theMsg->text($dom->saveXML($node));

	// append new message
	$theMsg->startElement("msg");

	$theMsg->writeElement("time", $when);
	$theMsg->writeElement("user", $who);
	$theMsg->startElement("data");
	$theMsg->writeCData($msg);
	$theMsg->endElement();

	$theMsg->endElement(); // </msg>
	$theMsg->text("</messages>");

	$theMsg->endDocument();

	//LOG PART
	if ($logger["messages"])
		write_log($when, $who, "write", $logger["msgcontent"] ? $msg : null);


	//RETURN PART
	$xml  = simplexml_import_dom($dom);
	$nmsg = count($xml->msg);

	$lastAut  = $xml->msg[$nmsg-1]->user;
	$lastTime = (int)$xml->msg[$nmsg-1]->time;
	$out = "";

	if (date('Yz', $when) > date('Yz', $lastTime)) {
		$out .= '<p class="date">' . date('d/m/y', $when) . "</p>\n";
		$out .= '<p class="time">' . date('H:i', $when) . "</p>\n";
		$lastAut="";
	}
	elseif ((date('Gi', $when) >= date('Gi', $lastTime) + $timestampInter) || (is_int(date("i", $when) / $timestampEach) && !is_int(date("i", $lastTime) / $timestampEach) )) {
		$out .= '<p class="time">' . date('H:i', $when) . "</p>\n";
		$lastAut="";
	}

	$out .= '<p>';
	$out .= '<span class="name">' . ($who == $lastAut ? "..." : $who.":") . ' </span>';
	$out .= '<span class="txt">' . $msg . '</span>';
	$out .= "</p>\n";

	echo '{ "content": '.json_encode($out).' }';

}

?>
