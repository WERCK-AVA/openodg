<?php
/**
 * Model for Degustation
 *
 */

class Degustation extends BaseDegustation {
    public function constructId() {
        $id = sprintf("%s-%s-%s-%s", DegustationClient::TYPE_COUCHDB, $this->identifiant, str_replace("-", "", $this->date_degustation), $this->appellation);

        if($this->appellation_complement) {
            $id .= $this->appellation_complement;
        }

        if($this->millesime) {
            $id .= $this->millesime;
        }

        $this->set('_id', $id);
    }

    public function getMillesime() {
        if(!$this->_get('millesime')) {
            return ((int) substr($this->date_degustation, 0, 4) - 1)."";
        }

        return $this->_get('millesime');
    }

    public function getEtablissementObject() {

        return EtablissementClient::getInstance()->find("ETABLISSEMENT-".$this->identifiant);
    }

    public function updateFromEtablissement() {
        $etablissement = $this->getEtablissementObject();

        $this->cvi = $etablissement->cvi;
        $this->raison_sociale = $etablissement->raison_sociale;
        $this->adresse = $etablissement->adresse;
        $this->code_postal = $etablissement->code_postal;
        $this->commune = $etablissement->commune;
    }

    public function updateFromDRev($drev = null) {
        if(!$drev) {
            $drev = DRevClient::getInstance()->find($this->drev);
        }
        $this->drev = $drev->_id;
        $this->cvi = $drev->declarant->cvi;
        $this->raison_sociale = $drev->declarant->raison_sociale;
        $this->adresse = $drev->chais->cuve_->adresse;
        $this->code_postal = $drev->chais->cuve_->code_postal;
        $this->commune = $drev->chais->cuve_->commune;

        $prelevement = $drev->prelevements->{"cuve_".$this->appellation};
        $this->date_demande = $prelevement->date;
        $this->lots = array();

        if($this->appellation == "VTSGN") {
            $vtsgn_items = array("vt" => "VT", "sgn" => "SGN");
            foreach($drev->declaration->getProduitsCepage() as $detail) {
                foreach($vtsgn_items as $vtsgn_key => $vtsgn_libelle) {
                    if(!$detail->get("volume_revendique_".$vtsgn_key)) {
                        continue;
                    }

                    $lot = $prelevement->lots->add(str_replace("/", "-", $detail->getHash()."-".$vtsgn_key));
                    $lot->libelle = sprintf("%s %s", $detail->getCepageLibelle(), $vtsgn_libelle);
                    $lot->add('libelle_produit', sprintf("%s", $detail->getProduitLibelleComplet()));
                    $lot->hash_produit = $detail->getCepage()->getHash();
                    $lot->volume_revendique = $detail->get("volume_revendique_".$vtsgn_key);
                    $lot->vtsgn = $vtsgn_libelle;
                    $lot->nb_vtsgn = $lot->nb_vtsgn + 1;
                }
            }
        }

        foreach($prelevement->lots as $l_key => $l) {
            $lot = $this->lots->add($l_key);
            $lot->hash_produit = $l->hash_produit;
            $lot->libelle = $l->libelle;
            if($l->exist('libelle_produit')) {
                $lot->libelle_produit = $l->libelle_produit;
            }
            if($this->appellation == "VTSGN") {
                $lot->nb = $l->nb_vtsgn;
            } else {
                $lot->nb = $l->nb_hors_vtsgn;
            }
            $lot->vtsgn = $l->vtsgn;
            $lot->volume_revendique = $l->volume_revendique;
            $lot->prelevement = 0;
        }
    }

    public function getProduits() {
        $produits = array();

        foreach($this->lots as $lot) {
            $produits[$lot->key] = $lot->libelle;
        }

        return $produits;
    }

    public function getLastDegustationDate() {
        $last =  DegustationClient::getInstance()->getLastDegustationByStatut($this->appellation, $this->identifiant, "DEGUSTE");

        if(!$last) {
            return null;
        }

        return $last->date_degustation;
    }

    public function getDrev() {
        if($this->_get('drev')) {

            return $this->_get('drev');
        }

        return "DREV-".$this->getIdentifiant()."-2014";
    }

    public function getAppellationLibelle() {
        if(!$this->appellation) {
            return null;
        }

        if(!$this->_get('appellation_libelle')) {
            $appellationsWithLibelle = TourneeCreationForm::getAppellationChoices();
            $this->_set("appellation_libelle", $appellationsWithLibelle[$this->appellation]);
        }

        return $this->_get('appellation_libelle');
    }

    public function isDeguste() {
          foreach($this->prelevements as $prelevement) {
            if(!is_null($prelevement->anonymat_degustation)) {
                return true;
            }
        }

        return false;
    }

