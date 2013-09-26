<?php
chdir(dirname(__FILE__));
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/Util/Filter.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

error_reporting(E_ALL | E_STRICT);

require_once $testFile =
    is_file($ownCopy = '../XSLT2Processor.php') ?
        $ownCopy
    : 'XML/XSLT2Processor.php';

try {
    PHPUnit_Util_Filter::addFileToWhitelist($testFile);
}catch(Exception $e) {
    foreach (explode(PATH_SEPARATOR, get_include_path()) as $path) {
        if (is_file($path . DIRECTORY_SEPARATOR . $testFile)) {
            PHPUnit_Util_Filter::addFileToWhitelist(
                    $path . DIRECTORY_SEPARATOR . $testFile
            );
            break;
        }
    }
}

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('XML_XSLT2Processor_SelfAssigned', true);
    define('PHPUnit_MAIN_METHOD', 'All_Tests');
}

function report_errors($object, $message = null) {
    $libxml = print_r(libxml_get_last_error(), true);
    $internal = print_r(XML_XSLT2Processor::getErrors(), true);
    $rawErrorLog =
    defined('XML_XSLT2PROCESSOR_ERROR_LOG') &&
    is_file(XML_XSLT2PROCESSOR_ERROR_LOG) ?
        file_get_contents(XML_XSLT2PROCESSOR_ERROR_LOG)
        : 'Not available';
    $command = $object instanceof XML_XSLT2Processor ? $object->command : $object;
    return
    "$message\n\nCommand:\n{$command}\n\nLibxml:\n$libxml\n" .
    "XML_XSLT2Processor:\n$internal\nRaw error log:\n$rawErrorLog\n";
}

