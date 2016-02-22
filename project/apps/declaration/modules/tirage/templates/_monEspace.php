<?php use_helper('Date'); ?>

<div class="col-xs-4">
    <div class="block_declaration panel <?php if ($tirage && $tirage->validation): ?>panel-success<?php else: ?>panel-primary<?php endif; ?>">     
        <div class="panel-heading">
            <h3>Tirage Crémant&nbsp;<?php echo ConfigurationClient::getInstance()->getCampagneManager()->getCurrent(); ?><br /><br /></h3>
        </div>
        <?php if ($tirage && $tirage->validation): ?>
            <div class="panel-body">
                <p>Votre déclaration de revendication de Marc d'Alsace Gewurztraminer a été validée pour cette année.</p>
            </div>
            <div class="panel-bottom">
                <p>
                    <a class="btn btn-lg btn-block btn-primary" href="<?php echo url_for('tirage_visualisation', $tirage) ?>">Visualiser</a>
                </p>
                <?php if (TirageSecurity::getInstance($sf_user, $tirage->getRawValue())->isAuthorized(TirageSecurity::DEVALIDATION)): ?>
                    <p>
                        <a class="btn btn-xs btn-warning pull-right" href="<?php echo url_for('tirage_devalidation', $tirage) ?>"><span class="glyphicon glyphicon-remove-sign"></span>&nbsp;&nbsp;Dévalider la déclaration</a>
                    </p>
                <?php endif; ?>
            </div>
        <?php elseif ($tirage): ?>
                <div class="panel-body">
                    <p>Une déclaration de tirage a été débutée.</p>
                </div>
                <div class="panel-bottom">
                    <p>
                        <a class="btn btn-lg btn-block btn-default" href="<?php echo url_for('tirage_edit', $tirage) ?>"><?php if($tirage->isPapier()): ?><span class="glyphicon glyphicon-file"></span> Continuer la saisie papier<?php else: ?>Continuer la télédéclaration<?php endif; ?></a>
                    </p>
                    <p>
                        <a class="btn btn-xs btn-danger pull-right" href="<?php echo url_for('tirage_delete', $tirage) ?>"><span class="glyphicon glyphicon-trash"></span>&nbsp;&nbsp;Supprimer le brouillon</a>
                    </p>
                </div>
        <?php else: ?>
        <div class="panel-body">
            <p>Créer une nouvelle déclaration de tirage</p>
        </div>
        <div class="panel-bottom">  
            <p>
                <a class="btn btn-lg btn-block btn-default" href="<?php echo url_for('tirage_create', $etablissement) ?>">Démarrer la télédéclaration</a>
            </p>
            <?php if ($sf_user->isAdmin()): ?>
                <p>
                    <a class="btn btn-xs btn-warning btn-block" href="<?php echo url_for('tirage_create_papier', $etablissement) ?>"><span class="glyphicon glyphicon-file"></span>&nbsp;&nbsp;Saisir la déclaration papier</a>
                </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
