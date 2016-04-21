<?php
/**
 * EBSCO Response class
 * LOCATION: http://widgets.ebscohost.com/prod/ftf-atoz/EBSCOConnector.php
 * APP NAME: Publication Finder API Sample
 **/

/**
   Class: EBSCOResponse 

   Functions used to retrieve a response from the API server
 **/
class EBSCOResponse
{
    /**
     * Constructor
     * Setup EBSCO API Response
     *
     * @param SimpleXML $response 
     *
     * @access public
     */
    public function __construct($response)
    {
        $this->response = $response;
    }

    /**
     * Function: result
     * A proxy method which decides which subsequent method should be     
     * called, based on the SimpleXml object structure
     *
     * @return array      An associative array of data or the SimpleXml object itself in case of API error messages
     * @access public
     */
    public function result()
    {
        // If there is an ErrorNumber tag then return the object itself.
        // Should not happen, this method is called after parsing the SimpleXml for API errors
        if (!empty($this->response->ErrorNumber)) {
            return $this->response;
        } else {
            if (!empty($this->response->AuthToken)) {
                return $this->_buildAuthenticationToken();
            } else if (!empty($this->response->SessionToken)) {
                return (string)$this->_buildSessionToken();
            } else if (!empty($this->response->SearchResult)) {
                return $this->_buildSearch();
            } else if (!empty($this->response->Record)) {
                return $this->_buildRetrieve();
            } else if (!empty($this->response->AvailableSearchCriteria)) {
                return $this->_buildInfo();
            }
        }
    }

    /**
     * Function: _buildAuthenticationToken
     * Parse the SimpleXml object when an AuthenticationToken API call was executed
     *
     * @return array  An associative array of data with information about the authentication
     * @access private
     */
    private function _buildAuthenticationToken()
    {
        $token = (string) $this->response->AuthToken;
        $timeout = (integer) $this->response->AuthTimeout;

        $result = array(
            'authenticationToken'   => $token,
            'authenticationTimeout' => $timeout,
            'authenticationTimeStamp'=> time()
        );
        return $result;
    }

    /**
     * Function: _buildSessionToken
     * Parse the SimpleXml object when an SessionToken API call was executed
     *
     * @return sessionToken
     * @access private
     */
    private function _buildSessionToken()
    {  
        $sessionToken = (string) $this->response->SessionToken;      
        return $sessionToken;
    }

    /**
     * Function: _buildSearch
     * Parse the SimpleXml object when a Search API call was executed
     *
     * @return array $results An associative array of data
     * @access private
     */
    private function _buildSearch()
    {
        $hits = (integer) $this->response->SearchResult->Statistics->TotalHits;
        $queryString = (string)$this->response->SearchRequestGet->QueryString;
        $records = array();
        $queries = array();
        
        if ($this->response->SearchRequestGet->SearchCriteriaWithActions->QueriesWithAction) {
            $queriesWithAction = $this->response->SearchRequestGet->SearchCriteriaWithActions->QueriesWithAction->QueryWithAction;
            foreach ($queriesWithAction as $queryWithAction) {
                $queries[]=array(
                'query' => (string)$queryWithAction->Query->Term,
                'removeAction'=> (string) $queryWithAction->RemoveAction
                );
            }
        }
        
        if ($hits > 0) {
            $records = $this->_buildRecords();
            //$facets = $this->_buildFacets();
        }

        $results = array(
            'recordCount' => $hits,
            'queryString' => $queryString,
            'queries'     => $queries,
            'records'     => $records,
        );

        return $results;
    }

