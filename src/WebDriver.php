<?php
namespace gpibarra\WebDriverPHP;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Exception\NoSuchWindowException;

class WebDriver {
    static private $log = false;
    static private $port = '4444';
    static private $host = 'http://localhost:'.'4444'.'/wd/hub';
    static private $seleniumJavaBin = "selenium-server-standalone-3.7.1.jar";
    static private $chromeExeWinBin = "chromedriver.exe";
    static private $chromeExeLinBin = "chromedriver";
    static private $seleniumGET = "http://selenium-release.storage.googleapis.com/3.7/selenium-server-standalone-3.7.1.jar";
    static private $chromeWinx64GET = "https://chromedriver.storage.googleapis.com/2.33/chromedriver_win32.zip";
    static private $chromeWinx86GET = "https://chromedriver.storage.googleapis.com/2.33/chromedriver_win32.zip";
    static private $chromeLinx64GET = "https://chromedriver.storage.googleapis.com/2.33/chromedriver_linux64.zip";
    //Chrome x86 decreapted - lastversion 48
    static private $chromeLinx86GET = "https://chromedriver.storage.googleapis.com/2.20/chromedriver_linux86.zip";
    ////wget -N https://archive.org/download/google-chrome-stable_48.0.2564.116-1_i386/google-chrome-stable_48.0.2564.116-1_i386.deb -P ~/
    ////sudo dpkg -i --force-depends ~/google-chrome-stable_48.0.2564.116-1_i386.deb
    ////sudo apt-get -f install -y
    //https://gist.github.com/ziadoz/3e8ab7e944d02fe872c3454d17af31a5
    public $driver;
    private $blPersistent = false;
    private $blDeamon = false;
    static private $relativeStorageWebDriversDir = '/../storage/webdrivers';
    static private $relativeStorageSessionsDir = '/../storage/sessions';
    static private $storageSessionsFilename = 'session_file.txt';


    function __construct($blPersistent = false) {
        $this->blPersistent = $blPersistent;
        if (!$this->blPersistent) {
            if (self::runningInConsole()) echo "Creando Driver\n";
            self::startServer();
            $this->desired_capabilities = DesiredCapabilities::chrome();
            $this->driver = RemoteWebDriver::create(self::$host, $this->desired_capabilities);
        }
        else {
            $folderSession = __DIR__.self::$relativeStorageSessionsDir;
            if (file_exists($folderSession.'/'.self::$storageSessionsFilename)) {
                if (self::runningInConsole()) echo "Restaurando sesion\n";
                self::startServer();
                try {
                    $dataSaved = unserialize(file_get_contents($folderSession.'/'.self::$storageSessionsFilename));
                    $this->driver = RemoteWebDriver::createBySessionID($dataSaved['sessionID']);
                    $url = $this->driver->getCurrentURL();
                }
//                catch (NoSuchWindowException $e) {
                catch (\Exception $e) {
                    $this->desired_capabilities = DesiredCapabilities::chrome();
                    $this->driver = RemoteWebDriver::create(self::$host, $this->desired_capabilities);
                }
            }
            else {
                if (self::runningInConsole()) echo "Creando Driver\n";
                self::startServer();
                $this->desired_capabilities = DesiredCapabilities::chrome();
                $this->driver = RemoteWebDriver::create(self::$host, $this->desired_capabilities);
            }
        }
    }

    function __destruct() {
        if (!$this->blPersistent) {
            $this->driver->quit();
            if (!$this->blDeamon) {
                self::stopServer();
            }
        }
        else {
            $folderSession = __DIR__.self::$relativeStorageSessionsDir;
            if (file_exists($folderSession)) {
                if (!is_dir($folderSession)) {
                    if (self::runningInConsole()) echo "Renombrando archivo $folderSession\n";
                    rename($folderSession,$folderSession."_");
                    if (self::runningInConsole()) echo "Creando carpeta $folderSession\n";
                    mkdir($folderSession);
                }
            }
            else {
                mkdir($folderSession);
            }

            file_put_contents($folderSession.'/'.self::$storageSessionsFilename,serialize(['sessionID' => $this->driver->getSessionID()]));
        }
    }

    public function setPersistent($bl) {
        $this->blPersistent = $bl;
    }

    public function setDeamon($bl) {
        $this->blDeamon = $bl;
    }

