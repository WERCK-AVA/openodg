<?php

class parcellaireCremantComponents extends sfComponents {

    public function executeMonEspace(sfWebRequest $request) {
        $this->parcellaireCremant = ParcellaireAffectationClient::getInstance()->find('PARCELLAIRECREMANT-' . $this->etablissement->cvi . '-' . $this->campagne);
    }

}