    /**
     * Function: _buildRecords
     * Parse the SimpleXml object when a Search API call was executed
     * and find all records
     *
     * @return array $results An associative array of data
     * @access private
     */
    private function _buildRecords()            
    {
        $results = array();

        $records = $this->response->SearchResult->Data->Records->Record;
        foreach ($records as $record) {
            $result = array();
            $result['AccessLevel'] = $record->Header->AccessLevel?(string)$record->Header->AccessLevel:'';
            $result['ResultId'] = $record->ResultId ? (integer) $record->ResultId : '';
            $result['PLink'] = $record->PLink ? (string) $record->PLink : '';
            $result['ResourceType']=$record->Header->ResourceType? (string) $record->Header->ResourceType:'';
            $result['PublicationId'] = $record->Header->PublicationId ? (string) $record->Header->PublicationId : '';
            $result['IsSearchable'] = $record->Header->IsSearchable ? (string) $record->Header->IsSearchable : '';
            $result['RelevancyScore'] = $record->Header->RelevancyScore ? (string) $record->Header->RelevancyScore : '';

            if ($record->Items) {
                $result['Items'] = array();
                foreach ($record->Items->Item as $item) {                   
                    $label = $item->Label ? (string) $item->Label : '';
                    $group = $item->Group ? (string) $item->Group : '';
                    $data = $item->Data ? (string) $item->Data : '';
                    $result['Items'][$group] = array(                     
                        'Label' => $label,
                        'Group' => $group,
                        'Data'  => $this->_toHTML($data, $group)
                    );
                }
            }
            
            if ($record->FullTextHoldings) {
                $result['FullTextHoldings'] = array();
                foreach ($record->FullTextHoldings->FullTextHolding as $fullTextHolding) {                   
                    $urlFTH = $fullTextHolding->URL ? (string) $fullTextHolding->URL : '';
                    $nameFTH = $fullTextHolding->Name ? (string) $fullTextHolding->Name : '';
                    $coverageDates = $fullTextHolding->CoverageStatement ? (string) $fullTextHolding->CoverageStatement : '';
                    $embargo = $fullTextHolding->EmbargoDescription ? (string) $fullTextHolding->EmbargoDescription  : '';
                    $result['FullTextHoldings'][$nameFTH] = array(                     
                        'URL' => $urlFTH,
                        'Name' => $nameFTH,
                        'CoverageDates' => $coverageDates,
                        'Embargo' => $embargo
                    );
                }
            }
            $results[] = $result;
        }

        return $results;
    }

