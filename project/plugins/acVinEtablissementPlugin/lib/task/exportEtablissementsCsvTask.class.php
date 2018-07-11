<?php

class exportEtablissementsCsvTask extends sfBaseTask
{

    protected function configure()
    {
        $this->addArguments(array(
        ));

        $this->addOptions(array(
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'default'),
        ));

        $this->namespace = 'export';
        $this->name = 'etablissements-csv';
        $this->briefDescription = "Export csv des établissements";
        $this->detailedDescription = <<<EOF
EOF;
    }

    protected function execute($arguments = array(), $options = array())
    {
        $databaseManager = new sfDatabaseManager($this->configuration);
        $connection = $databaseManager->getDatabase($options['connection'])->getConnection();

        $results = EtablissementClient::getInstance()->findAll();

        echo "IdOp,IdTitre,Raison sociale,Adresse,Adresse 2,Adresse 3,Code postal,Commune,CVI,SIRET,Téléphone bureau,Fax,Téléphone mobile,Email,Activité,Réception ODG,Enresgistrement ODG,Transmission AVPI,Date Habilitation,Date Archivage,Observation,Etat,IR,Ordre,Zone,Code comptable,Famille,Date de dernière modification,Statut\n";

        foreach($results->rows as $row) {
            $etablissement = EtablissementClient::getInstance()->find($row->id, acCouchdbClient::HYDRATE_JSON);
            $societe = SocieteClient::getInstance()->find($etablissement->id_societe, acCouchdbClient::HYDRATE_JSON);
            $compte = CompteClient::getInstance()->find($etablissement->compte, acCouchdbClient::HYDRATE_JSON);
            $habilitation = HabilitationClient::getInstance()->getLastHabilitation($etablissement->identifiant, acCouchdbClient::HYDRATE_JSON);

            $habilitationStatut = null;
            $activites = array();
            if(isset($habilitation)) {
                foreach($habilitation->declaration as $produit) {
                    foreach($produit->activites as $activiteKey => $activite) {
                        if(!$activite->statut) {
                            continue;
                        }
                        $activites[$activiteKey] = $activiteKey;
                        $habilitationStatut = HabilitationClient::getInstance()->getLibelleStatut($activite->statut);
                    }
                }
            }

            $activitesSorted = array();
            foreach(HabilitationClient::getInstance()->getActivites() as $key => $libelle) {
                if(!array_key_exists($key, $activites)) {
                    continue;
                }
                $activitesSorted[] = $libelle;
            }

            $ordre = null;

            if($etablissement->region && $etablissement->famille == EtablissementFamilles::FAMILLE_PRODUCTEUR_VINIFICATEUR) {
                $ordre = 'CP ';
            }
            if($etablissement->region && $etablissement->famille == EtablissementFamilles::FAMILLE_COOPERATIVE) {
                $ordre = 'CC ';
            }
            if($etablissement->region && $etablissement->famille == EtablissementFamilles::FAMILLE_NEGOCIANT) {
                $ordre = 'N';
            }
            if($etablissement->region) {
                $ordre .= substr($etablissement->region, -2);
            }

            $intitules = "EARL|EI|ETS|EURL|GAEC|GFA|HOIRIE|IND|M|MM|Mme|MME|MR|SA|SARL|SAS|SASU|SC|SCA|SCE|SCEA|SCEV|SCI|SCV|SFF|SICA|SNC|SPH|STE|STEF";
            $intitule = null;
            $raisonSociale = $etablissement->raison_sociale;

            if(preg_match("/^(".$intitules.") /", $raisonSociale, $matches)) {
                $intitule = $matches[1];
                $raisonSociale = preg_replace("/^".$intitule." /", "", $raisonSociale);
            }

            if(preg_match("/ \((".$intitules.")\)$/", $raisonSociale, $matches)) {
                $intitule = $matches[1];
                $raisonSociale = preg_replace("/ \((".$intitule.")\)$/", "", $raisonSociale);
            }
$adresses_complementaires = explode(' − ', str_replace(array('"',','),array('',''), $etablissement->adresse_complementaire));
$adresse_complementaire = array_shift($adresses_complementaires);
            echo
            $societe->identifiant.",".
            $intitule.",".
            $this->protectIso($raisonSociale).",".
            str_replace(array('"',','),array('',''), $etablissement->adresse).",".
            str_replace(',', '',$adresse_complementaire).",".
            implode(' − ', $adresses_complementaires).",".
            $etablissement->code_postal.",".
            $this->protectIso($etablissement->commune).",".
            $etablissement->cvi.",".
            '"'.$etablissement->siret.'",'.
            $etablissement->telephone_bureau.",".
            $etablissement->fax.",".
            $etablissement->telephone_mobile.",".
            '"'.$etablissement->email.'",'.
            preg_replace('/[0-9][0-9]_/', '', implode(";", $activitesSorted)).",". // Activité habilitation
            ','. //Reception ODG
            ','. //Enregistrement ODG
            ','. //Transmission AVPI
            ','. //Date Habilitation
            ','. //date archivage
            '"'.str_replace('"', "''", str_replace(',', ' / ', $this->protectIso($etablissement->commentaire))).'",'.
            $habilitationStatut.",". // Etat
            "Faux,", //demande AVPI
            $ordre.",". // Ordre
            str_replace("_", " ", preg_replace("/_[0-9]+$/", "", $etablissement->region)).",". // Region
            $societe->code_comptable_client.",".
            $etablissement->famille.",".
            $compte->date_modification.",".
            $etablissement->statut.",".
            "\n";
        }
    }

    public function protectIso($str){
        return str_replace(array('œ',',',"\n"),array('','',''),$str);
    }
}
