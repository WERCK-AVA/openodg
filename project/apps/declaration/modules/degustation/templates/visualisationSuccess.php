<?php use_helper("Date"); ?>
<?php use_helper('Degustation') ?>

<div class="page-header no-border">
    <h2><?php echo $tournee->appellation_libelle; ?>&nbsp;<span class="small"><?php echo getDatesPrelevements($tournee); ?></span>&nbsp;<div class="btn-group"><button class="btn btn-default btn-default-step btn-sm"><?php echo count($tournee->operateurs) ?>&nbsp;opérateurs</button><button class="btn btn-default btn-default-step btn-sm"><?php echo ($tournee->nombre_prelevements) ? $tournee->nombre_prelevements : "0" ?> prélevement<?php if($tournee->nombre_prelevements): ?>s<?php endif; ?> (<?php echo $tournee->getNbLots() ?> prévus)</button></div></h2>
</div>

<?php if ($sf_user->hasFlash('notice')): ?>
    <div class="alert alert-success" role="alert"><?php echo $sf_user->getFlash('notice') ?></div>
<?php endif; ?>

<?php include_partial('degustation/recap', array('tournee' => $tournee)); ?>

<?php if (in_array($tournee->statut, array(TourneeClient::STATUT_COURRIERS, TourneeClient::STATUT_TERMINE))): ?>
    <?php include_partial('degustation/notes', array('tournee' => $tournee)); ?>
<?php endif; ?>

<div class="row row-margin">
    <div class="col-xs-2 text-left">
            <a class="btn btn-primary btn-lg btn-upper" href="<?php echo url_for('degustation') ?>"><span class="eleganticon arrow_carrot-left"></span>&nbsp;&nbsp;Retour</a>
    </div>
    <div class="col-xs-10 text-right">
        <?php if (in_array($tournee->statut, array(TourneeClient::STATUT_COURRIERS))): ?>
            <?php $nbCourrierToSend = count($tournee->getPrelevementsCourrierToSend()); ?>
            <div class="btn-group">
            <a class="btn btn-default btn-default-step btn-lg" href="<?php echo url_for('degustation_courriers', $tournee); ?>"><span class="glyphicon glyphicon-list-alt"></span>&nbsp;&nbsp;Affecter les types courriers <span class="badge"><?php echo $tournee->countNotTypeCourrier() ?></span></a>
            <a <?php if(!$nbCourrierToSend): ?>disabled="disabled"<?php endif; ?> onclick="return confirm('Étes vous sûr d\'envoyer les courrier restant ?')" class="btn btn-warning btn-lg" href="<?php echo url_for('degustation_generation_courriers', $tournee); ?>"><span class="glyphicon glyphicon-envelope"></span>&nbsp;&nbsp;Envoyer les courriers <span class="badge"><?php echo $nbCourrierToSend ?></span></a>
            <a <?php if(!$tournee->hasAllTypeCourrier()): ?>disabled="disabled"<?php endif; ?> onclick="return confirm('/!\\\ Il reste des mails non envoyés ! Étes-vous sur de vouloir clôturer la dégustation ?')" class="btn btn-default btn-default-step btn-lg" href="<?php echo url_for('degustation_cloturer', $tournee) ?>"><span class="glyphicon glyphicon-check"></span>&nbsp;&nbsp;Clôturer</span></a>
            </div>
        <?php elseif ($tournee->statut == TourneeClient::STATUT_DEGUSTATIONS && $tournee->isDegustationTerminee()): ?>
            <a class="btn btn-warning btn-lg" href="<?php echo url_for('degustation_lever_anonymat', $tournee) ?>"><span class="glyphicon glyphicon-user"></span>&nbsp;&nbsp;Lever l'anonymat</a>
        <?php elseif ($tournee->statut == TourneeClient::STATUT_DEGUSTATIONS || ($tournee->statut == TourneeClient::STATUT_AFFECTATION && $tournee->isAffectationTerminee())): ?>
            <a class="btn btn-warning btn-lg" href="<?php echo url_for('degustation_degustations', $tournee) ?>"><span class="glyphicon glyphicon-glass"></span>&nbsp;&nbsp;Saisir les dégustations</a>
        <?php elseif ($tournee->statut == TourneeClient::STATUT_AFFECTATION || ($tournee->statut == TourneeClient::STATUT_TOURNEES && $tournee->isTourneeTerminee())): ?>
            <a class="btn btn-warning btn-lg" href="<?php echo url_for('degustation_affectation', $tournee) ?>"><span class="glyphicon glyphicon-list-alt"></span>&nbsp;&nbsp;Anonymer les prélèvements</a>
        <?php elseif (!in_array($tournee->statut, array(TourneeClient::STATUT_COURRIERS, TourneeClient::STATUT_TERMINE))): ?>
            <a class="btn btn-warning btn-lg" href="<?php echo url_for('degustation_organisation', $tournee) ?>"><span class="glyphicon glyphicon-pencil"></span>&nbsp;&nbsp;Modifier l'organisation des tournées</a>
        <?php endif; ?>
    </div>
</div>