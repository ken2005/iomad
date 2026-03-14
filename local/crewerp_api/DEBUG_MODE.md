# Mode DEBUG - CrewERP Ultimate Bridge API

## 🔍 Vue d'ensemble

Cette version **DEBUG MODE** du plugin a été créée pour faciliter le débogage en supprimant toutes les couches de gestion d'erreurs personnalisées et en permettant aux exceptions Moodle natives de remonter directement dans les réponses de l'API Web Services.

**Version** : 2.1 DEBUG  
**Date** : Décembre 2024  
**Type** : Version de débogage

---

## 🎯 Objectifs du mode DEBUG

### Problème résolu

**Avant** : Les erreurs étaient masquées par des messages génériques comme `Exception: {$a}` ou `errorcreatinglabel`, ce qui rendait le débogage difficile.

**Maintenant** : Les exceptions Moodle natives (comme `nopermissions`, `invalidcourse`, `invalidparameter`) remontent directement dans la réponse de l'API, permettant d'identifier rapidement la cause exacte des erreurs.

---

## ✨ Modifications principales

### 1. Suppression des try/catch dans `add_to_course()`

**Avant** :
```php
private static function add_to_course($module, $modulename) {
    try {
        $instanceid = call_user_func($addfunction, $module, null);
        // ...
    } catch (Exception $e) {
        throw new moodle_exception('errorcreatingmodule', 'local_crewerp_api', '', $e->getMessage());
    }
}
```

**Maintenant (DEBUG MODE)** :
```php
private static function add_to_course($module, $modulename) {
    // RAW CALL - No Try/Catch - Let native exceptions bubble up
    $module->instance = call_user_func($add_instance_function, $module, null);
    
    if (!$module->instance) {
        throw new moodle_exception('errorcreatingmodule', 'local_crewerp_api', '', $modulename);
    }
    // ...
}
```

**Avantages** :
- ✅ Les exceptions Moodle natives remontent directement
- ✅ Messages d'erreur Moodle originaux visibles
- ✅ Identification rapide des problèmes de permissions, paramètres invalides, etc.

### 2. Création automatique des sections manquantes

**Nouvelle fonctionnalité** :
```php
// 2. Auto-Create Section if missing
$target_section = isset($module->section) ? (int)$module->section : 0;
if ($target_section > 0) {
    course_create_sections_if_missing($course, $target_section);
}
```

**Avantages** :
- ✅ Plus besoin de créer les sections manuellement avant d'ajouter des modules
- ✅ Création automatique si la section demandée n'existe pas
- ✅ Évite les erreurs "Section not found"

### 3. Génération automatique du nom pour les Labels

**Problème résolu** : Moodle DB requiert un champ `name` pour tous les modules, mais les Labels n'ont que `intro`. Cela causait des erreurs de contrainte DB.

**Solution** :
```php
// LABEL FIX: Moodle DB requires a name, but Label only has intro.
// We generate a name from intro to avoid DB errors.
if (!isset($module->name) && isset($module->intro)) {
    $module->name = strip_tags($module->intro);
    if (strlen($module->name) > 20) {
        $module->name = substr($module->name, 0, 20) . '...';
    }
    if (empty($module->name)) {
        $module->name = 'Label';
    }
}
```

**Logique** :
1. Extrait le texte du HTML (`strip_tags`)
2. Limite à 20 caractères avec "..." si nécessaire
3. Utilise "Label" par défaut si le contenu est vide

**Avantages** :
- ✅ Évite les erreurs de contrainte DB
- ✅ Génération automatique du nom
- ✅ Nom lisible basé sur le contenu

### 4. Fonctions en mode DEBUG

**Fonctions complètement implémentées** :
- ✅ `create_label()` - Fonctionnelle, sans try/catch
- ✅ `create_url()` - Fonctionnelle, sans try/catch

**Fonctions en mode stub** (retournent `debug_mode`) :
- ⚠️ `update_url()` - Stub
- ⚠️ `update_label()` - Stub
- ⚠️ `create_scorm()` - Stub
- ⚠️ `create_quiz()` - Stub
- ⚠️ `delete_module()` - Stub
- ⚠️ `update_section()` - Stub

**Note** : Les stubs sont nécessaires pour éviter les erreurs fatales PHP si le service web attend ces fonctions. Ils retournent simplement `{'status': 'debug_mode'}`.

---

## 📋 Exemples de messages d'erreur

### Exemple 1 : Erreur de permissions

**Avant (masqué)** :
```json
{
  "exception": "moodle_exception",
  "errorcode": "errorcreatinglabel",
  "message": "Error creating Label module"
}
```

**Maintenant (DEBUG MODE)** :
```json
{
  "exception": "moodle_exception",
  "errorcode": "nopermissions",
  "message": "You do not have permission to add instances of this activity.",
  "debuginfo": "mod/label:addinstance"
}
```

### Exemple 2 : Cours invalide

**Avant (masqué)** :
```json
{
  "exception": "moodle_exception",
  "errorcode": "errorcreatinglabel",
  "message": "Error creating Label module"
}
```

**Maintenant (DEBUG MODE)** :
```json
{
  "exception": "dml_missing_record_exception",
  "errorcode": "invalidcourseid",
  "message": "Can't find data record in database table course.",
  "debuginfo": "SELECT * FROM mdl_course WHERE id = ?"
}
```

