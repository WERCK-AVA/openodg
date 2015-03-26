<?php

class degustationActions extends sfActions {

    public function executeIndex(sfWebRequest $request) {
        $this->tournee = new Tournee();
        $this->form = new TourneeCreationForm($this->tournee);

        if (!$request->isMethod(sfWebRequest::POST)) {

            return sfView::SUCCESS;
        }

        $this->form->bind($request->getParameter($this->form->getName()));

        if (!$this->form->isValid()) {

            return sfView::SUCCESS;
        }

        $this->form->save();

        return $this->redirect('degustation_creation', $this->tournee);
    }

    public function executeEdit(sfWebRequest $request) {
        $degustation = $this->getRoute()->getTournee();

        if ($degustation->exist('etape') && $degustation->etape) {

            return $this->redirect('degustation_' . strtolower($degustation->etape), $degustation);
        }

        return $this->redirect('degustation_creation', $degustation);
    }

    public function executeCreation(sfWebRequest $request) {
        $this->tournee = $this->getRoute()->getTournee();

        if ($this->tournee->storeEtape($this->getEtape($this->tournee, TourneeEtapes::ETAPE_CREATION))) {
            $this->tournee->save();
        }

        $this->operateurs = TourneeClient::getInstance()->getPrelevements($this->tournee->date_prelevement_debut, $this->tournee->date_prelevement_fin);


        $this->nb_reports = $this->tournee->getPrevious() ? count($this->tournee->getPrevious()->getOperateursReporte()) : 0;

        $this->form = new TourneeCreationFinForm($this->tournee);

        if (!$request->isMethod(sfWebRequest::POST)) {

            return sfView::SUCCESS;
        }

        $this->form->bind($request->getParameter($this->form->getName()));

        if (!$this->form->isValid()) {

            return sfView::SUCCESS;
        }

        $this->form->save();

        $nb_a_prelever = $this->form->getValue('nombre_operateurs_a_prelever') + $this->nb_reports;

        return $this->redirect('degustation_operateurs', array('sf_subject' => $this->tournee, 'nb_a_prelever' => $nb_a_prelever));
    }

    public function executeOperateurs(sfWebRequest $request) {
        $this->tournee = $this->getRoute()->getTournee();

        if ($this->tournee->storeEtape($this->getEtape($this->tournee, TourneeEtapes::ETAPE_OPERATEURS))) {
            $this->tournee->save();
        }

        $this->tournee->updateOperateursFromPrevious();
        $this->tournee->updateOperateursFromDRev();

        $this->form = new TourneeOperateursForm($this->tournee);

        $this->nb_a_prelever = $request->getParameter('nb_a_prelever', 0);

        if (!$request->isMethod(sfWebRequest::POST)) {

            return sfView::SUCCESS;
        }

        $this->form->bind($request->getParameter($this->form->getName()));

        if(!$this->form->isValid()) {

            return sfView::SUCCESS;
        }

        $this->form->update();
        
        $this->tournee->save();

        if ($request->isXmlHttpRequest()) {

            return $this->renderText(json_encode(array("success" => true, "document" => array("id" => $this->tournee->_id, "revision" => $this->tournee->_rev))));
        }

        return $this->redirect('degustation_degustateurs', $this->tournee);
    }

    public function executeDegustateurs(sfWebRequest $request) {

        return $this->redirect('degustation_degustateurs_type', array('sf_subject' => $this->getRoute()->getTournee(), 'type' => CompteClient::ATTRIBUT_DEGUSTATEUR_PORTEUR_MEMOIRES));
    }

    public function executeDegustateursType(sfWebRequest $request) {
        $this->tournee = $this->getRoute()->getTournee();

        if ($this->tournee->storeEtape($this->getEtape($this->tournee, TourneeEtapes::ETAPE_DEGUSTATEURS))) {
            $this->tournee->save();
        }

        $this->types = CompteClient::getInstance()->getAttributsForType(CompteClient::TYPE_COMPTE_DEGUSTATEUR);

        $this->type = $request->getParameter('type', null);

        if (!array_key_exists($this->type, $this->types)) {

            return $this->forward404(sprintf("Le type de dégustateur \"%s\" est introuvable", $request->getParameter('type', null)));
        }

        $this->noeud = $this->tournee->degustateurs->add($this->type);

        $this->degustateurs = TourneeClient::getInstance()->getDegustateurs($this->type, "-declaration-certification-genre-appellation_ALSACE");

        if (!$request->isMethod(sfWebRequest::POST)) {

            return sfView::SUCCESS;
        }

        $values = $request->getParameter("degustateurs", array());

        foreach ($values as $key => $value) {
            $d = $this->degustateurs[$key];
            $degustateur = $this->noeud->add($d->_id);
            $degustateur->nom = $d->nom_a_afficher;
            $degustateur->email = $d->email;
            $degustateur->adresse = $d->adresse;
            $degustateur->commune = $d->commune;
            $degustateur->code_postal = $d->code_postal;
        }

        $degustateurs_to_delete = array();

        foreach($this->noeud as $degustateur) {
            if(array_key_exists($degustateur->getKey(), $values)) {
               continue; 
            }

            $degustateurs_to_delete[] = $degustateur->getKey();
        }

        foreach($degustateurs_to_delete as $degustateur_key) {
            $this->noeud->remove($degustateur_key);
        }

        $this->tournee->save();

        if ($request->isXmlHttpRequest()) {

            return $this->renderText(json_encode(array("success" => true, "document" => array("id" => $this->tournee->_id, "revision" => $this->tournee->_rev))));
        }

        return $this->redirect('degustation_degustateurs_type_suivant', array('sf_subject' => $this->tournee, 'type' => $this->type));
    }

