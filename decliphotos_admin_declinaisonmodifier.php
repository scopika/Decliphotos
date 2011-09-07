<?php
require_once 'pre.php';
require_once 'auth.php';
include_once realpath(dirname(__FILE__)) . '/../../../fonctions/authplugins.php';
autorisation('decliphotos');

// Si aucun id n'est transmis, ou si la déclinaison n'existe pas, on arrête là le massacre!
require_once realpath(dirname(__FILE__)) . '/../../../classes/Declinaison.class.php';
if(empty($_REQUEST['id']) || !preg_match('/^[0-9]*$/', $_REQUEST['id'])) return false;
$decli = new Declinaison();
if(!$decli->charger($_REQUEST['id'])) return false;

// langue
$lang=1;
if(!empty($_GET['lang'])) $lang=$_GET['lang'];

require_once realpath(dirname(__FILE__)) . '/Decliphotos.class.php';
$decliphotos = new Decliphotos();
$decliphotos->renderUI(null, $decli, $lang);