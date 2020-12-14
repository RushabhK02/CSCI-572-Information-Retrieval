<? php ini_set ('memory_limit', '1024M')?>
<?php

require_once("SpellCorrector.php");
$fileHandle = fopen("C:\\Users\\rushk\\Desktop\\CS books\\CSCI 572\\HWs\\HW4\\shared\\solr-7.7.3\\example\\LATIMES\\URLtoHTML_latimes_news.csv","r");

// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/html; charset=utf-8');


function getPageContent($path)
{
    $strArray = explode('/',$path);
    $filename = end($strArray);
    $filePath = "C:\\Users\\rushk\\Desktop\\CS books\\CSCI 572\\HWs\\HW4\\shared\\solr-7.7.3\\example\\LATIMES\\latimes\\";
    $fullPath = $filePath.$filename;
    // echo $fullPath;
    // $filestr = file_get_contents($path);
    $filestr = file_get_contents($fullPath);
    $filter = array();
    preg_match_all('/<body.*<\/body>/s', $filestr, $filter);
    if ($filter) {
        $filestr = $filter[0][0];
        $filestr = preg_replace('/<script.*?<\/script>/s', "", $filestr);
    }
    $filestr = strip_tags($filestr);

    //$filestr = preg_replace ("/\s(?=\s)/","\\1", $filestr);
    $filestr = preg_replace("/\s+/", " ", $filestr);
    return $filestr;
}


function getHighlight($words, $filestr)
{
    $matches = array();
    $searchs = "";
    $searchs_total = "";

    $matchpos = -1;
    //return $filestr;
    foreach ($words as $word) {
        if (strcmp($searchs, "") == 0) {
            $searchs = "(?=.{1,150}?[^a-z]" . $word . "[^a-z])";
            $searchs_total = "[^a-z]" . $word . "[^a-z]";
        } else {
            $searchs = $searchs . "(?=.{1,150}?[^a-z]" . $word . "[^a-z])";
            $searchs_total = $searchs_total . $word . "[^a-z]";
        }
    }
    $searchs = "/" . $searchs . "/si";
    $searchs_total = "/" . $searchs_total . "/si";
    preg_match($searchs_total, $filestr, $matches, PREG_OFFSET_CAPTURE);
    if ($matches) {
        $matchpos = $matches[0][1];
    } else {

        preg_match($searchs, $filestr, $matches, PREG_OFFSET_CAPTURE);

        if ($matches) {
            $matchpos = $matches[0][1];

            $matches = array();
            $searchs = str_replace("150", "100", $searchs);
            preg_match($searchs, $filestr, $matches, PREG_OFFSET_CAPTURE);
            if ($matches) {
                $matchpos = $matches[0][1];
            }

        } elseif (count($words) != 1) {

            foreach ($words as $word) {
                $matches = array();
                $searchs = "(?=.{1,50}?[^a-z]" . $word . "[^a-z])";
                $searchs = "/" . $searchs . "/si";
                preg_match($searchs, $filestr, $matches, PREG_OFFSET_CAPTURE);
                if ($matches) {
                    $matchpos = $matches[0][1];
                    break;
                }
            }
        } else {
            return "";
        }


    }
    if ($matchpos != -1) {
        if ($matchpos < 10) {
            $matchpos = 0;
        } else {
            $matchpos = $matchpos - 10;
        }
        $finals = substr($filestr, $matchpos, 175);
        //$finals = htmlspecialchars($finals, ENT_NOQUOTES, 'utf-8');

        $left = strpos($finals, " ");
        if ($left > 10) {
            $left = 0;
        }
        $right = strrpos($finals, " ");
        if ($right < strlen($finals) - 10) {
            $finals = substr($finals, $left);
        } else {
            $finals = substr($finals, $left, $right - $left);
        }

        foreach ($words as $word) {
            $finals = preg_replace("/([^a-z])(" . $word . ")([^a-z])/i", "$1<b>$2</b>$3", $finals);
        }

        return "... " . $finals . " ...";
    }
    return "";
}


$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$sortMethod = isset($_REQUEST['sort_method']) ? $_REQUEST['sort_method'] : "lucene";
$stillSearch = isset($_REQUEST['still_search']) ? $_REQUEST['still_search'] : false;
$results = false;
$usePageRank = false;
$useSpellCorrect = false;

$luceneParameters = array(
    'fl' => 'id,title,og_url,og_description'
);
$pageRankParameters = array(
    'fl' => 'id,title,og_url,og_description',
    'sort' => 'pageRankFile desc'
);

$additionalParameters = $luceneParameters;

//$result = $solr->search($query, $start, $rows, $additionalParameters);


