# Documentation SCORM - CrewERP Full Bridge API

## 📋 Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Prérequis](#prérequis)
3. [Fonction `create_scorm`](#fonction-create_scorm)
4. [Workflow d'upload de fichier](#workflow-dupload-de-fichier)
5. [Exemples d'utilisation](#exemples-dutilisation)
6. [Gestion des erreurs](#gestion-des-erreurs)
7. [Détails techniques](#détails-techniques)
8. [Troubleshooting](#troubleshooting)

---

## 🎯 Vue d'ensemble

La fonctionnalité SCORM a été ajoutée au plugin **CrewERP Full Bridge API** pour permettre la création de modules SCORM dans Moodle via l'API Web Services REST. Cette fonctionnalité permet à une application ERP externe (Laravel) d'uploader des packages SCORM et de les intégrer automatiquement dans les cours Moodle.

### Fonctionnalités

- ✅ Upload de packages SCORM (ZIP) via la zone de brouillon Moodle
- ✅ Création automatique de modules SCORM dans les sections de cours
- ✅ Parsing automatique du manifest SCORM
- ✅ Configuration par défaut optimale pour la plupart des cas d'usage
- ✅ Gestion d'erreurs avec nettoyage automatique en cas d'échec

---

## 📦 Prérequis

### 1. Modules Moodle requis

- **Module SCORM** : Le module SCORM doit être installé et activé dans Moodle
  - Vérification : `Administration du site` → `Plugins` → `Modules d'activité` → Vérifier que SCORM est installé

### 2. Permissions requises

L'utilisateur du service web doit avoir la capacité suivante :
- `mod/scorm:addinstance` - Pour créer des modules SCORM

### 3. Configuration du service

Assurez-vous que :
1. Le service "CrewERP Service" est activé
2. La fonction `local_crewerp_api_create_scorm` est incluse dans le service
3. L'utilisateur de service a les permissions nécessaires dans les cours cibles

---

## 🔧 Fonction `create_scorm`

### Description

Crée un nouveau module SCORM dans une section de cours spécifiée. Le package SCORM doit être préalablement uploadé dans la zone de brouillon Moodle via l'API `core_files_upload`.

### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `courseid` | integer | ✅ Oui | ID du cours où créer le module |
| `sectionnum` | integer | ✅ Oui | Numéro de la section (commence à 0) |
| `name` | string | ✅ Oui | Nom du module SCORM |
| `intro` | string (HTML) | ❌ Non | Texte d'introduction (par défaut: '') |
| `draftitemid` | integer | ✅ Oui | ID de la zone de brouillon contenant le fichier SCORM |

### Retour

```json
{
  "id": 125,
  "status": "success"
}
```

- `id` : ID du module de cours créé (cmid)
- `status` : Statut de l'opération ("success")

### Format de réponse d'erreur

```json
{
  "exception": "moodle_exception",
  "errorcode": "errorcreatingscorm",
  "message": "Error message details",
  "debuginfo": "Technical details..."
}
```

---

## 📤 Workflow d'upload de fichier

### Étape 1 : Upload du fichier dans la zone de brouillon

Avant d'appeler `create_scorm`, vous devez d'abord uploader le package SCORM dans la zone de brouillon Moodle en utilisant l'API `core_files_upload`.

#### Exemple avec l'API Moodle

```php
// 1. Upload du fichier SCORM dans la zone de brouillon
$uploadResponse = Http::asMultipart()->post("{$domain}/webservice/rest/server.php", [
    'wstoken' => $token,
    'wsfunction' => 'core_files_upload',
    'moodlewsrestformat' => 'json',
    'contextid' => $usercontextid, // Context ID de l'utilisateur
    'component' => 'user',
    'filearea' => 'draft',
    'itemid' => 0, // 0 pour créer une nouvelle zone de brouillon
    'filepath' => '/',
    'filename' => 'scorm_package.zip',
    'filecontent' => base64_encode(file_get_contents('path/to/scorm_package.zip'))
]);

$uploadResult = $uploadResponse->json();
$draftitemid = $uploadResult['itemid']; // Conserver cet ID
```

#### Exemple avec cURL

```bash
curl -X POST "https://votre-moodle.com/webservice/rest/server.php" \
  -F "wstoken=VOTRE_TOKEN" \
  -F "wsfunction=core_files_upload" \
  -F "moodlewsrestformat=json" \
  -F "contextid=USER_CONTEXT_ID" \
  -F "component=user" \
  -F "filearea=draft" \
  -F "itemid=0" \
  -F "filepath=/" \
  -F "filename=scorm_package.zip" \
  -F "filecontent=$(base64 -w 0 scorm_package.zip)"
```

### Étape 2 : Création du module SCORM

Une fois le fichier uploadé et le `draftitemid` obtenu, appelez `create_scorm` :

```php
// 2. Création du module SCORM
$createResponse = Http::asForm()->post("{$domain}/webservice/rest/server.php", [
    'wstoken' => $token,
    'wsfunction' => 'local_crewerp_api_create_scorm',
    'moodlewsrestformat' => 'json',
    'courseid' => 2,
    'sectionnum' => 1,
    'name' => 'Formation SCORM',
    'intro' => '<p>Description de la formation</p>',
    'draftitemid' => $draftitemid // ID obtenu à l'étape 1
]);

$result = $createResponse->json();
if (isset($result['id'])) {
    echo "Module SCORM créé avec l'ID: " . $result['id'];
}
```

---

## 💻 Exemples d'utilisation

### Exemple 1 : Workflow complet en PHP (Laravel)

```php
<?php

use Illuminate\Support\Facades\Http;

class MoodleScormService
{
    private $domain;
    private $token;
    private $usercontextid;

    public function __construct($domain, $token, $usercontextid)
    {
        $this->domain = $domain;
        $this->token = $token;
        $this->usercontextid = $usercontextid;
    }

    /**
     * Upload un package SCORM et crée un module dans un cours
     *
     * @param int $courseid ID du cours
     * @param int $sectionnum Numéro de section
     * @param string $name Nom du module
     * @param string $filepath Chemin vers le fichier SCORM
     * @param string $intro Description (optionnel)
     * @return array
     */
    public function createScormModule($courseid, $sectionnum, $name, $filepath, $intro = '')
    {
        // Étape 1: Upload du fichier dans la zone de brouillon
        $draftitemid = $this->uploadToDraftArea($filepath);
        
        if (!$draftitemid) {
            throw new Exception('Échec de l\'upload du fichier');
        }

        // Étape 2: Création du module SCORM
        $response = Http::asForm()->post("{$this->domain}/webservice/rest/server.php", [
            'wstoken' => $this->token,
            'wsfunction' => 'local_crewerp_api_create_scorm',
            'moodlewsrestformat' => 'json',
            'courseid' => $courseid,
            'sectionnum' => $sectionnum,
            'name' => $name,
            'intro' => $intro,
            'draftitemid' => $draftitemid
        ]);

        $result = $response->json();

        if (isset($result['exception'])) {
            throw new Exception($result['message']);
        }

        return $result;
    }

    /**
     * Upload un fichier dans la zone de brouillon Moodle
     *
     * @param string $filepath Chemin vers le fichier
     * @return int|null ID de la zone de brouillon
     */
    private function uploadToDraftArea($filepath)
    {
        $filename = basename($filepath);
        $filecontent = base64_encode(file_get_contents($filepath));

        $response = Http::asForm()->post("{$this->domain}/webservice/rest/server.php", [
            'wstoken' => $this->token,
            'wsfunction' => 'core_files_upload',
            'moodlewsrestformat' => 'json',
            'contextid' => $this->usercontextid,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
            'filecontent' => $filecontent
        ]);

        $result = $response->json();

        if (isset($result['itemid'])) {
            return $result['itemid'];
        }

        return null;
    }
}

// Utilisation
$service = new MoodleScormService(
    'https://votre-moodle.com',
    'VOTRE_TOKEN',
    $userContextId
);

try {
    $result = $service->createScormModule(
        courseid: 2,
        sectionnum: 1,
        name: 'Formation e-learning',
        filepath: '/path/to/scorm_package.zip',
        intro: '<p>Formation complète sur le sujet</p>'
    );
    
    echo "Module créé avec succès! ID: " . $result['id'];
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
?>
```

### Exemple 2 : JavaScript/Node.js avec axios

```javascript
const axios = require('axios');
const fs = require('fs');
const FormData = require('form-data');

class MoodleScormClient {
    constructor(domain, token, userContextId) {
        this.domain = domain;
        this.token = token;
        this.userContextId = userContextId;
        this.baseUrl = `${domain}/webservice/rest/server.php`;
    }

    /**
     * Upload un fichier dans la zone de brouillon
     */
    async uploadToDraftArea(filePath) {
        const fileContent = fs.readFileSync(filePath);
        const fileName = filePath.split('/').pop();
        const base64Content = fileContent.toString('base64');

        const formData = new FormData();
        formData.append('wstoken', this.token);
        formData.append('wsfunction', 'core_files_upload');
        formData.append('moodlewsrestformat', 'json');
        formData.append('contextid', this.userContextId);
        formData.append('component', 'user');
        formData.append('filearea', 'draft');
        formData.append('itemid', 0);
        formData.append('filepath', '/');
        formData.append('filename', fileName);
        formData.append('filecontent', base64Content);

        const response = await axios.post(this.baseUrl, formData, {
            headers: formData.getHeaders()
        });

        if (response.data.itemid) {
            return response.data.itemid;
        }
        throw new Error('Échec de l\'upload');
    }

    /**
     * Crée un module SCORM
     */
    async createScormModule(courseId, sectionNum, name, draftItemId, intro = '') {
        const formData = new FormData();
        formData.append('wstoken', this.token);
        formData.append('wsfunction', 'local_crewerp_api_create_scorm');
        formData.append('moodlewsrestformat', 'json');
        formData.append('courseid', courseId);
        formData.append('sectionnum', sectionNum);
        formData.append('name', name);
        formData.append('intro', intro);
        formData.append('draftitemid', draftItemId);

        const response = await axios.post(this.baseUrl, formData, {
            headers: formData.getHeaders()
        });

        if (response.data.exception) {
            throw new Error(response.data.message);
        }

        return response.data;
    }

    /**
     * Workflow complet : upload + création
     */
    async uploadAndCreateScorm(courseId, sectionNum, name, filePath, intro = '') {
        // 1. Upload
        const draftItemId = await this.uploadToDraftArea(filePath);
        console.log(`Fichier uploadé, draftitemid: ${draftItemId}`);

        // 2. Création
        const result = await this.createScormModule(
            courseId,
            sectionNum,
            name,
            draftItemId,
            intro
        );

        return result;
    }
}

// Utilisation
(async () => {
    const client = new MoodleScormClient(
        'https://votre-moodle.com',
        'VOTRE_TOKEN',
        userContextId
    );

    try {
        const result = await client.uploadAndCreateScorm(
            2, // courseid
            1, // sectionnum
            'Formation SCORM',
            './scorm_package.zip',
            '<p>Description</p>'
        );
        console.log('Module créé:', result);
    } catch (error) {
        console.error('Erreur:', error.message);
    }
})();
```

### Exemple 3 : Python avec requests

```python
import requests
import base64
import json

class MoodleScormClient:
    def __init__(self, domain, token, user_context_id):
        self.domain = domain
        self.token = token
        self.user_context_id = user_context_id
        self.base_url = f"{domain}/webservice/rest/server.php"

    def upload_to_draft_area(self, file_path):
        """Upload un fichier dans la zone de brouillon"""
        with open(file_path, 'rb') as f:
            file_content = base64.b64encode(f.read()).decode('utf-8')
        
        filename = file_path.split('/')[-1]
        
        data = {
            'wstoken': self.token,
            'wsfunction': 'core_files_upload',
            'moodlewsrestformat': 'json',
            'contextid': self.user_context_id,
            'component': 'user',
            'filearea': 'draft',
            'itemid': 0,
            'filepath': '/',
            'filename': filename,
            'filecontent': file_content
        }
        
        response = requests.post(self.base_url, data=data)
        result = response.json()
        
        if 'itemid' in result:
            return result['itemid']
        raise Exception('Échec de l\'upload')

    def create_scorm_module(self, course_id, section_num, name, draft_item_id, intro=''):
        """Crée un module SCORM"""
        data = {
            'wstoken': self.token,
            'wsfunction': 'local_crewerp_api_create_scorm',
            'moodlewsrestformat': 'json',
            'courseid': course_id,
            'sectionnum': section_num,
            'name': name,
            'intro': intro,
            'draftitemid': draft_item_id
        }
        
        response = requests.post(self.base_url, data=data)
        result = response.json()
        
        if 'exception' in result:
            raise Exception(result['message'])
        
        return result

    def upload_and_create_scorm(self, course_id, section_num, name, file_path, intro=''):
        """Workflow complet : upload + création"""
        # 1. Upload
        draft_item_id = self.upload_to_draft_area(file_path)
        print(f"Fichier uploadé, draftitemid: {draft_item_id}")
        
        # 2. Création
        result = self.create_scorm_module(
            course_id,
            section_num,
            name,
            draft_item_id,
            intro
        )
        
        return result

# Utilisation
if __name__ == '__main__':
    client = MoodleScormClient(
        'https://votre-moodle.com',
        'VOTRE_TOKEN',
        user_context_id
    )
    
    try:
        result = client.upload_and_create_scorm(
            course_id=2,
            section_num=1,
            name='Formation SCORM',
            file_path='./scorm_package.zip',
            intro='<p>Description</p>'
        )
        print(f"Module créé: {result}")
    except Exception as e:
        print(f"Erreur: {e}")
```

---

## ⚠️ Gestion des erreurs

### Codes d'erreur courants

| Code | Description | Solution |
|------|-------------|----------|
| `nofile` | Aucun fichier trouvé dans la zone de brouillon | Vérifiez que `draftitemid` est correct et que le fichier a été uploadé |
| `errorcreatingscorm` | Erreur lors de la création du module SCORM | Vérifiez les logs Moodle pour plus de détails |
| `errorfilemove` | Échec du déplacement du fichier | Vérifiez les permissions de fichiers et l'espace disque |
| `invalidparameter` | Paramètre invalide | Vérifiez le format et les valeurs des paramètres |
| `accessexception` | Permissions insuffisantes | Vérifiez que l'utilisateur a `mod/scorm:addinstance` |

### Exemple de gestion d'erreurs

```php
try {
    $result = $service->createScormModule(...);
    
    if (isset($result['id'])) {
        // Succès
        echo "Module créé: " . $result['id'];
    }
} catch (Exception $e) {
    // Gestion des erreurs
    $errorCode = $e->getCode();
    $errorMessage = $e->getMessage();
    
    switch ($errorCode) {
        case 'nofile':
            // Réessayer l'upload
            break;
        case 'errorcreatingscorm':
            // Vérifier les logs Moodle
            error_log("Erreur SCORM: " . $errorMessage);
            break;
        default:
            // Erreur générique
            echo "Erreur: " . $errorMessage;
    }
}
```

---

## 🔍 Détails techniques

### Processus interne

1. **Validation des paramètres**
   - Vérification des types et valeurs
   - Validation du contexte du cours

2. **Vérification du fichier**
   - Vérification de l'existence du fichier dans la zone de brouillon
   - Validation que le fichier appartient à l'utilisateur

3. **Création de l'instance SCORM**
   - Création de l'enregistrement dans la table `scorm`
   - Configuration avec valeurs par défaut (voir ci-dessous)

4. **Déplacement du fichier**
   - Utilisation de `file_save_draft_area_files()` pour déplacer le fichier
   - Destination : `mod_scorm/package/0`

5. **Mise à jour des métadonnées**
   - Enregistrement du nom de fichier
   - Enregistrement du hash SHA1
   - Version SCORM par défaut (sera mise à jour lors du parsing)

6. **Parsing du package**
   - Appel de `scorm_parse()` pour extraire le manifest
   - Création de la structure SCORM dans la base de données

7. **Ajout à la section**
   - Ajout du module à la section spécifiée
   - Reconstruction du cache du cours

### Configuration par défaut

Le module SCORM est créé avec les paramètres suivants :

| Paramètre | Valeur | Description |
|-----------|--------|-------------|
| `scormtype` | `SCORM_TYPE_LOCAL` | Type local (fichier uploadé) |
| `maxgrade` | `100` | Note maximale |
| `grademethod` | `GRADEHIGHEST` | Méthode de notation (note la plus haute) |
| `maxattempt` | `0` | Tentatives illimitées |
| `whatgrade` | `0` | Utiliser la note la plus haute |
| `displayattemptstatus` | `1` | Afficher le statut des tentatives |
| `updatefreq` | `SCORM_UPDATE_EVERY` | Mise à jour à chaque accès |
| `skipview` | `SCORM_SKIPVIEW_FIRST` | Passer la vue d'introduction au premier accès |
| `hidetoc` | `SCORM_TOC_SIDE` | Afficher la table des matières sur le côté |
| `nav` | `SCORM_NAV_UNDER` | Navigation sous le contenu |

### Tables de base de données affectées

- `scorm` - Instance SCORM
- `scorm_scoes` - Structure du contenu (après parsing)
- `course_modules` - Module de cours
- `files` - Fichiers (package SCORM)

---

## 🐛 Troubleshooting

### Le fichier n'est pas trouvé dans la zone de brouillon

**Symptôme** : Erreur `nofile`

**Solutions** :
1. Vérifiez que `draftitemid` correspond à un upload récent
2. Vérifiez que l'upload a réussi (vérifiez la réponse de `core_files_upload`)
3. Vérifiez que le fichier n'a pas été supprimé automatiquement (les fichiers de brouillon peuvent expirer)

### Le parsing SCORM échoue

**Symptôme** : Module créé mais ne fonctionne pas

**Solutions** :
1. Vérifiez que le package SCORM est valide (ZIP avec manifest.xml)
2. Vérifiez les logs Moodle pour les erreurs de parsing
3. Testez le package SCORM manuellement dans Moodle pour vérifier sa validité

### Erreur de permissions

**Symptôme** : Erreur `accessexception`

**Solutions** :
1. Vérifiez que l'utilisateur a la capacité `mod/scorm:addinstance`
2. Vérifiez que l'utilisateur a les permissions dans le cours
3. Vérifiez que le service est activé et contient la fonction

### Le module est créé mais le fichier est manquant

**Symptôme** : Module visible mais erreur lors de l'ouverture

**Solutions** :
1. Vérifiez les permissions du répertoire de fichiers Moodle
2. Vérifiez l'espace disque disponible
3. Consultez les logs Moodle pour les erreurs de fichiers

### Performance lente

**Symptôme** : La création prend beaucoup de temps

**Causes possibles** :
1. Package SCORM très volumineux
2. Parsing complexe du manifest
3. Problèmes de réseau lors du déplacement du fichier

**Solutions** :
1. Optimisez la taille du package SCORM
2. Augmentez les limites PHP (memory_limit, max_execution_time)
3. Vérifiez la performance du serveur de fichiers

---

## 📝 Notes importantes

### Format de fichier SCORM

- Le package doit être un fichier ZIP valide
- Le ZIP doit contenir un fichier `imsmanifest.xml` à la racine
- Formats supportés : SCORM 1.2 et SCORM 2004

### Zone de brouillon

- Les fichiers dans la zone de brouillon peuvent être supprimés automatiquement après un certain temps
- Utilisez le `draftitemid` immédiatement après l'upload
- Ne réutilisez pas un `draftitemid` ancien

### Parsing SCORM

- Le parsing est effectué automatiquement lors de la création
- Le parsing peut prendre du temps pour les packages volumineux
- En cas d'échec du parsing, le module sera créé mais ne fonctionnera pas correctement

### Limitations

- Un seul fichier par zone de brouillon (le premier fichier est utilisé)
- Le nom du fichier doit être valide (pas de caractères spéciaux)
- Taille maximale limitée par les paramètres PHP de Moodle

---

## 🔗 Ressources supplémentaires

- [Documentation Moodle SCORM](https://docs.moodle.org/en/SCORM_module)
- [API Moodle Web Services](https://docs.moodle.org/dev/Web_services_API)
- [Format SCORM](https://scorm.com/scorm-explained/)

---

**Version** : 1.0  
**Date** : Décembre 2024  
**Compatibilité** : Moodle/IOMAD 3.9+