    /**
     * Function: _toHTML
     * Transform a SimpleXML object into a HTML string
     *
     * @param SimpleXml $data  Description of one item
     * @param string    $group Type of item
     *
     * @return string The HTML string
     * @access protected
     */
    private function _toHTML($data, $group = '')
    {
        global $path;
        // Any group can be added here, but we only use Au (Author) 
        // Other groups, not present here, won't be transformed to HTML links
        $allowed_searchlink_groups = array('Au','Su');
        $allowed_link_groups = array('URL');
        // Map xml tags to the HTML tags
        // This is just a small list, the total number of xml tags is far more greater
        $xml_to_html_tags = array(
            '<jsection'    => '<section',
            '</jsection'   => '</section',
            '<highlight'   => '<span class="highlight"',
            '<highligh'    => '<span class="highlight"', // Temporary bug fix
            '</highlight>' => '</span>', // Temporary bug fix
            '</highligh'   => '</span>',
            '<text'        => '<div',
            '</text'       => '</div',
            '<title'       => '<h2',
            '</title'      => '</h2',
            '<anid'        => '<p',
            '</anid'       => '</p',
            '<aug'         => '<strong',
            '</aug'        => '</strong',
            '<hd'          => '<h3',
            '</hd'         => '</h3',
            '<linebr'      => '<br',
            '</linebr'     => '',
            '<olist'       => '<ol',
            '</olist'      => '</ol',
            '<reflink'     => '<a',
            '</reflink'    => '</a',
            '<blist'       => '<p class="blist"',
            '</blist'      => '</p',
            '<bibl'        => '<a',
            '</bibl'       => '</a',
            '<bibtext'     => '<span',
            '</bibtext'    => '</span',
            '<ref'         => '<div class="ref"',
            '</ref'        => '</div',
            '<ulink'       => '<a',
            '</ulink'      => '</a',
            '<superscript' => '<sup',
            '</superscript'=> '</sup',
            '<relatesTo'   => '<sup',
            '</relatesTo'  => '</sup',
            // A very basic security implementation, using a  blackist instead of a whitelist as needed.
            // But the total number of xml tags is so large that we won't build a whitelist right now
            '<script'      => '',
            '</script'     => '',
            '<i>'          => '',
            '</i>'         => '',
            '<br />'       => ' '
        );

        // Map xml types to Search types used by the UI
        $xml_to_search_types = array(
            'Au' => 'Author',
            'Su' => 'Subject'
        );

        //  The XML data is XML escaped, let's unescape html entities (e.g. &lt; => <)
        $data = html_entity_decode($data);

        // Start parsing the xml data
        if (!empty($data)) {
            // Replace the XML tags with HTML tags
            $search = array_keys($xml_to_html_tags);
            $replace = array_values($xml_to_html_tags);
            $data = str_replace($search, $replace, $data);

            // Temporary : fix unclosed tags
            $data = preg_replace('/<\/highlight/', '</span>', $data);
            $data = preg_replace('/<\/span>>/', '</span>', $data);
            $data = preg_replace('/<\/searchLink/', '</searchLink>', $data);
            $data = preg_replace('/<\/searchLink>>/', '</searchLink>', $data);

            // Parse searchLinks
            if (!empty($group) && in_array($group, $allowed_searchlink_groups)) {
                $type = $xml_to_search_types[$group];
                $link_xml = '/<searchLink fieldCode="([^"]*)" term="([^"]*)">/';
                $link_html = "<a href=\"results.php?query=$2&fieldcode=$1\">";  //replaced $path with "result.php"
                $data = preg_replace($link_xml, $link_html, $data);
                $data = str_replace('</searchLink>', '</a>', $data);
                $data = str_replace('<br />', '; ', $data);
                $data = str_replace('*', '', $data);
            }
             // Parse link
            if (!empty($group) && in_array($group, $allowed_link_groups)) {          
                $link_xml = '/<link linkTarget="([^"]*)" linkTerm="([^"]*)" linkWindow="([^"]*)">/';
                $link_html = "<a name=\"$1\" href=\"$2\" target=\"$3\">";  //replaced $path with "result.php"
                $data = preg_replace($link_xml, $link_html, $data);
                $data = str_replace('</link>', '</a>', $data);            
            }
            // Replace the rest of searchLinks with simple spans
            $link_xml = '/<searchLink fieldCode="([^\"]*)" term="%22([^\"]*)%22">/';
            $link_html = '<span>';
            $data = preg_replace($link_xml, $link_html, $data);
            $data = str_replace('</searchLink>', '</span>', $data);
             // Parse bibliography (anchors and links)
            $data = preg_replace('/<a idref="([^\"]*)"/', '<a href="#$1"', $data);
            $data = preg_replace('/<a id="([^\"]*)" idref="([^\"]*)" type="([^\"]*)"/', '<a id="$1" href="#$2"', $data);
        }

        return $data;
    }




    // A PARTIR DE ACA NO
     /**
     * Function: _buildFacets
     * Parse the SimpleXml object when a Search API call was executed
     * and find all facets
     *
     * @return array    An associative array of data
     * @access private
     */
    private function _buildFacets()
    {
        $results = array();
        
        if ($this->response->SearchResult->AvailableFacets) {
            $facets = $this->response->SearchResult->AvailableFacets->AvailableFacet;
            foreach ($facets as $facet) {
                $values = array();
                foreach ($facet->AvailableFacetValues->AvailableFacetValue as $value) {
                    $values[] = array(
                    'Value'  => (string) $value->Value,
                    'Action' => (string) $value->AddAction,
                    'Count'  => (string) $value->Count
                    );
                }
                $id = (string) $facet->Id;
                $label = (string) $facet->Label;
                $results[] = array(
                'Id'     => $id,
                'Label'  => $label,
                'Values' => $values
                );
            }
        }
        return $results;
    }


