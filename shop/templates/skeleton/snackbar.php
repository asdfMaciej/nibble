<?php
	$colors = [
		200 => ["#83ce1a", "#000000"],
		400 => ["#ce1f19", "#FFFFFF"],
		403 => ["#ce1f19", "#FFFFFF"],
		404 => ["#ce1f19", "#FFFFFF"]
	];
	if (array_key_exists($code, $colors)) {
		$color = $colors[$code][0];
		$font = $colors[$code][1];
	} else {
		$color = "#ce196a";
		$font = "#FFFFFF";
	}
?>
<div id="header_message" style="background-color: <?=$color?>; color: <?=$font?>">
	<?=$message?>
</div>