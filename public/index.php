<?php
require '../vendor/autoload.php';

$app = new Slim\App();

function getDB(){
	$dbhost = "localhost";
	$dbname = "picture";
	$dbuser = "admin";
	$dbpass = "secret";

	$mysql_conn_string = "mysql:host=$dbhost;dbname=$dbname";
	$dbConnection = new PDO($mysql_conn_string, $dbuser, $dbpass);
	$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $dbConnection;
}

function filter($fields, $pictures){
	$picturesFiltered="";

	if($fields){
		$fields=explode(",", $fields);

		foreach($pictures as $key => $picture){
			foreach($picture as $k => $p){
				if(in_array($k, $fields)){
					$picturesFiltered[$key][$k] = $pictures[$key][$k];
				}
			}
		}
	}else{
		$picturesFiltered = $pictures;
	}


	if($pictures AND !$picturesFiltered ){ //Validación nombres de campos incorrectos.
		$picturesFiltered = $pictures;
	}

	return $picturesFiltered;
}



$app->get('/', function ($request, $response, $args) {
    $response->write("Welcome to pictures API REST!");
    return $response;
});

$app->get('/pictures', function ($request, $response, $args) {
	$headers = $request->getHeaders();
	$data = $request->getParams();


	if($data["filters"]){
		if($data["filters"]["country"]){
			$arrSqlFilter[]="c.name = :country_name";
		}
		if($data["filters"]["painter"]){
			$arrSqlFilter[]="painter.name = :picture_name";
		}

		$sqlFilter = implode(" AND ", $arrSqlFilter);
		$sqlFilter = " AND " . $sqlFilter;
	}

	try{
		$db = getDB();
		$sth = $db->prepare("SELECT pic.id AS id_picture, pic.name AS picture_name, pic.id_painter, pic.id_country,
			c.name AS country,
			painter.name AS painter_name
			FROM picture pic
			JOIN country c ON c.id_country = pic.id_country
			JOIN painter painter ON painter.id_painter = pic.id_painter
			
			WHERE pic.id_user = :id_user
			$sqlFilter
			");



		$sth->bindParam(":id_user", $headers["HTTP_X_HTTP_USER_ID"][0], PDO::PARAM_INT);
		if($data["filters"]["country"]){
			$sth->bindParam(":country_name", $data["filters"]["country"], PDO::PARAM_STRING);

		}
		if($data["filters"]["painter"]){
			$sth->bindParam(":picture_name", $data["filters"]["painter"], PDO::PARAM_STRING);

		}

		$sth->execute();
		$pictures = $sth->fetchAll(PDO::FETCH_ASSOC);

	} catch(PDOException $e){
		$response = $response->withStatus(500);
		$response->write('{"error":{"message":'.$e->getMessage().'}}');

		return $response;
	}


	if(!$pictures){
		$response = $response->withStatus(404);
		$response->write('{"error":{"message":"Pictures not found"}}');
		return $response;
	}

	$picturesFiltered=filter($data["fields"], $pictures);

	if($picturesFiltered){
		$response = $response->withJson($picturesFiltered);
		$db = null;
	}


    
    return $response;
});


$app->get('/pictures/{id}', function ($request, $response, $args) {
	try{
		$headers = $request->getHeaders();
		$data = $request->getParams();

		$db = getDB();
		$sth = $db->prepare("SELECT pic.id AS id_picture, pic.name AS picture_name, pic.id_painter, pic.id_country,
			c.name AS country,
			painter.name AS painter_name
			FROM picture pic
			JOIN country c ON c.id_country = pic.id_country
			JOIN painter painter ON painter.id_painter = pic.id_painter
			
			WHERE pic.id_user = :id_user
			AND pic.id = :id
			");

		$sth->bindParam(":id_user", $headers["HTTP_X_HTTP_USER_ID"][0], PDO::PARAM_INT);
		$sth->bindParam(":id", $args["id"], PDO::PARAM_INT);		
		$sth->execute();
		$pictures = $sth->fetchAll(PDO::FETCH_ASSOC);

		if(!$pictures){
			 $response = $response->withStatus(404);
			 $response->write('{"error":{"message":"Pictures not found"}}');
			 return $response;
		}

		$picturesFiltered=filter($data["fields"], $pictures);

		if($picturesFiltered){
			$response = $response->withJson($picturesFiltered);
			$db = null;
		}

	} catch(PDOException $e){
		$response = $response->withStatus(500);
		$response->write('{"error":{"message":'.$e->getMessage().'}}');
	}
    
    return $response;
});



$app->put('/pictures/update/{id}', function ($request, $response, $args) {
	try{
		$headers = $request->getHeaders();

		$data = $request->getParams();
		$db = getDB();
		$sth = $db->prepare("UPDATE picture SET
			name=?, id_painter=?, id_country=?
			where id=?
			AND id_user = ?
			");

		$sth->execute(array($data["name"], $data["id_painter"], $data["id_country"], $args["id"], $headers["HTTP_X_HTTP_USER_ID"][0]));
		$response->write('{"error":"ok"}');
	} catch(PDOException $e){
		$response = $response->withStatus(500);
		$response->write('{"error":{"message":'.$e->getMessage().'}}');
	}
    
    return $response;
});



$app->put('/pictures/add', function ($request, $response) {
	try{
		$headers = $request->getHeaders();

		$data = $request->getParams();
		$db = getDB();
		$sth = $db->prepare("INSERT INTO picture 
										(id_user, name, id_painter, id_country)
										VALUES (?,?,?,?)");

		$sth->execute(array($headers["HTTP_X_HTTP_USER_ID"][0], $data["name"], $data["id_painter"], $data["id_country"]));
		$response->write('{"error":"ok"}');
	} catch(PDOException $e){
		$response = $response->withStatus(500);
		$response = $response->withAddedHeader('Allow', 'POST');

		$response->write('{"error":{"message":'.$e->getMessage().'}}');
	}
    
    return $response;
});



$app->delete('/pictures/delete/{id}', function ($request, $response, $args) {
	try{
		$headers = $request->getHeaders();

		$db = getDB();
		$sth = $db->prepare("DELETE FROM picture
			where id=:id
			AND id_user = :id_user");


		$sth->bindParam(":id",  $args["id"], PDO::PARAM_INT);
		$sth->bindParam(":id_user", $headers["HTTP_X_HTTP_USER_ID"][0], PDO::PARAM_INT);

		$sth->execute();
		$response->write('{"error":"ok"}');
	} catch(PDOException $e){
		$response = $response->withStatus(500);
		$response->write('{"error":{"message":'.$e->getMessage().'}}');
	}
    
    return $response;
});

$app->get('/countries', function ($request, $response, $args) {
	try{
		$headers = $request->getHeaders();
		$data = $request->getParams();

		$db = getDB();
		$sth = $db->prepare("SELECT c.id_country,
			c.name AS name
			FROM country c
			");

		$sth->execute();
		$pictures = $sth->fetchAll(PDO::FETCH_ASSOC);

		if(!$pictures){
			 $response = $response->withStatus(404);
			 $response->write('{"error":{"message":"Countries not found"}}');
			 return $response;
		}

		$picturesFiltered=filter($data["fields"], $pictures);

		if($picturesFiltered){
			$response = $response->withJson($picturesFiltered);
			$db = null;
		}

	} catch(PDOException $e){
		$response = $response->withStatus(500);
		$response->write('{"error":{"message":'.$e->getMessage().'}}');
	}
    
    return $response;
});


$app->get('/painters', function ($request, $response, $args) {
	try{
		$headers = $request->getHeaders();
		$data = $request->getParams();

		$db = getDB();
		$sth = $db->prepare("SELECT id_painter,
			name
			FROM painter
			");

		$sth->execute();
		$pictures = $sth->fetchAll(PDO::FETCH_ASSOC);

		if(!$pictures){
			 $response = $response->withStatus(404);
			 $response->write('{"error":{"message":"Pinters not found"}}');
			 return $response;
		}

		$picturesFiltered=filter($data["fields"], $pictures);

		if($picturesFiltered){
			$response = $response->withJson($picturesFiltered);
			$db = null;
		}

	} catch(PDOException $e){
		$response = $response->withStatus(500);
		$response->write('{"error":{"message":'.$e->getMessage().'}}');
	}
    
    return $response;
});


$app->post('/uploadfile', function ($request, $response, $args) {
	$data = $request->getParams();
	//print_R($data);


    $uploadedFiles = $request->getUploadedFiles();
    $directory="./upload";
    $uploadedFile = $uploadedFiles['example1'];

    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
        $filename = moveUploadedFile($directory, $uploadedFile);
        $response->write('uploaded ' . $filename . '<br/>');
    }    
});



$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, X_HTTP_USER_ID')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Catch-all route to serve a 404 Not Found page if none of the routes match
// NOTE: make sure this route is defined last
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function($req, $res) {
    $handler = $this->notFoundHandler; // handle using the default Slim page not found handler
    return $handler($req, $res);
});


/**
 * Moves the uploaded file to the upload directory and assigns it a unique name
 * to avoid overwriting an existing uploaded file.
 *
 * @param string $directory directory to which the file is moved
 * @param UploadedFile $uploaded file uploaded file to move
 * @return string filename of moved file
 */
function moveUploadedFile($directory, $uploadedFile)
{
    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
    $filename = sprintf('%s.%0.8s', $basename, $extension);

    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    return $filename;
}

$app->run();
