<?php
namespace server_counter;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\Server;
use pocketmine\event\server\QueryRegenerateEvent;
use pocketmine\utils\TextFormat as color;

class main extends PluginBase implements Listener{
	public function onEnable(){
		$this->getLogger()->info(color::GREEN.'PlayserCounter > config.yml will be generated!');
		$this->getLogger()->info(color::GREEN.'Playsercounter > Set the server IP Port to get the status!');
		$config = new Config($this->getDataFolder() . "config.yml", Config::YAML,[
			'PluginPrefix' => 'PlayserCounter',
			'target_server' => [
				1 => [
					'ip' => 'be.kuwa.cf',
					'port' => 19132
				],
				[
					'ip' => '0.0.0.0',
					'port' => 19132
				]
				],
			'TimeOut' => 10,
			'debug_log' => false
			// pmmp Memo about YMAL Generation
			// the array here creates a 1 in target_server, creates an ip in it, writes 0.0.0.0,
			// Further array of creating a port and assigning 19132
			/After writing / 1=>○○, it will be serialized without permission if you keep it separated by
		]);
		$this- > getServer ()- > getPluginManager () - > registerEvents ($this, $this); / / Required by QueryRegenerateEvent
	}
	public function getStatus($ip, $port, $timeout){
		$data = b"\x01\x00\x00\x00\x00L\x00\x00\x00\x00\xff\xff\x00\xfe\xfe\xfe\xfe\xfd\xfd\xfd\xfd\x124Vx\x00\x00\x00\x00\x00\x00\x00\x00";

		$ sndtimeo = $ timeout; // connection timeout
		$ rcvtimeo = $ timeout; // receive timeout
		$error = 'None';
		// Socket creation
		$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP );
		// Option Settings
		socket_set_option( $sock, SOL_SOCKET, SO_SNDTIMEO, array("sec"=>$sndtimeo,"usec"=>0) );
		socket_set_option( $sock, SOL_SOCKET, SO_RCVTIMEO, array("sec"=>$rcvtimeo,"usec"=>0) );
		
		// Connect
		try{
			socket_connect ($sock, $ ip, $ port);
		} catch(Exception $e) {
			$error = 'error';
		}

		$error_code = socket_last_error($sock);
		// Error code list https://www.php.net/manual/ja/function.socket-last-error.php#95160
		if($error_code == 0){
			$error = 'None';
		}elseif($error_code == 110){
			$error = 'timeout';
		}elseif($error_code == 11001){
			$error = 'nothost';
		}else{
			$error = socket_strerror($error_code);
		}
		
		if($error == "None"){
			// Send and receive
			$start_time = microtime(true);
			socket_write($sock, $data);
			$end_time = microtime(true);
			$status = socket_read( $sock, 10240 );
			$rec_time = microtime(true);
		
			$connect_time = ($end_time - $start_time) * 1000;
			$rec_time = ($rec_time - $end_time) * 1000;
			$total_time = $connect_time + $rec_time;
			// Once processing time measurement
			$error_code = socket_last_error($sock);
			// Error code list https://www.php.net/manual/ja/function.socket-last-error.php#95160
			if($error_code == 0){
				$error = 'None';
			}elseif($error_code == 110){
				$error = 'timeout';
			}else{
				$error = socket_strerror($error_code);
				// echo $error;
			}
		}
		
		if($error == 'None'){
			$status = strstr($status, 'MCPE');
			$status = explode(';', $status);

			return $status[4];
		}else{
		        return 'error';
	        }
	}

    public function query(QueryRegenerateEvent $event){
		$config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		$prefix = $config->get('PluginPrefix');
		$timeout = $config->get('TimeOut');
		$num = 0;
		$ player_count = sizeof ($this - > getserver () - > Getonlineplayers ()); / / Number of connections on the server itself
		foreach ($config->get('target_server') as $i => $nalue) {
			$num = $num + 1;
			if ($config->get('debug_log') == 'true') {
			$this->getServer()->getLogger()->info(color::BLUE.'getting status...');
			}
			$count = main::getStatus($config->get('target_server')[$num]['ip'], $config->get('target_server')[$num]['port'],$timeout);
			if ($config->get('debug_log') == 'true') {
				$this->getServer()->getLogger()->info(color::YELLOW.$prefix.' > '.
					'ip:'.
					$config->get('target_server')[$num]['ip'].
					' port:'.
					$config->get('target_server')[$num]['port'].
					'Number of people connected:'.
					$count);
				$this->getServer()->getLogger()->info(color::GREEN.$prefix.' > '.'Got the status!');
			}
			$player_count = $player_count + $count;// setting.Add the number of connections of servers in yml
		}
		$event->setPlayerCount($player_count);//Change number of connections
		if ($config->get('debug_log') == 'true') {
			$this->getServer()->getLogger()->info(color::GREEN.$prefix.' > '."Updated the number of connections on the server!");
		}	
    }
}
