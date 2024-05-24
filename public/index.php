<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

session_start();

class Validator
{
    function validate($user) {
        $errors = [];
        if (empty($user['name'])) {
            $errors['name'] = "Name can't be blank";
        }
        if (empty($user['email'])) {
            $errors['email'] = "Email can't be blank";
        }
        return $errors;
    }
}

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);
$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');

    return $response;
});

//$app->get('/posts', function ($request, $response, $args) use ($repo) {
//    $page = $request->getQueryParam('page', 1);
//    $per = $request->getQueryParam('per', 5);
//    $posts = array_slice($repo->all(), $page * $per - $per, $per);
//    $params = [
//        'posts' => $posts,
//        'page' => $page
//    ];
//    return $this->get('renderer')->render($response, 'posts/index.phtml', $params);
//}); PAGE FUNCTIONALITY FOR FUTURE

$app->get('/users', function ($request, $response){
    $userData = file_get_contents(__DIR__ . '/../UsersData/UsersData.json');
    $users = json_decode($userData, true, 512, JSON_THROW_ON_ERROR);
    $messages = $this->get('flash')->getMessages();
    $params = [
        'users' => $users,
        'flash' => $messages
    ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users.index');

$app->post('/users', function ($request, $response) use ($router) {
    $validator = new Validator();
    $newUserData = $request->getParsedBodyParam('user');
    $errors = $validator->validate($newUserData);

    if (count($errors) === 0) {
        $userData = file_get_contents(__DIR__ . '/../UsersData/UsersData.json');
        $users = json_decode($userData, true, 512, JSON_THROW_ON_ERROR);

        $i = 0;
        foreach ($users as $user) {
            if (!$user['id'] == $i) {
                $id = $i;
            }
            $i += 1;
        }
        $id = $i;

        $newUser = ['name' => $newUserData['name'], 'email' => $newUserData['email'], 'id' => $id];
        $users[] = $newUser;
        $users = json_encode($users);
        file_put_contents(__DIR__ . '/../UsersData/UsersData.json', $users);
        $this->get('flash')->addMessage('success', 'User was added successfully');
        return $response->withRedirect("{$router->urlFor('users.index')}", 302);
    }

    $params = [
        'user' => ['name' => "{$newUserData['name']}", 'email' => "{$newUserData['email']}"],
        'errors' => $errors
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
})->setName('users.post');

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['name' => '', 'email' => ''],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('user.create');

$app->get('/users/{id}', function (Request $request, Response $response, $args) use ($router){
    $userData = file_get_contents(__DIR__ . '/../UsersData/UsersData.json');
    $users = json_decode($userData, true, 512, JSON_THROW_ON_ERROR);
    $reqId = $args['id'];
    foreach ($users as $user) {
        if ($user['id'] == $reqId) {
            $params = [
                'reqUser' => $user,
            ];
            return $this->get('renderer')->render($response, 'users/user.phtml', $params);
        }
    }

    return $response->withStatus(404);
});

$app->run();
