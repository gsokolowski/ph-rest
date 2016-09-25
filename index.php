<?php

use Phalcon\Mvc\Micro;
use Phalcon\Loader;
use Phalcon\Di\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;
use Phalcon\Http\Response;

// Use Loader() to autoload our model
$loader = new Loader();

$loader->registerDirs(
    [
        __DIR__ . "/models/"
    ]
)->register();

$di = new FactoryDefault();

// Set up the database service
$di->set(
    "db",
    function () {
        return new PdoMysql(
            [
                "host"     => "localhost",
                "username" => "root",
                "password" => "root",
                "dbname"   => "ph-rest",
            ]
        );
    }
);

//var_dump($di);

// Create and bind the DI to the application
$app = new Micro($di);

//var_dump($app);

$app->notFound(function () use ($app) {
    $app->response->setStatusCode(404, "Not Found")->sendHeaders();
    echo 'This is crazy, but this page was not found!';
});

// Retrieves all robots
// http://ph-rest.local/api/robots
$app->get("/api/robots", function () use ($app)  {

        $phql = "SELECT * FROM Robots ORDER BY name";
        $robots = $app->modelsManager->executeQuery($phql);
        $data = [];
        foreach ($robots as $robot) {
            $data[] = [
                "id"   => $robot->id,
                "name" => $robot->name,
                "type" => $robot->type,
                "year" => $robot->year,
            ];
        }
        echo json_encode($data);
    }
);

// Searches for robots with $name in their name
// http://ph-rest.local/api/robots/search/C-3PO
$app->get("/api/robots/search/{name}", function ($name) use ($app) {

        $phql = "SELECT * FROM Robots WHERE name LIKE :name: ORDER BY name";
        $robots = $app->modelsManager->executeQuery(
            $phql,
            [
                "name" => "%" . $name . "%"
            ]
        );
        $data = [];
        foreach ($robots as $robot) {
            $data[] = [
                "id"   => $robot->id,
                "name" => $robot->name,
                "type" => $robot->type,
                "year" => $robot->year,
            ];
        }
        echo json_encode($data);
    }
);

// Retrieves robots based on primary key
// http://ph-rest.local/api/robots/1
$app->get("/api/robots/{id:[0-9]+}", function ($id) use ($app) {

            $phql = "SELECT * FROM Robots WHERE id = :id:";
            $robot = $app->modelsManager->executeQuery(
                $phql,
                [
                    "id" => $id,
                ]
            )->getFirst();

            // Create a response
            $response = new Response();

            if ($robot === false) {
                $response->setJsonContent(
                    [
                        "status" => "NOT-FOUND"
                    ]
                );
            } else {
                $response->setJsonContent(
                    [
                        "status" => "FOUND",
                        "data"   => [
                            "id"   => $robot->id,
                            "name" => $robot->name
                        ]
                    ]
                );
            }

            return $response;
        }
);

// Insert Data - Adds a new robot
// http://ph-rest.local/api/robots POST from POSTMAN raw {"name":"Marta","type":"mechanical","year":1981}
$app->post("/api/robots", function () use ($app){

        $robot = $app->request->getJsonRawBody();

        //var_dump($robot);

        $phql = "INSERT INTO Robots (name, type, year) VALUES (:name:, :type:, :year:)";

        $status = $app->modelsManager->executeQuery(
            $phql,
            [
                "name" => $robot->name,
                "type" => $robot->type,
                "year" => $robot->year,
            ]
        );

        // Create a response
        $response = new Response();

        // Check if the insertion was successful
        if ($status->success() == true) {
            // Change the HTTP status
            $response->setStatusCode(201, "Created");

            $robot->id = $status->getModel()->id;

            $response->setJsonContent(
                [
                    "status" => "OK",
                    "data"   => $robot,
                ]
            );
        } else {
            // Change the HTTP status
            $response->setStatusCode(409, "Conflict");

            // Send errors to the client
            $errors = [];

            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }

            $response->setJsonContent(
                [
                    "status"   => "ERROR",
                    "messages" => $errors,
                ]
            );
        }

        return $response;

    }
);

// Update Data - Updates robots based on primary key
// http://ph-rest.local/api/robots/1 PUT from POSTMAN raw {"name":"Marta","type":"virtual","year":1981}
$app->put("/api/robots/{id:[0-9]+}", function ($id) use ($app) {

        $robot = $app->request->getJsonRawBody();

        $phql = "UPDATE Robots SET name = :name:, type = :type:, year = :year: WHERE id = :id:";

        $status = $app->modelsManager->executeQuery(
            $phql,
            [
                "id"   => $id,
                "name" => $robot->name,
                "type" => $robot->type,
                "year" => $robot->year,
            ]
        );

        // Create a response
        $response = new Response();

        // Check if the insertion was successful
        if ($status->success() == true) {
            $response->setJsonContent(
                [
                    "status" => "OK"
                ]
            );
        } else {
            // Change the HTTP status
            $response->setStatusCode(409, "Conflict");

            $errors = [];

            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }

            $response->setJsonContent(
                [
                    "status"   => "ERROR",
                    "messages" => $errors,
                ]
            );
        }

        return $response;

    }
);

// Delete Data - Deletes robots based on primary key
// http://ph-rest.local/api/robots/6 DELETE from POSTMAN no parameters
$app->delete("/api/robots/{id:[0-9]+}", function ($id) use ($app) {

        $phql = "DELETE FROM Robots WHERE id = :id:";

        $status = $app->modelsManager->executeQuery(
            $phql,
            [
                "id" => $id,
            ]
        );

        // Create a response
        $response = new Response();

        if ($status->success() == true) {
            $response->setJsonContent(
                [
                    "status" => "OK"
                ]
            );
        } else {
            // Change the HTTP status
            $response->setStatusCode(409, "Conflict");

            $errors = [];

            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }

            $response->setJsonContent(
                [
                    "status"   => "ERROR",
                    "messages" => $errors,
                ]
            );
        }

        return $response;
    }
);

$app->handle();