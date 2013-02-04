<?php
	define('CS546', true);
	include('includes/settings.php');
	include('includes/template.php');
	 
	$SETTINGS["PAGE_TITLE"] = "Examples Page"; //This gets used in header.php after  "include('includes/header.php')"
	
	//Init the template (You can echo as many templates as listed in the filenames)
	$main_body = new Template($SETTINGS["TEMPLATE_DIR"]);
	$main_body->set_filenames(array(
	 'example_body' => 'example_body.htm'
	));
	
	$my_text = "You are viewing the example page. HTML must be edited in the template files, data gets taken care of by PHP! =]";
	
	//Assigning variables example
	$main_body->assign_vars(array(
		'EXAMPLE' => $my_text,
		'MY_VAR' => "foo",
	));	 
	
	//Looping example
	for($i = 0; $i < 10; $i++)
	{
		$main_body->assign_block_vars('my_row', Array(
				'MY_VAR1' => "ABC",
				'MY_VAR2' => $i,
		));
	}
	
	//Switch example (Same as looping but we just don't put anything)
	$main_body->assign_block_vars('switch_test_on', Array());
	
	//Off switches are simply not assigned and they won't appear
	//$main_body->assign_block_vars('switch_test_off', Array());
			 
	//Echo everything
	include('includes/header.php');
	$main_body->pparse('example_body');
	include('includes/footer.php');
?>