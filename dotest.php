<?php

/* ***** BEGIN LICENSE BLOCK *****
 * Roadsend PHP Compiler
 * Copyright (C) 2009 Shannon Weyrick
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public License
 * as published by the Free Software Foundation; either version 2.1
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
 * ***** END LICENSE BLOCK ***** */

/**
 * This is a test suite program designed to be compatible with Zend .phpt test
 * template files. It supports features that are specific to Roadsend PHP, but should
 * also be "mostly" compatible with Zend PHP
 */

class Control {

    const GREEN = 32;
    const RED = 31;

    static public $useColor = true;
    static public $verbosity = 1;
    static public $rphpBinary;
//    static public $rphpiBinary;
    static public $rphpVersion;
    static public $doCompiled = true;
    static public $testRoot;
    static public $outDir;
    static public $singleMode = false;
    
    public static function log($level, $msg) {
        if (self::$verbosity >= $level)
            echo "$msg\n";
    }

    public static function findPCC() {
        if (getenv('RPHP_BINARY')) {
            self::$rphpBinary = trim(getenv('RPHP_BINARY'));
        }
//        if (getenv('RPHPI_BINARY')) {
//            self::$rphpiBinary = trim(getenv('RPHPI_BINARY'));
//        }
        if (empty(self::$rphpBinary) /*|| empty(self::$rphpiBinary)*/) {
            // XXX non portable, find a better way to do this
            $b = `which rphp`;
            if (!empty($b)) {
                self::$rphpBinary = trim($b);
            }
//            $b = `which rphpi`;
//            if (!empty($b)) {
//                self::$rphpiBinary = trim($b);
//            }
            if (empty(self::$rphpBinary) /*|| empty(self::$rphpiBinary)*/) {
                self::bomb('Unable to find rphp binary. Try setting RPHP_BINARY or putting rphp in the PATH');
            }
        }

        self::$rphpVersion = trim(exec(self::$rphpBinary.' --version'));
        echo "Roadsend PHP: ".self::$rphpBinary."\n";
        echo "     Version: ".self::$rphpVersion."\n";
    }
    
    public static function bomb($msg) {
        die("FAIL: {$msg}\n");
    }

    public static function flush() {
        fflush(STDOUT);
    }

    public static function colorMsg($c, $msg) {
        if (self::$useColor)
            echo "\033[{$c};1m{$msg}\033[0m";
        else
            echo $msg;
    }
    
}

class TestSuite {

    protected $testList;
    
    public function usage() {
        echo "Roadsend PHP Test Suite\n";
        echo "dotest [-dfl] <directory or file> [output directory]\n";
        echo "  -d <path>\t\tRun all tests in the specified root directory\n";
        echo "  -f <file>\t\tRun the single test specified\n";
        echo "  -l <file>\t\tRun all tests listed in the specified file\n";
        die("\n");
    }
    
    public function findTestsInDirectory($dir) {

        if (substr($dir,-1) != DIRECTORY_SEPARATOR)
            $dir .= DIRECTORY_SEPARATOR;
        
        if (!is_dir($dir))
            Control::bomb("$dir is not a directory");
        
        $d = opendir($dir);
        while ($file = readdir($d)) {
            if (($file == '.') || ($file == '..'))
                continue;
            if (fnmatch('*.phpt',basename($file)))
                $this->testList[] = new PHP_Test($dir.$file);
            elseif (is_dir($dir.$file)) {
                $this->findTestsInDirectory($dir.$file);
            }
        }
        closedir($d);
    }

    public function run() {

        if (($GLOBALS['argc'] < 3) ||
           (!preg_match('/^-([dfl])$/',$GLOBALS['argv'][1]))) {
           if (!empty($argv[2]))
            echo "invalid option: {$GLOBALS['argv'][1]}\n";
           $this->usage();
        }

        Control::findPCC();
       
        switch ($GLOBALS['argv'][1]) {
           case '-d':
               Control::$testRoot = $GLOBALS['argv'][2];
               $this->findTestsInDirectory(Control::$testRoot);
               break;
           case '-f':
               Control::$singleMode = true;
               $this->testList[] = new PHP_Test($GLOBALS['argv'][2]);
               break;
           default:
               Control::bomb('invalid option: '.$GLOBALS['argv'][1]);
               break;
        }

        Control::$outDir = $GLOBALS['argv'][3];
        if (!is_dir(Control::$outDir))
            Control::bomb('output directory is invalid: '.Control::$outDir);
        if (substr(Control::$outDir,-1) != DIRECTORY_SEPARATOR)
            Control::$outDir .= DIRECTORY_SEPARATOR;
       
        Control::log(1,sizeof($this->testList).' total tests');
        foreach ($this->testList as $testH) {
            echo substr($testH->tptFileName,strlen(Control::$testRoot)).': ';
            $testH->runTest();
        }

        $this->showResults();
        
    }

