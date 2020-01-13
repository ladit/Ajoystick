<?php

namespace Ajoystick;

use DOMDocument;
use DOMXPath;
use RuntimeException;

/**
 * Class ADB
 * @package Ajoystick
 */
class ADB
{
    use Genyshell;

    /**
     * battery status map
     */
    const BATTERY_STATUS = [
        1 => 'BATTERY_STATUS_UNKNOWN',
        2 => 'BATTERY_STATUS_CHARGING',
        3 => 'BATTERY_STATUS_DISCHARGING',
        4 => 'BATTERY_STATUS_NOT_CHARGING',
        5 => 'BATTERY_STATUS_FULL'
    ];

    /**
     * sleep microseconds after sendKeyEvent, sendText, tap and swipe
     *
     * @var int
     */
    protected static $commandInterval = 500000;

    /**
     * default timeout for waitUntil in microseconds
     *
     * @var int
     */
    public $waitUntilTimeout = 6000000;

    /**
     * default sleep interval for waitUntil in microseconds
     *
     * @var int
     */
    public $waitUntilSleepInterval = 1000000;

    /**
     * the hosting OS
     *
     * @var string
     */
    protected static $hostOS;

    /**
     * grep under specific OS
     *
     * @var string
     */
    protected static $grepCommand;

    /**
     * Android home environment variable
     *
     * @var string
     */
    protected static $androidHome;

    /**
     * adb executive path
     *
     * @var string
     */
    protected static $adbExecutive = 'adb';

    /**
     * whether adbKeyboard is default IME
     *
     * @var bool
     */
    protected static $adbKeyboardIsDefaultIME = false;

    /**
     * specify which device to use
     *
     * @var string
     */
    protected $adbDeviceParam = '';

    /**
     * SDK version of the device
     *
     * @var int
     */
    public $sdkVersion;

    /**
     * width of the device
     *
     * @var int
     */
    public $width;

    /**
     * height of the device
     *
     * @var int
     */
    public $height;

    /**
     * ADB constructor.
     */
    public function __construct()
    {
        self::$hostOS = strtolower(substr(PHP_OS, 0, 3)) === 'win' ? 'windows' : 'linux';

        // system specific commands
        self::$grepCommand = self::$hostOS === 'windows' ? 'findstr' : 'grep';

        if (trim($this->shell('settings get secure default_input_method')) === 'com.android.adbkeyboard/.AdbIME') {
            self::$adbKeyboardIsDefaultIME = true;
        }
    }

    /**
     * @param int $commandInterval
     */
    public static function setCommandInterval(int $commandInterval)
    {
        self::$commandInterval = $commandInterval;
    }

    /**
     * get Android home from env
     *
     * @return string
     */
    public static function getAndroidHome(): string
    {
        if (self::$androidHome === null) {
            // is ANDROID_HOME set?
            $androidHome = getenv('ANDROID_HOME');
            if ($androidHome === false) {
                Log::error('ANDROID_HOME not set');
                exit(1);
            }
            self::$androidHome = $androidHome;
        }
        return self::$androidHome;
    }

    /**
     * @param string $androidHome
     */
    public static function setAndroidHome(string $androidHome)
    {
        self::$androidHome = $androidHome;
    }

    /**
     * @return string
     */
    public static function getAdbExecutive(): string
    {
        return self::$adbExecutive;
    }

    /**
     * @param string $adbExecutive
     */
    public static function setAdbExecutive(string $adbExecutive)
    {
        self::$adbExecutive = $adbExecutive;
    }

    /**
     * specify target device
     *
     * @param string $deviceId
     * @return ADB
     */
    public function setTargetDevice(string $deviceId): ADB
    {
        $this->adbDeviceParam = '-s ' . $deviceId;
        return $this;
    }

    /**
     * adb command
     *
     * @param string $args
     * @param bool $noBlocking
     * @return mixed
     */
    public function adb(string $args, bool $noBlocking = false)
    {
        $command = self::getAdbExecutive() . ' ' . $this->adbDeviceParam . ' ' . $args;
        Log::debug("ADB:adb: $command");
        $handle = popen($command, 'rb');
        if ($handle === false) {
            Log::error("executing command \{$command} failed");
            return "";
        }
        if ($noBlocking === false) {
            stream_set_blocking($handle, 1);
        }
        $output = stream_get_contents($handle);
        pclose($handle);
        return $output;
    }

    /**
     * adb shell command
     *
     * @param string $args
     * @return mixed
     */
    public function shell(string $args)
    {
        return $this->adb('shell ' . $args);
    }