    public function executeDegustateursTypePrecedent(sfWebRequest $request) {
        $prev_key = null;
        foreach (CompteClient::getInstance()->getAttributsForType(CompteClient::TYPE_COMPTE_DEGUSTATEUR) as $type_key => $type_libelle) {
            if ($type_key != $request->getParameter('type', null)) {
                $prev_key = $type_key;
                continue;
            }
            if (!$prev_key) {
                continue;
            }

            return $this->redirect('degustation_degustateurs_type', array('sf_subject' => $this->getRoute()->getTournee(), 'type' => $prev_key));
        }

        return $this->redirect('degustation_operateurs', $this->getRoute()->getTournee());
    }

    public function executeDegustateursTypeSuivant(sfWebRequest $request) {
        $find = false;
        foreach (CompteClient::getInstance()->getAttributsForType(CompteClient::TYPE_COMPTE_DEGUSTATEUR) as $type_key => $type_libelle) {
            if (!$find && $type_key != $request->getParameter('type', null)) {
                continue;
            }
            if ($type_key == $request->getParameter('type', null)) {
                $find = true;
                continue;
            }

            return $this->redirect('degustation_degustateurs_type', array('sf_subject' => $this->getRoute()->getTournee(), 'type' => $type_key));
        }

        return $this->redirect('degustation_agents', $this->getRoute()->getTournee());
    }

    public function executeAgents(sfWebRequest $request) {
        $this->tournee = $this->getRoute()->getTournee();

        if ($this->tournee->storeEtape($this->getEtape($this->tournee, TourneeEtapes::ETAPE_AGENTS))) {
            $this->tournee->save();
        }

        $this->agents = TourneeClient::getInstance()->getAgents();

        $this->jours = array();
        $date = new DateTime($this->tournee->date);
        $date->modify('-7 days');

        for ($i = 1; $i <= 7; $i++) {
            $this->jours[] = $date->format('Y-m-d');
            $date->modify('+ 1 day');
        }

        if (!$request->isMethod(sfWebRequest::POST)) {

            return sfView::SUCCESS;
        }

        $values = $request->getParameter("agents", array());

        foreach ($values as $key => $value) {
            $agent = $this->tournee->agents->add($key);
            $a = $this->agents[$key];
            $agent->nom = sprintf("%s %s.", $a->prenom, substr($a->nom, 0, 1));
            $agent->email = $a->email;
            $agent->adresse = $a->adresse;
            $agent->commune = $a->commune;
            $agent->code_postal = $a->code_postal;
            $agent->lat = $a->lat;
            $agent->lon = $a->lon;
            $agent->dates = $value;
        }

        $this->tournee->save();

        if ($request->isXmlHttpRequest()) {

            return $this->renderText(json_encode(array("success" => true, "document" => array("id" => $this->tournee->_id, "revision" => $this->tournee->_rev))));
        }

        return $this->redirect('degustation_prelevements', $this->tournee);
    }

    public function executePrelevements(sfWebRequest $request) {
        $this->tournee = $this->getRoute()->getTournee();

        if ($this->tournee->storeEtape($this->getEtape($this->tournee, TourneeEtapes::ETAPE_PRELEVEMENTS))) {
            $this->tournee->save();
        }

        $result = $this->organisation($request);

        if($result !== true) {

            return $result;
        }

        return $this->redirect('degustation_validation', $this->tournee);
    }

    public function executeOrganisation(sfWebRequest $request) {
        $this->tournee = $this->getRoute()->getTournee();

        $result = $this->organisation($request);

        if($result !== true) {

            return $result;
        }

        return $this->redirect('degustation_visualisation', $this->tournee);
    }

