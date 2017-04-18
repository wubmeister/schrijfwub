<?php

setlocale(LC_ALL, 'nl_NL');

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Framewub\Http\Message\ServerRequest;
use Framewub\Http\Message\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use App\Template;

session_start();

$config = include dirname(__DIR__) . '/config/local.config.php';

Template::$themesDir = dirname(__DIR__) . '/templates';

$db = new App\Database($config['db']);
$articleStorage = new App\Storage\Articles($db);

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
	$router = new App\Router\Main();
	$response = $router($request, $response);
} catch (App\NotFoundException $ex) {
	$response = show404($request, $response);
} catch (Exception $ex) {
	$template = new Template('500');
	$response->getBody()->write($template->render([ 'exception' => $ex ]));
}

$response->flush();