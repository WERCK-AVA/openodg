<?php

class TourneeSaisieDegustationForm extends acCouchdbForm {

    public function configure() {
        $produits = $this->getProduits();

        $this->setWidget('numero', new bsWidgetFormInput());
        $this->setValidator('numero', new sfValidatorInteger(array("required" => true)));

        $this->setWidget('etablissement', new bsWidgetFormInput());
        $this->setValidator('etablissement', new sfValidatorString(array("required" => true)));

        $this->setWidget('produit', new bsWidgetFormChoice(array('choices' => $produits)));
        $this->setValidator('produit', new sfValidatorChoice(array('required' => true, 'choices' => array_keys($produits)), array('required' => "Le produit est requis")));
    }

    public function getProduits() {
        $produitsConfig = ConfigurationClient::getConfiguration()->declaration->getProduitsFilter(_ConfigurationDeclaration::TYPE_DECLARATION_DEGUSTATION);
        $produits = array("" => "");

        foreach ($produitsConfig as $hash => $produit) {
            $produits[$hash] = $produit->getLibelleComplet();
        }

        return $produits;
    }

}
