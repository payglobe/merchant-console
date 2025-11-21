<?php
require_once 'authentication.php';
	header('Content-Type: application/json');
	header("Access-Control-Allow-Origin: *");
 // http://merchant-console.payglobe.it/payglobe/mc/totals.php?f=2018-01-17&t=2018-05-16&id=8490480400590
	//CONNECT TO DATABASE NAMED lifeDB WITH MYSQL WITH ROOT USER AND EMPTY PASSWORD
	
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

	
	
	$query = "SELECT count(*) AS count, date(dataOperazione) AS dataOperazione FROM tracciato GROUP BY date( dataOperazione) ORDER BY date(dataOperazione) DESC  limit 7 ";	
	
	
	
	$result = $db->query($query);
	
	$data = array();
		foreach ($result as $row) {
			$data[] = $row;
	}

	print json_encode($data);

	
?>

