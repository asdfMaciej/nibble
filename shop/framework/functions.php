<?php
function json_headers() { 
	header("Access-Control-Allow-Origin: *");
	header("Content-Type: application/json");
}

function getIp() {
	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
				$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$ip = $_SERVER['HTTP_X_FORWARDED_FOR']; // security risk, but proxy on mikr.us
		} else {
				$ip = $_SERVER['REMOTE_ADDR'];
		}
	
	//$ip = $_SERVER['REMOTE_ADDR'];
	return $ip;
}

function get($id) {
	if(isset($_GET[$id])) {
    	return $_GET[$id];
	} else {
		return "";
	}
}

function post($id) {
	if(isset($_POST[$id])) {
    	return $_POST[$id];
	} else {
		return "";
	}
}

function retrieve($method, $string) {
	if ($method == "post") {
		return post($string);
	} elseif ($method == "get") {
		return get($string);
	} 
}

function json($str) {
	return json_encode($str, JSON_UNESCAPED_SLASHES);
}

function split($string) {
	$array = preg_split("/\r\n|\n|\r/", $string);
	return $array;
}

function markdown($string) {
	// it's not exactly markdown
	// but it should do its job (prove me wrong)
	// ~ Maciej Kaszkowiak, 27.05.2018
	$string = htmlspecialchars($string);
	$lines = split($string);
	if (count($lines) > 100) {
		return 0;
	}
	$html = "";
	foreach ($lines as $line) {
		$h2 = 0;
		$newline = 1;
		if (substr($line, 0, 2) == "# ") {
			$html .= "<h2>";
			$h2 = 1;
			$newline = 0;
			$line = substr($line, 2);
		}
		if (substr($line, 0, 3) == "---") {
			$html .= "<hr>";
			$newline = 0;
			$line = substr($line, 3);
		}
		$html .= $line;
		if ($h2) {
			$html .= "</h2>";
		}
		if ($newline) {
			$html .=  "<br>";
		}
		$html .= "\n";
	}
	$bold_lines = explode("**", $html);
	if (count($bold_lines) < 3) {
		return $html;
	}
	$html = "";
	$opened = 0;
	foreach ($bold_lines as $line) {
		$html .= $line;
		if (!$opened) {
			$html .= "<b>";
			$opened = 1;
		} else {
			$html .= "</b>";
			$opened = 0;
		}
	}
	if ($opened) {
		$html = substr($html, 0, -3);
	}
	return $html;
}

?>