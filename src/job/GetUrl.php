<?php

namespace marvin255\fias\job;

use marvin255\fias\pipe\FlowInterface;
use SoapClient;

/**
 * Задача, которая получает url файла с обновлениями из soap-сервиса ФИАС.
 */
class GetUrl implements JobInterface
{
    /**
     * Объект soap-клиента для получения обновлений.
     *
     * @var \SoapClient
     */
    protected $client = null;
    /**
     * Номер текущей версии, для которой нужно загрузить дельты.
     *
     * @var string
     */
    protected $fiasVersion = null;

    /**
     * Конструктор.
     * Задает ссылку на объект SoapClient, которым будет пользоваться задача.
     * Задает номер текущей версии, для которой будет загружена дельта изменений.
     *
     * @param \SoapClient $client
     * @param int         $fiasVersion
     */
    public function __construct(SoapClient $client, $fiasVersion = null)
    {
        $this->client = $client;
        $this->fiasVersion = $fiasVersion;
    }

    /**
     * @inheritdoc
     */
    public function run(FlowInterface $flow)
    {
        if ($this->fiasVersion === null) {
            $return = $this->getFull($flow);
        } else {
            $return = $this->getDelta($flow, $this->fiasVersion);
        }

        return $return;
    }

    /**
     * Возвращает ссылку на полную базу данных ФИАС в формате xml.
     *
     * @param \marvin255\fias\pipe\FlowInterface $flow
     *
     * @return bool
     */
    protected function getFull(FlowInterface $flow)
    {
        $res = $this->client->__soapCall('GetLastDownloadFileInfo ', []);
        $flow->set(
            'download_url',
            $res->GetLastDownloadFileInfoResult->FiasCompleteXmlUrl
        );
        $flow->set(
            'fias_version',
            $res->GetLastDownloadFileInfoResult->VersionId
        );

        return true;
    }

    /**
     * Вовзращает ссылку на дельту между указанной версией и последующей.
     *
     * @param \marvin255\fias\pipe\FlowInterface $flow
     * @param int                                $fiasVersion
     *
     * @return bool
     */
    protected function getDelta(FlowInterface $flow, $fiasVersion)
    {
        $return = false;

        $res = $this->client->__soapCall('GetAllDownloadFileInfo', []);
        $versions = [];
        $versionsSort = [];
        foreach ($res->GetAllDownloadFileInfoResult->DownloadFileInfo as $key => $version) {
            $versions[$key] = (array) $version;
            $versionsSort[$key] = (int) $version->VersionId;
        }
        array_multisort($versionsSort, SORT_ASC, $versions);

        foreach ($versions as $version) {
            if ($version['VersionId'] <= $fiasVersion) {
                continue;
            }
            $return = true;
            $flow->set('download_url', $version['FiasDeltaXmlUrl']);
            $flow->set('fias_version', $version['VersionId']);
            break;
        }

        return $return;
    }
}
