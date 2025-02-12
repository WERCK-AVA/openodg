<?php

class DouaneRecolteConvert2csvTask extends sfBaseTask
{

    protected function configure()
    {
        $this->addArguments(array(
        	new sfCommandArgument('fichier', sfCommandArgument::REQUIRED, "Chemin du fichier"),
        ));

        $this->addOptions(array(
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'declaration'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'default'),
            new sfCommandOption('header', null, sfCommandOption::PARAMETER_REQUIRED, 'Add CSV header', false),
        ));

        $this->namespace = 'douaneRecolte';
        $this->name = 'convert2csv';
        $this->briefDescription = "Convertion des documents douaniers";
        $this->detailedDescription = <<<EOF
EOF;
    }

    protected function execute($arguments = array(), $options = array())
    {
        $databaseManager = new sfDatabaseManager($this->configuration);
        $connection = $databaseManager->getDatabase($options['connection'])->getConnection();

        $file = $arguments['fichier'];

        if (!file_exists($file) || !is_file($file)) {
          return;
        }

        $csvfile = $file;
        $infos = pathinfo($file);
        $extension = (isset($infos['extension']) && $infos['extension'])? strtolower($infos['extension']): null;
        if (strtolower($extension) == 'xls') {
          $csvfile = Fichier::convertXlsFile($file);
        }elseif (strtolower($extension) != 'csv') {
          throw new sfException("extention de ".$file."non géré");
        }
        if (isset($options['header']) && $options['header']) {
            echo "type;année;id interne;cvi;raison sociale;réservé;commune;tiers;tiers id;categorie;genre;denomination;mention;lieu;couleur;cepage;inao;libelle;denomination complementaire;ligne numero;ligne libelle;ligne valeur;acheteur id;acheteur raison sociale;réservé;ville apporteur (sv)\n";
        }
        $fichier = DouaneImportCsvFile::getNewInstanceFromType(DouaneImportCsvFile::getTypeFromFile($file), $csvfile);
        $m = array();
        preg_match("/[a-zA-Z0-9]+-([0-9]{4})-.+/",$file,$m);
        if(count($m) > 1){
          $fichier->setCampagne($m[1]);
        }
        print $fichier->convert();
    }

}
