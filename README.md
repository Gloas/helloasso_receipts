# helloasso_recieps
Télécharger la liste des clients de votre boutique au format CSV à l'adresse suivante :  https://admin.helloasso.com/"mon-asso"/boutiques/"ma-boutique"/statistiques

Déposer le fichier ici avec le nom : export-paiements.csv

Inspecter la requête suivante avec votre navigateur :
https://www.helloasso.com/associations/"mon-asso"/boutiques/"ma-boutique"/paiement-attestation/"oredr_id"
Puis copier-coller le contenu du paramêtre Cookie de la requête envoyée lorsque vous télécharger manuellement une facture d'un client ici, dans le fichier cookies.txt

lancer la commande
php download_recieps.php "ma-boutique"

Consulter les pdf dans le dossier recieps
