<?php 
/**
 * Results view
 * LOCATION: http://widgets.ebscohost.com/prod/ftf-atoz/app/views/results.html.php
 * APP NAME: Publication Finder API Sample
 **/

$queryStringUrl = $results['queryString'];
// URL used by facets links
$refineParams = array(
    'refine' => 'y',
    'query'  => $searchTerm,
    'fieldcode' => $fieldCode
);
$refineParams = http_build_query($refineParams);
$refineSearchUrl = "results.php?".$refineParams;
$encodedSearchTerm = http_build_query(array('query'=>$searchTerm));
$encodedHighLigtTerm = http_build_query(array('highlight'=>$searchTerm));
?>

<!--START: copied from basic_search.html -->

<script type="text/javascript">
      function submitForm(letter) {
          $('#spinner').show();
          var form = document.searchLetter;
          form.query.value = "JN " + letter + "*";
          form.submit();
      }
      function submitTerm() {
          var form1 = document.searchLetter;
          var form2 = document.searchTerm;
          var term = form2.sTerm.value;
          if (term == "") {
              $('.messageST').show();
          } else {
              $('#spinner').show();
              var pre;
              if (form2.prefix[1].checked) pre = 'SO';
              else pre = 'JN';
              form1.query.value = pre + " " + term + "*";
              form1.submit();
          }
      }
      function showSubletterMenu(currentLetter) {
          var subletterMenu = '<ul><li class="subletter"><a href="#" style="margin-right: 10px;" onclick="javascript:submitForm(\'' + currentLetter + '\')">' + currentLetter + '</a></li>';
          var letters = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
          var size = letters.length;
          for (var i = 0; i < size; ++i) {
              subletterMenu += '<li class="subletter"><a href="#" onclick="javascript:submitForm(\'' + currentLetter + letters[i] + '\')">' + currentLetter + letters[i] + '</a></li>';
          }
          subletterMenu += '</ul>';
          $('#letter-submenu').html(subletterMenu);
      }
      $(document).ready(function () {
          $('#submit').click(function () {
              var form1 = document.searchLetter;
              var form2 = document.searchTerm;
              var term = form2.sTerm.value;
              if (term == "") {
                  $('.messageST').show();
              } else {
                  $('#spinner').show();
                  var pre;
                  if (form2.prefix[1].checked) pre = 'SO';
                  else pre = 'JN';
                  form1.query.value = pre + " " + term + "*";
                  form1.submit();
              }
          });
          $('.searchBox').keypress(function (e) {
              if (e.which == 13) {
                  submitTerm();
                  return false;
              }
          });
      });


		</script>
		<strong>Browse:</strong>
		<form action="results.php" name="searchLetter">
			<input type="hidden" name="query" value="" id="lookfor" /> 
			<input type="hidden" name="expander" value="" />            
			<div class="letter-container">
				<div class="letter-container2">	
					<ul>
<?php
$letters = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
for ($i = 0, $size = count($letters); $i < $size; ++$i) {
    echo '<li class="letter letter-menu"><a href="#" onclick="javascript:showSubletterMenu(\''.$letters[$i].'\')">'.$letters[$i].'</a></li>';
}
?>
					</ul>
				</div>
                <div id="letter-submenu" class="letter-container2"></div>
			</div>
		</form>
		
<!--END: copied from basic_search.html -->

<div id="toptabcontent">
<div class="table">
    <div class="table-row">
<div class="table-cell">
<?php if ($debug=='y') {?>
    <div style="float:right"><a target="_blank" href="debug.php?result=y">Search response XML</a></div>
    <?php   
} 
    ?>
<div class="top-menu">
    <h2>Results for <?php echo $searchTerm ?></h2> 
<?php if ($error) { ?>
    <div class="error">
        <?php echo $error; ?>
    </div>
    <?php 
} 
    ?>

<?php if (!empty($results)) { ?>
    <div class="statistics">
        Showing <strong>
    <?php 
    if ($results['recordCount']>0) { 
        echo ($start - 1) * $limit + 1;
    } else { 
        echo 0; 
    } ?>  - 
    <?php 
    if ((($start - 1) * $limit + $limit)>=$results['recordCount']) { 
        echo $results['recordCount']; 
    } else { 
        echo ($start - 1) * $limit + $limit;
    } ?></strong> of <strong><?php echo $results['recordCount']; ?></strong>
    </div>
    <div class="newSearch"><a href='./index.php'>New Search</a></div> <br><br>            
    <div style="text-align: center">
        <div class="pagination"><?php echo paginate($results['recordCount'], $limit, $start, $encodedSearchTerm, $fieldCode); ?></div>
    </div>
    <?php 
} 
    ?>

<div class="results table">
    <?php if (empty($results['records'])) { ?>
        <div class="result table-row">
            <div class="table-cell">
                <h2><i>No results were found.</i></h2>
            </div>
        </div>
    <?php 
} else { 
    ?> 
    <?php 
    foreach ($results['records'] as $result) { 
        ?>
            <div class="result table-row">
                <div class="record-id table-cell">
                    <?php echo $result['ResultId']; ?>.
                </div>                     
                <div class="table-cell">
                    <div style="margin-left: 10px">
        <?php 
        if ((!isset($_COOKIE['login']))&&$result['AccessLevel']==1) { ?>
                            <p>This record from <b>[<?php echo $result['DbLabel'] ?>]</b> cannot be displayed.<a href="login.php?path=results&<?php echo $encodedSearchTerm;?>&fieldcode=<?php echo $fieldCode; ?>">Login</a> for full access.</p>
		<?php 
        } else {  
            ?>
						      <div class="link">
			<?php 
            if (!empty($result['PLink'])&&(!empty($result['Items']['Ti']))) {
                 ?>  
							         <a target="_blank" style="color: blue;" href="<?php echo $result['PLink'].'&authtype=cookie,uid&scope=site'; ?>"><?php echo $result['Items']['Ti']['Data']; ?> </a>
						          </div>
							         <div class="info">
            <?php 
            if (!empty($result['Items']['ISSN']['Data'])) echo 'ISSN: '.$result['Items']['ISSN']['Data'].'. <br>';
            if (!empty($result['Items']['Su']['Data'])) echo 'Subjects: '.$result['Items']['Su']['Data'].'. <br>';
            if (!empty($result['Items']['Note']['Data'])) echo 'Description: '.$result['Items']['Note']['Data'].'<br>';
                if (!empty($result['FullTextHoldings'])) {
                    echo 'FullText Holdings: <br>';
                    foreach ($result['FullTextHoldings'] as $fullTextHolding) {
                        echo '- Name: '.$fullTextHolding['Name'].'. '/*.' URL: '.$fullTextHolding['URL']*/;
                        if (!empty($fullTextHolding['CoverageDates'])) echo ' Coverage dates: '.$fullTextHolding['CoverageDates'].'. ';
                        if (!empty($fullTextHolding['Embargo'])) echo ' '.$fullTextHolding['Embargo'];
                        echo '<br>';
                    }
                } 
            
                ?>
							           </div>
			<?php 
            } ?>
		<?php 
        } ?>
					   </div>
                </div>
            </div>
    <?php 
    } ?>
    <?php 
} ?>
</div>
<?php if (!empty($results)) { ?>
    <div style="text-align: center">
        <div class="pagination"><?php echo paginate($results['recordCount'], $limit, $start, $encodedSearchTerm, $fieldCode); ?></div>       
    </div>
    <?php 
} ?>
        </div>
    </div>
</div>
</div>      
</div>

