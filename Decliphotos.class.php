<?php
include_once(realpath(dirname(__FILE__)) . "/../../../classes/PluginsClassiques.class.php");
include_once(realpath(dirname(__FILE__)) . "/../../../classes/Imagedesc.class.php");
include_once(realpath(dirname(__FILE__)) . "/../../../classes/Declidisp.class.php");
include_once(realpath(dirname(__FILE__)) . "/../../../classes/Declidispdesc.class.php");
include_once(realpath(dirname(__FILE__)) . "/../../../classes/Declinaison.class.php");
include_once(realpath(dirname(__FILE__)) . "/../../../classes/Declinaisondesc.class.php");
include_once(realpath(dirname(__FILE__)) . "/Imagedeclidisp.class.php");

class Decliphotos extends PluginsClassiques{

	/**
	 * Installation
	 * @see PluginsClassiques::init()
	 */
	function init(){
		// ajout d'un champs 'declidispdesc' dans la table image
		$this->query('ALTER TABLE  `image` ADD  `declidispdesc` INT( 11 ) UNSIGNED NOT NULL AFTER  `dossier` , ADD INDEX (  `declidispdesc` );');

		// ajout d'un champs 'declidispdesc_prod' dans la table image
		// On ne peut pas utiliser le champs 'produit' déjà présent, car ça fait foirer la boucle IMAGE de base.
		$this->query('ALTER TABLE  `image` ADD  `declidispdesc_prod` INT( 11 ) UNSIGNED NOT NULL AFTER  `declidispdesc` , ADD INDEX (  `declidispdesc_prod` );');

		// dossier d'upload
		if(!is_dir(realpath(dirname(__FILE__)) . '/../../../client/gfx/photos/declidisp/')) {
			mkdir(realpath(dirname(__FILE__)) . '/../../../client/gfx/photos/declidisp/');
		}
		// dossier de cache
		if(!is_dir(realpath(dirname(__FILE__)) . '/../../../client/cache/declidisp/')) {
			mkdir(realpath(dirname(__FILE__)) . '/../../../client/cache/declidisp/');
		}
	}

	/**
	 * Désinstallation
	 * @see PluginsClassiques::destroy()
	 */
	function destroy(){
		$this->query('ALTER TABLE  `image` DROP `declidispdesc`');
		$this->query('ALTER TABLE  `image` DROP `declidispdesc_prod`');
	}

	/**
	 * Modification d'un produit
	 * @see PluginsClassiques::modprod()
	 */
	function modprod($produit) {
		$this->actions($produit);
	}

	/**
	 * Modification d'une déclinaison
	 * @see PluginsClassiques::moddeclinaison()
	 */
	function moddeclinaison($declinaison) {
	    $this->actions();
	}