    public static function isStartedServer($pid = false) {
        $isSeleniumAlreadyRunning = false;
        $pidSeleniumAlreadyRunning = 0;
        if (self::isWindows()) {
            if (self::runningInConsole()) echo "Chequeando Instancia existente (Windows)\n";
            exec("wmic process where name=\"java.exe\" get ProcessID,commandline 2>&1",$output);
            foreach($output as $line)
            {
                $fields = preg_split("/[\s]+/",$line);
                if(strpos($line, self::$seleniumJavaBin) !== FALSE)
                {
                    $isSeleniumAlreadyRunning = true;
                    $pidSeleniumAlreadyRunning = $fields[6];
                    break;
                }
            }
        }
        else if (self::isLinux()) {
            if (self::runningInConsole()) echo "Chequeando Instancia existente (Linux)\n";
            exec("ps -C java -fwww",$output);
            foreach($output as $line)
            {
                $fields = preg_split("/[\s]+/",$line);
                if(strpos($line, self::$seleniumJavaBin) !== FALSE)
                {
                    $isSeleniumAlreadyRunning = true;
                    $pidSeleniumAlreadyRunning = $fields[1];
                    break;
                }
            }
        }
        else {
            if (self::runningInConsole()) echo "SO no disponible";
        }
        if ($pid) {
            return $pidSeleniumAlreadyRunning;
        }
        else {
            return $isSeleniumAlreadyRunning;
        }
    }

    public static function isFilesExists($folder, $pathSelenium, $pathChrome) {
        if (file_exists($folder)) {
            if (!is_dir($folder)) {
                if (self::runningInConsole()) echo "Renombrando archivo $folder\n";
                rename($folder,$folder."_");
                if (self::runningInConsole()) echo "Creando carpeta $folder\n";
                mkdir($folder);
            }
            else {
                //OK
            }
        }
        else {
            if (self::runningInConsole()) echo "Creando carpeta $folder\n";
            mkdir($folder);
        }
        //Selenium
        if (!file_exists($pathSelenium)) {
            $files = glob(substr($pathSelenium,5) . '*');
            foreach ($files as $file) {
                unlink($file);
            }
            if (self::runningInConsole()) echo "Descargando Selenium ($pathSelenium)\n";
            $content = file_get_contents(self::$seleniumGET);
            file_put_contents($pathSelenium, $content);
            if (self::isLinux()) {
                chmod($pathSelenium,0777);
            }
        }
        //ChromeDriver
        if (!file_exists($pathChrome)) {
            $files = glob(substr($pathChrome,5) . '*');
            foreach ($files as $file) {
                unlink($file);
            }
            $blInstall = false;
            $tmpURLChrome = "";
            $tmpFileZipChrome = "";
            if (self::isWindows()) {
                if (self::runningInConsole()) echo "Descargando Chrome (Windows) [$pathChrome]\n";
                $tmpURLChrome = self::$chromeWinx86GET;
                $tmpFileZipChrome = str_replace(".exe",".zip",$pathChrome);
                $blInstall = true;
            }
            else if (self::isLinux()) {
                if (self::runningInConsole()) echo "Descargando Chrome (Linux) [$pathChrome]\n";
                if (self::isx86()) {
                    $tmpURLChrome = self::$chromeLinx86GET;
                }
                else {
                    $tmpURLChrome = self::$chromeLinx64GET;
                }
                $tmpFileZipChrome = $pathChrome.".zip";
                $blInstall = true;
            }
            else {
                if (self::runningInConsole()) echo "SO no disponible";
            }
            if ($blInstall) {
                $content = file_get_contents($tmpURLChrome);
                file_put_contents($tmpFileZipChrome, $content);
                $zip = new \ZipArchive();
                $res = $zip->open($tmpFileZipChrome);
                if ($res === TRUE) {
                    $zip->extractTo(dirname($pathChrome));
                    $zip->close();
                } else {

                }
                if (self::isLinux()) {
                    chmod($pathChrome,0777);
                }
            }
        }
        if (file_exists($pathSelenium) && file_exists($pathChrome)) {
            return true;
        }
        return false;
    }

    public static function deleteFiles($folder, $pathSelenium, $pathChrome) {
        //Selenium
        /*
        if (file_exists($pathSelenium)) {
            unlink($pathSelenium);
        }
        //ChromeDriver
        if (file_exists($pathChrome)) {
            unlink($pathChrome);
        }
        */
        self::deleteDir($folder);
    }

