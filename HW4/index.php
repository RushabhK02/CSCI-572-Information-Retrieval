<!-- http://192.168.1.19:8983/solr/myexample/select?indent=on&q=foundation -->
<?php
header('Content-Type:text/html; charset=utf-8');
$limit = 10;
$query= isset($_REQUEST['q'])?$_REQUEST['q']:false;
$results = false;

if($query){
        require_once('solr-php-client/Apache/Solr/Service.php');
        $solr = new Apache_Solr_Service('192.168.1.19', 8983, '/solr/myexample/');
        if(get_magic_quotes_gpc() == 1){
                $query = stripslashes($query);
        }
        try{
		if(!isset($_GET['algorithm']))$_GET['algorithm']="lucene";
		if($_GET['algorithm'] == "lucene"){

			 $results = $solr->search($query, 0, $limit);

		}else{

			$param = array('sort'=>'pageRankFile desc');
			$results = $solr->search($query, 0, $limit, $param);

		}

	 }
        catch(Exception $e){
                die("<html><head><title>SEARCH EXCEPTION</title></head><body><pre>{$e->__toString()}</pre></body></html>");
        }
}
?>


<html>
<head>
        <title> PHP Solr Client Example </title>
<style>
	body{
		background: lightblue; 
	}
	
</style>
</head>
<body>
<h1> WitZZ Search </h1><br/>
<form accept-charset="utf-8" method="get">

        <input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8');?>"/><br/><br/> 
	<input type="radio" name="algorithm" value="lucene" /> Solr's Default - Lucene
	<input type="radio" name="algorithm" value="pagerank" /> Google's - PageRank <br/><br/> 
	<input type="submit" />
</form>
<?php
if($results){
        $total = (int)$results->response->numFound; 
        $start = min(1,$total);
        $end = min($limit, $total); 
?>
<div> Results <?php echo $start; ?> - <<?php echo $end;?> of <?php echo $total;?>:</div> 
<ol style="list-style:none;">
<?php

foreach ($results->response->docs as $doc){
	 foreach($doc as $field => $value){
                if($field == "og_url"){
                        $link = $value; 
                }
        } 


?>

<li>
<a href = <?php echo $link ; ?>>
	<table style ="border: 1px solid black; text-align: left; border-radius:10px; ">

	<?php


		foreach($doc as $field => $value){
			if($field!="id" && $field!="title" && $field!="description" && $field!="og_url")continue;  
			if(sizeof($value)==1){
			?>
			<tr><th><?php echo htmlspecialchars($field, ENT_NOQUOTES, 'utf-8') ; ?></th>
			<td><?php echo htmlspecialchars($value,  ENT_NOQUOTES, 'utf-8') ; ?></td></tr>
			
			<?php } else {?>
			<tr><th><?php echo htmlspecialchars($field, ENT_NOQUOTES, 'utf-8') ; ?></th>
			<td><ol>
			
				<?php 
					foreach($value as $item){

				?>

					<li><?php echo htmlspecialchars($item, ENT_NOQUOTES, 'utf-8' ); ?></li>

				<?php } ?>
			</ol></td></tr>
			<?php } ?>
			

	<?php } 
		 
	?>
	</table></a></li>
	<?php } ?>
	</ol>
	<?php } ?>
</body>
</html>