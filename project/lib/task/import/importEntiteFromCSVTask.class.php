<?php

class importEntitesFromCSVTask extends sfBaseTask
{

    protected $file_path = null;
    protected $chaisAttributsInImport = array();
    protected $isSuspendu = false;

    const CSV_OLDID = 0;
    const CSV_TITRE = 1;
    const CSV_NOM = 2;
    const CSV_ADRESSE_1 = 3;
    const CSV_ADRESSE_2 = 4;
    const CSV_ADRESSE_3 = 5;
    const CSV_CP = 6;
    const CSV_VILLE = 7;


    const CSV_EVV = 8;
    const CSV_SIRET = 9;

    const CSV_TELEPHONE = 10;
    const CSV_PORTABLE = 11;
    const CSV_FAX = 12;
    const CSV_EMAIL = 13;

    const CSV_ACTIVITES = 14;
    const CSV_ETAT = 15;
    const CSV_ORDRE = 16;
    const CSV_ZONE = 17;
    const CSV_ID_TIERS = 18;
    const CSV_CHAIS_TYPE = 19;
    const CSV_CHAIS_ACTIVITES = 20;


    const CSV_CHAIS_ADRESSE_1 = 21;
    const CSV_CHAIS_ADRESSE_2 = 22;
    const CSV_CHAIS_ADRESSE_3 = 23;
    const CSV_CHAIS_CP = 24;
    const CSV_CHAIS_VILLE = 25;


    const CSV_CAVE_APPORTEURID = 26;
    const CSV_CAVE_COOP = 27;


    const CSV_DATE_RECEPTION_ODG = 28;
    const CSV_DATE_ENREGISTREMENT_ODG = 29;
    const CSV_DATE_TRANSMISSION_AVPI = 30;
    const CSV_DATE_HABILITATION = 31;
    const CSV_DATE_ARCHIVAGE = 32;

    const CSV_COMMENTAIRE = 33;

    const CSV_CHAI_RESPONSABLE_NOM = 34;
    const CSV_CHAI_RESPONSABLE_TELEPHONE = 35;
    const CSV_CHAI_ARCHIVE = 36;

    const CSV_SOCIETE_TYPE = 37;



    protected function configure()
    {
        $this->addArguments(array(
            new sfCommandArgument('file_path', sfCommandArgument::REQUIRED, "Fichier csv pour l'import")
        ));

        $this->addOptions(array(
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'default'),
        ));

        $this->namespace = 'import';
        $this->name = 'entite-from-csv';
        $this->briefDescription = "Import d'une entite";
        $this->detailedDescription = <<<EOF
EOF;

