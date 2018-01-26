<?php

namespace Asimlqt\MailchimpExport\Writer;

use PDO;
use PDOStatement;
use Asimlqt\MailchimpExport\Writer;

class DatabaseWriter implements Writer
{
    /**
     * @var PDO
     */
    private $conn;

    /**
     * @var string
     */
    private $table;

    /**
     * @var array
     */
    private $mapping;

    /**
     * @var int
     */
    private $batchSize = 100;

    /**
     * @var bool
     */
    private $tableTruncated = false;

    public function __construct(PDO $pdo, string $table, array $mapping)
    {
        $this->conn = $pdo;
        $this->table = $table;
        $this->mapping = $mapping;
    }

    public function setBatchSize(int $batchSize)
    {
        $this->batchSize = $batchSize;
    }

    public function write(array $data)
    {
        if (empty($data)) {
            return;
        }

        $this->truncate();

        $columns = $this->getMappedFields($data[0]);

        foreach (array_chunk($data, $this->batchSize) as $chunk) {
            $stmt = $this->prepareStatement($columns, count($chunk));
            $stmt->execute($this->getBindValues($chunk));
        }
    }

    private function truncate()
    {
        if (!$this->tableTruncated) {
            $this->conn->query("truncate table $this->table");
            $this->tableTruncated = true;
        }
    }

    /**
     * Gets the mapped fields in the correct order which migh tbe different to
     * the order of the results supplied by mailchimp.
     */
    private function getMappedFields(array $row): array
    {
        return array_merge(
            array_intersect_key($row, $this->mapping),
            $this->mapping
        );
    }

    private function prepareStatement($cols, $chunkSize): PDOStatement
    {
        return $this->conn->prepare(sprintf(
            "INSERT INTO %s (%s) values %s",
            $this->table,
            implode(",", $cols),
            implode(",", array_fill(0, $chunkSize, $this->createRowPlaceholders(count($cols))))
        ));
    }

    private function createRowPlaceholders(int $num): string
    {
        return sprintf(
            "(%s)",
            implode(",", array_fill(0, $num, "?"))
        );
    }

    private function getBindValues(array $chunk): array
    {
        $bindValues = [];
        foreach ($chunk as $row) {
            foreach (array_intersect_key($row, $this->mapping) as $value) {
                $bindValues[] = $value;
            }
        }

        return $bindValues;
    }

}
