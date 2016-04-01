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


	function doActions ($parameters, &$object, &$action, $hookmanager)
	{
		if (GETPOST('addcontratline'))
		{
			$fk_line_contrat_origin = GETPOST('fk_line_contrat_origin', 'int');
			if ($fk_line_contrat_origin > 0)
			{
				global $db;
				dol_include_once('/contrat/class/contrat.class.php');
				
				$lineContrat = new ContratLigne($db);
				$res = $lineContrat->fetch($fk_line_contrat_origin);
				if ($res > 0)
				{
					$linePropal = new PropaleLigne($db);
					$array_options = array();
					
					require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		            $extrafields = new ExtraFields($db);
		            $TExtra = $extrafields->fetch_name_optionals_label($linePropal->table_element);
					
					// Récupération des extrafields de la ligne contrat vers la ligne propal
					$lineContrat->fetch_optionals();
					foreach ($lineContrat->array_options as $key => $val)
					{
						$subkey = substr($key, 8);
						if (isset($TExtra[$subkey]))
						{
							$array_options[$key] = $val;
						}
					}
					
					if (isset($TExtra['fk_contratdet_origin'])) $array_options['options_fk_contratdet_origin'] = $lineContrat->id;
					
					$object->addline($lineContrat->description, $lineContrat->subprice, $lineContrat->qty, $lineContrat->tva_tx, $lineContrat->localtax1_tx, $lineContrat->localtax2_tx, $lineContrat->fk_product, $lineContrat->remise_percent, 'HT',  0.0, $lineContrat->info_bits, 1, -1, 0, 0, 0, $lineContrat->pa_ht, '', $lineContrat->date_ouverture_prevue, $lineContrat->date_fin_validite, $array_options, $lineContrat->fk_unit);
				}
			}
		}
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function formAddObjectLine ($parameters, &$object, &$action, $hookmanager) 
	{
		global $db,$langs,$user,$conf;
		
		$langs->load('contratconcurrent@contratconcurrent');
		$TContext = explode(':',$parameters['context']);
		if (in_array('propalcard',$TContext)) 
        {
        	dol_include_once('/core/class/html.form.class.php');
		//$f = new TFormCore;
			$form = new Form($db);
			$colspan=7;
			if($conf->margin->enabled)$colspan+=2;
			
        	?>
        	
        	<tr class="liste_titre nodrag nodrop">
				<td colspan="9"><?php echo $langs->trans('ImportContratLine') ?></td>
				<td></td>
			</tr>
			<tr class="pair">
			<?php
				$TContratConcurrent = $this->getTContratConcurrent();
				
				
			?>
				<script type="text/javascript">
					function checkInputRadioContratConcurrent() 
					{
						$('input#prod_entry_mode_import_line_contrat_concurrent').click();
						$('#select_type option[value="-1"]').attr('selected', true).prop('selected', true);
						$('#idprod').val('');
						$('#search_idprod').val('');
					}
				</script>
				<td colspan="<?php echo $colspan; ?>">
					<label>
						<input id="prod_entry_mode_import_line_contrat_concurrent" type="radio" value="contrat_line" name="prod_entry_mode">
						<?php echo $langs->trans('add_fk_contrat_line_in_propal'); ?>
					</label>
					
					<?php
					$moreparam = 'onchange="checkInputRadioContratConcurrent();" style="min-width:150px;"';
					print Form::selectarray('fk_line_contrat_origin', $TContratConcurrent, '', 1, 0, 0, $moreparam, 0, 0, 0, '', '', 1);				
					?>
				</td>
				<td valign="middle" align="center">
					<input type="submit" id="addcontratline" name="addcontratline" value="Ajouter" class="button">
				</td>
			
			</tr>
			
        	<?php
		}
	}

	function getTContratConcurrent()
	{
		global $db;
		
		$Tab = array();
		
		$sql = 'SELECT c.ref, cd.rowid AS fk_contratdet, cd.fk_product, cd.description, cd.total_ht, p.ref AS product_ref, p.label AS product_label
				FROM '.MAIN_DB_PREFIX.'contrat c
				INNER JOIN '.MAIN_DB_PREFIX.'contratdet cd ON (c.rowid = cd.fk_contrat)
				INNER JOIN '.MAIN_DB_PREFIX.'contrat_extrafields ce ON (c.rowid = ce.fk_object)
				LEFT JOIN '.MAIN_DB_PREFIX.'product p ON (p.rowid = cd.fk_product)
				
				WHERE ce.concurrent = 1
		';
		
		$resql = $db->query($sql);
		if ($resql)
		{
			while ($line = $db->fetch_object($resql))
			{
				if ($line->fk_product) $Tab[$line->fk_contratdet] = $line->ref.' - '.$line->product_ref.' - '.$line->product_label.' - '.price($line->total_ht, 0, '', 1, 'MT', -1, 'auto').' HT';
				else $Tab[$line->fk_contratdet] = $line->ref.' - '.$line->description.' - '.price($line->total_ht, 0, '', 1, 'MT', -1, 'auto').' HT';
				
			}
		}
		
		return $Tab;
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
										color:"#9900bb"
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
									color:"#9900bb"
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