<?php
/**
 * Główny plik projektu.
 * Definiuje kontrolery, usługi i uruchamia aplikację
 */
require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();
$app['debug'] = true;
$app->register(new Silex\Provider\ValidatorServiceProvider());

/* Form */
$app->register(new Silex\Provider\FormServiceProvider());

/* Twig */
$app->register(
    new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/../src/views',
)
);


/* Doctrine */
$app->register(
    new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver' => 'pdo_mysql',
        'host' => '127.0.0.1',
        'dbname' => 'licencjat',
        'user' => 'root',
        'password' => 'root',
        'charset' => 'utf8',
    ),
)
);

$app->register(new Silex\Provider\SessionServiceProvider());

$app->register(
    new Silex\Provider\TranslationServiceProvider(), array(
    'translator.domains' => array(),
)
);

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

use Symfony\Component\HttpFoundation\Response;

$app->register(
    new Silex\Provider\SecurityServiceProvider(), array(
        'security.firewalls' => array(
            'admin' => array(
                'pattern' => '^.*$',
                'form' => array(
                    'login_path' => '/auth/login',
                    'check_path' => '/auth/login_check',
                    'default_target_path' => '/kokpit',
                    'username_parameter' => 'form[login]',
                    'password_parameter' => 'form[haslo]',
                ),
                'logout' => true,
                'anonymous' => true,
                'logout' => array('logout_path' => '/auth/logout'),
                'users' => $app->share(
                    function() use ($app) {
                        return new User\UserProvider($app);
                    }
                ),
            ),
        ),
        'security.access_rules' => array(
           /* array(
                '^/auth/.+$|^/klasa/$|^/przedmiot/$',
                'IS_AUTHENTICATED_ANONYMOUSLY'),
            array(
                '^/ocena/student.*$|^/ocena/avg.*$|^/okresowe/student.*$|'
                . '^/user/view.*$|^/ok/$', 'ROLE_UCZEN'),
            array(
                '^/ocena/.*$', 'ROLE_NAUCZYCIEL'),
            array(
                '^/.+$', 'ROLE_ADMIN') */
            array('^/.+$', 'IS_AUTHENTICATED_ANONYMOUSLY') //chwilowe ustawienia
        ),
        'security.role_hierarchy' => array(
            'ROLE_ADMIN' => array(
                'ROLE_USER',
                'ROLE_ANONYMUS'),

        ),
    )
);

$app->mount('/auth/', new Controller\AuthController());
$app->mount('/posts/', new Controller\PostsController());
$app->mount('/categories/', new Controller\CategoriesController());
$app->mount('/users/', new Controller\UsersController());
$app->mount('/comments/', new Controller\CommentsController());
$app->get(
    '/', function() use($app) {
    return $app['twig']->render('index.twig');
    }
);

$app->get(
    '/about', function() use($app) {
    return $app['twig']->render('about.twig');
    }
);
$app->get(
    '/znaczenie', function() use($app) {
    return $app['twig']->render('znaczenie.twig');
    }
);
$app->get(
    '/kultura', function() use($app) {
    return $app['twig']->render('kultura.twig');
    }
);
$app->get(
    '/zbilansowanie', function() use($app) {
    return $app['twig']->render('zbilansowanie.twig');
    }
);

$app->get('/kokpit', function() use($app) {
    return $app['twig']->render('kokpithome.twig');
});

$app->get(
    '/kontakt', function() use($app) {
    return $app['twig']->render('kontakt.twig');
    }
);

$app->error(
    function (\Exception $e, $code) use ($app) {
        if ($code == 404) {
            return new Response(
                $app['twig']->render('404.twig'), 404
            );
        }
    }
);


$app->run();


