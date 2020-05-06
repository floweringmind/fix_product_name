<?php
// Fixes products images assigned to a store that the product isn't assigned to
// Author Chris Rosenau

include '../app/bootstrap.php';

use Magento\Framework\App\Bootstrap;

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('frontend');

$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
$connection = $resource->getConnection();

// Change these if your attributes ids are different
// 87 -  imabe/base
// 88 -  small_image
// 89 -  thumbnail
// 134 - swatch_image
// 144 - alt_image
// 181 - series  
$sql = "SELECT * FROM catalog_product_entity_varchar WHERE attribute_id = 87 OR attribute_id = 88 OR attribute_id = 89 OR attribute_id = 134 OR attribute_id = 144 OR attribute_id = 181";
$allProductImages = $connection->fetchAll($sql);

$sql = "SELECT entity_id, sku FROM catalog_product_entity";
$allProducts = $connection->fetchAll($sql);

$remove = array();

foreach ($allProducts as $product) {

    $entity_id = $product['entity_id'];

    // get all product stores
    $sql = "SELECT a.entity_id as entity_id, a.sku as sku, b.store_id as store_id  FROM catalog_product_entity as a, catalog_product_entity_varchar as b WHERE a.entity_id = $entity_id and a.entity_id = b.entity_id and b.attribute_id = 73";
    $allProductStores = $connection->fetchAll($sql);

    // search all images
    
    foreach ($allProductImages as $image) {
        $foundStore = false;
        if ($image['entity_id'] == $entity_id){
            foreach ($allProductStores as $imageStore) {
                if ($image['store_id'] == $imageStore['store_id']){
                    $foundStore = true;
                }
            }
            if ($foundStore == false){
                $remove[] = $image['value_id'];
            }
        }   
    }

}

// remove images
foreach ($remove as $value) {
    $sql = "DELETE FROM catalog_product_entity_varchar WHERE value_id = $value";
    $connection->query($sql);
}



echo "Finished cleaning product images
";
