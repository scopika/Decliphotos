<?php
// Surcharge de fonctions/redimlive.php pour que les images des declidisp
// puissent être redimensionnées par thelia (checktype=0)
include_once(realpath(dirname(__FILE__)) . "/../../../../fonctions/divers.php");

// Declaration des variables
$vars = array('type', 'nomorig', 'height', 'width', 'opacite', 'nb', 'miroir');

foreach($vars as $var) {
	if (isset($_REQUEST[$var]))
		$$var = $_REQUEST[$var];
	else
		$$var = '';
}

// C'est juste pour paramétrer le checktype=0 qu'on surcharge redimlive.php
$nomcache = redim($type, $nomorig, $width, $height, $opacite, $nb, $miroir,0);
if ($nomcache != '' && preg_match("/([^\/]*).((jpg|gif|png|jpeg))/i", $nomorig, $nsimple)) {

	switch(strtolower($nsimple[2])) {
        case "gif" :
            header("Content-type: image/gif");
        	break;
        case "jpg": case "jpeg":
            header("Content-type: image/jpeg");
            break;
        case "png":
            header("Content-type: image/png");
        break;
        default:
        	exit();
	}
	
	if ($stat = @stat($nomcache)) {

		header('Last-Modified: '.date('r', $stat['mtime']));
		header('Content-Length: '.$stat['size']);
	}

	readfile(realpath(dirname(__FILE__)) . "/../../../../" . $nomcache);
}
