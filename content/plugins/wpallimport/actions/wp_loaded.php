<?php

function pmxi_wp_loaded() {				
	
	@ini_set("max_input_time", PMXI_Plugin::getInstance()->getOption('max_input_time'));
	@ini_set("max_execution_time", PMXI_Plugin::getInstance()->getOption('max_execution_time'));		

	/* Check if cron is manualy, then execute import */
	$cron_job_key = PMXI_Plugin::getInstance()->getOption('cron_job_key');
	
	if (!empty($cron_job_key) and !empty($_GET['import_id']) and !empty($_GET['import_key']) and $_GET['import_key'] == $cron_job_key and !empty($_GET['action']) and in_array($_GET['action'], array('processing','trigger'))) {		
		$import = new PMXI_Import_Record();
		$import->getById($_GET['import_id']);		
		if ( ! $import->isEmpty() ){

			if (!in_array($import->type, array('url', 'ftp', 'file'))) {
				exit('Scheduling update is not working with "upload" import type.'); die;
			}

			switch ($_GET['action']) {
				case 'trigger':
					if ( ! $import->processing ){
						$import->set(array(
							'triggered' => 1,						
							'imported' => 0,
							'created' => 0,
							'updated' => 0,
							'skipped' => 0,
							'queue_chunk_number' => 0,
							'current_post_ids' => ''
						))->update();
					}
					
					exit('Cron job triggered'); die;			 		

					break;
				case 'processing':
					if ( $import->processing == 1 and time() - strtotime($import->registered_on) > 300){ // it means processor crashed, so it will reset processing to false, and terminate. Then next run it will work normally.
						$import->set(array(
							'processing' => 0
						))->update();
					}
					
					// start execution imports that is in the cron process					
					if ( (int) $import->triggered and ! (int) $import->processing ){
						$import->execute(); // repull record from database since list didn't contain all the fileds for performance optimization purposes
						exit('Import complete'); die;
					}
					else {
						exit('Import already processing. Request skipped.'); die;
					}

					break;					
			}			
		}
	}		
}