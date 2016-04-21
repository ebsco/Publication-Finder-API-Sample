<?php
/**
 * Basic search
 * LOCATION: http://widgets.ebscohost.com/prod/ftf-atoz/basic_search.php
 * APP NAME: Publication Finder API Sample
 **/
error_reporting(E_ERROR | E_PARSE);
require 'app/app.php';

render('basic_search.html', 'layout.html');
?>