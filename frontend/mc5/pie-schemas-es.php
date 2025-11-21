<?php
require_once 'authentication.php';
header('Content-Type: application/json');
//header("Access-Control-Allow-Origin: *");
 // http://merchant-console.payglobe.it/payglobe/mc/totals.php?f=2018-01-17&t=2018-05-16&id=8490480400590
//CONNECT TO DATABASE NAMED lifeDB WITH MYSQL WITH ROOT USER AND EMPTY PASSWORD


 $colori  = array('giallo','coral','BurlyWood','green','LightBlue','Gray','LightSalmon','red','MediumSeaGreen','MediumPurple','Moccasin','Gold','DarkSlateGray');

use AWSSmgrApp\AWSSmgrWrapper;
$smgr = new AWSSmgrWrapper();
$smgr->initialize();
$db = $smgr->getSecretInfo('prod/mysql/tasfr');
$db_connetdata=json_decode($db);


//header("Access-Control-Allow-Origin: *");
 // http://merchant-console.payglobe.it/payglobe/mc/totals.php?f=2018-01-17&t=2018-05-16&id=8490480400590
//CONNECT TO DATABASE NAMED lifeDB WITH MYSQL WITH ROOT USER AND EMPTY PASSWORD

	try{
		$db = new PDO('mysql:host='.$db_connetdata->{'host'}.';dbname='.$db_connetdata->{'dbname'}.';', $db_connetdata->{'username'},$db_connetdata->{'password'});
	} catch(Exception $e){
		echo "Cannot connect to database.";
		return;
	}


// ESPAÑA VERSION - using tracciatoes instead of tracciato
if (isset($_GET['WHERE'])) {

		//$query = "SELECT count(*) AS count , tag4f AS tag4f FROM tracciatoes  WHERE ".urldecode($_GET['WHERE'])."   GROUP BY tag4f ORDER BY tag4f ASC";
		 $query = "SELECT count(*) AS count, SUBSTRING_INDEX(tag4f,' ',1) AS tag4f FROM tracciatoes WHERE ".urldecode($_GET['WHERE']).  " GROUP BY SUBSTRING_INDEX(tag4f,' ',1) ORDER BY tag4f  ASC";
	}else {
		//$query = "SELECT count(*) AS count, tag4f AS tag4f FROM tracciatoes  GROUP BY tag4f ORDER BY tag4f ASC";
		  $query = "SELECT count(*) AS count, SUBSTRING_INDEX(tag4f,' ',1) AS tag4f FROM tracciatoes  GROUP BY SUBSTRING_INDEX(tag4f,' ',1) ORDER BY tag4f  ASC";
	}


	$result = $db->query($query);
	$filtered = array();
	$c=0;
	foreach ($result as $key => $value) {
				//echo " k ".$key." v ".$value['tag4f']."  ".$value['count'];
				if ( strcasecmp($value['tag4f'],"----")){
									$newvalue= $arrayName = array('count' => $value['count'] ,'tag4f' => $value['tag4f'],'colore'=> $colori[$c++] );
									if ($c > sizeof($colori)) $c=0;
									array_push($filtered, $newvalue);
								}

				}



	$data = array();
		foreach ($filtered as $row) {


			$data[] = $row;
	}

	print json_encode($data);


?>

