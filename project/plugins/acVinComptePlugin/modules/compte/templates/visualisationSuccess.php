<?php use_helper('Compte') ?>
<ol class="breadcrumb">
    <li><a href="<?php echo url_for('societe') ?>">Contacts</a></li>
    <li><a href="<?php echo url_for('societe_visualisation', array('identifiant' => $societe->identifiant)); ?>"><span class="<?php echo comptePictoCssClass($societe->getRawValue()) ?>"></span> <?php echo $societe->raison_sociale; ?></a></li>
    <li class="active"><a href="<?php echo url_for('compte_visualisation', array('identifiant' => $compte->identifiant)); ?>"><span class="<?php echo comptePictoCssClass($compte->getRawValue()) ?>"></span> <?php echo $compte->nom_a_afficher; ?></a></li>
</ol>

<div class="row">
    <div class="col-xs-8">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-9">
                        <h4>Compte n° <?php echo $compte->identifiant; ?></h4>
                    </div>
                    <div class="col-xs-3 text-muted text-right">
                        <div class="btn-group">
                            <a class="btn dropdown-toggle " data-toggle="dropdown" href="#">Modifier <span class="caret"></span></a>
                            <ul class="dropdown-menu">
                                <li<?php echo ($compte->isSuspendu() || $compte->isSuspendu()) ? ' class="disabled"' : ''; ?>><a href="<?php echo ($compte->isSuspendu() || $compte->isSuspendu()) ? 'javascript:void(0)' : url_for('compte_modification', $compte); ?>">Editer</a></li>
                                <li<?php echo ($compte->isSuspendu() || $compte->isSuspendu())? ' class="disabled"' : ''; ?>><a href="<?php echo ($compte->isSuspendu() || $compte->isSuspendu())? 'javascript:void(0)' : url_for('compte_switch_statut', array('identifiant' => $compte->identifiant)); ?>">Suspendre</a></li>
                                <li<?php echo ($compte->isSuspendu() || $compte->isActif())? ' class="disabled"' : ''; ?>><a href="<?php echo ($compte->isSuspendu() || $compte->isActif())? 'javascript:void(0)' : url_for('compte_switch_statut', array('identifiant' => $compte->identifiant)); ?>">Activer</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="panel-body">
                <h2><span class="glyphicon glyphicon-user"></span> <?php echo $compte->nom_a_afficher; ?></h2>
                <hr/>
                <div class="row">
                    <div class="col-xs-5">
                        <div class="row">
                            <?php if ($compte->identifiant): ?>
                                <div style="margin-bottom: 5px;" class="col-xs-4 text-muted">Identifiant&nbsp;:</div>
                                <div style="margin-bottom: 5px;" class="col-xs-8"><?php echo $compte->identifiant; ?></div>
                            <?php endif; ?>
                            <?php if ($compte->fonction): ?>
                                <div style="margin-bottom: 5px;" class="col-xs-4 text-muted">Fonction&nbsp;:</div>
                                <div style="margin-bottom: 5px;" class="col-xs-8"><?php echo $compte->fonction; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-xs-7" style="border-left: 1px solid #eee">
                        <?php include_partial('compte/visualisationAdresse', array('compte' => $compte)); ?>
                    </div>
                </div>
                <hr />
                <h5 style="margin-bottom: 15px; margin-top: 15px;" class="text-muted"><strong>Informations complémentaires</strong></h5>
                <?php include_partial('compte/visualisationTags', array('compte' => $compte)); ?>
                <?php if ($compte->commentaire) : ?>
                <hr />
                <h5 class="text-muted" style="margin-bottom: 15px; margin-top: 0px;"><strong>Commentaire</strong></h5>
                <pre><?php echo $compte->commentaire; ?></pre>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-xs-4">
        <?php include_component('societe', 'sidebar', array('societe' => $societe, 'activeObject' => $compte)); ?>
    </div>
</div>