<?php

namespace Sumkabum\Magento2RemoveOrphanImages\Service;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class RemoveOrphanImages
{
    const LOG_TOPIC = 'REMOVE_ORPHAN_IMAGES';
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var State
     */
    private $state;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var Filesystem
     */
    private $filesystem;

    public $imageFilesToKeep = [];
    /**
     * @var \Symfony\Component\Console\Output\OutputInterface|null
     */
    public $output = null;

    public function __construct(
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        State $state,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductRepositoryInterface $productRepository,
        ResourceConnection $resourceConnection,
        Filesystem $filesystem
    ) {
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->state = $state;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepository = $productRepository;
        $this->resourceConnection = $resourceConnection;
        $this->filesystem = $filesystem;
    }

    public function run($deleteFiles = false): int
    {
        $this->logMessage('getting rows from db ...');

        $connection = $this->resourceConnection->getConnection();

        $sql = "
            select value from catalog_product_entity_media_gallery cpemg
                inner join catalog_product_entity_media_gallery_value_to_entity cpemgvte on cpemg.value_id = cpemgvte.value_id
        ";
        $dbImageRows = $connection->fetchCol($sql);
        foreach ($dbImageRows as $row) {
            $this->imageFilesToKeep[ltrim($row, '/')] = $row;
        }

        $mediaDir = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath() . 'catalog/product';


        $this->logMessage('getting files from filesystem ...');
        $allFiles = $this->getFilePaths($mediaDir, $mediaDir . '/cache');

        $filesToRemove = 0;
        $filesToKeep = 0;
        foreach ($allFiles as $file) {

            $pathRel = str_replace($mediaDir, '', $file);
            $fileSlashRemoved = ltrim($pathRel, '/');

            if (!array_key_exists($fileSlashRemoved, $this->imageFilesToKeep)) {
                if ($deleteFiles) {
                    unlink($file);
                    $this->logMessage('deleted: ' . $file);
                } else {
                    $this->logMessage('potentially deleted : ' . $file);
                }
                $filesToRemove++;
            } else {
                $filesToKeep++;
            }
        }
        $this->logMessage('images in filesystem:                 ' . count($allFiles));
        $this->logMessage('media rows related to products in db: ' . count($dbImageRows));
        $this->logMessage('Total files to keep:                  ' . $filesToKeep);
        $this->logMessage('Total files to delete (orphans):      ' . $filesToRemove);

        return $filesToRemove;
    }

    function getFilePaths($dir, $excludeDir = null) {
        $files = array();

        // Get directory contents
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));

        foreach($iterator as $file) {
            if($file->isDir() || ($excludeDir && strpos($file->getPathname(), $excludeDir) !== false)){
                continue;
            }
            $files[] = $file->getPathname();
        }

        return $files;
    }

    private function logMessage($message)
    {
        $this->logger->info(self::LOG_TOPIC . ' ' . $message);
        if ($this->output) {
            $this->output->writeln($message);
        }
    }
}
