<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class dbm6000 extends eqLogic {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*
		     * Fonction exécutée automatiquement toutes les minutes par Jeedom
		      public static function cron() {

		      }
	*/

	/*
		     * Fonction exécutée automatiquement toutes les heures par Jeedom
		      public static function cronHourly() {

		      }
	*/

	/*
		     * Fonction exécutée automatiquement tous les jours par Jeedom
		      public static function cronDayly() {

		      }
	*/

	/*     * *********************Méthodes d'instance************************* */

	public function preInsert() {

	}

	public function postInsert() {

	}

	public function preSave() {

	}

	public function postSave() {

	}

	public function preUpdate() {

	}

	public function postUpdate() {

	}

	public function preRemove() {

	}

	public function postRemove() {

	}

	public static function syncEqLogicWithRazberry($_serverId = 1) {

		$server = config::byKey('mssqlServer', 'dbm6000');
		$db_connect = mssql_connect($server, config::byKey('mssqlUsername', 'dbm6000'), config::byKey('mssqlPassword', 'dbm6000'));
		$link = mssql_select_db("DBM6000", $db_connect);

		if (!$link) {
			$result["status"] = "error";
			$result["message"] = "Couldn't connect to the database";

			echo json_encode($result);
			exit;
		}

		$eqLogics = eqLogic::byType('dbm6000');

		$query = "select DoorSN, Name from doors";
		$db = mssql_query($query, $db_connect);
		while (is_array($row = mssql_fetch_assoc($db))) {
			$cmd = $row["DoorSN"];

			$cmds[$cmd] = utf8_encode($row["Name"]);
			$aliases[$cmd] = utf8_encode($row["Name"]);

			$found = false;

			foreach ($eqLogics as $eqLogic) {
				if ($cmd == $eqLogic->getConfiguration('deviceURL')) {
					$eqLogic_found = $eqLogic;
					$found = true;
					break;
				}
			}

			if (!$found) {
				$eqLogic = new eqLogic();
				$eqLogic->setEqType_name('dbm6000');
				$eqLogic->setIsEnable(1);
				$eqLogic->setIsVisible(1);
				$eqLogic->setName($row["Name"]);
				$eqLogic->setConfiguration('deviceURL', $cmd);
				$eqLogic->save();

				$eqLogic = self::byId($eqLogic->getId());

				$ZeebaseCmd = new dbm6000Cmd();

				$ZeebaseCmd->setType('action');
				$ZeebaseCmd->setSubType('other');
				$ZeebaseCmd->setDisplay('icon', '<i class="fa jeedom-porte-ouverte"></i>');

				$ZeebaseCmd->setName('open');
				$ZeebaseCmd->setEqLogic_id($eqLogic->getId());
				$ZeebaseCmd->setConfiguration('deviceURL', $cmd);
				$ZeebaseCmd->setConfiguration('commandName', 'open');

				$ZeebaseCmd->save();

			} else {
				$eqLogic = $eqLogic_found;
			}

		}
	}

	/*
		     * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
		      public function toHtml($_version = 'dashboard') {

		      }
	*/

	/*     * **********************Getteur Setteur*************************** */
}

class dbm6000Cmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	/*
		     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
		      public function dontRemoveCmd() {
		      return true;
		      }
	*/

	public function execute($_options = array()) {

		$doorSN = $this->getConfiguration('deviceURL');

		error_log("error: " . $doorSN);

		$server = '172.16.130.42'; // Serveur PC
		$port = 6002;

		if (!($sock = socket_create(AF_INET, SOCK_DGRAM, 0))) {
			$errorcode = socket_last_error();
			$errormsg = socket_strerror($errorcode);

			die("Couldn't create socket: [$errorcode] $errormsg \n");
		}

		$input = pack("cccvcc", 0x01, 0x0b, 0x00, $doorSN, 0x00, 0x00);

		if (!socket_sendto($sock, $input, strlen($input), 0, $server, $port)) {
			$errorcode = socket_last_error();
			$errormsg = socket_strerror($errorcode);

			die("Could not send data: [$errorcode] $errormsg \n");
		}

	}

	/*     * **********************Getteur Setteur*************************** */
}

?>
