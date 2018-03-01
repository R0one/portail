# Portail des assos - API

Nouvelle API du [Portail des Assos](https://assos.utc.fr), construite avec [Laravel 5.6](https://laravel.com/) nécessitant au moins PHP 7.1.3



## Installation

- Vérifier que qu'une version supérieure à 7.1.3 de PHP est installé : `php -v`
- Installer [composer](https://getcomposer.org/download/)
- Installer les packages avec `composer install` (attention à être dans le bon dossier)
- Copier `.env.example` en `.env` et spécifier les identifiants de connexions à la base de données (par exemple localhost)
- Lancer les commances suivantes :
    + Suppression du cache : `php artisan config:clear`
    + Création de la clé : `php artisan key:generate`
- Créer la base de données `portail` à la mano
- Lancer la commande suivante : `php artisan migrate:fresh`
- Pour populer la BDD : `php artisan db:seed`
- Lancer l'application via :
    + Artisan : `php artisan serve` et aller sur http://localhost:8000
    + Wamp : aller directement sur le dossier `public` de l'installation via Wamp
- Ça part !



## Models

Il s'agit des modèles de données, avec lesquelles on peut intéragir via Eloquent.
Namespace : `\App\Models\...`
Dossier :   `app/Models`


### User
```
login: varchar(10) primary key
email: varchar() unique
prenom: varchar()
nom: varchar()
```


## Permissions

Avec le package [spatie/laravel-permission](https://github.com/spatie/laravel-permission)


## Controllers

Interfaces de validation des données envoyées en POST.
Namespace : `\App\Http\Requests\...`
Dossier :   `app/Http/Requests`



## Middlewares

Ils permettent de modifier les requêtes avant qu'elles ne soient traitées.
Namespace : `\App\Http\Middleware\...`
Dossier :   `app/Http/Middleware`



## Services

Il s'agit des services externes tels que le CAS ou Ginger
Namespace : `\App\Services\...`
Dossier :   `app/Services`

### Auth

Héritent de `App\Services\Auth\AuthService`
Doivent implémenter :
- `protected $name;`
- `protected $processURL;      // Callback pour process login`
- `protected $config;`
- `public function login(Request $request)`


```php
public function __construct() {`
    $this->processURL = route('login.process', ['provider' => $this->name]);
    $this->config = config("auth.services." . $this->name);
}
```

Config :

Dans `config/auth.php`, le tableau 'services', rajouter le nom du service :


#### CAS


### Ginger



## API

Voici les routes de l'API
