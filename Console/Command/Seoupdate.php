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
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$key = '12EARvPDuOPaTxdFcI4z18bBLSUtf4NcXFwVnXpC9kIs';
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
		
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$helper = $objectManager->get('Ss\Seo\Helper\Data');
		
		$seoFile = $helper->__saveCsvToLocal($spreadsheet_data);
		$helper->__UpdateData($seoFile);
		
		/* $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$state = $objectManager->get('Magento\Framework\App\State');
		$state->setAreaCode('frontend'); */
		
		return;
	}
}