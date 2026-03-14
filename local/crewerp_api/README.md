# CrewERP Full Bridge API - Documentation Complète

## 📋 Table des matières

1. [Introduction](#introduction)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Fonctions disponibles](#fonctions-disponibles)
5. [Exemples d'utilisation](#exemples-dutilisation)
6. [Gestion des erreurs](#gestion-des-erreurs)
7. [Sécurité et permissions](#sécurité-et-permissions)
8. [Structure technique](#structure-technique)

---

## 🎯 Introduction

Le plugin **CrewERP Full Bridge API** est un module local Moodle/IOMAD (version 3.9+) qui agit comme un pont entre une application ERP externe (Laravel) et Moodle. Il permet d'effectuer des opérations CRUD complètes sur les modules de cours (URLs et Labels) et les sections de cours via l'API Web Services REST de Moodle.

### Fonctionnalités principales

- ✅ Création de modules URL dans les sections de cours
- ✅ Mise à jour de modules URL existants
- ✅ Création de modules Label avec contenu HTML
- ✅ Mise à jour de modules Label existants
- ✅ Création de modules SCORM avec packages uploadés
- ✅ Suppression de modules de cours
- ✅ Mise à jour des noms de sections de cours

### Prérequis

- Moodle/IOMAD version 3.9 ou supérieure
- Web Services activés dans Moodle
- Protocole REST activé
- Permissions appropriées pour l'utilisateur du service

---

## 📦 Installation

### Étape 1 : Copier les fichiers

Copiez le dossier `local/crewerp_api` dans le répertoire `local/` de votre installation Moodle :

```
votre-moodle/
└── local/
    └── crewerp_api/
        ├── version.php
        ├── externallib.php
        ├── db/
        │   └── services.php
        └── lang/
            └── en/
                └── local_crewerp_api.php
```

### Étape 2 : Installation via l'interface Moodle

1. Connectez-vous en tant qu'administrateur
2. Allez dans **Administration du site** → **Notifications**
3. Moodle détectera automatiquement le nouveau plugin
4. Cliquez sur **Mettre à jour la base de données maintenant**
5. Attendez la fin de l'installation

### Étape 3 : Vérification

Vérifiez que le plugin est installé en allant dans **Administration du site** → **Plugins** → **Plugins locaux**. Vous devriez voir "CrewERP Full Bridge API" dans la liste.

---

## ⚙️ Configuration

### 1. Activer les Web Services

1. Allez dans **Administration du site** → **Fonctionnalités avancées**
2. Cochez **Activer les services web**
3. Cochez **Activer les protocoles** → **REST**
4. Enregistrez les modifications

### 2. Créer un utilisateur de service (recommandé)

1. Créez un utilisateur dédié pour l'API (ex: `crewerp_api_user`)
2. Accordez-lui les rôles nécessaires dans les cours concernés
3. Notez l'ID de l'utilisateur

### 3. Activer le service CrewERP

1. Allez dans **Administration du site** → **Plugins** → **Services web** → **Services externes**
2. Recherchez **"CrewERP Service"** dans la liste
3. Cliquez sur l'icône d'édition (crayon)
4. Cochez **Activé**
5. Enregistrez

### 4. Créer un token d'accès

1. Allez dans **Administration du site** → **Plugins** → **Services web** → **Gérer les tokens**
2. Cliquez sur **Créer un token**
3. Sélectionnez l'utilisateur de service créé précédemment
4. Sélectionnez le service **"CrewERP Service"**
5. Cliquez sur **Enregistrer**
6. **Copiez et conservez le token généré** (il ne sera plus affiché)

### 5. Configurer les permissions

Assurez-vous que l'utilisateur de service a les capacités suivantes :
- `mod/url:addinstance` - Pour créer/modifier des modules URL
- `mod/label:addinstance` - Pour créer/modifier des modules Label
- `moodle/course:manageactivities` - Pour supprimer des modules
- `moodle/course:update` - Pour modifier les sections

---

## 🔧 Fonctions disponibles

### 1. `local_crewerp_api_create_url`

Crée un nouveau module URL dans une section de cours.

#### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `courseid` | integer | ✅ Oui | ID du cours |
| `sectionnum` | integer | ✅ Oui | Numéro de la section (commence à 0) |
| `name` | string | ✅ Oui | Nom du module |
| `url` | string (URL) | ✅ Oui | URL à lier |
| `intro` | string (HTML) | ❌ Non | Texte d'introduction (par défaut: '') |

#### Retour

```json
{
  "id": 123,
  "status": "success"
}
```

- `id` : ID du module de cours créé (cmid)
- `status` : Statut de l'opération

#### Exemple de requête

```bash
POST /webservice/rest/server.php?wstoken=VOTRE_TOKEN&wsfunction=local_crewerp_api_create_url&moodlewsrestformat=json

{
  "courseid": 2,
  "sectionnum": 1,
  "name": "Lien vers documentation",
  "url": "https://example.com/docs",
  "intro": "<p>Cliquez ici pour accéder à la documentation</p>"
}
```

---

### 2. `local_crewerp_api_update_url`

Met à jour un module URL existant.

#### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `cmid` | integer | ✅ Oui | ID du module de cours |
| `name` | string | ❌ Non | Nouveau nom du module |
| `url` | string (URL) | ❌ Non | Nouvelle URL |
| `intro` | string (HTML) | ❌ Non | Nouveau texte d'introduction |

**Note** : Au moins un paramètre optionnel doit être fourni.

#### Retour

```json
{
  "status": "success"
}
```

#### Exemple de requête

```bash
POST /webservice/rest/server.php?wstoken=VOTRE_TOKEN&wsfunction=local_crewerp_api_update_url&moodlewsrestformat=json

{
  "cmid": 123,
  "name": "Nouveau nom du lien",
  "url": "https://example.com/new-url"
}
```

---

### 3. `local_crewerp_api_create_label`

Crée un nouveau module Label avec contenu HTML dans une section de cours.

#### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `courseid` | integer | ✅ Oui | ID du cours |
| `sectionnum` | integer | ✅ Oui | Numéro de la section |
| `content` | string (HTML) | ✅ Oui | Contenu HTML du label |

#### Retour

```json
{
  "id": 124,
  "status": "success"
}
```

- `id` : ID du module de cours créé (cmid)
- `status` : Statut de l'opération

#### Exemple de requête

```bash
POST /webservice/rest/server.php?wstoken=VOTRE_TOKEN&wsfunction=local_crewerp_api_create_label&moodlewsrestformat=json

{
  "courseid": 2,
  "sectionnum": 1,
  "content": "<h2>Titre de section</h2><p>Contenu du label avec <strong>formatage HTML</strong></p>"
}
```

---

### 4. `local_crewerp_api_update_label`

Met à jour le contenu d'un module Label existant.

#### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `cmid` | integer | ✅ Oui | ID du module de cours |
| `content` | string (HTML) | ✅ Oui | Nouveau contenu HTML |

#### Retour

```json
{
  "status": "success"
}
```

#### Exemple de requête

```bash
POST /webservice/rest/server.php?wstoken=VOTRE_TOKEN&wsfunction=local_crewerp_api_update_label&moodlewsrestformat=json

{
  "cmid": 124,
  "content": "<h2>Nouveau titre</h2><p>Contenu mis à jour</p>"
}
```

---

### 5. `local_crewerp_api_create_scorm`

Crée un nouveau module SCORM dans une section de cours. Le package SCORM doit être préalablement uploadé dans la zone de brouillon Moodle via l'API `core_files_upload`.

> 📖 **Documentation complète** : Voir [SCORM_DOCUMENTATION.md](SCORM_DOCUMENTATION.md) pour les détails complets sur l'utilisation de SCORM.

#### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `courseid` | integer | ✅ Oui | ID du cours |
| `sectionnum` | integer | ✅ Oui | Numéro de la section |
| `name` | string | ✅ Oui | Nom du module |
| `intro` | string (HTML) | ❌ Non | Texte d'introduction (par défaut: '') |
| `draftitemid` | integer | ✅ Oui | ID de la zone de brouillon contenant le fichier SCORM |

#### Retour

```json
{
  "id": 125,
  "status": "success"
}
```

- `id` : ID du module de cours créé (cmid)
- `status` : Statut de l'opération

#### Workflow

1. **Upload du fichier** : Utilisez `core_files_upload` pour uploader le package SCORM dans la zone de brouillon
2. **Création du module** : Appelez `create_scorm` avec le `draftitemid` obtenu

#### Exemple de requête

```bash
POST /webservice/rest/server.php?wstoken=VOTRE_TOKEN&wsfunction=local_crewerp_api_create_scorm&moodlewsrestformat=json

{
  "courseid": 2,
  "sectionnum": 1,
  "name": "Formation SCORM",
  "intro": "<p>Description de la formation</p>",
  "draftitemid": 12345
}
```

#### Prérequis

- Module SCORM installé dans Moodle
- Capacité `mod/scorm:addinstance` pour l'utilisateur
- Package SCORM valide (ZIP avec manifest.xml)

---

### 6. `local_crewerp_api_delete_module`

Supprime un module de cours (peut être n'importe quel type de module).

#### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `cmid` | integer | ✅ Oui | ID du module de cours à supprimer |

#### Retour

```json
{
  "status": "success"
}
```

#### Exemple de requête

```bash
POST /webservice/rest/server.php?wstoken=VOTRE_TOKEN&wsfunction=local_crewerp_api_delete_module&moodlewsrestformat=json

{
  "cmid": 123
}
```

---

### 7. `local_crewerp_api_update_section`

Met à jour le nom d'une section de cours.

#### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `courseid` | integer | ✅ Oui | ID du cours |
| `sectionnum` | integer | ✅ Oui | Numéro de la section |
| `name` | string | ✅ Oui | Nouveau nom de la section |

#### Retour

```json
{
  "status": "success"
}
```

#### Exemple de requête

```bash
POST /webservice/rest/server.php?wstoken=VOTRE_TOKEN&wsfunction=local_crewerp_api_update_section&moodlewsrestformat=json

{
  "courseid": 2,
  "sectionnum": 1,
  "name": "Nouvelle section"
}
```

---

## 💻 Exemples d'utilisation

### Exemple 1 : Créer un module URL avec cURL (PHP)

```php
<?php
$token = 'VOTRE_TOKEN_MOODLE';
$domain = 'https://votre-moodle.com';
$function = 'local_crewerp_api_create_url';

$params = array(
    'courseid' => 2,
    'sectionnum' => 1,
    'name' => 'Documentation API',
    'url' => 'https://api.example.com/docs',
    'intro' => '<p>Accédez à la documentation complète de l\'API</p>'
);

$url = $domain . '/webservice/rest/server.php';
$data = array(
    'wstoken' => $token,
    'wsfunction' => $function,
    'moodlewsrestformat' => 'json'
);

// Ajouter les paramètres
foreach ($params as $key => $value) {
    $data[$key] = $value;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
print_r($result);
?>
```

### Exemple 2 : Utilisation avec Laravel (Guzzle HTTP)

```php
<?php
use Illuminate\Support\Facades\Http;

$token = 'VOTRE_TOKEN_MOODLE';
$domain = 'https://votre-moodle.com';

$response = Http::asForm()->post("{$domain}/webservice/rest/server.php", [
    'wstoken' => $token,
    'wsfunction' => 'local_crewerp_api_create_label',
    'moodlewsrestformat' => 'json',
    'courseid' => 2,
    'sectionnum' => 1,
    'content' => '<h2>Bienvenue</h2><p>Ceci est un label créé via l\'API</p>'
]);

$result = $response->json();

if (isset($result['id'])) {
    echo "Module créé avec l'ID: " . $result['id'];
} else {
    echo "Erreur: " . ($result['message'] ?? 'Erreur inconnue');
}
?>
```

### Exemple 3 : Workflow complet (Créer, Modifier, Supprimer)

```php
<?php
// 1. Créer un module URL
$createResponse = Http::asForm()->post("{$domain}/webservice/rest/server.php", [
    'wstoken' => $token,
    'wsfunction' => 'local_crewerp_api_create_url',
    'moodlewsrestformat' => 'json',
    'courseid' => 2,
    'sectionnum' => 1,
    'name' => 'Mon lien',
    'url' => 'https://example.com'
]);

$cmid = $createResponse->json()['id'];
echo "Module créé: {$cmid}\n";

// 2. Modifier le module
$updateResponse = Http::asForm()->post("{$domain}/webservice/rest/server.php", [
    'wstoken' => $token,
    'wsfunction' => 'local_crewerp_api_update_url',
    'moodlewsrestformat' => 'json',
    'cmid' => $cmid,
    'name' => 'Mon lien modifié',
    'url' => 'https://example.com/new'
]);

echo "Module modifié\n";

// 3. Supprimer le module
$deleteResponse = Http::asForm()->post("{$domain}/webservice/rest/server.php", [
    'wstoken' => $token,
    'wsfunction' => 'local_crewerp_api_delete_module',
    'moodlewsrestformat' => 'json',
    'cmid' => $cmid
]);

echo "Module supprimé\n";
?>
```

### Exemple 4 : JavaScript (Fetch API)

```javascript
async function createUrlModule(token, domain, courseId, sectionNum, name, url) {
    const formData = new FormData();
    formData.append('wstoken', token);
    formData.append('wsfunction', 'local_crewerp_api_create_url');
    formData.append('moodlewsrestformat', 'json');
    formData.append('courseid', courseId);
    formData.append('sectionnum', sectionNum);
    formData.append('name', name);
    formData.append('url', url);
    formData.append('intro', '<p>Description du lien</p>');

    try {
        const response = await fetch(`${domain}/webservice/rest/server.php`, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        
        if (result.id) {
            console.log('Module créé avec succès:', result.id);
            return result;
        } else {
            console.error('Erreur:', result);
            throw new Error(result.message || 'Erreur inconnue');
        }
    } catch (error) {
        console.error('Erreur réseau:', error);
        throw error;
    }
}

// Utilisation
createUrlModule(
    'VOTRE_TOKEN',
    'https://votre-moodle.com',
    2,
    1,
    'Mon lien',
    'https://example.com'
);
```

---

## ⚠️ Gestion des erreurs

### Codes d'erreur courants

| Code | Description | Solution |
|------|-------------|----------|
| `invalidtoken` | Token invalide ou expiré | Vérifiez le token et régénérez-le si nécessaire |
| `accessexception` | Permissions insuffisantes | Vérifiez les capacités de l'utilisateur |
| `invalidparameter` | Paramètre invalide | Vérifiez le format et les valeurs des paramètres |
| `invalidcourse` | Cours introuvable | Vérifiez que le courseid existe |
| `invalidcoursemodule` | Module introuvable | Vérifiez que le cmid existe |
| `errorcreatingurl` | Erreur lors de la création | Vérifiez les logs Moodle |
| `errorupdatingurl` | Erreur lors de la mise à jour | Vérifiez les logs Moodle |

### Format de réponse d'erreur

```json
{
  "exception": "moodle_exception",
  "errorcode": "invalidparameter",
  "message": "Invalid parameter value detected",
  "debuginfo": "Details techniques..."
}
```

### Gestion des erreurs en PHP

```php
<?php
$response = Http::asForm()->post("{$domain}/webservice/rest/server.php", [
    'wstoken' => $token,
    'wsfunction' => 'local_crewerp_api_create_url',
    'moodlewsrestformat' => 'json',
    // ... paramètres
]);

$result = $response->json();

if (isset($result['exception'])) {
    // Erreur détectée
    $errorCode = $result['errorcode'];
    $errorMessage = $result['message'];
    
    switch ($errorCode) {
        case 'invalidtoken':
            // Régénérer le token
            break;
        case 'accessexception':
            // Vérifier les permissions
            break;
        default:
            // Autre erreur
            error_log("Erreur API: {$errorCode} - {$errorMessage}");
    }
} else {
    // Succès
    echo "Opération réussie: " . json_encode($result);
}
?>
```

---

## 🔒 Sécurité et permissions

### Permissions requises

Le plugin utilise les capacités Moodle standard :

| Fonction | Capacité requise |
|----------|------------------|
| `create_url` | `mod/url:addinstance` |
| `update_url` | `mod/url:addinstance` |
| `create_label` | `mod/label:addinstance` |
| `update_label` | `mod/label:addinstance` |
| `create_scorm` | `mod/scorm:addinstance` |
| `delete_module` | `moodle/course:manageactivities` |
| `update_section` | `moodle/course:update` |

### Bonnes pratiques de sécurité

1. **Utilisez un utilisateur dédié** : Ne partagez pas le token avec d'autres applications
2. **Limitez les permissions** : Accordez uniquement les capacités nécessaires
3. **Utilisez HTTPS** : Toujours utiliser HTTPS pour les appels API
4. **Validez les entrées** : Validez tous les paramètres côté client avant l'envoi
5. **Surveillez les logs** : Consultez régulièrement les logs Moodle pour détecter les abus
6. **Régénérez les tokens** : Changez régulièrement les tokens d'accès
7. **Restreignez par IP** : Si possible, limitez l'accès par adresse IP dans Moodle

### Configuration des rôles

Pour créer un rôle personnalisé pour l'API :

1. Allez dans **Administration du site** → **Utilisateurs** → **Permissions** → **Définir les rôles**
2. Créez un nouveau rôle "API CrewERP"
3. Accordez les capacités suivantes :
   - `mod/url:addinstance`
   - `mod/label:addinstance`
   - `mod/scorm:addinstance`
   - `moodle/course:manageactivities`
   - `moodle/course:update`
4. Assignez ce rôle à l'utilisateur de service dans les cours concernés

---

## 🏗️ Structure technique

### Architecture du plugin

```
local/crewerp_api/
├── version.php          # Métadonnées du plugin
├── externallib.php      # Logique métier et fonctions API
├── db/
│   └── services.php     # Définition des services web
└── lang/
    └── en/
        └── local_crewerp_api.php  # Chaînes de langue
```

### Flux d'exécution

1. **Réception de la requête** : Moodle reçoit la requête REST
2. **Validation du token** : Vérification de l'authentification
3. **Chargement du service** : Chargement de `db/services.php`
4. **Exécution de la fonction** : Appel de la méthode dans `externallib.php`
5. **Validation des paramètres** : Vérification des types et valeurs
6. **Vérification des permissions** : Contrôle des capacités utilisateur
7. **Exécution de la logique** : Création/modification/suppression
8. **Mise à jour du cache** : Rebuild du cache du cours
9. **Retour de la réponse** : Format JSON avec résultat

### Dépendances

Le plugin utilise les APIs Moodle suivantes :

- `mod_url_add_instance()` - Création de modules URL
- `mod_url_update_instance()` - Mise à jour de modules URL
- `mod_label_add_instance()` - Création de modules Label
- `mod_label_update_instance()` - Mise à jour de modules Label
- `scorm_add_instance()` - Création de modules SCORM
- `scorm_parse()` - Parsing des packages SCORM
- `file_save_draft_area_files()` - Gestion des fichiers
- `course_delete_module()` - Suppression de modules
- `course_add_cm_to_section()` - Ajout de modules aux sections
- `course_update_course_module()` - Mise à jour des modules
- `rebuild_course_cache()` - Reconstruction du cache

### Format des données

#### Module URL
- `name` : Nom affiché du module
- `externalurl` : URL cible
- `intro` : Description (format HTML)
- `display` : Mode d'affichage (par défaut: AUTO)

#### Module Label
- `name` : Nom du module (par défaut: "Label")
- `intro` : Contenu HTML du label
- `introformat` : Format du contenu (FORUM_HTML)

#### Module SCORM
- `name` : Nom du module
- `intro` : Description (format HTML)
- `draftitemid` : ID de la zone de brouillon contenant le package SCORM
- Le package doit être uploadé via `core_files_upload` avant la création
- Format supporté : ZIP avec manifest.xml (SCORM 1.2 ou SCORM 2004)

#### Module SCORM
- `name` : Nom du module
- `intro` : Description (format HTML)
- `draftitemid` : ID de la zone de brouillon contenant le package SCORM
- Le package doit être uploadé via `core_files_upload` avant la création

---

## 📝 Notes importantes

### Numérotation des sections

⚠️ **Important** : Les sections dans Moodle commencent à **0** (zéro) :
- Section 0 = Section générale (en haut)
- Section 1 = Première section de contenu
- Section 2 = Deuxième section de contenu
- etc.

### Format HTML

- Le contenu HTML est accepté pour les champs `intro` et `content`
- Le format est automatiquement défini sur `FORMAT_HTML`
- Assurez-vous que le HTML est valide et sécurisé

### Cache Moodle

- Le cache du cours est automatiquement reconstruit après chaque opération
- Les modifications peuvent prendre quelques secondes à apparaître dans l'interface
- Utilisez `rebuild_course_cache()` si nécessaire

### Limitations

- Les modules doivent être créés dans des sections existantes
- Les sections doivent appartenir au cours spécifié
- Les modules supprimés ne peuvent pas être restaurés via l'API

---

## 🐛 Dépannage

### Le plugin n'apparaît pas dans les services

1. Vérifiez que le plugin est installé (Administration → Plugins → Plugins locaux)
2. Vérifiez que les Web Services sont activés
3. Videz le cache Moodle (Administration → Développement → Purger tous les caches)

### Erreur "Invalid token"

1. Vérifiez que le token est correct
2. Vérifiez que le token n'a pas expiré
3. Régénérez un nouveau token si nécessaire

### Erreur "Access exception"

1. Vérifiez les capacités de l'utilisateur de service
2. Vérifiez que l'utilisateur a les permissions dans le cours
3. Vérifiez que le service est activé

### Les modules ne s'affichent pas

1. Vérifiez que la section existe
2. Videz le cache du cours
3. Vérifiez les logs Moodle pour les erreurs

---

## 📞 Support

Pour toute question ou problème :

1. Consultez les logs Moodle : `Administration → Rapports → Logs`
2. Vérifiez la documentation Moodle sur les Web Services
3. Contactez votre administrateur Moodle

---

## 📄 Licence

Ce plugin est distribué sous la licence GPL v3 ou ultérieure, conformément à Moodle.

---

**Version du plugin** : 1.0  
**Compatibilité** : Moodle/IOMAD 3.9+  
**Dernière mise à jour** : Décembre 2024