    public function getLotsPrelevement() {
        $lots = array();

        foreach($this->lots as $lot) {
            if(!$lot->prelevement) {
                continue;
            }

            $lots[$lot->getKey()] = $lot;
        }

        return $lots;
    }

    public function getNbLots() {
        $nb = 0;
        foreach($this->lots as $lot) {
            if(!$lot->prelevement) {
                continue;
            }

            if(!$lot->nb) {
                $nb++;
            } else {
                $nb = $nb + $lot->nb;
            }
        }

        return $nb;
    }

    public function resetLotsPrelevement() {
        foreach($this->lots as $lot) {
            $lot->prelevement = 0;
        }
    }

    public function isAffecteTournee() {

        return $this->date_prelevement && $this->heure && $this->agent;
    }

    public function getNombrePrelevements() {
        $nb = 0;
        foreach($this->prelevements as $prelevement) {
            if(!$prelevement->isPreleve()) {

                continue;
            }

            $nb++;
        }

        return $nb;
    }

    public function getPrelevementsByAnonymatPrelevement($anonymat_prelevement) {
        foreach($this->prelevements as $prelevement) {
            if($prelevement->anonymat_prelevement == $anonymat_prelevement) {

                return $prelevement;
            }
        }

        return null;
    }

    public function getPrelevementsByAnonymatDegustation($anonymat_degustation) {
        foreach($this->prelevements as $prelevement) {
            if($prelevement->anonymat_degustation == $anonymat_degustation) {

                return $prelevement;
            }
        }

        return null;
    }

    public function cleanPrelevements() {
        $hash_to_delete = array();

        foreach($this->prelevements as $prelevement) {
            if($prelevement->motif_non_prelevement == DegustationClient::MOTIF_NON_PRELEVEMENT_REPORT) {
                $this->add('reports')->add($prelevement->hash_produit);
            }

            if($prelevement->isPreleve()) {
                continue;
            }
            $hash_to_delete[$prelevement->getHash()] = $prelevement->getHash();
        }

        krsort($hash_to_delete);

        foreach($hash_to_delete as $hash) {
            $this->remove($hash);
        }
    }

    public function generateNotes() {
         foreach($this->prelevements as $prelevement) {
            foreach(DegustationClient::getInstance()->getNotesTypeByAppellation($this->appellation) as $key_type_note => $libelle_type_note) {
                    $prelevement->notes->add($key_type_note);
            }
        }
    }

    public function addPrelevementFromLot($lot) {
        $prelevement = $this->prelevements->add();
        $prelevement->hash_produit = $lot->hash_produit;
        $prelevement->libelle = $lot->libelle;
        $prelevement->libelle_produit = $lot->libelle_produit;
        $prelevement->vtsgn = $lot->vtsgn;
        $prelevement->volume_revendique = $lot->volume_revendique;

        $prelevement->preleve = 1;

        return $prelevement;
    }

    public function isTourneeTerminee() {
        if($this->motif_non_prelevement)  {

            return true;
        }

        foreach($this->prelevements as $prelevement) {

            if($prelevement->isPreleve()) {

                return true;
            }
        }

        return false;
    }

    public function isAffectationTerminee() {
        foreach($this->prelevements as $prelevement) {
            if(!$prelevement->isAffectationTerminee()) {

                return false;
            }
        }

        return true;
    }

    public function isDegustationTerminee() {
        foreach($this->prelevements as $prelevement) {
            if(!$prelevement->isDegustationTerminee()) {

                return false;
            }
        }

        return true;
    }

    public function isInCommission($commission) {
        foreach($this->prelevements as $prelevement) {
            if($prelevement->commission == $commission) {

                return true;
            }
        }

        return false;
    }

    public function generateCourrier() {

    }

    public function getCompte() {

        return CompteClient::getInstance()->findByIdentifiant("E" . $this->getIdentifiant());
    }

    public function updateFromCompte() {
        $compte = $this->getCompte();

        $this->email = $compte->email;
        $this->telephone_bureau = $compte->telephone_bureau;
        $this->telephone_prive = $compte->telephone_prive;
        $this->telephone_mobile = $compte->telephone_mobile;
        $chai = $compte->findChai($this->adresse, $this->commune, $this->code_postal);

        if($chai) {
            $this->lat = $chai->lat;
            $this->lon = $chai->lon;
        }

        if(!$this->lat || !$this->lon) {
            $coordonnees = $compte->calculCoordonnees($this->adresse, $this->commune, $this->code_postal);
            if($coordonnees) {
                $this->lat = $coordonnees["lat"];
                $this->lon = $coordonnees["lon"];
            }
        }
    }

    public function getOrganisme() {
        if(!$this->exist('organisme') || !$this->_get('organisme')) {

            return DegustationClient::ORGANISME_DEFAUT;
        }

        return $this->_get('organisme');
    }
}
