<div class="col-sm-6 col-md-4 col-xs-12">
    <div class="block_declaration panel  <?php if ($parcellaire && $parcellaire->validation): ?>panel-success<?php elseif($parcellaire): ?>panel-primary<?php else : ?>panel-default<?php endif; ?>">
        <div class="panel-heading">
            <h3 class="panel-title">Affectation&nbsp;parcellaire&nbsp;<?php echo $campagne; ?></h3>
        </div>
        <?php if ($parcellaire && $parcellaire->validation): ?>
        <div class="panel-body">
                <p>Vous avez déjà validé votre déclaration d'affectation parcellaire pour cette année.</p>
            </div>
        <div class="panel-bottom">
            <p>
                <a class="btn btn-lg btn-block btn-primary" href="<?php echo url_for('parcellaire_visualisation', $parcellaire) ?>">Visualiser</a>
            </p>
            <?php if($sf_user->isAdmin()): ?>
            <p>
                <a onclick='return confirm("Êtes vous sûr de vouloir dévalider cette déclaration ?");' class="btn btn-xs btn-warning pull-right" href="<?php echo url_for('parcellaire_devalidation', $parcellaire) ?>"><span class="glyphicon glyphicon-remove-sign"></span>&nbsp;&nbsp;Dévalider la déclaration</a>
            </p>
            <?php endif; ?>
        </div>
        <?php elseif ($parcellaire):  ?>
            <div class="panel-body">
                <p>Vous avez débuté votre déclaration d'affectation parcellaire pour cette année sans la valider.</p>
                <div style="margin-top: 50px;">
                    <a class="btn btn-block btn-primary" href="<?php echo url_for('parcellaire_edit', $parcellaire) ?>"><?php if($parcellaire->isPapier()): ?><span class="glyphicon glyphicon-file"></span> Continuer la saisie papier<?php else: ?>Continuer la télédéclaration<?php endif; ?></a>
                    <a onclick='return confirm("Êtes vous sûr de vouloir supprimer cette saisie ?");' class="btn btn-xs btn-default btn-block" href="<?php echo url_for('parcellaire_delete', $parcellaire) ?>"><span class="glyphicon glyphicon-trash"></span>&nbsp;&nbsp;Supprimer le brouillon</a>
                </div>
            </div>
            <?php elseif (!ParcellaireClient::getInstance()->isOpen()): ?>
                <div class="panel-body">
                    <?php if(date('Y-m-d') > ParcellaireClient::getInstance()->getDateOuvertureFin()): ?>
                    <p>Le Téléservice est fermé. Pour toute question, veuillez contacter directement l'AVA.</p>
                    <?php else: ?>
                    <p>Le Téléservice sera ouvert à partir du <?php echo format_date(ParcellaireClient::getInstance()->getDateOuvertureDebut(), "D", "fr_FR") ?>.</p>
                    <?php endif; ?>
                    <div style="margin-top: 50px;">
                        <?php if ($sf_user->isAdmin()): ?>
                                <a class="btn btn-default btn-block" href="<?php echo url_for('parcellaire_create', array('sf_subject' => $etablissement, 'campagne' => $campagne)) ?>">Démarrer la télédéclaration</a>
                                <a class="btn btn-xs btn-default btn-block" href="<?php echo url_for('parcellaire_create_papier', array('sf_subject' => $etablissement, 'campagne' => $campagne)) ?>"><span class="glyphicon glyphicon-file"></span>&nbsp;&nbsp;Saisir la déclaration papier</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else:  ?>
            <div class="panel-body">
                    <p>Aucune déclaration d'affectation parcellaire n'a été débutée vous concernant cette année</p>
                     </div>
            <div class="panel-bottom">
                    <p>
                        <a class="btn btn-lg btn-block btn-default" href="<?php echo url_for('parcellaire_create', array('sf_subject' => $etablissement, 'campagne' => $campagne)) ?>">Démarrer la télédéclaration</a>
                    </p>

                    <?php if ($sf_user->isAdmin()): ?>
                        <p>
                            <a class="btn btn-xs btn-warning btn-block" href="<?php echo url_for('parcellaire_create_papier', array('sf_subject' => $etablissement, 'campagne' => $campagne)) ?>"><span class="glyphicon glyphicon-file"></span>&nbsp;&nbsp;Saisir la déclaration papier</a>
                        </p>
                    <?php endif; ?>
            </div>
            <?php endif; ?>
    </div>
</div>