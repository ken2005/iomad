# Debug Mode - Tracer Version

## 🎯 Vue d'ensemble

Ce document décrit la version **"Tracer"** de la fonction `add_to_course()` utilisée pour déboguer l'erreur persistante `Object of class stdClass could not be converted to string`.

**Date** : Décembre 2024  
**Type** : Version de débogage  
**Objectif** : Identifier précisément l'étape exacte qui provoque l'erreur

---

## 🔍 Principe de fonctionnement

### Concept

La version "Tracer" enveloppe **chaque étape** (STEP 1 à STEP 10) dans un bloc `try/catch (\Throwable $e)`. Si une erreur se produit à n'importe quelle étape, elle lance une exception spécifique indiquant **"CRASH AT STEP X"**, permettant d'identifier précisément la ligne problématique.

### Correction importante : Ordre des arguments

**Version V2 (Corrigée)** : Le message d'erreur est passé en **4ème argument** à `moodle_exception` :

```php
// ✅ CORRECT (V2)
throw new moodle_exception('generalexceptionmessage', 'error', '', "CRASH AT STEP X: " . $e->getMessage());
```

**Signature de moodle_exception** :
```php
moodle_exception($errorcode, $module, $link, $a = null, $debuginfo = null)
```

- **4ème argument (`$a`)** : Remplace le placeholder `{$a}` dans la string de langue
- **5ème argument (`$debuginfo`)** : Information de debug supplémentaire (non affichée dans le message principal)

**Avant (V1 - Incorrect)** :
```php
// ❌ INCORRECT (V1)
throw new moodle_exception('generalexceptionmessage', 'error', '', null, "CRASH AT STEP X: " . $e->getMessage());
// Résultat : "Exception : {$a}" (le message n'est pas affiché)
```

### Avantages

1. **Précision** : Identifie exactement l'étape qui échoue
2. **Message d'erreur clair** : "CRASH AT STEP X" + message d'erreur original (maintenant correctement affiché)
3. **Capture complète** : Capture toutes les exceptions et erreurs (`\Throwable`)
4. **Isolation** : Chaque étape est isolée, facilitant le débogage

---

## 📋 Les 10 étapes tracées

### STEP 1: LOAD COURSE
```php
try {
    $course = $DB->get_record('course', array('id' => $module->course), '*', MUST_EXIST);
} catch (\Throwable $e) {
    throw new moodle_exception(..., "CRASH AT STEP 1 (Get Course): " . $e->getMessage());
}
```
**Objectif** : Charger le cours depuis la base de données

---

### STEP 2: SECTIONS
```php
try {
    $target_section = isset($module->section) ? (int)$module->section : 0;
    if ($target_section > 0) {
        course_create_sections_if_missing($course, $target_section);
    }
} catch (\Throwable $e) {
    throw new moodle_exception(..., "CRASH AT STEP 2 (Sections): " . $e->getMessage());
}
```
**Objectif** : Créer les sections manquantes si nécessaire

---

### STEP 3: PREPARE DATA
```php
try {
    $clean_modulename = str_replace('mod_', '', $modulename);
    $module->modulename = $clean_modulename;
    $module_type_id = $DB->get_field('modules', 'id', array('name' => $clean_modulename), MUST_EXIST);
    $module->module = $module_type_id;
    $module->section = $target_section;
    $module->visible = 1;
} catch (\Throwable $e) {
    throw new moodle_exception(..., "CRASH AT STEP 3 (Data Prep): " . $e->getMessage());
}
```
**Objectif** : Préparer les données de base du module

---

### STEP 4: LOAD LIB
```php
try {
    $libfile = $CFG->dirroot . "/mod/" . $clean_modulename . "/lib.php";
    if (file_exists($libfile)) {
        require_once($libfile);
    } else {
        throw new \Exception("Library not found: $libfile");
    }
} catch (\Throwable $e) {
    throw new moodle_exception(..., "CRASH AT STEP 4 (Load Lib): " . $e->getMessage());
}
```
**Objectif** : Charger la bibliothèque du module

---

### STEP 5: ADD INSTANCE
```php
try {
    // FIX LABEL NAME
    if ($clean_modulename === 'label') {
        if (!isset($module->name) || empty($module->name)) {
            $module->name = substr(strip_tags($module->intro), 0, 30) ?: 'Label';
        }
    }
    // Force string cast
    $module->name = (string)$module->name;
    
    $add_instance_function = $clean_modulename . '_add_instance';
    if (function_exists($add_instance_function)) {
        $instance_id = call_user_func($add_instance_function, $module, null);
    } else {
        throw new \Exception("Function $add_instance_function not found");
    }
} catch (\Throwable $e) {
    throw new moodle_exception(..., "CRASH AT STEP 5 (Add Instance): " . $e->getMessage());
}
```
**Objectif** : Créer l'instance du module (label, url, etc.)

