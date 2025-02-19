<?php

require "vendor/autoload.php";

use Dumbo\Dumbo;
use Dumbo\Helpers\BearerAuth;

$app = new Dumbo();
$user = new Dumbo();

$protectedRoutes = new Dumbo();
$token = "mysupersecret";

$app->use(BearerAuth::bearer($token));

$userData = [
    [
        "id" => 1,
        "name" => "Jamie Barton",
        "email" => "jamie@notrab.dev",
    ],
];

$user->get("/", function ($c) use ($userData) {
    return $c->json($userData);
});

$user->get("/:id", function ($c) use ($userData) {
    $id = (int) $c->req->param("id");

    $user =
        array_values(array_filter($userData, fn($u) => $u["id"] === $id))[0] ??
        null;

    if (!$user) {
        return $c->json(["error" => "User not found"], 404);
    }

    return $c->json($user);
});

$user->post("/", function ($c) use ($userData) {
    $body = $c->req->body();

    if (!isset($body["name"]) || !isset($body["email"])) {
        return $c->json(["error" => "Name and email are required"], 400);
    }

    $newId = max(array_column($userData, "id")) + 1;

    $newUserData = array_merge(["id" => $newId], $body);

    return $c->json($newUserData, 201);
});

$user->delete("/:id", function ($c) use ($userData) {
    $id = (int) $c->req->param("id");

    $user =
        array_values(array_filter($userData, fn($u) => $u["id"] === $id))[0] ??
        null;

    if (!$user) {
        return $c->json(["error" => "User not found"], 404);
    }

    return $c->json(["message" => "User deleted successfully"]);
});

$app->get("/greet/:greeting", function ($c) {
    $greeting = $c->req->param("greeting");
    $name = $c->req->query("name");

    return $c->json([
        "message" => "$greeting, $name!",
    ]);
});

$app->route("/users", $user);

$app->use(function ($ctx, $next) {
    $ctx->set("message", "Dumbo");
    return $next($ctx);
});

$app->use(function ($c, $next) {
    $c->header("X-Powered-By", "Dumbo");

    return $next($c);
});

$app->get("/redirect", function ($c) {
    $message = $c->get("message");

    return $c->redirect("/greet/hello?name=$message", 301);
});

$app->get("/", function ($c) {
    $message = $c->get("message");

    return $c->html("<h1>Hello from $message!</h1>", 200, [
        "X-Hello" => "World",
    ]);
});

$app->run();