    protected function organisation(sfWebRequest $request) {
        $this->couleurs = array("#91204d", "#fa6900", "#1693a5", "#e05d6f", "#7ab317", "#ffba06", "#907860");
        $this->heures = array();
        for ($i = 8; $i <= 18; $i++) {
            $this->heures[sprintf("%02d:00", $i)] = sprintf("%02d", $i);
        }
        $this->heures["24:00"] = "24";
        $this->operateurs = $this->tournee->getOperateursOrderByHour();
        $this->agents_couleur = array();
        $i = 0;
        foreach ($this->tournee->agents as $agent) {
            foreach($agent->dates as $date) {
                $this->agents_couleur[$agent->getKey().$date] = $this->couleurs[$i];
                $i++;
            }
        }

        if (!$request->isMethod(sfWebRequest::POST)) {

            return sfView::SUCCESS;
        }

        $values = $request->getParameter("operateurs", array());
        $i = 0;
        foreach ($values as $key => $value) {
            $operateur = $this->tournee->operateurs->get($key);
            if(!str_replace("-", "", $value["tournee"])) {
                $operateur->agent = null;
                $operateur->date = null;
            } else {
                $operateur->agent = preg_replace("/(COMPTE-[A-Z0-9]+)-([0-9]+-[0-9]+-[0-9]+)/", '\1', $value["tournee"]);
                $operateur->date = preg_replace("/(COMPTE-[A-Z0-9]+)-([0-9]+-[0-9]+-[0-9]+)/", '\2', $value["tournee"]);
            }
            $operateur->heure = $value["heure"];
            $operateur->position = $i++;
        }

        $this->tournee->save();

        if ($request->isXmlHttpRequest()) {

            return $this->renderText(json_encode(array("success" => true, "document" => array("id" => $this->tournee->_id, "revision" => $this->tournee->_rev))));
        }

        return true;
    }

    public function executeValidation(sfWebRequest $request) {
        $this->tournee = $this->getRoute()->getTournee();

        if ($this->tournee->storeEtape($this->getEtape($this->tournee, TourneeEtapes::ETAPE_VALIDATION))) {
            $this->tournee->save();
        }

        $this->tournee->cleanOperateurs();

        if (!$request->isMethod(sfWebRequest::POST)) {
            $this->validation = new TourneeValidation($this->tournee);
        }

        $this->form = new TourneeValidationForm($this->tournee);
        
        if ($request->isMethod(sfWebRequest::POST)) {
            $this->form->bind($request->getParameter($this->form->getName()));
            if ($this->form->isValid()) {              
                $this->form->save();

                Email::getInstance()->sendDegustationOperateursMails($this->tournee);
                Email::getInstance()->sendDegustationDegustateursMails($this->tournee);

                $this->getUser()->setFlash("notice", "Les emails d'invitations et d'avis de passage ont bien été envoyés");

                return $this->redirect('degustation_visualisation', $this->tournee);
            }
        }
    }

    public function executeVisualisation(sfWebRequest $request) {
        $this->tournee = $this->getRoute()->getTournee();
    }

    public function executeTourneesGenerate(sfWebRequest $request) {
        $this->tournee = $this->getRoute()->getTournee();
        if($this->tournee->generatePrelevements()) {
            $this->tournee->save();
        }
        
        return $this->redirect('degustation_visualisation', $this->tournee);
    }

    public function executeTournee(sfWebRequest $request) {
        $this->tournee = $this->getRoute()->getTournee();
        $this->agent = $this->tournee->agents->get($request->getParameter('agent'));
        $this->date = $request->getParameter('date');
        $this->operateurs = $this->tournee->getTourneeOperateurs($request->getParameter('agent'), $request->getParameter('date'));
        $this->reload = $request->getParameter('reload', 0);
        $this->produits = array();
        foreach($this->tournee->getProduits() as $produit) {
            $this->produits[$produit->getHash()] = $produit->getLibelleLong();
        }
        $this->setLayout('layoutResponsive');
    }

    public function executeTourneeJson(sfWebRequest $request) {
        $json = array();

        $this->tournee = $this->getRoute()->getTournee();
        $this->operateurs = $this->tournee->getTourneeOperateurs($request->getParameter('agent'), $request->getParameter('date'));

        foreach($this->operateurs as $operateur) {
            $degustation = $operateur->getDegustationObject();
            $json[$degustation->_id] = $degustation->toJson();
        }

        if(!$request->isMethod(sfWebRequest::POST)) {
            $this->response->setContentType('application/json');

            return $this->renderText(json_encode($json));
        }

        $json = json_decode($request->getContent());

        $json_return = array();

        foreach($json as $key => $json_degustation) {
            if(!$this->tournee->operateurs->exist($json_degustation->cvi)) {
                continue;
            }

            $degustation = $this->tournee->operateurs->get($json_degustation->cvi)->getDegustationObject();

            if($degustation->_rev != $json_degustation->_rev) {
                $json_return[$degustation->_id] = false;
                continue;
            }

            foreach($json_degustation->prelevements as $prelevement_key => $prelevement) {
                if($degustation->prelevements->exist($prelevement_key)) {
                    $p = $degustation->prelevements->get($prelevement_key);
                } else {
                    $p = $degustation->prelevements->add();
                }
                $p->cuve = $prelevement->cuve;  
                $p->anonymat_prelevement = $prelevement->anonymat_prelevement;                
                $p->hash_produit = $prelevement->hash_produit;                
                $p->libelle = $prelevement->libelle;                
                $p->preleve = $prelevement->preleve;
            }

            $degustation->save();

            $json_return[$degustation->_id] = $degustation->_rev;
        }

        $this->response->setContentType('application/json');

        return $this->renderText(json_encode($json_return));
    }

