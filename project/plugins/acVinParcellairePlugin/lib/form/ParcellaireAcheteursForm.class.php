<?php

class ParcellaireAcheteursForm extends acCouchdbForm {

    public function __construct(acCouchdbDocument $doc, $defaults = array(), $options = array(), $CSRFSecret = null) {
        parent::__construct($doc, $defaults, $options, $CSRFSecret);
        $this->updateDefaults($doc);
    }

    public function configure() {
        
        $produits = $this->getDocument()->declaration->getProduitsWithLieuEditable();
        ksort($produits);

        $lieux_editable = $this->getDocument()->declaration->getLieuxEditable();

        foreach($produits as $hash => $cepage) {
            $lieu_libelle = $cepage->getCouleur()->getLieu()->getLibelle();
            if($cepage->getConfig()->hasLieuEditable()) {
                $lieu_libelle = $lieux_editable[preg_replace("|^.*/lieu([^/]*)/.+$|", '\1', $hash)];
            }
            $this->setWidget($hash, new sfWidgetFormChoice(array('choices' => $this->getAcheteurs(), 'multiple' => true, 'expanded' => true)));
            $this->setValidator($hash, new sfValidatorChoice(array('choices' => array_keys($this->getAcheteurs()), 'multiple' => true, 'required' => false)));   
            $this->getWidget($hash)->setLabel(
                sprintf("%s - %s - %s", 
                    str_replace("AOC Alsace ", "", $cepage->getCouleur()->getLieu()->getAppellation()->libelle),
                    $lieu_libelle,
                    $cepage->libelle
                )
            );
        }

        if($this->hasProduits() > 0) {
            $this->validatorSchema->setPostValidator(new ParcellaireAcheteursValidator(null, array("acheteurs" => $this->getAcheteurs())));
        }

        $this->widgetSchema->setNameFormat('parcellaire_acheteurs[%s]');
    }

    public function hasProduits() {

        return count($this->getDocument()->declaration->getProduitsWithLieuEditable()) > 0;
    }

    public function updateDefaults() {
        $defaults = $this->getDefaults();

        $produits = $this->getDocument()->declaration->getProduitsWithLieuEditable();

        if(count($this->getAcheteurs()) == 1) {
            $key_acheteur = key($this->getAcheteurs());
            foreach($produits as $hash => $produit) {
                $defaults[$hash] = array($key_acheteur);
            }
        }

        foreach($produits as $hash => $produit) {
            $lieu_key = null;
            if($produit->getConfig()->hasLieuEditable()) {
                $lieu_key = preg_replace("|^.*/lieu([^/]*)/.+$|", '\1', $hash);
            }
            foreach($produit->getAcheteursNode($lieu_key) as $type => $acheteurs) {
                foreach($acheteurs as $acheteur) {
                    if(!isset($defaults[$hash])) {
                        $defaults[$hash] = array();
                    }
                    $key = sprintf("/acheteurs/%s/%s", $acheteur->getParent()->getKey(), $acheteur->getKey());
                    if(in_array($key, $defaults[$hash])) {
                        continue;
                    }
                    $defaults[$hash] = array_merge($defaults[$hash], array($key));
                }
            }
        }

        $this->setDefaults($defaults);
    }

    public function getAcheteurs() {
        $acheteurs = array();

        foreach($this->getDocument()->acheteurs as $achs) {
            foreach($achs as $acheteur) {
                $acheteurs[$acheteur->getHash()] = sprintf("%s", $acheteur->nom);
            }
        }

        return $acheteurs;
    }

    public function update() {
        foreach($this->getDocument()->getProduits() as $produit) {
                $produit->remove('acheteurs');
                $produit->add('acheteurs');
        }

        $produits = $this->getDocument()->declaration->getProduitsWithLieuEditable();

        foreach($this->values as $hash_produit_value => $hash_acheteurs) {
            if(!is_array($hash_acheteurs)) {
                continue;
            }
            
            foreach($hash_acheteurs as $hash_acheteur) {
                $hash_produit = $produits[$hash_produit_value]->getHash();
                $produit = $this->getDocument()->get($hash_produit);
                $acheteur = $this->getDocument()->get($hash_acheteur);
                $lieu = null;
                if($produit->getConfig()->hasLieuEditable()) {
                    $lieu = preg_replace("|^.*/lieu([^/]*)/.+$|", '\1', $hash_produit_value);
                }
                $produit->addAcheteurFromNode($acheteur, $lieu);
            }
        }
    }

    public function save() {
        $this->getDocument()->save();
    }
}
