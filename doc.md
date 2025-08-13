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
