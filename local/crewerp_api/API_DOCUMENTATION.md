# CrewERP Ultimate Bridge API - Documentation Complète

## 📋 Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Installation et mise à jour](#installation-et-mise-à-jour)
3. [Fonctions disponibles](#fonctions-disponibles)
4. [Exemples d'utilisation](#exemples-dutilisation)
5. [Gestion des erreurs](#gestion-des-erreurs)
6. [Architecture technique](#architecture-technique)

---

## 🎯 Vue d'ensemble

Le plugin **CrewERP Ultimate Bridge API** (version 2.0) est un module local Moodle/IOMAD (version 3.9+) qui permet à une application ERP externe (Laravel) d'effectuer des opérations CRUD complètes sur les modules de cours via l'API Web Services REST de Moodle.

### Fonctionnalités principales

- ✅ **URL Modules** : Création et mise à jour de modules URL
- ✅ **Label Modules** : Création et mise à jour de modules Label avec contenu HTML
- ✅ **SCORM Modules** : Création de modules SCORM avec packages uploadés
- ✅ **Quiz Modules** : Création de quiz vides (shell)
- ✅ **Gestion des modules** : Suppression de modules de cours
- ✅ **Gestion des sections** : Mise à jour des noms de sections

### Version actuelle

- **Version** : 2.0 (2025122802)
- **Compatibilité** : Moodle/IOMAD 3.9+
- **Maturité** : STABLE

---

## 📦 Installation et mise à jour

### Installation initiale

1. Copiez le dossier `local/crewerp_api` dans le répertoire `local/` de votre installation Moodle
2. Connectez-vous en tant qu'administrateur
3. Allez dans **Administration du site** → **Notifications**
4. Cliquez sur **Mettre à jour la base de données maintenant**

### Mise à jour

La version 2.0 force automatiquement une mise à jour lors de l'installation. Assurez-vous de :

1. Sauvegarder votre base de données
2. Vider le cache Moodle après la mise à jour
3. Vérifier que toutes les fonctions sont disponibles dans le service

### Configuration

1. **Activer les Web Services** : Administration → Fonctionnalités avancées
2. **Activer REST** : Cochez le protocole REST
3. **Créer un token** : Administration → Plugins → Services web → Gérer les tokens
4. **Activer le service** : Vérifiez que "CrewERP Service" est activé

---

## 🔧 Fonctions disponibles

### 1. `local_crewerp_api_create_url`

Crée un nouveau module URL dans une section de cours.

#### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `courseid` | integer | ✅ | ID du cours |
| `sectionnum` | integer | ✅ | Numéro de la section (commence à 0) |
| `name` | string | ✅ | Nom du module |
| `url` | string (URL) | ✅ | URL à lier |
| `intro` | string (HTML) | ❌ | Texte d'introduction (défaut: '') |

#### Retour

```json
{
  "id": 123,
  "status": "success"
}
```

#### Capacité requise

- `mod/url:addinstance`

---

### 2. `local_crewerp_api_update_url`

Met à jour un module URL existant.

#### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `cmid` | integer | ✅ | ID du module de cours |
| `name` | string | ❌ | Nouveau nom du module |
| `url` | string (URL) | ❌ | Nouvelle URL |
| `intro` | string (HTML) | ❌ | Nouveau texte d'introduction |

**Note** : Au moins un paramètre optionnel doit être fourni.

#### Retour

```json
{
  "status": "success"
}
```

#### Capacité requise

- `mod/url:addinstance`

---

### 3. `local_crewerp_api_create_label`

Crée un nouveau module Label avec contenu HTML.

#### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `courseid` | integer | ✅ | ID du cours |
| `sectionnum` | integer | ✅ | Numéro de la section |
| `content` | string (HTML) | ✅ | Contenu HTML du label |

#### Retour

```json
{
  "id": 124,
  "status": "success"
}
```

#### Capacité requise

- `mod/label:addinstance`

---

### 4. `local_crewerp_api_update_label`

Met à jour le contenu d'un module Label existant.

#### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `cmid` | integer | ✅ | ID du module de cours |
| `content` | string (HTML) | ✅ | Nouveau contenu HTML |

#### Retour

```json
{
  "status": "success"
}
```

#### Capacité requise

- `mod/label:addinstance`

---

### 5. `local_crewerp_api_create_scorm`

Crée un nouveau module SCORM avec un package uploadé.

> 📖 **Documentation détaillée** : Voir [SCORM_DOCUMENTATION.md](SCORM_DOCUMENTATION.md)

#### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `courseid` | integer | ✅ | ID du cours |
| `sectionid` | integer | ✅ | Numéro de la section |
| `name` | string | ✅ | Nom du module |
| `draftitemid` | integer | ✅ | ID de la zone de brouillon contenant le fichier SCORM |

#### Retour

```json
{
  "id": 125,
  "status": "success"
}
```

#### Workflow

1. **Upload du fichier** : Utilisez `core_files_upload` pour uploader le package SCORM
2. **Création du module** : Appelez `create_scorm` avec le `draftitemid` obtenu

#### Capacité requise

- `mod/scorm:addinstance`

#### Prérequis

- Module SCORM installé dans Moodle
- Package SCORM valide (ZIP avec manifest.xml)

---

### 6. `local_crewerp_api_create_quiz`

Crée un nouveau module Quiz vide (shell).

#### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `courseid` | integer | ✅ | ID du cours |
| `sectionid` | integer | ✅ | Numéro de la section |
| `name` | string | ✅ | Nom du quiz |

#### Retour

```json
{
  "id": 126,
  "status": "success"
}
```

#### Configuration par défaut

Le quiz est créé avec les paramètres suivants :

- `timeopen` : 0 (pas de date d'ouverture)
- `timeclose` : 0 (pas de date de fermeture)
- `attempts` : 0 (tentatives illimitées)
- `grademethod` : QUIZ_GRADEHIGHEST (note la plus haute)
- `grade` : 100 (note maximale)
- `preferredbehaviour` : 'deferredfeedback'
- `questionsperpage` : 1

#### Capacité requise

- `mod/quiz:addinstance`

#### Note

Le quiz créé est un shell vide. Vous devrez ajouter des questions manuellement ou via d'autres APIs Moodle.

---

### 7. `local_crewerp_api_delete_module`

Supprime un module de cours (tous types).

#### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `cmid` | integer | ✅ | ID du module de cours à supprimer |

#### Retour

```json
{
  "status": "success"
}
```

#### Capacité requise

- `moodle/course:manageactivities`

#### ⚠️ Attention

La suppression est définitive et ne peut pas être annulée.

---

### 8. `local_crewerp_api_update_section`

Met à jour le nom d'une section de cours.

#### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `courseid` | integer | ✅ | ID du cours |
| `sectionnum` | integer | ✅ | Numéro de la section |
| `name` | string | ✅ | Nouveau nom de la section |

#### Retour

```json
{
  "status": "success"
}
```

#### Capacité requise

- `moodle/course:update`

---

## 💻 Exemples d'utilisation

### Exemple 1 : Créer un module URL (PHP/Laravel)

```php
<?php
use Illuminate\Support\Facades\Http;

$token = 'VOTRE_TOKEN';
$domain = 'https://votre-moodle.com';

$response = Http::asForm()->post("{$domain}/webservice/rest/server.php", [
    'wstoken' => $token,
    'wsfunction' => 'local_crewerp_api_create_url',
    'moodlewsrestformat' => 'json',
    'courseid' => 2,
    'sectionnum' => 1,
    'name' => 'Documentation API',
    'url' => 'https://api.example.com/docs',
    'intro' => '<p>Accédez à la documentation complète</p>'
]);

$result = $response->json();
if (isset($result['id'])) {
    echo "Module créé avec l'ID: " . $result['id'];
}
?>
```

### Exemple 2 : Créer un Label (JavaScript)

```javascript
async function createLabel(token, domain, courseId, sectionNum, content) {
    const formData = new FormData();
    formData.append('wstoken', token);
    formData.append('wsfunction', 'local_crewerp_api_create_label');
    formData.append('moodlewsrestformat', 'json');
    formData.append('courseid', courseId);
    formData.append('sectionnum', sectionNum);
    formData.append('content', content);

    const response = await fetch(`${domain}/webservice/rest/server.php`, {
        method: 'POST',
        body: formData
    });

    const result = await response.json();
    return result;
}

// Utilisation
createLabel(
    'VOTRE_TOKEN',
    'https://votre-moodle.com',
    2,
    1,
    '<h2>Titre</h2><p>Contenu du label</p>'
);
```

### Exemple 3 : Créer un Quiz (Python)

```python
import requests

def create_quiz(domain, token, course_id, section_id, name):
    url = f"{domain}/webservice/rest/server.php"
    data = {
        'wstoken': token,
        'wsfunction': 'local_crewerp_api_create_quiz',
        'moodlewsrestformat': 'json',
        'courseid': course_id,
        'sectionid': section_id,
        'name': name
    }
    
    response = requests.post(url, data=data)
    return response.json()

# Utilisation
result = create_quiz(
    'https://votre-moodle.com',
    'VOTRE_TOKEN',
    course_id=2,
    section_id=1,
    name='Quiz de test'
)
print(f"Quiz créé: {result}")
```

### Exemple 4 : Workflow SCORM complet (PHP)

```php
<?php
// Étape 1: Upload du fichier SCORM
$uploadResponse = Http::asForm()->post("{$domain}/webservice/rest/server.php", [
    'wstoken' => $token,
    'wsfunction' => 'core_files_upload',
    'moodlewsrestformat' => 'json',
    'contextid' => $userContextId,
    'component' => 'user',
    'filearea' => 'draft',
    'itemid' => 0,
    'filepath' => '/',
    'filename' => 'scorm_package.zip',
    'filecontent' => base64_encode(file_get_contents('scorm.zip'))
]);

$draftItemId = $uploadResponse->json()['itemid'];

// Étape 2: Création du module SCORM
$createResponse = Http::asForm()->post("{$domain}/webservice/rest/server.php", [
    'wstoken' => $token,
    'wsfunction' => 'local_crewerp_api_create_scorm',
    'moodlewsrestformat' => 'json',
    'courseid' => 2,
    'sectionid' => 1,
    'name' => 'Formation SCORM',
    'draftitemid' => $draftItemId
]);

$result = $createResponse->json();
echo "Module SCORM créé: " . $result['id'];
?>
```

### Exemple 5 : Mettre à jour une section

```php
<?php
$response = Http::asForm()->post("{$domain}/webservice/rest/server.php", [
    'wstoken' => $token,
    'wsfunction' => 'local_crewerp_api_update_section',
    'moodlewsrestformat' => 'json',
    'courseid' => 2,
    'sectionnum' => 1,
    'name' => 'Nouvelle section'
]);

$result = $response->json();
?>
```

---

## ⚠️ Gestion des erreurs

### Codes d'erreur courants

| Code | Description | Solution |
|------|-------------|----------|
| `invalidtoken` | Token invalide ou expiré | Régénérez le token |
| `accessexception` | Permissions insuffisantes | Vérifiez les capacités de l'utilisateur |
| `invalidparameter` | Paramètre invalide | Vérifiez le format des paramètres |
| `invalidcourse` | Cours introuvable | Vérifiez que le courseid existe |
| `invalidcoursemodule` | Module introuvable | Vérifiez que le cmid existe |
| `nofile` | Fichier introuvable dans la zone de brouillon | Vérifiez le draftitemid |
| `errorcreatingmodule` | Erreur lors de la création | Consultez les logs Moodle |
| `errorfilemove` | Échec du déplacement de fichier | Vérifiez les permissions et l'espace disque |

### Format de réponse d'erreur

```json
{
  "exception": "moodle_exception",
  "errorcode": "invalidparameter",
  "message": "Invalid parameter value detected",
  "debuginfo": "Details techniques..."
}
```

### Exemple de gestion d'erreurs

```php
<?php
try {
    $response = Http::asForm()->post(...);
    $result = $response->json();
    
    if (isset($result['exception'])) {
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
                error_log("Erreur API: {$errorCode} - {$errorMessage}");
        }
        
        throw new Exception($errorMessage);
    }
    
    // Succès
    return $result;
} catch (Exception $e) {
    // Gestion de l'erreur
    echo "Erreur: " . $e->getMessage();
}
?>
```

---

## 🏗️ Architecture technique

### Structure du plugin

```
local/crewerp_api/
├── version.php          # Métadonnées et version
├── externallib.php      # Logique métier et fonctions API
├── db/
│   └── services.php     # Définition des services web
├── lang/
│   └── en/
│       └── local_crewerp_api.php  # Chaînes de langue
├── README.md           # Documentation principale
├── SCORM_DOCUMENTATION.md  # Documentation SCORM
└── API_DOCUMENTATION.md    # Cette documentation
```

### Helper `add_to_course()`

Le plugin utilise un helper interne `add_to_course()` pour éviter la duplication de code lors de la création de modules simples (URL, Label). Ce helper :

1. Récupère l'ID du module
2. Appelle la fonction `add_instance` appropriée
3. Ajoute le module à la section
4. Reconstruit le cache du cours

### Flux d'exécution

```
Requête REST
    ↓
Validation du token
    ↓
Chargement du service (db/services.php)
    ↓
Exécution de la fonction (externallib.php)
    ↓
Validation des paramètres
    ↓
Vérification des permissions
    ↓
Exécution de la logique métier
    ↓
Mise à jour du cache
    ↓
Retour de la réponse JSON
```

### Dépendances Moodle

- `mod_url_add_instance()` / `mod_url_update_instance()`
- `mod_label_add_instance()` / `mod_label_update_instance()`
- `scorm_add_instance()` / `scorm_parse()`
- `quiz_add_instance()`
- `course_delete_module()`
- `course_add_cm_to_section()`
- `file_save_draft_area_files()`
- `rebuild_course_cache()`

### Tables de base de données

Les fonctions affectent les tables suivantes :

- `course_modules` - Modules de cours
- `url` - Instances URL
- `label` - Instances Label
- `scorm` / `scorm_scoes` - Instances SCORM
- `quiz` - Instances Quiz
- `course_sections` - Sections de cours
- `files` - Fichiers (pour SCORM)

---

## 📝 Notes importantes

### Numérotation des sections

⚠️ **Important** : Les sections dans Moodle commencent à **0** :
- Section 0 = Section générale
- Section 1 = Première section de contenu
- Section 2 = Deuxième section de contenu

### Différence entre `sectionnum` et `sectionid`

- **`sectionnum`** : Utilisé pour URL, Label, update_section (numéro de section)
- **`sectionid`** : Utilisé pour SCORM et Quiz (numéro de section, même chose mais nom différent pour cohérence)

### Format HTML

- Le contenu HTML est accepté pour les champs `intro` et `content`
- Le format est automatiquement défini sur `FORMAT_HTML`
- Assurez-vous que le HTML est valide et sécurisé

### Cache Moodle

- Le cache du cours est automatiquement reconstruit après chaque opération
- Les modifications peuvent prendre quelques secondes à apparaître
- Utilisez `rebuild_course_cache()` si nécessaire

### Limitations

- Les modules doivent être créés dans des sections existantes
- Les quiz créés sont vides (shell) - ajoutez les questions séparément
- Les packages SCORM doivent être valides (ZIP avec manifest.xml)
- Les modules supprimés ne peuvent pas être restaurés via l'API

---

## 🔒 Sécurité

### Permissions requises

| Fonction | Capacité |
|----------|----------|
| `create_url` / `update_url` | `mod/url:addinstance` |
| `create_label` / `update_label` | `mod/label:addinstance` |
| `create_scorm` | `mod/scorm:addinstance` |
| `create_quiz` | `mod/quiz:addinstance` |
| `delete_module` | `moodle/course:manageactivities` |
| `update_section` | `moodle/course:update` |

### Bonnes pratiques

1. **Utilisez un utilisateur dédié** pour l'API
2. **Limitez les permissions** aux capacités nécessaires
3. **Utilisez HTTPS** pour tous les appels API
4. **Validez les entrées** côté client avant l'envoi
5. **Surveillez les logs** Moodle régulièrement
6. **Régénérez les tokens** périodiquement
7. **Restreignez par IP** si possible

---

## 🐛 Dépannage

### Le plugin n'apparaît pas dans les services

1. Vérifiez l'installation (Administration → Plugins → Plugins locaux)
2. Vérifiez que les Web Services sont activés
3. Videz le cache Moodle

### Erreur "Invalid token"

1. Vérifiez que le token est correct
2. Vérifiez que le token n'a pas expiré
3. Régénérez un nouveau token

### Erreur "Access exception"

1. Vérifiez les capacités de l'utilisateur de service
2. Vérifiez que l'utilisateur a les permissions dans le cours
3. Vérifiez que le service est activé

### Les modules ne s'affichent pas

1. Vérifiez que la section existe
2. Videz le cache du cours
3. Consultez les logs Moodle

### Erreur lors de la création SCORM

1. Vérifiez que le package SCORM est valide
2. Vérifiez que le `draftitemid` est correct
3. Vérifiez les permissions de fichiers
4. Consultez les logs Moodle pour les détails

---

## 📞 Support

Pour toute question ou problème :

1. Consultez les logs Moodle : **Administration → Rapports → Logs**
2. Vérifiez la documentation Moodle sur les Web Services
3. Contactez votre administrateur Moodle

---

## 📄 Changelog

### Version 2.0 (2025122802)

- ✅ Ajout de la fonction `create_quiz`
- ✅ Correction des conventions de nommage
- ✅ Ajout du helper `add_to_course()` pour éviter la duplication
- ✅ Mise à jour du nom du plugin : "CrewERP Ultimate Bridge API"
- ✅ Correction des paramètres SCORM (sectionid)

### Version 1.1 (2024120101)

- ✅ Ajout du support SCORM
- ✅ Documentation SCORM complète

### Version 1.0 (2024120100)

- ✅ Version initiale
- ✅ Support URL et Label
- ✅ Gestion des sections

---

## 📄 Licence

Ce plugin est distribué sous la licence GPL v3 ou ultérieure, conformément à Moodle.

---

**Version du plugin** : 2.0  
**Compatibilité** : Moodle/IOMAD 3.9+  
**Dernière mise à jour** : Décembre 2024