        $this->convert_attributs["Vinification"] = "VINIFICATION";
        $this->convert_attributs["VV Stockage"] = "STOCKAGE_VRAC";
        $this->convert_attributs['VC Stockage'] = "STOCKAGE_VIN_CONDITIONNE";
        $this->convert_attributs['DGC'] = "DGC";
    }

    protected function execute($arguments = array(), $options = array())
    {
        // initialize the database connection
        $databaseManager = new sfDatabaseManager($this->configuration);
        $connection = $databaseManager->getDatabase($options['connection'])->getConnection();
        $this->file_path = $arguments['file_path'];

        error_reporting(E_ERROR | E_PARSE);

        $this->import();

    }

    protected function import(){

      if(!$this->file_path){
        throw new  sfException("Le paramètre du fichier csv doit être renseigné");

      }
      error_reporting(E_ERROR | E_PARSE);
      $this->chaisAttributsInImport = EtablissementClient::$chaisAttributsInImport;

      foreach(file($this->file_path) as $line) {
          $line = str_replace("\n", "", $line);
          if(preg_match("/tbl_CDPOps/", $line)) {
              continue;
          }
          $this->importEntite($line);
        }
    }

    protected function importEntite($line){
            $data = str_getcsv($line, ';');
            if(!preg_match('/^'.SocieteClient::getInstance()->getSocieteFormatIdentifiantRegexp().'$/', $data[self::CSV_OLDID])) {
                throw new Exception("Mauvais identifiant ". $data[self::CSV_OLDID]);
            }
            $identifiant = $data[self::CSV_OLDID];

            $this->isSuspendu = ($data[self::CSV_ETAT] == "Archivé");

            $soc = SocieteClient::getInstance()->find($identifiant);
            if(!$soc){
                $soc = $this->importSociete($data,$identifiant);
                $etb = $this->importEtablissement($soc,$data,$identifiant);
                $etb = EtablissementClient::getInstance()->find($etb->_id);
                $activitesChais = explode(';',$data[self::CSV_CHAIS_ACTIVITES]);

                $chaiApport = false;
                if(count($activitesChais) > 0){
                      $this->importLiaisons($etb,$line);
                      if($data[self::CSV_CHAIS_TYPE] != "Apporteur" || !$data[self::CSV_CAVE_APPORTEURID]){
                          $this->addChaiForEtablissement($etb,$data);
                      }
                }
                $this->addResponsableDeChai($soc,$data);
                echo "\n";
            }else{
              $etb = $soc->getEtablissementPrincipal();
              echo "La société : ".$identifiant." est déjà dans la base => on va alimenter les chais  ";
              $activitesChais = explode(';',$data[self::CSV_CHAIS_ACTIVITES]);

              if(count($activitesChais) > 0){
                    $this->importLiaisons($etb,$line);
                    if($data[self::CSV_CHAIS_TYPE] != "Apporteur" || !$data[self::CSV_CAVE_APPORTEURID]){
                        $this->addChaiForEtablissement($etb,$data);
                    }
              }
              $this->addResponsableDeChai($soc,$data);

              echo "\n";
            }
      }

    protected function importSociete($data,$identifiant){
            $societe = new societe();
            $societe->identifiant = $identifiant;
            $cvi = $data[self::CSV_EVV];
            $societe->type_societe = SocieteClient::TYPE_OPERATEUR ;

            $societe->constructId();
            $societe->raison_sociale = $this->buildRaisonSociete($data);
            $societe->add('date_creation', date("Y-m-d"));

            $societe->code_comptable_client = ($data[self::CSV_ID_TIERS]) ? $data[self::CSV_ID_TIERS] : $societe->identifiant;
            $siege = $societe->getOrAdd('siege');

            $societe->siret = ($data[self::CSV_SIRET])? $data[self::CSV_SIRET] : null;

            $societe->siege->adresse = $data[self::CSV_ADRESSE_1];
            $societe->siege->adresse_complementaire = $data[self::CSV_ADRESSE_2];

            if($data[self::CSV_ADRESSE_3]){
              $societe->siege->adresse_complementaire .= " − ".$data[self::CSV_ADRESSE_3];
            }

            $societe->siege->code_postal = $data[self::CSV_CP];
            $societe->siege->commune = $data[self::CSV_VILLE];
            if($data[self::CSV_CP]){
              $societe->siege->pays = "France";
            }else{
              $societe->siege->pays = ($data[self::CSV_ADRESSE_3])? $data[self::CSV_ADRESSE_3] : 'Autre Pays';
            }


            $societe->telephone_bureau = $this->formatTel($data[self::CSV_TELEPHONE]);
            $societe->telephone_mobile = $this->formatTel($data[self::CSV_PORTABLE]);
            $societe->fax = $this->formatTel($data[self::CSV_FAX]);
            //$emails = (explode(";",$data[self::CSV_EMAIL]));

            $societe->email = str_replace("'","",trim($data[self::CSV_EMAIL]));

            if($this->isSuspendu){
              $societe->setStatut(SocieteClient::STATUT_SUSPENDU);
            }else{
              $societe->setStatut(SocieteClient::STATUT_ACTIF);
            }
            $societe->save();
            $societe = SocieteClient::getInstance()->find($societe->_id);
            return $societe;
          }

    protected function importEtablissement($societe,$data,$identifiant){
          $type_etablissement = EtablissementFamilles::FAMILLE_PRODUCTEUR;

          if($data[self::CSV_ORDRE]){
              if(($data[self::CSV_ORDRE] == "N83") || ($data[self::CSV_ORDRE] == "N13")){
                  $type_etablissement = EtablissementFamilles::FAMILLE_NEGOCIANT_VINIFICATEUR;
              }
              if(($data[self::CSV_ORDRE] == "CC 83") || ($data[self::CSV_ORDRE] == "CC 13")){
                  $type_etablissement = EtablissementFamilles::FAMILLE_COOPERATIVE;
              }
              if(($data[self::CSV_ORDRE] == "CP 83") || ($data[self::CSV_ORDRE] == "CP 13")){
                  $type_etablissement = EtablissementFamilles::FAMILLE_PRODUCTEUR_VINIFICATEUR;
              }
          }
          if($data[self::CSV_SOCIETE_TYPE] == "NEGOCIANT"){
              $type_etablissement = EtablissementFamilles::FAMILLE_NEGOCIANT;
          }

          $cvi = $data[self::CSV_EVV];
          $etablissement = $societe->createEtablissement($type_etablissement);
          $etablissement->constructId();
          $etablissement->cvi = $cvi;
          $etablissement->nom = $this->buildRaisonSociete($data);

          $departement = null;
          if(preg_match("/([0-9]{2})$/", $data[self::CSV_ORDRE], $matches)) {
              $departement = $matches[1];
          } else {
              $departement = substr($data[self::CSV_CP], 0, 2);
          }
          if($data[self::CSV_ZONE]){
              $etablissement->region = str_replace(" ", "_", $data[self::CSV_ZONE])."_".$departement;
          }

          if($data[self::CSV_ZONE] && !array_key_exists($etablissement->region, EtablissementClient::getRegions())) {
              echo $etablissement->identifiant . " : La région ".$etablissement->region." n'existe pas\n";
          }

          $etablissement->save();
          if($this->isSuspendu){
            $etablissement->setStatut(SocieteClient::STATUT_SUSPENDU);
          }else{
            $etablissement->setStatut(SocieteClient::STATUT_ACTIF);
          }


          echo "L'entité $identifiant CVI (".$cvi.")  etablissement =>  $etablissement->_id  ";
          echo ($this->isSuspendu)? " SUSPENDU   " : " ACTIF ";
          if(trim($data[self::CSV_COMMENTAIRE])){
              $etablissement->setCommentaire(str_replace("#","\n",$data[self::CSV_COMMENTAIRE]));
          }
          $etablissement->save();

          return $etablissement;

        }

        protected function addChaiForEtablissement($etb,$data){
          $newChai = $etb->getOrAdd('chais')->add();
          $newChai->nom = $data[self::CSV_CHAIS_VILLE];
          $newChai->adresse = $this->getChaiAdresseConcat($data);

          $newChai->commune = $data[self::CSV_CHAIS_VILLE];
          $newChai->code_postal = $data[self::CSV_CHAIS_CP];
          $newChai->archive = $data[self::CSV_CHAI_ARCHIVE];
          $activites = explode(';',$data[self::CSV_CHAIS_ACTIVITES]);
          foreach ($activites as $activite) {
            if(!array_key_exists(trim($activite),$this->chaisAttributsInImport)){
              var_dump($activite); exit;
            }
            $activiteKey = $this->chaisAttributsInImport[trim($activite)];
            $newChai->getOrAdd('attributs')->add($activiteKey,EtablissementClient::$chaisAttributsLibelles[$activiteKey]);
          }
          echo " LE CHAI ".$newChai->nom." ".$newChai->adresse."...  a été crée   ";
          $etb->save();
          return $etb;
        }

    protected function buildRaisonSociete($data){
      $civilites = array("MR","MME", "MM", "M");
      if(in_array($data[self::CSV_TITRE],$civilites)){
        return trim($data[self::CSV_NOM].' ('.$data[self::CSV_TITRE].')');
      }
      return trim($data[self::CSV_TITRE].' '.$data[self::CSV_NOM]);
    }

    protected function importLiaisons($viti,$line){
        $data = str_getcsv($line, ';');
        if($data[self::CSV_CAVE_APPORTEURID]){
            $coopOrNego = EtablissementClient::getInstance()->findByIdentifiant($data[self::CSV_CAVE_APPORTEURID]."01");
            if(!$viti){
                echo "\n/!\ viti non trouvé : ".$data[self::CSV_OLDID]."\n";
                return false;
            }
            if(!$coopOrNego){
                echo "\n/!\ cave coop ou négo non trouvé : ".$data[self::CSV_CAVE_APPORTEURID]."\n";
                return false;
            }
            if($coopOrNego->_id == $viti->_id){
                echo "\n/!\ Liaison sur lui même trouvée : ".$data[self::CSV_CAVE_APPORTEURID]."\n";
                return false;
            }

            if($data[self::CSV_CHAIS_TYPE] == "Apporteur"){
                $attributs_chai = array(EtablissementClient::CHAI_ATTRIBUT_APPORT);
                if($coopOrNego->isCooperative()){
                    $chaiAssocie = $this->getChaiAssocie($data,$coopOrNego);
                    $viti->addLiaison(EtablissementClient::TYPE_LIAISON_COOPERATIVE,$coopOrNego,true,$chaiAssocie,$attributs_chai);
                }elseif($coopOrNego->isNegociant()) {
                    $chaiAssocie = $this->getChaiAssocie($data,$coopOrNego);
                    $viti->addLiaison(EtablissementClient::TYPE_LIAISON_NEGOCIANT,$coopOrNego,true,$chaiAssocie,$attributs_chai);
                }elseif($coopOrNego->isNegociantVinificateur()) {
                    $chaiAssocie = $this->getChaiAssocie($data,$coopOrNego);
                    $viti->addLiaison(EtablissementClient::TYPE_LIAISON_NEGOCIANT_VINIFICATEUR,$coopOrNego,true,$chaiAssocie,$attributs_chai);
                }
            }else{
                $chaiAssocie = $this->getChaiAssocie($data,$coopOrNego);
                $attributs_chai = $this->convertAttributsChais(explode(';',$data[self::CSV_CHAIS_ACTIVITES]));
                $viti->addLiaison(EtablissementClient::TYPE_LIAISON_HEBERGE_TIERS,$coopOrNego,true,$chaiAssocie,$attributs_chai);
            }

            $viti->save();
            $type = ($coopOrNego->isNegociant())? ' (négociant) ' : ' (coopérative)';
            echo " LA LIAISON ".$viti->_id." ".$coopOrNego->_id." ".$type.' a été créée   ';

        }
    }

    protected function getChaiAssocie($data,$coopOrNego){
        if(!$coopOrNego->exist('chais') || !$coopOrNego->chais){
            return null;
        }
        $chais = $coopOrNego->getChais();
        if(count($chais) == 1){
            foreach ($chais as $chai) {
                return $chai;
            }
        }
        foreach ($coopOrNego->getChais() as $key => $chai) {
            $adresse = str_replace('BOULEVARD','BD',$data[self::CSV_CHAIS_ADRESSE_2]);
            if($data[self::CSV_CHAIS_ADRESSE_3]){
                $adresse .= " - ".str_replace('BOULEVARD','BD',$data[self::CSV_CHAIS_ADRESSE_3]);
            }
            if($adresse == str_replace('BOULEVARD','BD',$chai->adresse)
                && ($data[self::CSV_CHAIS_VILLE] == $chai->commune)
                && ($data[self::CSV_CHAIS_CP] == $chai->code_postal)){
                    return $chai;
            }
        }
        echo "\n/!\ ".$data[self::CSV_OLDID]." : on ne trouve pas le chai ".$data[self::CSV_CHAIS_ADRESSE_1] ." ".$data[self::CSV_CHAIS_ADRESSE_2]." ".$data[self::CSV_CHAIS_ADRESSE_3]." ".$data[self::CSV_CHAIS_VILLE]." ".$data[self::CSV_CHAIS_CP]. " dans ".$coopOrNego->_id."\n";

        return null;
    }

    protected function formatTel($tel){
        if(!$tel){
            return null;
        }
        $t = str_replace(array(' ','.'),array('',''),$tel);
        $tk = sprintf("%010d",$t);
        return substr($tk, 0,2)." ".substr($tk,2,2)." ".substr($tk,4,2)." ".substr($tk,6,2)." ".substr($tk,8,2);
    }

    protected function convertAttributsChais($chaisAttributsCsv){
        $attributsConverted = array();
        foreach ($chaisAttributsCsv as $chaiCsv) {
            $attributsConverted[] = $this->convert_attributs[trim($chaiCsv)];
        }
        return $attributsConverted;
    }


    protected function getChaiAdresseConcat($data){
        $adresse = $data[self::CSV_CHAIS_ADRESSE_1];
        if($data[self::CSV_CHAIS_ADRESSE_2]) $adresse .=' - '.$data[self::CSV_CHAIS_ADRESSE_2];
        if($data[self::CSV_CHAIS_ADRESSE_3]) $adresse .=' - '.$data[self::CSV_CHAIS_ADRESSE_3];
        return $adresse;
    }


    protected function addResponsableDeChai($societe,$data){
        if(!$data[self::CSV_CHAI_RESPONSABLE_NOM] && !$data[self::CSV_CHAI_RESPONSABLE_TELEPHONE]){
            return;
        }
        $nom = trim($data[self::CSV_CHAI_RESPONSABLE_NOM]);
        $telephone = trim($this->formatTel($data[self::CSV_CHAI_RESPONSABLE_TELEPHONE]));

        if(!$nom){
            $nom = "AUTRE CONTACT";
        }

        $contact = CompteClient::getInstance()->createCompteInterlocuteurFromSociete($societe);
        $contact->nom = $nom;
        $contact->fonction = "Responsable de chais";
        $contact->adresse = $data[self::CSV_CHAIS_ADRESSE_1];

        $adresse_complementaire = "";
        if($data[self::CSV_CHAIS_ADRESSE_2]) $adresse_complementaire .= $data[self::CSV_CHAIS_ADRESSE_2];
        if($data[self::CSV_CHAIS_ADRESSE_3]) $adresse_complementaire .=' - '.$data[self::CSV_CHAIS_ADRESSE_3];

        $contact->adresse_complementaire = $adresse_complementaire;
        $contact->code_postal = $data[self::CSV_CHAIS_CP];
        $contact->commune = $data[self::CSV_CHAIS_VILLE];

        if(preg_match('/^(06|07)/',$telephone)){
            $contact->telephone_mobile = $telephone;
        }else{
            $contact->telephone_bureau = $telephone;
        }
            echo " (+INTERLOCUTEUR $contact->_id ".$contact->nom." (".$contact->telephone_bureau."/".$contact->telephone_mobile.") ";


        $contact->save();
    }

}
