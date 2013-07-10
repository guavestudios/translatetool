<?php

namespace Guave\translatetool;

require 'app/converter/convert.class.php';

$c = new converter();

// JSON

echo "Opening ".__DIR__.'\tests\testdata\test.json';
$testData = $c->load('json', __DIR__.'/tests/testdata/test.json');
var_dump($testData);

echo "Re-rendering data.";
$output = $c->save('json', $testData);
var_dump($output);

echo "Saving file.<br><br>";
$result = $c->write('tests/testoutput/', 'test', $output);

// YAML

echo "Opening ".__DIR__.'\tests\testdata\test.yml';
$testData = $c->load('yaml', __DIR__.'/tests/testdata/test.yml');
var_dump($testData);

echo "Re-rendering data.";
$output = $c->save('yaml', $testData);
var_dump($output);

echo "Saving file.";
$result = $c->write('tests/testoutput/', 'test', $output);