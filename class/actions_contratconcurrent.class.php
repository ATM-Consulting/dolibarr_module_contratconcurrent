<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    class/actions_contratconcurrent.class.php
 * \ingroup contratconcurrent
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class ActionsContratConcurrent
 */
class ActionsContratConcurrent
{
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
	}



	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 * 	 */
	 
	function formObjectOptions($parameters, &$object, &$action, $hookmanager) {
		
		if(in_array('commcard',explode(':', $parameters['context']))) {
		
			?><script type="text/javascript">
			
				$(document).ready(function() {
					
					$('a[href^="<?php echo dol_buildpath('/contrat/card.php',1) ?>"]').each(function(i,item) {
						
						if($(item).find('img').length == 0) {
							$.ajax({
								url:"<?php echo dol_buildpath('/contratconcurrent/script/interface.php',1) ?>"
								,data:"get=is-concurrent&"+$(item).get(0).search.substring(1)
							}).done(function(data) {
								
								if(data == 1) {
									
									$(item).css({
										color:"grey"
										,'font-weight':'bold'
									}).attr('title','Est un contrat concurrent');
								}
								
							});
							
						}
								
					});
					
				});
			
			</script><?php
			
		}
	}
	 
	function printFieldPreListTitle($parameters, &$object, &$action, $hookmanager) {
		
		if($parameters['currentcontext']=='main' && strpos($_SERVER['PHP_SELF'],'/contrat/list.php')!==false)
		 {
		
			?><script type="text/javascript">
			
				$(document).ready(function() {
					
					$('a[href^="<?php echo 'card.php' ?>"]').each(function(i,item) {
							
						$.ajax({
							url:"<?php echo dol_buildpath('/contratconcurrent/script/interface.php',1) ?>"
							,data:"get=is-concurrent&"+$(item).get(0).search.substring(1)
						}).done(function(data) {
							
							if(data == 1) {
								
								$(item).css({
									color:"grey"
									,'font-weight':'bold'
								}).attr('title','Est un contrat concurrent');
							}
							
						});
					
					
								
					});
					
				});
			
			</script><?php
			
		}
		
	}
	 
	function beforePDFCreation($parameters, &$object, &$action, $hookmanager) {
		
		if ($parameters['currentcontext'] == 'contractcard')
		{
			global $db, $conf, $langs;
			
			dol_include_once('/societe/class/societe.class.php');
			
			foreach($object->lines as &$line) {
				
				if(!empty($line->array_options['options_fk_leaser'])) {
					
					$leaser = new Societe($db);
					$leaser->fetch($line->array_options['options_fk_leaser']);
					
					if(!empty($line->product_label))$line->product_label.=' - ';
					$line->product_label.=$leaser->name;
					
				}	
				
			}
				
		}
		
		
	}	 
	 
	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{

		if ($parameters['currentcontext'] == 'contractcard')
		{
			 ?><script type="text/javascript">
			 $(document).ready(function() {
			 	$('a.butAction[href*="facture.php"]').closest('div.divButAction').remove();
			 });
			 </script>
			 <?php
		}

	}
}