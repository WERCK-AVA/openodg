<?php if(count($history) > 0): ?>
<h2>Historique des déclarations</h2>
<div class="list-group">
<?php foreach ($history as $document): ?>
	<?php if ($document->type == DRevMarcClient::TYPE_MODEL): ?>
        <a class="list-group-item" href="<?php echo url_for('drevmarc_visualisation', $document) ?>">Revendication de Marc d'Alsace Gewurztraminer <?php echo $document->campagne ?> <small class="text-muted"><?php if($document->isPapier()): ?>(Papier)<?php else: ?>(Télédéclaration)<?php endif; ?></small></a>
	<?php elseif($document->type == DRevClient::TYPE_MODEL): ?>
        <a class="list-group-item" href="<?php echo url_for('drev_visualisation', $document) ?>">Revendication des appellations viticoles <?php echo $document->campagne ?> <small class="text-muted"><?php if($document->isPapier()): ?>(Papier)<?php else: ?>(Télédéclaration)<?php endif; ?></small></a>
    <?php elseif($document->type == ParcellaireClient::TYPE_MODEL && strpos($document->_id, ParcellaireClient::TYPE_COUCHDB."-") !== false): ?>
        <a class="list-group-item" href="<?php echo url_for('parcellaire_visualisation', $document) ?>">Affectation parcellaire <?php echo $document->campagne ?> <small class="text-muted"><?php if($document->isPapier()): ?>(Papier)<?php else: ?>(Télédéclaration)<?php endif; ?></small></a>
    <?php elseif($document->type == ParcellaireClient::TYPE_MODEL && strpos($document->_id, ParcellaireClient::TYPE_COUCHDB_PARCELLAIRE_CREMANT."-") !== false): ?>
        <a class="list-group-item" href="<?php echo url_for('parcellaire_visualisation', $document) ?>">Affectation parcellaire Crémant <?php echo $document->campagne ?> <small class="text-muted"><?php if($document->isPapier()): ?>(Papier)<?php else: ?>(Télédéclaration)<?php endif; ?></small></a>
    <?php elseif($document->type == ParcellaireClient::TYPE_MODEL && strpos($document->_id, ParcellaireClient::TYPE_COUCHDB_TIRAGE."-") !== false): ?>
        <a class="list-group-item" href="<?php echo url_for('tirage_visualisation', $document) ?>">Affectation parcellaire Crémant <?php echo $document->campagne ?> <small class="text-muted"><?php if($document->isPapier()): ?>(Papier)<?php else: ?>(Télédéclaration)<?php endif; ?></small></a>
    <?php endif; ?>
<?php endforeach; ?>
</div>
<?php endif; ?>
