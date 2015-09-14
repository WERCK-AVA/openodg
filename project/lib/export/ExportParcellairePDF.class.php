<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ExportParcellairePdf
 *
 * @author mathurin
 */
class ExportParcellairePDF extends ExportPDF {

    protected $parcellaire = null;

    public function __construct($parcellaire, $type = 'pdf', $use_cache = false, $file_dir = null,  $filename = null) {
        $this->parcellaire = $parcellaire;
        if(!$filename) {
            $filename = $this->getFileName(true, true);
        }

        parent::__construct($type, $use_cache, $file_dir, $filename);
    }

    public function create() {
        $this->parcellesByLieux = $this->parcellaire->getParcellesByLieux();

        if(count($this->parcellesByLieux) == 0) {
            $this->printable_document->addPage($this->getPartial('parcellaire/pdfVide', array('parcellaire' => $this->parcellaire)));

            return;
        }

        foreach ($this->parcellesByLieux as $lieuHash => $parcellesByLieu) {
            $this->printable_document->addPage($this->getPartial('parcellaire/pdf', array('parcellaire' => $this->parcellaire, 'parcellesByLieu' => $parcellesByLieu)));
        }
    }

    protected function getHeaderTitle() {
        if($this->parcellaire->isParcellaireCremant()){
            return sprintf("Déclaration d'affectation parcellaire crémant %s", $this->parcellaire->campagne);
        }
        return sprintf("Déclaration d'affectation parcellaire %s", $this->parcellaire->campagne);
    }

    protected function getHeaderSubtitle() {
        $header_subtitle = sprintf("%s\n\n", $this->parcellaire->declarant->nom);
        if (!$this->parcellaire->isPapier()) {
            if ($this->parcellaire->validation && $this->parcellaire->campagne >= "2015") {
                $date = new DateTime($this->parcellaire->validation);
                $header_subtitle .= sprintf("Signé électroniquement via l'application de télédéclaration le %s", $date->format('d/m/Y'));
            }else{
                $header_subtitle .= sprintf("Exemplaire brouilllon");
            }
        }

        if ($this->parcellaire->isPapier() && $this->parcellaire->validation && $this->parcellaire->validation !== true) {
            $date = new DateTime($this->parcellaire->validation);
            $header_subtitle .= sprintf("Reçue le %s", $date->format('d/m/Y'));
        } 

        return $header_subtitle;        
    }

    protected function getConfig() {

        return new ExportDRevPDFConfig();
    }

    public function getFileName($with_rev = false) {

      return self::buildFileName($this->parcellaire, true, false);
    }

    public static function buildFileName($parcellaire, $with_rev = false) {
        
        $prefixName = ($parcellaire->isParcellaireCremant())? "PARCELLAIRE_CREMANT_%s_%s" :"PARCELLAIRE_%s_%s";
        $filename = sprintf($prefixName, $parcellaire->identifiant, $parcellaire->campagne);

        $declarant_nom = strtoupper(KeyInflector::slugify($parcellaire->declarant->nom));
        $filename .= '_' . $declarant_nom;

        if ($with_rev) {
            $filename .= '_' . $parcellaire->_rev;
        }

        return $filename . '.pdf';
    }
}