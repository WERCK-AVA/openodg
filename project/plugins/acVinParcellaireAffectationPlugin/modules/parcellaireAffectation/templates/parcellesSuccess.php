<?php use_helper('Float'); ?>
<?php include_partial('parcellaire/breadcrumb', array('parcellaire' => $parcellaire )); ?>
<?php include_partial('step', array('step' => 'parcelles', 'parcellaire' => $parcellaire));
$isVtSgn = is_string($appellationNode) && ($appellationNode == ParcellaireAffectationClient::APPELLATION_VTSGN);
?>

<?php if ($recapParcellaire): ?>
	<div class="page-header"><h2>Rappel de votre parcellaire crémant <?php echo $recapParcellaire->campagne; ?></h2></div>
	<?php include_partial('parcellaire/recap', array('parcellaire' => $recapParcellaire)); ?>
<?php endif; ?>

<div class="page-header">
    <h2>Saisie des <?php if ($parcellaire->isIntentionCremant()): ?>intentions de production<?php else: ?>parcelles<?php endif; ?><?php echo ($parcellaire->isParcellaireCremant()) ? ' de Crémant' : ''; ?></h2>
</div>

<?php if(count($parcellaireAppellations)): ?>
<ul class="nav nav-tabs">
    <?php
    $selectedAppellationName = "";
    foreach ($parcellaireAppellations as $appellationKey => $appellationName) :
        $isSelectedAppellation = ($appellation == $appellationKey);
        if (!$selectedAppellationName && $isSelectedAppellation) {
            $selectedAppellationName = $appellationName;
        }
        ?>
        <li role="presentation" class="<?php echo ($isSelectedAppellation) ? 'active' : '' ?>"><a href="<?php echo url_for('parcellaire_affectation_parcelles', array('id' => $parcellaire->_id, 'appellation' => $appellationKey)) ?>" class="ajax"><?php echo $appellationName; ?></a></li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>

<?php if ($sf_user->hasFlash('warning')): ?>
    <div class="alert alert-warning" role="alert"><?php echo $sf_user->getFlash('warning') ?></div>
<?php endif; ?>

