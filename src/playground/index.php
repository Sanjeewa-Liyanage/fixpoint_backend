<?php
// version 1.0.0
$dir = '../api'; // replace with your actual directory
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

$endpoints = [];
foreach ($files as $file) {
    if ($file->isDir() || pathinfo($file->getFilename(), PATHINFO_EXTENSION) !== "php") {
        continue;
    }

    $tokens = token_get_all(file_get_contents($file->getPathname()));
    $count = count($tokens);
    for ($i = 0; $i < $count; $i++) {
        if ($tokens[$i][0] === T_FUNCTION) {
            if ($tokens[$i - 2][0] === T_PUBLIC) {
                $methodName = '';
                for ($j = $i + 1; $j < $count; $j++) {
                    if ($tokens[$j] === '(') {
                        break;
                    }
                    if (is_array($tokens[$j])) {
                        $methodName .= $tokens[$j][1];
                    }
                }
                $methodName = trim($methodName);
                if ($methodName !== '__construct') {
                    $filePath = str_replace([$dir, '\\', '.php'], ['', '/', ''], $file->getPathname());
                    $endpoints[] = ltrim($filePath, '/') . '/' . $methodName;
                }
            }
        }
    }
}

function generateOpenAPISpecs($endpoints) {
    $specs = [
        'openapi' => '3.0.0',
        'info' => [
            'title' => 'EzMenu Backend API',
            'version' => '1.0.0',
        ],
        'servers' => [
            ['url' => 'http://localhost/ezmenu-be-php'],
            ['url' => 'https://ezmenu-be.azurewebsites.net/ezmenu-be-php']
        ],
        'paths' => []
    ];

    foreach ($endpoints as $endpoint) {
        $path = '/' . str_replace('Api', '', $endpoint);
        $operationId = str_replace('/', '_', trim($path, '/'));
        $params = [];

        if (stripos($operationId, 'search') !== false || stripos($operationId, 'getall') !== false) {
            $params = [
                [
                    'name' => 'limit',
                    'in' => 'body',
                    'required' => true,
                    'schema' => [
                        'type' => 'integer'
                    ]
                ],
                [
                    'name' => 'page',
                    'in' => 'body',
                    'required' => true,
                    'schema' => [
                        'type' => 'integer'
                    ]
                ]
            ];
        } elseif (stripos($operationId, 'get') !== false || stripos($operationId, 'read') !== false || stripos($operationId, 'delete') !== false || stripos($operationId, 'update') !== false) {
            $params = [
                [
                    'name' => 'id',
                    'in' => 'body',
                    'required' => true,
                    'schema' => [
                        'type' => 'integer'
                    ]
                ]
            ];
        }

        $specs['paths'][$path] = [
            'post' => [
                'summary' => 'Endpoint for ' . $operationId,
                'operationId' => $operationId,
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => !empty($params) ? array_reduce($params, function($carry, $param) {
                                    $carry[$param['name']] = $param['schema'];
                                    return $carry;
                                }, []) : (object)[]
                            ]
                        ]
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Successful response',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'message' => ['type' => 'string'],
                                        'status' => ['type' => 'string', 'enum' => ['success', 'error']],
                                        'data' => ['type' => 'object', 'properties' => (object)[]]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    return json_encode($specs, JSON_PRETTY_PRINT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_openapi'])) {
    $openAPISpecs = generateOpenAPISpecs($endpoints);
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="openapi.json"');
    echo $openAPISpecs;
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Tester</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .api-tester {
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
        }

        .api-tester h2 {
            font-size: 20px;
            margin-bottom: 20px;
        }

        .api-tester .form-group {
            margin-bottom: 20px;
        }

        .api-tester .form-control {
            font-size: 14px;
        }

        .api-tester button {
            font-size: 14px;
        }

        .response {
            margin-top: 40px;
        }

        .response h2 {
            font-size: 20px;
            margin-bottom: 20px;
        }

        .response pre {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="container mt-5 api-tester">
        <h2>fix_point Backend : API Tester</h2>
        <div class="row">
            <div class="col-md-6">
                <div class="api-tester">
                    <h2>Request</h2>
                    <div class="form-group">
                        <label for="url">API URL:</label>
                        <select class="form-control" id="url">
                            <?php foreach ($endpoints as $endpoint) : ?>
                                <?php $endpoint = str_replace('Api', '', $endpoint); ?>
                                <option value="/<?php echo $endpoint; ?>"><?php echo $endpoint; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="method">Method:</label>
                        <select class="form-control" id="method">
                            <option value="GET">GET</option>
                            <option value="POST">POST</option>
                            <option value="PUT">PUT</option>
                            <option value="DELETE">DELETE</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="data">Data:</label>
                        <textarea class="form-control" id="data" rows="5" placeholder="Enter JSON data (if applicable)"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="headers">Headers (JSON format):</label>
                        <textarea class="form-control" id="headers" rows="3">
                    {
                        "Authorization": "Bearer YOUR_DEFAULT_TOKEN"
                    }
                        </textarea>
                    </div>



                    <div class="form-group">
                        <button type="button" class="btn btn-primary" onclick="sendRequest()">Send Request</button>
                        <button type="button" class="btn btn-secondary" onclick="shareRequest()">Share Request</button>
                    </div>
                    <input type="text" class="form-control" id="sharedUrl" readonly>
                    <hr>
                    <div class="axios-code" class="response">
                        <h2>Axios Code:</h2>
                        <pre id="axiosCode"></pre>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="response">
                    <h2>Response:</h2>
                    <pre id="response"></pre>
                </div>
            </div>
        </div>
        <div class="form-group mt-5">
            <form method="post">
                <input type="hidden" name="generate_openapi" value="1">
                <button type="submit" class="btn btn-success">Generate OpenAPI Specs</button>
            </form>
        </div>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        function sendRequest() {
    var url = "/ezmenu-be-php" + $('#url').val();
    var method = $('#method').val();
    var data = $('#data').val();
    var headers = {};

    try {
        headers = JSON.parse($('#headers').val() || '{}');
    } catch (e) {
        $('#response').html('Invalid header JSON format');
        return;
    }

    $.ajax({
        url: url,
        method: method,
        contentType: 'application/json',
        headers: headers,
        data: data,
        success: function(response) {
            $('#response').html(JSON.stringify(response, null, 2));
        },
        error: function(xhr, status, error) {
            $('#response').html('Error: ' + xhr.responseText);
        }
    });

    generateAxiosCode();
}


        function shareRequest() {
            var url = $('#url').val();
            var method = $('#method').val();
            var data = $('#data').val();
            var shareUrl = new URL(location.href);
            shareUrl.searchParams.set('url', url);
            shareUrl.searchParams.set('method', method);
            shareUrl.searchParams.set('data', data);
            $('#sharedUrl').val(shareUrl.href);
        }

        function generateAxiosCode() {
    var baseUrl = location.origin;
    var url = baseUrl + "/ezmenu-be-php" + $('#url').val();
    var method = $('#method').val();
    var data = $('#data').val();
    var headers = $('#headers').val();

    var axiosCode = `
async function fetchData() {
    try {
        const response = await axios({
            url: '${url}',
            method: '${method.toLowerCase()}',
            headers: ${headers || '{}'},
            data: ${data || '{}'},
        });
        console.log(response.data);
    } catch (error) {
        console.error(error);
    }
}
    `;

    $('#axiosCode').text(axiosCode);
}


        $(document).ready(function() {
            var url = new URL(location.href);
            var shareUrl = decodeURIComponent(url.searchParams.get('url'));
            var method = decodeURIComponent(url.searchParams.get('method'));
            var data = decodeURIComponent(url.searchParams.get('data'));
            if (shareUrl) {
                $('#url').val(shareUrl);
            }
            if (method) {
                $('#method').val(method);
            }
            if (data) {
                $('#data').val(data);
            }
        });
    </script>
</body>

</html>