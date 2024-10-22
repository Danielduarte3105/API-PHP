<?php
// Definir cabeçalhos para a API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Função para ler o arquivo JSON
function readData() {
    $file_path = 'data.json'; // Caminho para o arquivo JSON
    if (file_exists($file_path)) {
        $json_data = file_get_contents($file_path);
        return json_decode($json_data, true);
    } else {
        // Se o arquivo não existir, retorne um array vazio
        return [];
    }
}

// Função para salvar dados no arquivo JSON
function writeData($data) {
    file_put_contents('data.json', json_encode($data, JSON_PRETTY_PRINT));
}

// Função para analisar a URL
function parseUrl() {
    $url = $_SERVER['REQUEST_URI'];  // Obtem a URL
    $urlComponents = explode('/', $url);  // Separa por '/'
    return array_filter($urlComponents);  // Remove componentes vazios
}

// Função para obter dados enviados no corpo da requisição
function getInputData() {
    return json_decode(file_get_contents('php://input'), true);
}

// Função para obter valor de query string
function getQueryStringValue($queryString) {
    $parts = explode('=', $queryString);
    return isset($parts[1]) ? $parts[1] : null;
}

// Função para gerar o próximo ID automaticamente
function generateNextId($data) {
    if (count($data) > 0) {
        // Encontrar o maior ID e incrementar
        $ids = array_column($data, 'id');
        return max($ids) + 1;
    } else {
        // Se o arquivo estiver vazio, começar com o ID 1
        return 1;
    }
}

// Identificar rota da URL
$urlComponents = parseUrl();

// Definir a ação e ID com base na URL
$action = isset($urlComponents[2]) ? $urlComponents[2] : null;
$id = isset($urlComponents[3]) ? getQueryStringValue($urlComponents[3]) : null;

// Verifica o método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Roteamento baseado na URL
switch ($action) {
    case 'get':
        $data = readData();
        if ($id) {
            // Buscar um único registro por ID
            $item = null;
            foreach ($data as $entry) {
                if ($entry['id'] == $id) {
                    $item = $entry;
                    break;
                }
            }
            echo json_encode($item ? $item : ['message' => 'Registro não encontrado']);
        } else {
            // Listar todos os registros
            echo json_encode($data);
        }
        break;

    case 'post':
        // Adicionar um novo registro
        $data = readData();
        $newEntry = getInputData();

        // Gerar um ID único automaticamente
        $newEntry['id'] = generateNextId($data);

        // Adicionar o novo item ao array de registros
        $data[] = $newEntry;

        // Salvar no arquivo JSON
        writeData($data);

        echo json_encode(['message' => 'Registro adicionado com sucesso', 'data' => $newEntry]);
        break;

    case 'put':
        // Editar um registro existente
        if ($id) {
            $data = readData();
            $updatedEntry = getInputData();
            $updated = false;

            foreach ($data as &$entry) {
                if ($entry['id'] == $id) {
                    // Atualizar o registro com os novos dados
                    $entry = array_merge($entry, $updatedEntry);
                    $entry['id'] = $id;  // Garantir que o ID original não seja alterado
                    $updated = true;
                    break;
                }
            }

            if ($updated) {
                // Salvar alterações no arquivo JSON
                writeData($data);
                echo json_encode(['message' => 'Registro atualizado com sucesso', 'data' => $updatedEntry]);
            } else {
                echo json_encode(['message' => 'Registro não encontrado']);
            }
        } else {
            echo json_encode(['message' => 'ID do registro não especificado']);
        }
        break;

    case 'delete':
        // Excluir um registro
        if ($id) {
            $data = readData();
            $initialCount = count($data);

            // Filtrar e remover o registro com o ID correspondente
            $data = array_filter($data, function ($entry) use ($id) {
                return $entry['id'] != $id;
            });

            if (count($data) < $initialCount) {
                // Reindexar e salvar no arquivo JSON
                writeData(array_values($data));
                echo json_encode(['message' => 'Registro excluído com sucesso']);
            } else {
                echo json_encode(['message' => 'Registro não encontrado']);
            }
        } else {
            echo json_encode(['message' => 'ID do registro não especificado']);
        }
        break;

    default:
        echo json_encode(['message' => 'Ação não suportada']);
        break;
}
?>