	/**
	 * Traitements des actions
	 * @param Produit $produit
	 */
    function actions (Produit $produit=null) {
		$lang=$_SESSION["util"]->lang;
		if(!empty($_REQUEST['lang']) && preg_match('/^[0-9]{1,}$/', $_REQUEST['lang'])) $lang=$_REQUEST['lang'];
		if(empty($lang)) $lang=1;

		// Instances de classes dont on aura besoin
		$declidispdescObj = new Declidispdesc();
		$imagedescObj = new Imagedesc();

		foreach((array) $_POST['decliphotos']['photos'] as $declidispdescId => $decliphoto) {
		    // upload(s)
		    foreach((array) $_FILES['decliphotos']['tmp_name']['photos'][$declidispdescId]['uploads'] as $key => $fileTmpName) {
		        $file = array(
		            'name' => $_FILES['decliphotos']['name']['photos'][$declidispdescId]['uploads'][$key]['data'],
		            'type' => $_FILES['decliphotos']['type']['photos'][$declidispdescId]['uploads'][$key]['data'],
		            'size' => $_FILES['decliphotos']['size']['photos'][$declidispdescId]['uploads'][$key]['data'],
		            'tmp_name' => $_FILES['decliphotos']['tmp_name']['photos'][$declidispdescId]['uploads'][$key]['data'],
		            'error' => $_FILES['decliphotos']['error']['photos'][$declidispdescId]['uploads'][$key]['data']
		        );
		        if(!empty($file['error'])) continue;
                $image = $this->upload($file, $declidispdescId, $produit);

		        // Doit-on répercuter l'upload sur toutes les autres langues ?
				if($image && !empty($decliphoto['uploads'][$key]['fallback_langs'])) {
                    $reqDeclidispOtherLangs = $this->query('
                        SELECT d1.id
                        FROM ' .
                        	$declidispdescObj->table . ' AS d1, ' .
                        	$declidispdescObj->table . ' AS d2
                        WHERE
                        	d2.id=' . $declidispdescId . '
                       		AND d2.declidisp = d1.declidisp
                        	AND d1.id!='. $declidispdescId
					);
        			while($row = mysql_fetch_object($reqDeclidispOtherLangs)){
        			    $imageTmp = clone $image;
        			    $imageTmp->id='';
        			    $imageTmp->declidispdesc = $row->id;
        			    $imageTmp->add();
            		}
				}
		    } // fin des uploads

		    // modification des photos (titre, chapo, description)
    		foreach((array) $decliphoto['photos'] as $imageId => $image) {
    			if(!$imagedescObj->charger($imageId, $lang)) {
    			    $imagedescObj->id = '';
                    $imagedescObj->image = $imageId;
                    $imagedescObj->lang = $lang;
    			}
    			$imagedescObj->titre = empty($image['titre']) ? '' : mysql_real_escape_string($image['titre']);
    			$imagedescObj->chapo = empty($image['chapo']) ? '' : mysql_real_escape_string($image['chapo']);
    			$imagedescObj->description = empty($image['description']) ? '' : mysql_real_escape_string($image['description']);
    			if(!$imagedescObj->id) $imagedescObj->add();
    			else $imagedescObj->maj();
    		}
		}
		return $this;
	}