    /**
     * get devices
     *
     * @return array
     */
    public function getDevices(): array
    {
        $result = explode(PHP_EOL, $this->adb('devices'));
        $devices = [];
        for ($i = 0; $i < count($result); $i++) {
            preg_match('/^(.+)?\s+(offline|bootloader|device)$/', $result[$i], $match);
            if (!empty($match)) {
                $devices[] = [
                    'id' => $match[1],
                    'state' => $match[2],
                ];
            }

        }
        return $devices;
    }

    /**
     * get device state: offline | bootloader | device
     *
     * @return string
     */
    public function getDeviceState(): string
    {
        return trim($this->adb('get-state'));
    }

    /**
     * get device ID
     *
     * @return string
     */
    public function getDeviceID(): string
    {
        return trim($this->adb('get-serialno'));
    }

    /**
     * get Android version of device, i.e. 6.0
     *
     * @return string
     */
    public function getAndroidVersion(): string
    {
        return trim($this->shell('getprop ro.build.version.release'));
    }

    /**
     * get SDK version
     *
     * @return int
     */
    public function getSdkVersion(): int
    {
        return intval($this->shell('getprop ro.build.version.sdk'));
    }

    /**
     * get device model
     *
     * @return string
     */
    public function getDeviceModel(): string
    {
        return trim($this->shell('getprop ro.product.model'));
    }

    /**
     * set device locale
     *
     * @param string $locale
     */
    public function setLocale(string $locale)
    {
        $this->shell("\"setprop persist.sys.locale $locale; setprop ctl.restart zygote\"");
    }

    /**
     * update the date and time of the device
     */
    public function updateDateTime()
    {
        $this->shell('"date `date +%m%d%H%M%Y.%S`"');
    }

    /**
     * get process pid
     *
     * @param string $packageName com.android.settings
     * @return int|array
     */
    public function getPid(string $packageName)
    {
        $lines = $this->shell('ps | ' . self::$grepCommand . ' ' . $packageName);
        if ($lines === '') {
            Log::warning("ADB::getPid: the process of $packageName does not exist.");
            return -1;
        }
        $delimiter = self::$hostOS === 'windows' ? "\r\r\n" : "\r\n";
        $processes = explode($delimiter, $lines);
        $result = preg_split('/ +/', $processes[0]);
        return intval($result[1]);
    }

    /**
     * kill process, root required
     *
     * @param int $pid
     * @return bool
     */
    public function killProcess(int $pid): bool
    {
        $result = $this->shell("kill $pid");
        explode(': ', $result);
        if (isset($result[3])) {
            Log::notice("ADB::killProcess: {$result[3]}");
            return false;
        }
        return true;
    }

    /**
     * quit app, similar to kill process
     *
     * @param string $packageName
     * @return void
     */
    public function quitApp(string $packageName)
    {
        $this->shell("am force-stop $packageName");
    }

    /**
     * get current package and activity, i.e. packageName/activityName
     *
     * @return string
     */
    public function getCurrentPackageAndActivity(): string
    {
        $result = trim($this->shell('dumpsys window windows | ' . self::$grepCommand . ' mCurrentFocus'));
        preg_match_all('/mCurrentFocus=Window\{[a-zA-Z0-9]+ u0 (.+?\/.+?)\}/', $result, $matches);
        if (!isset($matches[1][0])) {
            Log::warning("ADB::getFocusedPackageAndActivity: no match for $result");
            return '';
        }
        return $matches[1][0];
    }

    /**
     * get current package
     *
     * @return string
     */
    public function getCurrentPackage(): string
    {
        $currentPackageAndActivity = $this->getCurrentPackageAndActivity();
        $result = explode('/', $currentPackageAndActivity);
        if (isset($result[0])) {
            return $result[0];
        } else {
            Log::error("ADB::getCurrentPackage: explode error, currentPackageAndActivity: $currentPackageAndActivity");
        }

        return '';
    }

    /**
     * get current activity
     *
     * @return string
     */
    public function getCurrentActivity(): string
    {
        $currentPackageAndActivity = $this->getCurrentPackageAndActivity();
        $result = explode('/', $currentPackageAndActivity);
        if (isset($result[1])) {
            return $result[1];
        } else {
            Log::error("ADB::getCurrentActivity: explode error, currentPackageAndActivity: $currentPackageAndActivity");
        }

        return '';
    }