if ($query) {
    // The Apache Solr Client library should be on the include path
    // which is usually most easily accomplished by placing in the
    // same directory as this script ( . or current directory is a default
    // php include path entry in the php.ini)
    require_once('Apache/Solr/Service.php');

    // create a new solr service instance - host, port, and webapp
    // path (all defaults in this example)
    $solr = new Apache_Solr_Service('192.168.1.236', 8983, '/solr/myexample/');
    // $solr = new Apache_Solr_Service('localhost', 8983, '/solr/cs572/');

    // if magic quotes is enabled then stripslashes will be needed
    if (get_magic_quotes_gpc() == 1) {
        $query = stripslashes($query);
    }

    // in production code you'll always want to use a try /catch for any
    // possible exceptions emitted  by searching (i.e. connection
    // problems or a query parsing error)
    if (strcmp($sortMethod, "pagerank") == 0) {
        $additionalParameters = $pageRankParameters;
    }
    $realRearchQuery = $query;
    $queryWords = explode(" ", $query);
    $queryWords = array_filter($queryWords);

    if (!$stillSearch) {
        
        foreach ($queryWords as $word) {
            // echo $word;
            $preWord = trim($word);
            $preWord = strtolower($preWord);
            $newWord = SpellCorrector::correct($word);
            if (strcmp($newWord, $preWord) != 0) {
                $realRearchQuery = str_replace($word, $newWord, $realRearchQuery);
            }
        }
        $queryWords = explode(" ", $realRearchQuery);
        $queryWords = array_filter($queryWords);
        if (strcmp($realRearchQuery, $query) != 0) {
            $useSpellCorrect = true;
        } else {
            $useSpellCorrect = false;
        }
    } else {
        $useSpellCorrect = false;
    }

    try {
        $results = $solr->search($realRearchQuery, 0, $limit, $additionalParameters);
    } catch (Exception $e) {
        // in production you'd probably log or email this error to an admin
        // and then show a special message to the user but for this example
        // we're going to show the full exception
        die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
    }
}

?>
<html>

<head>
    <link rel="stylesheet" href="http://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script>
        $(function () {

// $("#query_content").autocomplete({
//     minLength: 2,
//     source: "autocomplete.php"
// });

$("#still_search").click(function () {
    var newElement = document.createElement("input");
    newElement.setAttribute("type", "hidden");
    newElement.name = "still_search";
    newElement.value = 1;
    $("#search_form").append(newElement);

    $("#search_form").submit();
});
$("#correct_searchh").click(function () {

    $("#query_content").val(this.text.trim());

    $("#search_form").submit();
});

var URL_PREFIX = "http://192.168.1.236:8983/solr/myexample/suggest?q=";
var URL_SUFFIX = "&wt=json&indent=true";
var count=0;
var tags = [];
$("#query_content").autocomplete({
    source : function(request, response) {
        var correct="",before="";
        var query = $("#query_content").val().toLowerCase();
        var character_count = query.length - (query.match(/ /g) || []).length;
        var space =  query.lastIndexOf(' ');
        if(query.length-1>space && space!=-1){
        correct=query.substr(space+1);
        before = query.substr(0,space);
        }
        else{
        correct=query.substr(0); 
        }
        var URL = URL_PREFIX + correct+ URL_SUFFIX;
        $.ajax({
        url : URL,
        success : function(data) {
        var js =data.suggest.suggest;
        var docs = JSON.stringify(js);
        var jsonData = JSON.parse(docs);
        var result = jsonData[correct].suggestions;
        var j=0;
        var stem =[];
        tags = [];
        // for(var i=0; i<result.length; i++){
        //     tags[i] = result[i].term;
        //     j++;
        //     if(j==5) break;
        // }
        for(var i=0;i<5 && j<result.length;i++,j++){
            var s =(result[j].term);
            if(stem.length == 5) break;
            if(stem.indexOf(s) == -1)
            {
                stem.push(s);
                if(before==""){
                    tags[i]=s;
                } else {
                    tags[i] = before+" ";
                    tags[i]+=s;
                }
            }
        }

        console.log(tags);
        response(tags);
        },
        dataType : 'jsonp',
        jsonp : 'json.wrf'
    });
    },
    minLength : 1
    })
});

    </script>


    <title>CSCI 572 HW5 PHP Solr Client</title>
</head>

