<?php
if (!defined('CS546'))
{
	die("Nice try, no hacking allowed.");
}

//Creates the bottom part of the site stopping at </html>

//Init the template
$footer = new Template($SETTINGS["TEMPLATE_DIR"]);
$footer->set_filenames(array(
         'footer' => 'footer.htm')
);

//Parse, and display.
$footer->pparse('footer');
?>