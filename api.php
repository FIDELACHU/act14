<?php
//para permitir origenes desconocidos o externos
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET,POST,PUT,DELETE");
header("Access-Control-Allow-Headers: Content-Type");

session_start();            //iniciar sesion para guardar datos
$archivo = "datos.json";    //archivo donde se guardaran los datos
//si el archivo no existe, lo creamos vacio
if(!file_exists($archivo)){
    file_put_contents($archivo, "[]");     
}
//leer datos
$datos = json_decode(file_get_contents($archivo), true);

//METODO (el que va a estar "al pendiente")
$metodo = $_SERVER["REQUEST_METHOD"];

if($metodo === "GET"){                      
    if(isset($_GET['id'])){                 //checar si el campo existe
        $id = (int)$_GET['id'];             //que el string sea un entero
        $encontrado = null;
        
        foreach($datos as $dato){         //Iteramos en la lista hasta encontrar el id del dato
            if($dato['id'] === $id){
                $encontrado = $dato;
                break;                      //salimos del for
            }
        }

        if($encontrado) {
            echo json_encode($encontrado, JSON_PRETTY_PRINT);   //se es un get normal, se muestra la lista de datos
        }else {
            http_response_code(404);        //No existe / No se encuentra
            echo json_encode(["error" => "dato no encontrado"], JSON_PRETTY_PRINT);
        }
    }else {
        echo json_encode($datos, JSON_PRETTY_PRINT);
    }
}else if($metodo === "POST"){              
    //Se espera que el input sea un JSON
    $input = json_decode(file_get_contents("php://input"), true);

        //Aqui evaluamos si el formato es el esperado
    if( !$input || !isset($input['Title']) || !isset($input['Author']) || !isset($input['Date and time']) || !isset($input['Body of the note']) || !isset($input['Classification'])) {
        http_response_code(400);            // solicitud incorrecta
        echo json_encode(["error" => "Datos invalidos"], JSON_PRETTY_PRINT);
        exit;                               //terminar aqui
    }
    // si todo sale bien, creamos y agreagmos un nuevo dato a la lista
    $nuevo =  [
        "id" => count($datos) +1,
        "Title" => $input['Title'],
        "Author" => $input['Author'],
        "Date and time" => $input['Date and time'],
        "Body of the note" => $input['Body of the note'],
        "Classification" => $input['Classification'],
    ];
    //todo fine, se crea el recurso
    http_response_code(201);
    echo json_encode($datos, JSON_PRETTY_PRINT);
    // GUARDAR
    $datos[] = $nuevo;
    file_put_contents($archivo, json_encode($datos, JSON_PRETTY_PRINT)); 
    header("Location: api.php");
    exit;
}else if($metodo === "PUT"){
    $input = json_decode(file_get_contents("php://input"), true);
    if(!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(["error" => "Falta id"], JSON_PRETTY_PRINT);
        exit;
    }
    $id = (int)($_GET['id']);
    $actualizado = false;
    foreach ($datos as &$dato) {
        if ($dato['id'] === $id) {
            // actualiza solo los campos enviados
            foreach ($input as $clave => $valor) {
                $dato[$clave] = $valor;
            }
            $actualizado = true;
            break;
        }
    }

    if ($actualizado) {
        file_put_contents($archivo, json_encode($datos, JSON_PRETTY_PRINT));
        echo json_encode(["success" => "Dato actualizado"], JSON_PRETTY_PRINT);
    } else {
        http_response_code(404);
        echo json_encode(["error" => "Dato no encontrado"], JSON_PRETTY_PRINT);
    }
}else if($metodo === "DELETE") {
    if(!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(["error" => "Falta id"], JSON_PRETTY_PRINT);
        exit;
    }
    $id = (int)($_GET['id']);

    $encontrado = false;
    foreach ($datos as $index => $dato) {
        if ($dato['id'] === $id) {
            unset($datos[$index]); // eliminar ese elemento
            $encontrado = true;
            break;
        }
    }

    if ($encontrado) {
        // Guardar
        $datos = array_values($datos);
        file_put_contents($archivo, json_encode($datos, JSON_PRETTY_PRINT));
        echo json_encode(["success" => "Dato eliminado"], JSON_PRETTY_PRINT);
    } else {
        http_response_code(404);
        echo json_encode(["error" => "Dato no encontrado"], JSON_PRETTY_PRINT);
    }

}
else { //si intentamos utilizar otro metodo, nos marca error (que no sea POST/GET)
    http_response_code(405);
    echo json_encode(["error" => "Metodo no permitido."], JSON_PRETTY_PRINT);
}

?>