	/**
	 * Boucle <THELIA type="DECLIPHOTOS">
	 * @see PluginsClassiques::boucle()
	 */
	function boucle($texte, $args) {

	    $params = array();
		// param declinaison
		$params['declinaison']  = lireTag($args, 'declinaison', 'int');
		if(!preg_match('/^[0-9]{1,}$/', $params['declinaison'])) $params['declinaison'] = '';

		// param declidisp
		$params['declidisp']        = lireTag($args, 'declidisp', 'int');
		if(!preg_match('/^[0-9]{1,}$/', $params['declidisp'])) $params['declidisp'] = '';

		// param produit
		$params['produit']          = lireTag($args, 'produit', 'int');
		if(!preg_match('/^[0-9]{1,}$/', $params['produit'])) $params['produit'] = '';

		// param lang
		$params['lang']             = lireTag($args, 'lang', 'int');
		if(empty($params['lang'])) $params['lang'] = $_SESSION['navig']->lang;

		// param fallback
		$params['fallback'] = '|' . lireTag($args, 'fallback');
		$params['fallbacks'] = array();
		$params['fallbacks'] = explode('|', $params['fallback']);
		foreach((array) $params['fallbacks'] as $key => $fallback) {
		    $params['fallbacks'][$key] = explode(',', $fallback);
		}

		// params LIMIT et ORDER BY
		$params['debut']  = lireTag($args,"debut", 'int');
		$params['num']              = lireTag($args,"num", 'int');
		$params['classement']       = lireTag($args, "classement");

		// params config image(s)
		$params['largeur']          = lireTag($args, "largeur", "int_list");
		$params['hauteur']          = lireTag($args, "hauteur", "int_list");
		$params['opacite']          = lireTag($args, "opacite", "int");
		$params['noiretblanc']      = lireTag($args, "noiretblanc", "int");
		$params['miroir']           = lireTag($args, "miroir", "int");

		if(empty($params['declinaison']) && empty($params['declidisp']) && empty($params['produit'])) return '';

		// On boucle sur les fallbacks jusqu'à trouver au moins un résultat
		$results = array(); // tableau de résultats
		foreach((array) $params['fallbacks'] as $key => $fallbacks) {
		    $paramsSQL = $params;
		    $paramsSQL['fallbacks'] = $params['fallbacks'][$key];
		    $req = $this->query($this->_boucleSQL($paramsSQL));
		    $total = mysql_num_rows($req);
		    if($total > 0) {
        		while($row = mysql_fetch_object($req)){
        		    $results[] = $row;
        		}
                break;
		    }
		}

		$res = ''; // résultat final;
		$compt = 1;
		$total = count($results);
		foreach((array) $results as $row) {
		    // images : #ID, #TITRE, #CHAPO, #DESCRIPTION
		    $temp = str_replace("#ID", $row->id, $texte);
    		$temp = str_replace("#TITRE",$row->titre,$temp);
			$temp = str_replace("#CHAPO",$row->chapo,$temp);
			$temp = str_replace("#DESCRIPTION",$row->description,$temp);

			// On peut demander l'image dans autant de variantes que l'on veut (largeurs, hauteurs,...),
			// elles seront accessibles dans la boucle via
			// #FICHIER, #IMAGE,
			// #2_FICHIER, #2_IMAGE,
			// #3_FICHIER, #3_IMAGE,
			// etc
			$largeurs     = explode(',', $params['largeur']);
			$hauteurs     = explode(',', $params['hauteur']);
			$opacites     = explode(',', $params['opacite']);
            $noiretblancs = explode(',', $params['noiretblanc']);
            $miroirs      = explode(',', $params['miroir']);
            $biggestArray = count(max($largeurs, $hauteurs, $opacites, $noiretblancs, $miroirs));
            for($i=0; $i<$biggestArray; $i++) {
                $largeur = (!empty($largeurs[$i]) && preg_match('/^[0-9]{1,}$/', $largeurs[$i]) ? $largeurs[$i] : '');
                $hauteur = (!empty($hauteurs[$i]) && preg_match('/^[0-9]{1,}$/', $hauteurs[$i]) ? $hauteurs[$i] : '');
                $opacite = (!empty($opacites[$i]) && preg_match('/^[0-9]{1,}$/', $opacites[$i]) ? $opacites[$i] : '');
                $noiretblanc = (!empty($noiretblancs[$i]) && preg_match('/^[0-9]{1,}$/', $noiretblancs[$i]) ? $noiretblancs[$i] : '');
                $miroir = (!empty($miroirs[$i]) && preg_match('/^[0-9]{1,}$/', $miroirs[$i]) ? $miroirs[$i] : '');

                $baliseIMAGE = array('#' . ($i+1) . '_IMAGE');
                $baliseFICHIER = array('#' . ($i+1) . '_FICHIER');
                if($i==0) { // on garde la syntaxe des balises de la boucle IMAGE pour le premier résultat
                    $baliseIMAGE[] = '#IMAGE';
                    $baliseFICHIER[] = '#FICHIER';
                }

                $temp = str_replace($baliseFICHIER,  "client/gfx/photos/declidisp/" . $row->fichier, $temp);
                if($largeur || $hauteur || $opacite || $noiretblanc || $miroir) {
                    $nomcache = redim("declidisp", $row->fichier, $largeur, $hauteur, $opacite, $noiretblanc, $miroir, 0);
                    $temp = str_replace($baliseIMAGE, $nomcache, $temp);
    			} else $temp = str_replace($baliseIMAGE, "client/gfx/photos/declidisp/" . $row->fichier, $temp);
            }

			// #PRODUIT
			$temp = str_replace("#PRODUIT", $row->declidispdesc_prod, $temp);

			// declidisp : #DECLIDISP et #DECLIDISPTITRE
            $temp = str_replace("#DECLIDISPTITRE",$row->declidisptitre,$temp);
			$temp = str_replace("#DECLIDISP",$row->declidisp,$temp);

			// déclinaison : #DECLINAISON, #DECLITITRE, #DECLICHAPO, #DECLIDESCRIPTION
			$temp = str_replace("#DECLINAISON",$row->declinaison,$temp);
			$temp = str_replace("#DECLITITRE",$row->declititre,$temp);
            $temp = str_replace("#DECLICHAPO",$row->declichapo,$temp);
            $temp = str_replace("#DECLIDESCRIPTION",$row->declidescription,$temp);

            // compteurs
			$temp = str_replace("#COMPT",$compt,$temp);
			$temp = str_replace("#TOTAL",$total,$temp);
            $compt++;
			$res .= $temp;
		}
 		return $res;
	}

