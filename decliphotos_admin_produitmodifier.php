<?php
require_once 'pre.php';
require_once 'auth.php';
include_once realpath(dirname(__FILE__)) . '/../../../fonctions/authplugins.php';
autorisation('decliphotos');

// Si aucune ref n'est transmise, ou si le produit n'existe pas, on arrête là le massacre!
if(empty($_REQUEST['ref'])) return false;
$produit = new Produit();
if(!$produit->charger(lireParam('ref'))) return false;

// langue
$lang=1;
if(!empty($_GET['lang'])) $lang=$_GET['lang'];

require_once realpath(dirname(__FILE__)) . '/Decliphotos.class.php';
$decliphotos = new Decliphotos();
$decliphotos->renderUI($produit, null, $lang);