<?php
/**
 * App
 * Contains general functions
 * LOCATION: http://widgets.ebscohost.com/prod/ftf-atoz/views/app.php
 * APP NAME: Publication Finder API Sample
 **/

session_start();

/**
  * Function: root
  * Return the root directory relative to current PHP file    
  * which is /app
  *
  * @return strin     Name of the root directory
  * @access public
  */
function root() 
{
     return dirname(__FILE__) . '/';
}

/**
  * Function: render_template
  * Render a template    
  *
  * @param array  $locals   Array of variables
  * @param string $fileName Name of the file 
  *
  * @return string    Result of rendering the corresponding php file
  * @access public
  */
function render_template($locals, $fileName) 
{
    extract($locals);
    ob_start();
    include root() . 'views/' . $fileName . '.php';
    return ob_get_clean();
}

/**
  * Function: render
  * Render a view with a template
  *
  * @param string $fileName      Name of the view
  * @param string $templateName  Name of the template
  * @param array  $variableArray Array of variables
  *
  * @return void
  * @access public
  */
function render($fileName, $templateName, $variableArray=array()) 
{
    $variableArray['content'] = render_template($variableArray, $fileName);
    print render_template($variableArray, $templateName);
}

/**
  * Function: paginate
  * A basic pagination that displays maximum 10 pages
  *
  * @param int    $recordCount 
  * @param int    $limit       
  * @param int    $page        Current page
  * @param string $searchTerm  Search term
  * @param string $fieldCode   
  *
  * @return string output
  * @access public
  */
function paginate($recordCount, $limit, $page, $searchTerm, $fieldCode) 
{   
    $output = '';
    $linkCount =ceil($recordCount/$limit);
    if (!empty($page)) {
        if ($page>$linkCount) {
            $page = $linkCount;
        }   
    } else {
        $page = 1;
    }
    $base_url = "pageOptions.php?$searchTerm&fieldcode=$fieldCode";
    
    if ($page%10 != 0) {
        $f = floor($page/10);
    } else {
        $f=floor($page/10)-1;
    }
    $s = $page-1;
    if ($linkCount >= 1) {
        $output = '<p>';
        if ($s>0) {
            $output .= "<a href=\"{$base_url}&pagenumber=GoToPage({$s})\"><span class='results-paging-previous'>&nbsp;&nbsp;&nbsp;&nbsp;</span></a>";
        }
        if ($f < floor($linkCount/10)) {
            for ($i = $f*10; $i < $f*10+10; $i++) {
                $p = $i+1;                     
                if ($p != $page) {
                    $output .= "<a href=\"{$base_url}&pagenumber=GoToPage({$p})\"><u>{$p}</u></a>";
                } else {
                    $output .= '<strong>'.$p.'</strong>';
                }          
            }
        } else {
            for ($i = $f*10; $i < $linkCount; $i++) {
                $p = $i+1;                    
                if ($p != $page) {
                    $output .= "<a href=\"{$base_url}&pagenumber=GoToPage({$p})\">{$p}</a>";
                } else {
                    $output .= $p;
                }        
            }
        }   
        $p_1 = $page+1;
        if ($p_1 <= $linkCount) {
            $output .= "<a href=\"{$base_url}&pagenumber=GoToPage({$p_1})\"><span class='results-paging-next'>&nbsp;&nbsp;&nbsp;&nbsp;</span></a>";
        }
        $output .= '<br class="clear" /></p>';
    }
    return $output;
}

?>