<body>
<form id="search_form" accept-charset="utf-8" method="get">
    <label for="q">Search:</label>
    <input id="query_content" name="q" type="text"
           value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
    <input type="submit"/> &nbsp;&nbsp;&nbsp;Ranking Algorithm:&nbsp;&nbsp; lucene

    <input type="radio" name="sort_method" value="lucene" <?php
    if (strcmp($sortMethod, "lucene") == 0) {
        echo "checked";
    } ?>> &nbsp;&nbsp;&nbsp;pageRank
    <input type="radio" name="sort_method" value="pagerank" <?php
    if (strcmp($sortMethod, "lucene") != 0) {
        echo "checked";
    } ?>>

</form>

<?php
if ($useSpellCorrect) {
    ?>
    <div>Showing results for:
        <a id="correct_searchh" href="javascript: void(0)">
            <?php echo $realRearchQuery; ?></a><br> Search instead for:
        <a id="still_search" href="javascript: void(0)">
            <?php echo $query; ?>
        </a>
    </div>
    <?php
}
?>

<?php

// display results
if ($results) {
    $total = (int)$results->response->numFound;
    $start = min(1, $total);
    $end = min($limit, $total);
    ?>
    <div>Results
        <?php echo $start; ?> -
        <?php echo $end; ?> of
        <?php echo $total; ?>:
    </div>
    <ol>
        <?php
        // iterate result documents
        $docs = $results->response->docs;
        foreach ($docs as $doc) {
            $doc1 = array();
            foreach ($doc as $field => $value) {
                if ($field == "id") {
                    $doc1["testid"] = $value;
                    $value = str_replace("/usr/local/solr/Pages/", "", $value);
                }
                $doc1[$field] = $value;
            }
            if (!array_key_exists("og_url",$doc1)){
                $strArray = explode('/',$doc1["testid"]);
	            $filename = end($strArray);
                $link = "";
                //Loop through the CSV rows.
                while (($row = fgetcsv($fileHandle, 0, ",")) !== FALSE) {
                    //Print out my column data.
                    if(strcmp($filename, $row[0]) == 0){
                        $link = $row[1];
                        // echo "match found";
                    }
                    // echo 'filename: ' . $row[0] . '<br>';
                    // echo 'URL: ' . $row[1] . '<br>';
                    // echo '<br>';
                }
                $doc1["og_url"] = $link;
            }
            ?>
            <li>
                <table style="border: 1px solid black; text-align: left;">

                    <tr>
                        <th>
                            <?php echo htmlspecialchars("title", ENT_NOQUOTES, 'utf-8'); ?>
                        </th>
                        <td>
                            <a href="<?php echo htmlspecialchars($doc1["og_url"], ENT_NOQUOTES, 'utf-8'); ?>">
                                <?php echo htmlspecialchars($doc1["title"], ENT_NOQUOTES, 'utf-8'); ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <?php echo htmlspecialchars("url", ENT_NOQUOTES, 'utf-8'); ?>
                        </th>
                        <td>
                            <a href="<?php echo htmlspecialchars($doc1["og_url"], ENT_NOQUOTES, 'utf-8'); ?>">
                                <?php echo htmlspecialchars($doc1["og_url"], ENT_NOQUOTES, 'utf-8'); ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <?php echo htmlspecialchars("id", ENT_NOQUOTES, 'utf-8'); ?>
                        </th>
                        <td>
                            <?php echo htmlspecialchars($doc1["id"], ENT_NOQUOTES, 'utf-8'); ?>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <?php echo htmlspecialchars("Description", ENT_NOQUOTES, 'utf-8'); ?>
                        </th>
                        <td>
                            <?php
                            if (array_key_exists("description",$doc1))  {
                                echo htmlspecialchars($doc1["description"], ENT_NOQUOTES, 'utf-8');
                            } else {
                                echo "N/A";
                            }

                            //echo htmlspecialchars($highlightContent, ENT_NOQUOTES, 'utf-8');
                            ?>
                        </td>
                    </tr>
                    <!-- <tr>
                        <th>
                            <?php 
                            // echo htmlspecialchars("Snippet", ENT_NOQUOTES, 'utf-8'); 
                            ?>
                        </th>
                        <td>
                            <?php
                            // $pageContent = getPageContent($doc1["testid"]);
                            // $highlightContent = getHighlight($queryWords, $pageContent);
                            
                            // if (strcmp($highlightContent, "") != 0) {
                            //     echo($highlightContent);
                            // } else if (array_key_exists("og_description",$doc1))  {
                            //     echo htmlspecialchars($doc1["og_description"], ENT_NOQUOTES, 'utf-8');
                            // } else {
                            //     echo "No snippet available";
                            // }

                            //echo htmlspecialchars($highlightContent, ENT_NOQUOTES, 'utf-8');
                            ?>
                        </td>
                    </tr> -->
                </table>
            </li>
            <?php
        }
        ?>
    </ol>
    <?php
}
?>
</body>

</html>
