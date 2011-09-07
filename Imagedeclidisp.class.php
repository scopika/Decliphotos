<?php
include_once(realpath(dirname(__FILE__)) . "/../../../classes/Image.class.php");
class ImageDeclidisp extends Image {

	public $declidispdesc;
	public $declidispdesc_prod;
	
	function ImageDeclidisp($id = 0){
		$this->bddvars[] = 'declidispdesc';
		$this->bddvars[] = 'declidispdesc_prod';
		parent::__construct($id);
	}
	
	function changer_classement($id, $sens){
		if(!$this->charger($id)) return false;

		$remplace = new ImageDeclidisp();
		switch($sens) {
			case 'M' :
				$res = $remplace->getVars('
					SELECT * 
					FROM ' . $this->table . '  
					WHERE 
						declidispdesc_prod=' . $this->declidispdesc_prod . ' 
    					AND declidispdesc=' . $this->declidispdesc .  '
    					AND classement<' . $this->classement . '
					ORDER BY classement DESC LIMIT 0,1
				');
				break;
			default :
				$res = $remplace->getVars('
					SELECT * 
					FROM ' . $this->table . ' 
					WHERE 
						declidispdesc_prod=' . $this->declidispdesc_prod . ' 
    					AND declidispdesc=' . $this->declidispdesc .  '
    					AND classement>' . $this->classement . ' 
					ORDER BY classement ASC LIMIT 0,1
				');
				break;
		}
		if(!$res) return false;
		
		$sauv = $remplace->classement;
		$remplace->classement = $this->classement;
		$this->classement = $sauv;
		
		$remplace->maj();
		$this->maj();
	}
	
	
	/**
	 * Recherche les photos associées à une declidispdesc (et éventuellement un produit) 
	 * @param int $declidispdesc
	 * @param int $produit
	 * @return array ImageDeclidisp
	 */
	function charger_declidispdesc_photos($declidispdesc, $produit=0) {

		if(!preg_match('/^[0-9]{1,}$/', $declidispdesc)) return 0;
		if(!preg_match('/^[0-9]{1,}$/', $produit)) $produit=0;
		
		$results = array();
		$query = '
			SELECT id 
			FROM ' . $this->table . ' 
			WHERE declidispdesc=' . $declidispdesc . '
			AND declidispdesc_prod=' . $produit . '
			ORDER BY classement ASC';
		$resul = $this->query($query);
		while($row = mysql_fetch_object($resul)){
			$image = new ImageDeclidisp();
			$image->charger($row->id);
			$results[] = $image;
		}
		return $results;
	}	
}
