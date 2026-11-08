<?php
// api/xml_export.php
// Exports current product configuration as XML.
// Demonstrates XML generation on the server (Syllabus: XML).
// Import happens in pages/products.php (parsing via SimpleXML).

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();
header('Content-Type: application/xml; charset=utf-8');

$products = products_list(false);

$xml = new XMLWriter();
$xml->openMemory();
$xml->setIndent(true);
$xml->startDocument('1.0','UTF-8');
$xml->startElement('products');
foreach ($products as $p) {
    $xml->startElement('product');
    $xml->writeElement('name', $p['name']);
    $xml->writeElement('unit', $p['unit']);
    $xml->writeElement('pricing_method', $p['pricing_method']);
    $xml->writeElement('default_price', (string)(float)$p['default_price']);
    if ($p['sack_size_kg'] !== null) $xml->writeElement('sack_size_kg', (string)(float)$p['sack_size_kg']);
    $xml->writeElement('is_active', $p['is_active'] ? '1':'0');
    $xml->endElement();
}
$xml->endElement();
echo $xml->outputMemory();
