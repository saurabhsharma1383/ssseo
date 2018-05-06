<?php

namespace Ss\Seo\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
	protected $objectManager;
	protected $_dir;
	private $pageFactory;
	
	public function __construct(
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Magento\Framework\Filesystem\DirectoryList $dir,
		\Magento\Cms\Model\PageFactory $pageFactory
	) {
		$this->_objectmanager = $objectManager;
		$this->_dir = $dir;
		$this->pageFactory = $pageFactory;
    }	
	
	public function __saveCsvToLocal($spreadsheet_data){
		
		$dir = $this->_dir->getRoot().'/app/code/Ss/Seo/Console/seodata/';

		if (!file_exists($dir)) {
		   mkdir($dir, 0777, true);
		}
		$seoFile = $dir.'seo-'.time().'.csv';

		$fp = fopen($seoFile, "w");
		
		foreach ($spreadsheet_data as $fields) {
			fputcsv($fp, $fields);
		}
		return $seoFile;
	}
	
	public function __UpdateData($seoFile){
		
		$state = $this->_objectmanager->get('Magento\Framework\App\State');
		$state->setAreaCode('frontend');
		// robots.txt
		$robotsFile = $this->_dir->getRoot().'/robots.txt';
		if (!file_exists($robotsFile)) {
		  touch($robotsFile);
		}
		
		$rfile = fopen($robotsFile, "a");
		
		// Obj url_rewrite
		$urlRewrite = $this->_objectmanager->get('Magento\UrlRewrite\Model\UrlRewrite');
		// Obj base url
		$storeManager = $this->_objectmanager->get('Magento\Store\Model\StoreManagerInterface');
		
		$uploadedFile = fopen($seoFile, "r");
		$i=1;
		
		$metaObj = $this->_objectmanager->get('Magento\Framework\View\Result\PageFactory');
		$resultPage = $metaObj->create();
				
		//while(! feof($uploadedFile)){
		while ( ($dataArray = fgetcsv($uploadedFile, 500, ",")) !==FALSE) {
			if($i==1){
				$data = array();
				foreach($dataArray as $key => $value){
					$data[str_replace(' ','',strtolower($value))] = $key;
				}
			}
			if($i!=1){
				$url = $dataArray[$data['url']];
				$title = $dataArray[$data['title']];
				$desc = $dataArray[$data['description']];
				$robots = $dataArray[$data['robots']];
				$canonical = $dataArray[$data['canonical']];
				
				// Get entity from url
				
				$urlSearch = str_replace($storeManager->getStore()->getBaseUrl(),'',$url);
				$collection='';
				$collection = $urlRewrite->getCollection()
								->addFieldToFilter('request_path', $urlSearch)->getData();
				
				if($collection){
					$entityType = $collection[0]['entity_type'];
				}
				else{
					$entityType = '';
				}
				// update title based on entity
				
				if($entityType=='category'){
					// update category title
					
					$categoryId = $collection[0]['entity_id'];
					if($categoryId){
						$category = $this->_objectmanager->create('Magento\Catalog\Model\Category')->load($categoryId);
						
						$category->setMetaTitle($title);
						$category->setMetaDescription($desc);
						$category->save();
					}
				}else if($entityType=='product'){	
					// update product title
					$productId = $collection[0]['entity_id'];
					if($productId){
						$product = $this->_objectmanager->create('Magento\Catalog\Model\Product')->load($productId);
						
						$product->setMetaTitle($title);
						$product->setMetaDescription($desc);
						$product->save();
					}
				}else{
					$page = $this->pageFactory->create();
					foreach($page->getCollection() as $item)
					{
						$item->setTitle($title);
						$item->setTitle($desc);
						$item->save();
					}
				}
				// open robots.txt
				
				if($robots=='Allow'){
					continue;
				}
				else{
					$robotsDataCan = 'DisAllow		'.$canonical.PHP_EOL;
					fwrite($rfile, $robotsDataCan);
				}
			}
			$i++;
		}
		
		//fclose($fp);
		fclose($uploadedFile);
		fclose($rfile);
	}
}