    /**
     * Function: _buildInfo
     * Parse the SimpleXml object when an Info API call was executed
     *
     * @return array $result An associative array of data
     * @access private
     */
    private function _buildInfo()
    {
        // Sort options
        $sort = array();
        foreach ($this->response->AvailableSearchCriteria->AvailableSorts->AvailableSort as $element) {
            $sort[] = array(
                'Id'     => (string) $element->Id,
                'Label'  => (string) $element->Label,
                'Action' => (string) $element->AddAction
            );
        }

        // Search fields
        $search = array();
        foreach ($this->response->AvailableSearchCriteria->AvailableSearchFields->AvailableSearchField as $element) {
            $search[] = array(
                'Label' => (string) $element->Label,
                'Code'  => (string) $element->FieldCode
            );
        }

        // Expanders
        $expanders = array();
        foreach ($this->response->AvailableSearchCriteria->AvailableExpanders->AvailableExpander as $element) {
            $expanders[] = array(
                'Id'     => (string) $element->Id,
                'Label'  => (string) $element->Label,
                'Action' => (string) $element->AddAction
            );
        }

        // Limiters
        $limiters = array();
        foreach ($this->response->AvailableSearchCriteria->AvailableLimiters->AvailableLimiter as $element) {
            $values = array();
            if ($element->LimiterValues) {
                $items = $element->LimiterValues->LimiterValue;                
                foreach ($items as $item) {
                    $values[] = array(
                        'Value'  => (string) $item->Value,
                        'Action' => (string) $item->AddAction
                    );
                }
            }
            $limiters[] = array(
                'Id'     => (string) $element->Id,
                'Label'  => (string) $element->Label,
                'Action' => (string) $element->AddAction,
                'Type'   => (string) $element->Type,
                'values' => $values
            );
        }

        $result = array(
            'sort'      => $sort,
            'search'    => $search,
            'expanders' => $expanders,
            'limiters'  => $limiters
        );

        return $result;
    }


