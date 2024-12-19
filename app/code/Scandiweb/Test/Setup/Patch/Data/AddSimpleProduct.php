<?php
    namespace Scandiweb\Test\Setup\Patch\Data;

    use Magento\Framework\Setup\Patch\DataPatchInterface;
    use Magento\Framework\Setup\ModuleDataSetupInterface;
    use Magento\Catalog\Model\ProductFactory;
    use Magento\Store\Model\StoreManagerInterface;
    use Magento\Catalog\Api\ProductRepositoryInterface;
    use Magento\Framework\App\State;
    use Magento\Framework\Exception\LocalizedException;

    class AddSimpleProduct implements DataPatchInterface
    {
        /**
         * @var ModuleDataSetupInterface
         */
        private $moduleDataSetup;

        /**
         * @var ProductFactory
         */
        private $productFactory;

        /**
         * @var StoreManagerInterface
         */
        private $storeManager;

        /**
         * @var ProductRepositoryInterface
         */
        private $productRepository;

        /**
         * @var State
         */
        private $appState;

        /**
         * Constructor
         *
         * @param ModuleDataSetupInterface $moduleDataSetup
         * @param ProductFactory $productFactory
         * @param StoreManagerInterface $storeManager
         * @param ProductRepositoryInterface $productRepository
         * @param State $appState
         */
        public function __construct(
            ModuleDataSetupInterface $moduleDataSetup,
            ProductFactory $productFactory,
            StoreManagerInterface $storeManager,
            ProductRepositoryInterface $productRepository,
            State $appState
        ) {
            $this->moduleDataSetup = $moduleDataSetup;
            $this->productFactory = $productFactory;
            $this->storeManager = $storeManager;
            $this->productRepository = $productRepository;
            $this->appState = $appState;
        }

        /**
         * Apply Patch
         *
         * @return void
         * @throws LocalizedException
         */
        public function apply()
        {
            $this->moduleDataSetup->getConnection()->startSetup();

            // Set Area Code to 'adminhtml' to ensure proper context
            try {
                $this->appState->setAreaCode('adminhtml');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                // Area code is already set, no action needed
            }

            // Define product data
            $productData = [
                'sku' => 'scandiweb-simple-product',
                'name' => 'Scandiweb Simple Product',
                'attribute_set_id' => 4, // Default attribute set ID
                'price' => 49.99,
                'status' => 1, // Enabled
                'visibility' => 4, // Catalog, Search
                'type_id' => 'simple',
                'weight' => 1,
                'description' => 'This is a simple product added by Scandiweb_Test module.',
                'short_description' => 'Simple product description.',
                'tax_class_id' => 2, // Taxable Goods
                'category_ids' => [3], // Assign to category ID 3 (e.g., Men)
                'qty' => 100,
                'is_in_stock' => 1,
            ];

            // Check if the product already exists
            try {
                $existingProduct = $this->productRepository->get($productData['sku']);
                // If product exists, skip creation
                $this->moduleDataSetup->getConnection()->endSetup();
                return;
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                // Product does not exist, proceed to create
            }

            /** @var \Magento\Catalog\Model\Product $product */
            $product = $this->productFactory->create();
            $product->setSku($productData['sku']);
            $product->setName($productData['name']);
            $product->setAttributeSetId($productData['attribute_set_id']);
            $product->setPrice($productData['price']);
            $product->setStatus($productData['status']);
            $product->setVisibility($productData['visibility']);
            $product->setTypeId($productData['type_id']);
            $product->setWeight($productData['weight']);
            $product->setDescription($productData['description']);
            $product->setShortDescription($productData['short_description']);
            $product->setTaxClassId($productData['tax_class_id']);
            $product->setCategoryIds($productData['category_ids']);

            // Stock Data
            $stockData = [
                'use_config_manage_stock' => 1,
                'qty' => $productData['qty'],
                'is_qty_decimal' => 0,
                'is_in_stock' => $productData['is_in_stock'],
            ];
            $product->setStockData($stockData);

            // Assign to default store
            $storeId = $this->storeManager->getDefaultStoreView()->getId();
            $product->setStoreId($storeId);

            try {
                $this->productRepository->save($product);
            } catch (\Exception $e) {
                throw new LocalizedException(__("Error while saving product: %1", $e->getMessage()));
            }

            $this->moduleDataSetup->getConnection()->endSetup();
        }

        /**
         * Get Dependencies
         *
         * @return array
         */
        public static function getDependencies()
        {
            return [];
        }

        /**
         * Get Aliases
         *
         * @return array
         */
        public function getAliases()
        {
            return [];
        }
    }