class XML_XSLT2Processor_Test extends PHPUnit_Framework_TestCase {
    public $processorPaths = array(
        'testSAXON8_CLI' => null,
        'testSAXON9_CLI' => null,
        'testSAXON9he_CLI' => null,
        'testSAXON_CLI' => null,
        'testSAXON8_JAVACLI' => null,
        'testSAXON9_JAVACLI' => null,
        'testSAXON9he_JAVACLI' => null,
        'testSAXON_JAVACLI' => null,
        'testAltovaXML_CLI' => null,
        'testAltovaXML_COM' => null
    );
    public $jre = null;
    protected $i;
    protected $tmpFiles = array();
    public function tearDown()
    {
        XML_XSLT2Processor::clearErrors();
        libxml_clear_errors();
        unset($this->i);
        foreach($this->tmpFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }elseif (is_dir($file)) {
                rmdir($file);
            }
        }
        $this->tmpFiles = array();
    }

    public function testInvalidProcessor() {
        try {
            $this->i = new XML_XSLT2Processor('MyOwnProcessor');
            throw new Exception('No exception thrown', 0);
        }
        catch(Exception $e) {
            $this->assertEquals(1, $e->getCode());
        }
    }

    public function testInvalidPath() {
        try {
            $this->i = new XML_XSLT2Processor('SAXON', 'unknownFolder');
            throw new Exception('No exception thrown', 0);
        }
        catch(Exception $e) {
            $this->assertEquals(2, $e->getCode());
        }
    }

    public function testInvalidInterface() {
        try {
            $this->i =
                    new XML_XSLT2Processor('SAXON', null, 'MyOwnInterface');
            throw new Exception('No exception thrown', 0);
        }
        catch(Exception $e) {
            $this->assertEquals(3, $e->getCode());
        }
    }

    public function testValidInterfaceForWrongProcessor() {
        try {
            $this->i = new XML_XSLT2Processor('SAXON', null, 'COM');
            throw new Exception('No exception thrown', 0);
        }
        catch(Exception $e) {
            $this->assertEquals(5, $e->getCode());
        }

        try {
            $this->i =
                    new XML_XSLT2Processor('AltovaXML', null, 'JAVA-CLI');
            throw new Exception('No exception thrown', 0);
        }
        catch(Exception $e) {
            $this->assertEquals(5, $e->getCode());
        }
    }

    public function testSimpleTransformation()
    {
        $this->i->importStylesheet('stylesheets/simple.xsl');
        $this->assertEquals('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Untitled Document</title></head><body>This is a simple well formed XML document.</body></html>',
        $this->i->transformToXML('sources/well-formed.xml'),
        report_errors($this->i));
    }

    public function testSimpleDomTransformation()
    {
        $this->i->importStylesheet('stylesheets/simple.xsl');
        $this->assertEquals('DOMDocument',
        get_class($this->i->transformToDoc('sources/well-formed.xml')),
        report_errors($this->i));
    }

    public function testSimpleUriTransformation()
    {
        $outDir = 'results';
        mkdir($outDir);
        $this->tmpFiles[] = $output = $outDir . '/simpleUriTransformation.xml';
        $this->tmpFiles[] = $outDir;
        $this->i->importStylesheet('stylesheets/simple.xsl');
        $this->assertEquals(180,
                $this->i->transformToURI('sources/well-formed.xml', $output),
                report_errors($this->i)
        );
    }

    public function testMissingStylesheet()
    {
        $this->assertFalse(
                $this->i->importStylesheet('stylesheets/simple .xsl'),
                report_errors($this->i)
        );
    }

    public function testMissingSource()
    {
        $this->i->importStylesheet('stylesheets/param.xsl');
        $this->assertFalse(
                $this->i->transformToXML('sources/well-formed .xml'),
                report_errors($this->i)
        );
    }

    public function testDomStylesheet()
    {
        $this->assertTrue($this->i->importStylesheet(
                @DOMDocument::load('stylesheets/param.xsl')),
                report_errors($this->i)
        );
    }

    public function testDomModifiedStylesheet()
    {
        $dom = new DOMDocument;
        $dom->load('stylesheets/param.xsl');
        $params = $dom->getElementsByTagNameNS(
                'http://www.w3.org/1999/XSL/Transform', 'param'
        );
        $params->item(0)->setAttribute('select','\'changed\\\'');
        $params->item(1)->setAttribute('select','\'used\'');
        $this->i->importStylesheet($dom);
        $this->assertEquals('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Untitled Document</title></head><body>changed\<br>used<br></body></html>',
        $this->i->transformToXML('sources/well-formed.xml'),
        report_errors($this->i));
    }

    public function testDomStylesheetOnTheFLy()
    {
        $this->assertTrue($this->i->importStylesheet(
                @DOMDocument::loadXML('<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="2.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="html" encoding="utf-8" indent="no"/>
    <xsl:param name="test" select="\'default\'"/>
    <xsl:param name="path" select="\'unchanged\'"/>
    <xsl:template match="/">
        <html>
            <head>
                <title>Untitled Document</title>
            </head>
            <body>
                <xsl:apply-templates/>
            </body>
        </html>
    </xsl:template>
</xsl:stylesheet>')),
        report_errors($this->i));
    }

    public function testDomSource()
    {
        $this->i->importStylesheet(
                @DOMDocument::load('stylesheets/simple.xsl')
        );
        $this->assertEquals('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Untitled Document</title></head><body>This is a simple well formed XML document.</body></html>',
        $this->i->transformToXML(
                @DOMDocument::load('sources/well-formed.xml')
                ),
                report_errors($this->i)
        );
    }

    public function testDomModifiedSource()
    {
        $dom = new DOMDocument;
        $dom->load('sources/well-formed.xml');
        $dom->documentElement->nodeValue = 'Altered';
        $this->i->importStylesheet(
                @DOMDocument::load('stylesheets/simple.xsl')
        );
        $this->assertEquals('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Untitled Document</title></head><body>Altered</body></html>',
        $this->i->transformToXML($dom),
        report_errors($this->i));
    }

    public function testDomSourceOnTheFly()
    {
        $this->i->importStylesheet(
                @DOMDocument::load('stylesheets/simple.xsl')
        );
        $this->assertEquals('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Untitled Document</title></head><body>This is a simple well formed XML document.</body></html>',
        $this->i->transformToXML(@DOMDocument::loadXML('<root at="yeah">This is a simple well formed XML document.</root>')),
        report_errors($this->i));
    }

    public function testSetAndGetParameter()
    {
        $pName = 'test';
        $value = 'value';
        $this->i->setParameter(null, $pName, $value);
        $this->assertEquals($value, $this->i->getParameter(null, $pName),
        report_errors($this->i));
    }

    public function testSetAndRemoveParameter()
    {
        $pName = 'test';
        $value = 'value';
        $this->i->setParameter(null, $pName, $value);
        $this->assertTrue($this->i->removeParameter(null, $pName));
        $this->assertNull(
                $this->i->getParameter(null, $pName),
                report_errors($this->i)
        );
    }

    public function testRemoveParameterFailure()
    {
        $this->assertFalse(
                $this->i->removeParameter(null, 'test'),
                report_errors($this->i)
        );
    }

    public function testSetParameterModeOUTPUT()
    {
        $this->i->setParameter(null, 'indent', 'yes', 'OUTPUT');
        $this->i->importStylesheet('stylesheets/simple.xsl');
        $this->assertNotEquals('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Untitled Document</title></head><body>This is a simple well formed XML document.</body></html>',
        $this->i->transformToXML('sources/well-formed.xml'),
        report_errors($this->i));
    }

    public function testSetParameterModeSTRING()
    {
        $this->i->setParameter(null, 'test', '"changed\\');
        $this->i->setParameter(null, 'path', 'us"ed"', 'STRING');
        $this->i->importStylesheet('stylesheets/param.xsl');
        $this->assertEquals('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Untitled Document</title></head><body>"changed\<br>us"ed"<br></body></html>',
        $this->i->transformToXML('sources/well-formed.xml'),
        report_errors($this->i));
    }

    public function testSetParameterModeSAFE_STRING()
    {
        $this->i->setParameter(null, 'test', 'йе\\', 'SAFE-STRING');
        $this->i->setParameter(null, array('path' => '>яко'), 'SAFE-STRING');
        $this->i->importStylesheet('stylesheets/param.xsl');
        $this->assertEquals('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Untitled Document</title></head><body>йе\<br>&gt;яко<br></body></html>',
        $this->i->transformToXML('sources/well-formed.xml'),
        report_errors($this->i));
    }

    public function testSetParameterModeFILE()
    {
        $this->i->setParameter(null, 'test', 'sources/well-formed.xml', 'FILE');
        $this->i->setParameter(null, array('path' => 'sources/param.xml'), 'FILE');
        $this->i->importStylesheet('stylesheets/param.xsl');
        $this->assertEquals('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Untitled Document</title></head><body>This is a simple well formed XML document.<br>parameter<br></body></html>',
        $this->i->transformToXML('sources/well-formed.xml'),
        report_errors($this->i));
    }

    public function testSetParameterModeEXPRESSION()
    {
        $this->i->setParameter(null, 'test', '/*/@at', 'EXPRESSION');
        $this->i->setParameter(null, array('path' => 'substring-after(/*/@at,\'ye\')'), 'EXPRESSION');
        $this->i->importStylesheet('stylesheets/param.xsl');
        $this->assertEquals('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Untitled Document</title></head><body>yeah<br>ah<br></body></html>',
        $this->i->transformToXML('sources/well-formed.xml'),
        report_errors($this->i));
    }

    public function testSetParameterModeUNKNOWN()
    {
        $this->i->setParameter(null, 'test', 'sources/well-formed.xml', 'BLABLA');
        $this->i->setParameter(null, array('path' => 'some String'), 'PHONY');
        $this->i->importStylesheet('stylesheets/param.xsl');
        $this->assertEquals('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Untitled Document</title></head><body>default<br>unchanged<br></body></html>',
        $this->i->transformToXML('sources/well-formed.xml'),
        report_errors($this->i));
    }

    public function testHasExsltSupportAltova()
    {
        $this->i->importStylesheet('stylesheets/param.xsl');
        $this->assertFalse($this->i->hasExsltSupport(),
        report_errors($this->i));
    }

    public function testHasExsltSupportSAXON()
    {
        $this->i->importStylesheet('stylesheets/param.xsl');
        $this->assertTrue($this->i->hasExsltSupport(),
        report_errors($this->i));
    }

    public function testAutoStylesheetByPI() {
        $this->i->importStylesheet(XML_XSLT2PROCESSOR_PI);
        $this->assertEquals('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Untitled Document</title></head><body>This is a simple well formed XML document.</body></html>',
        $this->i->transformToXML('sources/well-formed.xml'),
        report_errors($this->i));
    }

    public function testDomAutoStylesheetByPI() {
        $this->i->importStylesheet(XML_XSLT2PROCESSOR_PI);
        $dom = @DOMDocument::load('sources/well-formed.xml');
        $this->assertEquals('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Untitled Document</title></head><body>This is a simple well formed XML document.</body></html>',
        $this->i->transformToXML($dom),
        report_errors($this->i));
    }

    public function testDomOnTheFlyAutoStylesheetByPI() {
        $this->i->importStylesheet(XML_XSLT2PROCESSOR_PI);
        $dom = new DOMDocument;
        $dom->loadXML('<?xml version="1.0"?>
<?xml-stylesheet type="application/xslt+xml" href="stylesheets/param.xsl"?>
<?xml-stylesheet type="text/xsl" href="stylesheets/simple.xsl"?>
<root at="yeah">This is a simple well formed XML document.</root>');
        //$this->assertEquals($dom->documentURI, getcwd());
        $this->assertEquals('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Untitled Document</title></head><body>This is a simple well formed XML document.</body></html>',
        $this->i->transformToXML($dom),
        report_errors($this->i));
    }

    public function testGetErrorsFalseWhenEmpty() {
        $this->assertFalse($this->i->getErrors(), report_errors($this->i));
    }

    public function testGetErrorsInternals101() {
        $this->i->importStylesheet('stylesheets/simple .xsl');
        $this->assertEquals(101, $this->i->getErrors(-1)->code, report_errors($this->i));
    }

    public function testGetErrorsInternals102() {
        $this->i->importStylesheet(false);
        $this->assertEquals(102, $this->i->getErrors(-1)->code, report_errors($this->i));
    }

    public function testGetErrorsInternals201() {
        $this->i->setParameter(null, array('test' => 'value1', 'path' => 'value2'), 'STRING', 'OUTPUT');
        $this->assertEquals(201, $this->i->getErrors(-1)->code, report_errors($this->i));
    }

    public function testGetErrorsInternals202() {
        $this->i->setParameter('invalid URI', array('test' => 'value1', 'path' => 'value2'), 'STRING');
        $this->assertEquals(202, $this->i->getErrors(-1)->code, report_errors($this->i));
    }

    public function testGetErrorsInternals203() {
        $this->i->setParameter(null, 191, 0, 'STRING');
        $this->assertEquals(203, $this->i->getErrors(-1)->code, report_errors($this->i));
        $this->i->clearErrors();

        $this->i->setParameter(null, '191', 0, 'STRING');
        $this->assertEquals(203, $this->i->getErrors(-1)->code, report_errors($this->i));
        $this->i->clearErrors();

        $this->i->setParameter(null, '191s', 0, 'STRING');
        $this->assertEquals(203, $this->i->getErrors(-1)->code, report_errors($this->i));
        $this->i->clearErrors();

        $this->i->setParameter(null, 'xsl:qname', 0, 'STRING');
        $this->assertEquals(203, $this->i->getErrors(-1)->code, report_errors($this->i));
        $this->i->clearErrors();
    }

    public function testGetErrorsInternals204() {
        $this->i->setParameter(null, 'test', new DOMDocument, 'STRING');
        $this->assertEquals(204, $this->i->getErrors(-1)->code, report_errors($this->i));
    }

    public function testGetErrorsInternals205() {
        $this->i->setParameter('NON-NULL', 'UNKNOWN_OPTION', 'UNKNOWN_VALUE', 'OPTION');
        $this->assertEquals(205, $this->i->getErrors(-1)->code, report_errors($this->i));
    }

    public function testGetErrorsInternals206() {
        $this->i->setParameter(null, 'UNKNOWN_OPTION', 'UNKNOWN_VALUE', 'OPTION');
        $this->assertEquals(206, $this->i->getErrors(-1)->code, report_errors($this->i));
    }

    public function testGetErrorsInternals207saxon() {
        $this->i->setParameter(null, 'VERSION_WARNING', 'UNKNOWN_VALUE', 'OPTION');
        $this->assertEquals(207, $this->i->getErrors(-1)->code, report_errors($this->i));
    }

    public function testGetErrorsInternals207altova() {
        $this->i->setParameter(null, 'XSLT_STACK_SIZE', 50, 'OPTION');
        $this->assertEquals(207, $this->i->getErrors(-1)->code, report_errors($this->i));
    }

    public function testGetErrorsInternals401() {
        $this->tmpFiles[] = $outDir = 'results';
        mkdir($outDir);
        $this->i->importStylesheet('stylesheets/simple.xsl');
        $this->i->transformToURI('sources/', $outDir);
        $this->assertEquals(401, $this->i->getErrors(-1)->code, report_errors($this->i));
    }

    public function testGetErrorsInternals402() {
        $outDir = 'results';
        $this->tmpFiles[] = $output = $outDir . '/simple-well-formed.xml';
        $this->tmpFiles[] = $outDir;
        mkdir($outDir);
        $this->i->importStylesheet('stylesheets/simple.xsl');
        $this->i->transformToURI('sources/well-formed .xml', $output);
        $this->assertEquals(402, $this->i->getErrors(-1)->code, report_errors($this->i));
    }

    public function testGetErrorsInternals403() {
        $this->tmpFiles[] = $outDir = 'results';
        mkdir($outDir);
        $this->i->importStylesheet('stylesheets/simple.xsl');
        $this->i->transformToURI('sources1/', $outDir);
        $this->assertEquals(403, $this->i->getErrors(-1)->code, report_errors($this->i));
    }

    public function testGetErrorsInternals404() {
        $this->i->importStylesheet('stylesheets/simple.xsl');
        $this->i->transformToURI('sources/', 'results');
        $this->assertEquals(404, $this->i->getErrors(-1)->code, report_errors($this->i));
    }

    public function testGetErrorsInternals405() {
        $this->i->importStylesheet('stylesheets/simple.xsl');
        $this->i->transformToXML(false);
        $this->assertEquals(405, $this->i->getErrors(-1)->code, report_errors($this->i));
    }

    public function testGetErrorsInternals501() {
        $this->i->importStylesheet(XML_XSLT2PROCESSOR_PI);
        $this->i->transformToXML('sources/param.xml');
        $this->assertEquals(501, $this->i->getErrors(-1)->code, report_errors($this->i));
    }

    public function testGetErrorsAltova() {
        $this->i->importStylesheet('./stylesheets/invalid.xsl');
        $this->i->transformToXML('./sources/well-formed.xml');
        $errors = get_object_vars($this->i->getErrors(-1));
        $code = $errors['code'] !== null ? 'XTSE0010' : null;
        $this->assertEquals(array('code' => $code, 'level' => 3, 'line' => 15, 'column' => 4, 'message' => 'Unexpected element at xsl:template', 'file' => getcwd() . '\stylesheets\invalid.xsl'),
        $errors,
        report_errors($this->i));
    }

    public function testGetErrorsSAXON8() {
        $this->i->importStylesheet('./stylesheets/invalid.xsl');
        $this->i->transformToXML('./sources/well-formed.xml');
        $errors = $this->i->getErrors();
        $this->assertEquals(array('code' => null, 'column' => null, 'file' => getcwd() . '\stylesheets\invalid.xsl', 'level' => 1, 'line' => 2, 'message' => 'Running an XSLT 1.0 stylesheet with an XSLT 2.0 processor'),
        get_object_vars($errors[0]),
        report_errors($this->i));

        $this->assertEquals(array('code' => 'XTSE0500', 'column' => null, 'file' => getcwd() . '\stylesheets\invalid.xsl', 'level' => 3, 'line' => 15, 'message' => 'xsl:template must have a name or match attribute (or both)'),
        get_object_vars($errors[1]),
        report_errors($this->i));

        $this->assertEquals(array('code' => 'XTSE0010', 'column' => null, 'file' => getcwd() . '\stylesheets\invalid.xsl', 'level' => 3, 'line' => 15, 'message' => 'An xsl:template element must not contain an xsl:template element'),
        get_object_vars($errors[2]),
        report_errors($this->i));

        $this->assertEquals(array('code' => 'XTSE0010', 'column' => null, 'file' => getcwd() . '\stylesheets\invalid.xsl', 'level' => 3, 'line' => 15, 'message' => 'Element must be used only at top level of stylesheet'),
        get_object_vars($errors[3]),
        report_errors($this->i));
    }

    public function testGetErrorsSAXON9() {
        $this->i->importStylesheet('./stylesheets/invalid.xsl');
        $this->i->transformToXML('./sources/well-formed.xml');
        $errors = $this->i->getErrors();
        $this->assertEquals(array('code' => null, 'column' => '80', 'file' => getcwd() . '\stylesheets\invalid.xsl', 'level' => 1, 'line' => 2, 'message' => 'Running an XSLT 1.0 stylesheet with an XSLT 2.0 processor'),
        get_object_vars($errors[0]),
        report_errors($this->i));

        $this->assertEquals(array('code' => 'XTSE0500', 'column' => '18', 'file' => getcwd() . '\stylesheets\invalid.xsl', 'level' => 3, 'line' => 15, 'message' => 'xsl:template must have a name or match attribute (or both)'),
        get_object_vars($errors[1]),
        report_errors($this->i));

        $this->assertEquals(array('code' => 'XTSE0010', 'column' => '18', 'file' => getcwd() . '\stylesheets\invalid.xsl', 'level' => 3, 'line' => 15, 'message' => 'An xsl:template element must not contain an xsl:template element'),
        get_object_vars($errors[2]),
        report_errors($this->i));

        $this->assertEquals(array('code' => 'XTSE0010', 'column' => '18', 'file' => getcwd() . '\stylesheets\invalid.xsl', 'level' => 3, 'line' => 15, 'message' => 'Element must be used only at top level of stylesheet'),
        get_object_vars($errors[3]),
        report_errors($this->i));
    }

    public function testGetMessagesSAXON() {
        $this->i->importStylesheet('./stylesheets/message.xsl');
        $this->i->transformToXML('./sources/well-formed.xml');
        $errors = $this->i->getErrors();
        $this->assertEquals(get_object_vars($errors[0]), array('code' => null, 'column' => null, 'file' => null, 'level' => 3, 'line' => 0, 'message' => 'This is an XSLT message with terminate set to <a>"no"</a>.'), report_errors($this->i));
        $this->assertEquals(get_object_vars($errors[1]), array('code' => null, 'column' => null, 'file' => null, 'level' => 3, 'line' => 0, 'message' => 'This is an XSLT message with terminate set to <s:a xmlns:s="urn:TEST:test">"yes"</s:a>.'), report_errors($this->i));
    }

    public function testSetOptionPropertyToUnknown()
    {
        $this->i->options = array(
            'TREE_MODEL' => array('TINY', 'LINKED'),
            'STRIP_WHITE_SPACE' => array('NONE', 'ALL', 'IGNORABLE'),
            'DTD_VALIDATION' => array(true, false),
            'SCHEMA_VALIDATION' => array(true, 'LAX', false),
            'OUTPUT_VALIDATION' => array(true, false),
            'ERROR_LEVEL' => array('SILENT', 'WARN', 'ERROR'),
            'XML_VERSION' => array('1.0', '1.1'),
            'DISABLE_EXTENSION_FUNCTIONS' => array(true, false),
            'VERSION_WARNING' => array(true, false),
            'SCHEMA_AWARE' => array(true, false),
            'XSLT_STACK_SIZE' => 0,
            'UNKNOWN_OPTION' => 'string'
        );
        $this->i->importStylesheet('stylesheets/simple.xsl');
        $this->assertEquals('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Untitled Document</title></head><body>This is a simple well formed XML document.</body></html>',
        $this->i->transformToXML('sources/well-formed.xml'),
        report_errors($this->i));
    }
    
    public function testSetAndGetOptionAltova() {
        $pName = 'XSLT_STACK_SIZE';
        $value = 1000;
        $this->i->setParameter(null, $pName, $value, 'OPTION');
        $this->assertEquals($value, $this->i->getParameter(null, $pName, 'OPTION'),
        report_errors($this->i));
    }
    
    public function testSetAndGetOptionSAXON() {
        $pName = 'VERSION_WARNING';
        $value = false;
        $this->i->setParameter(null, $pName, $value, 'OPTION');
        $this->assertEquals($value, $this->i->getParameter(null, $pName, 'OPTION'),
        report_errors($this->i));
    }

    public function testSetAndRemoveOptionAltova()
    {
        $pName = 'XSLT_STACK_SIZE';
        $value = 1000;
        $this->i->setParameter(null, $pName, $value, 'OPTION');
        $this->assertTrue($this->i->removeParameter(null, $pName, 'OPTION'));
        $this->assertNull($this->i->getParameter(null, $pName), report_errors($this->i));
    }

    public function testSetAndRemoveOptionSAXON()
    {
        $pName = 'VERSION_WARNING';
        $value = false;
        $this->i->setParameter(null, $pName, $value, 'OPTION');
        $this->assertTrue($this->i->removeParameter(null, $pName, 'OPTION'));
        $this->assertNull($this->i->getParameter(null, $pName), report_errors($this->i));
    }

    public function testRemoveOptionFailure()
    {
        $this->assertFalse($this->i->removeParameter(null, 'test', 'OPTION'), report_errors($this->i));
        $this->assertFalse($this->i->removeParameter('non-null', 'test', 'OPTION'), report_errors($this->i));
    }

    public function testAltovaXML_Options_XSLT_STACK_SIZE() {
        $this->i->importStylesheet('stylesheets/simple.xsl');
        $this->assertTrue($this->i->setParameter(null, 'XSLT_STACK_SIZE', 100, 'OPTION'), report_errors($this->i));
        $this->assertEquals('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Untitled Document</title></head><body>This is a simple well formed XML document.</body></html>',
        $this->i->transformToXML('sources/well-formed.xml'),
        report_errors($this->i));
    }

    public function testSAXON_Options_VERSION_WARNING_false() {
        $this->i->importStylesheet('stylesheets/simple.xsl');
        $this->assertTrue($this->i->setParameter(null, 'VERSION_WARNING', false, 'OPTION'), report_errors($this->i));
        $errorsBefore = $this->i->getErrors();
        $this->i->transformToXML('sources/well-formed.xml');
        $this->assertEquals($errorsBefore, $this->i->getErrors(), report_errors($this->i));
    }

    public function testSAXON_Options_VERSION_WARNING_true() {
        $this->i->importStylesheet('stylesheets/simple.xsl');
        $this->i->transformToXML('sources/well-formed.xml');
        $this->assertEquals(1, count($this->i->getErrors()), report_errors($this->i));

        $this->assertTrue($this->i->setParameter(null, 'VERSION_WARNING', true, 'OPTION'), report_errors($this->i));
        $this->assertTrue($this->i->options['VERSION_WARNING']);
        $this->assertTrue((bool) $this->i->transformToXML('sources/well-formed.xml'));
        $errors = $this->i->getErrors();
        //var_dump($errors);
        $this->assertEquals(2, count($errors), report_errors($this->i));
    }

    public function testSAXON_Options_TREE_MODEL() {
        $this->i->importStylesheet('stylesheets/simple.xsl');
        $this->assertTrue($this->i->setParameter(null, 'TREE_MODEL', 'TINY', 'OPTION'), report_errors($this->i));
        $tiny = $this->i->transformToXML('sources/well-formed.xml');
        $this->assertTrue($this->i->setParameter(null, 'TREE_MODEL', 'LINKED', 'OPTION'), report_errors($this->i));
        $linked = $this->i->transformToXML('sources/well-formed.xml');
        $this->assertEquals($tiny, $linked, report_errors($this->i));
    }

    public function testSAXON_Options_STRIP_WHITE_SPACE_CLI() {
        $this->i->importStylesheet('stylesheets/whitespace.xsl');
        $this->assertEquals('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Untitled Document</title></head><body xml:space="preserve">  This is  a simple well formed   XML  document.  </body></html>', $this->i->transformToXML('sources/whitespace.xml'), report_errors($this->i));

        $this->assertTrue($this->i->setParameter(null, 'STRIP_WHITE_SPACE', 'NONE', 'OPTION'), report_errors($this->i));
        $this->assertEquals('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Untitled Document</title></head><body xml:space="preserve">  This is  a simple well formed   XML  document.  </body></html>', $this->i->transformToXML('sources/whitespace.xml'), report_errors($this->i));

        $this->assertTrue($this->i->setParameter(null, 'STRIP_WHITE_SPACE', 'IGNORABLE', 'OPTION'), report_errors($this->i));
        $this->assertEquals('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Untitled Document</title></head><body xml:space="preserve">  This is  a simple well formed   XML  document.  </body></html>', $this->i->transformToXML('sources/whitespace.xml'), report_errors($this->i));

        $this->assertTrue($this->i->setParameter(null, 'STRIP_WHITE_SPACE', 'ALL', 'OPTION'), report_errors($this->i));
        $this->assertEquals('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Untitled Document</title></head><body xml:space="preserve">  This is  a simple well formed XML document.  </body></html>', $this->i->transformToXML('sources/whitespace.xml'), report_errors($this->i));
    }

    public function testSAXON_Options_STRIP_WHITE_SPACE_JAVACLI() {
        $this->i->importStylesheet('stylesheets/whitespace.xsl');
        $this->assertEquals('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Untitled Document</title></head><body xml:space="preserve">  This is a simple well formed XML document.  </body></html>', $this->i->transformToXML('sources/whitespace.xml'));

        $this->assertTrue($this->i->setParameter(null, 'STRIP_WHITE_SPACE', 'NONE', 'OPTION'), report_errors($this->i));
        $this->assertEquals('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Untitled Document</title></head><body xml:space="preserve">  This is  a simple well formed   XML  document.  </body></html>', $this->i->transformToXML('sources/whitespace.xml'));

        $this->assertTrue($this->i->setParameter(null, 'STRIP_WHITE_SPACE', 'IGNORABLE', 'OPTION'), report_errors($this->i));
        $this->assertEquals('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Untitled Document</title></head><body xml:space="preserve">  This is a simple well formed XML document.  </body></html>', $this->i->transformToXML('sources/whitespace.xml'));

        $this->assertTrue($this->i->setParameter(null, 'STRIP_WHITE_SPACE', 'ALL', 'OPTION'), report_errors($this->i));
        $this->assertEquals('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Untitled Document</title></head><body xml:space="preserve">  This is a simple well formed XML document.  </body></html>', $this->i->transformToXML('sources/whitespace.xml'));
    }

    public function testSAXON_Options_DTD_VALIDATION() {
        $this->i->importStylesheet('stylesheets/simple.xsl');
        $this->assertTrue($this->i->setParameter(null, 'VERSION_WARNING', false, 'OPTION'), report_errors($this->i));
        $this->assertEquals('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Untitled Document</title></head><body>This is a simple well formed XML document.</body></html>', $this->i->transformToXML('sources/well-formed.xml'), report_errors($this->i));
        $this->assertTrue($this->i->setParameter(null, 'DTD_VALIDATION', false, 'OPTION'), report_errors($this->i));
        $this->assertEquals('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Untitled Document</title></head><body>This is a simple well formed XML document.</body></html>', $this->i->transformToXML('sources/well-formed.xml'), report_errors($this->i));
        $this->assertTrue($this->i->setParameter(null, 'DTD_VALIDATION', true, 'OPTION'), report_errors($this->i));
        $dom = new DOMDocument;
        $dom->load('sources/well-formed.xml');
        $dom->documentElement->removeAttribute('at');
        $this->i->transformToXML($dom);
        $this->assertEquals(array('code' => 'SXXP0003', 'column' => 7, 'file' => null, 'level' => 2, 'line' => 5, 'message' => 'Error reported by XML parser: Attribute "at" is required and must be specified for element type "root".'), get_object_vars($this->i->getErrors(0)), report_errors($this->i));
    }

    public function testSAXON_Options_ERROR_LEVEL() {
        $this->i->importStylesheet(@DOMDocument::loadXML('<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="2.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="text" encoding="utf-8"/>
    <xsl:template match="/">yes</xsl:template>
    <xsl:template match="/">no</xsl:template>
</xsl:stylesheet>'));
        $this->assertEquals('no', $this->i->transformToXML('sources/well-formed.xml'), report_errors($this->i));
        $this->assertEquals('XTRE0540', $this->i->getErrors(-1)->code);
        $this->assertEquals(2, $this->i->getErrors(-1)->level);

        $this->i->clearErrors();
        $this->assertTrue($this->i->setParameter(null, 'ERROR_LEVEL', 'SILENT', 'OPTION'), report_errors($this->i));
        $this->assertEquals('no', $this->i->transformToXML('sources/well-formed.xml'), report_errors($this->i));
        $this->assertFalse($this->i->getErrors());

        $this->i->clearErrors();
        $this->assertTrue($this->i->setParameter(null, 'ERROR_LEVEL', 'WARN', 'OPTION'), report_errors($this->i));
        $this->assertEquals('no', $this->i->transformToXML('sources/well-formed.xml'), report_errors($this->i));
        $this->assertEquals('XTRE0540', $this->i->getErrors(-1)->code);
        $this->assertEquals(2, $this->i->getErrors(-1)->level);

        $this->i->clearErrors();
        $this->assertTrue($this->i->setParameter(null, 'ERROR_LEVEL', 'ERROR', 'OPTION'), report_errors($this->i));
        $this->assertEquals('no', $this->i->transformToXML('sources/well-formed.xml'), report_errors($this->i));
        $this->assertEquals('XTRE0540', $this->i->getErrors(-1)->code);
        $this->assertEquals(2, $this->i->getErrors(-1)->level);
    }

    public function testSAXON8_Options_XML_VERSION() {
        $this->i->importStylesheet(@DOMDocument::loadXML('<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="2.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:p="http://xslt2processor.sf.net">
    <xsl:output method="xml" version="1.1" encoding="utf-8"/>
    <xsl:template match="/">
        <xsl:element name="r&#x133;k"/>
    </xsl:template>
</xsl:stylesheet>'));
        $this->assertFalse($this->i->transformToXML(@DOMDocument::loadXML('<a/>')), report_errors($this->i));
        $this->assertEquals(array('level' => 3, 'code' => 'XTDE0820', 'column' => null, 'file' => null, 'line' => 5, 'message' => 'Element name <r\u0133k> is not a valid QName'), get_object_vars($this->i->getErrors(-1)), report_errors($this->i));
        $this->i->clearErrors();

        $this->assertTrue($this->i->setParameter(null, 'XML_VERSION', '1.1', 'OPTION'), report_errors($this->i));
        $this->assertEquals('<?xml version="1.1" encoding="utf-8"?><r' . html_entity_decode('&#x133;', ENT_NOQUOTES, 'UTF-8') . 'k/>', $this->i->transformToXML(@DOMDocument::loadXML('<a/>')), report_errors($this->i));
        $this->i->clearErrors();

        $this->assertTrue($this->i->setParameter(null, 'XML_VERSION', '1.0', 'OPTION'), report_errors($this->i));
        $this->assertFalse($this->i->transformToXML(@DOMDocument::loadXML('<a/>')), report_errors($this->i));
        $this->assertEquals(array('level' => 3, 'code' => 'XTDE0820', 'column' => null, 'file' => null, 'line' => 5, 'message' => 'Element name <r\u0133k> is not a valid QName'), get_object_vars($this->i->getErrors(-1)), report_errors($this->i));
    }

    public function testSAXON9_Options_XML_VERSION() {
        $this->i->importStylesheet(@DOMDocument::loadXML('<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="2.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:p="http://xslt2processor.sf.net">
    <xsl:output method="xml" version="1.1" encoding="utf-8"/>
    <xsl:template match="/">
        <xsl:element name="r&#x133;k"/>
    </xsl:template>
</xsl:stylesheet>'));
        $this->assertFalse($this->i->transformToXML(@DOMDocument::loadXML('<a/>')), report_errors($this->i));
        $this->assertEquals(array('level' => 3, 'code' => 'XTDE0820', 'column' => '34', 'file' => null, 'line' => 5, 'message' => 'Element name <r\u0133k> is not a valid QName'), get_object_vars($this->i->getErrors(-1)), report_errors($this->i));
        $this->i->clearErrors();

        $this->assertTrue($this->i->setParameter(null, 'XML_VERSION', '1.1', 'OPTION'), report_errors($this->i));
        $this->assertEquals('<?xml version="1.1" encoding="utf-8"?><r' . html_entity_decode('&#x133;', ENT_NOQUOTES, 'UTF-8') . 'k/>', $this->i->transformToXML(@DOMDocument::loadXML('<a/>')), report_errors($this->i));
        $this->i->clearErrors();

        $this->assertTrue($this->i->setParameter(null, 'XML_VERSION', '1.0', 'OPTION'), report_errors($this->i));
        $this->assertFalse($this->i->transformToXML(@DOMDocument::loadXML('<a/>')), report_errors($this->i));
        $this->assertEquals(array('level' => 3, 'code' => 'XTDE0820', 'column' => '34', 'file' => null, 'line' => 5, 'message' => 'Element name <r\u0133k> is not a valid QName'), get_object_vars($this->i->getErrors(-1)), report_errors($this->i));
    }

    public function testSAXON_JAVACLI_Options_DISABLE_EXTENSION_FUNCTIONS() {
        $this->assertTrue($this->i->importStylesheet(@DOMDocument::loadXML('<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="2.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="text" encoding="utf-8"/>
    <xsl:template match="/">
        <xsl:value-of select="S:new(\'Hello JAVA!\')" xmlns:S="java:java.lang.String"/>
    </xsl:template>
</xsl:stylesheet>')),
        report_errors($this->i));
        $this->assertEquals('Hello JAVA!', $this->i->transformToXML(@DOMDocument::loadXML('<a/>')), report_errors($this->i));
        $this->assertTrue($this->i->setParameter(null, 'DISABLE_EXTENSION_FUNCTIONS', false, 'OPTION'), report_errors($this->i));
        $this->assertEquals('Hello JAVA!', $this->i->transformToXML(@DOMDocument::loadXML('<a/>')), report_errors($this->i));
        $this->assertTrue($this->i->setParameter(null, 'DISABLE_EXTENSION_FUNCTIONS', true, 'OPTION'), report_errors($this->i));
        $this->assertFalse($this->i->transformToXML(@DOMDocument::loadXML('<a/>')), report_errors($this->i));
    }

    public function testSAXON_CLI_Options_DISABLE_EXTENSION_FUNCTIONS() {
        $this->assertTrue($this->i->importStylesheet(@DOMDocument::loadXML('<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="2.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="text" encoding="utf-8"/>
    <xsl:template match="/">
        <xsl:value-of select="Date:Now()" xmlns:Date="clitype:System.DateTime"/>
    </xsl:template>
</xsl:stylesheet>')),
        report_errors($this->i));
        $this->assertEquals(date('d'), $this->i->transformToXML(@DOMDocument::loadXML('<a/>')), report_errors($this->i));
        $this->assertTrue($this->i->setParameter(null, 'DISABLE_EXTENSION_FUNCTIONS', false, 'OPTION'), report_errors($this->i));
        $this->assertEquals(date('d'), $this->i->transformToXML(@DOMDocument::loadXML('<a/>')), report_errors($this->i));
        $this->assertTrue($this->i->setParameter(null, 'DISABLE_EXTENSION_FUNCTIONS', true, 'OPTION'), report_errors($this->i));
        $this->assertFalse($this->i->transformToXML(@DOMDocument::loadXML('<a/>')), report_errors($this->i));
    }
}

class testSAXON8_CLI extends XML_XSLT2Processor_Test {
    public function setUp()
    {
        try {
            $this->i = new XML_XSLT2Processor('SAXON8', $this->processorPaths[get_class($this)])/*, 'CLI')*/;
        }
        catch(Exception $e) {
            $this->markTestSkipped('SAXON 8 (Transform.exe) is not available from the PATH. Message from exception:' . $e->getMessage());
        }
    }
}

class testSAXON9_CLI extends XML_XSLT2Processor_Test {
    public function setUp()
    {
        try {
            $this->i = new XML_XSLT2Processor('SAXON9', $this->processorPaths[get_class($this)])/*, 'CLI')*/;
        }
        catch(Exception $e) {
            $this->markTestSkipped('SAXON 9 (Transform.exe) is not available from the PATH. Message from exception:' . $e->getMessage());
        }
    }
}

class testSAXON9he_CLI extends XML_XSLT2Processor_Test {
    public function setUp()
    {
        try {
            $this->i = new XML_XSLT2Processor('SAXON9he', $this->processorPaths[get_class($this)])/*, 'CLI')*/;
        }
        catch(Exception $e) {
            $this->markTestSkipped('SAXON 9he (Transform.exe) is not available from the PATH. Message from exception:' . $e->getMessage());
        }
    }
}

class testSAXON_CLI extends XML_XSLT2Processor_Test {
    public function setUp()
    {
        try {
            $this->i = new XML_XSLT2Processor('SAXON', $this->processorPaths[get_class($this)])/*, 'CLI')*/;
        }
        catch(Exception $e) {
            $this->markTestSkipped('SAXON * (Transform.exe) is not available from the PATH. Message from exception:' . $e->getMessage());
        }
    }
}

class testSAXON8_JAVACLI extends XML_XSLT2Processor_Test {
    public function setUp()
    {
        try {
            $this->i = new XML_XSLT2Processor('SAXON8',
                    array(
                        'processor' => $this->processorPaths[get_class($this)],
                        'runtime' => $this->jre
                    ), 'JAVA-CLI');
        }
        catch(Exception $e) {
            $this->markTestSkipped('SAXON 8 (saxon8.jar) is not available from the PATH. Message from exception:' . $e->getMessage());
        }
    }
}

class testSAXON9_JAVACLI extends XML_XSLT2Processor_Test {
    public function setUp()
    {
        try {
            $this->i = new XML_XSLT2Processor('SAXON9',
                    array(
                        'processor' => $this->processorPaths[get_class($this)],
                        'runtime' => $this->jre
                    ), 'JAVA-CLI');
        }
        catch(Exception $e) {
            $this->markTestSkipped('SAXON 9 (saxon9.jar) is not available from the PATH. Message from exception:' . $e->getMessage());
        }
    }
}

class testSAXON9he_JAVACLI extends XML_XSLT2Processor_Test {
    public function setUp()
    {
        try {
            $this->i = new XML_XSLT2Processor('SAXON9he',
                    array(
                        'processor' => $this->processorPaths[get_class($this)],
                        'runtime' => $this->jre
                    ), 'JAVA-CLI');
        }
        catch(Exception $e) {
            $this->markTestSkipped('SAXON 9 (saxon9he.jar) is not available from the PATH. Message from exception:' . $e->getMessage());
        }
    }
}

class testSAXON_JAVACLI extends XML_XSLT2Processor_Test {
    public function setUp()
    {
        try {
            $this->i = new XML_XSLT2Processor('SAXON',
                    array(
                        'processor' => $this->processorPaths[get_class($this)],
                        'runtime' => $this->jre
                    ), 'JAVA-CLI');
        }
        catch(Exception $e) {
            $this->markTestSkipped('SAXON * (saxon*.jar) is not available from the PATH. Message from exception:' . $e->getMessage());
        }
    }
}

class testSAXON_JAVA extends XML_XSLT2Processor_Test {
    public function setUp()
    {
        try {
            $this->i = new XML_XSLT2Processor('SAXON', null, 'JAVA');
        }
        catch(Exception $e) {
            $this->markTestSkipped('SAXON * (saxon*.jar) is not available from the PATH. Message from exception:' . $e->getMessage());
        }
    }
}

class testAltovaXML_CLI extends XML_XSLT2Processor_Test {
    public function setUp()
    {
        try {
            $this->i = new XML_XSLT2Processor('AltovaXML', $this->processorPaths[get_class($this)])/*, 'CLI')*/;
        }
        catch(Exception $e) {
            $this->markTestSkipped('AltovaXML (AltovaXML.exe) is not available from the PATH. Message from exception:' . $e->getMessage());
        }
    }
}

class testAltovaXML_COM extends XML_XSLT2Processor_Test {
    public function setUp()
    {
        try {
            $this->i = new XML_XSLT2Processor('AltovaXML', $this->processorPaths[get_class($this)], 'COM');
        }
        catch(Exception $e) {
            $this->markTestSkipped(($e->getCode() === 6 ?  $e->getMessage() : 'AltovaXML is not registered as a COM component or it can not be instantiated. Message from exception:' . $e->getMessage()));
        }
    }
}

class Common_Tests {
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tests independant from the processor');
        $suite->addTest(new XML_XSLT2Processor_Test('testInvalidProcessor'));
        $suite->addTest(new XML_XSLT2Processor_Test('testInvalidPath'));
        $suite->addTest(new XML_XSLT2Processor_Test('testInvalidInterface'));
        $suite->addTest(new XML_XSLT2Processor_Test('testValidInterfaceForWrongProcessor'));
        return $suite;
    }
}

class AltovaXML_CLI_Tests {
    const instance = 'testAltovaXML_CLI';
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('AltovaXML on the CLI interface tests');
        $suite->addTestSuite(Common_Tests::suite());
        $suite->addTestSuite(All_Tests::main(self::instance));
        $suite->addTestSuite(AltovaXML_Tests::main(self::instance));
        $suite->addTestSuite(self::main());
        return $suite;
    }
    public static function main()
    {
        $instance = self::instance;
        $suite = new PHPUnit_Framework_TestSuite('Tests applicable only for AltovaXML on the CLI interface');
        return $suite;
    }
}
class AltovaXML_COM_Tests {
    const instance = 'testAltovaXML_COM';
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('AltovaXML on the COM interface tests');
        $suite->addTestSuite(Common_Tests::suite());
        $suite->addTestSuite(All_Tests::main(self::instance));
        $suite->addTestSuite(AltovaXML_Tests::main(self::instance));
        $suite->addTestSuite(self::main());
        return $suite;
    }
    public static function main()
    {
        $instance = self::instance;
        $suite = new PHPUnit_Framework_TestSuite('Tests applicable only for AltovaXML on the COM interface');
        return $suite;
    }
}
class AltovaXML_Tests {
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('AltovaXML tests');
        $suite->addTestSuite(Common_Tests::suite());
        $suite->addTestSuite(All_Tests::main('testAltovaXML_CLI'));
        $suite->addTestSuite(All_Tests::main('testAltovaXML_COM'));
        $suite->addTestSuite(self::main('testAltovaXML_CLI'));
        $suite->addTestSuite(self::main('testAltovaXML_COM'));
        $suite->addTestSuite(AltovaXML_CLI_Tests::main());
        $suite->addTestSuite(AltovaXML_COM_Tests::main());
        return $suite;
    }
    public static function main($instance)
    {
        $suite = new PHPUnit_Framework_TestSuite("Tests applicable only for AltovaXML (running as $instance)");
        $suite->addTest(new $instance('testHasExsltSupportAltova'));
        $suite->addTest(new $instance('testSetParameterModeEXPRESSION'));
        $suite->addTest(new $instance('testGetErrorsInternals207altova'));
        $suite->addTest(new $instance('testGetErrorsInternals401'));
        $suite->addTest(new $instance('testGetErrorsInternals501'));
        $suite->addTest(new $instance('testGetErrorsAltova'));
        $suite->addTest(new $instance('testSetAndGetOptionAltova'));
        $suite->addTest(new $instance('testSetAndRemoveOptionAltova'));
        $suite->addTest(new $instance('testAltovaXML_Options_XSLT_STACK_SIZE'));
        return $suite;
    }
}
class SAXON8_CLI_Tests {
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('SAXON 8 on the CLI interface tests');
        $suite->addTestSuite(Common_Tests::suite());
        $suite->addTestSuite(All_Tests::main('testSAXON8_CLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON8_CLI'));
        $suite->addTestSuite(SAXON_CLI_Tests::main('testSAXON8_JAVA'));
        $suite->addTestSuite(self::main());
        return $suite;
    }
    public static function main()
    {
        $instance = 'testSAXON8_CLI';
        $suite = new PHPUnit_Framework_TestSuite('Tests applicable only for SAXON 8 on the CLI interface');
        $suite->addTest(new $instance('testSAXON_Options_STRIP_WHITE_SPACE_CLI'));
        return $suite;
    }
}
class SAXON9_CLI_Tests {
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('SAXON 9 on the CLI interface tests');
        $suite->addTestSuite(Common_Tests::suite());
        $suite->addTestSuite(All_Tests::main('testSAXON9_CLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON9_CLI'));
        $suite->addTestSuite(SAXON_CLI_Tests::main('testSAXON9_CLI'));
        $suite->addTestSuite(self::main());
        return $suite;
    }
    public static function main()
    {
        $instance = 'testSAXON9_CLI';
        $suite = new PHPUnit_Framework_TestSuite('Tests applicable only for SAXON 9 on the CLI interface');
        $suite->addTest(new $instance('testSAXON_Options_STRIP_WHITE_SPACE_CLI'));
        return $suite;
    }
}
class SAXON9he_CLI_Tests {
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('SAXON 9he on the CLI interface tests');
        $suite->addTestSuite(Common_Tests::suite());
        $suite->addTestSuite(All_Tests::main('testSAXON9he_CLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON9he_CLI'));
        $suite->addTestSuite(SAXON_CLI_Tests::main('testSAXON9he_CLI'));
        $suite->addTestSuite(self::main());
        return $suite;
    }
    public static function main()
    {
        $instance = 'testSAXON9he_CLI';
        $suite = new PHPUnit_Framework_TestSuite('Tests applicable only for SAXON 9he on the CLI interface');
        return $suite;
    }
}
class SAXON8_JAVACLI_Tests {
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('SAXON 8 on the JAVA-CLI interface tests');
        $suite->addTestSuite(Common_Tests::suite());
        $suite->addTestSuite(All_Tests::main('testSAXON8_JAVACLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON8_JAVACLI'));
        $suite->addTestSuite(SAXON_JAVACLI_Tests::main('testSAXON8_JAVACLI'));
        $suite->addTestSuite(self::main());
        return $suite;
    }
    public static function main()
    {
        $instance = 'testSAXON8_JAVACLI';
        $suite = new PHPUnit_Framework_TestSuite('Tests applicable only for SAXON 8 on the JAVA-CLI interface');
        $suite->addTest(new $instance('testSAXON_JAVACLI_Options_DISABLE_EXTENSION_FUNCTIONS'));
        return $suite;
    }
}
class SAXON9_JAVACLI_Tests {
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('SAXON 9 on the JAVA-CLI interface tests');
        $suite->addTestSuite(Common_Tests::suite());
        $suite->addTestSuite(All_Tests::main('testSAXON9_JAVACLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON9_JAVACLI'));
        $suite->addTestSuite(SAXON_JAVACLI_Tests::main('testSAXON9_JAVACLI'));
        $suite->addTestSuite(self::main());
        return $suite;
    }
    public static function main()
    {
        $instance = 'testSAXON9_JAVACLI';
        $suite = new PHPUnit_Framework_TestSuite('Tests applicable only for SAXON 9 on the JAVA-CLI interface');
        $suite->addTest(new $instance('testGetErrorsSAXON9'));
        $suite->addTest(new $instance('testSAXON9_Options_XML_VERSION'));
        $suite->addTest(new $instance('testSAXON_JAVACLI_Options_DISABLE_EXTENSION_FUNCTIONS'));
        return $suite;
    }
}
class SAXON9he_JAVACLI_Tests {
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('SAXON 9he on the JAVA-CLI interface tests');
        $suite->addTestSuite(Common_Tests::suite());
        $suite->addTestSuite(All_Tests::main('testSAXON9he_JAVACLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON9he_JAVACLI'));
        $suite->addTestSuite(SAXON_JAVACLI_Tests::main('testSAXON9he_JAVACLI'));
        $suite->addTestSuite(self::main());
        return $suite;
    }
    public static function main()
    {
        $instance = 'testSAXON9he_JAVACLI';
        $suite = new PHPUnit_Framework_TestSuite('Tests applicable only for SAXON 9he on the JAVA-CLI interface');
        $suite->addTest(new $instance('testGetErrorsSAXON9'));
        $suite->addTest(new $instance('testSAXON9_Options_XML_VERSION'));
        return $suite;
    }
}
class SAXON_CLI_Tests {
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('SAXON on the CLI interface tests');
        $suite->addTestSuite(Common_Tests::suite());
        $suite->addTestSuite(All_Tests::main('testSAXON8_CLI'));
        $suite->addTestSuite(All_Tests::main('testSAXON9_CLI'));
        $suite->addTestSuite(All_Tests::main('testSAXON9he_CLI'));
        $suite->addTestSuite(All_Tests::main('testSAXON_CLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON8_CLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON9_CLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON9he_CLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON_CLI'));
        $suite->addTestSuite(self::main('testSAXON8_CLI'));
        $suite->addTestSuite(self::main('testSAXON9_CLI'));
        $suite->addTestSuite(self::main('testSAXON9he_CLI'));
        $suite->addTestSuite(self::main('testSAXON_CLI'));
        $suite->addTestSuite(SAXON8_CLI_Tests::main());
        $suite->addTestSuite(SAXON9_CLI_Tests::main());
        $suite->addTestSuite(SAXON9he_CLI_Tests::main());
        return $suite;
    }
    public static function main($instance)
    {
        $suite = new PHPUnit_Framework_TestSuite("Tests applicable only for SAXON on the CLI interface (running as $instance)");
        return $suite;
    }
}
class SAXON_JAVACLI_Tests {
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('SAXON on the JAVA-CLI interface tests');
        $suite->addTestSuite(Common_Tests::suite());
        $suite->addTestSuite(All_Tests::main('testSAXON8_JAVACLI'));
        $suite->addTestSuite(All_Tests::main('testSAXON9_JAVACLI'));
        $suite->addTestSuite(All_Tests::main('testSAXON9he_JAVACLI'));
        $suite->addTestSuite(All_Tests::main('testSAXON_JAVACLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON8_JAVACLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON9_JAVACLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON9he_JAVACLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON_JAVACLI'));
        $suite->addTestSuite(self::main('testSAXON8_JAVACLI'));
        $suite->addTestSuite(self::main('testSAXON9_JAVACLI'));
        $suite->addTestSuite(self::main('testSAXON9he_JAVACLI'));
        $suite->addTestSuite(self::main('testSAXON_JAVACLI'));
        $suite->addTestSuite(SAXON8_JAVACLI_Tests::main());
        $suite->addTestSuite(SAXON9_JAVACLI_Tests::main());
        $suite->addTestSuite(SAXON9he_JAVACLI_Tests::main());
        return $suite;
    }
    public static function main($instance)
    {
        $suite = new PHPUnit_Framework_TestSuite("Tests applicable only for SAXON on the JAVA-CLI interface (running as $instance)");
        $suite->addTest(new $instance('testGetMessagesSAXON'));
        $suite->addTest(new $instance('testSAXON_Options_STRIP_WHITE_SPACE_JAVACLI'));
        $suite->addTest(new $instance('testSAXON_Options_DTD_VALIDATION'));
        $suite->addTest(new $instance('testSAXON_Options_ERROR_LEVEL'));
        return $suite;
    }
}
class SAXON8_Tests {
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('SAXON8 tests');
        $suite->addTestSuite(Common_Tests::suite());
        $suite->addTestSuite(All_Tests::main('testSAXON8_CLI'));
        $suite->addTestSuite(All_Tests::main('testSAXON8_JAVACLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON8_CLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON8_JAVACLI'));
        $suite->addTestSuite(self::main('testSAXON8_CLI'));
        $suite->addTestSuite(self::main('testSAXON8_JAVACLI'));
        $suite->addTestSuite(SAXON_CLI_Tests::main('testSAXON8_CLI'));
        $suite->addTestSuite(SAXON_JAVACLI_Tests::main('testSAXON8_JAVACLI'));
        $suite->addTestSuite(SAXON8_CLI_Tests::main());
        $suite->addTestSuite(SAXON8_JAVACLI_Tests::main());
        return $suite;
    }
    public static function main($instance)
    {
        $suite = new PHPUnit_Framework_TestSuite("Tests applicable only for SAXON8 (running as $instance)");
        $suite->addTest(new $instance('testGetErrorsSAXON8'));
        $suite->addTest(new $instance('testSAXON8_Options_XML_VERSION'));
        return $suite;
    }
}
class SAXON9_Tests {
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('SAXON9 tests');
        $suite->addTestSuite(Common_Tests::suite());
        $suite->addTestSuite(All_Tests::main('testSAXON9_CLI'));
        $suite->addTestSuite(All_Tests::main('testSAXON9_JAVACLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON9_CLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON9_JAVACLI'));
        $suite->addTestSuite(self::main('testSAXON9_CLI'));
        $suite->addTestSuite(self::main('testSAXON9_JAVACLI'));
        $suite->addTestSuite(SAXON_CLI_Tests::main('testSAXON9_CLI'));
        $suite->addTestSuite(SAXON_JAVACLI_Tests::main('testSAXON9_JAVACLI'));
        $suite->addTestSuite(SAXON9_CLI_Tests::main());
        $suite->addTestSuite(SAXON9_JAVACLI_Tests::main());
        return $suite;
    }
    public static function main($instance)
    {
        $suite = new PHPUnit_Framework_TestSuite("Tests applicable only for SAXON9 (running as $instance)");
        return $suite;
    }
}
class SAXON9he_Tests {
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('SAXON9he tests');
        $suite->addTestSuite(Common_Tests::suite());
        $suite->addTestSuite(All_Tests::main('testSAXON9he_CLI'));
        $suite->addTestSuite(All_Tests::main('testSAXONhe9_JAVACLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON9he_CLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON9he_JAVACLI'));
        $suite->addTestSuite(self::main('testSAXON9he_CLI'));
        $suite->addTestSuite(self::main('testSAXON9he_JAVACLI'));
        $suite->addTestSuite(SAXON_CLI_Tests::main('testSAXON9he_CLI'));
        $suite->addTestSuite(SAXON_JAVACLI_Tests::main('testSAXON9he_JAVACLI'));
        $suite->addTestSuite(SAXON9he_CLI_Tests::main());
        $suite->addTestSuite(SAXON9he_JAVACLI_Tests::main());
        return $suite;
    }
    public static function main($instance)
    {
        $suite = new PHPUnit_Framework_TestSuite("Tests applicable only for SAXON9he (running as $instance)");
        return $suite;
    }
}
class SAXON_Tests {
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('SAXON tests');
        $suite->addTestSuite(Common_Tests::suite());
        $suite->addTestSuite(All_Tests::main('testSAXON8_CLI'));
        $suite->addTestSuite(All_Tests::main('testSAXON8_JAVACLI'));
        $suite->addTestSuite(All_Tests::main('testSAXON9_CLI'));
        $suite->addTestSuite(All_Tests::main('testSAXON9_JAVACLI'));
        $suite->addTestSuite(All_Tests::main('testSAXON9he_CLI'));
        $suite->addTestSuite(All_Tests::main('testSAXON9he_JAVACLI'));
        $suite->addTestSuite(All_Tests::main('testSAXON_CLI'));
        $suite->addTestSuite(All_Tests::main('testSAXON_JAVACLI'));
        $suite->addTestSuite(self::main('testSAXON8_CLI'));
        $suite->addTestSuite(self::main('testSAXON8_JAVACLI'));
        $suite->addTestSuite(self::main('testSAXON9_CLI'));
        $suite->addTestSuite(self::main('testSAXON9_JAVACLI'));
        $suite->addTestSuite(self::main('testSAXON9he_CLI'));
        $suite->addTestSuite(self::main('testSAXON9he_JAVACLI'));
        $suite->addTestSuite(self::main('testSAXON_CLI'));
        $suite->addTestSuite(self::main('testSAXON_JAVACLI'));
        $suite->addTestSuite(SAXON_CLI_Tests::main('testSAXON8_CLI'));
        $suite->addTestSuite(SAXON_CLI_Tests::main('testSAXON9_CLI'));
        $suite->addTestSuite(SAXON_CLI_Tests::main('testSAXON9he_CLI'));
        $suite->addTestSuite(SAXON_CLI_Tests::main('testSAXON_CLI'));
        $suite->addTestSuite(SAXON_JAVACLI_Tests::main('testSAXON8_JAVACLI'));
        $suite->addTestSuite(SAXON_JAVACLI_Tests::main('testSAXON9_JAVACLI'));
        $suite->addTestSuite(SAXON_JAVACLI_Tests::main('testSAXON9he_JAVACLI'));
        $suite->addTestSuite(SAXON_JAVACLI_Tests::main('testSAXON_JAVACLI'));
        $suite->addTestSuite(SAXON8_Tests::main('testSAXON8_CLI'));
        $suite->addTestSuite(SAXON8_Tests::main('testSAXON8_JAVACLI'));
        $suite->addTestSuite(SAXON9_Tests::main('testSAXON9_CLI'));
        $suite->addTestSuite(SAXON9_Tests::main('testSAXON9_JAVACLI'));
        $suite->addTestSuite(SAXON9he_Tests::main('testSAXON9he_CLI'));
        $suite->addTestSuite(SAXON9he_Tests::main('testSAXON9he_JAVACLI'));
        $suite->addTestSuite(SAXON8_CLI_Tests::main());
        $suite->addTestSuite(SAXON9_CLI_Tests::main());
        $suite->addTestSuite(SAXON9he_CLI_Tests::main());
        $suite->addTestSuite(SAXON8_JAVACLI_Tests::main());
        $suite->addTestSuite(SAXON9_JAVACLI_Tests::main());
        $suite->addTestSuite(SAXON9he_JAVACLI_Tests::main());
        return $suite;
    }
    public static function main($instance)
    {
        $suite = new PHPUnit_Framework_TestSuite("Tests applicable only for SAXON (running as $instance)");
        $suite->addTest(new $instance('testSetParameterModeOUTPUT'));
        $suite->addTest(new $instance('testHasExsltSupportSAXON'));
        $suite->addTest(new $instance('testGetErrorsInternals207saxon'));
        $suite->addTest(new $instance('testGetErrorsInternals402'));
        $suite->addTest(new $instance('testGetErrorsInternals403'));
        $suite->addTest(new $instance('testGetErrorsInternals404'));
        $suite->addTest(new $instance('testSetAndGetOptionSAXON'));
        $suite->addTest(new $instance('testSetAndRemoveOptionSAXON'));
        $suite->addTest(new $instance('testSAXON_Options_VERSION_WARNING_false'));
        $suite->addTest(new $instance('testSAXON_Options_VERSION_WARNING_true'));
        $suite->addTest(new $instance('testSAXON_Options_TREE_MODEL'));
        return $suite;
    }
}
class All_Tests {
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('All tests');
        $suite->addTestSuite(Common_Tests::suite());
        $suite->addTestSuite(self::main('testSAXON8_CLI'));
        $suite->addTestSuite(self::main('testSAXON8_JAVACLI'));
        $suite->addTestSuite(self::main('testSAXON9_CLI'));
        $suite->addTestSuite(self::main('testSAXON9_JAVACLI'));
        $suite->addTestSuite(self::main('testSAXON9he_CLI'));
        $suite->addTestSuite(self::main('testSAXON9he_JAVACLI'));
        $suite->addTestSuite(self::main('testSAXON_CLI'));
        $suite->addTestSuite(self::main('testSAXON_JAVACLI'));
        $suite->addTestSuite(self::main('testAltovaXML_CLI'));
        $suite->addTestSuite(self::main('testAltovaXML_COM'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON8_CLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON8_JAVACLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON9_CLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON9_JAVACLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON9he_CLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON9he_JAVACLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON_CLI'));
        $suite->addTestSuite(SAXON_Tests::main('testSAXON_JAVACLI'));
        $suite->addTestSuite(SAXON_CLI_Tests::main('testSAXON8_CLI'));
        $suite->addTestSuite(SAXON_CLI_Tests::main('testSAXON9_CLI'));
        $suite->addTestSuite(SAXON_CLI_Tests::main('testSAXON9he_CLI'));
        $suite->addTestSuite(SAXON_CLI_Tests::main('testSAXON_CLI'));
        $suite->addTestSuite(SAXON_JAVACLI_Tests::main('testSAXON8_JAVACLI'));
        $suite->addTestSuite(SAXON_JAVACLI_Tests::main('testSAXON9_JAVACLI'));
        $suite->addTestSuite(SAXON_JAVACLI_Tests::main('testSAXON9he_JAVACLI'));
        $suite->addTestSuite(SAXON_JAVACLI_Tests::main('testSAXON_JAVACLI'));
        $suite->addTestSuite(SAXON8_Tests::main('testSAXON8_CLI'));
        $suite->addTestSuite(SAXON8_Tests::main('testSAXON8_JAVACLI'));
        $suite->addTestSuite(SAXON9_Tests::main('testSAXON9_CLI'));
        $suite->addTestSuite(SAXON9_Tests::main('testSAXON9_JAVACLI'));
        $suite->addTestSuite(SAXON9he_Tests::main('testSAXON9he_CLI'));
        $suite->addTestSuite(SAXON9he_Tests::main('testSAXON9he_JAVACLI'));
        $suite->addTestSuite(SAXON8_CLI_Tests::main());
        $suite->addTestSuite(SAXON8_JAVACLI_Tests::main());
        $suite->addTestSuite(SAXON9_CLI_Tests::main());
        $suite->addTestSuite(SAXON9_JAVACLI_Tests::main());
        $suite->addTestSuite(SAXON9he_CLI_Tests::main());
        $suite->addTestSuite(SAXON9he_JAVACLI_Tests::main());
        $suite->addTestSuite(AltovaXML_Tests::main('testAltovaXML_CLI'));
        $suite->addTestSuite(AltovaXML_Tests::main('testAltovaXML_COM'));
        $suite->addTestSuite(AltovaXML_CLI_Tests::main());
        $suite->addTestSuite(AltovaXML_COM_Tests::main());
        return $suite;
    }
    public static function main($instance)
    {
        $suite = new PHPUnit_Framework_TestSuite("Tests applicable for all processors and interfaces (running as $instance)");
        $suite->addTest(new $instance('testSimpleTransformation'));
        $suite->addTest(new $instance('testSimpleDomTransformation'));
        $suite->addTest(new $instance('testSimpleUriTransformation'));
        $suite->addTest(new $instance('testMissingStylesheet'));
        $suite->addTest(new $instance('testMissingSource'));
        $suite->addTest(new $instance('testDomStylesheet'));
        $suite->addTest(new $instance('testDomModifiedStylesheet'));
        $suite->addTest(new $instance('testDomSource'));
        $suite->addTest(new $instance('testDomModifiedSource'));
        $suite->addTest(new $instance('testDomStylesheetOnTheFly'));
        $suite->addTest(new $instance('testDomSourceOnTheFly'));
        $suite->addTest(new $instance('testSetAndGetParameter'));
        $suite->addTest(new $instance('testSetAndRemoveParameter'));
        $suite->addTest(new $instance('testRemoveParameterFailure'));
        $suite->addTest(new $instance('testSetParameterModeSTRING'));
        $suite->addTest(new $instance('testSetParameterModeSAFE_STRING'));
        $suite->addTest(new $instance('testSetParameterModeFILE'));
        $suite->addTest(new $instance('testSetParameterModeUNKNOWN'));
        $suite->addTest(new $instance('testAutoStylesheetByPI'));
        $suite->addTest(new $instance('testDomAutoStylesheetByPI'));
        $suite->addTest(new $instance('testDomOnTheFlyAutoStylesheetByPI'));
        $suite->addTest(new $instance('testGetErrorsFalseWhenEmpty'));
        $suite->addTest(new $instance('testGetErrorsInternals101'));
        $suite->addTest(new $instance('testGetErrorsInternals102'));
        $suite->addTest(new $instance('testGetErrorsInternals201'));
        $suite->addTest(new $instance('testGetErrorsInternals202'));
        $suite->addTest(new $instance('testGetErrorsInternals203'));
        $suite->addTest(new $instance('testGetErrorsInternals204'));
        $suite->addTest(new $instance('testGetErrorsInternals205'));
        $suite->addTest(new $instance('testGetErrorsInternals206'));
        $suite->addTest(new $instance('testGetErrorsInternals405'));
        $suite->addTest(new $instance('testSetOptionPropertyToUnknown'));
        $suite->addTest(new $instance('testRemoveOptionFailure'));
        return $suite;
    }
}

if (defined('XML_XSLT2Processor_SelfAssigned') && defined('PHPUnit_MAIN_METHOD')) {
    $mainSuite = PHPUnit_MAIN_METHOD;
    $suite = new $mainSuite;
    $test = new PHPUnit_TextUI_TestRunner;
    $test->run($suite->suite(),
            array(
                'verbose' => true,
                'stop-on-failure' => false,
                )
            );
}
?>
