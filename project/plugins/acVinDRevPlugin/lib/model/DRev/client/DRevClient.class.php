<?php

class DRevClient extends acCouchdbClient implements FacturableClient {

    const TYPE_MODEL = "DRev";
    const TYPE_COUCHDB = "DREV";
    const DENOMINATION_BIO_TOTAL = "BIO_TOTAL";
    const DENOMINATION_BIO_PARTIEL = "BIO_PARTIEL";
    const DENOMINATION_BIO_LIBELLE_AUTO = "Agriculture Biologique";

    public static $denominationsAuto = array(
        self::DENOMINATION_BIO_PARTIEL => "Une partie de mes volumes sont certifiés en Bio",
        self::DENOMINATION_BIO_TOTAL => 'Tous mes volumes sont certifiés en Bio'
    );

    public static function getInstance()
    {

        return acCouchdbManager::getClient("DRev");
    }

    public function find($id, $hydrate = self::HYDRATE_DOCUMENT, $force_return_ls = false) {
        $doc = parent::find($id, $hydrate, $force_return_ls);

        if($doc && $doc->type != self::TYPE_MODEL) {

            throw new sfException(sprintf("Document \"%s\" is not type of \"%s\"", $id, self::TYPE_MODEL));
        }

        return $doc;
    }

    public function findMasterByIdentifiantAndCampagne($identifiant, $campagne, $hydrate = acCouchdbClient::HYDRATE_DOCUMENT) {
        $drevs = DeclarationClient::getInstance()->viewByIdentifiantCampagneAndType($identifiant, $campagne, self::TYPE_MODEL);
        foreach ($drevs as $id => $drev) {

            return $this->find($id, $hydrate);
        }

        return null;
    }

    public function findFacturable($identifiant, $campagne) {
    	$drev = $this->find('DREV-'.str_replace("E", "", $identifiant).'-'.$campagne);

        if(!$drev->validation_odg) {

            return null;
        }

        return $drev;
    }

    public function createDoc($identifiant, $campagne, $papier = false)
    {
        $drev = new DRev();
        $drev->initDoc($identifiant, $campagne);

        $drev->storeDeclarant();

        $etablissement = $drev->getEtablissementObject();

        if(!$etablissement->hasFamille(EtablissementFamilles::FAMILLE_PRODUCTEUR)) {
            $drev->add('non_recoltant', 1);
        }

        if(!$etablissement->hasFamille(EtablissementFamilles::FAMILLE_CONDITIONNEUR)) {
            $drev->add('non_conditionneur', 1);
        }

        if($papier) {
            $drev->add('papier', 1);
        }

        $previous_drev = self::findMasterByIdentifiantAndCampagne($identifiant, $campagne - 1 );
        if ($previous_drev) {
          foreach($previous_drev->getProduitsVci() as $produit) {
            if ($produit->vci->stock_final) {
              $drev->cloneProduit($produit);
            }
          }
        }
        return $drev;
    }

    public function getIds($campagne) {
        $ids = $this->startkey_docid(sprintf("DREV-%s-%s", "0000000000", "0000"))
                    ->endkey_docid(sprintf("DREV-%s-%s", "9999999999", "9999"))
                    ->execute(acCouchdbClient::HYDRATE_ON_DEMAND)->getIds();

        $ids_campagne = array();

        foreach($ids as $id) {
            if(strpos($id, "-".$campagne) !== false) {
                $ids_campagne[] = $id;
            }
        }

        sort($ids_campagne);

        return $ids_campagne;
    }

    public function getDateOuvertureDebut() {
        $dates = sfConfig::get('app_dates_ouverture_drev');

        return $dates['debut'];
    }

    public function getDateOuvertureFin() {
        $dates = sfConfig::get('app_dates_ouverture_drev');

        return $dates['fin'];
    }

    public function isOpen($date = null) {
        if(is_null($date)) {

            $date = date('Y-m-d');
        }

        return $date >= $this->getDateOuvertureDebut() && $date <= $this->getDateOuvertureFin();
    }

    public function getHistory($identifiant, $hydrate = acCouchdbClient::HYDRATE_DOCUMENT) {
        $campagne_from = "0000";
        $campagne_to = ConfigurationClient::getInstance()->getCampagneManager()->getCurrent()."";

        return $this->startkey(sprintf("DREV-%s-%s", $identifiant, $campagne_from))
                    ->endkey(sprintf("DREV-%s-%s_ZZZZZZZZZZZZZZ", $identifiant, $campagne_to))
                    ->execute($hydrate);
    }

    public function getOrdrePrelevements() {
        return array("cuve" => array("cuve_ALSACE", "cuve_GRDCRU", "cuve_VTSGN"), "bouteille" => array("bouteille_ALSACE","bouteille_GRDCRU","bouteille_VTSGN"));
    }
}
