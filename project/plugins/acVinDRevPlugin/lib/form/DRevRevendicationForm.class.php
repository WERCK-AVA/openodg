<?php
class DRevRevendicationForm extends acCouchdbObjectForm
{
	public function configure()
    {
        $this->embedForm('produits', new DRevRevendicationProduitsForm($this->getObject()->declaration->getProduits(), array(), $this->getOptions()));
        //$this->validatorSchema->setPostValidator(new DRevRevendicationProduitValidator());
        $this->widgetSchema->setNameFormat('drev_produits[%s]');
    }

    protected function doUpdateObject($values)
    {
        parent::doUpdateObject($values);
        foreach ($this->getEmbeddedForms() as $key => $embedForm) {
        	$embedForm->doUpdateObject($values[$key]);
        }

        $this->getObject()->getDocument()->updatePrelevementsFromRevendication();
    }

}