    /**
     * get current screenshot
     *
     * @param string $to string | handle | {destination path}
     * @param int|null $quality if is not null, require ext-gd, Compression level: from 0 (no compression) to 9.
     * @param float|null $scale if is not null, require ext-gd, percentage to scale
     * @return mixed
     */
    public function getCurrentScreenshot(string $to = 'handle', int $quality = null, float $scale = null)
    {
        $this->adb('exec-out screencap -p > /data/local/tmp/screenshot.png');
        $tmpPath = tempnam(sys_get_temp_dir(), 'adScreenshot');
        $this->adb("pull /data/local/tmp/screenshot.png $tmpPath");
        $this->shell('rm /data/local/tmp/screenshot.png');

        if ($quality !== null or $scale !== null) {
            list($width, $height, $type, $attr) = getimagesize($tmpPath);
            $imageHandle = imagecreatefrompng($tmpPath);
            if ($scale !== null) {
                $imageHandle = imagescale($imageHandle, $width * $scale);
            }
            imagepng($imageHandle, $tmpPath, $quality);
        }
        $result = null;
        if ($to === 'handle') {
            $result = fopen('php://memory', 'r+');
            fwrite($result, file_get_contents($tmpPath));
            rewind($result);
            unlink($tmpPath);
        } else if ($to === 'string') {
            $result = file_get_contents($tmpPath);
            unlink($tmpPath);
        } else {
            rename($tmpPath, $to);
            $result = $to;
        }
        return $result;
    }

    /**
     * get current screenshot and save to somewhere
     *
     * @param string|null $directory
     * @param string|null $fileName
     * @param string|null $fileNamePrefix
     * @return string
     */
    public function screenshotAndSaveTo(string $directory = null, string $fileName = null, string $fileNamePrefix = null): string
    {
        if ($directory === null) {
            $directory = '.';
        }
        if ($fileName === null) {
            $fileName = ($fileNamePrefix ? $fileNamePrefix . '-' : '') . date('YmdHis');
        }
        $path = "$directory/$fileName.png";
        file_put_contents($path, $this->getCurrentScreenshot());
        return $path;
    }

    /**
     * get battery info:
     * Array
     * (
     *   [AC powered] => 1
     *   [USB powered] =>
     *   [Wireless powered] =>
     *   [status] => 1
     *   [health] => 1
     *   [present] => 1
     *   [level] => 99
     *   [scale] => 100
     *   [voltage] => 10000
     *   [temperature] => 0
     *   [technology] => Unknown
     * )
     *
     * @return array
     */
    public function getBatteryInfo(): array
    {
        $delimiter = self::$hostOS === 'windows' ? "\r\r\n" : "\r\n";
        $result = explode($delimiter, $this->shell('dumpsys battery'));
        $entries = [];
        for ($i = 1; $i < count($result); $i++) {
            preg_match('/ {2}(.+): (.+)/', $result[$i], $match);
            if (count($match) === 3) {
                $value = $match[2];
                if ($match[2] === 'true') {
                    $value = true;
                } else if ($match[2] === 'false') {
                    $value = false;
                } else if (is_numeric($match[2])) {
                    $value = intval($match[2]);
                }
                $entries[$match[1]] = $value;
            }
        }
        return $entries;
    }

    /**
     * get battery status
     *
     * @return string
     */
    public function getBatteryStatus(): string
    {
        return self::BATTERY_STATUS[$this->getBatteryInfo()['status']];
    }

    /**
     * get battery temperature
     *
     * @return float
     */
    public function getBatteryTemperature(): float
    {
        return $this->getBatteryInfo()['temperature'] / 10.0;
    }

    /**
     * get screen resolution
     *
     * @return array
     */
    public function getScreenResolution(): array
    {
        $result = explode(': ', $this->shell('wm size'));
        $resolution = explode('x', $result[1]);
        return ['width' => intval($resolution[0]), 'height' => intval($resolution[1])];
    }

    /**
     * reboot
     */
    public function reboot()
    {
        $this->adb('reboot');
    }

    /**
     * enter fastboot mode
     */
    public function fastboot()
    {
        $this->adb('reboot bootloader');
    }

    /**
     * list out packages
     *
     * @param string|null $name
     * @param bool $disabled
     * @param bool $enabled
     * @param bool $system
     * @param bool $thirdParty
     * @param bool $uninstalled
     * @return array
     */
    public function listPackages(
        string $name = null,
        bool $disabled = false,
        bool $enabled = false,
        bool $system = false,
        bool $thirdParty = false,
        bool $uninstalled = false
    ): array
    {
        $args = '';
        if ($disabled) $args .= '-d ';
        if ($enabled) $args .= '-e ';
        if ($system) $args .= '-s ';
        if ($thirdParty) $args .= '-3 ';
        if ($uninstalled) $args .= '-u ';
        if ($name) $args .= $name;
        $delimiter = self::$hostOS === 'windows' ? "\r\r\n" : "\r\n";
        $packages = explode($delimiter, $this->shell("pm list packages $args"));
        foreach ($packages as &$package) {
            $package = substr($package, 8);
        }
        return $packages;
    }