    /**
     * Function: _buildRetrieve
     * Parse a SimpleXml object when a Retrieve API call was executed
     *
     * @return array      An associative array of data
     * @access private
     */
    private function _buildRetrieve()
    {
        $record = $this->response->Record;

        if ($record) {
            $record = $record[0]; // there is only one record
        }

        $result = array();
        $result['AccessLevel'] = $record->Header->AccessLevel?(string)$record->Header->AccessLevel:'';
        $result['pubType'] = $record->Header-> PubType? (string)$record->Header->PubType:'';
        $result['PubTypeId']=$record->Header->PubTypeId? (string) $record->Header->PubTypeId:'';
        $result['DbId'] = $record->Header->DbId ? (string) $record->Header->DbId : '';
        $result['DbLabel'] = $record->Header->DbLabel ? (string) $record->Header->DbLabel:'';
        $result['An'] = $record->Header->An ? (string) $record->Header->An : '';
        $result['PLink'] = $record->PLink ? (string) $record->PLink : ''; 
        $result['pdflink'] = $record->FullText->Links ? (string) $record->FullText->Links->Link->Url : '';
        $result['PDF'] = $record->FullText->Links ? (string) $record->FullText->Links->Link->Type : '';
        $value = $record->FullText->Text->Value ? (string) $record->FullText->Text->Value : '';
        $result['htmllink'] = $this->_toHTML($value, $group = '');
        $result['HTML'] = $record->FullText->Text->Availability? (string) $record->FullText->Text->Availability : '';
        if (!empty($record->ImageInfo->CoverArt)) {
            foreach ($record->ImageInfo->CoverArt as $image) {
                $size = (string) $image->Size;
                $target = (string) $image->Target;
                $result['ImageInfo'][$size] = $target;
            }
        } else {
            $result['ImageInfo'] = '';
        }
        $result['FullText'] = $record->FullText ? (string) $record->FullText : '';

        if ($record->CustomLinks) {
            $result['CustomLinks'] = array();
            foreach ($record->CustomLinks->CustomLink as $customLink) {
                $category = $customLink->Category ? (string) $customLink->Category : '';
                $icon = $customLink->Icon ? (string) $customLink->Icon : '';
                $mouseOverText = $customLink->MouseOverText ? (string) $customLink->MouseOverText : '';
                $name = $customLink->Name ? (string) $customLink->Name : '';
                $text = $customLink->Text ? (string) $customLink->Text : '';
                $url = $customLink->Url ? (string) $customLink->Url : '';
                $result['CustomLinks'][] = array(
                    'Category'      => $category,
                    'Icon'          => $icon,
                    'MouseOverText' => $mouseOverText,
                    'Name'          => $name,
                    'Text'          => $text,
                    'Url'           => $url
                );
            }
        }
        
        if ($record->FullText->CustomLinks) {
            $result['FullTextCustomLinks'] = array();
            foreach ($record->FullText->CustomLinks->CustomLink as $customLink) {
                    $category = $customLink->Category ? (string) $customLink->Category : '';
                    $icon = $customLink->Icon ? (string) $customLink->Icon : '';
                    $mouseOverText = $customLink->MouseOverText ? (string) $customLink->MouseOverText : '';
                    $name = $customLink->Name ? (string) $customLink->Name : '';
                    $text = $customLink->Text ? (string) $customLink->Text : '';
                    $url = $customLink->Url ? (string) $customLink->Url : '';
                    $result['CustomLinks'][] = array(
                        'Category'      => $category,
                        'Icon'          => $icon,
                        'MouseOverText' => $mouseOverText,
                        'Name'          => $name,
                        'Text'          => $text,
                        'Url'           => $url
                    );
            }
        }

        if ($record->Items) {
            $result['Items'] = array();
            foreach ($record->Items->Item as $item) {              
                $label = $item->Label ? (string) $item->Label : '';
                $group = $item->Group ? (string) $item->Group : '';
                $data = $item->Data ? (string) $item->Data : '';
                $result['Items'][] = array(                
                    'Label' => $label,
                    'Group' => $group,
                    'Data'  => $this->_retrieveHTML($data, $group)
                );
            }
        }
        
        if ($record->RecordInfo) {
            $result['RecordInfo'] = array();
            $result['RecordInfo']['BibEntity']=array(
                   'Identifiers'=>array(),
                   'Languages'=>array(),
                   'PhysicalDescription'=>array(),
                   'Subjects'=>array(),
                   'Titles'=>array()
               );
                           
            if ($record->RecordInfo->BibRecord->BibEntity->Identifiers) {
                foreach ($record->RecordInfo->BibRecord->BibEntity->Identifiers->Identfier as $identifier) {
                    $type = $identifier->Type? (string) $identifier->Type:'';
                    $value = $identifier->Value? (string) $identifier->Value:'';
                    $result['RecordInfo']['BibEntity']['Identifiers'][]= array(
                    'Type'=>$type,
                    'Value'=>$value
                    );
                }            
            }
               
            if ($record->RecordInfo->BibRecord->BibEntity->Languages) {
                foreach ($record->RecordInfo->BibRecord->BibEntity->Languages->Language as $language) {
                    $code = $language->Code? (string)$language->Code:'';
                    $text = $language->Text? (string)$language->Text:'';
                    $result['RecordInfo']['BibEntity']['Languages'][]= array(
                    'Code'=>$code,
                    'Text'=>$text
                    );
                }             
            }             
               
            if ($record->RecordInfo->BibRecord->BibEntity->PhysicalDescription) {
                $pageCount = $record->RecordInfo->BibRecord->BibEntity->PhysicalDescription->Pagination->PageCount? (string) $record->RecordInfo->BibRecord->BibEntity->PhysicalDescription->Pagination->PageCount:'';
                $startPage = $record->RecordInfo->BibRecord->BibEntity->PhysicalDescription->Pagination->StartPage? (string) $record->RecordInfo->BibRecord->BibEntity->PhysicalDescription->Pagination->StartPage:'';
                $result['RecordInfo']['BibEntity']['PhysicalDescription']['Pagination'] = $pageCount;
                $result['RecordInfo']['BibEntity']['PhysicalDescription']['StartPage'] = $startPage;
            }
                              
            if ($record->RecordInfo->BibRecord->BibEntity->Subjects) {
                foreach ($record->RecordInfo->BibRecord->BibEntity->Subjects->Subject as $subject) {
                    $subjectFull = $subject->SubjectFull? (string)$subject->SubjectFull:'';
                    $type = $subject->Type? (string)$subject->Type:'';
                    $result['RecordInfo']['BibEntity']['Subjects'][]=array(
                       'SubjectFull'=>$subjectFull,
                       'Type'=>$type
                    );
                }
            }
               
            if ($record->RecordInfo->BibRecord->BibEntity->Titles) {
                foreach ($record->RecordInfo->BibRecord->BibEntity->Titles->Title as $title) {
                    $titleFull = $title->TitleFull? (string)$title->TitleFull:'';
                    $type = $title->Type? (string)$title->Type:'';
                    $result['RecordInfo']['BibEntity']['Titles'][]=array(
                       'TitleFull'=>$titleFull,
                       'Type'=>$type
                    );
                }
            }
            $result['RecordInfo']['BibRelationships']=array(
                   'HasContributorRelationships'=>array(),
                   'IsPartOfRelationships'=>array()                
            );
               
            if ($record->RecordInfo->BibRecord->BibRelationships->HasContributorRelationships) {
                foreach ($record->RecordInfo->BibRecord->BibRelationships->HasContributorRelationships->HasContributor as $contributor) {
                    $nameFull = $contributor->PersonEntity->Name->NameFull? (string)$contributor->PersonEntity->Name->NameFull:'';
                    $result['RecordInfo']['BibRelationships']['HasContributorRelationships'][]=array(
                       'NameFull'=>$nameFull
                    );
                }
            }
               
            if ($record->RecordInfo->BibRecord->BibRelationships) {
                foreach ($record->RecordInfo->BibRecord->BibRelationships->IsPartOfRelationships->IsPartOf as $relationship) {
                    if ($relationship->BibEntity->Dates) {
                        foreach ($relationship->BibEntity->Dates->Date as $date) {
                            $d = $date->D? (string)$date->D:'';
                            $m = $date->M? (string)$date->M:'';
                            $type = $date->Type? (string)$date->Type:'';
                            $y = $date->Y? (string)$date->Y:'';
                            $result['RecordInfo']['BibRelationships']['IsPartOfRelationships']['date'][] = array(
                            'D'=> $d,
                            'M'=>$m,
                            'Type'=>$type,
                            'Y'=>$y
                            );
                        }
                    }
                   
                    if ($relationship->BibEntity->Identifiers) {
                        foreach ($relationship->BibEntity->Identifiers->Identfier as $identifier) {
                            $type = $identifier->Type? (string) $identifier->Type :'';
                            $value = $identifier->Value? (string) $identifier->Value:'';
                            $result['RecordInfo']['BibRelationships']['IsPartOfRelationships']['Identifiers'][]=array(
                                'Type'=>$type,
                                'Value'=>$value
                            );
                        }
                    }
                   
                    if ($relationship->BibEntity->Numbering) {
                        foreach ($relationship->BibEntity->Numbering->Number as $number) {
                            $type = (string)$number->Type;
                            $value= (string)$number->Value;
                            $result['RecordInfo']['BibRelationships']['IsPartOfRelationships']['numbering'][] = array(
                            'Type'=> $type,
                            'Value'=>$value
                            );
                        }
                    }
                   
                    if ($relationship->BibEntity->Titles) {
                        foreach ($relationship->BibEntity->Titles->Title as $title) {
                            $titleFull = $title->TitleFull? (string)$title->TitleFull:'';
                            $type = $title->Type? (string)$title->Type:'';
                            $result['RecordInfo']['BibRelationships']['IsPartOfRelationships']['Titles'][]=array(
                             'TitleFull' => $titleFull,
                             'Type'=>$type
                            );
                        }
                    }
                }
            }
        }
        return $result;
    }