	/**
	 * Génère la requête SQL utilisée pour les boucles
	 * @param array $params
	 */
	protected function _boucleSQL($params = array()) {
	    //var_dump($params);
	    // Objets dont on va avoir besoin
		$imageClass = new ImageDeclidisp();
        $imagedescClass = new Imagedesc();
        $declidispClass = new Declidisp();
        $declidispdescClass = new Declidispdesc();
        $declinaisonClass = new Declinaison();
        $declinaisondescClass = new Declinaisondesc();

        // fallback langue
        $params['lang'] = (empty($params['lang']) ? 1 : $params['lang']);
        if(in_array('-lang', $params['fallbacks'])) $params['lang']=1;

        // fallback produit
        if(!empty($params['produit']) && in_array('-produit', $params['fallbacks'])) {
            $params['produit']=0;
        }

		// WHERE ...
		$search = '';
        $search .= ' AND image.declidispdesc=declidispdesc.id
        			 AND declidispdesc.lang = ' . $params['lang'] . '
        			 AND declidispdesc.declidisp = declidisp.id
        			 AND declinaison.id = declidisp.declinaison
        			 AND declinaisondesc.declinaison = declinaison.id
        			 AND declinaisondesc.lang=' . $params['lang'];
        if($params['declinaison'] != '') {
            $search .= ' AND declinaison.id=' . $params['declinaison'];
        }
        if($params['declidisp'] != '') {
            $search .= ' AND declidisp.id=' . $params['declidisp'];
        }
        $search .= ' AND image.declidispdesc_prod=' . (!empty($params['produit']) ? $params['produit'] : 0);
        if(!empty($params['produit'])) {
            $exdecprod = new Exdecprod();
            $search .= ' AND declidisp.id NOT IN(
                			 SELECT declidisp FROM ' . $exdecprod->table . ' WHERE produit=' . $params['produit'] . '
                		 )';
        }

        // ORDER BY ...
        $order = '';
        switch($params['classement']) {
        	case 'id' :
        		$order = 'image.id ASC';
        		break;
        	case 'idinv' :
        	    $order = 'image.id DESC';
        		break;
        	case 'aleatoire' :
        		$order = 'RAND()';
        		break;
        	case 'manuelinv' :
        		$order = 'image.classement DESC';
        		break;
        	default :
        		$order = 'image.classement ASC';
        }
        $order = ' ORDER BY ' . $order;

        // LIMIT ...
        $limit= '';
        if(!empty($params['debut']) || !empty($params['num'])) {
            $limit_deb = ($params['debut'] != '') ? $params['debut'] : 0;
            $limit_num = ($params['num'] != '') ? $params['num'] : 99999;
            $limit = ' LIMIT ' . $limit_deb . ',' . $limit_num;
        }

