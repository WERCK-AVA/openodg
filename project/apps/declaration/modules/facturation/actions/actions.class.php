<?php

class facturationActions extends sfActions 
{
	
    public function executeIndex(sfWebRequest $request) 
    {
    	
    	$this->values = array();
    	$this->templatesFactures = ConfigurationClient::getConfiguration('2014')->getTemplatesFactures();
    	$this->form = new FacturationForm($this->templatesFactures);
        $this->generations = GenerationClient::getInstance()->findHistoryWithType(GenerationClient::TYPE_DOCUMENT_FACTURES,10);
    	
    	if ($request->isMethod(sfWebRequest::POST)) {
    		$this->form->bind($request->getParameter($this->form->getName()));
    		
	    	if($this->form->isValid()) {

	    		$this->values = $this->form->getValues();
	       		$compte = CompteClient::getInstance()->findByIdentifiant($this->values['declarant']);
	       		$templateFacture = TemplateFactureClient::getInstance()->find($this->values['template_facture']);
	       		$generation = FactureClient::getInstance()->createFactureByCompte($templateFacture, $compte->_id);
                $generation->save();

                return $this->redirect('facturation');
	    	}
        }
    }

    public function executeLatex(sfWebRequest $request) {
        
        $this->setLayout(false);
        $this->facture = FactureClient::getInstance()->find($request->getParameter('id'));
        $this->forward404Unless($this->facture);
        $latex = new FactureLatex($this->facture);
        $latex->echoWithHTTPHeader($request->getParameter('type'));
        exit;
    }
        
    private function getLatexTmpPath() {
            return "/tmp/";
    }
}
