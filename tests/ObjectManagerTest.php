<?php

namespace Midgard2CR;

class ObjectManagerTest extends TestCase
{
    public function testGetNodeByPath()
    {
        $this->markTestSkipped('No tests for this class yet');
    }

    public function testGetNodeTypes()
    {
        $this->markTestSkipped('No tests for this class yet');
    }

    public function testIsUUID()
    {
        $this->markTestSkipped('No tests for this class yet');
    }

    public function testNormalizePathUUID()
    {
        $this->markTestSkipped('No tests for this class yet');
    }

    public function testAbsolutePath($inputRoot, $inputRelPath, $output)
    {
        $this->markTestSkipped('No tests for this class yet');
    }

    public static function dataproviderAbsolutePath()
    {
        return array(
            array('/',      'foo',  '/foo'),
            array('/',      '/foo', '/foo'),
            array('',       'foo',  '/foo'),
            array('',       '/foo', '/foo'),
            array('/foo',   'bar',  '/foo/bar'),
            array('/foo',   '',     '/foo'),
            array('/foo/',  'bar',  '/foo/bar'),
            array('/foo/',  '/bar', '/foo/bar'),
            array('foo',    'bar',  '/foo/bar'),

            // normalization is also part of ::absolutePath
            array('/',          '../foo',       '/foo'),
            array('/',          'foo/../bar',   '/bar'),
            array('/',          'foo/./bar',    '/foo/bar'),
            array('/foo/nope',  '../bar',       '/foo/bar'),
            array('/foo/nope',  '/../bar',      '/foo/bar'),
        );
    }

    public function testVerifyAbsolutePath()
    {
        $this->markTestSkipped('No tests for this class yet');
    }
}
