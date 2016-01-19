<?php
	
	require '../config.php';
	
	$get = GETPOST('get');
	
	switch ($get) {
		case 'is-concurrent':
		
			$fk_contrat = (int)GETPOST('id');
		
			dol_include_once('/contrat/class/contrat.class.php');
			$c=new Contrat($db);
			$c->fetch($fk_contrat);
			
			if($c->id>0 && $c->array_options['options_concurrent'] == 1) echo 1;
			else echo 0; 
			
			break;
		default:
			
			break;
	}
