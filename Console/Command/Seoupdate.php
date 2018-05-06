<?php
namespace Ss\Seo\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Seoupdate extends Command
{
	protected function configure()
	{
		$this->setName('seo:update')
            ->setDescription('Seo Data Updating');
			
       parent::configure();
	}
	protected function __getObjmanager(){
		return \Magento\Framework\App\ObjectManager::getInstance();
	}
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		stream_context_set_default([
			'ssl' => [
				'verify_peer' => false,
				'verify_peer_name' => false,
			]
		]);
		
		$objectManager = $this->__getObjmanager();
		$scopeConfig = $objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface');
		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
		$key = $scopeConfig->getValue('ssgeneral/general/key', $storeScope);

		$url = 'https://docs.google.com/spreadsheets/d/'.$key.'/export?gid=0&format=csv';
		if (($handle = fopen($url, "r")) !== FALSE) {
			while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
				$spreadsheet_data[] = $data;
			}
			fclose($handle);
		}
		else{
			die("Problem reading csv");
		}
		$this->__UpdateSaveData($spreadsheet_data);
		$output->writeln('Seo Data Updated!! Please refresh cache and test.');
	}
	protected function __UpdateSaveData($spreadsheet_data){
		// Save csv to local
		
		$objectManager = $this->__getObjmanager();
		$helper = $objectManager->get('Ss\Seo\Helper\Data');
		
		$seoFile = $helper->__saveCsvToLocal($spreadsheet_data);
		$helper->__UpdateData($seoFile);
		
		/* $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$state = $objectManager->get('Magento\Framework\App\State');
		$state->setAreaCode('frontend'); */
		
		return;
	}
}