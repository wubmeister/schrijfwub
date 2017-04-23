<?php

setlocale(LC_ALL, 'nl_NL');

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Framewub\Http\Message\ServerRequest;
use Framewub\Http\Message\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use App\Template;
use App\Container\Container;

session_start();

Template::$themesDir = dirname(__DIR__) . '/templates';

$container = new Container();
$container
    ->set('Config', function ($container) {
        $config = include dirname(__DIR__) . '/config/local.config.php';
        return $config;
    })
    ->set('Database', function ($container) {
        $config = $container->get('Config');
        return new App\Database($config['db']);
    })
    ->set('MainModule', function ($container) {
        return new App\MainModule($container);
    })
    ->set('BlogModule', function ($container) {
        $container->set('ArticleStorage', function ($container) {
            $db = $container->get('Database');
            return new App\Blog\Storage\Articles($db);
        });

        $container->set('CommentStorage', function ($container) {
            $db = $container->get('Database');
            return new App\Blog\Storage\Comments($db);
        });

        $container->set('BlogController', function ($container) {
            $storage = $container->get('ArticleStorage');
            return new App\Blog\Controller\Blog($storage);
        });

        $container->set('CommentsController', function ($container) {
            $storage = $container->get('ArticleStorage');
            return new App\Blog\Controller\Comments($storage);
        });

        return new \App\Blog\Module($container);
    });

/* Basic login check */

function checkLogin()
{
    if (!isset($_SESSION['identity']) || $_SESSION['identity']['role'] != 'Admin') {
        $_SESSION['redirect'] = $_SERVER['REQUEST_URI'];
        header('Location: /login');
        exit;
    }
}

function isLoggedIn()
{
    return isset($_SESSION['identity']);
}

function getIdentity()
{
    return $_SESSION['identity'];
}

/* Funciones */

function sendMail($subject, $templateName, $toName, $toEmail, $variables)
{
    $template = new Template($templateName);

    $mail = new App\Mail\Mail();
    $mail
        ->setFrom('schrijf@wubbobos.nl', 'Wubbo Bos')
        ->addTo($toEmail, $toName)
        ->setSubject($subject);

    $template->toName = $toName;
    $template->toEmail = $toEmail;
    $mail->setBody($template->render($variables));

    // $transport = new App\Mail\Transport\Mailgun('sandboxe62c14ce20584e6b9317f3f38e244398.mailgun.org', 'key-4b681f8cf3cfa78a9670d56ffcad6e24');
    $transport = new App\Mail\Transport\Sendmail();
    $transport->send($mail);
}

function generateSlug($string, $maxLength = 32)
{
    if (strlen($string) > $maxLength) {
        $pos = strrpos(substr($string, 0, $maxLength), ' ');
        $string = substr($string, 0, $pos);
    }
    return trim(strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $string)), '-');
}

function show404(RequestInterface $request, ResponseInterface $response, callable $next = null)
{
    $layout = new Template('layout');
    $template = new Template('404');

    $template->layout = $layout;
    $layout->title = 'Blogs';
    $layout->content = $template->render();

    $response->getBody()->write($layout->render());

    if ($next) {
        $response = $next($request, $response);
    }

    return $response;
}


function handleLogin(RequestInterface $request, ResponseInterface $response, callable $next = null)
{
    global $db;

    /* Login */
    $result = [
        'error' => null,
        'username' => isset($_POST['username']) ? $_POST['username'] : ''
    ];

    if ($_POST) {
        if (!$_POST['username'] || !$_POST['password']) {
            $result['error'] = 'Vul a.u.b. uw gebruikersnaam en wachtwoord in';
        } else {
            $sql = "SELECT * FROM `users` WHERE `username` LIKE :username";
            $user = $db->fetchRow($sql, [ 'username' => $_POST['username'] ]);
            if (!$user) {
                $result['error'] = 'Geen gebruiker gevonden met die naam';
            } else {
                $hash = hash('sha256', $_POST['password'] . $user['salt']);
                if ($hash != $user['password']) {
                    $result['error'] = 'Incorrect wachtwoord';
                } else {
                    unset($user['password'], $user['salt']);
                    $_SESSION['identity'] = $user;
                    $redirect = '/';
                    if (isset($_SESSION['redirect'])) {
                        $redirect = $_SESSION['redirect'];
                        unset($_SESSION['redirect']);
                    }

                    header('Location: ' . $redirect);
                    exit;
                }
            }
        }
    }

    $layout = new Template('layout');
    $template = new Template('login');

    $layout->title = 'Login';
    $template->layout = $layout;
    $layout->content = $template->render($result);
    $response->getBody()->write($layout->render());

    if ($next) {
        $response = $next($request, $response);
    }

    return $response;
}

function handleLogout(RequestInterface $request, ResponseInterface $response, callable $next = null)
{
    /* Login */
    unset($_SESSION['identity']);
    $redirect = '/';
    if (isset($_SESSION['redirect'])) {
        $redirect = $_SESSION['redirect'];
        unset($_SESSION['redirect']);
    }

    header('Location: ' . $redirect);
    exit;
}

$request = new ServerRequest();
$response = new Response();

try {
    $router = $container->get('MainModule');
    $response = $router($request, $response);
} catch (App\NotFoundException $ex) {
    $response = show404($request, $response);
} catch (Exception $ex) {
    $template = new Template('500');
    $response->getBody()->write($template->render([ 'exception' => $ex ]));
}

$response->flush();