    /**
     * start activity, return startup information
     * Array
     * (
     *   [Stopping] => com.android.settings
     *   [Starting] => Intent { act=android.intent.action.MAIN cat=[android.intent.category.LAUNCHER] cmp=com.android.settings/.Settings }
     *   [Status] => ok
     *   [Activity] => com.android.settings/.Settings
     *   [ThisTime] => 730
     *   [TotalTime] => 730
     *   [WaitTime] => 868
     * )
     *
     * @param string $component com.android.settings/.Settings
     * @param bool $wait
     * @param bool $forceStop
     * @return array
     */
    public function startActivity(string $component, bool $wait = false, bool $forceStop = false): array
    {
        $args = '';
        if ($wait) $args .= '-W ';
        if ($forceStop) $args .= '-S ';
        $args .= $component;
        $delimiter = self::$hostOS === 'windows' ? "\r\r\n" : "\r\n";
        $result = explode($delimiter, $this->shell("am start $args"));
        $entries = [];
        for ($i = 0; $i < count($result) - 1; $i++) {
            preg_match('/(.+): (.+)/', $result[$i], $match);
            if (count($match) === 3) {
                $value = $match[2];
                if (is_numeric($match[2])) {
                    $value = intval($match[2]);
                }
                $entries[$match[1]] = $value;
            }
        }
        return $entries;
    }

    /**
     * install app
     *
     * @param string $path
     * @param bool $replace
     * @param bool $downgrade
     * @param bool $grant
     * @return bool
     */
    public function installApp(string $path, bool $replace = false, bool $downgrade = false, bool $grant = true): bool
    {
        $args = '';
        if ($replace) $args .= '-r ';
        if ($downgrade) $args .= '-d ';
        if ($grant) $args .= '-g ';
        $args .= realpath($path);
        $out = explode(PHP_EOL, $this->adb("install $args"));
        $result = explode(' ', $out[3]);
        if ($result[0] === 'Failure') {
            Log::error("ADB::installApp: {$result[1]}");
            return false;
        }
        return true;
    }

    /**
     * remove app
     *
     * @param string $package
     * @return bool
     */
    public function removeApp(string $package): bool
    {
        $result = explode(' ', $this->adb("uninstall $package"));
        if ($result[0] === 'Failure') {
            Log::error("ADB::removeApp: {$result[1]}");
            return false;
        }
        return true;
    }

    /**
     * clear all app data
     *
     * @param string $package
     * @return bool
     */
    public function clearAppData(string $package): bool
    {
        return $this->shell("pm clear $package") === 'Success';
    }

    /**
     * open a url
     *
     * @param string $url
     * @return void
     */
    public function openURL(string $url)
    {
        $this->shell("am start -a android.intent.action.VIEW -d $url");
    }

    /**
     * call a phone number
     * @param string $phone
     */
    public function callPhoneNumber(string $phone)
    {
        $this->shell("am start -a android.intent.action.CALL -d tel:$phone");
    }

    /**
     * press a key
     * https://developer.android.com/reference/android/view/KeyEvent.html
     *
     * @param int $keyCode
     * @param bool $longPress
     * @return void
     */
    public function sendKeyEvent(int $keyCode, bool $longPress = false)
    {
        $args = '';
        if ($longPress) $args .= '--longpress ';
        $args .= $keyCode;
        $this->shell("input keyevent $args");
        usleep(self::$commandInterval);
    }

    /**
     * todo: may have bug with escaping chars when host shell -> adb -> device shell
     * send text
     *
     * @param string $text
     * @return void
     */
    public function sendText(string $text)
    {
        if (self::$adbKeyboardIsDefaultIME === true) {
            // use ADBKeyboard
            $text = preg_replace('/["]/', '\\"', $text);
            $this->shell('am broadcast -a ADB_INPUT_B64 --es msg ' . base64_encode($text));
        } else {
            // use adb shell input text "text"
            $text = preg_replace('/["$]/', '\\\\\\\\\\\\$0', $text);
            $text = preg_replace('/[()<>|;&*\\~\']/', '\\\\$0', $text);
            $text = preg_replace('/ /', '%s', $text);
            $this->shell("input text \"$text\"");
        }
        usleep(self::$commandInterval);
    }

