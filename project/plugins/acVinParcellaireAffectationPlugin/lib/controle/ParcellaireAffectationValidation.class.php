<?php

class ParcellaireAffectationValidation extends DocumentValidation {

    const TYPE_ERROR = 'erreur';
    const TYPE_WARNING = 'vigilance';
    const TYPE_ENGAGEMENT = 'engagement';

    public function __construct($document, $options = null) {
        parent::__construct($document, $options);
    }

    public function configure() {
        /*
         * Warning
         */
        $this->addControle(self::TYPE_WARNING, 'parcellaire_complantation', 'Attention');
        $this->addControle(self::TYPE_ERROR, 'surface_vide', 'Superficie nulle (0 are)');
        $this->addControle(self::TYPE_ERROR, 'parcelle_doublon', 'Parcelle doublonnée');
        $this->addControle(self::TYPE_ERROR, 'acheteur_repartition', "La répartition des acheteurs n'est pas complète");

        /*
         * Error
         */
//        $this->addControle(self::TYPE_ERROR, 'parcellaire_invalidproduct', "Ce cépage non autorisé");
    }

    public function controle() {
        $parcelles = array();
        foreach ($this->document->declaration->getProduitsDetails() as $detailk => $detailv) {
            if(!$detailv->isAffectee()) {
                continue;
            }
            $pid = preg_replace('/.*\//', '', $detailk);
            if (!isset($parcelles[$pid])) {
                $parcelles[$pid] = array();
            }
            array_push($parcelles[$pid], $detailk);
            if (!$detailv->superficie) {
                $this->addPoint(self::TYPE_ERROR, 'surface_vide', 'parcelle n°' . $detailv->section . ' ' . $detailv->numero_parcelle . ' à ' . $detailv->commune . ' déclarée en ' . $detailv->getLibelleComplet(), $this->generateUrl('parcellaire_parcelles', array('id' => $this->document->_id,
                            'appellation' => preg_replace('/appellation_/', '', $detailv->getAppellation()->getKey()),
                            'erreur' => $detailv->getHashForKey())));
            }
        }
        foreach ($parcelles as $pid => $phashes) {
            if (count($phashes) > 1) {
                $detail = $this->document->get($phashes[0]);
                $this->addPoint(self::TYPE_WARNING, 'parcellaire_complantation', '<a href="' . $this->generateUrl('parcellaire_parcelles', array(
                            'id' => $this->document->_id,
                            'appellation' => preg_replace('/appellation_/', '', $detail->getAppellation()->getKey()),
                            'attention' => $detail->getHashForKey())) . "\" class='alert-link' >La parcelle " . $detail->section . ' ' . $detail->numero_parcelle . ' à ' . $detail->commune . " a été déclarée avec plusieurs cépages. </a>"
                        . "&nbsp;S’il ne s’agit pas d’une erreur de saisie de votre part, ne tenez pas compte de ce point de vigilance.", '');
            }
        }
        $uniqParcelles = array();
        foreach ($this->document->declaration->getProduitsDetails() as $pid => $detail) {
            if(!$detail->isAffectee()) {
                continue;
            }
            $keyParcelle = $detail->getCepage() . '/' . $detail->getCommune() . '-' . $detail->getSection() . '-' . $detail->getNumeroParcelle();
            if (array_key_exists($keyParcelle, $uniqParcelles)) {
                $this->addPoint(self::TYPE_ERROR, 'parcelle_doublon', 'parcelle n°' . $detail->getSection() . ' ' . $detail->getNumeroParcelle() . ' à ' . $detail->getCommune() . ' déclarée en ' . $detail->getLibelleComplet(), $this->generateUrl('parcellaire_parcelles', array('id' => $this->document->_id,
                            'appellation' => preg_replace('/appellation_/', '', $detailv->getAppellation()->getKey()),
                            'erreur' => $detail->getHashForKey())));
            } else {
                $uniqParcelles[$keyParcelle] = $keyParcelle;
            }
        }

        $acheteurs = $this->document->getAcheteursByHash();
        $acheteursUsed = array();
        $erreurRepartition = false;
        $hasParcelle = false;
        foreach ($this->document->declaration->getProduitsWithLieuEditable() as $hash => $produit) {
            $lieu_key = $produit->getLieuKeyFromHash($hash);
            if(!$produit->isAffectee($lieu_key)) {
                continue;
            }
            $hasParcelle = true;

            $acheteursParcelle = $produit->getAcheteursByHash($lieu_key);

            if(!count($acheteursParcelle)) {
                $this->addPoint(self::TYPE_ERROR, 'acheteur_repartition', 'terminer la répartition des acheteurs', $this->generateUrl('parcellaire_acheteurs', array('id' => $this->document->_id)));
                $erreurRepartition = true;
                break;
            }

            foreach($acheteursParcelle as $hash => $acheteurParcelle) {
                $acheteursUsed[$hash] = $acheteurParcelle;
            }
        }

        if($hasParcelle && !$erreurRepartition && count($acheteurs) != count($acheteursUsed)) {
            $this->addPoint(self::TYPE_ERROR, 'acheteur_repartition', 'terminer la répartition des acheteurs', $this->generateUrl('parcellaire_acheteurs', array('id' => $this->document->_id)));
        }
    }
}