---

### STEP 6: SANITIZE / PREPARE CM RECORD
```php
$cm_record = new \stdClass();
try {
    $cm_record->course = (int)$course->id;
    $cm_record->module = (int)$module_type_id;
    $cm_record->instance = (int)$instance_id;
    $cm_record->section = (int)$target_section;
    $cm_record->visible = 1;
    $cm_record->modulename = (string)$clean_modulename;
    if (isset($module->name)) {
        $cm_record->name = (string)$module->name;
    }
} catch (\Throwable $e) {
    throw new moodle_exception(..., "CRASH AT STEP 6 (Sanitize): " . $e->getMessage());
}
```
**Objectif** : Créer un objet propre pour le Course Module

---

### STEP 7: ADD COURSE MODULE
```php
$cmid = 0;
try {
    $cmid = add_course_module($cm_record);
} catch (\Throwable $e) {
    throw new moodle_exception(..., "CRASH AT STEP 7 (Add CM): " . $e->getMessage());
}
```
**Objectif** : Créer l'enregistrement Course Module dans la DB

**⚠️ ÉTAPE CRITIQUE** : C'est souvent ici que se produit l'erreur `Object could not be converted to string`

---

### STEP 8: CLEAN FETCH
```php
$clean_cm = null;
try {
    $clean_cm = $DB->get_record('course_modules', array('id' => $cmid), '*', MUST_EXIST);
} catch (\Throwable $e) {
    throw new moodle_exception(..., "CRASH AT STEP 8 (Fetch CM): " . $e->getMessage());
}
```
**Objectif** : Récupérer l'objet propre depuis la DB

---

### STEP 9: ADD TO SECTION (MANUAL DB BYPASS)
```php
try {
    // Get the actual section record from DB
    $section_record = $DB->get_record('course_sections', array('course' => $course->id, 'section' => $target_section), '*', MUST_EXIST);
    
    // 1. Update the sequence (comma separated list of CM IDs)
    $sequence = trim($section_record->sequence);
    if (empty($sequence)) {
        $sequence = (string)$cmid;
    } else {
        $sequence .= "," . $cmid;
    }
    $DB->set_field('course_sections', 'sequence', $sequence, array('id' => $section_record->id));
    
    // 2. Link the CM to the correct section ID (not just section number)
    $DB->set_field('course_modules', 'section', $section_record->id, array('id' => $cmid));
    
} catch (\Throwable $e) {
    throw new moodle_exception(..., "CRASH AT STEP 9 (Manual Section Add): " . $e->getMessage());
}
```
**Objectif** : Ajouter le module à la section via un bypass manuel de la DB

**⚠️ BYPASS MANUEL** : Cette étape utilise un bypass manuel au lieu de `course_add_cm_to_section()` car cette fonction standard cause une erreur "Object to string" dans certains environnements IOMAD (probablement due à un plugin tiers qui hook dans les événements).

**Logique du bypass** :
1. Récupère l'enregistrement `course_sections` depuis la DB
2. Ajoute manuellement le `$cmid` à la séquence (liste séparée par virgules)
3. Met à jour la table `course_modules` pour lier le module à la section ID
4. Le Step 10 (`rebuild_course_cache`) finalise les changements

---

### STEP 10: REBUILD CACHE
```php
try {
    rebuild_course_cache($course->id, true);
} catch (\Throwable $e) {
    throw new moodle_exception(..., "CRASH AT STEP 10 (Cache): " . $e->getMessage());
}
```
**Objectif** : Reconstruire le cache du cours

---

## 🔧 Utilisation

### Comment utiliser cette version

1. **Déployer** : Remplacer la fonction `add_to_course()` dans `externallib.php`
2. **Tester** : Exécuter une création de module via l'API
3. **Analyser** : Examiner le message d'erreur pour identifier l'étape exacte

### Exemple de message d'erreur

```
CRASH AT STEP 7 (Add CM): Object of class stdClass could not be converted to string
```

**Interprétation** : L'erreur se produit à l'étape 7, lors de l'appel à `add_course_module($cm_record)`.

---

## 📊 Analyse des résultats

### Scénarios possibles

#### Scénario 1 : Erreur à STEP 7 (Add CM)
**Cause probable** : L'objet `$cm_record` contient encore des données non-primitives

**Solution** : Vérifier que toutes les propriétés de `$cm_record` sont bien des primitives (int/string)

#### Scénario 2 : Erreur à STEP 9 (Manual Section Add)
**Cause probable** : 
- La section n'existe pas dans la DB
- Problème avec la séquence (format incorrect)
- Erreur lors de la mise à jour de la DB