    public function executeAffectationGenerate(sfWebRequest $request) {
        $this->tournee = $this->getRoute()->getTournee();
        $this->tournee->cleanPrelevements();
        $this->tournee->generateNumeroDegustation();
        $this->tournee->save();

        return $this->redirect('degustation_visualisation', $this->tournee);
    }

    public function executeAffectation(sfWebRequest $request) {
        $this->tournee = $this->getRoute()->getTournee();
        $this->setLayout('layoutResponsive');
    }

    public function executeAffectationJson(sfWebRequest $request) {
        $this->tournee = $this->getRoute()->getTournee();

        $this->prelevements = $this->tournee->getPrelevementsByNumeroPrelevement();
        $json = new stdClass();

        for($i=1; $i<=$this->tournee->nombre_commissions; $i++) {
            $json->commissions[]=$i;
        }

        $json->prelevements = array();

        foreach($this->prelevements as $key => $prelevement) {
            $p = $json->prelevements[] = new stdClass();
            $p->hash_produit = $prelevement->hash_produit;
            $p->libelle = $prelevement->libelle;
            $p->anonymat_degustation= $prelevement->anonymat_degustation;
            $p->anonymat_prelevement = $prelevement->anonymat_prelevement;
            $p->cuve = $prelevement->cuve;
            $p->commission = $prelevement->commission;
        }


        if(!$request->isMethod(sfWebRequest::POST)) {
            $this->response->setContentType('application/json');

            return $this->renderText(json_encode($json));
        }

        $json = json_decode($request->getContent());

        foreach($json->prelevements as $prelevement) {
            if(!isset($this->prelevements[$prelevement->anonymat_prelevement])) {
                continue;
            }

            $p = $this->prelevements[$prelevement->anonymat_prelevement];
            $p->commission = $prelevement->commission;
        }

        $this->tournee->save();

        $this->response->setContentType('application/json');

        return $this->renderText(json_encode(array("success" => true)));
    }

    public function executeDegustation(sfWebRequest $request) {
        $this->tournee = $this->getRoute()->getTournee();
        $this->commission = $request->getParameter('commission');
        $this->setLayout('layoutResponsive');
    }

    public function executeDegustationJson(sfWebRequest $request) {
        $this->tournee = $this->getRoute()->getTournee();
        $this->commission = $request->getParameter('commission');

        $json = new stdClass();
        $json->commission = $this->commission;
        $json->prelevements = array();
        $json->notes = TourneeClient::$note_type_libelles;

        $prelevements = $this->tournee->getPrelevementsByNumeroDegustation($this->commission);

        foreach($prelevements as $prelevement) {
            $p = $json->prelevements[] = new stdClass();
            $p->anonymat_degustation = $prelevement->anonymat_degustation;
            $p->hash_produit = $prelevement->hash_produit;
            $p->libelle = $prelevement->libelle;
            $p->notes = $prelevement->notes->toArray(true, false);
            $p->appreciations = $prelevement->appreciations;
        }

        if(!$request->isMethod(sfWebRequest::POST)) {
            $this->response->setContentType('application/json');

            return $this->renderText(json_encode($json));
        }

        $json = json_decode($request->getContent());

        foreach($json->prelevements as $p) {
            $prelevement = $prelevements[$p->anonymat_degustation];
            $prelevement->notes = array();
            foreach($p->notes as $key_note => $note) {
                $n = $prelevement->notes->add($key_note);
                $n->note = $note->note;
                $n->defauts = $note->defauts;
            }
            $prelevement->appreciations = $p->appreciations;
        }

        $this->tournee->save();

        $this->response->setContentType('application/json');

        return $this->renderText(json_encode(array("success" => true)));
    }

    protected function getEtape($doc, $etape) {
        $etapes = TourneeEtapes::getInstance();
        if (!$doc->exist('etape')) {
            return $etape;
        }
        return ($etapes->isLt($doc->etape, $etape)) ? $etape : $doc->etape;
    }

}
