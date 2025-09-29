<?php

	/*

	parseUserAgentString.php Class (Without Bots)
	Version 1.19
	Written by Thomas Parkison.
	thomas.parkison@gmail.com
	Download from http://www.toms-world.org/blog/parseuseragentstring

	================================================================

	All returned data is stored as variables in the class object so you would use the following code
	to access the data once the function above has been called.
	$parser->fullname;

	$parser->browsername — Browser Name
	$parser->osname — Operating System Name
	$parser->browserversion — Browser Version
	$parser->type — Device Type (PC, mobile, or bot)


	Boolean Values, it should be self-explanatory.
	$parser->javascript
	$parser->iframe
	$parser->css
	$parser->frames
	$parser->cookies
	$parser->backgroundsounds
	$parser->vbscript
	$parser->java
	$parser->activex
	$parser->mobile
	$parser->x86 (This indicates that the host OS is a 32-bit system)
	$parser->x64 (This indicates that the host OS is a 64-bit system)

	These are variables that are used for browser types, they are Boolean values.
	$parser->firefox
	$parser->firefoxClone
	$parser->chrome
	$parser->chromeClone
	$parser->internetexplorer
	$parser->ie (short version of the above variable)
	$parser->safari
	$parser->opera

	These are variables that are used for operating system types, they are Boolean values.
	$parser->ios
	$parser->android
	$parser->windows
	$parser->linux
	$parser->bsd
	$parser->chromeos
	$parser->macosx
	$parser->blackberry
	*
	*/

	namespace App\Facade;

	# ---------------------------------------------------------------- #
	#                 РЕАЛИЗАЦИЯ   ИНТЕРФЕЙСА                          #
	# ---------------------------------------------------------------- #
	class ParseUserAgentStringClass
	{
		public $classVersion = "1.19";

		public $css = false;
		public $css1 = false;
		public $css2 = false;
		public $javascript = false;
		public $iframe = false;
		public $frames = false;
		public $cookies = false;
		public $backgroundsounds = false;
		public $vbscript = false;
		public $java = false;
		public $activex = false;

		public $mobile = false;

		public $firefoxClone = false;
		public $firefox = false;

		public $chrome = false;
		public $chromeClone = false;

		public $ie = false;
		public $internetexplorer = false;
		public $internetexplorerClone = false;
		public $nextgenie = false;
		public $nextgeninternetexplorer = false;
		public $microsoftspartanie = false;

		public $safari = false;
		public $opera = false;
		public $linux = false;
		public $bsd = false;
		public $chromeos = false;
		public $blackberry = false;

		public $rawVersion = array();

		public $userAgentString = "";
		public $osname = "";
		public $fullname = "";
		public $browsername = "";
		public $browserversion = "";
		public $regexpattern = "";

		public $x86 = false;
		public $x64 = false;

		public $knownbrowser = true;

		public $includeAndroidName = true;
		public $includeWindowsName = true;
		public $includeMacOSName = true;
		public $treatClonesAsTheRealThing = true;
		public $treatProjectSpartanInternetExplorerLikeLegacyInternetExplorer = false;

		public $deviceTypeApp = "app";
		public $deviceTypeBot = "bot";
		public $deviceTypeDownloader = "downloader";
		public $deviceTypeMobile = "mobile";
		public $deviceTypePC = "PC";
		public $deviceTypeScript = "script";

		function StringContains($haystack, $needle) {
			if (stristr($haystack, $needle) === FALSE) return false;
			else return true;
		}

		function parseRawVersion(&$t)
		{
			if (isset($t[0])) {$result['major'] = $t[0];}	else $t[0] = '';
			if (isset($t[1])) {$result['minor'] = $t[1];}	else $t[1] = '';
			if (isset($t[2])) {$result['build'] = $t[2];}	else $t[2] = '';
			if (isset($t[3])) {$result['rev'] = $t[3]  ;}	else $t[3] = '';


			return $result;
		}

		public function parseUserAgentString($userAgent) {
			$this->knownbrowser = true;
			$this->userAgentString = $userAgent;
			global $firephp;
			$browserName = "";
			$browserVersion = "";
			$operatingSystem = "";
			$userAgentData = array();
			$userAgent = trim($userAgent);

			$checkForOS = true;

			# For detecting ELinks.
			if ($this->mypreg_match('%ELinks/([0-9.]*)%i', $userAgent, $matches)) {
				$browserName = "ELinks";
				$operatingSystem = $this->processOperatingSystemString($userAgent);

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]) . "." . trim($t[2]);
				}

				$this->fullname = "$browserName version $browserVersion on $operatingSystem";
				$this->type = $this->deviceTypePC;
				$this->browsername = $browserName;
				$this->osname = $operatingSystem;
				$this->browserversion = $browserVersion;
				return;
			}
			# For detecting BrowseX.
			elseif ($this->mypreg_match('%.*BrowseX \(([0-9.]*) Windows\).*%i', $userAgent, $matches)) {
				$browserName = "BrowseX";
				$operatingSystem = "Windows";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]);
				}
				else $browserVersion = trim($matches[1]);

				$this->fullname = "$browserName version $browserVersion on Windows";
				$this->type = $this->deviceTypePC;
				$this->browsername = $browserName;
				$this->osname = $operatingSystem;
				$this->browserversion = $browserVersion;
				return;
			}
			# For detecting K-Meleon.
			elseif ($this->mypreg_match('%Mozilla/5.0 \(.*\) Gecko/[0-9]* K-Meleon/([0-9.]*)%i', $userAgent, $matches)) {
				$browserName = "K-Meleon";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypePC;
			}
			# For detecting Opera.
			elseif ($this->mypreg_match('%Opera/([0-9.]*) \(.*\) Presto/.* Version/[0-9.]*%i', $userAgent, $matches)) {
				$browserName = "Opera";
				$browserVersion = trim($matches[1]);
				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->opera = true;
			}
			elseif ($this->mypreg_match('%Opera/[0-9.]* \(.*\) Presto/.* Version/([0-9.]*)%i', $userAgent, $matches)) {
				$browserName = "Opera";
				$browserVersion = trim($matches[1]);
				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->opera = true;
			}
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) AppleWebKit/.* \(KHTML, like Gecko\) Chrome/.* Safari/.* OPR/([0-9]*).*%i', $userAgent, $matches)) {
				$browserName = "Opera";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->chromeClone = true;
				$this->opera = true;
				if ($this->treatClonesAsTheRealThing) $this->chrome = true;
			}
			elseif ($this->mypreg_match('%Opera/([0-9.]*) \(Windows NT [0-9.]{3}.*\)%i', $userAgent, $matches)) {
				$browserName = "Opera";
				$browserVersion = trim($matches[1]);
				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->opera = true;
			}
			elseif ($this->mypreg_match('%Mozilla/[0-9.]* \(Windows NT [0-9.]{3}.*\) Opera ([0-9.]*)%i', $userAgent, $matches)) {
				$browserName = "Opera";
				$browserVersion = trim($matches[1]);
				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->opera = true;
			}
			elseif ($this->mypreg_match('%Opera/([0-9.]*) {0,1}\(.*\).*%i', $userAgent, $matches)) {
				$browserName = "Opera";
				$browserVersion = trim($matches[1]);
				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->opera = true;
			}
			elseif ($this->mypreg_match('%Mozilla/[0-9.]* \(.*\) Opera ([0-9.]*) \[en\]%i', $userAgent, $matches)) {
				$browserName = "Opera";

				$t = explode(".", trim($matches[1]));

				if ((int)$t[1] == 0) $browserVersion = $t[0];
				else $browserVersion = $t[0] . "." . $t[1];

				$this->rawVersion['major'] = $t[0];
				$this->rawVersion['minor'] = (int)$t[1];

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->opera = true;
			}
			# For detecting Maxathon.
			elseif ($this->mypreg_match('%Mozilla/.* \(.*MAXTHON ([0-9.]*)\)%i', $userAgent, $matches)) {
				$browserName = "Maxthon";
				$browserVersion = trim($matches[1]);
				$this->type = $this->deviceTypePC;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
			}
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) AppleWebKit/.* \(.*\) Maxthon/([0-9.]*) Chrome/.* Safari/.*%i', $userAgent, $matches)) {
				$browserName = "Maxthon";

				$t = explode(".", trim($matches[1]));

				$browserVersion = $t[0] . "." . $t[1];

				$this->rawVersion = $this->parseRawVersion($t);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->chromeClone = true;
				if ($this->treatClonesAsTheRealThing) $this->chrome = true;
			}
			elseif ($this->mypreg_match('%Mozilla/[0-9.]* \(.*\) AppleWebKit/[0-9.]*\+{0,1} \(KHTML,{0,1} like Gecko\) Maxthon/([0-9.]*) Safari/[0-9.]*%i', $userAgent, $matches)) {
				$browserName = "Maxthon";

				$t = explode(".", trim($matches[1]));

				$browserVersion = $t[0] . "." . $t[1];

				$this->rawVersion = $this->parseRawVersion($t);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->chromeClone = true;
				if ($this->treatClonesAsTheRealThing) $this->chrome = true;
			}
			# For detecting Lunascape in Internet Explorer Mode.
			elseif ($this->mypreg_match('%Mozilla/.*\(.*Trident/[0-9.]*.*rv:[0-9.]*; Lunascape ([0-9.]*)\) like Gecko%i', $userAgent, $matches)) {
				$browserName = "Lunascape (Internet Explorer Mode)";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->vbscript = true;
				$this->java = true;
				$this->activex = true;
				$this->css1 = true;
				$this->internetexplorerClone = true;
				if ($this->treatClonesAsTheRealThing) {
					$this->ie = true;
					$this->internetexplorer = true;
				}
			}
			# For detecting Microsoft Internet Explorer 12 (Project Spartan).
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) AppleWebKit/.* \(KHTML, like Gecko\) Chrome/[0-9.]* Safari/[0-9.]* Edge/([0-9.]*)%i', $userAgent, $matches)) {
				$browserName = "Microsoft Internet Explorer 12 (Project Spartan)";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->nextgenie = true;
				$this->nextgeninternetexplorer = true;
				$this->microsoftspartanie = true;

				if ($this->treatProjectSpartanInternetExplorerLikeLegacyInternetExplorer) {
					$this->ie = true;
					$this->internetexplorer = true;
				}
			}
			# For detecting Internet Explorer.
			elseif ($this->mypreg_match('%Mozilla/[0-9.]* \(.*Trident/7.0; Touch; rv:[0-9.]*\) like Gecko%i', $userAgent)) {
				$browserName = "Internet Explorer (Touch)";
				$browserVersion = "11.0";
				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->css1 = true;
				$this->java = true;
				$this->ie = true;
				$this->internetexplorer = true;
			}
			elseif ($this->mypreg_match('%Mozilla{1,2}/.*\(compatible; MSIE(?: ){0,1}([0-9.]*).*Avant Browser;.*Avant Browser; TheWorld\)%i', $userAgent, $matches)) {
				$browserName = "TheWorld Browser";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->vbscript = true;
				$this->java = true;
				$this->activex = true;
				$this->css1 = true;
				$this->internetexplorerClone = true;
				if ($this->treatClonesAsTheRealThing) {
					$this->ie = true;
					$this->internetexplorer = true;
				}
			}
			elseif ($this->mypreg_match('%Mozilla/.* \(.*MSIE ([0-9.]*).*Trident/[0-9.]*; Touch;.*\)%i', $userAgent, $matches)) {
				$browserName = "Internet Explorer (Touch)";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$browserVersion = trim($t[0]);

					$this->rawVersion = $this->parseRawVersion($t);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypeMobile;
				$this->mobile = true;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->css1 = true;
				$this->ie = true;
				$this->internetexplorer = true;
			}
			elseif ($this->mypreg_match('%Mozilla{1,2}/.*\(compatible; MSIE(?: ){0,1}([0-9.]+).*\)%i', $userAgent, $matches)) {
				$browserName = "Internet Explorer";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->vbscript = true;
				$this->java = true;
				$this->activex = true;
				$this->css1 = true;
				$this->ie = true;
				$this->internetexplorer = true;
			}
			elseif ($this->mypreg_match('%Mozilla/.* \(.*Trident/[0-9.]*; Touch;.*rv:([0-9.]*)\) like.*%i', $userAgent, $matches)) {
				$browserName = "Internet Explorer (Touch)";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$browserVersion = trim($t[0]);

					$this->rawVersion = $this->parseRawVersion($t);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypeMobile;
				$this->mobile = true;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->css1 = true;
				$this->ie = true;
				$this->internetexplorer = true;
			}
			elseif ($this->mypreg_match('%Mozilla/.*\(.*Trident/[0-9.]*.*rv:([0-9.]*)\) like Gecko%i', $userAgent, $matches)) {
				$browserName = "Internet Explorer";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$browserVersion = trim($t[0]);

					$this->rawVersion = $this->parseRawVersion($t);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->vbscript = true;
				$this->java = true;
				$this->activex = true;
				$this->css1 = true;
				$this->ie = true;
				$this->internetexplorer = true;
			}
			elseif ($this->mypreg_match('%Mozilla/.*\(.*Trident/[0-9.]*.*rv:([0-9.]*).*\) like Gecko%i', $userAgent, $matches)) {
				$browserName = "Internet Explorer";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));
					$browserVersion = trim($t[0]);

					$this->rawVersion = $this->parseRawVersion($t);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->vbscript = true;
				$this->java = true;
				$this->activex = true;
				$this->css1 = true;
				$this->ie = true;
				$this->internetexplorer = true;
			}
			elseif ($this->mypreg_match('%Mozilla/.* \(Mobile;.*Trident/[0-9.]*; Touch; rv:[0-9.]*; IEMobile/([0-9.]*).*\) like.*\(KHTML, like Gecko\).*%i', $userAgent, $matches)) {
				$browserName = "Mobile Internet Explorer";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$browserVersion = trim($t[0]);

					$this->rawVersion = $this->parseRawVersion($t);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypeMobile;
				$this->mobile = true;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->css1 = true;
				$this->ie = true;
				$this->internetexplorer = true;
			}
			elseif ($this->mypreg_match('%Mozilla/.*\(compatible;{0,1} MSIE ([0-9.]*);{0,1} Windows NT [0-9.]*;{0,1}.*Trident/[0-9.]*;{0,1}.*%i', $userAgent, $matches)) {
				$browserName = "Internet Explorer";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$browserVersion = trim($t[0]);

					$this->rawVersion = $this->parseRawVersion($t);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypeMobile;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->css1 = true;
				$this->ie = true;
				$this->internetexplorer = true;
				$this->activex = true;
			}
			elseif ($this->mypreg_match('%.*MSIE ([0-9.]+).*%i', $userAgent, $matches)) {
				$this->fullname = "Microsoft Internet Explorer version " . trim($matches[1]) . " on Unknown Windows Version";
				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				return;
			}
			# For detecting QupZilla.
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) AppleWebKit/.* \(.*\) QupZilla/([0-9.]*) Safari/.*%i', $userAgent, $matches)) {
				$browserName = "QupZilla";

				$t = explode(".", trim($matches[1]));

				$browserVersion = $t[0];

				$this->rawVersion = $this->parseRawVersion($t);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->chromeClone = true;
				if ($this->treatClonesAsTheRealThing) $this->chrome = true;
			}
			# For detecting Yandex Browser.
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) AppleWebKit/.* \(.*\) Chrome/.* YaBrowser/([0-9.]*) Safari/.*%i', $userAgent, $matches)) {
				$browserName = "Yandex Browser";

				$t = explode(".", trim($matches[1]));

				$browserVersion = $t[0];

				$this->rawVersion = $this->parseRawVersion($t);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->chromeClone = true;
				if ($this->treatClonesAsTheRealThing) $this->chrome = true;
			}
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) AppleWebKit/.* \(.*\) YaBrowser/([0-9.]*) Chrome/.* Safari/.*%i', $userAgent, $matches)) {
				$browserName = "Yandex Browser";

				$t = explode(".", trim($matches[1]));

				$browserVersion = $t[0];

				$this->rawVersion = $this->parseRawVersion($t);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->chromeClone = true;
				if ($this->treatClonesAsTheRealThing) $this->chrome = true;
			}
			# For detecting Coc Coc Browser.
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) AppleWebKit/.* \(.*\) coc_coc_browser/([0-9.]*) Chrome/.* Safari/.*%i', $userAgent, $matches)) {
				$browserName = "Coc Coc Browser";

				$t = explode(".", trim($matches[1]));

				$browserVersion = $t[0];

				$this->rawVersion = $this->parseRawVersion($t);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->chromeClone = true;
				if ($this->treatClonesAsTheRealThing) $this->chrome = true;
			}
			# For detecting WhiteHat Aviator.
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) AppleWebKit/.* \(.*\) WhiteHat Aviator/([0-9.]*) Chrome/.* Safari/.*%i', $userAgent, $matches)) {
				$browserName = "WhiteHat Aviator";

				$t = explode(".", trim($matches[1]));

				$browserVersion = $t[0];

				$this->rawVersion = $this->parseRawVersion($t);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->chromeClone = true;
				if ($this->treatClonesAsTheRealThing) $this->chrome = true;
			}
			# For detecting SRWare Iron.
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) AppleWebKit/.* \(.*\) Iron/([0-9.]*) Safari/.*%i', $userAgent, $matches)) {
				$browserName = "SRWare Iron";

				$t = explode(".", trim($matches[1]));

				$browserVersion = $t[0];

				$this->rawVersion = $this->parseRawVersion($t);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->chromeClone = true;
				if ($this->treatClonesAsTheRealThing) $this->chrome = true;
			}
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) AppleWebKit/.* \(.*\) Iron/([0-9.]*) Chrome/.* Safari/.*%i', $userAgent, $matches)) {
				$browserName = "SRWare Iron";

				$t = explode(".", trim($matches[1]));

				$browserVersion = $t[0];

				$this->rawVersion = $this->parseRawVersion($t);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->chromeClone = true;
				if ($this->treatClonesAsTheRealThing) $this->chrome = true;
			}
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) AppleWebKit/.* \(.*\) Chrome/[0-9.]* Iron/([0-9.]*) Safari/.*%i', $userAgent, $matches)) {
				$browserName = "SRWare Iron";

				$t = explode(".", trim($matches[1]));

				$browserVersion = $t[0];

				$this->rawVersion = $this->parseRawVersion($t);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->chromeClone = true;
				if ($this->treatClonesAsTheRealThing) $this->chrome = true;
			}
			# For detecting Cyberfox.
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) Gecko/[0-9]{8} (?:Firefox/.* ){0,1}Cyberfox/([0-9.]*)%i', $userAgent, $matches)) {
				$browserName = "Cyberfox";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->firefoxClone = true;
				if ($this->treatClonesAsTheRealThing) $this->firefox = true;
			}
			# For detecting Iceweasel.
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) Gecko/[0-9]{8} (?:Firefox/.* ){0,1}Iceweasel/([0-9.]*)%i', $userAgent, $matches)) {
				$browserName = "Iceweasel";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->firefoxClone = true;
				if ($this->treatClonesAsTheRealThing) $this->firefox = true;
			}
			# For detecting Palemoon.
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) Gecko/[0-9]{8} (?:Firefox/.* ){0,1}Palemoon/([0-9.]*)%i', $userAgent, $matches)) {
				$browserName = "Palemoon";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->firefoxClone = true;
				if ($this->treatClonesAsTheRealThing) $this->firefox = true;
			}
			# For detecting SeaMonkey.
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) Gecko/[0-9]{8} (?:Firefox/.* ){0,1}SeaMonkey/([0-9.]*)%i', $userAgent, $matches)) {
				$browserName = "SeaMonkey";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->firefoxClone = true;
				if ($this->treatClonesAsTheRealThing) $this->firefox = true;
			}
			# For detecting Lunascape in Gecko/Firefox Mode.
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) Gecko/[0-9]{8} (?:Firefox/.* ){0,1}Lunascape/([0-9.]*)%i', $userAgent, $matches)) {
				$browserName = "Lunascape (Gecko/Firefox Mode)";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->firefoxClone = true;
				if ($this->treatClonesAsTheRealThing) $this->firefox = true;
			}
			# For detecting WaterFox.
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) Gecko/[0-9]{8} (?:Firefox/.* ){0,1}WaterFox/([0-9.]*)%i', $userAgent, $matches)) {
				$browserName = "WaterFox";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->firefoxClone = true;
				if ($this->treatClonesAsTheRealThing) $this->firefox = true;
			}
			# For detecting IceDragon.
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) Gecko/[0-9]{8} (?:Firefox/.* ){0,1}IceDragon/([0-9.]*)%i', $userAgent, $matches)) {
				$browserName = "Comodo IceDragon";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->firefoxClone = true;
				if ($this->treatClonesAsTheRealThing) $this->firefox = true;
			}
			# For detecting Firefox.
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\)(?: Gecko/.*| Gecko){0,1} Firefox/([0-9.]*)%i', $userAgent, $matches)) {
				$browserName = "Firefox";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->firefoxClone = true;
				if ($this->treatClonesAsTheRealThing) $this->firefox = true;
			}
			# For detecting Mobile Chrome on iOS.
			elseif ($this->mypreg_match('%Mozilla/5\.0 \((?:iPhone|iPad); CPU iPhone OS [0-9_]{4,8} like Mac OS X\) AppleWebKit/[0-9.]* \(KHTML, like Gecko\) CriOS/([0-9.]*) Mobile/[A-Za-z0-9]* Safari/[0-9.]*%i', $userAgent, $matches)) {
				$browserName = "Google Chrome";

				$t = explode(".", trim($matches[1]));

				$browserVersion = $t[0];

				$this->rawVersion = $this->parseRawVersion($t);

				$this->type = $this->deviceTypeMobile;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->mobile = true;
				$this->css1 = true;
				$this->chrome = true;
			}
			elseif ($this->mypreg_match('%Mozilla/[0-9.]* \((?:iPhone|iPad); .*\) AppleWebKit/[0-9.]* \(KHTML, like Gecko\) CriOS/([0-9.]*) .*Safari/[0-9.]*%i', $userAgent, $matches)) {
				$browserName = "Google Chrome";

				$t = explode(".", trim($matches[1]));

				$browserVersion = $t[0];

				$this->rawVersion = $this->parseRawVersion($t);

				$this->type = $this->deviceTypeMobile;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->mobile = true;
				$this->css1 = true;
				$this->chrome = true;
			}
			# For detecting QQBrowser.
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) AppleWebKit/[0-9.]* \(KHTML, like Gecko\) {0,1}Version/[0-9.]* MQQBrowser/([0-9.]*) QQ-Manager Mobile Safari/[0-9.]*%i', $userAgent, $matches)) {
				$browserName = "QQBrowser";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->chromeClone = true;
				if ($this->treatClonesAsTheRealThing) $this->chrome = true;
			}
			# For detecting Sleipnir.
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) AppleWebKit/.* Chrome/[0-9.]* Safari/[0-9.]* Sleipnir/([0-9.]*)%i', $userAgent, $matches)) {
				$browserName = "Sleipnir";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->chromeClone = true;
				if ($this->treatClonesAsTheRealThing) $this->chrome = true;
			}
			# For detecting CoolNovo.
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) AppleWebKit/.* Chrome/[0-9.]* Safari/[0-9.]* CoolNovo/([0-9.]*)%i', $userAgent, $matches)) {
				$browserName = "CoolNovo";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->chromeClone = true;
				if ($this->treatClonesAsTheRealThing) $this->chrome = true;
			}
			# For detecting Vivaldi.
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) AppleWebKit/.* Chrome/[0-9.]* Vivaldi/([0-9.]*) Safari/[0-9.]*%i', $userAgent, $matches)) {
				$browserName = "Vivaldi";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->chromeClone = true;
				if ($this->treatClonesAsTheRealThing) $this->chrome = true;
			}
			# For detecting Chromium.
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) (?:AppleWebKit/.*){0,1} Chromium/([0-9.]*) Chrome/[0-9.]* .*(?:Safari/.*){0,1}%i', $userAgent, $matches)) {
				$browserName = "Chromium";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->chromeClone = true;
				if ($this->treatClonesAsTheRealThing) $this->chrome = true;
			}
			# For detecting MXNitro.
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) (?:AppleWebKit/.*){0,1} MxNitro/([0-9.]*) Chrome/[0-9.]* .*(?:Safari/.*){0,1}%i', $userAgent, $matches)) {
				$browserName = "MXNitro";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->chromeClone = true;
				if ($this->treatClonesAsTheRealThing) $this->chrome = true;
			}
			# For detecting UCBrowser.
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) (?:AppleWebKit/.*){0,1} UCBrowser/([0-9.]*) U3/[0-9.]* Mobile Safari/([0-9.]*)%i', $userAgent, $matches)) {
				$browserName = "UCBrowser";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->chromeClone = true;
				if ($this->treatClonesAsTheRealThing) $this->chrome = true;
			}
			elseif ($this->mypreg_match('%UCWEB/[0-9.]* \(.*\) U2/[0-9.]* UCBrowser/([0-9.]*) Mobile%i', $userAgent, $matches)) {
				$browserName = "UCBrowser";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypeMobile;
				$this->javascript = true;
				$this->mobile = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->chromeClone = true;
				if ($this->treatClonesAsTheRealThing) $this->chrome = true;
			}
			elseif ($this->mypreg_match('%UCWEB/[0-9.]* \(.*Nokia[0-9./]*\) U2/[0-9.]* UCBrowser/([0-9.]*) U2/[0-9.]* Mobile%i', $userAgent, $matches)) {
				$browserName = "UCBrowser";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypeMobile;
				$this->javascript = true;
				$this->mobile = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->chromeClone = true;
				if ($this->treatClonesAsTheRealThing) $this->chrome = true;
			}
			# For detecting Superbird.
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) (?:AppleWebKit/.*){0,1}Superbird/([0-9.]*) Chrome/[0-9.]*.*(?:Safari/.*){0,1}%i', $userAgent, $matches)) {
				$browserName = "Superbird";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->chrome = true;
			}
			# For detecting Google Chrome.
			elseif ($this->mypreg_match('%Mozilla/.* \(.*\) (?:AppleWebKit/.*){0,1}Chrome/([0-9.]*).*(?:Safari/.*){0,1}%i', $userAgent, $matches)) {
				$browserName = "Google Chrome";

				if ($this->StringContains($matches[1], ".")) {
					$t = explode(".", trim($matches[1]));

					$this->rawVersion = $this->parseRawVersion($t);

					if ($t[1] == 0) $browserVersion = trim($t[0]);
					else $browserVersion = trim($t[0]) . "." . trim($t[1]);
				}
				else $browserVersion = trim($matches[1]);

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->chrome = true;
			}
			# For detecting QT Integrated Browser.
			elseif ($this->mypreg_match('%Mozilla/[0-9.]* \(.*\) AppleWebKit/[0-9.]* \(KHTML,{0,1} like Gecko\) Qt/([0-9.]*)%i', $userAgent, $matches)) {
				$browserName = "Qt Integrated Browser";

				$t = explode(".", trim($matches[1]));

				$this->rawVersion = $this->parseRawVersion($t);

				$browserVersion = $t[0] . "." . $t[1] . $t[2];

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->safari = true;
			}
			# For detecting Lunascape in WebKit Mode.
			elseif ($this->mypreg_match('%Mozilla/[0-9.]* \(.*\) AppleWebKit/[0-9.]* \(KHTML,{0,1} like Gecko\) lswebkit Safari/[0-9.]* Lunascape/([0-9.]*) Safari/[0-9.]*%i', $userAgent, $matches)) {
				$browserName = "Lunascape (WebKit Mode)";

				$t = explode(".", trim($matches[1]));

				$this->rawVersion = $this->parseRawVersion($t);

				$browserVersion = $t[0] . "." . $t[1] . $t[2];

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->safari = true;
			}
			# For detecting Safari on Windows.
			elseif ($this->mypreg_match('%Mozilla/[0-9.]* \(.*\) AppleWebKit/[0-9.]*\+{0,1} Safari/([0-9.]*)%i', $userAgent, $matches)) {
				$browserName = "Safari";

				$t = explode(".", trim($matches[1]));

				$this->rawVersion = $this->parseRawVersion($t);

				$browserVersion = $t[0] . "." . $t[1] . $t[2];

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->safari = true;
			}
			# For detecting Safari on Mac OSX.
			elseif ($this->mypreg_match('%Mozilla/[0-9.]* \(Macintosh; .*\) AppleWebKit/[0-9.]*\+{0,1} \(KHTML,{0,1} like Gecko\) Version/([0-9.]*) Safari/[0-9.]*%i', $userAgent, $matches)) {
				$browserName = "Safari";

				$t = explode(".", trim($matches[1]));

				$this->rawVersion = $this->parseRawVersion($t);

				$browserVersion = $t[0] . "." . $t[1] . $t[2];

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->safari = true;
			}
			elseif ($this->mypreg_match('%Mozilla/[0-9.]* \(Macintosh; .*\) AppleWebKit/[0-9.]* \(KHTML, like Gecko\) Safari/([0-9.]*)%i', $userAgent, $matches)) {
				$browserName = "Safari";
				$browserVersion = $matches['1'];
				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->safari = true;
			}
			elseif ($this->mypreg_match('%Mozilla/[0-9.]* \((?:iPhone|iPad); .*\) AppleWebKit/[0-9.]*\+{0,1} {1,2}\(KHTML, like Gecko\) Version/([0-9.]*) .*Safari/[0-9.]*%i', $userAgent, $matches)) {
				$browserName = "Safari";

				$t = explode(".", trim($matches[1]));

				$this->rawVersion = $this->parseRawVersion($t);

				$browserVersion = $t[0] . "." . $t[1] . $t[2];

				$this->type = $this->deviceTypeMobile;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->mobile = true;
				$this->css1 = true;
				$this->safari = true;
			}
			elseif ($this->mypreg_match('%Mozilla/[0-9.]* \(Windows.*\) AppleWebKit/[0-9.]*\+{0,1} \(KHTML,{0,1} like Gecko\) Version/([0-9.]*) Safari/[0-9.]*%i', $userAgent, $matches)) {
				$browserName = "Safari";

				$t = explode(".", trim($matches[1]));

				$this->rawVersion = $this->parseRawVersion($t);

				$browserVersion = $t[0] . "." . $t[1] . $t[2];

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->safari = true;
			}
			elseif ($this->mypreg_match('%Mozilla/[0-9.]* \(.*\) AppleWebKit/[0-9.]*\+{0,1} \(KHTML,{0,1} like Gecko\) Qt/([0-9.]*) Safari/([0-9.]*)%i', $userAgent, $matches)) {
				$browserName = "Safari";

				$t = explode(".", trim($matches[1]));

				$this->rawVersion = $this->parseRawVersion($t);

				$browserVersion = $t[0] . "." . $t[1] . $t[2];

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->safari = true;
			}
			elseif ($this->mypreg_match('%Mozilla/[0-9.]* \(Macintosh; .*\) AppleWebKit/[0-9.]*\+{0,1} \(like Gecko\) Safari/([0-9.]*)%i', $userAgent, $matches)) {
				$browserName = "Safari";

				$t = explode(".", trim($matches[1]));

				$this->rawVersion = $this->parseRawVersion($t);

				$browserVersion = $t[0];

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->safari = true;
			}
			elseif ($this->mypreg_match('%Mozilla/[0-9.]* \((?:iPhone|iPad); .*\) AppleWebKit/([0-9.]*) \(KHTML, like Gecko\) Mobile/[0-9.A-Za-z]*%i', $userAgent)) {

				$browserName = "Mobile Safari";

				$t = explode(".", trim($matches[1]));

				$this->rawVersion = $this->parseRawVersion($t);

				$browserVersion = $t[0] . "." . $t[1] . $t[2];

				$this->type = $this->deviceTypeMobile;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->mobile = true;
				$this->css1 = true;
				$this->safari = true;
			}
			elseif ($this->mypreg_match('%Mozilla/[0-9.]* \((?:.(?!android))*\) AppleWebKit/[0-9.]*\+{0,1} \(KHTML,{0,1} like Gecko\).*Safari/([0-9.]*)%i', $userAgent, $matches)) {
				$browserName = "Safari";

				$t = explode(".", trim($matches[1]));

				$this->rawVersion = $this->parseRawVersion($t);

				$browserVersion = $t[0] . "." . $t[1] . $t[2];

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->safari = true;
			}
			# For detecting Android.
			elseif ($this->mypreg_match('%Mozilla/.* \(Linux;(?: ){0,1}U; {0,1}Android ([0-9.]*);.*\) (?:AppleWebKit|App3leWebKit)/[0-9.]*\+{0,1} {1,2}\(KHTML, {0,1}like Gecko\) Version/([0-9.]*).*(?:Mobile){0,1} Safari/.*%i', $userAgent, $matches)) {
				$browserName = "Android Browser";
				$operatingSystem = $this->processAndroidVersion($matches[1]);
				$browserVersion = trim($matches[2]);
				$this->fullname = "$browserName version $browserVersion on $operatingSystem";
				$this->type = $this->deviceTypeMobile;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->mobile = true;
				$this->css1 = true;
				$this->android = true;
			}
			# For detecting Mozilla.
			elseif ($this->mypreg_match('%Mozilla/[0-9.]* \(.*rv:[0-9.]*\) Gecko/[0-9.]* Netscape/([0-9.]*)%', $userAgent, $matches)) {
				$browserName = "Netscape Navigator (Legacy)";

				list($majorVer, $minorVer, $build, $rev) = explode(".", trim($matches[1]));

				$this->rawVersion['major'] = $majorVer;
				$this->rawVersion['minor'] = $minorVer;
				$this->rawVersion['build'] = $build;
				$this->rawVersion['rev'] = $rev;

				$browserVersion = "$majorVer.$minorVer$build";

				$this->type = $this->deviceTypePC;
				$this->frames = true;
				$this->cookies = true;
			}
			# For detecting Mozilla.
			elseif ($this->mypreg_match('%Mozilla/[0-9.]* \(.*rv:([0-9.]*)\) Gecko/[0-9.]* *%', $userAgent, $matches)) {
				$browserName = "Mozilla";

				list($majorVer, $minorVer, $build, $rev) = explode(".", trim($matches[1]));

				$this->rawVersion['major'] = $majorVer;
				$this->rawVersion['minor'] = $minorVer;
				$this->rawVersion['build'] = $build;
				$this->rawVersion['rev'] = $rev;

				$browserVersion = "$majorVer.$minorVer$build";

				$this->type = $this->deviceTypePC;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->java = true;
				$this->css1 = true;
				$this->firefoxClone = true;
				if ($this->treatClonesAsTheRealThing) $this->firefox = true;
			}
			# For detecting Blackberry devices.
			elseif ($this->mypreg_match('%BlackBerry.*/.* Profile/MIDP-2\.0 Configuration/CLDC-1\.1 VendorID/102 ips-agent%i', $userAgent)) {
				$this->fullname = "BlackBerry on RIM OS";
				$this->type = $this->deviceTypeMobile;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->css1 = true;
				$this->blackberry = true;
				return;
			}
			elseif ($this->mypreg_match('%Mozilla/[0-9.]* \(BB(?<blackBerryOSVersion>[0-9]{1,2}).*\) AppleWebKit/[0-9.]*\+ \(KHTML, like Gecko\) Version/(?<blackBerryBrowserVersion>[0-9.]*) Mobile Safari/[0-9.]*\+%i', $userAgent, $matches)) {

				list($majorVer, $minorVer, $build, $rev) = explode(".", trim($matches['blackBerryBrowserVersion']));
				$this->rawVersion['major'] = $majorVer;
				$this->rawVersion['minor'] = $minorVer;
				$this->rawVersion['build'] = $build;
				$this->rawVersion['rev'] = $rev;

				$this->fullname = "BlackBerry mobile browser version $majorVer.$minorVer on Blackberry OS version " . $matches['blackBerryOSVersion'];
				$this->type = $this->deviceTypeMobile;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->css1 = true;
				$this->blackberry = true;
				return;
			}
			elseif ($this->mypreg_match('%NetFront/([0-9.]*).*Windows NT 5.1%i', $userAgent, $matches)) {
				$this->fullname = "NetFront version " . trim($matches[1]) . " on Windows XP";
				$this->type = $this->deviceTypeMobile;
				return;
			}
			elseif ($this->mypreg_match('%.*HTTrack ([0-9.]*)x.*%i', $userAgent, $matches)) {
				$browserName = "HTTrack";

				list($majorVer, $minorVer, $build, $rev) = explode(".", trim($matches[1]));
				$this->rawVersion['major'] = $majorVer;
				$this->rawVersion['minor'] = $minorVer;

				$browserVersion = "$majorVer.$minorVer";

				$this->type = $this->deviceTypeMobile;
				$this->javascript = true;
				$this->iframe = true;
				$this->css = true;
				$this->frames = true;
				$this->cookies = true;
				$this->backgroundsounds = true;
				$this->css1 = true;
				$this->blackberry = true;
			}
			// For detecting OSSProxy.
			elseif ($this->mypreg_match('%.*OSSProxy ([0-9.]*).*%i', $userAgent, $matches)) {

				list($majorVer, $minorVer, $build, $rev) = explode(".", trim($matches[1]));
				$this->rawVersion['major'] = $majorVer;
				$this->rawVersion['minor'] = $minorVer;
				$this->rawVersion['build'] = $build;
				$this->rawVersion['rev'] = $rev;

				$this->fullname = "OSSProxy version $majorVer.$minorVer Build $build.$rev";
				$this->type = $this->deviceTypePC;
				return;
			}
			// For detecting PHP.
			elseif ($this->mypreg_match('%HTTP_Request2/([0-9.]*) \(http://pear.*\) PHP/([0-9.]*)%i', $userAgent, $matches)) {
				$this->fullname = "HTTP Request version " . trim($matches[1]) . " on PHP version " . trim($matches[2]);
				$this->type = $this->deviceTypeScript;
				return;
			}
			elseif ($this->mypreg_match('%PHP/([0-9.]*)%i', $userAgent, $matches)) {
				$this->fullname = "PHP version " . trim($matches[1]);
				$this->type = $this->deviceTypeScript;
				return;
			}
			// For detecting Go HTTP Package.
			elseif ($this->mypreg_match('%Go ([0-9.]*) package http%i', $userAgent, $matches)) {
				$this->fullname = "Go HTTP Package version " . trim($matches[1]);
				$this->type = $this->deviceTypeScript;
				return;
			}
			# For detecting Perl.
			elseif ($this->mypreg_match('%.*libwww-perl/([0-9.]*).*%i', $userAgent, $matches)) {
				$this->fullname = "Perl version " . $matches[1];
				$this->type = $this->deviceTypeScript;
				return;
			}
			# For detecting Wordpress and other things connected to Wordpress.
			elseif (($this->mypreg_match('%.*Jetpack.*%i', $userAgent, $matches)) and ($this->mypreg_match('%.*Wordpress.*%i', $userAgent, $matches))) {
				$this->fullname = "Jetpack by WordPress.com";
				$this->type = $this->deviceTypeScript;
				return;
			}
			elseif ($this->mypreg_match('%.*WordPress/([0-9.]*);.*%i', $userAgent, $matches)) {
				$this->fullname = "Wordpress version " . trim($matches[1]);
				$this->type = $this->deviceTypeScript;
				return;
			}
			elseif ($this->mypreg_match('%\AWordPress\Z%i', trim($userAgent))) {
				$this->fullname = "WordPress";
				$this->type = $this->deviceTypeScript;
				return;
			}
			elseif ($this->mypreg_match('%.*XML-RPC PHP Library -- WordPress/([0-9.]*).*%i', $userAgent, $matches)) {
				$this->fullname = "WordPress XML-RPC Library version " . trim($matches[1]);
				$this->type = $this->deviceTypeScript;
				return;
			}
			# For detecting Free Download Manager.
			elseif ($this->mypreg_match('%FDM ([0-9]{1,2})\.x%i', $userAgent, $matches)) {
				$this->fullname = "Free Download Manager version " . trim($matches[1]);
				$this->type = $this->deviceTypeDownloader;
				return;
			}
			# For detecting DnloadMage.
			elseif ($this->mypreg_match('%DnloadMage ([0-9.]*)%i', $userAgent, $matches)) {
				$this->fullname = "DnloadMage version " . trim($matches[1]);
				$this->type = $this->deviceTypeDownloader;
				return;
			}
			# For detecting Download Master.
			elseif ($this->mypreg_match('%.*Download Master%i', $userAgent, $matches)) {
				$this->fullname = "Download Master";
				$this->type = $this->deviceTypeDownloader;
				return;
			}
			# For detecting StarDownloader.
			elseif ($this->mypreg_match('%.*StarDownloader/([0-9.]*).*%i', $userAgent, $matches)) {
				$this->fullname = "StarDownloader version " . $matches[1];
				$this->type = $this->deviceTypeDownloader;
				return;
			}
			# For detecting WGET.
			elseif ($this->mypreg_match('%.*wget/([0-9.]*).*%i', $userAgent, $matches)) {
				list($majorVer, $minorVer) = explode(".", $matches[1]);
				$this->fullname = "WGet version $majorVer.$minorVer";
				$this->type = $this->deviceTypeDownloader;
				return;
			}
			# For detecting File Downloader.
			elseif ($this->mypreg_match('%.*FileDownloader/([0-9.]*).*%i', $userAgent, $matches)) {
				list($majorVer, $minorVer) = explode(".", $matches[1]);
				$this->fullname = "FileDownloader version $majorVer.$minorVer";
				$this->type = $this->deviceTypeDownloader;
				return;
			}
			# For detecting Python.
			elseif ($this->mypreg_match('%Python-urllib/([0-9.]*)%i', $userAgent, $matches)) {
				$this->fullname = "Python-urllib version " . $matches[1];
				$this->type = $this->deviceTypeScript;
				return;
			}
			# For detecting Ruby.
			elseif ($this->mypreg_match('%HTTPClient/[0-9.]* \((?P<rubyHTTPClientVersion>[0-9.]*), ruby (?P<rubyVersion>[0-9.]*).*\)%i', $userAgent, $matches)) {
				list($rubyHTTPMajorVersion, $rubyHTTPMinorVersion, $useless) = explode(".", $matches['rubyHTTPClientVersion']);
				list($rubyMajorVersion, $rubyMinorVersion, $useless) = explode(".", $matches['rubyVersion']);
				$this->type = $this->deviceTypeBot;
				$this->fullname = "Ruby HTTP Client version $rubyHTTPMajorVersion.$rubyHTTPMinorVersion on Ruby version $rubyMajorVersion.$rubyMinorVersion";
				return;
			}
			elseif ($this->mypreg_match('%\ARuby\Z%i', trim($userAgent))) {
				$this->fullname = "Unknown version of Ruby";
				$this->type = $this->deviceTypeScript;
				return;
			}
			elseif ($this->mypreg_match('%.*ruby ([0-9.]*).*%i', $userAgent, $matches)) {
				list($rubyMajorVersion, $rubyMinorVersion, $useless) = explode(".", $matches[1]);
				$this->fullname = "Ruby version $rubyMajorVersion.$rubyMinorVersion";
				$this->type = $this->deviceTypeBot;
				return;
			}
			# For detecting Java.
			elseif ($this->mypreg_match('%Java/{0,1}1\.(?P<version>[0-9])\.(?:0|1)_(?P<update>[0-9]*)%i', $userAgent, $matches)) {
				$this->fullname = "Java " . $matches['version'] . " Update " . (int)$matches['update'];
				$this->type = $this->deviceTypeScript;
				return;
			}
			elseif ($this->mypreg_match('%Java/([0-9.]*)%', $userAgent, $matches)) {
				list($javaMainVer, $javaMinorVer, $useless) = explode(".", $matches[1]);
				$this->fullname = "Java $javaMainVer.$javaMinorVer";
				$this->type = $this->deviceTypeScript;
				return;
			}
			elseif ($this->mypreg_match('%.*UCBrowser.*%i', $userAgent, $matches)) {
				$this->fullname = "UCBrowser";
				return;
			}
			elseif ($this->mypreg_match('%.*OffByOne.*\) Webster Pro V([0-9.]*)%i', $userAgent, $matches)) {
				$this->fullname = "OffByOne Browser version " . $matches[1];

				$browserName = "OffByOne Browser";
				$browserVersion = $matches[1];

				list($majorVer, $minorVer) = explode(".", trim($matches[1]));
				$this->rawVersion['major'] = $majorVer;
				$this->rawVersion['minor'] = $minorVer;

				$this->type = $this->deviceTypePC;
			}
			else {
				$this->type = "unknown";
				$this->fullname = "unknown";
				$checkForOS = false;
				return;
			}

			if ($checkForOS) $operatingSystem = $this->processOperatingSystemString($userAgent);

			if (!empty($browserVersion)) {
				$this->fullname = "$browserName version $browserVersion on $operatingSystem";
				$this->browsername = $browserName;
				$this->osname = $operatingSystem;
				$this->browserversion = $browserVersion;
			}
			else {
				$this->fullname = "$browserName (unknown version) on $operatingSystem";
				$this->browsername = $browserName;
				$this->osname = $operatingSystem;
				$this->browserversion = 0;
			}
			return;
		}

		function processOperatingSystemString($userAgent) {
				if (preg_match('/(?:Win95|Windows 95)/', $userAgent, $matches)) {
					$this->type = $this->deviceTypePC;
					$this->windows = true;
					$operatingSystem = "Windows 95";
				}
				elseif (preg_match('/(?:Win98|Windows 98)/', $userAgent, $matches)) {
					$this->type = $this->deviceTypePC;
					$this->windows = true;
					$operatingSystem = "Windows 98";
				}
				elseif (preg_match('/(?:WinXP|Windows XP)/', $userAgent, $matches)) {
					$this->type = $this->deviceTypePC;
					$this->windows = true;
					if ($this->includeWindowsName) $operatingSystem = "Windows XP";
					else $operatingSystem = "Windows NT 5.1";
				}
				elseif (preg_match('/(?:WinME|Windows ME)/', $userAgent, $matches)) {
					$this->type = $this->deviceTypePC;
					$this->windows = true;
					$operatingSystem = "Windows ME";
				}
				elseif (preg_match('/(?:Win2000|Win2k|Windows 2000)/', $userAgent, $matches)) {
					$this->type = $this->deviceTypePC;
					$this->windows = true;
					$operatingSystem = "Windows 2000";
					$this->windowsNTVersion = 5.0;
				}
				elseif (preg_match('/Windows Phone [0-9.]*/', $userAgent, $matches)) {
					$this->type = $this->deviceTypeMobile;
					$operatingSystem = trim($matches[0]);
				}
				elseif (preg_match('/Windows NT (?P<windowsVersion>[0-9.]*)/', $userAgent, $matches)) {
					$windowsVersion = floatval(trim($matches['windowsVersion']));

					if ($this->includeWindowsName) {
						if ($windowsVersion == 4) $operatingSystem = "Windows NT 4.0";
						elseif ($windowsVersion == 5) $operatingSystem = "Windows 2000";
						elseif ($windowsVersion == 5.1) $operatingSystem = "Windows XP";
						elseif ($windowsVersion == 5.2) $operatingSystem = "Windows XP (64-bit)";
						elseif ($windowsVersion == 6) $operatingSystem = "Windows Vista";
						elseif ($windowsVersion == 6.1) $operatingSystem = "Windows 7";
						elseif ($windowsVersion == 6.2) $operatingSystem = "Windows 8";
						elseif ($windowsVersion == 6.3) $operatingSystem = "Windows 8.1";
						elseif (($windowsVersion == 6.4) or ($windowsVersion == 10)) $operatingSystem = "Windows 10";
						elseif ($windowsVersion == 8.1) {
							$this->windowsNTVersion = 6.3;
							$operatingSystem = "Windows 8.1";
						}
						else $operatingSystem = "Unknown Version of Windows";
					}
					else $operatingSystem = "Windows NT $windowsVersion";

					$this->windowsNTVersion = (float)$windowsVersion;
					$this->type = $this->deviceTypePC;
					$this->windows = true;
				}
				elseif (preg_match('/Windows NT/', $userAgent, $matches)) {
					$this->type = $this->deviceTypePC;
					$operatingSystem = "Unknown Windows NT Version";
					$this->windowsNTVersion = "unknown";
				}
				elseif (preg_match('/Mac OS X 10(?:_|\.){0,1}(?P<macOSXVersion>[0-9]{1,2})/', $userAgent, $matches)) {
					$matches['macOSXVersion'] = trim($matches['macOSXVersion']);

					if (($matches['macOSXVersion'] != "10") and (strlen($matches['macOSXVersion']) == 2)) $matches['macOSXVersion'] = substr($matches['macOSXVersion'], 0, 1);

					$macOSXVersion = intval(trim($matches['macOSXVersion']), 10);

					if ($this->includeMacOSName) {
						if ($macOSXVersion == 4) $operatingSystem = "Mac OSX 10.4 Tiger";
						elseif ($macOSXVersion == 5) $operatingSystem = "Mac OSX 10.5 Leopard";
						elseif ($macOSXVersion == 6) $operatingSystem = "Mac OSX 10.6 Snow Leopard";
						elseif ($macOSXVersion == 7) $operatingSystem = "Mac OSX 10.7 Lion";
						elseif ($macOSXVersion == 8) $operatingSystem = "Mac OSX 10.8 Mountain Lion";
						elseif ($macOSXVersion == 9) $operatingSystem = "Mac OSX 10.9 Mavericks";
						elseif ($macOSXVersion == 10) $operatingSystem = "Mac OSX 10.10 Yosemite";
						elseif ($macOSXVersion == 11) $operatingSystem = "Mac OSX 10.11 Gala";
						else $operatingSystem = "Mac OSX 10 (Unknown Version)";
					}
					else $operatingSystem = "Mac OSX 10.$macOSXVersion";

					$this->macosxVersion = (float)"10.$macOSXVersion";
					$this->type = $this->deviceTypePC;
					$this->macosx = true;
				}
				elseif (preg_match('/PPC Mac OS X/', $userAgent, $matches)) {
					$this->type = $this->deviceTypePC;
					$operatingSystem = "PowerPC Mac OSX";
					$this->macosx = true;
				}
				elseif (preg_match('%Android ([0-9.]*)%i', $userAgent, $matches)) {
					$this->type = $this->deviceTypeMobile;
					$this->mobile = true;
					$operatingSystem = $this->processAndroidVersion($matches[1]);
					$this->android = true;
				}
				elseif (preg_match('%Android%i', $userAgent)) {
					$this->type = $this->deviceTypeMobile;
					$operatingSystem = "Android";
					$this->mobile = true;
					$this->android = true;
				}
				elseif (preg_match('%Gummy_Charged_([A-Z]{3,})_([0-9.]*)%i', $userAgent, $matches)) {
					$this->type = $this->deviceTypeMobile;
					$operatingSystem = $this->processAndroidVersion($matches[1]) . " (Third-Party ROM, Gummy Charged version " . $matches[2] . ")";
					$this->mobile = true;
					$this->android = true;
				}
				elseif (preg_match('%CrOS%i', $userAgent)) {
					$this->type = $this->deviceTypePC;
					$this->chromeos = true;
					$operatingSystem = "ChromeOS";
				}
				elseif (preg_match('%Linux%i', $userAgent)) {
					$this->type = $this->deviceTypePC;
					$operatingSystem = "Linux";
					$this->linux = true;
				}
				elseif (preg_match('%Nokia%i', $userAgent)) {
					$this->type = $this->deviceTypeMobile;
					$operatingSystem = "Legacy Nokia OS";
				}
				elseif (preg_match('%FreeBSD%i', $userAgent)) {
					$this->type = $this->deviceTypePC;
					$this->bsd = true;
					$operatingSystem = "FreeBSD";
				}
				elseif (preg_match('%OpenBSD%i', $userAgent)) {
					$this->type = $this->deviceTypePC;
					$this->bsd = true;
					$operatingSystem = "OpenBSD";
				}
				elseif (preg_match('/(?:iPhone OS |iPad;(?:U;){0,1} CPU OS )([0-9_.]*)/i', $userAgent, $matches)) {
					$this->type = $this->deviceTypeMobile;

					if ($this->StringContains($matches[1], "_")) list($majorVer, $minorVer, $build) = explode("_", trim($matches[1]));
					elseif ($this->StringContains($matches[1], ".")) list($majorVer, $minorVer, $build) = explode(".", trim($matches[1]));

					$this->iosVersion = floatval($majorVer . "." . $minorVer);

					if ($build != "") $operatingSystem = "iOS version " . $majorVer . "." . $minorVer . "." . $build;
					else $operatingSystem = "iOS version " . $majorVer . "." . $minorVer;

					$this->mobile = true;
					$this->ios = true;
				}
				elseif (preg_match('%like Mac OS X%i', $userAgent)) {
					$this->type = $this->deviceTypeMobile;
					$operatingSystem = "iOS";
					$this->mobile = true;
					$this->ios = true;
				}
				else $operatingSystem = "Unknown Operating System";

				if ((preg_match('/(?:WOW64|x64|Win64|amd64|x86_64)/i', $userAgent)) and (!$this->StringContains($operatingSystem, " (64-bit)"))) {
					$operatingSystem .= " (64-bit)";
						$this->x64 = true;
				}
				else $this->x86 = true;

				return $operatingSystem;
		}

		function mypreg_match($regExPattern, $haystack, &$matches = "") {
			$this->regexpattern = $regExPattern;
			return preg_match($regExPattern, $haystack, $matches);
		}

		function processAndroidVersion($version) {
			if (preg_match('/\A[0-9.]*\Z/i', $version)) {
				$operatingSystem = "Android " . trim($version);
				$androidVersionPieces = explode(".", trim($version));
				$androidVersion = floatval($androidVersionPieces[0] . "." . $androidVersionPieces[1]);
				$this->androidVersion = $androidVersion;

					if ($this->includeAndroidName) {
						if ($androidVersion == 2.1) $operatingSystem .= " Eclair";
						elseif ($androidVersion == 2.2) $operatingSystem .= " Froyo";
						elseif ($androidVersion == 2.3) $operatingSystem .= " Gingerbread";
						elseif (($androidVersion == 3) or ($androidVersion == 3.0)) $operatingSystem .= " Honeycomb";
						elseif (($androidVersion == 4) or ($androidVersion == 4.0)) $operatingSystem .= " Ice Cream Sandwich";
						elseif (($androidVersion == 4.1) or ($androidVersion == 4.2) or ($androidVersion == 4.3)) $operatingSystem .= " Jellybean";
						elseif ($androidVersion == 4.4) $operatingSystem .= " KitKat";
						elseif (($androidVersion == 5) or ($androidVersion == 5.0) or ($androidVersion == 5.1)) $operatingSystem .= " Lollipop";
					}
			}
			else {
				if ($this->includeAndroidName) {
					if ($version == "GBE") $operatingSystem = "Android version 2.3 Gingerbread";
				}
				else {
					if ($version == "GBE") $operatingSystem = "Android version 2.3";
				}
			}

			return $operatingSystem;
		}


	}



	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ] --------------- #
	# ---------------------------------------------------------------- #

	//Тeкущее значение ошибок
	$error_reporting = error_reporting();
	error_reporting(0);

	$parser = new ParseUserAgentStringClass(); // This creates a new instance of this class object.
	$parser->includeAndroidName = true;
	$parser->includeWindowsName = true;
	$parser->includeMacOSName = true;
	$parser->parseUserAgentString($_SERVER['HTTP_USER_AGENT']); // This calls the parser function in the class object.

	error_reporting($error_reporting);

	return $parser;
