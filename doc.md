Cette image Docker est conçue pour être exécutée dans un environnement de CI/CD (runner Gitlab).

Le paramètrage de son exécution est défini par un jeu de variables d'envrionnement qui doivent être mises en place (typiquement via l'UI Gitlab ou dans le fichier `.gitlab-ci.yml`)


# Variables d'environnement

## DEPLOY_PRIVATE_KEY

<table>
  <tr>
    <th>Requise</th>
    <td>Oui</td>
  </tr>
</table>

Clé privée utilisée pour la connexion SSH à l'hôte (voir `DEPLOY_TARGET_HOST`)

## DEPLOY_TARGET_HOST

<table>
  <tr>
    <th>Requise</th>
    <td>Oui</td>
  </tr>
</table>

Nom d'hôte ou adresse IP qui sera la cible du déploiement.

## DEPLOY_DIR
<table>
  <tr>
    <th>Requise</th>
    <td>Oui</td>
  </tr>
</table>

Chemin absolu du dossier depuis `/` dans lequel sera effectué le déploiement.\
par ex. `/home/mon-projet.com/sd/recette/www`

## DEPLOY_USER
<table>
  <tr>
    <th>Requise</th>
    <td>Oui</td>
  </tr>
</table>

Nom de l'utilisateur qui se connectera en SSH.

## DEPLOY_CHMOD_DIR
<table>
  <tr>
    <th>Requise</th>
    <td>Non</td>
  </tr>
</table>

Droits d'accès qui seront appliqués sur `DEPLOY_DIR` et ses sous-dossier. Par ex. `0755`.\
Si la variable n'est pas définie les droits ne seront pas changés, ils resteront ceux définis par le umask du système.

## DEPLOY_CHMOD_FILE
<table>
  <tr>
    <th>Requise</th>
    <td>Non</td>
  </tr>
</table>

Droits d'accès qui seront appliqués sur les fichiers. Par ex. `0644`.\
Si la variable n'est pas définie les droits ne seront pas changés, ils resteront ceux définis par le umask du système.

## DEPLOY_HOST_PHP_PATH
<table>
  <tr>
    <th>Requise</th>
    <td>Oui</td>
  </tr>
</table>

Chemin du binaire PHP qui sera utilisé. Par ex. `/usr/bin/php8.3`

## DEPLOY_ARTISAN_PATH
<table>
  <tr>
    <th>Requise</th>
    <td>Non</td>
  </tr>
</table>

Chemin vers "artisan", en général dans `DEPLOY_DIR`.\
Par ex. `/home/mon-projet.com/sd/recette/www/artisan`

## DO_NOT_RUN_MIGRATION
<table>
  <tr>
    <th>Requise</th>
    <td>Non</td>
  </tr>
</table>

Si la cette variable est présente la commande `artisan:migrate` ne sera pas exécutée lors du déploiement.

## SUPERVISOR_CONFD_DIR
<table>
  <tr>
    <th>Requise</th>
    <td>Non</td>
  </tr>
  <tr>
    <th>Défaut</th>
    <td>/etc/supervisor/conf.d</td>
  </tr>
</table>
Dossier de configuration Supervisor dans lequel les configs seront linkées

## SUPERVISOR_PROGRAM_NAME
<table>
  <tr>
    <th>Requise</th>
    <td>Non</td>
  </tr>
</table>
Nom de base du programme supervisor, le nom de l'environnement sera automatiquement ajouté en suffixe.

Ce nom doit être suffisamment unique pour ne pas interférer avec d'autres noms de programme déjà gérés par Supervisor.\
Utiliser le nom du projet semble être une bonne option.

Par exemple si la valeur de cette variable est définie à `mon-projet-laravel`, le nom du programme dans Supervisor sera `mon-projet-laravel_INTG`

C'est la présence ou l'absence de valeur dans cette variable qui définira si le déploiement prend en charge la configuration et le lancement des workers ou pas.

## SUPERVISOR_NUM_PROCS
<table>
  <tr>
    <th>Requise</th>
    <td>Non</td>
  </tr>
  <tr>
    <th>Défaut</th>
    <td>2</td>
  </tr>
</table>
Le superviseur lancera autant d'instances de ce programme que le nombre indiqué.

## DOTENV_APP_KEY

<table>
  <tr>
    <th>Requise</th>
    <td>Non</td>
  </tr>
</table>

Si cette variable est fournie, alors elle sera utilisée pour comme `APP_KEY` lors du déploiement
et la commande `artisan key:generate` ne sera pas jouée.

Ceci permet de ne pas changer la clé à chaque déploiement, ce qui invaliderai toutes les chaines chiffrées avec `Crypt::encryptString()` et `Crypt::decryptString()` ou les URL signées déjà diffusées.

## DOTENV_*
Toutes les variables préfixées par `DOTENV_` seront mises en place dans le fichier `.env`
