<?php echo include_partial('Email/headerMail') ?>
Madame, Monsieur,

Votre déclaration de Revendication Marc d'Alsace de Gewurztraminer <?php echo $drevmarc->campagne; ?> a bien été validée et envoyée au service Appui technique de l'AVA.

Cette validation sera définitive lorsque votre déclaration aura été vérifiée.

D'autre par vous trouverez en pièce jointe le document PDF de votre déclaration de Marc d'Alsace Gewurztraminer.

Vous pouvez à tout moment revenir sur votre compte pour consulter votre document : <?php echo sfContext::getInstance()->getRouting()->generate('drevmarc_visualisation', $drevmarc, true); ?>


Bien cordialement,

Le service Appui technique (via l'application de télédéclaration)
<?php echo include_partial('Email/footerMail') ?>