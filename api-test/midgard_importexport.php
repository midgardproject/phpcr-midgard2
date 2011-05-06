<?php

require_once 'APITestXMLImporter.php';

/**
 * Basic interface that is to be implemented by Implementations willing to test
 * against the API testsuite.
 */
interface __phpcrApiTestSuiteImportExportFixtureInterface
{
    /**
     * Required fixtures (see fixtures/ folder for the necessary data)
     *
     * nodetype/base
     * read/access/base
     * read/export/base
     * read/read/base
     * read/search/base
     * read/search/query
     * version/base
     * write/manipulation/add
     * write/manipulation/copy
     * write/manipulation/delete
     * write/manipulation/move
     * write/value/base
     *
     * @param string
     * @return void
     */
    public function import($fixture);
}

/**
 * Handles basic importing and exporting of fixtures trough
 * the java binary jack.jar
 *
 * Connection parameters for jackrabbit have to be set in the $GLOBALS array (i.e. in phpunit.xml)
 *     <php>
 *      <var name="jcr.url" value="http://localhost:8080/server" />
 *      <var name="jcr.user" value="admin" />
 *      <var name="jcr.pass" value="admin" />
 *      <var name="jcr.workspace" value="tests" />
 *      <var name="jcr.transport" value="davex" />
 *    </php>
 */
class midgard_importexport implements phpcrApiTestSuiteImportExportFixtureInterface
{

    protected $fixturePath;
    protected $jar;

    /**
     * @param string $fixturePath path to the fixtures directory. defaults to dirname(__FILE__) . '/../fixtures/'
     */
    public function __construct($fixturePath = null)
    {
        if (is_null($fixturePath)) {
            $this->fixturePath = dirname(__FILE__) . '/../fixtures/';
        } else {
            $this->fixturePath = $fixturePath;
        }
        if (!is_dir($this->fixturePath)) {
            throw new Exception('Not a valid directory: ' . $this->fixturePath);
        }
    }

    private function getArguments()
    {
        $args = array(
            'jcr.url' => 'storage',
            'jcr.user' => 'username',
            'jcr.pass' => 'password',
            'jcr.workspace' => 'workspace',
            'jcr.transport' => 'transport',
            'jcr.basepath' => 'repository-base-xpath',
        );
        $opts = "";
        foreach ($args AS $arg => $newArg) {
            if (isset($GLOBALS[$arg])) {
                if ($opts != "") {
                    $opts .= " ";
                }
                $opts .= " " . $newArg . "=" . $GLOBALS[$arg];
            }
        }
        return $opts;
    }

    /**
     * import the jcr dump into jackrabbit
     * @param string $fixture path to the fixture file, relative to fixturePath
     * @throws Exception if anything fails
     */
    public function import($fixture)
    {
        $fixture = $this->fixturePath . $fixture . ".xml";
        
        if (!is_readable($fixture)) {
            throw new Exception('Fixture not readable at: ' . $fixture);
        }

        $importer = new APITestXMLImporter ($fixture);
        $importer->execute ();

        return true;
    }

    /**
     * export a document view to a file
     *
     * TODO: add path parameter so you can export just content parts (exporting / exports jcr:system too, which is huge and ugly)
     * @param $file path to the file, relative to fixturePath. the file may not yet exist
     * @throws Exception if the file already exists or if the export fails
     */
    public function exportdocument($file)
    {
        $fixture = $this->fixturePath . $file;

        if (is_readable($fixture)) {
            throw new Exception('File existing: ' . $fixture);
        }

        throw new Exception ("exportdocument not implemented yet");

        return true;
    }
}