    public function showResults() {
        foreach ($this->testList as $testH) {
            if ($testH->interpretResult == PHP_Test::RESULT_FAIL)
                $iFail[] = $testH;
            if ($testH->compileResult == PHP_Test::RESULT_BUILDFAIL)
                $bFail[] = $testH;
            if ($testH->compileResult == PHP_Test::RESULT_BUILDFAIL_FAIL)
                $bFailFail[] = $testH;
            if ($testH->compileResult == PHP_Test::RESULT_FAIL)
                $cFail[] = $testH;
        }
        if (sizeof($iFail)) {
            echo "------------- INTERPRETER FAILURES -------------\n";
            foreach ($iFail as $testH) {
                echo "{$testH->tptFileName}\n";
                if (isset($testH->sectionData['KNOWNFAILURE']))
                    echo "--- KNOWN FAILURE:\n".$testH->sectionData['KNOWNFAILURE']."---\n";
                if (Control::$singleMode)
                    echo $testH->iDiffOutput;
            }
        }
        if (sizeof($bFail)) {
            echo "------------- BUILD FAILURES -------------\n";
            foreach ($bFail as $testH) {
                echo "{$testH->tptFileName}\n";
                if (Control::$singleMode) {
                    echo $testH->buildOutput;
                    echo file_get_contents($testH->buildErrFileName);
                }
            }
        }
        if (sizeof($bFailFail)) {
            echo "------------- EXPECTED BUILD FAIL FAILURES -------------\n";
            foreach ($bFailFail as $testH) {
                echo "{$testH->tptFileName}\n";
                if (Control::$singleMode) {
                    echo $testH->buildOutput;
                    echo file_get_contents($testH->buildErrFileName);
                }
            }
        }
        if (sizeof($cFail)) {
            echo "------------- COMPILE RUN FAILURES -------------\n";
            foreach ($cFail as $testH) {
                echo "{$testH->tptFileName}\n";
                if (Control::$singleMode)
                    echo $testH->cDiffOutput;
            }
        }
        if (empty($iFail)&&empty($bFail)&&empty($bFailFail)&&empty($cFail))
            echo "---- ALL TESTS PASSED ----\n";
    }

}

class PHP_Test {

    const RESULT_PASS = 0;
    const RESULT_FAIL = 1;
    const RESULT_SKIP = 2;
    const RESULT_BUILDFAIL = 3;
    const RESULT_UNKNOWN = 4;
    const RESULT_BUILDFAIL_PASS = 5; // we expected the compiler build to fail, and it did
    const RESULT_BUILDFAIL_FAIL = 6; // we expected the compiler build to fail, but it passed

    const INTERPRETER = 0;
    const COMPILER = 1;
    
    public $tptFileName;
    public $testFileName;
    public $ioutFileName; // interpreted output
    public $coutFileName; // compiled output
    public $expectFileName;
    public $buildFileName;
    public $idiffFileName;
    public $cdiffFileName;
    public $idiffOutput;
    public $cdiffOutput;
    public $buildOutput;
    
    public $iOutput;
    public $cOutput;

    protected $expectType = 'EXPECT';
    
    protected $templateData;
    public $sectionData;
    
    public $compileResult = self::RESULT_UNKNOWN;
    public $interpretResult = self::RESULT_UNKNOWN;
    
    public function __construct($fName) {
        Control::log(2,'adding test: '.$fName);
        $this->tptFileName = $fName;
    }

    protected function parseTest() {

        $this->templateData = file($this->tptFileName);
        if (empty($this->templateData))
            Control::bomb('unable to load test: '.$this->tptFileName);

        $curSection = '';
        for ($i=0; $i <= sizeof($this->templateData); $i++) {
            $line = $this->templateData[$i];
            if (preg_match('/^--([A-Z0-9:]+)--/',$line,$m)) {
                $curSection = strtoupper($m[1]);
                continue;
            }
            else {
                if (empty($curSection))
                    Control::bomb(($i+1).' - template parse error');
                else {
                    if ($curSection == 'TEST')
                        $line = trim($line);
                    $this->sectionData[$curSection] .= $line;
                }
            }
        }

        // pick the right expect data for our platform
        $wSize = PHP_INT_SIZE*8;
        if (isset($this->sectionData['EXPECT:'.$wSize]))
            $this->sectionData['EXPECT'] = $this->sectionData['EXPECT:'.$wSize];
        if (isset($this->sectionData['EXPECTF:'.$wSize]))
            $this->sectionData['EXPECTF'] = $this->sectionData['EXPECTF:'.$wSize];
        if (isset($this->sectionData['EXPECTREGEX:'.$wSize]))
            $this->sectionData['EXPECTREGEX'] = $this->sectionData['EXPECTREGEX:'.$wSize];


        // verify we have code to write
        if (empty($this->sectionData['FILE']))
            Control::bomb('Invalid test template: no FILE section');

        if (isset($this->sectionData['EXPECTF'])) {
            $this->expectType = 'EXPECTF';
        }
        elseif (isset($this->sectionData['EXPECTREGEX'])) {
            $this->expectType = 'EXPECTREGEX';
        }
        elseif (!isset($this->sectionData['EXPECT'])) {
            print_r($this->sectionData);
            Control::bomb('no expect data');
        }

        // work files
        $bName = Control::$outDir.basename($this->tptFileName, '.phpt');
        $this->testFileName = $bName.'.php';
        $this->expectFileName = $bName.'.expect';
        $this->buildFileName = $bName.'.build.out';
        $this->buildErrFileName = $bName.'.build.err';
        $this->ioutFileName = $bName.'.i.out';
        $this->coutFileName = $bName.'.c.out';
        $this->ierrFileName = $bName.'.i.err';
        $this->cerrFileName = $bName.'.c.err';
        $this->idiffFileName = $bName.'.i.diff';
        $this->cdiffFileName = $bName.'.c.diff';
        
    }

