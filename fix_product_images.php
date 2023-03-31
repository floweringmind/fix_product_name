<?php
// Fixes product images assigned to a store that the product isn't assigned to
// Author: Chris Rosenau

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\ResourceConnection;

require '../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('frontend');

$resource = $objectManager->get(ResourceConnection::class);
$connection = $resource->getConnection();

// Attribute ids for images
// Change these if your attributes ids are different
// 87 -  image/base
// 88 -  small_image
// 89 -  thumbnail
// 134 - swatch_image
// 144 - alt_image
// 181 - series
$imageAttributeIds = [87, 88, 89, 134, 144, 181];

// Get all product images
$sql = "SELECT * FROM catalog_product_entity_varchar WHERE attribute_id IN (" . implode(',', $imageAttributeIds) . ")";
$productImages = $connection->fetchAll($sql);

// Get all products
$sql = "SELECT entity_id, sku FROM catalog_product_entity";
$products = $connection->fetchAll($sql);

$removeValueIds = [];

foreach ($products as $product) {
    $productId = $product['entity_id'];

    // Get all product stores
    $sql = "SELECT a.entity_id as entity_id, a.sku as sku, b.store_id as store_id  FROM catalog_product_entity as a, catalog_product_entity_varchar as b WHERE a.entity_id = ? and a.entity_id = b.entity_id and b.attribute_id = 73";
    $productStores = $connection->fetchAll($sql, [$productId]);

    // Search all images
    foreach ($productImages as $image) {
        if ($image['entity_id'] != $productId) {
            continue;
        }
        $foundStore = false;
        foreach ($productStores as $store) {
            if ($image['store_id'] == $store['store_id']) {
                $foundStore = true;
                break;
            }
        }
        if (!$foundStore) {
            $removeValueIds[] = $image['value_id'];
        }
    }
}

// Remove images
if (!empty($removeValueIds)) {
    $sql = "DELETE FROM catalog_product_entity_varchar WHERE value_id IN (" . implode(',', $removeValueIds) . ")";
    $connection->query($sql);
}

echo "Finished cleaning product images\n";