    /**
     * tap
     * if 0 < x, y < 1, consider it as percentage
     *
     * @param float $x
     * @param float $y
     * @return void
     */
    public function tap(float $x, float $y)
    {
        if (0 < $x and $x < 1) $x *= $this->width;
        if (0 < $y and $y < 1) $y *= $this->height;
        $this->shell("input tap $x $y");
        usleep(self::$commandInterval);
    }

    /**
     * long press
     *
     * @param float $x
     * @param float $y
     * @return void
     */
    public function longPress(float $x, float $y)
    {
        $this->swipe($x, $y, $x, $y, 2000);
    }

    /**
     * swipe
     * if 0 < x, y < 1, consider it as percentage
     *
     * @param float $startX
     * @param float $startY
     * @param float $endX
     * @param float $endY
     * @param int|string $duration
     * @return void
     */
    public function swipe(float $startX, float $startY, float $endX, float $endY, $duration = '')
    {
        if (0 < $startX and $startX < 1) $startX *= $this->width;
        if (0 < $startY and $startY < 1) $startY *= $this->height;
        if (0 < $endX and $endX < 1) $endX *= $this->width;
        if (0 < $endY and $endY < 1) $endY *= $this->height;
        $this->shell("input swipe $startX $startY $endX $endY $duration");
        usleep(self::$commandInterval);
    }

    /**
     * swipe to left
     *
     * @return void
     */
    public function swipeLeft()
    {
        $this->swipe(0.8, 0.5, 0.2, 0.5);
    }

    /**
     * swipe to right
     *
     * @return void
     */
    public function swipeRight()
    {
        $this->swipe(0.2, 0.5, 0.8, 0.5);
    }

    /**
     * swipe up
     *
     * @return void
     */
    public function swipeUp()
    {
        $this->swipe(0.5, 0.8, 0.5, 0.2);
    }

    /**
     * swipe down
     *
     * @return void
     */
    public function swipeDown()
    {
        $this->swipe(0.5, 0.2, 0.5, 0.8);
    }

    /**
     * dump current UI hierarchy xml file into a DOMXPath
     * class for query
     *
     * @param string|null $path local xml file path
     * @return DOMXPath
     */
    public function UIDump(string $path = ''): DOMXPath
    {
        $flag = '';
        if ($this->sdkVersion >= 19) $flag = '--compressed';
        if (empty($path)) {
            $parentDirectory = defined('APP_PATH') ? APP_PATH : '.';
            $localPath = $parentDirectory . DIRECTORY_SEPARATOR . uniqid('UIHierarchy_', true) . '.xml';
        } else {
            $localPath = $path;
        }

        $sleepCount = 6;
        // wait for the UI to be idle to be dumped
        while ($sleepCount--) {
            if (trim($this->shell("uiautomator dump $flag /data/local/tmp/uidump.xml")) == 'UI hierchary dumped to: /data/local/tmp/uidump.xml') {
                break;
            }
            usleep(500000);
        }

        $this->adb("pull /data/local/tmp/uidump.xml $localPath");
        // parse
        $UIHierarchy = new DOMDocument();
        $UIHierarchy->load($localPath);
        if (empty($path)) {
            unlink($localPath);
        }

        $this->shell('rm /data/local/tmp/uidump.xml');
        return new DOMXPath($UIHierarchy);
    }

    /**
     * wait until a callback, the callback should return [bool $shouldStopLoop, $result]
     * @param callable $waitingFor
     * @param array|null $params
     * @param int|null $timeout
     * @param int|null $sleepInterval
     * @param callable|null $timeoutCallback timeout callback, if not provided, raise a RuntimeException
     * @return mixed
     * @throws RuntimeException
     */
    public function waitUntil(callable $waitingFor, array $params = null, int $timeout = null, int $sleepInterval = null, callable $timeoutCallback = null)
    {
        if (is_null($sleepInterval)) {
            $sleepInterval = $this->waitUntilSleepInterval;
        }

        if (is_null($timeout)) {
            $timeout = $this->waitUntilTimeout;
        }

        $result = null;
        $elapsedTime = 0;
        while (true) {
            list($stop, $result) = call_user_func_array($waitingFor, $params ?? []);
            if ($stop) break;
            usleep($sleepInterval);
            $elapsedTime += $sleepInterval;
            if ($elapsedTime > $timeout) {
                if ($timeoutCallback) {
                    call_user_func_array($waitingFor, []);
                } else {
                    throw new RuntimeException("Time out after $timeout microseconds");
                }
            }
        }
        return $result;
    }
}