        // assemblage de la requête SQL
		$query = '
			SELECT
				image.id,
				image.declidispdesc_prod,
				image.fichier,
				imagedesc.titre,
				imagedesc.chapo,
				imagedesc.description,
				declidisp.id AS declidisp,
				declidispdesc.titre AS declidisptitre,
				declidisp.declinaison AS declinaison,
				declinaisondesc.titre AS declititre,
				declinaisondesc.chapo AS declichapo,
				declinaisondesc.description AS declidescription
			FROM ';
		// LEFT OUTER JOIN car toutes les images ne disposent pas d'une correspondance en table imagedesc,
		// et il ne faut pas pour autant les exclure
		$query .= $imageClass->table . ' AS image LEFT OUTER JOIN ' . $imagedescClass->table . ' AS imagedesc ON(image.id=imagedesc.image AND imagedesc.lang=' . $params['lang'] . '),' .
		        $declidispClass->table . ' AS declidisp,' .
		        $declidispdescClass->table . ' AS declidispdesc,' .
                $declinaisonClass->table . ' AS declinaison, ' .
                $declinaisondescClass->table . ' AS declinaisondesc
            WHERE 1 ' . $search . $order . $limit;
		//echo('' . $query . '<br/>');
		return $query;
	}

	/**
	 * Upload d'une photo
	 * @param $_FILES $uploadData
	 * @param int $declidisp
	 * @param int $produit
	 * @return Image $image
	 */
	function upload($uploadData, $declidispdesc, Produit $produit=null) {

		$photo = $uploadData['tmp_name'];
		$photo_name = $uploadData['name'];

		if(empty($photo)) {
			echo 'Aucune photo transmise';
			return false;
		}

		preg_match("/([^\/]*).((jpg|gif|png|jpeg))/i", $photo_name, $decoupe);
		$fich = eregfic($decoupe[1]);
		$extension = $decoupe[2];
		if($fich == "" || $extension == "") {
			echo 'Fichier non conforme';
			return false;
		}

		$image = new ImageDeclidisp();
		$imagedesc = new Imagedesc();

		$query = "SELECT MAX(classement) AS maxClassement
					FROM $image->table
					WHERE declidispdesc_prod='" . $produit->id . "'
					AND declidispdesc='" . $declidispdesc . "'";
		$resul = $this->query($query);
     	$maxClassement = mysql_result($resul, 0, "maxClassement");

     	$image->declidispdesc = $declidispdesc;
		$image->declidispdesc_prod = empty($produit) ? 0 : $produit->id;
		$image->classement = $maxClassement + 1;

		$lastid = $image->add();
		$image->charger($lastid);
		$image->fichier = ereg_caracspec($fich . "_" . $lastid) . "." . strtolower($extension);
		$image->maj();

		copy($photo, '../client/gfx/photos/declidisp/' . $image->fichier);

	    modules_fonction("uploadimage", $lastid);
		return $image;
	}

	/**
	 * Génère l'interface utilisateur
	 * @param unknown_type $produit
	 * @param unknown_type $lang
	 */
	public function renderUI(Produit $produit=null, Declinaison $decli=null, $lang=1) {

		// vérif de la langue
		if(!preg_match('/^[0-9]{1,}$/', $lang)) $lang=1;

		$decliObj 			= new Declinaison();
		$decliDescObj		= new Declinaisondesc();
		$decliDispObj 		= new Declidisp();
		$decliDispDescObj 	= new Declidispdesc();
		$langObj 	        = new Lang();
		$resultats = array();

		$query = '
			SELECT
				declidispdesc.id AS declidispdesc_id,
				declidispdesc.declidisp AS declidisp_id,
				declidispdesc.lang AS declidispdesc_lang,
				declidispdesc.titre AS declidispdesc_titre,
				declidispdesc.classement AS declidispdesc_classement,
				declinaisondesc.titre AS declinaison_titre,
				declinaison.id AS declinaison_id,';
		// On regarde si il y a plus d'une langue paramétrée,
		// auquel cas on va proposer une checkbox pour uploader
		// les photos dans toutes les langues d'un coup
		$query .= '(SELECT id FROM ' . $langObj->table . ' LIMIT 1,1) AS autrelang';

		$query .= '
			FROM
				' . $decliObj->table . ' AS declinaison,
				' . $decliDescObj->table . ' AS declinaisondesc,
				' . $decliDispObj->table . ' AS declidisp,
				' . $decliDispDescObj->table . ' AS declidispdesc
			WHERE
				declinaison.id = declinaisondesc.declinaison';

		if(!empty($decli))
		    $query .= ' AND declinaison.id =' . $decli->id;

		$query .= '
				AND declinaisondesc.declinaison = declidisp.declinaison
				AND declinaisondesc.lang=' . $lang . '
				AND declidisp.id = declidispdesc.declidisp
				AND declidispdesc.lang=' . $lang . '
			ORDER BY
				declinaison.classement ASC,
				declidispdesc.classement ASC';
		$DeclidispsReq = $decliObj->query($query);
		//var_dump($query);
		while($row = mysql_fetch_object($DeclidispsReq)) {
			$resultats[] = $row;
		}

		if(!empty($produit)) $decliphotos_urlRetour = 'produit_modifier.php?ref=' . lireParam('ref') . '&rubrique=' . lireParam('rubrique', 'int') . '&lang=' . $lang;
		else $decliphotos_urlRetour = 'declinaison_modifier.php?id=' . $decli->id;
		include realpath(dirname(__FILE__)) . "/inc/ui.php";
	}
}