**Solution** : 
- Vérifier que la section existe bien (`course_sections`)
- Vérifier le format de la séquence (liste séparée par virgules)
- Vérifier que les IDs sont corrects (section ID vs section number)

**Note** : Cette étape utilise un bypass manuel pour éviter l'erreur "Object to string" causée par `course_add_cm_to_section()` dans certains environnements IOMAD.

#### Scénario 3 : Erreur à STEP 5 (Add Instance)
**Cause probable** : La fonction `*_add_instance` retourne un objet au lieu d'un entier

**Solution** : Vérifier le type de retour de la fonction `*_add_instance`

#### Scénario 4 : Erreur à STEP 6 (Sanitize)
**Cause probable** : Tentative de cast d'un objet en int/string

**Solution** : Vérifier que toutes les valeurs sont bien des primitives avant le cast

---

## 🎯 Points d'attention

### Étape critique : STEP 7

L'étape 7 (`add_course_module`) est souvent l'étape où se produit l'erreur `Object could not be converted to string`. C'est pourquoi :

1. **STEP 6** crée un objet propre (`$cm_record`) avec uniquement des primitives
2. **STEP 7** utilise cet objet propre
3. Si l'erreur persiste à STEP 7, cela signifie que l'objet `$cm_record` contient encore des données non-primitives

### Bypass manuel : STEP 9

**Problème identifié** : La fonction standard `course_add_cm_to_section()` cause une erreur "Object to string" dans certains environnements IOMAD, probablement due à un plugin tiers qui hook dans les événements ou le système de logging.

**Solution appliquée** : Bypass manuel via la DB

Au lieu d'utiliser :
```php
course_add_cm_to_section($course, $clean_cm, $target_section); // ❌ Cause erreur
```

Nous utilisons un bypass manuel :
```php
// 1. Récupérer la section depuis la DB
$section_record = $DB->get_record('course_sections', array('course' => $course->id, 'section' => $target_section), '*', MUST_EXIST);

// 2. Ajouter le CM ID à la séquence
$sequence = trim($section_record->sequence);
if (empty($sequence)) {
    $sequence = (string)$cmid;
} else {
    $sequence .= "," . $cmid;
}
$DB->set_field('course_sections', 'sequence', $sequence, array('id' => $section_record->id));

// 3. Lier le CM à la section ID
$DB->set_field('course_modules', 'section', $section_record->id, array('id' => $cmid));
```

**Avantages du bypass** :
- ✅ Évite l'erreur "Object to string"
- ✅ Contrôle total sur les opérations DB
- ✅ Pas de dépendance aux hooks/événements Moodle
- ✅ Le `rebuild_course_cache` (STEP 10) finalise les changements

**Important** : Le STEP 10 (`rebuild_course_cache`) est **crucial** après le bypass manuel pour s'assurer que Moodle reconnaît les changements.

### Vérifications à faire

Si l'erreur se produit à STEP 7 :

1. ✅ Vérifier que `$cm_record->course` est bien un `int`
2. ✅ Vérifier que `$cm_record->module` est bien un `int`
3. ✅ Vérifier que `$cm_record->instance` est bien un `int`
4. ✅ Vérifier que `$cm_record->modulename` est bien un `string`
5. ✅ Vérifier que `$cm_record->name` (si présent) est bien un `string`
6. ✅ Vérifier qu'aucune propriété non-standard n'est présente dans `$cm_record`

---

## 🔄 Retour à la version normale

Une fois l'étape problématique identifiée :

1. **Analyser** : Comprendre pourquoi cette étape échoue
2. **Corriger** : Appliquer la correction appropriée
3. **Remplacer** : Remplacer la version "Tracer" par la version normale avec la correction

---

## 📝 Code complet

Voir `externallib.php` pour le code complet de la fonction `add_to_course()` en version "Tracer".

---

## ✅ Avantages de cette approche

1. **Précision** : Identifie exactement l'étape problématique
2. **Rapidité** : Permet de cibler rapidement la correction
3. **Clarté** : Messages d'erreur explicites
4. **Isolation** : Chaque étape est isolée, facilitant le débogage
5. **Complétude** : Capture toutes les exceptions (`\Throwable`)

---

## 🎓 Leçons apprises

1. **Isolation** : Isoler chaque étape facilite le débogage
2. **Messages clairs** : Des messages d'erreur explicites accélèrent la résolution
3. **Capture complète** : Utiliser `\Throwable` capture toutes les erreurs (Exception + Error)
4. **Étapes numérotées** : Numéroter les étapes facilite la communication et le suivi

---

**Date de création** : Décembre 2024  
**Auteur** : Senior Moodle Developer  
**Version** : Debug Tracer  
**Statut** : 🔧 Outil de débogage temporaire

