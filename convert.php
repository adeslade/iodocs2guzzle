<?php

$path = $argv[1];
$section = $argv[2];

function getSections($path) {
    $endpoints = file_get_contents($path);
    $endpoints = json_decode($endpoints, true);
    $sections = current($endpoints);

    $foo = array();

    foreach ($sections as $s) {
        $foo[strtolower($s['name'])] = $s['methods'];
    }

    return $foo;
}

$sections = getSections($path);

$section = $sections[strtolower($section)];

$endpoints = $section;
//$endpoints = array_slice($section, 0, 4);

$service = array(
    'operations' => array(
    ),
);

foreach ($endpoints as $endpoint) {
    $name = str_replace(' ', '', $endpoint['MethodName']);

    $uri = $endpoint['URI'];

    if (strtoupper($endpoint['HTTPMethod']) === 'GET') {
        $name = 'Get';

        if (preg_match_all('~/([^:/]+)~', $uri, $matches)) {
            $matches = $matches[1];
            
            foreach ($matches as $match) {
                if (false !== strpos($match, '-')) {
                    $name .= implode(array_map('ucwords', explode('-', $match)));
                } else {
                    $name .= ucwords($match);
                }
            }
        }

        if (preg_match('~:[^/]*$~', $uri) && $name[strlen($name) - 1] === 's') {
            $name = substr($name, 0, -1);
        }
    }

    if (false !== strpos($uri, ':')) {
        $uri = preg_replace('~:([^/]+)~', '{$1}', $uri);
    }

    $service['operations'][$name] = array(
        'httpMethod' => strtoupper($endpoint['HTTPMethod']),
        'uri' => $uri,
        'summary' => $endpoint['Synopsis'],
    );

    $params = array();

    if (preg_match_all('~\{([^\}]+)\}~', $uri, $matches)) {
        $matches = $matches[1];

        foreach ($matches as $match) {
            $params[$match] = array(
                'location' => 'uri',
                'required' => true,
            );
        }
    }

    if (!empty($endpoint['parameters'])) {
        foreach ($endpoint['parameters'] as $param) {
            $type = $param['Type'];

            $insert = array(
                'type' => $type,
                'required' => $param['Required'] === 'Y',
                'description' => $param['Description'],
            );

            if (strlen($param['Default']) != 0) {
                $insert['default'] = $param['Default'];
            }

            if ($type === 'enumerated') {
                $insert['type'] = 'string';
                $insert['enum'] = array_values($param['EnumeratedList']);
            }

            if (in_array($endpoint['HTTPMethod'], array('POST', 'PUT'))) {
                $insert['location'] = 'json';
            } else {
                $insert['location'] = 'query';
            }

            if ($type === 'boolean') {
                $insert['type'] = 'integer';
                $insert['enum'] = array("0", "1");
            }

            if (isset($params[$param['Name']])) {
                $params[$param['Name']] = array_replace($insert, $params[$param['Name']]);
            } else {
                $params[$param['Name']] = $insert;
            }
        }
    }

    if (!empty($params)) {
        $service['operations'][$name]['parameters'] = $params;
    }
}

echo json_encode($service) . PHP_EOL;
