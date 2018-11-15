<?php

declare(strict_types=1);

namespace marvin255\fias\mapper;

use SimpleXMLElement;
use Throwable;
use RuntimeException;

/**
 * Базовый класс для универсального маппера.
 */
abstract class AbstractMapper implements SqlMapperInterface, XmlMapperInterface
{
    /**
     * Флаг, который указывает, что поля были закешированы.
     *
     * @var bool
     */
    protected $isMapCached = false;
    /**
     * Закешированный массив сущностей.
     *
     * @var \marvin255\fias\mapper\FieldInterface[]
     */
    protected $cachedMap = [];

    /**
     * Создает список полей и возвращает ассоциативный массив, в котором ключами
     * служат названия полей, а значениями - объекты FieldInterface.
     *
     * @return \marvin255\fias\mapper\FieldInterface[]
     */
    abstract protected function createFields(): array;

    /**
     * @inhertitdoc
     */
    public function getMap(): array
    {
        if (!$this->isMapCached) {
            $this->isMapCached = true;
            $this->cachedMap = $this->createFields();
        }

        return $this->cachedMap;
    }

    /**
     * @inhertitdoc
     */
    public function mapArray(array $messyArray): array
    {
        $map = $this->getMap();
        $mappedArray = [];

        foreach ($map as $fieldName => $field) {
            $mappedArray[$fieldName] = $messyArray[$fieldName] ?? null;
        }

        return $mappedArray;
    }

    /**
     * @inhertitdoc
     */
    public function convertToStrings(array $messyArray): array
    {
        $map = $this->getMap();
        $convertedArray = [];

        foreach ($messyArray as $fieldName => $value) {
            $convertedArray[$fieldName] = isset($map[$fieldName])
                ? $map[$fieldName]->convertToString($value)
                : $value;
        }

        return $convertedArray;
    }

    /**
     * @inhertitdoc
     */
    public function mapPrimaries(array $messyArray): array
    {
        $primaries = $this->getSqlPrimary();
        $primariesArray = [];

        foreach ($primaries as $primary) {
            $primariesArray[$primary] = $messyArray[$primary] ?? null;
        }

        return $primariesArray;
    }

    /**
     * @inhertitdoc
     */
    public function mapNotPrimaries(array $messyArray): array
    {
        $map = $this->getMap();
        $primaries = $this->getSqlPrimary();
        $notPrimariesArray = [];

        foreach ($messyArray as $fieldName => $value) {
            if (isset($map[$fieldName]) && !in_array($fieldName, $primaries)) {
                $notPrimariesArray[$fieldName] = $value;
            }
        }

        return $notPrimariesArray;
    }

    /**
     * {@inhertitdoc}.
     *
     * @throws \RuntimeException
     */
    public function extractArrayFromXml(string $xml): array
    {
        $return = [];

        try {
            $attributes = $this->convertStringToSimpleXml($xml)->attributes();
            foreach ($this->getMap() as $fieldName => $field) {
                $value = (string) $attributes[$fieldName] ?? '';
                $return[$fieldName] = $field->convertToData($value);
            }
        } catch (Throwable $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function getXmlPath(): string
    {
        return '';
    }

    /**
     * @inhertitdoc
     */
    public function getInsertFileMask(): string
    {
        return '';
    }

    /**
     * @inhertitdoc
     */
    public function getDeleteFileMask(): string
    {
        return '';
    }

    /**
     * @inhertitdoc
     */
    public function getSqlName(): string
    {
        return trim(str_replace('\\', '_', strtolower(get_class($this))), '_');
    }

    /**
     * @inhertitdoc
     */
    public function getSqlIndexes(): array
    {
        return [];
    }

    /**
     * @inhertitdoc
     */
    public function getSqlPartitionsCount(): int
    {
        return 1;
    }

    /**
     * @inhertitdoc
     */
    public function getSqlPartitionField(): string
    {
        return '';
    }

    /**
     * Преобразует строку xml в объект SimpleXml.
     *
     * @param string $xml
     *
     * @return \SimpleXMLElement
     *
     * @throws \RuntimeException
     */
    protected function convertStringToSimpleXml(string $xml): SimpleXMLElement
    {
        libxml_use_internal_errors(true);

        $return = simplexml_load_string($xml);

        if (!($return instanceof SimpleXMLElement) || libxml_get_errors()) {
            $exceptionMessages = [];
            foreach (libxml_get_errors() as $error) {
                $exceptionMessages[] = $error->message;
            }
            libxml_clear_errors();
            throw new RuntimeException(implode(', ', $exceptionMessages));
        }

        libxml_use_internal_errors(false);

        return $return;
    }
}
