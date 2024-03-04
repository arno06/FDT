<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={$configuration.global_encoding}" >
        <base href="{$configuration.server_url}"/>
		<title>{$head.title}</title>
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
		<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300..800&display=swap" rel="stylesheet">
		<meta name="description" content="{$head.description}"/>
{foreach from=$styles item=style}
		<link type="text/css" rel="stylesheet" href="{$style}">
{/foreach}
{foreach from=$scripts item=script}
        <script type="text/javascript" src="{$script}"></script>
{/foreach}
	</head>
	<body>