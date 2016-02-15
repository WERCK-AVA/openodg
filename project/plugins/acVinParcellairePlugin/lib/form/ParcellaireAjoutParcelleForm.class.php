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
class ParcellaireAjoutParcelleForm extends acCouchdbObjectForm {

    private $appellationKey;

    public function __construct(acCouchdbJson $object, $appellationKey, $options = array(), $CSRFSecret = null) {
        $this->appellationKey = $appellationKey;
        parent::__construct($object, $options, $CSRFSecret);
    }

    public function configure() {
        $appellationNode = $this->getAppellationNode();

        $hasLieuEditable = is_string($appellationNode) ? true : $appellationNode->getConfig()->hasLieuEditable();

        $produits = $this->getProduits();
        $communes = $this->getCommunes();
        $this->setWidget('commune', new sfWidgetFormChoice(array('choices' => $communes)));
        $this->setWidget('section', new sfWidgetFormInput());
        $this->setWidget('numero_parcelle', new sfWidgetFormInput());
        $this->setWidget('superficie', new sfWidgetFormInputFloat(array('float_format' => '%01.2f')));

        if (!$hasLieuEditable) {
            $this->setWidget('lieuCepage', new sfWidgetFormChoice(array('choices' => $produits)));
        } else {
            $this->setWidget('cepage', new sfWidgetFormChoice(array('choices' => $produits)));
            $this->setWidget('lieuDit', new sfWidgetFormInput());
        }

        $this->widgetSchema->setLabel('commune', 'Commune :');
        $this->widgetSchema->setLabel('section', 'Section :');
        $this->widgetSchema->setLabel('numero_parcelle', 'Numéro :');
        if (!$hasLieuEditable) {
            $this->widgetSchema->setLabel('lieuCepage', 'Lieu-dit/cépage :');
        } else {
            $this->widgetSchema->setLabel('lieuDit', 'Lieu Dit:');
            $this->widgetSchema->setLabel('cepage', 'Cépage :');
        }

        $this->setValidator('commune', new sfValidatorChoice(array('required' => true, 'choices' => array_keys($communes)), array('required' => "Aucune commune saisie.")));
        $this->setValidator('section', new sfValidatorRegex(array("required" => true, "pattern" => "/^[0-9A-Z]+$/"), array("invalid" => "La section doit être composée de numéro et lettres en majuscules")));
        $this->setValidator('numero_parcelle', new sfValidatorRegex(array("required" => true, "pattern" => "/^[0-9]+$/"), array("invalid" => "Le numéro doit être un nombre")));

        if (!$hasLieuEditable) {
            $this->setValidator('lieuCepage', new sfValidatorChoice(array('required' => true, 'choices' => array_keys($produits)), array('required' => "Aucun cépage saisie.")));
        } else {
            $this->setValidator('cepage', new sfValidatorChoice(array('required' => true, 'choices' => array_keys($produits)), array('required' => "Aucun cépage saisie.")));
            $this->setValidator('lieuDit', new sfValidatorString(array('required' => true)));
        }

        $this->setValidator('superficie', new sfValidatorNumber(array('required' => true)));

        $this->widgetSchema->setNameFormat('parcellaire_ajout_parcelle[%s]');
    }

    public function getAppellationNode() {

        return $this->getObject()->getAppellationNodeFromAppellationKey($this->appellationKey, true);
    }

    public function getProduitsForVtSGN() {
        $this->allCepagesAppellation = array();
        $allAppellationsKeys = array_keys(ParcellaireClient::getInstance()->getAppellationsKeys());
        foreach ($allAppellationsKeys as $appellationKey) {
            $appellationNode = $this->getObject()->getAppellationNodeFromAppellationKey($appellationKey, true);

            foreach ($appellationNode->getConfig()->getProduitsFilter(_ConfigurationDeclaration::TYPE_DECLARATION_PARCELLAIRE) as $key => $cepage) {
                $keyCepage = str_replace('/', '-', $key);
                $libelleCepage = $cepage->getLibelleLong();
                $lieu = $cepage->getCouleur()->getLieu();
                $libelleLieu = $lieu->getLibelle();
                $this->allCepagesAppellation[$keyCepage] = trim($libelleLieu . ' ' . $libelleCepage);
            }
        }
        return $this->allCepagesAppellation;
    }

    public function getProduits() {
        $appellationNode = $this->getAppellationNode();
        $this->allCepagesAppellation = array();
        if ($appellationNode == ParcellaireClient::APPELLATION_VTSGN) {
            $this->allCepagesAppellation = $this->getProduitsForVtSGN();
        } else {
            foreach ($appellationNode->getConfig()->getProduitsFilter(_ConfigurationDeclaration::TYPE_DECLARATION_PARCELLAIRE) as $key => $cepage) {
                $keyCepage = str_replace('/', '-', $key);
                $libelleCepage = $cepage->getLibelleLong();
                $lieu = $cepage->getCouleur()->getLieu();
                $libelleLieu = $lieu->getLibelle();
                $this->allCepagesAppellation[$keyCepage] = trim($libelleLieu . ' ' . $libelleCepage);
            }
        }

        asort($this->allCepagesAppellation);
        $this->allCepagesAppellation = array_merge(array('' => ''), $this->allCepagesAppellation);
        return $this->allCepagesAppellation;
    }

    public function getCommunes() {
        $config = $this->getObject()->getConfiguration();
        $communes = array();
        foreach ($config->communes as $communeName => $dpt) {
            $communes[strtoupper($communeName)] = $communeName;
        }
        return array_merge(array('' => ''), $communes);
    }

    protected function doUpdateObject($values) {

        if ((!isset($values['commune']) || empty($values['commune'])) ||
                (!isset($values['section']) || empty($values['section'])) ||
                (!isset($values['numero_parcelle']) || empty($values['numero_parcelle']))
        ) {
            return;
        }

        $config = $this->getObject()->getConfiguration();
        $commune = $values['commune'];
        $section = preg_replace('/^0*/', '', $values['section']);
        $numero_parcelle = preg_replace('/^0*/', '', $values['numero_parcelle']);
        $lieu = null;
        $dpt = $config->communes[$commune];

        if (!$this->getAppellationNode()->getConfig()->hasLieuEditable()) {
            $cepage = $values['lieuCepage'];
        } else {
            $cepage = $values['cepage'];
            $lieu = $values['lieuDit'];
        }

        $parcelle = $this->getObject()->addParcelleForAppellation($this->appellationKey, $cepage, $commune, $section, $numero_parcelle, $lieu, $dpt);

        $parcelle->superficie = $values['superficie'];
    }

    public function getLieuDetailForAutocomplete() {
        $lieuxDetail = array();
        if ($this->getAppellationNode() == ParcellaireClient::APPELLATION_VTSGN) {
            $allAppellationsKeys = array_keys(ParcellaireClient::getInstance()->getAppellationsKeys());
            foreach ($allAppellationsKeys as $appellationKey) {
                $appellationNode = $this->getObject()->getAppellationNodeFromAppellationKey($appellationKey, true);
                foreach ($appellationNode->getLieuxEditable() as $libelle) {
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
        } else {
            foreach ($this->getAppellationNode()->getLieuxEditable() as $libelle) {
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

    protected function updateDefaultsFromObject() {
        parent::updateDefaultsFromObject();
    }

}
