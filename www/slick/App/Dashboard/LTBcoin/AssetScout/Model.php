<?php
class Slick_App_Dashboard_LTBcoin_AssetScout_Model extends Slick_Core_Model
{
	function __construct()
	{
		parent::__construct();
		$this->inventory = new Slick_App_Dashboard_LTBcoin_Inventory_Model;
	}
	
	public function getScoutForm()
	{
		$form = new Slick_UI_Form;
		$form->setMethod('GET');
		
		$name = new Slick_UI_Textbox('asset');
		$name->addAttribute('required');
		$name->addAttribute('placeholder', 'Enter an asset name or a username');
		$form->add($name);
		
		return $form;
	}
	
	public function scoutAsset($data)
	{
		if(!isset($data['asset'])){
			throw new Exception('Please enter a asset name');
		}
		
		$getAsset = $this->inventory->getAssetData(strtoupper($data['asset']));
		$isUser = false;
		if(!$getAsset){
			$getUser = $this->get('users', trim($data['asset']), array('userId', 'username', 'slug'), 'username');
			if(!$getUser){
				throw new Exception('Error getting asset or user data');
			}
			$isUser = $getUser;
		}
		
		if($isUser){
			$balances =  $this->inventory->getUserBalances($getUser['userId'], false, 'btc', true);
			return array('isUser' => true, 'user' => $getUser, 'balances' => $balances);
		}
		else{
			$getBalances = $this->getAll('xcp_balances', array('asset' => $getAsset['asset']));
			$totalUsers = 0;
			$totalAddresses = 0;
			$totalBalance = 0;
			$usedAddresses = array();
			$usedUsers = array();
			$balanceList = array();
			foreach($getBalances as $balance){
				$totalBalance += $balance['balance'];
				$getAddress = $this->get('coin_addresses', $balance['addressId']);
				if(!$getAddress OR $getAddress['isXCP'] == 0 OR $getAddress['verified'] == 0){
					continue;
				}
				if(!in_array($balance['addressId'], $usedAddresses)){
					$usedAddresses[] = $getAddress['addressId'];
					$totalAddresses++;
				}
				$getUser = $this->get('users', $getAddress['userId'], array('userId', 'username', 'slug'));
				if(!in_array($getAddress['userId'], $usedUsers)){
					$usedUsers[] = $getUser['userId'];
					$totalUsers++;
				}
				if(!isset($balanceList[$getUser['userId']])){
					$balanceList[$getUser['userId']] = $getUser;
					$balanceList[$getUser['userId']]['balance'] = $balance['balance'];
					$balanceList[$getUser['userId']]['last_check'] = $balance['lastChecked'];
					$balanceList[$getUser['userId']]['addresses'] = 1;
				}
				else{
					$balanceList[$getUser['userId']]['balance'] += $balance['balance'];
					$balanceList[$getUser['userId']]['addresses']++;
					if(strtotime($balance['lastChecked']) > strtotime($balanceList[$getUser['userId']]['last_check'])){
						$balanceList[$getUser['userId']]['last_check'] = $balance['lastChecked'];
					}
				}
				
			}
			return array('isUser' => false, 'asset' => $getAsset['asset'], 'users' => $totalUsers, 'addresses' => $totalAddresses, 'balance' => $totalBalance, 'list' => $balanceList);
		}
	}
	
	
}
