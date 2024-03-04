<!DOCTYPE html>
<html>
    <head>
        <title></title>
        <link href='http://fonts.googleapis.com/css?family=Lato:400,400italic' rel='stylesheet' type='text/css'>
		<style>
			body{
				font-family: Lato, Helvetica, Arial, sans-serif;
				background:#F5F5F5;
			}
			header{
				position:relative;
				width:960px;
				margin:0 auto;
			}
			header h1{
				height:107px;
				line-height: 107px;
				padding:0;
				margin:0;
				font-size:30px;
				font-weight:normal;
			}

			header h2{
				margin-top: -45px;
				font-size: 18px;
				margin-left: 181px;
			}

			header menu{
				position:absolute;
				right:0;
				top:30px;
				padding:0;
				margin:0;
			}

			header menu li{
				display:inline-block;

			}

			header menu li a{
				color:#777;
				display:inline-block;
				padding:10px 5px;
				text-decoration: none;;
				border-bottom:3px solid rgba(0, 0, 0, 0);
				transition:border-color .3s;
			}

			header menu li a:hover{
				border-bottom:3px solid #777;
			}

			.content
			{
				padding:15px;
				width:930px;
				background:#ffffff;
				border:solid 1px #D2D2D2;
				margin:0 auto;
			}

			.content .intro{

			}

			.content .intro h1{
				font-weight:normal;
				float:left;
			}

			.content .intro h1 span{
				font-size:14px;
				font-weight:normal;
				margin-left:10px;
				color:#aaa;
			}

			.content .intro .about{
				float:right;
				text-align: right;
				font-size:13px;
				padding-top:25px;
			}
			.content .intro .about{
				line-height: 17px;
			}

			.content .intro .description{
				clear:both;
			}

			.content h2{
				font-weight:normal;
				font-size:30px;
				display:inline-block;
			}

			.content span.scope, .content span.type{
				font-style:italic;
				color:#666;
			}

			.content .property{
				border-top: dashed 1px #aaa;
				margin-top:25px;
			}

			.content h3{
				font-weight:normal;
			}

			.content pre{
				padding:15px;
				background:#F5F5F5;
				overflow:auto;
				border:solid 1px #CFCFCF;
				color:#444;
			}

			.content .method{
				border-top: dashed 1px #444;
				margin-top:25px;
			}

			.content .method.depreciated div{
				opacity:.5;
			}

			.content .method div{
				padding:0 10px;
			}

			.content .method .parameters{
				padding:0;
			}

			.content .method .description, .content .property .description{
				font-size:14px;
			}

			.content .method .parameters table, .content .annexes table{
				width:100%;
				border-collapse: collapse;
				border:solid 1px #DDDDDD;
			}

			.content .method .parameters table td, .methods .method .parameters table th, .content .annexes table td, .content .annexes table th{
				border:solid 1px #DDDDDD;
				padding:5px;
			}

			.content .method .parameters table th, .content .annexes table th{
				background-color: #f0f0f0;
				color:#555;
				font-size:14px;
				padding:7px;
			}

			.content .method .parameters table td.varname, .content .annexes table td.nom{
				width:200px;
			}

			.content .method .parameters table td.varname span, .content .annexes table td.nom span{
				font-size:13px;
				font-family: monospace;
				background:#f7f7f9;
				border:solid 1px #e1e1e8;
				color:#48484c;
				display:inline-block;
				padding:3px;
				border-radius:2px;
			}

			.content .method .parameters table td.vartype, .content .annexes table td.type{
				text-align: center;
				width:80px;
			}

			.content .method .parameters table td.vardesc, .content .annexes table td.description{
				font-size:13px;
			}

			.content .method .vartype span, .content .annexes table td.type span{
				font-style:italic;
				font-size:12px;
			}
			.content .annexes table td.obligatoire{text-align:center;font-size:12px;}

			.content .annexes{
				margin-top:30px;
				border-top:dotted 1px #666;
			}

			.content .annexes h1{
				font-weight:normal;
			}

			footer{
				clear:both;
				padding:15px;
				width:930px;
				text-align: center;
				font-size:13px;
				margin:0 auto;
			}

		</style>
    </head>

    <body>
        <header>
            <h1>Framework PHP</h1>
            <h2>v3.0.0</h2>
            <menu>
                <li><a href="{if $details}../{/if}index.html">Readme</a></li>
                <li><a href="{if $details}../{/if}classes.html">Classes</a></li>
            </menu>
        </header>
    <div class="content">