<form action="<?php echo url_for('parcellaire_affectation_parcelles', array('id' => $parcellaire->_id, 'appellation' => $appellation)); ?>" method="post" class="form-horizontal ajaxForm parcellaireForm">
    <?php echo $form->renderHiddenFields(); ?>
    <?php echo $form->renderGlobalErrors(); ?>

    <div class="row">
        <?php if ($appellation == ParcellaireAffectationClient::APPELLATION_VTSGN): ?>
            <div class="col-xs-12">
                <p><strong>&nbsp;Pour affecter une parcelle en mention VT ou SGN, cliquez sur la ligne.</strong></p>
            </div>
        <?php endif; ?>
        <div class="col-xs-12">
            <div id="listes_cepages">
                <?php if (count($parcelles)) : ?>
                    <table class="table table-bordered table-condensed table-striped">
                        <thead>
                            <tr>
                                <th class="col-xs-1">Affectée</th>
                                <th class="col-xs-3">Produit</th>
                                <th class="col-xs-2">Commune</th>
                                <th class="col-xs-1">Section</th>
                                <th class="col-xs-1">Numéro</th>
                                <th class="col-xs-2"><?php if ($appellation == ParcellaireAffectationClient::APPELLATION_VTSGN): ?>Lieu-dit / <?php endif; ?>Cépage</th>
								<th class="col-xs-1">Année de plantation</th>
                                <th class="col-xs-1">Superficie</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $tabindex = 1;
                            foreach ($parcelles as $key => $parcelle):
                                $attention_ret = ($attention && ($attention == $parcelle->getHashForKey()));
                                $erreur_ret = ($erreur && ($erreur == $parcelle->getHashForKey()));
                                $class = ($erreur_ret || $attention_ret) ? 'error_field_to_focused' : '';
                                $styleErr = ($attention_ret) ? 'style="border-style: solid; border-width: 1px; border-color: darkorange;"' : "";
                                $styleWar = ($erreur_ret) ? 'style="border-style: solid; border-width: 1px; border-color: darkred;"' : "";
                                ?>
                                <tr <?php echo $styleErr . $styleWar; ?> >
                                    <td class="text-center">
                                        <?php
                                        if (isset($form['produits'][$parcelle->getHashForKey()]['vtsgn'])) {
                                            echo $form['produits'][$parcelle->getHashForKey()]['vtsgn']->render();
                                        } else {
                                            echo $form['produits'][$parcelle->getHashForKey()]['active']->render();
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $parcelle->getProduitLibelle(); ?></td>
                                    <td><?php echo $parcelle->getCommune(); ?></td>
                                    <td><?php echo $parcelle->getSection(); ?></td>
                                    <td><?php echo $parcelle->getNumeroParcelle(); ?></td>
                                    <td>
                                        <?php
                                        if ($appellation == ParcellaireAffectationClient::APPELLATION_VTSGN) {
                                            echo ($parcelle->getLieuLibelle()) ? $parcelle->getLieuLibelle() . " / " : "";
                                        }
                                        echo $parcelle->getCepageLibelle();
                                        ?>
                                    </td>
									<td><?php echo $parcelle->campagne_plantation; ?></td>
                                    <td class="edit">
                                        <div class="row">
                                            <div class="col-xs-6 text-right">
                                                <?php echoFloat($parcelle->getSuperficie()) ?>
                                            </div>
                                            <div class="col-xs-6 text-left">
                                                <?php if (!$isVtSgn || $parcelle->isFromAppellation(ParcellaireAffectationClient::APPELLATION_ALSACEBLANC)): ?>
                                                    &nbsp;<a class="btn btn-link btn-xs ajax" href="<?php echo url_for('parcellaire_affectation_parcelle_modification', array('id' => $parcellaire->_id, 'appellation' => $appellation, 'parcelle' => $parcelle->getHashForKey())); ?>" ><span class="glyphicon glyphicon-pencil"></span></a>
                                                <?php else: ?>
                                                    <span class="btn btn-link btn-xs opacity-md" data-toggle="tooltip" title="Cette parcelle provient d'un autre onglet, elle n'est modifiable qu'à son origine"><span class="glyphicon glyphicon-pencil"></span></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                    </td>
                                </tr>
                                <?php
                                $tabindex++;
                            endforeach;
                            ?>
                        </tbody>
                    </table>
            <?php else : ?>
                <p class="text-muted">Vous n'avez affecté aucune <?php if ($parcellaire->isIntentionCremant()): ?>intention de production<?php else: ?>parcelle<?php endif; ?> pour cette appellation.</p><br/>
            <?php endif; ?>
			</div>
            <div class="text-left">
                <button class="btn btn-sm btn-warning ajax" data-toggle="modal" data-target="#popupForm" type="button"><span class="glyphicon glyphicon-plus-sign"></span>&nbsp;&nbsp;Ajouter une parcelle</button>
            </div>
        </div>
    </div>
    <div class="row row-margin row-button" style="margin-top: 20px;">
        <div class="col-xs-6">
            <?php if ($isVtSgn) : ?>
                <a href="<?php echo url_for('parcellaire_affectation_parcelles', array('id' => $parcellaire->_id, 'appellation' => "GRDCRU")); ?>" class="btn btn-default"><span class="glyphicon glyphicon-chevron-left"></span> Retourner à l'étape précédente</a>
            <?php elseif ($appellationNode && $appellationNode->getPreviousAppellationKey()) : ?>
                <a href="<?php echo url_for('parcellaire_affectation_parcelles', array('id' => $parcellaire->_id, 'appellation' => $appellationNode->getPreviousAppellationKey())); ?>" class="btn btn-default"><span class="glyphicon glyphicon-chevron-left"></span> Retourner à l'étape précédente</a>
            <?php else : ?>
                <a href="<?php echo url_for("parcellaire_affectation_propriete", $parcellaire) ?>" class="btn btn-default"><span class="glyphicon glyphicon-chevron-left"></span> Retourner à l'étape précédente</a>
            <?php endif; ?>
        </div>
        <div class="col-xs-6 text-right">
            <?php if ($parcellaire->exist('etape') && $parcellaire->etape == ParcellaireEtapes::ETAPE_VALIDATION): ?>
                <button id="btn-validation" type="submit" class="btn btn-default btn-lg btn-upper"><span class="glyphicon glyphicon-check"></span>&nbsp;&nbsp;Retourner <small>à la validation</small></button>
            <?php else: ?>
				<button type="submit" class="btn btn-primary btn-upper">Valider et continuer <span class="glyphicon glyphicon-chevron-right"></span></button>
            <?php endif; ?>
        </div>
    </div>
</form>

<?php include_partial('parcellaire/popupAjoutForm', array('url' => url_for('parcellaire_affectation_parcelle_ajout', array('id' => $parcellaire->_id, 'appellation' => $appellation)), 'form' => $ajoutForm)); ?>