    protected function bomb($msg) {
        Control::bomb($this->tptFileName.': '.$msg);
    }
    
    protected function writeTest() {
        
        if (!file_put_contents($this->testFileName, $this->sectionData['FILE']))
            Control::bomb("unable to write .php test file (FILE section): ".$this->testFileName);
        
        if (!file_put_contents($this->expectFileName, $this->sectionData[$this->expectType]))
            Control::bomb("unable to write expect test file ({$this->expectType} section): ".$this->expectFileName);
        
    }

    protected function executeTest($type) {

        if ($type == self::INTERPRETER) {
            
            /*
            if (defined('ROADSEND_PHP')) {
                $cmd = Control::$rphpBinary.' -I '.dirname($this->tptFileName).' -f '.$this->testFileName;
            }
            else {
            // XXX do zend command here
            }
            */
            $cmd = Control::$rphpBinary.' -f '.$this->testFileName;

            // setup output vars
            $output =& $this->iOutput;
            $outFileName =& $this->ioutFileName;
            $result =& $this->interpretResult;
            $errFileName =& $this->ierrFileName;
        }
        else {
            // compiled executable
            $cmd = Control::$outDir.basename($this->tptFileName, '.phpt');

            // compiler output vars
            $output =& $this->cOutput;
            $outFileName =& $this->coutFileName;
            $result =& $this->compileResult;
            $errFileName =& $this->cerrFileName;
        }

        $descriptorspec = array(
            1 => array("pipe", "w"),  // stdout
            2 => array("file", $errFileName,"w")   // stderr
        );

        Control::log(2, $cmd);
        $process = proc_open($cmd, $descriptorspec, $pipes);
        if (!is_resource($process)) {
            Control::bomb("unable to execute test");
        }

        $output = '';
        while(!feof($pipes[1])) {
            $output .= fgets($pipes[1]);
        }
        fclose($pipes[1]);

        $return_value = proc_close($process);
        $output = trim($output);

        if (!file_put_contents($outFileName, $output))
            Control::bomb("unable to write output file: ".$outFileName);

        // get the correct expect data for this run
        if (($type == self::COMPILER) && (isset($this->sectionData['COMPILER:'.$this->expectType]))) {
            $expectData = $this->sectionData['COMPILER:'.$this->expectType];
        }
        else {
            $expectData = $this->sectionData[$this->expectType];
        }

        $result = $this->compareOutput($this->expectType, $expectData, $output);
        if ($type == self::INTERPRETER)
            $this->interpretResult = $result;
        else
            $this->compileResult = $result;
        
    }

    protected function compareOutput($expectType, $expectData, $output) {
    
        // compare output
        if ($expectType != 'EXPECT')
            $re_expect = trim($expectData);
        switch ($this->expectType) {
            case 'EXPECT':
                if ($output != trim($expectData)) {
                    $result = self::RESULT_FAIL;
                }
                else {
                    $result = self::RESULT_PASS;
                }
                break;
            case 'EXPECTF':
                $re_expect = preg_quote($re_expect, '/');
                $re_expect = str_replace('%e', '\\' . DIRECTORY_SEPARATOR, $re_expect);
                $re_expect = str_replace('%s', '[^\r\n]+', $re_expect);
                $re_expect = str_replace('%a', '.+', $re_expect);
                $re_expect = str_replace('%w', '\s*', $re_expect);
                $re_expect = str_replace('%i', '[+-]?\d+', $re_expect);
                $re_expect = str_replace('%d', '\d+', $re_expect);
                $re_expect = str_replace('%x', '[0-9a-fA-F]+', $re_expect);
                $re_expect = str_replace('%f', '[+-]?\.?\d+\.?\d*(?:E-?\d+)?', $re_expect);
                $re_expect = str_replace('%c', '.', $re_expect);
            case 'EXPECTREGEX':
                if (preg_match("/^$re_expect\$/s", trim($output))) {
                    $result = self::RESULT_PASS;
                }
                else {
                    $result = self::RESULT_FAIL;
                }
                break;
        }

        return $result;
        
    }
    
