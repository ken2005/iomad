# Theme CrewERP Embed - Documentation

## Vue d'ensemble

**Theme CrewERP Embed** est un thème enfant minimal pour Moodle/IOMAD conçu spécifiquement pour l'intégration "Headless" via iframe. Il hérite de `theme_boost` (Bootstrap 4) et masque tous les éléments d'interface utilisateur (en-tête, pied de page, navigation, blocs) pour afficher uniquement le contenu du cours (SCORM, Quiz, etc.).

## Objectif

Ce thème permet d'intégrer du contenu Moodle dans une application externe via un iframe, en fournissant une expérience utilisateur épurée sans les éléments de navigation et d'interface standard de Moodle.

## Structure des fichiers

```
theme_crewerp_embed/
├── version.php                          # Informations de version du plugin
├── config.php                           # Configuration du thème
├── lang/
│   └── en/
│       └── theme_crewerp_embed.php      # Fichier de langue (nom du plugin)
├── style/
│   └── custom.css                       # Styles CSS personnalisés
└── README.md                            # Cette documentation
```

## Détails des fichiers

### 1. `version.php`

Fichier de version standard Moodle contenant :

- **Component** : `theme_crewerp_embed`
- **Version** : `2025122800` (format YYYYMMDDNN)
- **Requires** : `2020061500` (Moodle 3.9+)
- **Dependencies** : `theme_boost` (version 2020061500)
- **Release** : `1.0`

### 2. `config.php`

Configuration principale du thème :

- **Nom** : `crewerp_embed`
- **Parent** : `['boost']` - Hérite de theme_boost (Bootstrap 4)
- **Feuilles de style** : `['custom']` - Utilise custom.css
- **Dock désactivé** : `enable_dock = false`
- **Renderer factory** : `theme_overridden_renderer_factory`
- **Blocs requis** : Vide (`requiredblocks = ''`) - Aucun bloc affiché
- **Position des blocs** : `BLOCK_ADDBLOCK_POSITION_FLATNAV`

### 3. `lang/en/theme_crewerp_embed.php`

Fichier de traduction anglais contenant uniquement le nom du plugin :

```php
$string['pluginname'] = 'CrewERP Embed (Headless)';
```

### 4. `style/custom.css`

Feuille de style CSS critique qui :

#### Masque tous les éléments d'interface :

- `#page-header` - En-tête de page
- `#page-footer` - Pied de page
- `.fixed-top`, `.navbar` - Barre de navigation
- `.secondary-navigation` - Navigation secondaire
- `.drawer-left`, `.drawer-right` - Tiroirs de navigation
- `.block-region` - Régions de blocs
- `#nav-drawer` - Tiroir de navigation
- `[data-region="drawer"]` - Tous les tiroirs
- `.activity-navigation` - Navigation d'activité
- `.breadcrumb` - Fil d'Ariane
- `#region-main-settings-menu` - Menu de paramètres

#### Réinitialise la mise en page :

- Supprime toutes les marges, paddings et bordures
- Définit les arrière-plans en transparent
- Applique ces règles à : `body`, `#page`, `#page-wrapper`, `#region-main`

#### Assure la hauteur complète du viewport :

- `#page` et `#region-main` prennent `100vh` (hauteur du viewport)
- Permet un défilement correct dans l'iframe
- Largeur maximale à 100%

#### Assure la visibilité du contenu :

- Le contenu du cours (SCORM, Quiz, etc.) est visible et correctement dimensionné
- Les conteneurs n'ont pas de contraintes de largeur
- Gestion du défilement optimisée pour l'iframe

## Installation

### Prérequis

- Moodle 3.9 ou supérieur
- Theme Boost installé et activé

### Étapes d'installation

1. **Copier le thème** :
   ```
   Copier le dossier `theme_crewerp_embed` dans :
   /chemin/vers/moodle/theme/
   ```

2. **Mettre à jour Moodle** :
   - Se connecter en tant qu'administrateur
   - Aller dans **Administration du site** → **Notifications**
   - Cliquer sur **Mettre à jour la base de données**

3. **Activer le thème** :
   - Aller dans **Administration du site** → **Apparence** → **Thèmes** → **Sélecteur de thème**
   - Sélectionner **CrewERP Embed (Headless)** comme thème par défaut ou pour un contexte spécifique

4. **Vérifier l'installation** :
   - Visiter une page de cours dans un iframe
   - Vérifier que tous les éléments d'interface sont masqués
   - Vérifier que seul le contenu du cours est visible

## Utilisation

### Intégration via iframe

Pour intégrer du contenu Moodle dans une application externe :

```html
<iframe 
    src="https://votre-moodle.com/mod/scorm/view.php?id=123" 
    width="100%" 
    height="600px"
    frameborder="0">
</iframe>
```

### Paramètres recommandés pour l'iframe

- **Largeur** : `100%` ou une valeur fixe selon vos besoins
- **Hauteur** : `600px` minimum (ou `100vh` pour la hauteur complète)
- **Frameborder** : `0` pour un rendu sans bordure
- **Scrolling** : `auto` (par défaut) pour permettre le défilement du contenu

## Compatibilité

- **Moodle** : 3.9+ (testé jusqu'à la version actuelle)
- **IOMAD** : Compatible avec toutes les versions basées sur Moodle 3.9+
- **Navigateurs** : Tous les navigateurs modernes (Chrome, Firefox, Safari, Edge)

## Personnalisation

### Modifier les styles

Pour personnaliser l'apparence, modifier le fichier `style/custom.css`. Les règles CSS utilisent `!important` pour garantir la priorité sur les styles du thème parent.

### Ajouter des éléments visibles

Si vous souhaitez afficher certains éléments (par exemple, un bouton de retour), commentez ou supprimez la règle CSS correspondante dans `custom.css`.

## Dépannage

### Les éléments d'interface sont toujours visibles

1. Vérifier que le thème est bien activé
2. Vider le cache Moodle : **Administration du site** → **Développement** → **Purge des caches**
3. Vérifier que `custom.css` est bien chargé (inspecter la page)

### Le contenu ne prend pas toute la hauteur

1. Vérifier que les règles CSS pour `#page` et `#region-main` sont appliquées
2. Vérifier que l'iframe a une hauteur définie
3. Inspecter les styles appliqués dans les outils de développement du navigateur

### Problèmes de défilement

1. Vérifier les règles `overflow-x` et `overflow-y` dans `custom.css`
2. S'assurer que l'iframe a `scrolling="auto"` ou `scrolling="yes"`

## Support

Pour toute question ou problème, consulter :
- La documentation Moodle : https://docs.moodle.org/
- Les forums Moodle : https://moodle.org/mod/forum/

## Licence

Ce thème est distribué sous la licence GPL v3 ou ultérieure, conformément à la licence Moodle.

## Version

- **Version actuelle** : 1.0
- **Date de création** : 2024-12-28
- **Dernière mise à jour** : 2024-12-28

## Auteur

Développé pour l'intégration CrewERP avec Moodle/IOMAD.

