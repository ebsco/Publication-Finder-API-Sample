<?php
/**
 * Basic search view
 * LOCATION: http://widgets.ebscohost.com/prod/ftf-atoz/app/views/basic_search.html.php
 * APP NAME: Publication Finder API Sample
 **/

$api =  new EBSCOAPI();
$Info = $api->getInfo();
?>
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
		<h4>Search:</h4>
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
		<form name="searchTerm" id="searchTerm" action="">
			<div class="subtitle">Custom search (enter a few characters):</div>
			<input type="radio" name="prefix" value="JN" checked/><span style="font-size:0.8em;">Publication title begins with:</span><br/>
			<input type="radio" name="prefix" value="SO"/><span style="font-size:0.8em;">Publication title contains:</span><br/><br/>
			<input type="text" name="sTerm" class="searchBox" maxlength="25"/>
			<input type="button" id="submit" name="submit" value="Search"/>
			<div class="messageST">Please enter search term(s).</div>
		</form>
		<div id="spinner" class="spinner" style="display:none;">
			<img id="img-spinner" src="web/spinner.gif" alt="Loading"/>
		</div>