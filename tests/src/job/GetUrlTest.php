<?php

namespace marvin255\fias\tests\job;

class GetUrlTest extends \PHPUnit_Framework_TestCase
{
    public function testRunFull()
    {
        $soapResponse = new \stdClass;
        $soapResponse->GetLastDownloadFileInfoResult = new \stdClass;
        $soapResponse->GetLastDownloadFileInfoResult->FiasCompleteXmlUrl = 'FiasCompleteXmlUrl_' . mt_rand();
        $soapResponse->GetLastDownloadFileInfoResult->VersionId = 'VersionId_' . mt_rand();

        $soap = $this->getMockBuilder('\SoapClient')
            ->disableOriginalConstructor()
            ->getMock();
        $soap->expects($this->once())
            ->method('__soapCall')
            ->with($this->equalTo('GetLastDownloadFileInfo '), $this->equalTo([]))
            ->will($this->returnValue($soapResponse));

        $params = [];
        $flow = $this->getMockBuilder('\marvin255\fias\pipe\FlowInterface')
            ->getMock();
        $flow->method('set')
            ->will($this->returnCallback(function ($name, $value) use (&$params) {
                $params[$name] = $value;
            }));

        $job = new \marvin255\fias\job\GetUrl($soap);

        $this->assertSame(
            true,
            $job->run($flow)
        );
        ksort($params);
        $this->assertSame(
            [
                'download_url' => $soapResponse->GetLastDownloadFileInfoResult->FiasCompleteXmlUrl,
                'fias_version' => $soapResponse->GetLastDownloadFileInfoResult->VersionId,
            ],
            $params
        );
    }

    public function testRunDelta()
    {
        $fiasVersions = [
            new \stdClass,
            new \stdClass,
            new \stdClass,
        ];
        $fiasVersions[0]->VersionId = 3;
        $fiasVersions[0]->FiasDeltaXmlUrl = 'FiasDeltaXmlUrl_0_' . mt_rand();
        $fiasVersions[1]->VersionId = 1;
        $fiasVersions[1]->FiasDeltaXmlUrl = 'FiasDeltaXmlUrl_1_' . mt_rand();
        $fiasVersions[2]->VersionId = 2;
        $fiasVersions[2]->FiasDeltaXmlUrl = 'FiasDeltaXmlUrl_2_' . mt_rand();

        $soapResponse = new \stdClass;
        $soapResponse->GetAllDownloadFileInfoResult = new \stdClass;
        $soapResponse->GetAllDownloadFileInfoResult->DownloadFileInfo = $fiasVersions;

        $soap = $this->getMockBuilder('\SoapClient')
            ->disableOriginalConstructor()
            ->getMock();
        $soap->expects($this->once())
            ->method('__soapCall')
            ->with($this->equalTo('GetAllDownloadFileInfo'), $this->equalTo([]))
            ->will($this->returnValue($soapResponse));

        $params = [];
        $flow = $this->getMockBuilder('\marvin255\fias\pipe\FlowInterface')
            ->getMock();
        $flow->method('set')
            ->will($this->returnCallback(function ($name, $value) use (&$params) {
                $params[$name] = $value;
            }));

        $job = new \marvin255\fias\job\GetUrl($soap, 2);

        $this->assertSame(
            true,
            $job->run($flow)
        );
        ksort($params);
        $this->assertSame(
            [
                'download_url' => $fiasVersions[0]->FiasDeltaXmlUrl,
                'fias_version' => $fiasVersions[0]->VersionId,
            ],
            $params
        );
    }

    public function testRunEmptyDelta()
    {
        $fiasVersions = [
            new \stdClass,
            new \stdClass,
            new \stdClass,
        ];
        $fiasVersions[0]->VersionId = 3;
        $fiasVersions[0]->FiasDeltaXmlUrl = 'FiasDeltaXmlUrl_0_' . mt_rand();
        $fiasVersions[1]->VersionId = 1;
        $fiasVersions[1]->FiasDeltaXmlUrl = 'FiasDeltaXmlUrl_1_' . mt_rand();
        $fiasVersions[2]->VersionId = 2;
        $fiasVersions[2]->FiasDeltaXmlUrl = 'FiasDeltaXmlUrl_2_' . mt_rand();

        $soapResponse = new \stdClass;
        $soapResponse->GetAllDownloadFileInfoResult = new \stdClass;
        $soapResponse->GetAllDownloadFileInfoResult->DownloadFileInfo = $fiasVersions;

        $soap = $this->getMockBuilder('\SoapClient')
            ->disableOriginalConstructor()
            ->getMock();
        $soap->expects($this->once())
            ->method('__soapCall')
            ->with($this->equalTo('GetAllDownloadFileInfo'), $this->equalTo([]))
            ->will($this->returnValue($soapResponse));

        $params = [];
        $flow = $this->getMockBuilder('\marvin255\fias\pipe\FlowInterface')
            ->getMock();
        $flow->method('set')
            ->will($this->returnCallback(function ($name, $value) use (&$params) {
                $params[$name] = $value;
            }));

        $job = new \marvin255\fias\job\GetUrl($soap, 4);

        $this->assertSame(
            false,
            $job->run($flow)
        );
        $this->assertSame(
            [],
            $params
        );
    }
}
