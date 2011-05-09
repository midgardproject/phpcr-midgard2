<?php
require_once('Midgard2XMLImporter.php');

/**
 * Handles basic importing and exporting of fixtures into Midgard2
 */
class Midgard2ImportExport implements phpcrApiTestSuiteImportExportFixtureInterface
{
    protected $fixturePath;

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

        $importer = new Midgard2XMLImporter($fixture);
        $importer->execute();

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
