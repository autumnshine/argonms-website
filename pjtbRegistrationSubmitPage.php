<?php
/*
 * Project Throwback website
 * Copyright (C) 2012  GoldenKevin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined("allow entry"))
	require('hackingattempt.php');

require("pjtbBasePage.php");

/**
 * 
 *
 * @author GoldenKevin
 */
class pjtbRegistrationSubmitPage extends pjtbBasePage {
	private $timeout;
	private $message;
	private $url;

	public function __construct() {
		if (!isset($_POST["username"]) || !isset($_POST["password"]))
			require('hackingattempt.php');

		//client side JS should've checked these, but just in case there was a glitch, or js is disabled...
		if (!is_numeric($_POST["birthyear"])) {
			$this->timeout = 3;
			$this->message = "Birthyear must be a number! You will be brought back to the last page";
			$this->url = "index.php?action=regform";
		} else if (strlen($_POST["password"]) < 5) {
			$this->timeout = 3;
			$this->message = "Password is too short. Your password must be at between 5-12 characters long. Please try another.<br />You will be brought back to the last page";
			$this->url = "index.php?action=regform";
		} else if (strlen($_POST["password"]) > 12) {
			$this->timeout = 3;
			$this->message = "Password is too long. Your password must be at between 5-12 characters long. Please try another.<br />You will be brought back to the last page";
			$this->url = "index.php?action=regform";
		} else if (strlen($_POST["username"]) < 4) {
			$this->timeout = 3;
			$this->message = "Username is too short. Your username must be between 4-12 characters long. Please try another.<br />You will be brought back to the last page";
			$this->url = "index.php?action=regform";
		} else if (strlen($_POST["username"]) > 12) {
			$this->timeout = 3;
			$this->message = "Username is too long. Your username must be between 4-12 characters long. Please try another.<br />You will be brought back to the last page";
			$this->url = "index.php?action=regform";
		} else if (!preg_match('/^[A-Za-z0-9_]+$/', $_POST["username"])) {
			//Surprisingly enough, the client does not give "You have entered an incorrect LOGIN ID" for any
			//names with at least one non-alphanumeric character (and underscore), except for period and at sign.
			//But, let's just limit them to alphanumeric and underscore anyway.
			$this->timeout = 3;
			$this->message = "Username must only consist of the characters a-z (lowercase letters), A-Z (uppercase letters), 0-9 (numbers), and _ (underscore). Please try another.<br />You will be brought back to the last page";
			$this->url = "index.php?action=regform";
		} else {
			require('databasemanager.php');
			$con = makeDatabaseConnection();

			$alreadyexists = false;
			$ps = $con->prepare("SELECT COUNT(*) FROM `accounts` WHERE `name` = ?");
			$ps->bind_param('s', $_POST["username"]);
			$ps->execute();
			$ps->bind_result($usermatchcount);
			if ($ps->fetch() && $usermatchcount > 0)
				$alreadyexists = true;
			$ps->close();

			if ($alreadyexists) {
				$this->timeout = 3;
				$this->message = "That username is already being used. Please try another.<br />You will be brought back to the last page";
				$this->url = "index.php?action=regform";
			} else {
				require('hashfunctions.php');
				$salt = makeSalt();
				$passhash = makeSaltedSha512Hash($_POST["password"], $salt);

				$birthday = $_POST["birthyear"] * 10000 + intval($_POST["birthmonth"]) * 100 + intval($_POST["birthday"]);

				$ps = $con->prepare("INSERT INTO `accounts`(`name`,`password`,`salt`,`birthday`) VALUES (?,?,?,?)");
				$ps->bind_param('sssi', $_POST["username"], $passhash, $salt, $birthday);
				$ps->execute();
				$ps->close();

				require('config.php');
				$now = new DateTime(NULL, $timezone);
				$now = $now->format("Y-m-d");
				$ps = $con->prepare("INSERT INTO `websitestats` (`day`,`registrations`) VALUES (?,1) ON DUPLICATE KEY UPDATE `registrations` = `registrations` + 1");
				$ps->bind_param('s', $now);
				$ps->execute();
				$ps->close();

				$this->timeout = 3;
				$this->message = "User {$_POST["username"]} registered successfully. You will be brought to your account's control panel";
				$this->url = "index.php?action=cp";
			}
			$con->close();
		}
	}

	protected function getHtmlHeader() {
		return parent::getHtmlHeader() . "\n<meta http-equiv=\"Refresh\" content=\"{$this->timeout}; {$this->url}\">";
	}

	protected function getBodyContent() {
		return "<p>{$this->message} momentarily (or click <a href=\"{$this->url}\">here</a> to do so immediately).</p>";
	}

	protected function getTitle() {
		return "Project Throwback";
	}
}
?>