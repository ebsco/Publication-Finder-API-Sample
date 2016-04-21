<?php 
/**
 * Layout view
 * LOCATION: http://widgets.ebscohost.com/prod/ftf-atoz/app/views/layout.html.php
 * APP NAME: Publication Finder API Sample
 **/
?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>AtoZ Browse App</title>
        <link rel="stylesheet" href="web/styles.css" type="text/css" media="screen" />
		<link rel="shortcut icon" href="web/favicon.ico" />   
		<script type="text/javascript" src="web/jquery.js" ></script>  
    </head>

    <body>
        <div class="container">
        <div class="header">
			<h1 style="text-align: center;">A-Z Publication Browse</h1>
        </div>

        <div class="content">
            <?php echo $content; ?>
        </div>
<?php 
$xml ="Config.xml";
$dom = new DOMDocument();
$dom->load($xml);  
$version = $dom ->getElementsByTagName('Version')->item(0)->nodeValue;
?>
        <div class="footer">        
            <div class="span-5">

           </div>
            <div style="text-align: right;
    font-size: 85%; 
    color: lightgray;
    min-height: 10px;
    position: relative;"><?php echo $version ?></div>
        </div>
        </div>
    </body>
</html>