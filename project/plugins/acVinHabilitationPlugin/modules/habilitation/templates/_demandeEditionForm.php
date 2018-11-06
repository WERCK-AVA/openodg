<?php use_helper('Orthographe'); ?>
<div class="modal fade modal-page" aria-labelledby="Modifier la demande" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
            <form method="post" action="" role="form" class="form-horizontal">
                <div class="modal-header">
                    <a href="<?php echo url_for("habilitation_declarant", $etablissement) ?>" class="close" aria-hidden="true">&times;</a>
                    <h4 class="modal-title" id="myModalLabel">Demande <?php echo elision("de", strtolower($demande->getDemandeLibelle())) ?></h4>
					<?php echo $demande->libelle ?>
                </div>
                <div class="modal-body">
					<table class="table table-condensed table-bordered table-striped">
					    <thead>
					        <tr>
					            <th class="col-xs-1">Date</th>
					            <th class="col-xs-3">Statut</th>
					            <th class="col-xs-9">Commentaire</th>
					        </tr>
					    </thead>
					    <tbody>
							<?php foreach($demande->getFullHistorique() as $event): ?>
					        <tr style="<?php if($demande->date == $event->date && $demande->statut = $event->statut): ?>font-weight: bold;<?php endif; ?>">
					            <td><?php echo Date::francizeDate($event->date); ?></td>

					            <td><?php echo HabilitationClient::getInstance()->getDemandeStatutLibelle($event->statut); ?></td>

					            <td><?php echo $event->commentaire; ?></td>
					        </tr>
							<?php endforeach; ?>
					    </tbody>
					</table>

					<?php if($form instanceof sfForm): ?>
					<hr />
					<?php include_partial('habilitation/demandeForm', array('form' => $form, 'demande' => $demande)); ?>
					<?php endif; ?>
				</div>
                <div class="modal-footer">
					<?php if($form instanceof sfForm): ?>
                    <a class="btn btn-default pull-left" href="<?php echo (isset($urlRetour) && $urlRetour) ? $urlRetour : url_for("habilitation_declarant", $etablissement) ?>">Annuler</a>
                    <button type="submit" class="btn btn-success pull-right">Valider le changement</button>
					<?php else: ?>
						<a class="btn btn-default" href="<?php echo (isset($urlRetour) && $urlRetour) ? $urlRetour : url_for("habilitation_declarant", $etablissement) ?>">Fermer</a>
					<?php endif; ?>
				</div>
            </form>
        </div>
	</div>
</div>