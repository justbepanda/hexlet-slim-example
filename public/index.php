<?php

namespace Mof\HexletSlimExample;

// sudo kill -9 `sudo lsof -t -i:8080`
// php -S localhost:8080 -t public public/index.php
// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';


use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use DI\Container;

use tightenco\collect;
use function DI\get;
use function Symfony\Component\String\s;

session_start();


$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

AppFactory::setContainer($container);

$app = AppFactory::create();
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);


$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
});

$app->get('/users', function ($request, $response) {
    $users = new UserRepository(json_decode($request->getCookieParam('users', json_encode([])), true));
    $searchByNickname = $request->getQueryParam('nickname');
    $usersByName = collect($users->all())->keyBy('nickname');
    if ($searchByNickname) {
        $filteredUsers = $usersByName->filter(fn($value, $key) => s($key)->ignoreCase()->startsWith($searchByNickname));
        $params = ['users' => $filteredUsers, 'searchByNickname' => $searchByNickname];
    } else {
        $params = ['users' => $usersByName, 'searchByNickname' => ''];
    }
    $params['user'] = ['email' => '', 'nickname' => ''];
    $messages = $this->get('flash')->getMessages();
    $params['flash'] = $messages;

    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users.index');


$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['nickname' => '', 'email' => ''],
        'errors' => []
    ];

    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('user.create');

$app->get('/users/{id}', function ($request, $response, $args) {
    $users = new UserRepository(json_decode($request->getCookieParam('users', json_encode([])), true));
    $id = $args['id'];

    $user = $users->find($id);

    if (!$user) {
        return $response->withStatus(404);
    }
    $params = ['id' => $id, 'nickname' => $user['nickname'], 'email' => $user['email']];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('user.show');

$router = $app->getRouteCollector()->getRouteParser();


$app->post('/users', function ($request, $response) use ($router) {
    $users = new UserRepository(json_decode($request->getCookieParam('users', json_encode([])), true));
    $user = $request->getParsedBodyParam('user');
    $validator = new Validator();
    $errors = $validator->validate($user);

    if (count($errors) === 0) {
        $newUsers = $users->save($user);
        $encodedNewUsers = json_encode($newUsers);
        $url = $router->urlFor('users.index');
        $this->get('flash')->addMessage('success', 'Пользователь добавлен');
        return $response->withHeader('Set-Cookie', "users={$encodedNewUsers}")->withRedirect($url);
    }

    $params = [
        'user' => $user,
        'errors' => $errors
    ];
    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('user.create');

$app->get('/users/{id}/edit', function ($request, $response, $args) {
    $users = new UserRepository(json_decode($request->getCookieParam('users', json_encode([])), true));
    $id = $args['id'];
    $user = $users->find($id);
    $params = [
        'user' => $user,
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
})->setName('user.edit');

$app->patch('/users/{id}', function ($request, $response, $args) use ($router) {
    $users = new UserRepository(json_decode($request->getCookieParam('users', json_encode([])), true));
    $id = $args['id'];
    $user = $users->find($id);

    $data = $request->getParsedBodyParam('user');

    $validator = new Validator();
    $errors = $validator->validate($data);

    if (count($errors) === 0) {
        $user['nickname'] = $data['nickname'];
        $user['email'] = $data['email'];

        $this->get('flash')->addMessage('success', 'User has been updated');


        $newUsers = $users->save($user);
        $encodedNewUsers = json_encode($newUsers);
        $url = $router->urlFor('user.edit', ['id' => $user['id']]);
        return $response->withHeader('Set-Cookie', "users={$encodedNewUsers}")->withRedirect($url);
    }

    $params = [
        'user' => $user,
        'errors' => $errors
    ];
    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
});

$app->delete('/users/{id}', function ($request, $response, $args) use ($router) {
    $users = new UserRepository(json_decode($request->getCookieParam('users', json_encode([])), true));
    $id = $args['id'];
    $newUsers = $users->destroy($id);
    $encodedNewUsers = json_encode($newUsers);
    $this->get('flash')->addMessage('success', 'User has been deleted');
    return $response->withHeader('Set-Cookie', "users={$encodedNewUsers}")->withRedirect($router->urlFor('users.index'));
});

$app->post('/session', function ($request, $response) {
    $users = new UserRepository(json_decode($request->getCookieParam('users', json_encode([])), true));
    $user = $request->getParsedBodyParam('user');
    $_SESSION['nickName'] = '';

    $usersByEmail = collect($users->all())->keyBy('email')->all();

    if (isset($usersByEmail[$user['email']])) {
        $_SESSION['login'] = 1;
        $_SESSION['nickName'] = $usersByEmail[$user['email']]['nickname'];
        return $response->withRedirect('/users');
    }

    $this->get('flash')->addMessage('error', 'Wrong email');
    return $response->withRedirect('/users');
});

$app->delete('/session', function ($request, $response) {
    $_SESSION['login'] = 0;
    $_SESSION['nickName'] = '';
    return $response->withRedirect('/users');
});

$app->run();

