<html>
	<head>
		<title>{{title}}</title>
		<meta charset="UTF-8"/>
		<link href="https://fonts.googleapis.com/css?family=Roboto:400,900" rel="stylesheet">
		<?php foreach ($stylesheets as $style): ?>
		<link rel="stylesheet" type="text/css" href="{{style}}">
		<?php endforeach ?>

		<?php foreach ($scripts as $script): ?>
			<script src="{{script}}"></script>
		<?php endforeach ?>
	</head>
	<body>