<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ParcellaireAjoutParcelleForm
 *
 * @author mathurin
 */
abstract class ParcellaireAffectationParcelleForm extends acCouchdbObjectForm {

    protected $produits = null;

    public function configure() {

        $this->setWidget('produit', new sfWidgetFormChoice(array('choices' => $this->getProduits())));
        $this->setValidator('produit', new sfValidatorChoice(array('required' => true, 'choices' => array_keys($this->getProduits())),array('required' => "Aucune appellation saisie.")));
        $this->widgetSchema->setLabel('produit', 'Produit :');

        $this->setWidget('commune', new sfWidgetFormChoice(array('choices' => $this->getCommunes())));
        $this->setValidator('commune', new sfValidatorChoice(array('required' => true,'choices' => array_keys($this->getCommunes())), array('required' => "Aucune commune saisie.")));
        $this->widgetSchema->setLabel('commune', 'Commune :');

        $this->setWidget('section', new sfWidgetFormInput());
        $this->setValidator('section', new sfValidatorRegex(array("required" => true, "pattern" => "/^[0-9A-Z]+$/"), array("invalid" => "La section doit être composée de numéro et lettres en majuscules")));
        $this->widgetSchema->setLabel('section', 'Section :');

        $this->setWidget('numero_parcelle', new sfWidgetFormInput());
        $this->setValidator('numero_parcelle',new sfValidatorRegex(array("required" => true, "pattern" => "/^[0-9]+[A-Za-z]*$/"), array("invalid" => "La section doit être composée d'un numéro")));
        $this->widgetSchema->setLabel('numero_parcelle', 'Numéro :');

        $this->setWidget('superficie', new sfWidgetFormInputFloat(array('float_format' => '%01.2f')));
        $this->setValidator('superficie', new sfValidatorNumber(array('required' => true, 'min' => '0.01'), array('min' => 'La superficie doit être supérieure à 0')));

        $this->setWidget('campagne_plantation', new sfWidgetFormInput());
        $this->setValidator('campagne_plantation', new sfValidatorRegex(array("required" => true, "pattern" => "/^[0-9]+$/"), array("invalid" => "La campagne de plantation doit contenir uniquement des nombres")));
        $this->widgetSchema->setLabel('campagne_plantation', 'Année de plantation :');

        $this->setWidget('cepage', new sfWidgetFormChoice(array('choices' => $this->getCepages())));
        $this->setValidator('cepage', new sfValidatorChoice(array('required' => true, 'choices' => array_keys($this->getCepages())), array('required' => "Aucun cépage saisie.")));
        $this->widgetSchema->setLabel('cepage', 'Cépage :');

        $this->setWidget('lieudit', new sfWidgetFormInput());
        $this->setValidator('lieudit', new sfValidatorString(array('required' => false)));
        $this->widgetSchema->setLabel('lieudit', 'Lieu-dit:');

        $this->widgetSchema->setNameFormat('parcelle[%s]');
    }

    public function getProduits()
    {
        if (!$this->produits) {
            $produits = $this->getObject()->getConfigProduits();
            foreach ($produits as $produit) {
                $this->produits[$produit->getHash()] = $produit->getLibelleComplet();
            }
        }
        return array_merge(array('' => ''), $this->produits);
    }

    public function getCepages()
    {

        return array_merge(array("" => ""), $this->getObject()->getDocument()->getConfiguration()->getCepagesAutorises());
    }

    public function getCommunes() {
       $config = $this->getObject()->getDocument()->getConfiguration();
       $communes = array();
       if($config->exist('commune')) {
           foreach($config->communes as $communeName => $dpt) {
               $communes[strtoupper($communeName)] = $communeName;
           }
       }

       $communes = CommunesConfiguration::getInstance()->getByCodeCommune();

       return array_merge(array('' => ''), $communes);
    }

    public function getAppellationNode() {

        return null;
    }

    protected function doUpdateObject($values) {
        $communes = $this->getCommunes();
        if ((!isset($values['commune']) || empty($values['commune'])) ||
                (!isset($values['section']) || empty($values['section'])) ||
                (!isset($values['numero_parcelle']) || empty($values['numero_parcelle']))
        ) {
            return;
        }

        $config = $this->getObject()->getDocument()->getConfiguration();
        $commune = $communes[$values['commune']];
        $section = preg_replace('/^0*/','',$values['section']);
        $numero_parcelle = preg_replace('/^0*/','',$values['numero_parcelle']);
        $lieu = null;
//        $dpt = $config->communes[$commune];
        $dpt = null;

        if ($this->getAppellationNode() && !$this->getAppellationNode()->getConfig()->hasLieuEditable()) {
            $cepage = $values['lieuCepage'];
        } else {
            $cepage = $values['cepage'];
            $lieu = $values['lieuDit'];
        }

        $parcelle = $this->getObject()->getDocument()->addParcelle($values['produit'], $values['cepage'], $values['campagne_plantation'], $commune, $section, $numero_parcelle, $lieu);
        $parcelle->superficie = $values['superficie'];

        $parcelle->active = 1;
        if ($this->getAppellationNode() && $this->getAppellationNode()->getKey() == 'appellation_'.ParcellaireAffectationClient::APPELLATION_ALSACEBLANC) {
        	$parcelle->vtsgn = 1;
        }

        if($this->getObject() instanceof ParcellaireAffectationParcelle && $this->getObject()->getHash() != $parcelle->getHash()) {
            $this->getObject()->getCepage()->detail->remove($this->getObject()->getKey());
        }
    }

    public function getLieuDetailForAutocomplete() {
        $lieuxDetail = array();
        foreach ($this->getObject()->declaration->getLieuxEditable() as $libelle) {
        	$lieuxDetail[] = $libelle;
        }
        $entries = array();
        foreach ($lieuxDetail as $lieu) {
        	$entry = new stdClass();
            $entry->id = trim($lieu);
            $entry->text = trim($lieu);
            $entries[] = $entry;
        }
        return $entries;
    }

}