    /**
     * Function: _retrieveHTML
     * Parse the "inner XML" of a SimpleXml element and 
     * return it as an HTML string
     *
     * @param SimpleXml $data  A SimpleXml DOM
     * @param string    $group  
     *
     * @return string $data The HTML string
     * @access protected
     */
    private function _retrieveHTML($data, $group = '')
    {
        //global $path;
        // Any group can be added here, but we only use Au (Author) 
        // Other groups, not present here, won't be transformed to HTML links
        $allowed_searchlink_groups = array('Au','Su');
        $allowed_link_groups = array('URL');
        // Map xml tags to the HTML tags
        // This is just a small list, the total number of xml tags is far more greater
        $xml_to_html_tags = array(
            '<jsection'    => '<section',
            '</jsection'   => '</section',
            '<highlight'   => '<span class="highlight"',
            '<highligh'    => '<span class="highlight"', // Temporary bug fix
            '</highlight>' => '</span>', // Temporary bug fix
            '</highligh'   => '</span>',
            '<text'        => '<div',
            '</text'       => '</div',
            '<title'       => '<h2',
            '</title'      => '</h2',
            '<anid'        => '<p',
            '</anid'       => '</p',
            '<aug'         => '<strong',
            '</aug'        => '</strong',
            '<hd'          => '<h3',
            '</hd'         => '</h3',
            '<linebr'      => '<br',
            '</linebr'     => '',
            '<olist'       => '<ol',
            '</olist'      => '</ol',
            '<reflink'     => '<a',
            '</reflink'    => '</a',
            '<blist'       => '<p class="blist"',
            '</blist'      => '</p',
            '<bibl'        => '<a',
            '</bibl'       => '</a',
            '<bibtext'     => '<span',
            '</bibtext'    => '</span',
            '<ref'         => '<div class="ref"',
            '</ref'        => '</div',
            '<ulink'       => '<a',
            '</ulink'      => '</a',
            '<superscript' => '<sup',
            '</superscript'=> '</sup',
            '<relatesTo'   => '<sup',
            '</relatesTo'  => '</sup',
            // A very basic security implementation, using a  blackist instead of a whitelist as needed.
            // But the total number of xml tags is so large that we won't build a whitelist right now
            '<script'      => '',
            '</script'     => ''
        );

        // Map xml types to Search types used by the UI
        $xml_to_search_types = array(
            'Au' => 'Author',
            'Su' => 'Subject'
        );

        //  The XML data is XML escaped, let's unescape html entities (e.g. &lt; => <)
        $data = html_entity_decode($data);

        // Start parsing the xml data
        if (!empty($data)) {
            // Replace the XML tags with HTML tags
            $search = array_keys($xml_to_html_tags);
            $replace = array_values($xml_to_html_tags);
            $data = str_replace($search, $replace, $data);

            // Temporary : fix unclosed tags
            $data = preg_replace('/<\/highlight/', '</span>', $data);
            $data = preg_replace('/<\/span>>/', '</span>', $data);
            $data = preg_replace('/<\/searchLink/', '</searchLink>', $data);
            $data = preg_replace('/<\/searchLink>>/', '</searchLink>', $data);

            // Parse searchLinks
            if (!empty($group) && in_array($group, $allowed_searchlink_groups)) {
                $type = $xml_to_search_types[$group];
                $link_xml = '/<searchLink fieldCode="([^"]*)" term="([^"]*)">/';
                $link_html = "<a href=\"results.php?query=$2&fieldcode=$1\">";  //replaced $path with "result.php"
                $data = preg_replace($link_xml, $link_html, $data);
                $data = str_replace('</searchLink>', '</a>', $data);
                $data = str_replace('*', '', $data);
            }
             // Parse link
            if (!empty($group) && in_array($group, $allowed_link_groups)) {          
                $link_xml = '/<link linkTarget="([^"]*)" linkTerm="([^"]*)" linkWindow="([^"]*)">/';
                $link_html = "<a name=\"$1\" href=\"$2\" target=\"$3\">";  //replaced $path with "result.php"
                $data = preg_replace($link_xml, $link_html, $data);
                $data = str_replace('</link>', '</a>', $data);            
            }
            // Replace the rest of searchLinks with simple spans
            $link_xml = '/<searchLink fieldCode="([^\"]*)" term="%22([^\"]*)%22">/';
            $link_html = '<span>';
            $data = preg_replace($link_xml, $link_html, $data);
            $data = str_replace('</searchLink>', '</span>', $data);
             // Parse bibliography (anchors and links)
            $data = preg_replace('/<a idref="([^\"]*)"/', '<a href="#$1"', $data);
            $data = preg_replace('/<a id="([^\"]*)" idref="([^\"]*)" type="([^\"]*)"/', '<a id="$1" href="#$2"', $data);
        }

        return $data;
    }



}


?>