### Exemple 3 : Section manquante (maintenant auto-créée)

**Avant** :
```json
{
  "exception": "moodle_exception",
  "errorcode": "errorcreatinglabel",
  "message": "Error adding to section 5: Section not found"
}
```

**Maintenant (DEBUG MODE)** :
- La section est automatiquement créée
- Plus d'erreur "Section not found"
- Le module est créé avec succès

### Exemple 4 : Paramètre invalide

**Avant (masqué)** :
```json
{
  "exception": "moodle_exception",
  "errorcode": "errorcreatinglabel",
  "message": "Error creating Label module"
}
```

**Maintenant (DEBUG MODE)** :
```json
{
  "exception": "invalid_parameter_exception",
  "errorcode": "invalidparameter",
  "message": "Invalid parameter value detected",
  "debuginfo": "content: Empty value not allowed"
}
```

---

## 🔧 Utilisation

### Pour déboguer une erreur

1. **Appelez la fonction** via l'API Web Services
2. **Observez la réponse** - Les exceptions Moodle natives seront visibles
3. **Identifiez le code d'erreur** - Par exemple : `nopermissions`, `invalidcourse`, `invalidparameter`
4. **Consultez le message** - Le message Moodle original vous indiquera la cause exacte
5. **Vérifiez debuginfo** - Contient souvent des détails techniques utiles

### Exemple de requête

```bash
POST /webservice/rest/server.php?wstoken=TOKEN&wsfunction=local_crewerp_api_create_label&moodlewsrestformat=json

{
  "courseid": 2,
  "sectionnum": 1,
  "content": "<p>Test label</p>"
}
```

### Réponse en cas d'erreur (DEBUG MODE)

```json
{
  "exception": "moodle_exception",
  "errorcode": "nopermissions",
  "message": "You do not have permission to add instances of this activity.",
  "debuginfo": "mod/label:addinstance"
}
```

---

## ⚠️ Notes importantes

### 1. Mode DEBUG uniquement

⚠️ **Cette version est destinée au débogage uniquement**. Elle ne doit pas être utilisée en production car :
- Les exceptions détaillées peuvent révéler des informations sensibles
- Pas de gestion d'erreurs gracieuse
- Les stubs retournent `debug_mode` au lieu de fonctionner réellement

### 2. Fonctions fonctionnelles

Seules ces fonctions sont complètement fonctionnelles en mode DEBUG :
- `create_label()`
- `create_url()`

Toutes les autres fonctions retournent `{'status': 'debug_mode'}`.

### 3. Création automatique des sections

Les sections sont maintenant créées automatiquement si elles n'existent pas. Cela évite les erreurs mais peut créer des sections inattendues si vous spécifiez un numéro de section trop élevé.

### 4. Génération du nom pour Labels

Le nom est généré automatiquement à partir du contenu. Si vous avez besoin d'un nom spécifique, vous devrez modifier le code pour ajouter cette fonctionnalité.

---

## 🔄 Retour à la version normale

Pour revenir à la version normale avec gestion d'erreurs complète :

1. Restaurez le fichier `externallib.php` de la version 2.1 normale
2. Les try/catch seront restaurés
3. Les messages d'erreur personnalisés seront de retour
4. Toutes les fonctions seront complètement implémentées

---

## 📝 Fichiers modifiés

### `local/crewerp_api/externallib.php`

**Changements** :
- ✅ Suppression de tous les try/catch dans `add_to_course()`
- ✅ Ajout de `course_create_sections_if_missing()`
- ✅ Ajout de la logique de génération de nom pour Labels
- ✅ `create_label()` et `create_url()` sans try/catch
- ✅ Autres fonctions en mode stub

---

## 🐛 Codes d'erreur Moodle courants

Voici les codes d'erreur Moodle que vous pourriez voir en mode DEBUG :

| Code | Description | Solution |
|------|-------------|----------|
| `nopermissions` | Permissions insuffisantes | Vérifiez les capacités de l'utilisateur |
| `invalidcourseid` | Cours introuvable | Vérifiez que le courseid existe |
| `invalidparameter` | Paramètre invalide | Vérifiez le format des paramètres |
| `dml_missing_record_exception` | Enregistrement manquant en DB | Vérifiez que la ressource existe |
| `invalid_parameter_exception` | Exception de paramètre invalide | Vérifiez les valeurs des paramètres |

---

## 📚 Documentation associée

- **API Documentation** : `API_DOCUMENTATION.md` - Documentation complète de l'API
- **SCORM Documentation** : `SCORM_DOCUMENTATION.md` - Documentation spécifique SCORM
- **Changelog** : `CHANGELOG.md` - Historique des versions
- **README** : `README.md` - Guide d'installation et d'utilisation

---

## 🚀 Prochaines étapes

Une fois le débogage terminé :

1. **Identifiez la cause** de l'erreur grâce aux messages Moodle natifs
2. **Corrigez le problème** (permissions, paramètres, etc.)
3. **Restaurez la version normale** avec gestion d'erreurs complète
4. **Testez à nouveau** pour confirmer que le problème est résolu

---

**Version** : 2.1 DEBUG  
**Compatibilité** : Moodle/IOMAD 3.9+  
**Usage** : Débogage uniquement  
**Dernière mise à jour** : Décembre 2024

