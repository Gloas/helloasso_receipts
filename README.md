# Téléchargeur de reçus HelloAsso

Outil pour télécharger automatiquement les reçus d'une boutique depuis HelloAsso.

## 🚀 Fonctionnalités

### 📦 Téléchargement automatisé
- Téléchargement massif des reçus fiscaux depuis HelloAsso
- Gestion des erreurs de téléchargement avec système de logs
- Support des URLs dynamiques avec paramètres personnalisés

### ⚙️ Configuration avancée
- Chargement des configurations depuis fichier PHP
- Support des en-têtes HTTP personnalisés
- Traitement des fichiers CSV avec mapping des colonnes
- Vérification automatique de l'existence des fichiers

### 🛠️ Gestion des erreurs
- Journalisation détaillée des opérations
- Gestion des exceptions avec messages clairs
- Vérification des prérequis système
- Tests unitaires couvrant les principales fonctionnalités

### 🧪 Environnement de test
- Tests automatisés avec PHPUnit
- Mocks pour les appels HTTP
- Validation des URLs générées
- Tests de cas limites (fichiers vides, configs invalides)

## 📋 Prérequis
- PHP 8.0 ou supérieur
- Compte HelloAsso avec accès administrateur
- Extensions PHP : `curl`

## 🔧 Installation
```bash
git clone git@github.com:Gloas/helloasso_receipts.git
```

## ⚙️ Configuration

### 1. Exporter les données
1. Connectez-vous à [HelloAsso Admin](https://admin.helloasso.com)
2. Allez dans `Boutique` > `Statistiques`
3. Exportez la liste des paiements au format CSV
4. Renommez le fichier en `export-paiements.csv` à la racine du projet

### 2. Configurer les headers
1. Ouvrez l'URL des reçus dans votre navigateur :
   ```
   https://www.helloasso.com/associations/mon-asso/boutiques/ma-boutique/paiement-attestation/order_id
   ```
2. Ouvrez les outils de développement (F12)
3. Allez dans l'onglet Network et copiez les headers de la requête
4. Collez-les dans `headers.txt`

## 🖥️ Utilisation
```bash
php download_receipts.php "nom-de-votre-boutique"
```

Exemple concret :
```bash
php download_receipts.php "vente-d-ete-2025"
```

Les reçus seront sauvegardés dans `receipts/`.

## 🛠️ Dépannage
- Vérifiez que le fichier CSV est correctement formaté
- Vérifiez que les headers sont à jour
- Assurez-vous d'avoir les permissions d'écriture
- Consultez les logs générés dans `logs/`

## 📄 Licence
[MIT](LICENSE) - © 2024-2025 Ghislain Loas
