<?php

/* ***** BEGIN LICENSE BLOCK *****
 * Roadsend PHP Compiler
 * Copyright (C) 2009-2010 Shannon Weyrick
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

    const BLUE = 34;
    const GREEN = 32;
    const RED = 31;

    static public $useColor = true;
    static public $verbosity = 1;
    static public $rphpBinary;
    static public $rphpABinary;
    static public $rphpVersion;    
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
        if (getenv('RPHP_ANALYZER_BINARY')) {
            self::$rphpABinary = trim(getenv('RPHP_ANALYZER_BINARY'));
        }
        if (empty(self::$rphpBinary) || empty(self::$rphpABinary)) {
            // XXX non portable, find a better way to do this
            $b = `which rphp`;
            if (!empty($b)) {
                self::$rphpBinary = trim($b);
            }
            $b = `which rphp-analyzer`;
            if (!empty($b)) {
                self::$rphpABinary = trim($b);
            }
            if (empty(self::$rphpBinary) || empty(self::$rphpABinary)) {
                self::bomb('Unable to find rphp binaries (rphp,rphp-analyzer). Try setting RPHP_BINARY and RPHP_ANALYZER_BINARY or putting rphp in the PATH');
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
        echo "dotest [-df] <directory or file> [output directory]\n";
        echo "  -d <path>\t\tRun all tests in the specified root directory\n";
        echo "  -f <file>\t\tRun the single test specified\n";
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
        
        $allPassed = true;
        foreach ($this->testList as $testH) {
            if ($testH->interpretResult == PHP_Test::RESULT_FAIL)
                $iFail[] = $testH;
            if ($testH->compileResult == PHP_Test::RESULT_BUILDFAIL)
                $bFail[] = $testH;
            if ($testH->compileResult == PHP_Test::RESULT_BUILDFAIL_FAIL)
                $bFailFail[] = $testH;
            if ($testH->compileResult == PHP_Test::RESULT_FAIL)
                $cFail[] = $testH;
            if ($testH->parseResult == PHP_Test::RESULT_FAIL)
                $pFail[] = $testH;
        }
        if (sizeof($iFail)||sizeof($bFail)||sizeof($bFailFail)||sizeof($cFail)||sizeof($pFail))
            $allPassed = false;

        $this->showResultList('INTERPRETER', $iFail, $testH->iDiffOutput, $testH->ierrFileName);
        $this->showResultList('BUILD', $bFail, $testH->builtOutput, $testH->buildErrFileName);
        $this->showResultList('EXPECTED BUILD FAIL', $bFailFail, $testH->builtOutput, $testH->buildErrFileName);
        $this->showResultList('COMPILED RUN', $cFail, $testH->cDiffOutput);
        $this->showResultList('PARSE', $pFail, $testH->pDiffOutput);
        
        if ($allPassed)
            echo "---- ALL TESTS PASSED ----\n";
    }

    public function showResultList($msg, $fails, $singleExtra=NULL, $extraFile=NULL) {
        if (sizeof($fails)) {
            echo "------------- $msg FAILURES -------------\n";
            foreach ($fails as $testH) {
                echo "{$testH->tptFileName}\n";
                if (isset($testH->sectionData['KNOWNFAILURE']))
                    echo "--- KNOWN FAILURE:\n".$testH->sectionData['KNOWNFAILURE']."---\n";
                if (Control::$singleMode) {
                    if ($singleExtra) {
                        echo $singleExtra;
                    }
                    if ($extraFile && file_exists($extraFile)) {
                        echo "--- ERROR OUTPUT ---\n";
                        echo file_get_contents($extraFile);
                    }
                }
            }
        }
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

    const INTERPRETER = 1;
    const COMPILER = 2;
    const ANALYZER = 4;
    
    public $testTypes = 0;
    
    public $tptFileName;
    public $testFileName;
    public $ioutFileName; // interpreted output
    public $coutFileName; // compiled output
    public $poutFileName; // parse output
    public $expectFileName;
    public $buildFileName;
    public $idiffFileName;
    public $cdiffFileName;
    public $pdiffFileName;
    public $idiffOutput;
    public $cdiffOutput;
    public $buildOutput;
    public $pdiffOutput;
    
    public $iOutput;
    public $cOutput;
    public $pOutput;

    protected $expectType = 'EXPECT';
    
    protected $templateData;
    public $sectionData;
    
    public $compileResult = self::RESULT_UNKNOWN;
    public $interpretResult = self::RESULT_UNKNOWN;
    public $parseResult = self::RESULT_UNKNOWN;
    
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
            $this->testTypes |= self::COMPILER & self::INTERPRETER;
            $this->expectType = 'EXPECTF';
        }
        elseif (isset($this->sectionData['EXPECTREGEX'])) {
            $this->testTypes |= self::COMPILER & self::INTERPRETER;
            $this->expectType = 'EXPECTREGEX';
        }
        elseif (isset($this->sectionData['EXPECTPARSE'])) {
            $this->testTypes |= self::ANALYZER;
            $this->expectType = 'EXPECTPARSE';
        }
        elseif (isset($this->sectionData['EXPECT'])) {
            $this->testTypes |= self::COMPILER & self::INTERPRETER;
            $this->expectType = 'EXPECT';
        }
        else {            
            print_r($this->sectionData);
            Control::bomb('no expect data');
        }

        // XXX do skips and remove interpreter/compiler as necessary

        // work files
        $bName = Control::$outDir.basename($this->tptFileName, '.phpt');
        $this->testFileName = $bName.'.php';        
        $this->expectFileName = $bName.'.expect';
        // build (compiles only)
        $this->buildFileName = $bName.'.build.out';
        $this->buildErrFileName = $bName.'.build.err';
        // stdout
        $this->ioutFileName = $bName.'.i.out';
        $this->coutFileName = $bName.'.c.out';
        $this->poutFileName = $bName.'.p.out';
        // stderr
        $this->ierrFileName = $bName.'.i.err';
        $this->cerrFileName = $bName.'.c.err';
        $this->perrFileName = $bName.'.p.err';
        // diff
        $this->idiffFileName = $bName.'.i.diff';
        $this->cdiffFileName = $bName.'.c.diff';
        $this->pdiffFileName = $bName.'.p.diff';
        
    }

    protected function bomb($msg) {
        Control::bomb($this->tptFileName.': '.$msg);
    }
    
    protected function writeTest() {
        
        if (!file_put_contents($this->testFileName, $this->sectionData['FILE']))
            Control::bomb("unable to write .php test file (FILE section): ".$this->testFileName);
        
        
        if (!file_put_contents($this->expectFileName, $this->getExpectData())) {
            touch($this->expectFileName);
        } 
        
    }

    protected function executeTest($type) {

        if ($type == self::INTERPRETER) {
            
            $cmd = Control::$rphpBinary.' -f '.$this->testFileName;

            // setup output vars
            $output =& $this->iOutput;
            $outFileName =& $this->ioutFileName;
            $result =& $this->interpretResult;
            $errFileName =& $this->ierrFileName;
            
        }
        elseif ($type == self::ANALYZER) {
            
            $passes = trim($this->sectionData['PASSES']);
            if (empty($passes)) {
                $passes = 'dump-ast';
            }            
            $cmd = Control::$rphpABinary.' --passes='.$passes.' '.$this->testFileName;
            
            // setup output vars
            $output =& $this->pOutput;
            $outFileName =& $this->poutFileName;
            $result =& $this->parseResult;
            $errFileName =& $this->perrFileName;
            
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

        if ($output) {
            if (!file_put_contents($outFileName, $output))
                Control::bomb("unable to write output file: ".$outFileName);
        }
        else {
            touch($outFileName);
        }

        $result = $this->compareOutput($output);
        if ($type == self::INTERPRETER)
            $this->interpretResult = $result;
        elseif ($type == self::ANALYZER)
            $this->parseResult = $result;
        else
            $this->compileResult = $result;
        
    }

    protected function compareOutput($output) {
    
        $expectType = $this->expectType;
        $expectData = $this->getExpectData();
    
        // compare output
        if ($expectType != 'EXPECT') {
            $re_expect = trim($expectData);
        }
        
        switch ($this->expectType) {
            case 'EXPECTPARSE':
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

    protected function getExpectData() {
        return $this->sectionData[$this->expectType];
    }
    
    protected function writeDiff($type) {

        if ($type == self::INTERPRETER) {
            $cmd = 'diff '.$this->expectFileName.' '.$this->ioutFileName;
            Control::log(2, $cmd);
            $this->iDiffOutput = `$cmd`;
            file_put_contents($this->idiffFileName, $this->iDiffOutput);
        }
        elseif ($type == self::ANALYZER) {
            $cmd = 'diff '.$this->expectFileName.' '.$this->poutFileName;
            Control::log(2, $cmd);
            $this->pDiffOutput = `$cmd`;
            file_put_contents($this->pdiffFileName, $this->pDiffOutput);
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
        
        if ($this->testTypes & self::INTERPRETER) {
            echo "INTERPRETER: ";
            Control::flush();
            
            // do interpreter test
            $this->executeTest(self::INTERPRETER);
    
            if ($this->interpretResult == self::RESULT_FAIL) {
                $this->writeDiff(self::INTERPRETER);
            }
    
            switch ($this->interpretResult) {
                case self::RESULT_PASS:
                    Control::colorMsg(Control::GREEN,"PASS ");
                    break;
                case self::RESULT_FAIL:
                    Control::colorMsg(Control::RED,"FAIL ");
                    break;
                case self::RESULT_SKIP:
                    Control::colorMsg(Control::BLUE,"SKIP ");
                    break;
            }
        }
        
        if ($this->testTypes & self::ANALYZER) {
            echo "PARSE: ";
            Control::flush();
            
            // do interpreter test
            $this->executeTest(self::ANALYZER);
    
            if ($this->parseResult == self::RESULT_FAIL) {
                $this->writeDiff(self::ANALYZER);
            }
    
            switch ($this->parseResult) {
                case self::RESULT_PASS:
                    Control::colorMsg(Control::GREEN,"PASS ");
                    break;
                case self::RESULT_FAIL:
                    Control::colorMsg(Control::RED,"FAIL ");
                    break;
                case self::RESULT_SKIP:
                    Control::colorMsg(Control::BLUE,"SKIP ");
                    break;
            }
        }        
        
        // do compiled test
        if ($this->testTypes & self::COMPILER) {
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