    public static function deleteDir($dirPath) {
        if (! file_exists($dirPath)) {
            return;
        }
        if (! is_dir($dirPath)) {
            throw new InvalidArgumentException("$dirPath must be a directory");
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }

    public static function updateServer() {
        self::stopServer();
        $pathBin = __DIR__.self::$relativeStorageWebDriversDir;
        $pathSelenium = $pathBin."/".self::$seleniumJavaBin;
        if (self::isWindows()) {
            $pathChrome = $pathBin."/".self::$chromeExeWinBin;
        }
        else if (self::isLinux()) {
            $pathChrome = $pathBin."/".self::$chromeExeLinBin;
        }
        self::deleteFiles($pathBin, $pathSelenium, $pathChrome);
        self::isFilesExists($pathBin, $pathSelenium, $pathChrome);
    }

    public static function startServer() {
        $isSeleniumAlreadyRunning = self::isStartedServer();
        if(!$isSeleniumAlreadyRunning) {
            $pathBin = __DIR__.self::$relativeStorageWebDriversDir;
            $pathSelenium = $pathBin."/".self::$seleniumJavaBin;
            if (self::isWindows()) {
                $pathChrome = $pathBin."/".self::$chromeExeWinBin;
            }
            else if (self::isLinux()) {
                $pathChrome = $pathBin."/".self::$chromeExeLinBin;
            }
            else {
                $pathChrome = $pathBin."/".self::$chromeExeLinBin;
            }


            $isSeleniumFilesExists = self::isFilesExists($pathBin, $pathSelenium, $pathChrome);
            if ($isSeleniumFilesExists) {
                $cmd = "";
                //Execute in the background by sending output to NUL
                if (self::isWindows()) {
                    $cmd = "java -jar -Dwebdriver.chrome.driver=".$pathChrome." ".$pathSelenium." -port ".self::$port." 2>&1>NUL";
                    if (self::runningInConsole()) echo "Starting Java Server Selenium (Windows)\n";
                    //echo $cmd;
                    pclose(popen("start /B ". $cmd, "r"));
                    //exec("start /B ". $cmd);
                }
                else if (self::isLinux()) {
                    $cmd = "java -jar -Dwebdriver.chrome.driver=".$pathChrome." ".$pathSelenium." -port ".self::$port." > /dev/null 2>&1";
                    if (self::runningInConsole()) echo "Starting Java Server Selenium (Linux)\n";
                    //echo $cmd."\n";
                    exec("nohup ".$cmd." &");
                }
                else {
                    if (self::runningInConsole()) echo "SO no disponible";
                }
                sleep(5);
            }
        }
        else {
            if (self::runningInConsole()) echo "Already Java Server Selenium\n";
        }
    }

    public static function stopServer() {
        $pidSeleniumAlreadyRunning = self::isStartedServer(true);
        if($pidSeleniumAlreadyRunning>0)
        {
            if (self::isWindows()) {
                if (self::runningInConsole()) echo "Stopping Java Server Selenium (Windows)\n";
                $cmd = "taskkill /F /PID ".$pidSeleniumAlreadyRunning." 2>&1>NUL";
                exec($cmd);
            }
            else if (self::isLinux()) {
                if (self::runningInConsole()) echo "Stopping Java Server Selenium (Linux)\n";
                $cmd = "kill -9 ".$pidSeleniumAlreadyRunning." > /dev/null 2>&1";
                exec($cmd);
            }
            else {
                if (self::runningInConsole()) echo "SO no disponible";
            }
        }
        else {
            if (self::runningInConsole()) echo "No Java Server Selenium started\n";
        }
    }

    public static function statusServer() {
        if (self::runningInConsole()) {
            if (self::isStartedServer()) {
                echo "WebDriver is started!\n";
            }
            else  {
                echo "WebDriver is stopped!\n";
            }
        }
    }

    public static function checkPersistence() {
        if (!self::isStartedServer()) {
            return false;
        }
        $folderSession = __DIR__.self::$relativeStorageSessionsDir;
        if (!file_exists($folderSession.'/'.self::$storageSessionsFilename)) {
            return false;
        }
        try {
            $dataSaved = unserialize(file_get_contents($folderSession.'/'.self::$storageSessionsFilename));
            $tmpDriver = RemoteWebDriver::createBySessionID($dataSaved['sessionID']);
            $url = $tmpDriver->getCurrentURL();
            return true;
        }
//        catch (NoSuchWindowException $e) {
        catch (\Exception $e) {
            return false;
        }
    }

    private static function runningInConsole() {
        return \App::runningInConsole();
    }

    private static function isx86() {
        return (2147483647 == PHP_INT_MAX);
    }

    private static function isWindows() {
        return (substr(php_uname(), 0, 7) == "Windows");
    }
    private static function isLinux() {
        return (substr(php_uname(), 0, 5) == "Linux");
    }

}