    protected function writeDiff($type) {

        if ($type == self::INTERPRETER) {
            $cmd = 'diff '.$this->expectFileName.' '.$this->ioutFileName;
            Control::log(2, $cmd);
            $this->iDiffOutput = `$cmd`;
            file_put_contents($this->idiffFileName, $this->iDiffOutput);
        }
        else {
            $cmd = 'diff '.$this->expectFileName.' '.$this->coutFileName;
            Control::log(2, $cmd);
            $this->cDiffOutput = `$cmd`;
            file_put_contents($this->cdiffFileName, $this->cDiffOutput);
        }
        
    }
    
    protected function doCompilerBuild() {

        // XXX look at section for rphp args

        $descriptorspec = array(
            1 => array("pipe", "w"),  // stdout
            2 => array("file", $this->buildErrFileName,"w")   // stderr
        );

        //$cmd = Control::$rphpBinary.' -v -I '.dirname($this->tptFileName).' '.$this->testFileName;
        $cmd = Control::$rphpBinary.' -v 2 '.$this->testFileName;
        Control::log(2, $cmd);
        $process = proc_open($cmd, $descriptorspec, $pipes);
        if (is_resource($process)) {

            while(!feof($pipes[1])) {
                $this->buildOutput .= fgets($pipes[1]);
            }
            fclose($pipes[1]);

            $return_value = proc_close($process);

            file_put_contents($this->buildFileName, $this->buildOutput);

            // if we have a COMPILER:BUILDFAILEXPECTF, make sure we failed
            if (isset($this->sectionData['COMPILER:BUILDFAILEXPECTF'])) {
                // we expect the build to fail
                if ($return_value == 0) {
                    // woops, we built ok
                    Control::log(2,'expected test not to build, but it did anyway');
                    $this->compileResult = self::RESULT_BUILDFAIL_FAIL;
                }
                else {
                    // we did fail, but did our fail output match?
                    Control::log(2,'expected test not to build, and it didn\'t');
                    $result = $this->compareOutput('EXPECTF',$this->sectionData['COMPILER:BUILDFAILEXPECTF'],trim(file_get_contents($this->buildErrFileName)));
                    if ($result == self::RESULT_PASS)
                        $this->compileResult = self::RESULT_BUILDFAIL_PASS;
                    else
                        $this->compileResult = self::RESULT_BUILDFAIL_FAIL;
                }
            }
            else {

                // we expect the build to succeed
                if ($return_value != 0) {
                    $this->compileResult = self::RESULT_BUILDFAIL;
                }
                else {
                    // release build output mem if we aren't using it
                    unset($this->buildOutput);
                }
                
            }

        }
        else {
            // couldn't open compiler process
            Command::bomb("unable to run compile command:\n$cmd");
        }

    }

    public function runTest() {
        
        $this->parseTest();

        // XXX do skip check

        // write test
        $this->writeTest();
        
        echo "INTERPRETER: ";
        Control::flush();
        
        // do interpreter test
        $this->executeTest(self::INTERPRETER);

        if ($this->interpretResult == self::RESULT_FAIL)
            $this->writeDiff(self::INTERPRETER);

        echo ($this->interpretResult == self::RESULT_PASS) ?
                Control::colorMsg(Control::GREEN,"PASS ") :
                Control::colorMsg(Control::RED,"FAIL ");
        
        // do compiled test
        if (Control::$doCompiled) {
            echo "   BUILD: ";
            Control::flush();
            $this->doCompilerBuild();
            if ($this->compileResult == self::RESULT_UNKNOWN) {
                Control::colorMsg(Control::GREEN,"OK");
                echo "    RUN: ";
                Control::flush();
                $this->executeTest(self::COMPILER);
                if ($this->compileResult == self::RESULT_FAIL)
                    $this->writeDiff(self::COMPILER);
                echo ($this->compileResult == self::RESULT_PASS) ?
                        Control::colorMsg(Control::GREEN,"PASS ") :
                        Control::colorMsg(Control::RED,"FAIL ");
            }
            else {
                // build fail
                if ($this->compileResult == self::RESULT_BUILDFAIL_PASS)
                    Control::colorMsg(Control::GREEN,"CONTROLLED FAIL");
                else
                    Control::colorMsg(Control::RED,"FAIL ");
            }
        }
        
        echo "\n";
        
    }
    
}

// MAIN
$c = new TestSuite();
$c->run();

?>