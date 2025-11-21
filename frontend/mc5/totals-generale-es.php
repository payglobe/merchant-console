<?php
require_once 'authentication.php';


if (!($role=="Admin"))
{
  echo "Non Hai i permessi per accedere";
  die();
}
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


	//CALCOLO IMPORTO TOTALE E TRANSAZ - ESPAÑA VERSION

	if (isset($_GET['WHERE'])) {

		$query = "SELECT importo FROM tracciato_pos_es  WHERE ".urldecode($_GET['WHERE'])."";
	}else {
		$query = "SELECT importo FROM tracciato_pos_es ";
	}



	$result = $db->query($query);
	$outp = "";
	$totale =0;
	$numtransactions = 0;
	foreach($result as $rs){

		$totale = $totale + floatval($rs["importo"]);
		$numtransactions++;

	}


// ESPAÑA: tracciato_pos_es doesn't have tipoOperazione column
// For eCommerce, we'll use internazionale as approximation since all ES data is eCommerce
if (isset($_GET['WHERE'])) {

		$query = "SELECT importo FROM tracciato_pos_es where domestico = 'Internazionale' AND ".urldecode($_GET['WHERE'])."";
	}else {
		$query = "SELECT importo FROM tracciato_pos_es where domestico = 'Internazionale'";
	}



	$result = $db->query($query);
	$outp = "";
	$totaleComm =0;

	foreach($result as $rs){

		$totaleComm = $totaleComm + floatval($rs["importo"]);


	}



// TOTALE INTERNAZIONALE


if (isset($_GET['WHERE'])) {
		$query = "SELECT importo FROM tracciato_pos_es where domestico = 'Internazionale'  AND ".urldecode($_GET['WHERE'])."";
	}else {
		$query = "SELECT importo FROM tracciato_pos_es where domestico = 'Internazionale' ";
	}


	$result = $db->query($query);

	$internazionale =0;
	foreach($result as $rs){

		$internazionale = $internazionale + floatval($rs["importo"]);

	}

	if (isset($_GET['WHERE'])) {

		$query = "SELECT importo FROM tracciato_pos_es where domestico = 'PagoBancomat' AND ".urldecode($_GET['WHERE'])."";
	}else {

		$query = "SELECT importo FROM tracciato_pos_es where domestico = 'PagoBancomat' ";
	}


	$result = $db->query($query);

	$bancomat =0;
	foreach($result as $rs){

		$bancomat = $bancomat + floatval($rs["importo"]);

	}


	//$outp = '{"totale":'.$totale.',"internazionale":"'.$internazionale.'","pagobancomat":"'.$bancomat.'"}';
	$outp = '{"totale":'.$totale.',"internazionale":"'.$internazionale.'","internazionalecomm":"'.$totaleComm.'","pagobancomat":"'.$bancomat.'","numtransactions":"'.$numtransactions.'"}';

	echo($outp);
?>
