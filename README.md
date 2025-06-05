# Téléchargeur de reçus HelloAsso

## Prérequis
- PHP 8.0 ou supérieur
- Un compte HelloAsso avec accès administrateur

## Configuration

1. **Exporter les données** :
   - Téléchargez la liste des clients au format CSV depuis :  
     `https://admin.helloasso.com/mon-asso/boutiques/ma-boutique/statistiques`
   - Renommez le fichier en `export-paiements.csv` et placez-le à la racine du projet

2. **Configurer les headers** :
   - Inspectez cette URL dans votre navigateur :  
     `https://www.helloasso.com/associations/mon-asso/boutiques/ma-boutique/paiement-attestation/order_id`
   - Copiez les en-têtes (F12 > Network) dans `headers.txt`

## Utilisation

```bash
php download_receipts.php "ma-boutique"
```

Les reçus seront téléchargés dans le dossier `receipts/`.

## Support

Pour tout problème, vérifiez que :
- Le fichier CSV est correctement formaté
- Les headers sont à jour
- Vous avez les permissions d'écriture
