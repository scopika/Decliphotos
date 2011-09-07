<?php
//autorisation
require_once 'pre.php';
require_once 'auth.php';
include_once realpath(dirname(__FILE__)) . '/../../../../fonctions/authplugins.php';
autorisation('decliphotos');

include_once(realpath(dirname(__FILE__)) . "/../Imagedeclidisp.class.php");
$idproduit = !empty($produit->id) ? $produit->id : '';

// Suppression d'une photo (ces actions ne peuvent à priori pas être appellées via un pipeline)?
if(!empty($_REQUEST['decliphotos_action']) && $_REQUEST['decliphotos_action'] == 'declidisp_photos_supprimer') {
	if(preg_match('/^[0-9]{1,}$/', $_REQUEST['id_photo'])) {
		$image = new ImageDeclidisp();
		$image->charger($_REQUEST['id_photo']);
		
		//suppression du fichier sicelui-ci n'est pas utilisé sur une autre declidispdesc
		$req = $image->query('
			SELECT count(id) AS total 
			FROM ' . $image->table . '
			WHERE fichier=(SELECT fichier FROM ' . $image->table . ' WHERE id='.$_REQUEST['id_photo'].')');
		$res = mysql_fetch_object($req);
		if($res->total > 1) {
		    // suppression du fichier
		    $fichier = realpath(dirname(__FILE__)) . "/../../../client/gfx/photos/declidisp/". $image->fichier;
		    @unlink($fichier);
		}
		// suppression en BDD
		$image->supprimer();
	}
}

// Classement des photos
if(!empty($_REQUEST['decliphotos_action']) && $_REQUEST['decliphotos_action'] == 'declidisp_photos_order') {
	if(preg_match('/^[0-9]{1,}$/', $_REQUEST['id_photo'])) {
		$image = new ImageDeclidisp();
		$image->charger($id);
		$image->changer_classement($_REQUEST['id_photo'],$_GET['sens']);
	}
}
?>
<script type="text/javascript" src="../client/plugins/decliphotos/js/decliphotos.js"></script>

<div class="entete_liste_config">
	<div class="titre">ASSOCIER DES IMAGES AUX DECLINAISONS</div>
	<div class="fonction_valider"><a href="#" class="decliphotos_valider">VALIDER LES MODIFICATIONS</a></div>
</div>

<div class="blocs_pliants_prod decliphotos" id="pliantdecliphotos">

	<?php 
	$currentDeclinaisonId = null;
	$imageDesc = new Imagedesc();
	$imageDeclidisp = new ImageDeclidisp();

	foreach((array) $resultats as $key => $row) {

		// Changement de déclinaison ?
		if($row->declinaison_id != $currentDeclinaisonId) {
			$currentDeclinaisonId = $row->declinaison_id;
			if($key > 0) echo '</ul> <!-- /.accordion-wrapper -->';
			?>
			<ul class="ligne1">
				<li class="cellule">Déclinaison &quot;<?php echo $row->declinaison_titre; ?>&quot;</li>
			</ul>
			<ul class="accordion-wrapper-decliphotos">
			<?php
		}
		$photos = $imageDeclidisp->charger_declidispdesc_photos($row->declidispdesc_id, $idproduit);
		$totalPhotos = count($photos);
		?>

		<li class="accordion-item">
    		<a href="#" title="" class="accordion-toggle">
    			<?php 
    			echo $row->declidispdesc_titre;
                if($totalPhotos > 0) {
    			    echo ' [' . $totalPhotos . ' image' . (($totalPhotos > 1) ? 's' : '') . ']';
                }
    			?>
    		</a>
    		<div class="accordion-deroulant">
    			<fieldset class="photoform clearfix">
    			    <?php 
    				// Un champs bidon indispensable juste pour que l'upload soit bien traité 
    				// dans le cas où la case 'toutes les langues' est décochée
    				$fieldName = 'decliphotos[photos][' . $row->declidispdesc_id . '][uploads][0][foo]';
    				 ?>
    				<input type="hidden" name="<?php echo $fieldName; ?>" value="bar" />
    			
    				<legend>Ajouter une photo</legend>
        			<!-- Champs upload -->
        			<?php //$fieldName = 'decliphotos_' . $row->declidispdesc_id . '[]'; 
    		    	    $fieldName = 'decliphotos[photos][' . $row->declidispdesc_id . '][uploads][0][data]'; 
    		    	 ?>
        			        			
        			<label for="<?php echo $fieldName; ?>">Ajouter une photo</label>
        			<!-- <input type="hidden" name="decliphotos_action[<?php echo $row->declidispdesc_id; ?>]" value="declidisp_upload" />-->
        			<!-- <input type="hidden" name="<?php echo $uploadFieldName; ?>" value="declidisp_upload" />-->
    		    	<input type="file" size="18" name="<?php echo $fieldName; ?>" id="<?php echo $fieldName; ?>"/>

    		    	<!-- Checkbox "ajouter dans toutes les langues -->
        			<?php 
    		    	if(!empty($row->autrelang)) {
    		    	    $fieldName = 'decliphotos[photos][' . $row->declidispdesc_id . '][uploads][0][fallback_langs]'; ?>
    		    	    <br/><label for="<?php echo $fieldName; ?>">Ajouter la photo dans toutes les langues</label>
    		    	    <input type="checkbox" name="<?php echo $fieldName; ?>" checked="checked" value="true"/>
    		    	    <?php 
    		    	} ?>
    		    </fieldset>

    		    <?php
		    	// Liste des photos
				if($totalPhotos > 0) { 
					foreach((array) $photos as $image) {
						$imageDesc->charger($image->id, $lang);
						?>
						<div class="photoform clearfix">
    						<div class="fields">
    							<!-- titre -->
    							<div class="field">
    								<?php $fieldName = 'decliphotos[photos][' . $row->declidispdesc_id . '][photos][' . $image->id . '][titre]'; ?>
    								<label for="<?php echo $fieldName; ?>">Titre</label>
    								<input type="text" name="<?php echo $fieldName; ?>" id="<?php echo $fieldName; ?>" value="<?php echo $imageDesc->titre; ?>" />
    							</div>
    							
    							<!-- chapo -->
    							<div class="field">
    								<?php $fieldName = 'decliphotos[photos][' . $row->declidispdesc_id . '][photos][' . $image->id . '][chapo]'; ?>
    								<label for="<?php echo $fieldName; ?>">Chapo</label>
    								<textarea name="<?php echo $fieldName; ?>" id="<?php echo $fieldName; ?>"><?php echo $imageDesc->chapo; ?></textarea>
    							</div>
    							
    							<!-- chapo -->
    							<div class="field">
    								<?php $fieldName = 'decliphotos[photos][' . $row->declidispdesc_id . '][photos][' . $image->id . '][description]'; ?>
    								<label for="<?php echo $fieldName; ?>">Description</label>
    								<textarea name="<?php echo $fieldName; ?>" id="<?php echo $fieldName; ?>"><?php echo $imageDesc->description; ?></textarea>
    							</div>
    						</div>
    						<div class="preview">
        						<p class="actions">
        							<!-- classement : monter -->
        							<a href="<?php echo $decliphotos_urlRetour; ?>&amp;decliphotos_action=declidisp_photos_order&amp;id_photo=<?php echo($image->id); ?>&amp;sens=M">Monter</a>
        							<!-- classement : descendre -->
        							<a href="<?php echo $decliphotos_urlRetour; ?>&amp;decliphotos_action=declidisp_photos_order&amp;id_photo=<?php echo($image->id); ?>&amp;sens=D">Descendre</a>
        							<!-- supprimer -->
        							[<a href="<?php echo $decliphotos_urlRetour; ?>&amp;decliphotos_action=declidisp_photos_supprimer&amp;id_photo=<?php echo($image->id); ?>" class="supprimer" title="supprimer">X</a>]
        						</p>
    							<img src="../client/plugins/decliphotos/fonctions/redimlive.php?type=declidisp&nomorig=<?php echo($image->fichier); ?>&amp;width=225&amp;height=&amp;opacite=" border="0" alt="aperçu" />
    						</div>
    					</div> <!-- .photoform -->
						<?php
					} // endforeach $photos
				} ?>
			</div>
		</li> <!-- /.accordion-item -->
		<?php
	} // end foreach $declidisps
	?>
</div> <!-- /.blocs_pliants_prod .decliphotos -->