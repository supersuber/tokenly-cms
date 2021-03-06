<?php
namespace API;
/*
					COPYRIGHT

Copyright 2007 Sergio Vaccaro <sergio@inservibile.org>

This file is part of JSON-RPC PHP.

JSON-RPC PHP is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

JSON-RPC PHP is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with JSON-RPC PHP; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * The object of this class are generic jsonRPC 1.0 clients
 * http://json-rpc.org/wiki/specification
 *
 * @author sergio <jsonrpcphp@inservibile.org>
 */
class Bitcoin
{

	
	/**
	 * Debug state
	 *
	 * @var boolean
	 */
	private $debug;
	
	/**
	 * The server URL
	 *
	 * @var string
	 */
	private $url;
	/**
	 * The request id
	 *
	 * @var integer
	 */
	private $id = 0;
	/**
	 * If true, notifications are performed instead of requests
	 *
	 * @var boolean
	 */
	private $notification = false;
	
	public $modify_request = true;
	
	protected static $utxoset = false;
	
	/**
	 * Takes the connection parameters
	 *
	 * @param string $url
	 * @param boolean $debug
	 */
	public function __construct($url,$debug = false) {
		// server URL
		$this->url = $url;
		// proxy
		empty($proxy) ? $this->proxy = '' : $this->proxy = $proxy;
		// debug state
		empty($debug) ? $this->debug = false : $this->debug = true;
		// message id
		$this->id = 1;
	}
	
	/**
	 * Sets the notification state of the object. In this state, notifications are performed, instead of requests.
	 *
	 * @param boolean $notification
	 */
	public function setRPCNotification($notification) {
		empty($notification) ?
							$this->notification = false
							:
							$this->notification = true;
	}
	
	/**
	 * Performs a jsonRCP request and gets the results as an array
	 *
	 * @param string $method
	 * @param array $params
	 * @return array
	 */
	public function __call($method,$params) {
		
		// check
		if (!is_scalar($method)) {
			throw new \Exception('Method name has no scalar value');
		}
		
		// check
		if (is_array($params)) {
			// no keys
			$params = array_values($params);
		} else {
			throw new \Exception('Params must be given as array');
		}
		
		// sets notification or request task
		if ($this->notification) {
			$currentId = NULL;
		} else {
			$currentId = $this->id;
		}

		// prepares the request
		$request = array(
						'method' => $method,
						'params' => $params,
						'jsonrpc' => '2.0',
						'id' => $currentId
						);
		$request = json_encode($request);
		if($this->modify_request){
			$request = str_replace(array('[{', '}]'), array('{', '}'), $request);
		}
	
		
		$this->debug && $this->debug.='***** Request *****'."\n".$request."\n".'***** End Of request *****'."\n\n";
		
		// performs the HTTP POST
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $this->url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $request,
            CURLOPT_HTTPHEADER => array('Content-type: application/json'),
            CURLOPT_RETURNTRANSFER => true,
            CURLINFO_HEADER_OUT => true
        ));
        
        $response = curl_exec($ch);
        if($response === false)
        {
			throw new \Exception('Unable to connect to '.$this->url);
		}
        
        $this->debug && $this->debug.='***** Server response *****'."\n".$response.'***** End of server response *****'."\n";
        $response = json_decode($response,true);
		
		// debug output
		if ($this->debug) {
			echo nl2br($debug);
		}
		
		// final checks and return
		if (!$this->notification) {
			// check

			if (@$response['id'] != $currentId) {
				if(isset($response['data'])){
					throw new \Exception($response['data']);
				}
				throw new \Exception('Incorrect response id (request id: '.$currentId.', response id: '.$response['id'].')');
			}
			if (isset($response['error']) AND !is_null($response['error'])) {
				if(isset($response['data'])){
					throw new \Exception($response['data']);
				}				
				throw new \Exception('Request error: '.$response['error']['message']);
			}
			
			return $response['result'];
			
		} else {
			return true;
		}
	}
	

	public function sendfromaddress($address, $amount, $to, $fee = 0.00001)
	{
		$unspent = $this->listunspent();
		$outputsFound = array();
		$totalFound = 0;
		foreach($unspent as $utxo){
			if($utxo['address'] == $address){
				$outputsFound[] = $utxo;
				$totalFound += $utxo['amount'];
				if($totalFound >= $amount){
					break;
				}
			}
		}
		
		if(count($outputsFound) == 0){
			throw new \Exception('No valid unspent outputs found for this address');
		}

		if($totalFound < ($amount + $fee)){
			throw new \Exception('Insufficient funds at this address (need '.(($amount + $fee) - $totalFound).')');
		}
		
		$rawInputs = array();
		foreach($outputsFound as $utxo){
			$item = array('txid' => $utxo['txid'], 'vout' => $utxo['vout']);
			$rawInputs[] = $item;
		}
		

		$rawAddresses = array($to => $amount);
		$leftover = $totalFound - $amount;
		$change = $leftover - $fee;
		if($change > 0.000055){
			$rawAddresses[$address] = $change;
		}
		
		$this->modify_request = false;
		$createRaw = $this->createrawtransaction($rawInputs, $rawAddresses);
		
		$signData = array();
		foreach($outputsFound as $utxo){
			$item = array('txid' => $utxo['txid'], 'vout' => $utxo['vout'], 'scriptPubKey' => $utxo['scriptPubKey']);
			$signData[] = $item;
		}
		
		$signRaw = $this->signrawtransaction($createRaw, $signData);
		$this->modify_request = true;
		
		return $this->sendrawtransaction($signRaw['hex']);
		
	}
	
	public function getaddressbalance($address)
	{
		if(!self::$utxoset){
			$this->updateutxoset();
		}
		$unspent = self::$utxoset;
		$balance = 0;
		foreach($unspent as $utxo){
			if($utxo['address'] == $address){
				$balance += $utxo['amount'];
			}
		}
		return $balance;
	}	
	
	public function updateutxoset()
	{
		self::$utxoset = $this->listunspent();
		return self::$utxoset;
	}
	
	public function getaddresstxlist($address, $level = 0)
	{
		//gets a full list of transactions involving this address, using blockchain.info
		$output = array();
		$limit = 50;
		$offset = 0;
		if($level > 0){
			$offset = $level * $limit;
		}
		$url = 'https://blockchain.info/address/'.$address.'?format=json&limit='.$limit.'&offset='.$offset;
		$get = @file_get_contents($url);
		$decode = json_decode($get, true);
		if($decode AND isset($decode['txs']) AND count($decode['txs']) > 0){
			$tx_count = $decode['n_tx'];
			$pages = ceil($tx_count / $limit);
			
			foreach($decode['txs'] as $tx){
				$item = array();
				$item['txId'] = $tx['hash'];
				$item['time'] = $tx['time'];
				$item['block'] = $tx['block_height'];
				$item['amount'] = 0;
				foreach($tx['inputs'] as $input){
					if($input['prev_out']['addr'] == $address){
						$item['amount'] -= $input['prev_out']['value'];
					}
				}
				foreach($tx['out'] as $out){
					if($out['addr'] == $address){
						$item['amount'] += $out['value'];
					}
				}
				$output[] = $item;
			}
			
			if($level == 0){
				$level_tx = array();
				for($i = 1; $i <= $pages; $i++){
					$level_tx = array_merge($level_tx, $this->getaddresstxlist($address, $i));
				}
				$output = array_merge($output, $level_tx);
				aasort($output, 'block');
				$output = array_values($output);
			}
		}
		return $output;
	}
}
?>

